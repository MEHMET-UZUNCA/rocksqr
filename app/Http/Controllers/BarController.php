<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MapsOrders;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\WaiterCall;
use App\Services\MssqlService;
use App\Support\ScreenCleaner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BarController extends Controller
{
    use MapsOrders;

    public function __construct(private MssqlService $mssql) {}

    public function bar()
    {
        return view('admin.bar');
    }

    public function barUpdateStatus(Request $request, Order $order)
    {
        $request->validate(['status' => 'required|in:preparing']);

        if (in_array($order->kitchen_status, ['cancelled', 'completed'], true)) {
            return response()->json(['success' => false, 'message' => 'Bu sipariş onaylanamaz.'], 422);
        }

        $order->update([
            'bar_status'    => 'approved',
            'bar_approved_at' => $order->bar_approved_at ?? now(),
            'kitchen_status'  => $order->kitchen_status === 'waiting' ? 'new' : $order->kitchen_status,
            'status'          => $order->kitchen_status === 'waiting' ? 'new' : $order->status,
            'completed_at'    => null,
        ]);

        return response()->json(['success' => true, 'status' => $order->kitchen_status]);
    }

    public function cancelOrder(Order $order)
    {
        if ($order->bar_status !== 'new') {
            return response()->json(['success' => false, 'message' => 'Bu sipariş iptal edilemez.'], 422);
        }

        $order->update([
            'status'         => 'cancelled',
            'bar_status'     => 'cancelled',
            'kitchen_status' => 'cancelled',
            'completed_at'   => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function attendWaiterCall(WaiterCall $waiterCall)
    {
        $waiterCall->markAsAttended();
        return response()->json(['success' => true]);
    }

    public function barSymphonyDelivered(Request $request)
    {
        $validated = $request->validate(['group_key' => 'required|string|max:64']);
        DB::table('kitchen_pos_completions')
            ->where('group_key', $validated['group_key'])
            ->update(['delivered_at' => now()]);
        try {
            DB::table('kitchen_item_logs')
                ->where('group_key', $validated['group_key'])
                ->whereNull('delivered_at')
                ->update(['delivered_at' => now()]);
        } catch (\Throwable) {
        }
        return response()->json(['success' => true]);
    }

    public function barApiOrders()
    {
        // Ekran temizleme saati dolduysa bu anket tetikler (gunde bir kez)
        ScreenCleaner::clearIfDue();
        $clearedAt = ScreenCleaner::clearedAt();

        $completedLimit    = (int) Setting::get('bar_completed_display', 12);
        $readyLimit        = (int) Setting::get('bar_ready_display', 12);
        $undoWindowSeconds = (int) Setting::get('ready_undo_seconds', 30);

        $orders = Order::where('bar_status', 'new')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($order) => $this->mapBarOrder($order));

        $readyOrders = Order::where('kitchen_status', 'ready')
            ->orderBy('kitchen_ready_at', 'desc')
            ->limit($readyLimit)
            ->get()
            ->map(fn ($order) => $this->mapBarOrder($order))
            ->values()
            ->all();

        // Symphony KDS onayları — delivered_at IS NULL olanlar bar "servise götür" şeridinde.
        // kind=item satırları kısmi (ürün bazlı) onaylardır: tek ürün onaylandığında da
        // bara gerçek ürün adıyla düşer. kind=check kartları canlı feed'den çözülür;
        // feed'de yoksa db_items (onay anında persist edilen ürün listesi) yedek içerik olur.
        $symphonyReady = DB::table('kitchen_pos_completions')
            ->whereNull('delivered_at')
            ->whereIn('kind', ['check', 'checkless_msg', 'item'])
            ->orderByDesc('completed_at')
            ->limit($readyLimit)
            ->get();

        foreach ($symphonyReady as $row) {
            $completedAt       = \Carbon\Carbon::parse($row->completed_at);
            $readySinceSeconds = (int) $completedAt->diffInSeconds(now());
            $undoRemaining     = max(0, $undoWindowSeconds - $readySinceSeconds);
            $itemName          = trim((string) ($row->name ?? ''));
            $qty               = (int) ($row->qty ?? 1);

            if ($row->kind === 'item') {
                $itemsArr = [['id' => null, 'name' => $itemName !== '' ? $itemName : 'Mutfak ürünü', 'quantity' => max(1, $qty)]];
                $dbItems  = null;
            } else {
                $itemsArr = [['id' => null, 'name' => 'Adisyon #' . ($row->check_number ?: '-'), 'quantity' => 1]];
                $dbItems  = $row->kind === 'check' && $itemName !== '' ? $this->namesToItems($itemName) : null;
            }

            $servedCount = count(json_decode((string) ($row->served_item_keys ?? '[]'), true) ?: []);

            $readyOrders[] = [
                'id'                  => 0,
                'source'              => 'symphony',
                'group_key'           => $row->group_key,
                'kind'                => $row->kind,
                'table_no'            => $row->table_no,
                'items'               => $itemsArr,
                'db_items'            => $dbItems,
                'waiter_name'         => null,
                'served_count'        => $servedCount,
                'total_units'         => $row->kind === 'item' ? null : $servedCount,
                'total_price'         => 0,
                'order_note'          => $itemName !== '' && $row->kind !== 'check' ? trim((string) ($row->note ?? '')) : null,
                'status'              => 'ready',
                'bar_status'          => 'approved',
                'kitchen_status'      => 'ready',
                'created_at'          => $completedAt->format('H:i:s'),
                'order_time'          => $completedAt->toIso8601String(),
                'seconds_ago'         => $readySinceSeconds,
                'preparing_seconds'   => null,
                'ready_seconds'       => $readySinceSeconds,
                'ready_since_seconds' => $readySinceSeconds,
                'can_undo_ready'      => $undoRemaining > 0,
                'undo_remaining_seconds' => $undoRemaining,
            ];
        }

        usort($readyOrders, fn($a, $b) => ($a['ready_since_seconds'] ?? 99999) <=> ($b['ready_since_seconds'] ?? 99999));
        $readyOrders = array_slice($readyOrders, 0, $readyLimit);

        // SON seridi sadece son temizlemeden sonrakileri gosterir
        $completedOrders = Order::whereIn('kitchen_status', ['completed', 'cancelled'])
            ->when($clearedAt, fn ($q) => $q->where('completed_at', '>', $clearedAt))
            ->orderBy('completed_at', 'desc')
            ->limit($completedLimit)
            ->get()
            ->map(fn ($order) => $this->mapOrder($order))
            ->values()
            ->all();

        // Symphony servis edilenleri tamamlananlara ekle
        $symphonyDelivered = DB::table('kitchen_pos_completions')
            ->whereNotNull('delivered_at')
            ->when($clearedAt, fn ($q) => $q->where('delivered_at', '>', $clearedAt))
            ->orderByDesc('delivered_at')
            ->limit($completedLimit)
            ->get();

        foreach ($symphonyDelivered as $row) {
            $deliveredAt = \Carbon\Carbon::parse($row->delivered_at);
            $itemName    = trim((string) ($row->name ?? ''));
            $qty         = (int) ($row->qty ?? 1);

            $itemsArr = $row->kind === 'check'
                ? [['id' => null, 'name' => 'Adisyon #' . ($row->check_number ?: '-'), 'quantity' => 1]]
                : [['id' => null, 'name' => $itemName !== '' ? $itemName : 'Mutfak mesajı', 'quantity' => max(1, $qty)]];

            $completedOrders[] = [
                'id'              => 0,
                'source'          => 'symphony',
                'group_key'       => $row->group_key,
                'table_no'        => $row->table_no,
                'items'           => $itemsArr,
                'total_price'     => 0,
                'order_note'      => null,
                'status'          => 'completed',
                'bar_status'      => 'approved',
                'kitchen_status'  => 'completed',
                'created_at'      => $deliveredAt->format('H:i:s'),
                'completed_at_ts' => $deliveredAt->getTimestamp(),
                'seconds_ago'     => (int) $deliveredAt->diffInSeconds(now()),
            ];
        }

        usort($completedOrders, function ($a, $b) {
            $av = $a['completed_at_ts'] ?? 0;
            $bv = $b['completed_at_ts'] ?? 0;
            if (!$av && isset($a['completed_at'])) $av = strtotime($a['completed_at']);
            if (!$bv && isset($b['completed_at'])) $bv = strtotime($b['completed_at']);
            return $bv <=> $av;
        });
        $completedOrders = array_slice($completedOrders, 0, $completedLimit);

        $waiterCalls = WaiterCall::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($call) => [
                'id'         => $call->id,
                'table_no'   => $call->table_no,
                'room_no'    => $call->room_no,
                'note'       => $call->note,
                'created_at' => $call->created_at->format('H:i:s'),
                'order_time' => $call->created_at->toIso8601String(),
                'seconds_ago'=> (int) $call->created_at->diffInSeconds(now()),
            ]);

        $attendedCalls = WaiterCall::where('status', 'attended')
            ->where('attended_at', '>=', now()->subMinutes(10))
            ->orderBy('attended_at', 'desc')
            ->limit(8)
            ->get()
            ->map(fn ($call) => [
                'id'          => $call->id,
                'table_no'    => $call->table_no,
                'note'        => $call->note,
                'attended_at' => $call->attended_at?->format('H:i:s') ?? '',
                'seconds_ago' => (int) ($call->attended_at?->diffInSeconds(now()) ?? 0),
            ]);

        return response()->json([
            'orders'                => $orders->values(),
            'ready_orders'          => array_values($readyOrders),
            'ready_orders_limit'    => $readyLimit,
            'completed_orders'      => collect($completedOrders)->values(),
            'completed_orders_limit'=> $completedLimit,
            'waiter_calls'          => $waiterCalls,
            'attended_calls'        => $attendedCalls,
            'server_now' => \App\Support\Clock::nowIso(),
        ]);
    }

    public function barApiSymphony()
    {
        $host     = (string) Setting::get('mssql_bds_host', Setting::get('mssql_kds_host', ''));
        $port     = (string) Setting::get('mssql_bds_port', Setting::get('mssql_kds_port', '1433'));
        $database = (string) Setting::get('mssql_bds_database', Setting::get('mssql_kds_database', ''));
        $username = (string) Setting::get('mssql_bds_username', Setting::get('mssql_kds_username', ''));
        $password = (string) Setting::get('mssql_bds_password', '') ?: (string) Setting::get('mssql_kds_password', '');
        $query    = trim((string) Setting::get('mssql_bds_query', ''));

        if ($query === '' || !$host || !$database || !$username) {
            return response()->json([
                'success' => false,
                'message' => 'BDS MSSQL ayarları/sorgusu eksik. Admin → MSSQL Ayarları → BDS sekmesinden tanımlayın.',
                'orders'  => [],
            ]);
        }

        try {
            $pdo  = $this->mssql->connect($host, $port, $database, $username, $password);
            $rows = $this->mssql->runQuery($pdo, $this->mssql->cleanSql($query));

            // v1.4 sorgu ödeme/indirim/diğer satırları da döndürür (ODEME, INDIRIM, DIGER);
            // bunlar barda ürün gibi görünmesin diye yazılım tarafında elenir.
            // Eski sorguda LineKind kolonu yoktur → boş döner, tüm satırlar korunur.
            $mssql = $this->mssql;
            $rows  = array_values(array_filter($rows, function ($r) use ($mssql) {
                $lk = strtoupper((string) $mssql->getField($r, ['LineKind', 'line_kind', 'LineType', 'line_type'], ''));
                if ($lk === '') return true;
                return in_array($lk, ['URUN', 'MODIFIER', 'MESAJ', 'MARS', 'IADE', 'COMBO', 'PRODUCT', 'KITCHEN_MESSAGE', 'BAR_MESSAGE'], true);
            }));

            // RVC / Gelir Merkezi yazılım filtresi (sorgunun RVC kapsamı FULL kalır)
            $rvcFilterIds = SymphonyKdsController::parseRvcFilter((string) Setting::get('mssql_bds_rvc_filter', ''));

            $groups = [];
            $msgGuids = [];
            $closedCheckNums = [];
            foreach ($rows as $row) {
                $tableNo    = (string) $this->mssql->getField($row, ['TableNo', 'TableNumber', 'MASA', 'table_no'], '');
                $checkNum   = $this->mssql->getField($row, ['CheckNumber', 'CheckNum', 'ADISYON', 'check_number'], null);
                $itemName   = (string) $this->mssql->getField($row, ['ItemName', 'ProductName', 'Name', 'item_name'], '');
                $orderTime  = $this->mssql->getField($row, ['OrderTime', 'ItemTime', 'Time', 'order_time'], null);
                $note       = (string) $this->mssql->getField($row, ['Note', 'RefInfo', 'MessageNote', 'note'], '');
                $waiterName = trim(
                    (string) $this->mssql->getField($row, ['WaiterName', 'waiter_name'], '') . ' ' .
                    (string) $this->mssql->getField($row, ['WaiterSurname', 'waiter_surname'], '')
                );

                // v1.4: POS'ta kapanan checkler bar ekranda gösterilmez; aşağıda
                // bar onay kayıtları "servis edildi" işaretlenir (SON şeridine düşer).
                $status = strtoupper((string) $this->mssql->getField($row, ['Status', 'CheckStatus', 'check_status'], ''));
                if ($status === 'C') {
                    if ($checkNum !== null && (string) $checkNum !== '') $closedCheckNums[(string) $checkNum] = true;
                    continue;
                }

                // RVC yazılım filtresi (boş = tüm RVC'ler; RVC'siz satır filtrelenmez)
                $rvcId = (int) $this->mssql->getField($row, ['RevenueCenterID', 'revenue_center_id'], 0);
                if ($rvcFilterIds !== [] && $rvcId > 0 && !isset($rvcFilterIds[$rvcId])) {
                    continue;
                }

                $key = $checkNum !== null && $checkNum !== '' ? 'C' . $checkNum : 'T' . $tableNo;
                // MajorGroupID: 1=Yiyecek, 2=İçecek, 3=Alkollü İçecek, 99=Mesaj/Mars
                $mg = (int) $this->mssql->getField($row, ['MajorGroupID', 'major_group_id', 'MajGrp', 'maj_grp'], 0);
                // Filtre kodu: Bar Mesaj satırları (LineType BAR_MESSAGE / ProductObjectNumber
                // 9001020, MajGrp 99) 98'e, diğerleri MajorGroupID ile aynı olur — mutfak tick yapısıyla paralel.
                $lineType = strtoupper((string) $this->mssql->getField($row, ['LineType', 'line_type', 'LineKind', 'line_kind'], ''));
                $objNo    = (int) $this->mssql->getField($row, ['ProductObjectNumber', 'product_object_number'], 0);
                $rowQty   = (int) $this->mssql->getField($row, ['Qty', 'Quantity', 'ADET', 'qty', 'SalesCount'], 1);
                $checkGid = (string) $this->mssql->getField($row, ['CheckGID', 'check_gid'], '');
                $fc = ($lineType === 'BAR_MESSAGE' || ($lineType === 'MESAJ' && $objNo === 9001020)) ? 98 : $mg;

                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'group_key'    => $key,
                        'table_no'     => $tableNo,
                        'check_number' => $checkNum,
                        'order_time'   => $orderTime,
                        'waiter_name'  => $waiterName,
                        'items'        => [],
                        'merge'        => [],
                    ];
                }
                if ($orderTime && (!$groups[$key]['order_time'] || strcmp((string) $orderTime, (string) $groups[$key]['order_time']) < 0)) {
                    $groups[$key]['order_time'] = $orderTime;
                }

                if ($lineType === 'MESAJ' || $lineType === 'MARS') {
                    // Mesaj satırları birleştirilmez; metin fiş çözümünden gelir
                    $entry = ['name' => $itemName, 'qty' => max(1, $rowQty), 'note' => $note, 'mg' => $mg, 'fc' => $fc, 'is_returned' => false];
                    if ($lineType === 'MESAJ' && $checkGid !== '') {
                        $entry['_gid'] = $checkGid;
                        $msgGuids[$checkGid] = true;
                    }
                    $groups[$key]['items'][] = $entry;
                } elseif ($objNo > 0 && ($mIdx = $groups[$key]['merge'][$objNo] ?? null) !== null) {
                    // v1.4: aynı checkte aynı ürün (ProductObjectNumber) → tek satırda birleştir.
                    // Net adet sıfırın altına inerse satır komple iade sayılır.
                    $it = &$groups[$key]['items'][$mIdx];
                    $it['qty_net']   += $rowQty;
                    $it['row_count'] += 1;
                    if ($rowQty < 0) {
                        $it['qty_returned'] += abs($rowQty);
                    }
                    unset($it);
                } else {
                    $groups[$key]['items'][] = [
                        'name'         => $itemName,
                        'qty'          => max(1, $rowQty),
                        'note'         => $note,
                        'mg'           => $mg,
                        'fc'           => $fc,
                        'qty_net'      => $rowQty,
                        'qty_returned' => $rowQty < 0 ? abs($rowQty) : 0,
                        'row_count'    => 1,
                        'is_returned'  => (bool) (int) $this->mssql->getField($row, ['IsReturned', 'is_returned'], 0) || $rowQty < 0,
                    ];
                    if ($objNo > 0) {
                        $groups[$key]['merge'][$objNo] = count($groups[$key]['items']) - 1;
                    }
                }
            }

            // Kapanan checklerin bar onay kayıtları otomatik "servis edildi" olur
            if ($closedCheckNums !== []) {
                try {
                    DB::table('kitchen_pos_completions')
                        ->whereIn('check_number', array_keys($closedCheckNums))
                        ->whereNull('delivered_at')
                        ->update(['delivered_at' => now()]);
                } catch (\Throwable) {
                }
                try {
                    DB::table('kitchen_item_logs')
                        ->whereIn('check_number', array_keys($closedCheckNums))
                        ->whereNull('delivered_at')
                        ->update(['delivered_at' => now()]);
                } catch (\Throwable) {
                }
            }

            // Birleştirilmiş v1.4 satırları: net adet ve iade durumu nihai değerlere düşer.
            // Kısmi iade (net>0) net adedi gösterir, iade işareti almaz.
            foreach ($groups as &$g) {
                foreach ($g['items'] as &$it) {
                    if (($it['row_count'] ?? 0) >= 1) {
                        if ($it['row_count'] > 1) {
                            $it['is_returned'] = $it['qty_net'] <= 0;
                        }
                        $it['qty'] = $it['is_returned']
                            ? max(1, abs($it['qty_net']) ?: $it['qty_returned'])
                            : max(1, $it['qty_net']);
                    }
                    unset($it['qty_net'], $it['qty_returned'], $it['row_count']);
                }
                unset($it);
                unset($g['merge']);
            }
            unset($g);

            // MESAJ satırlarının gerçek metni POS_JOURNAL_LOG fiş metninden çözülür
            $journalTexts = $msgGuids !== [] ? SymphonyKdsController::resolveJournalMessages($pdo, array_keys($msgGuids)) : [];
            $cursor = [];
            $nextText = function (array &$item) use (&$cursor, $journalTexts) {
                $gid = (string) ($item['_gid'] ?? '');
                unset($item['_gid']);
                if ($gid === '' || !isset($journalTexts[$gid])) return;
                $i = $cursor[$gid] ?? 0;
                if (isset($journalTexts[$gid][$i])) {
                    $item['note'] = $journalTexts[$gid][$i];
                }
                $cursor[$gid] = $i + 1;
            };
            foreach ($groups as &$g) {
                foreach ($g['items'] as &$it) {
                    if (($it['fc'] ?? 0) === 98 || ($it['fc'] ?? 0) === 99) $nextText($it);
                }
                unset($it);
            }
            unset($g);

            uasort($groups, fn($a, $b) => strcmp((string) $a['order_time'], (string) $b['order_time']));

            $out = [];
            foreach ($groups as $g) {
                $secondsAgo = 0;
                if ($g['order_time']) {
                    try {
                        $secondsAgo = max(0, (int) \Carbon\Carbon::parse((string) $g['order_time'], 'Europe/Istanbul')
                            ->diffInSeconds(\Carbon\Carbon::now('Europe/Istanbul')));
                    } catch (\Exception) {}
                }
                $out[] = [
                    'source'       => 'symphony',
                    'group_key'    => $g['group_key'],
                    'table_no'     => $g['table_no'],
                    'check_number' => $g['check_number'],
                    'waiter_name'  => $g['waiter_name'] ?? '',
                    'order_time'   => $g['order_time']
                        ? \Carbon\Carbon::parse((string) $g['order_time'], 'Europe/Istanbul')->toIso8601String()
                        : null,
                    'seconds_ago'  => $secondsAgo,
                    'items'        => array_values($g['items']),
                ];
            }

            return response()->json(['success' => true, 'orders' => $out, 'count' => count($out), 'server_now' => \App\Support\Clock::nowIso()]);
        } catch (\Exception $e) {
            Log::error('BDS MSSQL sorgu hatası', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'BDS bağlantı hatası oluştu.', 'orders' => []]);
        }
    }

    // Bar filtresi: her QR ürününe kategori sınıfı (food/drink) + siparişte yiyecek var mı
    private function mapBarOrder(Order $order): array
    {
        $m = $this->mapOrder($order);
        $items   = is_array($m['items'] ?? null) ? $m['items'] : [];
        $hasFood = false;
        foreach ($items as &$it) {
            $cat = $this->qrItemCat((int) ($it['id'] ?? 0));
            $it['cat'] = $cat;
            if ($cat === 'food') $hasFood = true;
        }
        unset($it);
        $m['items']    = array_values($items);
        $m['has_food'] = $hasFood;
        return $m;
    }

    // "Adana Kebap x2 · Lahmacun x3" biçimindeki persist edilmiş liste → kart ürün satırları
    private function namesToItems(?string $names): array
    {
        $out = [];
        foreach (array_filter(array_map('trim', explode('·', (string) $names)), fn ($p) => $p !== '') as $part) {
            if (preg_match('/^(.+?)\s*x(\d+)$/u', $part, $m)) {
                $out[] = ['id' => null, 'name' => trim($m[1]), 'quantity' => max(1, (int) $m[2])];
            } else {
                $out[] = ['id' => null, 'name' => $part, 'quantity' => 1];
            }
        }
        return $out;
    }

    private function qrItemCat(int $productId): string
    {
        static $drinkIds = null, $prodCats = null;
        if ($drinkIds === null) {
            $drinkIds = [];
            foreach (preg_split('/[\s,]+/', (string) Setting::get('bar_qr_drink_cats', '11-30,32')) as $part) {
                if ($part === '') continue;
                if (str_contains($part, '-')) {
                    [$a, $b] = array_map('intval', explode('-', $part, 2));
                    for ($i = min($a, $b); $i <= max($a, $b); $i++) $drinkIds[$i] = true;
                } else {
                    $drinkIds[(int) $part] = true;
                }
            }
            $prodCats = Product::pluck('category_id', 'id');
        }
        $catId = (int) ($prodCats[$productId] ?? 0);
        return isset($drinkIds[$catId]) ? 'drink' : 'food';
    }
}
