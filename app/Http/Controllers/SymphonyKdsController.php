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
        return $this->kdsPayload('mssql_kds_rvc_filter');
    }

    // Mutfak ve Ana Mutfak (AKDS) ekranları aynı v1.4 sorguyu ve payload
    // mantığını paylaşır; yalnız RVC yazılım filtresi ayar anahtarıyla ayrışır.
    private function kdsPayload(string $rvcFilterKey)
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

            // v1.4 sorgu ödeme/indirim/diğer satırları da döndürür (ODEME, INDIRIM, DIGER);
            // bunlar mutfakta ürün gibi görünmesin diye yazılım tarafında elenir.
            // Eski sorguda LineKind kolonu yoktur → boş döner, tüm satırlar korunur.
            $mssql = $this->mssql;
            $rows  = array_values(array_filter($rows, function ($r) use ($mssql) {
                $lk = strtoupper((string) $mssql->getField($r, ['LineKind', 'line_kind', 'LineType', 'line_type'], ''));
                if ($lk === '') return true;
                return in_array($lk, ['URUN', 'MODIFIER', 'MESAJ', 'MARS', 'IADE', 'COMBO', 'PRODUCT', 'KITCHEN_MESSAGE', 'BAR_MESSAGE'], true);
            }));

            // RVC / Gelir Merkezi yazılım filtresi (sorgunun RVC kapsamı FULL kalır).
            // first_seen kaydı aşağıdaki pre-pass'ta tüm satırlar için tutulur;
            // filtre kapatılıp açılırsa sayaçlar doğru devam eder.
            $rvcFilterIds = self::parseRvcFilter((string) Setting::get($rvcFilterKey, ''));

            // ── Satır bazlı kalıcı unit anahtarı + local first_seen_at ──────────────
            // Symphony, ürün eklenince tüm satırların ItemTime'ını günceller.
            // MSSQL ItemTime'ına güvenemeyiz; ilk gördüğümüz anı local DB'ye kaydederiz.
            // v1.3 sorguda UnitID kolonu yoktur: her satır için kalıcı bir anahtar
            // üretilir, aksi halde order_time her poll'da yeniden doğar (sayaç 00:00 takılması).
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
            foreach (array_keys(KitchenFilter::RVCS + KitchenFilter::ANA_RVCS) as $filterRvcId) {
                $kitchenVisible[$filterRvcId] = KitchenFilter::visibleMap($filterRvcId);
            }

            // Ürün grubu (FamGrp) gizle listeleri — RVC başına; admin Ekran ayarlarından
            // yönetilir (işaretli grup mutfak ekranına yazılmaz).
            $famHide = [];
            foreach (array_keys(KitchenFilter::RVCS + KitchenFilter::ANA_RVCS) as $hideRvcId) {
                $famHide[$hideRvcId] = KitchenFilter::familyHideMap($hideRvcId);
            }

            // Açık Yiyecek/İçecek/Diğer POS kodları (RVC bazlı): fiş içerik çözümü bu satırlara uygulanır
            $openSets = [];

            $checks              = [];
            $checkless           = [];
            $comboParentIdxByKey = [];
            $lastUrunIdxByKey    = [];
            $prodRowIdxByKey     = [];
            $prodMergeIdxByKey   = [];
            $journalGuids        = [];

            foreach ($rows as $rowIndex => $row) {
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

                // v1.4 satır alanları: ürün anahtarı (ProductObjectNumber), satır adedi,
                // üst ürün bağlantısı ve check GUID'i (fiş mesajı çözümü için).
                // Eski sorguda bu kolonlar yoktur → 0/'' döner, eski kod yolları çalışır.
                $objNo        = (int) $mssql->getField($row, ['ProductObjectNumber', 'product_object_number'], 0);
                $rowQty       = (int) $mssql->getField($row, ['Qty', 'qty', 'Quantity', 'SalesCount'], 1);
                $parentItemId = (int) $mssql->getField($row, ['ParentItemID', 'parent_item_id'], 0);
                $checkGid     = (string) $mssql->getField($row, ['CheckGID', 'check_gid'], '');
                $isV14Row     = $objNo > 0;
                $isOpenRow    = false;
                if ($isV14Row && $rvcId > 0) {
                    $openSets[$rvcId] ??= KitchenFilter::openCodeMap($rvcId);
                    $isOpenRow = isset($openSets[$rvcId][$objNo]);
                }

                // RVC / Gelir Merkezi yazılım filtresi (boş = tüm RVC'ler; RVC'siz satır filtrelenmez)
                if ($rvcFilterIds !== [] && $rvcId > 0 && !isset($rvcFilterIds[$rvcId])) {
                    continue;
                }

                // v1.4: kapanmış checkler mutfak ekranında gösterilmez
                if ($status === 'C') continue;

                // Eski sorgu uyumluluğu (LineKind/LineType yoksa): MajGrp/MajorGroupID=99 → MESAJ
                $majGrpId = (int) $mssql->getField($row, ['MajorGroupID', 'major_group_id', 'MajGrp', 'maj_grp'], 0);
                if ($lineKindRaw === null && $majGrpId === 99) $lineKind = 'MESAJ';

                // v1.4 sorguda FamGrpObjNum AS FamilyGroupID gelir; eski sorguda kolon
                // yoktur → 0 döner ve ürün grubu gizlemesi hiçbir satırı etkilemez.
                $famGrp = (int) $mssql->getField($row, ['FamilyGroupID', 'family_group_id', 'FamGrpObjNum', 'fam_grp_obj_num'], 0);

                $isMessage = ($lineKind === 'MESAJ');
                $isMars    = ($lineKind === 'MARS');
                $isCombo   = ($lineKind === 'COMBO') || $isComboItem;
                $hasCheck  = $checkNum !== null && (int) $checkNum > 0;

                // RVC + kategori filtresi. first_seen kaydı pre-pass'ta tüm satırlar için
                // tutulur; filtre kapatılıp açılırsa sayaçlar doğru devam eder.
                // Bilinmeyen RVC (eski sorgu, RVC alanı yok) filtrelenmez.
                // Mesajlar: Bar Mesajı → İçecek Mesajı (98), Mutfak Mesajı ve MARS → Mutfak Mesajı (99).
                // v1.4'te üç mesaj nesnesi de LineKind=MESAJ sütununa iner; Bar Mesaj
                // ayrımı ProductObjectNumber=9001020'den yapılır.
                $filterMg = $majGrpId;
                if ($isMessage || $isMars) {
                    $isBarMsg = strtoupper((string) $lineKindRaw) === 'BAR_MESSAGE'
                        || ($isMessage && $objNo === 9001020);
                    $filterMg = $isBarMsg ? 98 : 99;
                }
                if (isset($kitchenVisible[$rvcId][$filterMg]) && !$kitchenVisible[$rvcId][$filterMg]) {
                    continue;
                }

                // Ürün grubu gizle listesi (FamGrp): yalnız ürün satırlarını hedefler;
                // mesaj/mars satırlarında FamGrp 0'dır, asla gizlenmez.
                if ($famGrp > 0 && isset($famHide[$rvcId][$famGrp])) {
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
                    // v1.4 iade satırları (Qty<0) yalnız görüntü içindir: unit listesine girmez,
                    // hazırlanan/yeni ürün karşılaştırmalarını tetiklemez.
                    'unit_ids'    => (!$isV14Row || $rowQty > 0) ? [$unitId] : [],
                    'unit_qtys'   => (!$isV14Row || $rowQty > 0) ? [$unitId => $isV14Row ? $rowQty : 1] : [],
                    'item_id'     => $itemId,
                    'qty'         => $isV14Row ? max(1, $rowQty) : 1,
                    'qty_net'     => $isV14Row ? $rowQty : null,
                    'qty_returned'=> ($isV14Row && $rowQty < 0) ? abs($rowQty) : 0,
                    'row_count'   => $isV14Row ? 1 : 0,
                    'name'        => $name,
                    'note'        => $note,
                    'is_combo'    => $isCombo,
                    'is_condiment'=> $isCondiment,
                    'is_returned' => $isReturned || ($isV14Row && $rowQty < 0),
                    'line_kind'   => $lineKind,
                    'item_time'   => $itemTimeIso,
                    'age_seconds' => max(0, (int) $localCarbon->diffInSeconds(now())),
                ];

                // Açık satır: gerçek içerik POS_JOURNAL_LOG fişinden çözülür
                if ($isOpenRow && $checkGid !== '') {
                    $item['_gid'] = $checkGid;
                    $item['_open'] = true;
                    $journalGuids[$checkGid] = true;
                }

                // Mesaj ve Mars → checkless veya check.messages
                if ($isMessage || $isMars) {
                    // MESAJ satırlarının gerçek metni POS_JOURNAL_LOG fiş metninden çözülür;
                    // MARS metni fişte ayrı biçimde olduğundan adıyla kalır.
                    if ($isMessage && $checkGid !== '') {
                        $item['_gid'] = $checkGid;
                        $item['_k']   = $isBarMsg ? 'bar' : 'mutfak';
                        $journalGuids[$checkGid] = true;
                    }
                    if (!$hasCheck) {
                        $checkless[] = array_merge($item, ['table_no' => $tableNo, 'rvc' => $rvc, 'rvc_id' => $rvcId]);
                        continue;
                    }
                    $key = (string) $checkNum;
                    if (!isset($checks[$key])) {
                        $checks[$key] = $this->newCheck($checkNum, $tableNo, $rvc, $rvcId, $waiterFull, $status);
                    }
                    // POS fişindeki satır konumu: blade mesajları ürün satırları arasına buraya serpiştirir
                    $item['pos'] = count($checks[$key]['items']);
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
                $subReturned = $isReturned || ($isV14Row && $rowQty < 0);

                if (($isCondiment || $isComboItem) && $parentItemId > 0
                    && ($pIdx = $prodRowIdxByKey[$key][$parentItemId] ?? null) !== null
                    && isset($items[$pIdx])
                ) {
                    // v1.4: ParentItemID ile kesin üst ürün → condiment/combo alt satırı.
                    // İade alt satırı unit listesine girmez (hazırlanan karşılaştırmasını bozmaz).
                    if (!isset($items[$pIdx]['sub_items'])) $items[$pIdx]['sub_items'] = [];
                    $items[$pIdx]['sub_items'][] = ['unit_ids' => [$unitId], 'item_id' => $itemId, 'name' => $name, 'note' => $note, 'is_returned' => $subReturned, 'item_time' => $itemTimeIso];
                    if (!$subReturned) {
                        $items[$pIdx]['unit_ids'][]  = $unitId;
                        $items[$pIdx]['unit_qtys'][$unitId] = max(1, $rowQty);
                    }
                } elseif (!$isOpenRow && $objNo > 0 && ($mIdx = $prodMergeIdxByKey[$key][$objNo] ?? null) !== null && isset($items[$mIdx])) {
                    // v1.4: aynı checkte aynı ürün (ProductObjectNumber) → tek satırda birleştir.
                    // Net adet sıfırın altına inerse satır komple iade sayılır.
                    $items[$mIdx]['qty_net']   += $rowQty;
                    $items[$mIdx]['row_count'] += 1;
                    if ($rowQty < 0) {
                        $items[$mIdx]['qty_returned'] += abs($rowQty);
                    } else {
                        $items[$mIdx]['unit_ids'][]  = $unitId;
                        $items[$mIdx]['unit_qtys'][$unitId] = $rowQty;
                    }
                    if ($itemTimeIso < $items[$mIdx]['item_time']) {
                        $items[$mIdx]['item_time'] = $itemTimeIso;
                    }
                    if ($itemId !== null && (string) $itemId !== '') {
                        $prodRowIdxByKey[$key][(string) $itemId] = $mIdx;
                    }
                } elseif ($isCombo) {
                    $pIdx = $comboParentIdxByKey[$key] ?? null;
                    if ($pIdx !== null && isset($items[$pIdx]) && $items[$pIdx]['name'] !== $name) {
                        if (!isset($items[$pIdx]['sub_items'])) $items[$pIdx]['sub_items'] = [];
                        $items[$pIdx]['sub_items'][] = ['unit_ids' => [$unitId], 'item_id' => $itemId, 'name' => $name, 'note' => $note, 'is_returned' => $subReturned, 'item_time' => $itemTimeIso];
                        if (!$subReturned) {
                            $items[$pIdx]['unit_ids'][]  = $unitId;
                            $items[$pIdx]['unit_qtys'][$unitId] = max(1, $rowQty);
                        }
                    } else {
                        $item['sub_items'] = [];
                        $items[] = $item;
                        $comboParentIdxByKey[$key] = count($items) - 1;
                    }
                } elseif ($isCondiment) {
                    $pIdx = $lastUrunIdxByKey[$key] ?? null;
                    if ($pIdx !== null && isset($items[$pIdx])) {
                        if (!isset($items[$pIdx]['sub_items'])) $items[$pIdx]['sub_items'] = [];
                        $items[$pIdx]['sub_items'][] = ['unit_ids' => [$unitId], 'item_id' => $itemId, 'name' => $name, 'note' => $note, 'is_returned' => $subReturned, 'item_time' => $itemTimeIso];
                        if (!$subReturned) {
                            $items[$pIdx]['unit_ids'][]  = $unitId;
                            $items[$pIdx]['unit_qtys'][$unitId] = max(1, $rowQty);
                        }
                    } else {
                        $item['sub_items'] = [];
                        $items[] = $item;
                    }
                } elseif (!$isReturned && $lineKind === 'URUN'
                    && $objNo <= 0
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
                    $newIdx = count($items) - 1;
                    $lastUrunIdxByKey[$key] = $newIdx;
                    unset($comboParentIdxByKey[$key]);
                    if ($objNo > 0) $prodMergeIdxByKey[$key][$objNo] = $newIdx;
                    if ($itemId !== null && (string) $itemId !== '') {
                        $prodRowIdxByKey[$key][(string) $itemId] = $newIdx;
                    }
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

            // Birleştirilmiş v1.4 satırları: net adet ve iade durumu nihai değerlere düşer.
            // row_count>1 → iade bayrağı net adede göre; tek satır → satırın kendi bayrağı.
            // Kısmi iade (net>0) net adedi gösterir, iade işareti almaz.
            foreach ($checks as &$chk) {
                foreach ($chk['items'] as &$it) {
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
            }
            unset($chk);

            // MESAJ satırlarının gerçek metni POS_JOURNAL_LOG fiş metninden çözülür:
            // her check için fişteki mesaj metinleri, sonuç sırasındaki mesaj satırlarıyla
            // eşleşir; eşleşmeyen satır adıyla kalır.
            $journalTexts = $journalGuids !== [] ? self::resolveJournalTexts($pdo, array_keys($journalGuids)) : [];
            $cursor = [];
            $nextText = function (array &$item) use (&$cursor, $journalTexts) {
                $gid  = (string) ($item['_gid'] ?? '');
                $kind = (string) ($item['_k'] ?? 'mutfak');
                unset($item['_gid'], $item['_k']);
                if ($gid === '' || !isset($journalTexts[$gid]['messages'][$kind])) return;
                $i = $cursor[$gid . ':' . $kind] ?? 0;
                if (isset($journalTexts[$gid]['messages'][$kind][$i])) {
                    $item['note'] = $journalTexts[$gid]['messages'][$kind][$i];
                }
                $cursor[$gid . ':' . $kind] = $i + 1;
            };
            foreach ($checks as &$chk) {
                foreach ($chk['messages'] as &$m) $nextText($m);
                unset($m);
            }
            unset($chk);
            foreach ($checkless as &$m) $nextText($m);
            unset($m);

            // Açık satırlar: fişteki içerik satırları, sonuç sırasındaki açık satırlara
            // fiş sırasıyla atanır; çözülemeyen satır adıyla kalır.
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
            foreach ($checks as &$chk) {
                foreach ($chk['items'] as &$it) {
                    if (!empty($it['_open'])) $nextOpen($it);
                }
                unset($it);
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
                                // Birleştirilmiş satırda kalan adet: unit başına gerçek adet toplamı
                                $newQty = 0;
                                foreach ($newUnitIds as $uid) {
                                    $newQty += (int) ($item['unit_qtys'][$uid] ?? 1);
                                }
                                $item['qty'] = max(1, $newQty);
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
                        foreach ($units as $uid) {
                            if (isset($servedSet[(string) $uid])) {
                                $servedQty += (int) ($item['unit_qtys'][$uid] ?? 1);
                            }
                        }
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

            // unit_qtys yalnız sunucu içi adet hesabı içindir, payload'a çıkmaz
            foreach ($checks as &$chk) {
                foreach ($chk['items'] as &$it) unset($it['unit_qtys']);
                unset($it);
            }
            unset($chk);

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

    // "44+81" / "44,81" / "44 81" → [44=>true, 81=>true]; boş → [] (filtre yok)
    public static function parseRvcFilter(string $raw): array
    {
        $ids = [];
        foreach (preg_split('/[^0-9]+/', trim($raw)) ?: [] as $part) {
            if ($part !== '') $ids[(int) $part] = true;
        }
        return $ids;
    }

    // Check GUID listesi → POS_JOURNAL_LOG fiş metinlerinden mesaj metinleri +
    // açık satır içerikleri. Bir check'in fişleri zamanla değişir (M.Servis kısmi
    // fişleri bazı satırları hiç taşımaz) → yalnız son fişe bakmak yerine en çok
    // bölüm çözen fiş seçilir.
    public static function resolveJournalTexts(\PDO $pdo, array $guids): array
    {
        $guids = array_values(array_unique(array_filter(array_map('trim', $guids), fn($g) => $g !== '')));
        if ($guids === []) return [];

        $textsPerGuid = [];
        foreach (array_chunk($guids, 50) as $chunk) {
            $quoted = implode(',', array_map(fn($g) => "'" . str_replace("'", "''", (string) $g) . "'", $chunk));
            $sql = 'SELECT posJournalLogId, guid, journalText FROM CheckPostingDB.dbo.POS_JOURNAL_LOG '
                 . "WHERE type = 1 AND guid IN ({$quoted}) ORDER BY posJournalLogId ASC";
            try {
                foreach ($pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                    $g = (string) ($r['guid'] ?? $r['Guid'] ?? '');
                    if ($g !== '') $textsPerGuid[$g][] = (string) ($r['journalText'] ?? $r['JournalText'] ?? '');
                }
            } catch (\Throwable) {
                return [];
            }
        }

        $out = [];
        // Check uzun yaşarsa açık ürün ve mesajlar ayrı fişlerde (ayrı postinglerde)
        // gelir; tek "en iyi" fiş seçimi diğerini boş bırakır — tüm fişler
        // posJournalLogId sırasıyla birleştirilir. Bar/Mutfak mesaj metinleri ayrı
        // tutulur: ekranlar yalnız kendi türündeki satırları sırayla tüketir.
        foreach ($textsPerGuid as $guid => $list) {
            $messages = ['bar' => [], 'mutfak' => []];
            $opens    = [];
            foreach ($list as $text) {
                foreach (self::parseJournalMessageTexts($text) as $m) $messages[$m['k']][] = $m['t'];
                foreach (self::parseJournalOpenTexts($text) as $o) $opens[] = $o;
            }
            if ($messages['bar'] !== [] || $messages['mutfak'] !== [] || $opens !== []) {
                $out[$guid] = ['messages' => $messages, 'opens' => $opens];
            }
        }
        return $out;
    }

    // Fiş metni: " 1 Mutfak Mesaj  0.00" (1 boşluk girinti) başlık satırı,
    // hemen altındaki "   HERSEY MARS" (3 boşluk girinti) satırı gerçek mesaj metnidir.
    // Cafe Rocks (RVC 43) biçimi: mesaj, açık içerik bloğu içinde "     Mesaj"
    // (5 boşluk girinti) etiketiyle gelir; altındaki 3-girintili satır metindir.
    // Dönen kayıt tür etiketlidir: ['k' => 'bar'|'mutfak', 't' => metin].
    private static function parseJournalMessageTexts(string $journalText): array
    {
        $texts = [];
        $expectKind = null;
        foreach (explode("\n", str_replace("\r\n", "\n", $journalText)) as $line) {
            $indent = strlen($line) - strlen(ltrim($line));
            if ($indent >= 4 && preg_match('/^\s{4,}mesaj\b/iu', $line)) {
                $expectKind = 'mutfak';
                continue;
            }
            if ($indent === 1) {
                $expectKind = preg_match('/^ \d+ +bar mesaj\b/iu', $line)
                    ? 'bar'
                    : (preg_match('/^ \d+ +(?:mutfak mesaj|mesaj)\b/iu', $line) ? 'mutfak' : null);
            } elseif ($expectKind !== null && $indent === 3) {
                $texts[] = ['k' => $expectKind, 't' => trim($line)];
                $expectKind = null;
            } elseif ($indent !== 3) {
                $expectKind = null;
            }
        }
        return $texts;
    }

    // Fiş metni: " 1 Acik Yiyecek  1200.00" (1 boşluk girinti) başlık satırı,
    // altındaki "   ICE GIT" (3 boşluk girinti) satırlar içeriktir; bir sonraki
    // 1-girintili bölüm başlığı açık bölümü kapatır. Fiş sonu özet satırları
    // (AraToplam/Toplam/Ödeme/KDV) içerik sayılmaz, bölümü kapatır.
    private static function parseJournalOpenTexts(string $journalText): array
    {
        $opens  = [];
        $cur    = null;
        $isTail = fn(string $s) => (bool) preg_match('/^(ara ?toplam|genel ?toplam|toplam|odeme|oda hesabi|m\.servis|service|kdv|vergi|vat|tax|nakit|bakiye)\b/iu', $s);
        foreach (explode("\n", str_replace("\r\n", "\n", $journalText)) as $line) {
            $indent = strlen($line) - strlen(ltrim($line));
            if (preg_match('/^ \d+ +acik\b/iu', $line)) {
                if ($cur !== null) $opens[] = $cur;
                $cur = ['name' => trim($line), 'contents' => []];
                continue;
            }
            if ($cur === null) continue;
            $content = trim($line);
            if ($content !== '' && $isTail($content)) {
                $opens[] = $cur;
                $cur = null;
                continue;
            }
            // Cafe Rocks biçimi: "     Mesaj" etiketi açık bölümün içeriğini kapatır;
            // altındaki 3-girintili satırlar mesaj metnidir, açık içeriğe yazılmaz.
            if ($indent >= 4 && preg_match('/^\s{4,}mesaj\b/iu', $line)) {
                $opens[] = $cur;
                $cur = null;
                continue;
            }
            if ($indent === 3) {
                if ($content !== '') $cur['contents'][] = $content;
            } elseif ($indent === 1) {
                $opens[] = $cur;
                $cur = null;
            }
        }
        if ($cur !== null) $opens[] = $cur;
        return $opens;
    }

    // ──────────────────────────────────────────────
    // AKDS: Ana Mutfak canlı sipariş API
    // ──────────────────────────────────────────────

    public function kitchenAnaApi()
    {
        return $this->kdsPayload('mssql_akds_rvc_filter');
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
