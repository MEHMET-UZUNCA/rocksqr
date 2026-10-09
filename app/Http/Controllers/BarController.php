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

        // Kart adisyon bazli tek karttir; grup anahtari batch'li olabilir (#2 gibi).
        // Check varsa o adisyonun tum bekleyen onaylari birlikte teslim edilir.
        $checkNumber = DB::table('kitchen_pos_completions')
            ->where('group_key', $validated['group_key'])
            ->value('check_number');
        $checkNumber = $checkNumber !== null ? trim((string) $checkNumber) : '';

        if ($checkNumber !== '') {
            DB::table('kitchen_pos_completions')
                ->where('check_number', $checkNumber)
                ->whereNull('delivered_at')
                ->update(['delivered_at' => now()]);
            try {
                DB::table('kitchen_item_logs')
                    ->where('check_number', $checkNumber)
                    ->whereNull('delivered_at')
                    ->update(['delivered_at' => now()]);
            } catch (\Throwable) {
            }
            return response()->json(['success' => true]);
        }

        DB::table('kitchen_pos_completions')
            ->where('group_key', $validated['group_key'])
            ->whereNull('delivered_at')
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
        ScreenCleaner::clearIfDue('bar');
        $clearedAt = ScreenCleaner::clearedAt('bar');

        $completedLimit    = (int) Setting::get('bar_completed_display', 12);
        $readyLimit        = (int) Setting::get('bar_ready_display', 12);
        $undoWindowSeconds = (int) Setting::get('ready_undo_seconds', 30);

        // Bar yalniz mutfak (kitchen-pos) onaylarini gorur; ana mutfak onaylari bara dusmez.
        // rvc_id=0 deploy oncesi eski kayittir, gosterilmeye devam eder.
        $kposRvcIds = array_keys(SymphonyKdsController::parseRvcFilter((string) Setting::get('mssql_kds_rvc_filter', '')));
        $kposScope  = function ($q) use ($kposRvcIds) {
            if ($kposRvcIds === []) return;
            $q->where(fn ($w) => $w->whereIn('rvc_id', $kposRvcIds)->orWhere('rvc_id', 0));
        };

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
        // Kart adisyon bazlı TEK karttır: batch (#2 gibi) grup anahtarları ve kısmi
        // (kind=item) onaylar aynı check altında tek kartta toplanır; içerik en güncel
        // onaydan gelir. kind=check kartı içeriği mutfak onaylı ürün listesidir
        // (db_items); canlı feed yalnız onay kaydında isim yoksa yedektir.
        $symphonyRows = DB::table('kitchen_pos_completions')
            ->where($kposScope)
            ->whereNull('delivered_at')
            ->whereIn('kind', ['check', 'checkless_msg', 'item'])
            ->orderByDesc('completed_at')
            ->limit($readyLimit * 4)
            ->get();

        $symphonyBuckets = [];
        foreach ($symphonyRows as $row) {
            $checkNo = $row->check_number !== null ? trim((string) $row->check_number) : '';
            $bKey    = $checkNo !== '' ? 'C' . $checkNo : 'G' . $row->group_key;
            $symphonyBuckets[$bKey][] = $row;
        }

        foreach ($symphonyBuckets as $rows) {
            $latest            = $rows[0]; // orderByDesc: en güncel onay
            $completedAt       = \Carbon\Carbon::parse($latest->completed_at);
            $readySinceSeconds = (int) $completedAt->diffInSeconds(now());
            $undoRemaining     = 0;
            $servedKeys        = [];
            $checkRow          = null;
            foreach ($rows as $row) {
                $undoRemaining = max($undoRemaining, max(0, $undoWindowSeconds - (int) \Carbon\Carbon::parse($row->completed_at)->diffInSeconds(now())));
                $servedKeys    = array_merge($servedKeys, json_decode((string) ($row->served_item_keys ?? '[]'), true) ?: []);
                if ($checkRow === null && $row->kind === 'check') {
                    $checkRow = $row;
                }
            }
            $servedCount = count(array_unique($servedKeys));

            if ($checkRow !== null) {
                $itemsArr  = [['id' => null, 'name' => 'Adisyon #' . ($checkRow->check_number ?: '-'), 'quantity' => 1]];
                $checkName = trim((string) ($checkRow->name ?? ''));
                $dbItems   = $checkName !== '' ? $this->namesToItems($checkName) : null;
                $cardKind  = 'check';
                $orderNote = null;
            } else {
                // Kısmi (kind=item) onayların kümülatif adları zamana göre birleştirilir
                $merged = [];
                foreach (array_reverse($rows) as $row) {
                    $nm = trim((string) ($row->name ?? ''));
                    if ($nm === '') {
                        continue;
                    }
                    foreach (SymphonyKdsController::parseQtyNameParts($nm, max(1, (int) ($row->qty ?? 1))) as [$n, $q]) {
                        $merged[$n] = ($merged[$n] ?? 0) + $q;
                    }
                }
                $itemsArr = [];
                foreach ($merged as $n => $q) {
                    $itemsArr[] = ['id' => null, 'name' => $n, 'quantity' => max(1, $q)];
                }
                if ($itemsArr === []) {
                    $itemsArr = [['id' => null, 'name' => 'Mutfak ürünü', 'quantity' => 1]];
                }
                $dbItems   = null;
                $cardKind  = (string) $latest->kind;
                $latestNote = trim((string) ($latest->note ?? ''));
                $orderNote = $latestNote !== '' ? $latestNote : null;
            }

            $readyOrders[] = [
                'id'                  => 0,
                'source'              => 'symphony',
                'group_key'           => $latest->group_key,
                'kind'                => $cardKind,
                'table_no'            => $latest->table_no,
                'items'               => $itemsArr,
                'db_items'            => $dbItems,
                'waiter_name'         => null,
                'served_count'        => $servedCount,
                'total_units'         => $servedCount,
                'total_price'         => 0,
                'order_note'          => $orderNote,
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
            ->where($kposScope)
            ->whereNotNull('delivered_at')
            ->when($clearedAt, fn ($q) => $q->where('delivered_at', '>', $clearedAt))
            ->orderByDesc('delivered_at')
            ->limit($completedLimit)
            ->get();

        foreach ($symphonyDelivered as $row) {
            $deliveredAt = \Carbon\Carbon::parse($row->delivered_at);
            $itemName    = trim((string) ($row->name ?? ''));
            $qty         = (int) ($row->qty ?? 1);

            // Kayıtlı içerik listesi ("1x A · 1x B") varsa gerçek ürün satırlarına çöz;
            // boşsa tür bazlı yer tutucu (eski davranış).
            $parsedItems = $itemName !== '' ? $this->namesToItems($itemName) : [];
            if (!empty($parsedItems)) {
                $itemsArr = $parsedItems;
            } elseif ($row->kind === 'check') {
                $itemsArr = [['id' => null, 'name' => 'Adisyon #' . ($row->check_number ?: '-'), 'quantity' => 1]];
            } else {
                $itemsArr = [['id' => null, 'name' => 'Mutfak mesajı', 'quantity' => max(1, $qty)]];
            }

            $completedOrders[] = [
                'id'              => 0,
                'source'          => 'symphony',
                'group_key'       => $row->group_key,
                'check_number'    => $row->check_number !== null ? trim((string) $row->check_number) : null,
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
            ->limit(50)
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
                'room_no'     => $call->room_no,
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

            // Check kapanma davranışı: '1' (varsayılan) = kapanan hesap ekrandan hemen silinir,
            // '0' = kart "KAPANDI" rozetiyle personel tamamlayana kadar ekranda bekler.
            $barDeleteOnClose = Setting::get('bar_check_close_wait', '1') !== '0';

            $groups = [];
            $journalGuids = [];
            $closedCheckNums = [];
            // Açık Yiyecek/İçecek/Diğer POS kodları (RVC bazlı): fiş içerik çözümü bu satırlara uygulanır
            $openSets = [];
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

                // POS'ta kapanan checkler sil modunda ekranda gösterilmez (aşağıda bar onay
                // kayıtları "servis edildi" işaretlenir, SON şeridine düşer); bekleme modunda
                // satırlar korunur, kart "KAPANDI" rozetiyle ekranda kalır.
                $status = strtoupper((string) $this->mssql->getField($row, ['Status', 'CheckStatus', 'check_status'], ''));
                if ($status === 'C' && $barDeleteOnClose) {
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
                $isOpenRow = false;
                if ($objNo > 0 && $rvcId > 0) {
                    $openSets[$rvcId] ??= \App\Support\KitchenFilter::openCodeMap($rvcId, 'bar_open');
                    $isOpenRow = isset($openSets[$rvcId][$objNo]);
                }
                $fc = ($lineType === 'BAR_MESSAGE' || ($lineType === 'MESAJ' && $objNo === 9001020)) ? 98 : $mg;
                // FamGrpObjNum AS FamilyGroupID — bar tick/panelleri family gizlemeyi istemci tarafinda uygular
                $famGrp = (int) $this->mssql->getField($row, ['FamilyGroupID', 'family_group_id', 'FamGrpObjNum', 'fam_grp_obj_num'], 0);
                // Mesaj çapası: DtlSeq = POS fiş satır sırası, ParentItemID = 0 ise ana satır
                $dtlSeq = (int) $this->mssql->getField($row, ['DtlSeq', 'dtl_seq', 'DetailIndex', 'detail_index'], 0);
                $parId  = (int) $this->mssql->getField($row, ['ParentItemID', 'parent_item_id'], 0);

                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'group_key'    => $key,
                        'table_no'     => $tableNo,
                        'check_number' => $checkNum,
                        'order_time'   => $orderTime,
                        'waiter_name'  => $waiterName,
                        'status'       => $status,
                        'items'        => [],
                        'merge'        => [],
                    ];
                }
                if ($orderTime && (!$groups[$key]['order_time'] || strcmp((string) $orderTime, (string) $groups[$key]['order_time']) < 0)) {
                    $groups[$key]['order_time'] = $orderTime;
                }

                if ($lineType === 'MESAJ' || $lineType === 'MARS') {
                    // Mesaj satırları birleştirilmez; metin fiş çözümünden gelir
                    $entry = ['name' => $itemName, 'qty' => max(1, $rowQty), 'note' => $note, 'mg' => $mg, 'fc' => $fc, 'fam' => $famGrp, 'is_returned' => false, '_seq' => $dtlSeq];
                    if ($lineType === 'MESAJ' && $checkGid !== '') {
                        $entry['_gid'] = $checkGid;
                        $journalGuids[$checkGid] = true;
                    }
                    $groups[$key]['items'][] = $entry;
                } elseif (!$isOpenRow && $objNo > 0 && ($mIdx = $groups[$key]['merge'][$objNo] ?? null) !== null) {
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
                    $entry = [
                        'name'         => $itemName,
                        'qty'          => max(1, $rowQty),
                        'note'         => $note,
                        'mg'           => $mg,
                        'fc'           => $fc,
                        'fam'          => $famGrp,
                        'qty_net'      => $rowQty,
                        'qty_returned' => $rowQty < 0 ? abs($rowQty) : 0,
                        'row_count'    => 1,
                        'is_returned'  => (bool) (int) $this->mssql->getField($row, ['IsReturned', 'is_returned'], 0) || $rowQty < 0,
                        '_seq'         => $dtlSeq,
                        '_par'         => $parId,
                    ];
                    // Açık satır: gerçek içerik POS_JOURNAL_LOG fişinden çözülür
                    if ($isOpenRow && $checkGid !== '') {
                        $entry['_gid']  = $checkGid;
                        $entry['_open'] = true;
                        $journalGuids[$checkGid] = true;
                    }
                    $groups[$key]['items'][] = $entry;
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

            // MESAJ ve AÇIK satırlarının gerçek metni POS_JOURNAL_LOG fiş metninden çözülür
            $journalTexts = $journalGuids !== [] ? SymphonyKdsController::resolveJournalTexts($pdo, array_keys($journalGuids)) : [];
            $cursor = [];
            $nextText = function (array &$item) use (&$cursor, $journalTexts) {
                $gid  = (string) ($item['_gid'] ?? '');
                $kind = ((int) ($item['fc'] ?? 0) === 98) ? 'bar' : 'mutfak';
                unset($item['_gid']);
                if ($gid === '' || !isset($journalTexts[$gid]['messages'][$kind])) return;
                $i = $cursor[$gid . ':' . $kind] ?? 0;
                if (isset($journalTexts[$gid]['messages'][$kind][$i])) {
                    $item['note'] = $journalTexts[$gid]['messages'][$kind][$i];
                }
                $cursor[$gid . ':' . $kind] = $i + 1;
            };
            foreach ($groups as &$g) {
                foreach ($g['items'] as &$it) {
                    if (($it['fc'] ?? 0) === 98 || ($it['fc'] ?? 0) === 99) $nextText($it);
                }
                unset($it);
            }
            unset($g);

            // Açık satırlara fiş içerikleri (sıralı imleçle)
            $openCursor = [];
            $nextOpen = function (array &$item) use (&$openCursor, $journalTexts) {
                $gid = (string) ($item['_gid'] ?? '');
                unset($item['_gid'], $item['_open']);
                if ($gid === '' || !isset($journalTexts[$gid]['opens'])) return;
                $i = $openCursor[$gid] ?? 0;
                if (isset($journalTexts[$gid]['opens'][$i])) {
                    $item['note'] = implode(', ', $journalTexts[$gid]['opens'][$i]['contents']);
                }
                $openCursor[$gid] = $i + 1;
            };
            foreach ($groups as &$g) {
                foreach ($g['items'] as &$it) {
                    if (!empty($it['_open'])) $nextOpen($it);
                }
                unset($it);
            }
            unset($g);

            // Mesaj çapası: her mesaj satırı POS fiş sırasında (DtlSeq) kendisinden önce
            // gelen en yakın ANA ürün satırına bağlanır. Yan ürün (modifier) satırları çapa
            // olamaz — isim bazlı servis düşmeye girmediklerinden mesajı asılı bırakırlar.
            // _seq/_par bu geçişte tüm satırlardan silinir; JSON çıktısına sızamaz.
            $msgAnchors = [];
            foreach ($groups as $gKey => &$g) {
                $prodSeqs = [];
                foreach ($g['items'] as $idx => &$it) {
                    $seq = (int) ($it['_seq'] ?? 0);
                    $par = (int) ($it['_par'] ?? 0);
                    unset($it['_seq'], $it['_par']);
                    if ((int) ($it['fc'] ?? 0) === 98 || (int) ($it['fc'] ?? 0) === 99) {
                        $best = null;
                        $bestSeq = -1;
                        foreach ($prodSeqs as $pIdx => $pSeq) {
                            if ($pSeq < $seq && $pSeq > $bestSeq) {
                                $bestSeq = $pSeq;
                                $best = $pIdx;
                            }
                        }
                        if ($best !== null) $msgAnchors[$gKey][$idx] = $best;
                    } elseif ($par === 0) {
                        $prodSeqs[$idx] = $seq;
                    }
                }
                unset($it);
            }
            unset($g);

            // Mavi karttan hazır düşme: mutfak (KPOS) onayları ürün adı+adet eşleşmesiyle
            // canlı feed satırlarından sırayla düşülür. delivered_at filtresi YOKTUR —
            // garsona teslim edilmiş ürün de servis edilmiş sayılır, mavi karta geri dönmez.
            // Mesaj/MARS ve iade satırları eşleşmeye girmez; çapa ürünü servis edilen mesaj da
            // düşer (mutfaktaki batch kart davranışına parite), tüm ürünleri düşen kart komple kaybolur.
            $visibleChecks = [];
            foreach ($groups as $g) {
                $cn = trim((string) ($g['check_number'] ?? ''));
                if ($cn !== '') $visibleChecks[$cn] = true;
            }
            if ($visibleChecks !== []) {
                $tallyRvcIds = array_keys(SymphonyKdsController::parseRvcFilter((string) Setting::get('mssql_kds_rvc_filter', '')));
                $tallyRows = DB::table('kitchen_pos_completions')
                    ->whereIn('check_number', array_keys($visibleChecks))
                    ->whereIn('kind', ['check', 'item'])
                    ->when($tallyRvcIds !== [], fn ($q) => $q->where(fn ($w) => $w->whereIn('rvc_id', $tallyRvcIds)->orWhere('rvc_id', 0)))
                    ->get(['check_number', 'name', 'qty']);

                $servedTally = [];
                foreach ($tallyRows as $tr) {
                    $cn = trim((string) $tr->check_number);
                    $nm = trim((string) ($tr->name ?? ''));
                    if ($nm === '') continue;
                    foreach (SymphonyKdsController::parseQtyNameParts($nm, max(1, (int) ($tr->qty ?? 1))) as [$n, $q]) {
                        $servedTally[$cn][$n] = ($servedTally[$cn][$n] ?? 0) + $q;
                    }
                }

                foreach ($groups as $gKey => $gVal) {
                    $cn = trim((string) ($gVal['check_number'] ?? ''));
                    if ($cn === '' || empty($servedTally[$cn])) continue;
                    $remaining    = $servedTally[$cn];
                    $hadProduct   = false;
                    $keptProduct  = false;
                    $servedRows   = [];
                    $newItems     = [];
                    foreach ($gVal['items'] as $idx => $it) {
                        $fc   = (int) ($it['fc'] ?? 0);
                        $nm   = trim((string) ($it['name'] ?? ''));
                        if (!empty($it['is_returned']) || $nm === '') {
                            $newItems[] = $it;
                            continue;
                        }
                        if ($fc === 98 || $fc === 99) {
                            $anchor = $msgAnchors[$gKey][$idx] ?? null;
                            if ($anchor !== null && isset($servedRows[$anchor])) continue; // çapa ürünü servis edildi → mesaj düşer
                            $newItems[] = $it;
                            continue;
                        }
                        $hadProduct = true;
                        $need = max(1, (int) ($it['qty'] ?? 1));
                        $take = min($remaining[$nm] ?? 0, $need);
                        if ($take > 0) {
                            $remaining[$nm] = ($remaining[$nm] ?? 0) - $take;
                        }
                        if ($take >= $need) {
                            $servedRows[$idx] = true;
                            continue; // satır tamamen servis edildi
                        }
                        $it['qty']  = $need - $take;
                        $keptProduct = true;
                        $newItems[] = $it;
                    }
                    if ($hadProduct && !$keptProduct) {
                        unset($groups[$gKey]); // tüm ürünler servis edildi: kart (mesajlarla) düşer
                    } elseif ($hadProduct) {
                        $groups[$gKey]['items'] = array_values($newItems);
                    }
                }
            }

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
                    'status'       => $g['status'] ?? '',
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
    // "2x Adana · Lahmacun x3" biçimindeki persist edilmiş liste → kart ürün satırları
    private function namesToItems(?string $names): array
    {
        $out = [];
        foreach (SymphonyKdsController::parseQtyNameParts((string) $names, 1) as [$nm, $q]) {
            $out[] = ['id' => null, 'name' => $nm, 'quantity' => max(1, $q)];
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
