<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Services\MssqlService;
use App\Support\KitchenFilter;
use App\Support\ScreenCleaner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SymphonyKdsController extends Controller
{
    public function __construct(private MssqlService $mssql) {}

    // ──────────────────────────────────────────────
    // Ekran sayfaları
    // ──────────────────────────────────────────────

    public function kitchenPos()
    {
        return view('admin.kitchen-pos');
    }

    public function kitchenAna()
    {
        return view('admin.kitchen-ana');
    }

    // ──────────────────────────────────────────────
    // KDS: QR sipariş onay / geri al
    // ──────────────────────────────────────────────

    public function kitchenPosConfirmQr(Order $order)
    {
        if (in_array($order->kitchen_status, ['cancelled', 'completed'], true)) {
            return response()->json(['success' => false, 'message' => 'Bu sipariş onaylanamaz.'], 422);
        }

        $firstSeen = $order->kitchen_started_at ?? $order->created_at;
        $prepSeconds = $firstSeen ? max(0, (int) now()->diffInSeconds($firstSeen)) : null;

        try {
            $now = now();
            $rows = [];
            foreach ($order->items() as $it) {
                $rows[] = [
                    'name'          => (string) ($it['name'] ?? 'Ürün'),
                    'qty'           => max(1, (int) ($it['quantity'] ?? 1)),
                    'source'        => 'qr',
                    'table_no'      => $order->table_no !== null ? (string) $order->table_no : null,
                    'room_no'       => $order->room_no,
                    'check_number'  => null,
                    'group_key'     => 'Q' . $order->id,
                    'first_seen_at' => $firstSeen,
                    'completed_at'  => $now,
                    'prep_seconds'  => $prepSeconds,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
            if ($rows !== []) {
                DB::table('kitchen_item_logs')->insert($rows);
            }
        } catch (\Throwable) {
        }

        $order->update([
            'kitchen_status'     => 'ready',
            'status'             => 'ready',
            'bar_status'         => 'approved',
            'kitchen_ready_at'   => $order->kitchen_ready_at ?? now(),
            'kitchen_started_at' => $order->kitchen_started_at ?? now(),
            'completed_at'       => null,
        ]);
        return response()->json(['success' => true]);
    }

    public function kitchenPosUndoQr(Order $order)
    {
        $undoWindowSeconds = (int) Setting::get('ready_undo_seconds', 30);
        if ($order->kitchen_status !== 'ready') {
            return response()->json(['success' => false, 'message' => 'Bu sipariş geri alınamaz.'], 422);
        }
        if ($order->kitchen_ready_at && $order->kitchen_ready_at->diffInSeconds(now()) > $undoWindowSeconds) {
            return response()->json(['success' => false, 'message' => 'Geri alma süresi doldu.'], 422);
        }
        try {
            DB::table('kitchen_item_logs')
                ->where('group_key', 'Q' . $order->id)
                ->where('completed_at', '>=', now()->subSeconds($undoWindowSeconds))
                ->delete();
        } catch (\Throwable) {
        }
        $order->update([
            'kitchen_status'   => 'preparing',
            'status'           => 'preparing',
            'kitchen_ready_at' => null,
            'completed_at'     => null,
        ]);
        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────
    // KDS: Symphony hesap / mesaj tamamlama
    // ──────────────────────────────────────────────

    public function kitchenPosComplete(Request $request)
    {
        $validated = $request->validate([
            'kind'          => 'required|in:check,checkless_msg,item',
            'group_key'     => 'required|string|max:64',
            'check_number'  => 'nullable|string|max:64',
            'table_no'      => 'nullable|string|max:32',
            'name'          => 'nullable|string|max:255',
            'note'          => 'nullable|string|max:255',
            'qty'           => 'nullable|integer|min:1|max:999',
            'item_keys'     => 'nullable|array',
            'item_keys.*'   => 'string|max:128',
            'first_seen_at' => 'nullable|string|max:64',
        ]);

        $existing = DB::table('kitchen_pos_completions')->where('group_key', $validated['group_key'])->first();
        $existingKeys = $existing?->served_item_keys ? json_decode($existing->served_item_keys, true) : [];
        $newKeys = array_values(array_unique(array_merge(
            $existingKeys ?: [],
            array_filter($validated['item_keys'] ?? [], fn($k) => $k !== '')
        )));

        // Hazırlık süresi: önce kitchen_item_times'dan first_seen_at bul
        $firstSeenAt = null;
        if (!empty($validated['check_number'])) {
            $dbFirst = DB::table('kitchen_item_times')
                ->where('check_number', $validated['check_number'])
                ->min('first_seen_at');
            if ($dbFirst) $firstSeenAt = $dbFirst;
        }
        // DB'de yoksa client'tan gelen zamanı kullan
        if (!$firstSeenAt && !empty($validated['first_seen_at'])) {
            try {
                $parsed = \Carbon\Carbon::parse($validated['first_seen_at']);
                if ($parsed->isPast()) {
                    $firstSeenAt = $parsed->format('Y-m-d H:i:s');
                }
            } catch (\Exception) {}
        }

        $prepSeconds = $firstSeenAt
            ? max(0, (int) now()->diffInSeconds(\Carbon\Carbon::parse($firstSeenAt)))
            : null;

        $completedAt = now();

        DB::table('kitchen_pos_completions')->updateOrInsert(
            ['group_key' => $validated['group_key']],
            [
                'kind'             => $validated['kind'],
                'check_number'     => $validated['check_number'] ?? null,
                'table_no'         => $validated['table_no'] ?? null,
                'name'             => $validated['name'] ?? null,
                'note'             => $validated['note'] ?? null,
                'qty'              => $validated['qty'] ?? 1,
                'completed_at'     => $completedAt,
                'first_seen_at'    => $firstSeenAt,
                'prep_seconds'     => $prepSeconds,
                'served_item_keys' => json_encode($newKeys),
                // Yeni onay gelen grup bar'da daha önce "Teslim edildi" işaretlendiyse tekrar hazır şeridine düşsün
                'delivered_at'     => null,
            ]
        );

        // Ürün bazlı rapor geçmişi (append-only) — log hatası mutfak operasyonunu bozmasın
        try {
            $now = now();
            $logRow = fn (string $name, int $qty) => [
                'name'          => $name,
                'qty'           => $qty,
                'source'        => 'sym',
                'table_no'      => $validated['table_no'] ?? null,
                'room_no'       => null,
                'check_number'  => $validated['check_number'] ?? null,
                'group_key'     => $validated['group_key'],
                'first_seen_at' => $firstSeenAt,
                'completed_at'  => $completedAt,
                'prep_seconds'  => $prepSeconds,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];

            if ($validated['kind'] === 'item') {
                DB::table('kitchen_item_logs')->insert($logRow(
                    trim((string) ($validated['name'] ?? '')) !== '' ? $validated['name'] : 'Ürün',
                    max(1, (int) ($validated['qty'] ?? 1))
                ));
            } elseif ($validated['kind'] === 'check' && trim((string) ($validated['name'] ?? '')) !== '') {
                $logged = DB::table('kitchen_item_logs')
                    ->where('group_key', $validated['group_key'])
                    ->whereIn('name', collect(self::parseItemNames($validated['name']))->pluck(0))
                    ->pluck('name')
                    ->all();
                foreach (self::parseItemNames($validated['name']) as [$n, $q]) {
                    if (in_array($n, $logged, true)) {
                        continue;
                    }
                    DB::table('kitchen_item_logs')->insert($logRow($n, $q));
                }
            }
        } catch (\Throwable) {
        }

        return response()->json(['success' => true]);
    }

    // "Ad x2 · Y x1" listesini [isim, adet] çiftlerine ayır
    public static function parseItemNames(string $list): array
    {
        $out = [];
        foreach (explode(' · ', $list) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (preg_match('/^(.*?)\s+x(\d+)$/u', $part, $m) && trim($m[1]) !== '') {
                $out[] = [trim($m[1]), max(1, (int) $m[2])];
            } else {
                $out[] = [$part, 1];
            }
        }
        return $out;
    }

    public function kitchenPosUnserveItem(Request $request)
    {
        $validated = $request->validate([
            'group_key' => 'required|string|max:64',
            'item_keys' => 'required|array',
            'item_keys.*' => 'string|max:128',
        ]);

        $row = DB::table('kitchen_pos_completions')->where('group_key', $validated['group_key'])->first();
        if (!$row || $row->kind !== 'item') {
            return response()->json(['success' => false, 'message' => 'Kayıt bulunamadı.'], 404);
        }

        $served  = json_decode($row->served_item_keys ?? '[]', true) ?: [];
        $remove  = array_flip(array_filter($validated['item_keys'], fn($k) => $k !== ''));
        $remaining = array_values(array_filter($served, fn($k) => !isset($remove[$k])));

        if (empty($remaining)) {
            DB::table('kitchen_pos_completions')->where('group_key', $validated['group_key'])->delete();
        } else {
            DB::table('kitchen_pos_completions')->where('group_key', $validated['group_key'])
                ->update(['served_item_keys' => json_encode($remaining)]);
        }
        return response()->json(['success' => true]);
    }

    public function kitchenPosUncomplete(Request $request)
    {
        $validated = $request->validate(['group_key' => 'required|string|max:64']);

        $row = DB::table('kitchen_pos_completions')->where('group_key', $validated['group_key'])->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Kayıt bulunamadı.'], 404);
        }

        $undoWindowSeconds = (int) Setting::get('ready_undo_seconds', 30);
        if (now()->diffInSeconds(\Carbon\Carbon::parse($row->completed_at)) > $undoWindowSeconds) {
            return response()->json([
                'success' => false,
                'message' => 'Geri alma süresi doldu (' . $undoWindowSeconds . ' sn).',
            ], 422);
        }

        DB::table('kitchen_pos_completions')->where('group_key', $validated['group_key'])->delete();
        try {
            DB::table('kitchen_item_logs')
                ->where('group_key', $validated['group_key'])
                ->where('completed_at', '>=', now()->subSeconds($undoWindowSeconds))
                ->delete();
        } catch (\Throwable) {
        }
        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────
    // KDS: Symphony canlı sipariş API
    // ──────────────────────────────────────────────

    public function kitchenPosRaw(Request $request)
    {
        $host     = (string) Setting::get('mssql_kds_host', '');
        $port     = (string) Setting::get('mssql_kds_port', '1433');
        $database = (string) Setting::get('mssql_kds_database', '');
        $username = (string) Setting::get('mssql_kds_username', '');
        $password = (string) Setting::get('mssql_kds_password', '');
        $query    = trim((string) Setting::get('mssql_kds_query', ''));

        if ($query === '' || !$host || !$database || !$username) {
            return response()->json(['success' => false, 'message' => 'KDS ayarları eksik.']);
        }

        try {
            $pdo  = $this->mssql->connect($host, $port, $database, $username, $password);
            $rows = $this->mssql->runQuery($pdo, $this->mssql->cleanSql($query));

            $check = $request->input('check');
            $table = $request->input('table');

            if ($check !== null || $table !== null) {
                $rows = array_values(array_filter($rows, function ($r) use ($check, $table) {
                    $rChk = null; $rTbl = null;
                    foreach (['CheckNumber','check_number','ChkNum','CHECKNUMBER','checknumber'] as $k) {
                        if (array_key_exists($k, $r)) { $rChk = $r[$k]; break; }
                    }
                    foreach (['TableNumber','table_number','TABLENUMBER','TableNo','tableno'] as $k) {
                        if (array_key_exists($k, $r)) { $rTbl = $r[$k]; break; }
                    }
                    if ($check !== null && (string) $rChk !== (string) $check) return false;
                    if ($table !== null && (string) $rTbl !== (string) $table) return false;
                    return true;
                }));
            }

            return response()->json(['success' => true, 'count' => count($rows), 'rows' => $rows]);
        } catch (\Exception $e) {
            Log::error('KDS raw sorgu hatası', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'KDS bağlantı hatası oluştu.']);
        }
    }

    public function kitchenPosApi()
    {
        // Ekran temizleme saati dolduysa bu anket tetikler (gunde bir kez)
        ScreenCleaner::clearIfDue();

        $host     = (string) Setting::get('mssql_kds_host', '');
        $port     = (string) Setting::get('mssql_kds_port', '1433');
        $database = (string) Setting::get('mssql_kds_database', '');
        $username = (string) Setting::get('mssql_kds_username', '');
        $password = (string) Setting::get('mssql_kds_password', '');
        $query    = trim((string) Setting::get('mssql_kds_query', ''));

        if ($query === '' || !$host || !$database || !$username) {
            return response()->json([
                'success'  => false,
                'message'  => 'KDS MSSQL ayarları/sorgusu eksik. Admin → MSSQL Ayarları → KDS sekmesinden tanımlayın.',
                'orders'   => [],
                'messages' => [],
            ]);
        }

        try {
            $pdo  = $this->mssql->connect($host, $port, $database, $username, $password);
            $rows = $this->mssql->runQuery($pdo, $this->mssql->cleanSql($query));

            // ── Satır bazlı kalıcı unit anahtarı + local first_seen_at ──────────────
            // Symphony, ürün eklenince tüm satırların ItemTime'ını günceller.
            // MSSQL ItemTime'ına güvenemeyiz; ilk gördüğümüz anı local DB'ye kaydederiz.
            // v1.3 sorguda UnitID kolonu yoktur: her satır için kalıcı bir anahtar
            // üretilir, aksi halde order_time her poll'da yeniden doğar (sayaç 00:00 takılması).
            $mssql = $this->mssql;
            $resolveUnitId = function ($row) use ($mssql): string {
                $uid = (string) $mssql->getField($row, ['UnitID', 'unit_id', 'UNITID'], '');
                if ($uid !== '') {
                    return $uid;
                }
                $itemId = $mssql->getField($row, ['ItemID', 'item_id'], null);
                $dtlSeq = (int) $mssql->getField($row, ['DtlSeq', 'dtl_seq'], 0);
                $name   = (string) $mssql->getField($row, ['ProductName', 'product_name', 'Name'], '');
                $note   = (string) $mssql->getField($row, ['MessageNote', 'message_note', 'RefInfo'], '');
                return ($itemId ? $itemId : 'u') . '-' . ($dtlSeq ?: substr(md5($name . $note), 0, 8));
            };

            $rowUnitIds = [];
            $unitIdCheckMap = [];
            foreach ($rows as $i => $r) {
                $uid = $resolveUnitId($r);
                $rowUnitIds[$i] = $uid;
                $cn = $mssql->getField($r, ['CheckNumber', 'check_number', 'ChkNum'], null);
                $unitIdCheckMap[$uid] = $cn;
            }

            $allUnitIds = array_keys($unitIdCheckMap);
            $existingLocalTimes = $allUnitIds
                ? DB::table('kitchen_item_times')->whereIn('unit_id', $allUnitIds)->pluck('first_seen_at', 'unit_id')
                : collect();

            $nowTs = now()->format('Y-m-d H:i:s');
            $insertBatch = [];
            foreach ($allUnitIds as $uid) {
                if (!$existingLocalTimes->has($uid)) {
                    $insertBatch[] = ['unit_id' => $uid, 'check_number' => $unitIdCheckMap[$uid] ?? null, 'first_seen_at' => $nowTs];
                }
            }
            if (!empty($insertBatch)) {
                DB::table('kitchen_item_times')->insertOrIgnore($insertBatch);
                $existingLocalTimes = DB::table('kitchen_item_times')->whereIn('unit_id', $allUnitIds)->pluck('first_seen_at', 'unit_id');
            }

            // Mutfak filtresi — RVC başına (44=Pool Bar, 81=Rocks Patisserie) ayrı,
            // kategori başına (1/2/3/4, 99+98=mesajlar, 0=etiketsiz) ayrı tick.
            $kitchenVisible = [];
            foreach (array_keys(KitchenFilter::RVCS) as $filterRvcId) {
                $kitchenVisible[$filterRvcId] = KitchenFilter::visibleMap($filterRvcId);
            }

            $checks              = [];
            $checkless           = [];
            $comboParentIdxByKey = [];
            $lastUrunIdxByKey    = [];

            foreach ($rows as $rowIndex => $row) {
                $mssql = $this->mssql;
                $checkNum    = $mssql->getField($row, ['CheckNumber', 'check_number', 'ChkNum'], null);
                $unitId      = $rowUnitIds[$rowIndex];
                $itemId      = $mssql->getField($row, ['ItemID', 'item_id'], null);
                $tableNo     = (string) $mssql->getField($row, ['TableNumber', 'table_number'], '');
                $rvc         = $mssql->getField($row, ['RevenueCenter', 'revenue_center'], '');
                $rvcId       = (int) $mssql->getField($row, ['RevenueCenterID', 'revenue_center_id'], 0);
                $status      = (string) $mssql->getField($row, ['CheckStatus', 'check_status', 'Status', 'status'], '');
                $name        = (string) $mssql->getField($row, ['ProductName', 'product_name', 'Name'], '');
                $note        = (string) $mssql->getField($row, ['MessageNote', 'message_note', 'RefInfo'], '');
                $isCondiment = (bool)(int) $mssql->getField($row, ['IsCondiment', 'is_condiment'], 0);
                $isComboItem = (bool)(int) $mssql->getField($row, ['IsComboItem', 'is_combo_item'], 0);
                $isReturned  = (bool)(int) $mssql->getField($row, ['IsReturned', 'is_returned'], 0);
                $lineKindRaw = $mssql->getField($row, ['LineKind', 'line_kind', 'LineType', 'line_type'], null);
                $lineKind    = $lineKindRaw !== null ? strtoupper((string) $lineKindRaw) : 'URUN';
                // Yeni sorgu LineType değerleri → LineKind karşılıkları
                if ($lineKind === 'PRODUCT') $lineKind = 'URUN';
                if (in_array($lineKind, ['KITCHEN_MESSAGE', 'BAR_MESSAGE'], true)) $lineKind = 'MESAJ';
                $waiterFull  = trim(
                    (string) $mssql->getField($row, ['WaiterName', 'waiter_name'], '') . ' ' .
                    (string) $mssql->getField($row, ['WaiterSurname', 'waiter_surname'], '')
                );

                // Eski sorgu uyumluluğu (LineKind/LineType yoksa): MajGrp/MajorGroupID=99 → MESAJ
                $majGrpId = (int) $mssql->getField($row, ['MajorGroupID', 'major_group_id', 'MajGrp', 'maj_grp'], 0);
                if ($lineKindRaw === null && $majGrpId === 99) $lineKind = 'MESAJ';

                $isMessage = ($lineKind === 'MESAJ');
                $isMars    = ($lineKind === 'MARS');
                $isCombo   = ($lineKind === 'COMBO') || $isComboItem;
                $hasCheck  = $checkNum !== null && (int) $checkNum > 0;

                // RVC + kategori filtresi. first_seen kaydı pre-pass'ta tüm satırlar için
                // tutulur; filtre kapatılıp açılırsa sayaçlar doğru devam eder.
                // Bilinmeyen RVC (eski sorgu, RVC alanı yok) filtrelenmez.
                // Mesajlar: BAR_MESSAGE → İçecek Mesajı (98), diğerleri → Mutfak Mesajı (99).
                // Symphony'de iki mesaj türü de MajGrp 99'dur; ayrım satır adından gelir.
                $filterMg = $majGrpId;
                if ($isMessage || $isMars) {
                    $filterMg = strtoupper((string) $lineKindRaw) === 'BAR_MESSAGE' ? 98 : 99;
                }
                if (isset($kitchenVisible[$rvcId][$filterMg]) && !$kitchenVisible[$rvcId][$filterMg]) {
                    continue;
                }

                // Mesajlar için ItemID yoksa hash üret
                if (($isMessage || $isMars) && (!$itemId || (string) $itemId === '0')) {
                    $itemId = ($isMars ? 'mars-' : 'm-') . substr(md5(($tableNo ?? '') . '|' . ($checkNum ?? '') . '|' . $unitId . '|' . $name . '|' . ($note ?? '')), 0, 16);
                }

                $localTime   = $existingLocalTimes->get($unitId, $nowTs);
                $localCarbon = \Carbon\Carbon::parse($localTime, config('app.timezone'));
                $itemTimeIso = $localCarbon->toIso8601String();

                $item = [
                    'unit_ids'    => [$unitId],
                    'item_id'     => $itemId,
                    'qty'         => 1,
                    'name'        => $name,
                    'note'        => $note,
                    'is_combo'    => $isCombo,
                    'is_condiment'=> $isCondiment,
                    'is_returned' => $isReturned,
                    'line_kind'   => $lineKind,
                    'item_time'   => $itemTimeIso,
                    'age_seconds' => max(0, (int) $localCarbon->diffInSeconds(now())),
                ];

                // Mesaj ve Mars → checkless veya check.messages
                if ($isMessage || $isMars) {
                    if (!$hasCheck) {
                        $checkless[] = array_merge($item, ['table_no' => $tableNo, 'rvc' => $rvc, 'rvc_id' => $rvcId]);
                        continue;
                    }
                    $key = (string) $checkNum;
                    if (!isset($checks[$key])) {
                        $checks[$key] = $this->newCheck($checkNum, $tableNo, $rvc, $rvcId, $waiterFull, $status);
                    }
                    $checks[$key]['messages'][] = $item;
                    continue;
                }

                // Ürün (URUN, COMBO, condiment) → items
                $key = $hasCheck ? (string) $checkNum : ('T' . $tableNo);
                if (!isset($checks[$key])) {
                    $checks[$key] = $this->newCheck($checkNum, $tableNo, $rvc, $rvcId, $waiterFull, $status);
                }

                $items   = &$checks[$key]['items'];
                $lastIdx = count($items) - 1;

                if ($isCombo) {
                    $pIdx = $comboParentIdxByKey[$key] ?? null;
                    if ($pIdx !== null && isset($items[$pIdx]) && $items[$pIdx]['name'] !== $name) {
                        if (!isset($items[$pIdx]['sub_items'])) $items[$pIdx]['sub_items'] = [];
                        $items[$pIdx]['sub_items'][] = ['unit_ids' => [$unitId], 'item_id' => $itemId, 'name' => $name, 'note' => $note, 'is_returned' => $isReturned, 'item_time' => $itemTimeIso];
                        $items[$pIdx]['unit_ids'][]  = $unitId;
                    } else {
                        $item['sub_items'] = [];
                        $items[] = $item;
                        $comboParentIdxByKey[$key] = count($items) - 1;
                    }
                } elseif ($isCondiment) {
                    $pIdx = $lastUrunIdxByKey[$key] ?? null;
                    if ($pIdx !== null && isset($items[$pIdx])) {
                        if (!isset($items[$pIdx]['sub_items'])) $items[$pIdx]['sub_items'] = [];
                        $items[$pIdx]['sub_items'][] = ['unit_ids' => [$unitId], 'item_id' => $itemId, 'name' => $name, 'note' => $note, 'is_returned' => $isReturned, 'item_time' => $itemTimeIso];
                        $items[$pIdx]['unit_ids'][]  = $unitId;
                    } else {
                        $item['sub_items'] = [];
                        $items[] = $item;
                    }
                } elseif (!$isReturned && $lineKind === 'URUN'
                    && $lastIdx >= 0
                    && $items[$lastIdx]['item_id'] == $itemId
                    && $items[$lastIdx]['line_kind'] === 'URUN'
                    && !$items[$lastIdx]['is_combo']
                    && !$items[$lastIdx]['is_returned']
                ) {
                    // Ardışık aynı URUN → qty artır
                    $items[$lastIdx]['unit_ids'][] = $unitId;
                    $items[$lastIdx]['qty']++;
                    if ($itemTimeIso < $items[$lastIdx]['item_time']) {
                        $items[$lastIdx]['item_time'] = $itemTimeIso;
                    }
                    unset($comboParentIdxByKey[$key]);
                } else {
                    $item['sub_items'] = [];
                    $items[] = $item;
                    $lastUrunIdxByKey[$key] = count($items) - 1;
                    unset($comboParentIdxByKey[$key]);
                }
                unset($items);

                if (!$checks[$key]['order_time'] || $localTime < $checks[$key]['order_time']) {
                    $checks[$key]['order_time'] = $localTime;
                }
            }

            // order_time → ISO8601 + sunucu hesaplı yaş (sayaçlar için)
            foreach ($checks as &$chk) {
                if ($chk['order_time']) {
                    try {
                        $ot = \Carbon\Carbon::parse($chk['order_time'], config('app.timezone'));
                        $chk['order_time'] = $ot->toIso8601String();
                        $chk['age_seconds'] = max(0, (int) $ot->diffInSeconds(now()));
                    } catch (\Exception) {}
                }
            }
            unset($chk);

            // Onaylanan checksiz mesajları filtrele
            $completedMsgKeys = DB::table('kitchen_pos_completions')
                ->where('kind', 'checkless_msg')
                ->pluck('group_key')
                ->all();
            if (!empty($completedMsgKeys)) {
                $completedMsgKeys = array_flip($completedMsgKeys);
                foreach ($checks as $k => $chk) {
                    $checks[$k]['messages'] = array_values(array_filter(
                        $chk['messages'],
                        fn($m) => !isset($completedMsgKeys['M' . ($m['item_id'] ?? '')])
                    ));
                }
                $checkless = array_values(array_filter(
                    $checkless,
                    fn($m) => !isset($completedMsgKeys['M' . ($m['item_id'] ?? '')])
                ));
            }

            // Tamamlanmış Symphony hesaplarını filtrele (ek sipariş tespiti)
            $completedCheckRows = DB::table('kitchen_pos_completions')
                ->where('kind', 'check')
                ->select('group_key', 'served_item_keys')
                ->get()
                ->keyBy('group_key');

            if ($completedCheckRows->isNotEmpty()) {
                foreach ($checks as $k => $chk) {
                    if (!$completedCheckRows->has($k)) continue;
                    $servedKeys = json_decode($completedCheckRows[$k]->served_item_keys ?? '[]', true) ?: [];
                    if (empty($servedKeys)) {
                        // Yalnız mesaj satırlı (ürünsüz) hesap: "Komple Hazır" sonrası kart ekranda kalmasın
                        if (empty($chk['items'])) {
                            unset($checks[$k]);
                            continue;
                        }
                        $checks[$k]['is_reopened'] = true;
                        continue;
                    }
                    $servedSet = array_flip($servedKeys);
                    $newItems  = [];
                    foreach ($chk['items'] as $item) {
                        if (!empty($item['unit_ids'])) {
                            $newUnitIds = array_values(array_filter($item['unit_ids'], fn($uid) => !isset($servedSet[$uid])));
                            if (!empty($newUnitIds)) {
                                $item['unit_ids'] = $newUnitIds;
                                $item['qty'] = count($newUnitIds);
                                $newItems[] = $item;
                            }
                        } else {
                            $fk = ($item['item_id'] !== null && $item['item_id'] !== '')
                                ? (string) $item['item_id']
                                : (($item['dtl_seq'] ?? 0) . '|' . $item['name']);
                            if (!isset($servedSet[$fk])) $newItems[] = $item;
                        }
                    }
                    if (empty($newItems)) {
                        unset($checks[$k]);
                    } else {
                        $checks[$k]['items']      = $newItems;
                        $checks[$k]['is_addition']= true;
                        $earliest = collect($newItems)->filter(fn($i) => !empty($i['item_time']))->min('item_time');
                        if ($earliest) $checks[$k]['order_time'] = $earliest;
                    }
                }
            }

            // Ürün bazlı hazır işaretleri (kind=item) → satırlara served bayrağı
            $partialRows = DB::table('kitchen_pos_completions')
                ->where('kind', 'item')
                ->select('group_key', 'served_item_keys')
                ->get()
                ->keyBy('group_key');
            foreach ($checks as $k => $chk) {
                if (!$partialRows->has($k)) continue;
                $servedSet = array_flip(json_decode($partialRows[$k]->served_item_keys ?? '[]', true) ?: []);
                foreach ($checks[$k]['items'] as $i => $item) {
                    if (!empty($item['served'])) continue;
                    $units = (!empty($item['unit_ids']))
                        ? $item['unit_ids']
                        : ((isset($item['item_id']) && $item['item_id'] !== null && $item['item_id'] !== '') ? [(string) $item['item_id']] : []);
                    if (empty($units)) continue;
                    $allServed = true;
                    foreach ($units as $uid) {
                        if (!isset($servedSet[(string) $uid])) { $allServed = false; break; }
                    }
                    if ($allServed) $checks[$k]['items'][$i]['served'] = true;
                }
            }

            $checks = array_filter($checks, fn($c) => !empty($c['items']) || !empty($c['messages']));
            uasort($checks, fn($a, $b) => strcmp((string) $b['order_time'], (string) $a['order_time']));

            $completedLimit = (int) Setting::get('kitchen_completed_display', 6);
            // SON seritleri sadece son ekran temizlemesinden sonrakileri gosterir
            $clearedAt = ScreenCleaner::clearedAt();

            $completedMsgs = DB::table('kitchen_pos_completions')
                ->where('kind', 'checkless_msg')
                ->when($clearedAt, fn ($q) => $q->where('completed_at', '>', $clearedAt))
                ->orderByDesc('completed_at')
                ->limit($completedLimit)
                ->get()
                ->map(fn($r) => [
                    'is_message'   => true,
                    'group_key'    => $r->group_key,
                    'table_no'     => $r->table_no,
                    'check_number' => $r->check_number,
                    'name'         => $r->name,
                    'note'         => $r->note,
                    'qty'          => (int) ($r->qty ?? 1),
                    'prep_seconds' => $r->prep_seconds,
                    'completed_at' => $r->completed_at,
                ])->all();

            $completedChecks = DB::table('kitchen_pos_completions')
                ->where('kind', 'check')
                ->when($clearedAt, fn ($q) => $q->where('completed_at', '>', $clearedAt))
                ->orderByDesc('completed_at')
                ->limit($completedLimit)
                ->get()
                ->map(fn($r) => [
                    'is_check'     => true,
                    'group_key'    => $r->group_key,
                    'table_no'     => $r->table_no,
                    'check_number' => $r->check_number,
                    'prep_seconds' => $r->prep_seconds,
                    'completed_at' => $r->completed_at,
                ])->all();

            // Tek ürün onayları (kind=item) → alt şeritte "ÜRÜN" çipleri
            $completedItems = DB::table('kitchen_pos_completions')
                ->where('kind', 'item')
                ->when($clearedAt, fn ($q) => $q->where('completed_at', '>', $clearedAt))
                ->orderByDesc('completed_at')
                ->limit($completedLimit)
                ->get();
            $itemsOut = [];
            foreach ($completedItems as $r) {
                $servedSet = array_flip(json_decode((string) ($r->served_item_keys ?? '[]'), true) ?: []);
                $names = [];
                $chk = $checks[$r->group_key] ?? null;
                if ($chk) {
                    foreach ($chk['items'] as $item) {
                        if (!empty($item['is_returned'])) continue;
                        $units = (!empty($item['unit_ids']))
                            ? $item['unit_ids']
                            : ((isset($item['item_id']) && $item['item_id'] !== null && $item['item_id'] !== '') ? [(string) $item['item_id']] : []);
                        $servedQty = 0;
                        foreach ($units as $uid) { if (isset($servedSet[(string) $uid])) $servedQty++; }
                        if ($servedQty > 0) $names[] = $item['name'] . ' x' . $servedQty;
                    }
                }
                if (empty($names) && !empty($r->name)) {
                    $names[] = ($r->qty > 1 ? 'x' . $r->qty . ' ' : '') . $r->name;
                }
                if (empty($names)) continue;
                $itemsOut[] = [
                    'is_item'      => true,
                    'group_key'    => $r->group_key,
                    'table_no'     => $r->table_no,
                    'check_number' => $r->check_number,
                    'items_list'   => implode(' · ', $names),
                    'completed_at' => $r->completed_at,
                ];
            }

            // Bugun sayaci: son temizlemeden sonraki tamamlamalar
            $completedTodayCount = DB::table('kitchen_pos_completions')
                ->whereDate('completed_at', today())
                ->when($clearedAt, fn ($q) => $q->where('completed_at', '>', $clearedAt))
                ->where('kind', '!=', 'item')
                ->count();

            return response()->json([
                'success'         => true,
                'orders'          => array_values($checks),
                'messages'        => $checkless,
                'completed'       => [],
                'completed_msgs'  => $completedMsgs,
                'completed_checks'=> $completedChecks,
                'completed_items' => $itemsOut,
                'completed_limit' => $completedLimit,
                'completed_today' => $completedTodayCount,
                'fetched_at'      => now()->format('H:i:s'),
                'server_now' => \App\Support\Clock::nowIso(),
                'count'           => count($checks),
            ]);
        } catch (\Exception $e) {
            Log::error('KDS MSSQL sorgu hatası', ['error' => $e->getMessage()]);
            return response()->json([
                'success'         => false,
                'message'         => 'KDS bağlantı hatası oluştu.',
                'orders'          => [],
                'messages'        => [],
                'completed'       => [],
                'completed_msgs'  => [],
                'completed_checks'=> [],
                'completed_items' => [],
            ]);
        }
    }

    // ──────────────────────────────────────────────
    // AKDS: Ana Mutfak canlı sipariş API
    // ──────────────────────────────────────────────

    public function kitchenAnaApi()
    {
        $host      = (string) Setting::get('mssql_akds_host', '');
        $port      = (string) Setting::get('mssql_akds_port', '1433');
        $database  = (string) Setting::get('mssql_akds_database', '');
        $username  = (string) Setting::get('mssql_akds_username', '');
        $password  = (string) Setting::get('mssql_akds_password', '');
        $query     = trim((string) Setting::get('mssql_akds_query', ''));
        $rvcFilter = trim((string) Setting::get('mssql_akds_rvc_filter', ''));

        if ($query === '' || !$host || !$database || !$username) {
            return response()->json([
                'success' => false,
                'message' => 'Ana Mutfak (AKDS) MSSQL ayarları/sorgusu eksik. Admin → MSSQL Ayarları → Ana Mutfak (AKDS) sekmesinden tanımlayın.',
                'orders'  => [],
            ]);
        }

        // {{RVC}} placeholder → RVC filtre değeriyle değiştir (sadece sayı / virgülle ayrılmış liste)
        if (str_contains($query, '{{RVC}}')) {
            if ($rvcFilter === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'SQL sorgusunda {{RVC}} placeholder var ama RVC Filtresi boş. Admin → MSSQL Ayarları → Ana Mutfak (AKDS) → RVC Filtresi alanını doldurun.',
                    'orders'  => [],
                ]);
            }
            if (!preg_match('/^\d+(\s*,\s*\d+)*$/', $rvcFilter)) {
                return response()->json([
                    'success' => false,
                    'message' => 'RVC Filtresi sadece sayısal değer veya virgülle ayrılmış liste olabilir (örn: 43 ya da 43, 44, 45).',
                    'orders'  => [],
                ]);
            }
            $safeRvc = implode(', ', array_map('trim', explode(',', $rvcFilter)));
            $query   = str_replace('{{RVC}}', $safeRvc, $query);
        }

        try {
            $pdo  = $this->mssql->connect($host, $port, $database, $username, $password);
            $rows = $this->mssql->runQuery($pdo, $this->mssql->cleanSql($query));

            $checks = [];
            foreach ($rows as $row) {
                $mssql     = $this->mssql;
                $checkNum  = $mssql->getField($row, ['CheckNumber', 'check_number', 'ChkNum'], null);
                $tableNo   = (string) $mssql->getField($row, ['TableNumber', 'table_number'], '');
                $orderTime = $mssql->getField($row, ['OrderTime', 'order_time'], null);
                $itemTime  = $mssql->getField($row, ['ItemTime', 'item_time'], null);
                $rvc       = $mssql->getField($row, ['RevenueCenter', 'revenue_center'], '');
                $covers    = (int) $mssql->getField($row, ['Covers', 'covers'], 0);
                $qty       = (int) $mssql->getField($row, ['Qty', 'qty', 'Quantity'], 1);
                $name      = (string) $mssql->getField($row, ['ProductName', 'product_name', 'Name'], '');
                $note      = (string) $mssql->getField($row, ['MessageNote', 'message_note', 'RefInfo'], '');
                $itemId    = $mssql->getField($row, ['ItemID', 'item_id'], null);

                $groupKey = $checkNum !== null && (int) $checkNum > 0
                    ? (string) $checkNum
                    : 'T' . $tableNo;

                if (!isset($checks[$groupKey])) {
                    $checks[$groupKey] = [
                        'group_key'    => $groupKey,
                        'check_number' => $checkNum,
                        'table_no'     => $tableNo,
                        'rvc'          => $rvc,
                        'covers'       => $covers,
                        'order_time'   => $orderTime,
                        'items'        => [],
                    ];
                }

                if ($orderTime && (!$checks[$groupKey]['order_time'] || strcmp((string) $orderTime, (string) $checks[$groupKey]['order_time']) < 0)) {
                    $checks[$groupKey]['order_time'] = $orderTime;
                }

                $effectiveItemTime = $itemTime ?? $orderTime;
                $checks[$groupKey]['items'][] = [
                    'item_id'   => $itemId,
                    'qty'       => $qty,
                    'name'      => $name,
                    'note'      => $note,
                    'item_time' => $effectiveItemTime
                        ? \Carbon\Carbon::parse((string) $effectiveItemTime, 'Europe/Istanbul')->toIso8601String()
                        : null,
                ];

                if ($effectiveItemTime && (!$checks[$groupKey]['order_time'] || strcmp((string) $effectiveItemTime, (string) $checks[$groupKey]['order_time']) < 0)) {
                    $checks[$groupKey]['order_time'] = $effectiveItemTime;
                }
            }

            // order_time → ISO8601
            foreach ($checks as &$chk) {
                if ($chk['order_time']) {
                    try {
                        $chk['order_time'] = \Carbon\Carbon::parse((string) $chk['order_time'], 'Europe/Istanbul')->toIso8601String();
                    } catch (\Exception) {}
                }
            }
            unset($chk);

            // Tamamlanmış hesapları filtrele
            $completedCheckRows = DB::table('kitchen_pos_completions')
                ->where('kind', 'check')
                ->select('group_key', 'served_item_keys')
                ->get()
                ->keyBy('group_key');

            if ($completedCheckRows->isNotEmpty()) {
                foreach ($checks as $k => $chk) {
                    if (!$completedCheckRows->has($k)) continue;
                    $servedKeys = json_decode($completedCheckRows[$k]->served_item_keys ?? '[]', true) ?: [];
                    if (empty($servedKeys)) { unset($checks[$k]); continue; }
                    $servedSet = array_flip($servedKeys);
                    $newItems  = array_values(array_filter($chk['items'], function ($item) use ($servedSet) {
                        $key = ($item['item_id'] !== null && $item['item_id'] !== '')
                            ? (string) $item['item_id']
                            : (($item['dtl_seq'] ?? 0) . '|' . $item['name']);
                        return !isset($servedSet[$key]);
                    }));
                    if (empty($newItems)) {
                        unset($checks[$k]);
                    } else {
                        $checks[$k]['items']       = $newItems;
                        $checks[$k]['is_addition'] = true;
                        $earliest = collect($newItems)->filter(fn($i) => !empty($i['item_time']))->min('item_time');
                        if ($earliest) $checks[$k]['order_time'] = $earliest;
                    }
                }
            }

            uasort($checks, fn($a, $b) => strcmp((string) ($b['order_time'] ?? ''), (string) ($a['order_time'] ?? '')));

            return response()->json([
                'success'    => true,
                'orders'     => array_values($checks),
                'fetched_at' => now()->format('H:i:s'),
                'server_now' => \App\Support\Clock::nowIso(),
                'count'      => count($checks),
            ]);
        } catch (\Exception $e) {
            Log::error('AKDS MSSQL sorgu hatası', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Ana Mutfak bağlantı hatası oluştu.',
                'orders'  => [],
            ]);
        }
    }

    // ──────────────────────────────────────────────
    // Yardımcı
    // ──────────────────────────────────────────────

    private function newCheck($checkNum, string $tableNo, $rvc, int $rvcId, string $waiterFull, string $status): array
    {
        return [
            'check_number' => $checkNum,
            'table_no'     => $tableNo,
            'rvc'          => $rvc,
            'rvc_id'       => $rvcId,
            'waiter_name'  => $waiterFull,
            'order_time'   => null,
            'status'       => $status,
            'items'        => [],
            'messages'     => [],
        ];
    }
}
