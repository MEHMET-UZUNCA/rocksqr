<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Services\MssqlService;
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
            'kind'         => 'required|in:check,checkless_msg',
            'group_key'    => 'required|string|max:64',
            'check_number' => 'nullable|string|max:64',
            'table_no'     => 'nullable|string|max:32',
            'name'         => 'nullable|string|max:255',
            'note'         => 'nullable|string|max:255',
            'qty'          => 'nullable|integer|min:1|max:999',
            'item_keys'    => 'nullable|array',
            'item_keys.*'  => 'string|max:128',
        ]);

        $existing = DB::table('kitchen_pos_completions')->where('group_key', $validated['group_key'])->first();
        $existingKeys = $existing?->served_item_keys ? json_decode($existing->served_item_keys, true) : [];
        $newKeys = array_values(array_unique(array_merge(
            $existingKeys ?: [],
            array_filter($validated['item_keys'] ?? [], fn($k) => $k !== '')
        )));

        DB::table('kitchen_pos_completions')->updateOrInsert(
            ['group_key' => $validated['group_key']],
            [
                'kind'             => $validated['kind'],
                'check_number'     => $validated['check_number'] ?? null,
                'table_no'         => $validated['table_no'] ?? null,
                'name'             => $validated['name'] ?? null,
                'note'             => $validated['note'] ?? null,
                'qty'              => $validated['qty'] ?? 1,
                'completed_at'     => now(),
                'served_item_keys' => json_encode($newKeys),
            ]
        );

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

            // ── UnitID local first_seen_at ──────────────────────────────────────────
            // Symphony, ürün eklenince tüm satırların ItemTime'ını günceller.
            // MSSQL ItemTime'ına güvenemeyiz; ilk gördüğümüz anı local DB'ye kaydederiz.
            $unitIdCheckMap = [];
            foreach ($rows as $r) {
                $uid = null;
                foreach (['UnitID', 'unit_id', 'UNITID'] as $k) {
                    if (!empty($r[$k])) { $uid = (string) $r[$k]; break; }
                }
                if (!$uid) continue;
                $cn = null;
                foreach (['CheckNumber', 'check_number', 'ChkNum'] as $k) {
                    if (!empty($r[$k])) { $cn = (string) $r[$k]; break; }
                }
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

            $checks              = [];
            $checkless           = [];
            $comboParentIdxByKey = [];
            $lastUrunIdxByKey    = [];

            foreach ($rows as $row) {
                $mssql = $this->mssql;
                $checkNum    = $mssql->getField($row, ['CheckNumber', 'check_number', 'ChkNum'], null);
                $unitId      = (string) $mssql->getField($row, ['UnitID', 'unit_id'], '');
                $itemId      = $mssql->getField($row, ['ItemID', 'item_id'], null);
                $tableNo     = (string) $mssql->getField($row, ['TableNumber', 'table_number'], '');
                $rvc         = $mssql->getField($row, ['RevenueCenter', 'revenue_center'], '');
                $rvcId       = (int) $mssql->getField($row, ['RevenueCenterID', 'revenue_center_id'], 0);
                $status      = (string) $mssql->getField($row, ['Status', 'status'], '');
                $name        = (string) $mssql->getField($row, ['ProductName', 'product_name', 'Name'], '');
                $note        = (string) $mssql->getField($row, ['MessageNote', 'message_note', 'RefInfo'], '');
                $isCondiment = (bool)(int) $mssql->getField($row, ['IsCondiment', 'is_condiment'], 0);
                $isComboItem = (bool)(int) $mssql->getField($row, ['IsComboItem', 'is_combo_item'], 0);
                $isReturned  = (bool)(int) $mssql->getField($row, ['IsReturned', 'is_returned'], 0);
                $lineKind    = strtoupper((string) $mssql->getField($row, ['LineKind', 'line_kind'], 'URUN'));
                $waiterFull  = trim(
                    (string) $mssql->getField($row, ['WaiterName', 'waiter_name'], '') . ' ' .
                    (string) $mssql->getField($row, ['WaiterSurname', 'waiter_surname'], '')
                );

                // Eski sorgu uyumluluğu: LineKind yoksa MajGrp=99 → MESAJ
                if ($lineKind === 'URUN') {
                    $majGrp = (int) $mssql->getField($row, ['MajGrp', 'maj_grp'], 0);
                    if ($majGrp === 99) $lineKind = 'MESAJ';
                }

                $isMessage = ($lineKind === 'MESAJ');
                $isMars    = ($lineKind === 'MARS');
                $isCombo   = ($lineKind === 'COMBO') || $isComboItem;
                $hasCheck  = $checkNum !== null && (int) $checkNum > 0;

                // UnitID yoksa (eski sorgu) → ItemID + hash ile üret
                if ($unitId === '') {
                    $dtlSeq = (int) $mssql->getField($row, ['DtlSeq', 'dtl_seq'], 0);
                    $unitId = ($itemId ? $itemId : 'u') . '-' . ($dtlSeq ?: substr(md5($name . $note), 0, 8));
                }

                // Mesajlar için ItemID yoksa hash üret
                if (($isMessage || $isMars) && (!$itemId || (string) $itemId === '0')) {
                    $itemId = ($isMars ? 'mars-' : 'm-') . substr(md5(($tableNo ?? '') . '|' . ($checkNum ?? '') . '|' . $unitId . '|' . $name . '|' . ($note ?? '')), 0, 16);
                }

                $localTime   = $existingLocalTimes->get($unitId, $nowTs);
                $itemTimeIso = \Carbon\Carbon::parse($localTime, config('app.timezone'))->toIso8601String();

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

            // order_time → ISO8601
            foreach ($checks as &$chk) {
                if ($chk['order_time']) {
                    try {
                        $chk['order_time'] = \Carbon\Carbon::parse($chk['order_time'], config('app.timezone'))->toIso8601String();
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

            $checks = array_filter($checks, fn($c) => !empty($c['items']) || !empty($c['messages']));
            uasort($checks, fn($a, $b) => strcmp((string) $b['order_time'], (string) $a['order_time']));

            $completedLimit = (int) Setting::get('kitchen_completed_display', 6);

            $completedMsgs = DB::table('kitchen_pos_completions')
                ->where('kind', 'checkless_msg')
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
                    'completed_at' => $r->completed_at,
                ])->all();

            $completedChecks = DB::table('kitchen_pos_completions')
                ->where('kind', 'check')
                ->orderByDesc('completed_at')
                ->limit($completedLimit)
                ->get()
                ->map(fn($r) => [
                    'is_check'     => true,
                    'group_key'    => $r->group_key,
                    'table_no'     => $r->table_no,
                    'check_number' => $r->check_number,
                    'completed_at' => $r->completed_at,
                ])->all();

            $completedTodayCount = DB::table('kitchen_pos_completions')->whereDate('completed_at', today())->count();

            return response()->json([
                'success'         => true,
                'orders'          => array_values($checks),
                'messages'        => $checkless,
                'completed'       => [],
                'completed_msgs'  => $completedMsgs,
                'completed_checks'=> $completedChecks,
                'completed_limit' => $completedLimit,
                'completed_today' => $completedTodayCount,
                'fetched_at'      => now()->format('H:i:s'),
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
