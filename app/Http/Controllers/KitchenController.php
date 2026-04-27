<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\WaiterCall;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KitchenController extends Controller
{
    public function bar()
    {
        $orders = Order::where('bar_status', 'new')
            ->orderBy('created_at', 'desc')
            ->get();

        $waiterCalls = WaiterCall::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.bar', compact('orders', 'waiterCalls'));
    }

    public function kitchen()
    {
        $orders = Order::whereIn('kitchen_status', ['new', 'preparing', 'ready'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.kitchen', compact('orders'));
    }

    public function barUpdateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:preparing',
        ]);

        $order->update([
            'bar_status' => 'approved',
            'bar_approved_at' => $order->bar_approved_at ?? now(),
            'kitchen_status' => $order->kitchen_status === 'waiting' ? 'new' : $order->kitchen_status,
            'status' => $order->kitchen_status === 'waiting' ? 'new' : $order->status,
            'completed_at' => null,
        ]);

        return response()->json(['success' => true, 'status' => $order->kitchen_status]);
    }

    public function kitchenUpdateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:preparing,ready,completed',
        ]);

        $undoWindowSeconds = (int) Setting::get('ready_undo_seconds', 30);
        $isUndoToPreparing = $validated['status'] === 'preparing' && $order->kitchen_status === 'ready';

        if ($isUndoToPreparing) {
            if ($order->kitchen_ready_at === null || $order->kitchen_ready_at->diffInSeconds(now()) > $undoWindowSeconds) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geri alma suresi doldu.',
                ], 422);
            }
        }

        $payload = [
            'kitchen_status' => $validated['status'],
            'status' => $validated['status'],
            'bar_status' => 'approved',
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
        ];

        if ($validated['status'] === 'preparing' && $order->kitchen_started_at === null) {
            $payload['kitchen_started_at'] = now();
        }

        if ($validated['status'] === 'ready' && $order->kitchen_ready_at === null) {
            $payload['kitchen_ready_at'] = now();
        }

        if ($isUndoToPreparing) {
            $payload['kitchen_ready_at'] = null;
            $payload['completed_at'] = null;
        }

        $order->update($payload);

        return response()->json(['success' => true, 'status' => $order->kitchen_status]);
    }

    public function attendWaiterCall(WaiterCall $waiterCall)
    {
        $waiterCall->markAsAttended();
        return response()->json(['success' => true]);
    }

    public function barApiOrders()
    {
        $completedLimit = (int) Setting::get('bar_completed_display', 6);

        // Sadece henuz onaylanmamis QR siparisleri (onaylandiktan sonra Symphony POS karti devralir)
        $orders = Order::where('bar_status', 'new')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($order) => $this->mapOrder($order));

        $readyOrders = Order::where('kitchen_status', 'ready')
            ->orderBy('kitchen_ready_at', 'desc')
            ->limit($completedLimit)
            ->get()
            ->map(fn ($order) => $this->mapOrder($order))
            ->values()
            ->all();

        // Symphony POS / KDS mesaj onayları (delivered_at IS NULL) bar ekranında da görünsün
        $undoWindowSeconds = (int) Setting::get('ready_undo_seconds', 30);
        $symphonyReady = DB::table('kitchen_pos_completions')
            ->whereNull('delivered_at')
            ->orderByDesc('completed_at')
            ->limit($completedLimit)
            ->get();

        foreach ($symphonyReady as $row) {
            $completedAt = \Carbon\Carbon::parse($row->completed_at);
            $readySinceSeconds = (int) $completedAt->diffInSeconds(now());
            $undoRemaining = max(0, $undoWindowSeconds - $readySinceSeconds);
            $itemName = trim((string) ($row->name ?? ''));
            $qty = (int) ($row->qty ?? 1);

            if ($row->kind === 'check') {
                $itemsArr = [['id' => null, 'name' => 'Hesap onaylandı (Adisyon #' . ($row->check_number ?: '-') . ')', 'quantity' => 1]];
            } else {
                $itemsArr = [['id' => null, 'name' => $itemName !== '' ? $itemName : 'Mutfak mesajı', 'quantity' => max(1, $qty)]];
            }

            $readyOrders[] = [
                'id' => 0,
                'source' => 'symphony',
                'group_key' => $row->group_key,
                'kind' => $row->kind,
                'table_no' => $row->table_no,
                'items' => $itemsArr,
                'total_price' => 0,
                'order_note' => $itemName !== '' && $row->kind !== 'check' ? trim((string) ($row->note ?? '')) : null,
                'status' => 'ready',
                'bar_status' => 'approved',
                'kitchen_status' => 'ready',
                'created_at' => $completedAt->format('H:i:s'),
                'seconds_ago' => $readySinceSeconds,
                'preparing_seconds' => null,
                'ready_seconds' => $readySinceSeconds,
                'ready_since_seconds' => $readySinceSeconds,
                'can_undo_ready' => $undoRemaining > 0,
                'undo_remaining_seconds' => $undoRemaining,
            ];
        }

        // En yeni üstte
        usort($readyOrders, function ($a, $b) {
            return ($a['ready_since_seconds'] ?? 99999) <=> ($b['ready_since_seconds'] ?? 99999);
        });
        $readyOrders = array_slice($readyOrders, 0, $completedLimit);

        $completedOrders = Order::where('kitchen_status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit($completedLimit)
            ->get()
            ->map(fn ($order) => $this->mapOrder($order))
            ->values()
            ->all();

        // Symphony servis edilenleri de tamamlananlara ekle
        $symphonyDelivered = DB::table('kitchen_pos_completions')
            ->whereNotNull('delivered_at')
            ->orderByDesc('delivered_at')
            ->limit($completedLimit)
            ->get();

        foreach ($symphonyDelivered as $row) {
            $deliveredAt = \Carbon\Carbon::parse($row->delivered_at);
            $itemName = trim((string) ($row->name ?? ''));
            $qty = (int) ($row->qty ?? 1);

            if ($row->kind === 'check') {
                $itemsArr = [['id' => null, 'name' => 'Hesap onaylandı (Adisyon #' . ($row->check_number ?: '-') . ')', 'quantity' => 1]];
            } else {
                $itemsArr = [['id' => null, 'name' => $itemName !== '' ? $itemName : 'Mutfak mesajı', 'quantity' => max(1, $qty)]];
            }

            $completedOrders[] = [
                'id' => 0,
                'source' => 'symphony',
                'group_key' => $row->group_key,
                'table_no' => $row->table_no,
                'items' => $itemsArr,
                'total_price' => 0,
                'order_note' => null,
                'status' => 'completed',
                'bar_status' => 'approved',
                'kitchen_status' => 'completed',
                'created_at' => $deliveredAt->format('H:i:s'),
                'completed_at_ts' => $deliveredAt->getTimestamp(),
                'seconds_ago' => (int) $deliveredAt->diffInSeconds(now()),
            ];
        }

        // En yeni servis üstte
        usort($completedOrders, function ($a, $b) {
            $av = $a['completed_at_ts'] ?? 0;
            $bv = $b['completed_at_ts'] ?? 0;
            // Order modeli mapOrder'da completed_at_ts yok; fallback created_at karsilastir
            if (!$av && isset($a['completed_at'])) $av = strtotime($a['completed_at']);
            if (!$bv && isset($b['completed_at'])) $bv = strtotime($b['completed_at']);
            return $bv <=> $av;
        });
        $completedOrders = array_slice($completedOrders, 0, $completedLimit);
        $completedOrders = collect($completedOrders);

        $waiterCalls = WaiterCall::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'table_no' => $call->table_no,
                    'note' => $call->note,
                    'created_at' => $call->created_at->format('H:i:s'),
                    'seconds_ago' => (int) $call->created_at->diffInSeconds(now()),
                ];
            });

        // Son 10 dakika içinde tamamlanan garson çağrıları
        $attendedCalls = WaiterCall::where('status', 'attended')
            ->where('attended_at', '>=', now()->subMinutes(10))
            ->orderBy('attended_at', 'desc')
            ->limit(8)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'table_no' => $call->table_no,
                    'note' => $call->note,
                    'attended_at' => $call->attended_at?->format('H:i:s') ?? '',
                    'seconds_ago' => (int) $call->attended_at?->diffInSeconds(now()) ?? 0,
                ];
            });

        return response()->json([
            'orders' => $orders->values(),
            'ready_orders' => array_values($readyOrders),
            'ready_orders_limit' => $completedLimit,
            'completed_orders' => $completedOrders->values(),
            'completed_orders_limit' => $completedLimit,
            'waiter_calls' => $waiterCalls,
            'attended_calls' => $attendedCalls,
        ]);
    }

    /**
     * Bar ekranında Symphony/KDS mesaj kaydını "servis edildi" olarak işaretle.
     */
    public function barSymphonyDelivered(Request $request)
    {
        $validated = $request->validate(['group_key' => 'required|string|max:64']);
        DB::table('kitchen_pos_completions')
            ->where('group_key', $validated['group_key'])
            ->update(['delivered_at' => now()]);
        return response()->json(['success' => true]);
    }
    /**
     * BDS: Symphony'den canlı bar siparişlerini çeker (ayrı SQL sorgusu).
     * Beklenen kolon adları (case-insensitive, fallback'li):
     *   TableNo / TableNumber / MASA
     *   ItemName / Name / ProductName
     *   Qty / Quantity / ADET
     *   OrderTime / ItemTime / Time
     *   CheckNumber / CheckNum / ADISYON
     *   Note / RefInfo / MessageNote (opsiyonel)
     */
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
                'message' => 'BDS MSSQL ayarları/sorgusu eksik. Önce Admin → MSSQL Ayarları → BDS sekmesinden tanımlayın.',
                'orders'  => [],
            ], 200);
        }

        try {
            $actualPassword = '';
            if ($password) {
                try { $actualPassword = decrypt($password); } catch (\Exception $e) { $actualPassword = $password; }
            }

            $pdo = new \PDO("sqlsrv:Server={$host},{$port};Database={$database};TrustServerCertificate=1", $username, $actualPassword);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // -- yorum sat., \r\n ve trailing ; ODBC Driver 18'de SQLSTATE[42000] verebilir
            $lines = explode("\n", str_replace("\r\n", "\n", $query));
            $query = rtrim(trim(implode("\n", array_filter($lines, fn($l) => !preg_match('/^\s*--/', $l)))), ';');

            $stmt = $pdo->query($query);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $get = function (array $row, array $candidates, $default = null) {
                foreach ($candidates as $c) {
                    foreach ([$c, strtoupper($c), strtolower($c), ucfirst(strtolower($c))] as $k) {
                        if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                            return $row[$k];
                        }
                    }
                }
                return $default;
            };

            $groups = [];
            foreach ($rows as $row) {
                $tableNo   = (string) $get($row, ['TableNo', 'TableNumber', 'MASA', 'table_no'], '');
                $checkNum  = $get($row, ['CheckNumber', 'CheckNum', 'ADISYON', 'check_number'], null);
                $itemName  = (string) $get($row, ['ItemName', 'ProductName', 'Name', 'item_name'], '');
                $qty       = (int) $get($row, ['Qty', 'Quantity', 'ADET', 'qty'], 1);
                $orderTime = $get($row, ['OrderTime', 'ItemTime', 'Time', 'order_time'], null);
                $note      = (string) $get($row, ['Note', 'RefInfo', 'MessageNote', 'note'], '');

                $key = $checkNum !== null && $checkNum !== '' ? 'C' . $checkNum : 'T' . $tableNo;
                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'group_key'    => $key,
                        'table_no'     => $tableNo,
                        'check_number' => $checkNum,
                        'order_time'   => $orderTime,
                        'items'        => [],
                    ];
                }
                if ($orderTime && (!$groups[$key]['order_time'] || strcmp((string) $orderTime, (string) $groups[$key]['order_time']) < 0)) {
                    $groups[$key]['order_time'] = $orderTime;
                }
                $groups[$key]['items'][] = [
                    'name' => $itemName,
                    'qty'  => max(1, $qty),
                    'note' => $note,
                ];
            }

            // En eski sipariş üstte (en uzun bekleyen önce)
            uasort($groups, fn ($a, $b) => strcmp((string) $a['order_time'], (string) $b['order_time']));

            $out = [];
            foreach ($groups as $g) {
                $secondsAgo = 0;
                if ($g['order_time']) {
                    try {
                        // SQL Server local (Turkey UTC+3) time döndürür;
                        // Carbon'un uygulama timezone'undan (Berlin UTC+2) bağımsız doğru hesaplayalım.
                        $secondsAgo = max(0, (int) \Carbon\Carbon::parse((string) $g['order_time'], 'Europe/Istanbul')
                            ->diffInSeconds(\Carbon\Carbon::now('Europe/Istanbul')));
                    } catch (\Exception $e) { $secondsAgo = 0; }
                }
                $out[] = [
                    'source'       => 'symphony',
                    'group_key'    => $g['group_key'],
                    'table_no'     => $g['table_no'],
                    'check_number' => $g['check_number'],
                    // order_time UTC ISO8601 olarak gönderilir; JS timezone bağımsız parse eder
                    'order_time'   => $g['order_time']
                        ? \Carbon\Carbon::parse((string) $g['order_time'], 'Europe/Istanbul')->toIso8601String()
                        : null,
                    'seconds_ago'  => $secondsAgo,
                    'items'        => array_values($g['items']),
                ];
            }

            return response()->json([
                'success' => true,
                'orders'  => $out,
                'count'   => count($out),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'BDS sorgu hatası: ' . $e->getMessage(),
                'orders'  => [],
            ], 200);
        }
    }
    public function kitchenApiOrders()
    {
        $completedLimit = (int) Setting::get('kitchen_completed_display', 6);

        $activeOrders = Order::whereIn('kitchen_status', ['new', 'preparing'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($order) => $this->mapOrder($order));

        $completedOrders = Order::where('kitchen_status', 'ready')
            ->orderBy('kitchen_ready_at', 'desc')
            ->limit($completedLimit)
            ->get()
            ->map(fn ($order) => $this->mapOrder($order));

        return response()->json([
            'orders' => $activeOrders->values(),
            'completed' => $completedOrders->values(),
            'completed_limit' => $completedLimit,
        ]);
    }

    public function kitchenSse()
    {
        return response()->stream(function () {
            $completedLimit = (int) Setting::get('kitchen_completed_display', 6);
            $timeout = time() + 25;

            while (time() < $timeout) {
                $activeOrders = Order::whereIn('kitchen_status', ['new', 'preparing'])
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(fn ($order) => $this->mapOrder($order));

                $completedOrders = Order::where('kitchen_status', 'ready')
                    ->orderBy('kitchen_ready_at', 'desc')
                    ->limit($completedLimit)
                    ->get()
                    ->map(fn ($order) => $this->mapOrder($order));

                $payload = json_encode([
                    'orders' => $activeOrders->values(),
                    'completed' => $completedOrders->values(),
                    'completed_limit' => $completedLimit,
                ]);

                echo "data: {$payload}\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();

                if (connection_aborted()) {
                    break;
                }

                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    private function mapOrder(Order $order): array
    {
        $preparingSeconds = null;
        $readySeconds = null;
        $readySinceSeconds = null;
        $canUndoReady = false;
        $undoRemainingSeconds = 0;
        $undoWindowSeconds = (int) Setting::get('ready_undo_seconds', 30);

        if ($order->kitchen_started_at !== null && $order->kitchen_status === 'preparing') {
            $preparingSeconds = (int) $order->kitchen_started_at->diffInSeconds(now());
        }

        if ($order->kitchen_started_at !== null && $order->kitchen_ready_at !== null) {
            $readySeconds = (int) $order->kitchen_started_at->diffInSeconds($order->kitchen_ready_at);
        }

        if ($order->kitchen_status === 'ready' && $order->kitchen_ready_at !== null) {
            $readySinceSeconds = (int) $order->kitchen_ready_at->diffInSeconds(now());
            $undoRemainingSeconds = max(0, $undoWindowSeconds - $readySinceSeconds);
            $canUndoReady = $undoRemainingSeconds > 0;
        }

        return [
            'id' => $order->id,
            'table_no' => $order->table_no,
            'items' => $order->items,
            'total_price' => $order->total_price,
            'order_note' => $order->order_note,
            'status' => $order->status,
            'bar_status' => $order->bar_status,
            'kitchen_status' => $order->kitchen_status,
            'created_at' => $order->created_at->format('H:i:s'),
            'seconds_ago' => (int) $order->created_at->diffInSeconds(now()),
            'preparing_seconds' => $preparingSeconds,
            'ready_seconds' => $readySeconds,
            'ready_since_seconds' => $readySinceSeconds,
            'can_undo_ready' => $canUndoReady,
            'undo_remaining_seconds' => $undoRemainingSeconds,
        ];
    }

    /**
     * Symphony POS tabanlı KDS ekranı (read-only).
     */
    public function kitchenPos()
    {
        return view('admin.kitchen-pos');
    }

    /**
     * Tan\u0131 endpoint'i: KDS SQL'inin ham satırlarını filtre uygulamadan döndürür.
     * Kullanım: /kitchen-pos/raw?check=3626  veya  /kitchen-pos/raw?table=12
     */
    public function kitchenPosRaw(Request $request)
    {
        $host     = (string) Setting::get('mssql_kds_host', '');
        $port     = (string) Setting::get('mssql_kds_port', '1433');
        $database = (string) Setting::get('mssql_kds_database', '');
        $username = (string) Setting::get('mssql_kds_username', '');
        $password = (string) Setting::get('mssql_kds_password', '');
        $query    = trim((string) Setting::get('mssql_kds_query', ''));

        if ($query === '' || !$host || !$database || !$username) {
            return response()->json(['success' => false, 'message' => 'KDS ayarları eksik.'], 200);
        }

        try {
            $actualPassword = '';
            if ($password) {
                try { $actualPassword = decrypt($password); } catch (\Exception $e) { $actualPassword = $password; }
            }
            $pdo = new \PDO("sqlsrv:Server={$host},{$port};Database={$database};TrustServerCertificate=1", $username, $actualPassword);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // -- yorum sat., \r\n ve trailing ; ODBC Driver 18'de SQLSTATE[42000] verebilir
            $lines = explode("\n", str_replace("\r\n", "\n", $query));
            $query = rtrim(trim(implode("\n", array_filter($lines, fn($l) => !preg_match('/^\s*--/', $l)))), ';');

            $stmt = $pdo->query($query);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

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

            return response()->json([
                'success' => true,
                'count'   => count($rows),
                'rows'    => $rows,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 200);
        }
    }

    /**
     * Symphony POS canlı verisi: kayıtlı KDS sorgusunu çalıştırır,
     * CheckNumber'a göre gruplar; MajGrp=99 (Mutfak Mesajları) hem grup içinde
     * hem de checksiz olarak ayrı bir listede tutulur.
     */
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
                'message'  => 'KDS MSSQL ayarları/sorgusu eksik. Önce Admin → MSSQL Ayarları → KDS sekmesinden tanımlayın.',
                'orders'   => [],
                'messages' => [],
            ], 200);
        }

        try {
            $actualPassword = '';
            if ($password) {
                try { $actualPassword = decrypt($password); } catch (\Exception $e) { $actualPassword = $password; }
            }

            $pdo = new \PDO("sqlsrv:Server={$host},{$port};Database={$database};TrustServerCertificate=1", $username, $actualPassword);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // -- yorum sat., \r\n ve trailing ; ODBC Driver 18'de SQLSTATE[42000] verebilir
            $lines = explode("\n", str_replace("\r\n", "\n", $query));
            $query = rtrim(trim(implode("\n", array_filter($lines, fn($l) => !preg_match('/^\s*--/', $l)))), ';');

            $stmt = $pdo->query($query);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // ── Adım 1: Her UnitID için local "first_seen_at" zaman damgasını hazırla ──
            // Symphony, ürün eklendiğinde tüm satırların ItemTime'ını günceller.
            // Bu nedenle MSSQL'in ItemTime'ına güvenemeyiz; ilk gördüğümüz anı local DB'ye kaydederiz.
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
                ? DB::table('kitchen_item_times')
                    ->whereIn('unit_id', $allUnitIds)
                    ->pluck('first_seen_at', 'unit_id')
                : collect();

            $nowTs = now()->format('Y-m-d H:i:s');
            $insertBatch = [];
            foreach ($allUnitIds as $uid) {
                if (!$existingLocalTimes->has($uid)) {
                    $insertBatch[] = [
                        'unit_id'      => $uid,
                        'check_number' => $unitIdCheckMap[$uid] ?? null,
                        'first_seen_at' => $nowTs,
                    ];
                }
            }
            if (!empty($insertBatch)) {
                DB::table('kitchen_item_times')->insertOrIgnore($insertBatch);
                $existingLocalTimes = DB::table('kitchen_item_times')
                    ->whereIn('unit_id', $allUnitIds)
                    ->pluck('first_seen_at', 'unit_id');
            }

            $checks              = [];
            $checkless           = [];
            $comboParentIdxByKey = []; // combo zinciri için açık parent indeksi (key → idx)
            $lastUrunIdxByKey    = []; // condiment'ın bağlanacağı son URUN indeksi (key → idx)

            foreach ($rows as $row) {
                $get = function (array $candidates, $default = null) use ($row) {
                    foreach ($candidates as $c) {
                        foreach ([$c, strtoupper($c), strtolower($c)] as $k) {
                            if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                                return $row[$k];
                            }
                        }
                    }
                    return $default;
                };

                $checkNum    = $get(['CheckNumber', 'check_number', 'ChkNum'], null);
                $unitId      = (string) $get(['UnitID', 'unit_id'], '');
                $itemId      = $get(['ItemID', 'item_id'], null);
                $tableNo     = (string) $get(['TableNumber', 'table_number'], '');
                $rvc         = $get(['RevenueCenter', 'revenue_center'], '');
                $rvcId       = (int) $get(['RevenueCenterID', 'revenue_center_id'], 0);
                $status      = (string) $get(['Status', 'status'], '');
                $name        = (string) $get(['ProductName', 'product_name', 'Name'], '');
                $note        = (string) $get(['MessageNote', 'message_note', 'RefInfo'], '');
                $isCondiment = (bool)(int) $get(['IsCondiment', 'is_condiment'], 0);
                $isComboItem = (bool)(int) $get(['IsComboItem', 'is_combo_item'], 0);
                $isReturned  = (bool)(int) $get(['IsReturned', 'is_returned'], 0);
                $lineKind    = strtoupper((string) $get(['LineKind', 'line_kind'], 'URUN'));
                // Eski sorgu uyumluluğu: LineKind yoksa MajGrp=99 → MESAJ
                if ($lineKind === 'URUN') {
                    $majGrp = (int) $get(['MajGrp', 'maj_grp'], 0);
                    if ($majGrp === 99) $lineKind = 'MESAJ';
                }
                $isMessage = ($lineKind === 'MESAJ');
                $isMars    = ($lineKind === 'MARS');
                $isCombo   = ($lineKind === 'COMBO') || $isComboItem;
                $hasCheck  = $checkNum !== null && (int) $checkNum > 0;

                // UnitID yoksa (eski sorgu) → ItemID + hash ile üret
                if ($unitId === '') {
                    $dtlSeq = (int) $get(['DtlSeq', 'dtl_seq'], 0);
                    $unitId = ($itemId ? $itemId : 'u') . '-' . ($dtlSeq ?: substr(md5($name . $note), 0, 8));
                }

                // Mesajlar için ItemID yoksa hash üret
                if (($isMessage || $isMars) && (!$itemId || (string) $itemId === '0')) {
                    $itemId = ($isMars ? 'mars-' : 'm-') . substr(md5(($tableNo ?? '') . '|' . ($checkNum ?? '') . '|' . $unitId . '|' . $name . '|' . ($note ?? '')), 0, 16);
                }

                // item_time: local first_seen_at (MSSQL ItemTime yerine)
                $localTime = $existingLocalTimes->get($unitId, $nowTs);
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

                // Mesaj ve Mars: messages dizisine gönder
                if ($isMessage || $isMars) {
                    if (!$hasCheck) {
                        $checkless[] = array_merge($item, [
                            'table_no' => $tableNo,
                            'rvc'      => $rvc,
                            'rvc_id'   => $rvcId,
                        ]);
                        continue;
                    }
                    $key = (string) $checkNum;
                    if (!isset($checks[$key])) {
                        $checks[$key] = [
                            'check_number' => $checkNum,
                            'table_no'     => $tableNo,
                            'rvc'          => $rvc,
                            'rvc_id'       => $rvcId,
                            'order_time'   => null,
                            'status'       => $status,
                            'items'        => [],
                            'messages'     => [],
                        ];
                    }
                    $checks[$key]['messages'][] = $item;
                    continue;
                }

                // Ürün (URUN, COMBO, condiment) → items dizisine ekle
                $key = $hasCheck ? (string) $checkNum : ('T' . $tableNo);
                if (!isset($checks[$key])) {
                    $checks[$key] = [
                        'check_number' => $checkNum,
                        'table_no'     => $tableNo,
                        'rvc'          => $rvc,
                        'rvc_id'       => $rvcId,
                        'order_time'   => null,
                        'status'       => $status,
                        'items'        => [],
                        'messages'     => [],
                    ];
                }

                $items = &$checks[$key]['items'];
                $lastIdx = count($items) - 1;

                if ($isCombo) {
                    // COMBO: LineKind=COMBO — kendi parent zincirini kurar
                    // İlk COMBO satırı parent, sonraki farklı-adlı COMBOlar sub_item
                    $pIdx = $comboParentIdxByKey[$key] ?? null;
                    if ($pIdx !== null && isset($items[$pIdx]) && $items[$pIdx]['name'] !== $name) {
                        if (!isset($items[$pIdx]['sub_items'])) $items[$pIdx]['sub_items'] = [];
                        $items[$pIdx]['sub_items'][] = [
                            'unit_ids'    => [$unitId],
                            'item_id'     => $itemId,
                            'name'        => $name,
                            'note'        => $note,
                            'is_returned' => $isReturned,
                            'item_time'   => $itemTimeIso,
                        ];
                        $items[$pIdx]['unit_ids'][] = $unitId;
                    } else {
                        $item['sub_items'] = [];
                        $items[] = $item;
                        $comboParentIdxByKey[$key] = count($items) - 1;
                    }
                } elseif ($isCondiment) {
                    // CONDIMENT: LineKind=URUN, IsCondiment=1 — bir önceki URUN'un altına girer
                    $pIdx = $lastUrunIdxByKey[$key] ?? null;
                    if ($pIdx !== null && isset($items[$pIdx])) {
                        if (!isset($items[$pIdx]['sub_items'])) $items[$pIdx]['sub_items'] = [];
                        $items[$pIdx]['sub_items'][] = [
                            'unit_ids'    => [$unitId],
                            'item_id'     => $itemId,
                            'name'        => $name,
                            'note'        => $note,
                            'is_returned' => $isReturned,
                            'item_time'   => $itemTimeIso,
                        ];
                        $items[$pIdx]['unit_ids'][] = $unitId;
                    } else {
                        // parent URUN yoksa bağımsız göster
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
                    // Ardışık aynı URUN → qty artır; hâlâ aynı parent
                    $items[$lastIdx]['unit_ids'][] = $unitId;
                    $items[$lastIdx]['qty']++;
                    if ($itemTimeIso < $items[$lastIdx]['item_time']) {
                        $items[$lastIdx]['item_time'] = $itemTimeIso;
                    }
                    // lastUrunIdxByKey zaten bu indeksi gösteriyor, değişmez
                    unset($comboParentIdxByKey[$key]);
                } else {
                    // Yeni URUN (ya da iade) → yeni parent
                    $item['sub_items'] = [];
                    $items[] = $item;
                    $lastUrunIdxByKey[$key]    = count($items) - 1;
                    unset($comboParentIdxByKey[$key]);
                }
                unset($items);

                // check'in order_time'ı = en erken item first_seen_at
                if (!$checks[$key]['order_time'] || $localTime < $checks[$key]['order_time']) {
                    $checks[$key]['order_time'] = $localTime;
                }
            }

            // order_time → ISO8601
            foreach ($checks as &$chk) {
                if ($chk['order_time']) {
                    try {
                        $chk['order_time'] = \Carbon\Carbon::parse($chk['order_time'], config('app.timezone'))->toIso8601String();
                    } catch (\Exception $e) {}
                }
            }
            unset($chk);

            // Onaylanan (kullanıcı tarafından "kaldırılan") mutfak mesajlarını filtrele.
            // group_key formatı: 'M' + item_id  (kitchen-pos.blade.php Onayla butonu).
            // DB'den hiçbir şey silmiyoruz — sadece aktif listede görünmesinler diye filtreliyoruz.
            $completedMsgKeys = DB::table('kitchen_pos_completions')
                ->where('kind', 'checkless_msg')
                ->pluck('group_key')
                ->all();
            if (!empty($completedMsgKeys)) {
                $completedMsgKeys = array_flip($completedMsgKeys);
                foreach ($checks as $k => $chk) {
                    $checks[$k]['messages'] = array_values(array_filter(
                        $chk['messages'],
                        fn ($m) => !isset($completedMsgKeys['M' . ($m['item_id'] ?? '')])
                    ));
                }
                $checkless = array_values(array_filter(
                    $checkless,
                    fn ($m) => !isset($completedMsgKeys['M' . ($m['item_id'] ?? '')])
                ));
            }

            // Tamamlanmış Symphony hesaplarını filtrele.
            // UnitID bazlı fingerprint karşılaştırması — yeni eklenen ürünler EK SİPARİŞ olarak gösterilir.
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
                        // served_item_keys boşsa (eski kayıt) → tüm ürünleri göster ama "yeniden açıldı" işaretle
                        $checks[$k]['is_reopened'] = true;
                        continue;
                    }
                    $servedSet = array_flip($servedKeys);
                    $newItems = [];
                    foreach ($chk['items'] as $item) {
                        if (!empty($item['unit_ids'])) {
                            // Yeni format: unit_ids bazlı — sadece servis edilmemiş birimler
                            $newUnitIds = array_values(array_filter(
                                $item['unit_ids'],
                                fn($uid) => !isset($servedSet[$uid])
                            ));
                            if (!empty($newUnitIds)) {
                                $item['unit_ids'] = $newUnitIds;
                                $item['qty'] = count($newUnitIds);
                                $newItems[] = $item;
                            }
                        } else {
                            // Eski format fallback: item_id veya dtl_seq|name
                            $fk = ($item['item_id'] !== null && $item['item_id'] !== '')
                                ? (string) $item['item_id']
                                : (($item['dtl_seq'] ?? 0) . '|' . $item['name']);
                            if (!isset($servedSet[$fk])) {
                                $newItems[] = $item;
                            }
                        }
                    }
                    if (empty($newItems)) {
                        unset($checks[$k]);
                    } else {
                        $checks[$k]['items'] = $newItems;
                        $checks[$k]['is_addition'] = true;
                        $earliest = collect($newItems)->filter(fn($i) => !empty($i['item_time']))->min('item_time');
                        if ($earliest) $checks[$k]['order_time'] = $earliest;
                    }
                }
            }

            // Mesajı + ürünü kalmayan boş Symphony kartlarını listeden çıkar
            $checks = array_filter($checks, fn ($c) => !empty($c['items']) || !empty($c['messages']));

            // En yeni order önce
            uasort($checks, function ($a, $b) {
                return strcmp((string) $b['order_time'], (string) $a['order_time']);
            });

            $symphonyOrders = array_values($checks);

            // Sadece Symphony siparişleri gösterilir; QR siparişleri bu ekrana düşmez.
            $activeOrders = $symphonyOrders;

            // Symphony tamamlama kaydı (sadece read-only Symphony hesapları içindi; QR akışı artık Order tablosu üzerinden ilerliyor)
            // Geriye uyumluluk için tablo dursun ama artık UI'da gösterilmiyor.

            $activeMessages = $checkless;

            $completedLimit = (int) Setting::get('kitchen_completed_display', 6);

            // Onaylanmış mutfak mesajları (alt panelde "Tamamlananlar" gösterimi için).
            // DB'den hiçbir şey silinmez; sadece son N tanesi UI'a gönderilir.
            $completedMsgs = DB::table('kitchen_pos_completions')
                ->where('kind', 'checkless_msg')
                ->orderByDesc('completed_at')
                ->limit($completedLimit)
                ->get()
                ->map(fn ($r) => [
                    'is_message'   => true,
                    'group_key'    => $r->group_key,
                    'table_no'     => $r->table_no,
                    'check_number' => $r->check_number,
                    'name'         => $r->name,
                    'note'         => $r->note,
                    'qty'          => (int) ($r->qty ?? 1),
                    'completed_at' => $r->completed_at,
                ])
                ->all();

            // Onaylanmış Symphony hesapları (alt panelde "Tamamlananlar" gösterimi için).
            $completedChecks = DB::table('kitchen_pos_completions')
                ->where('kind', 'check')
                ->orderByDesc('completed_at')
                ->limit($completedLimit)
                ->get()
                ->map(fn ($r) => [
                    'is_check'     => true,
                    'group_key'    => $r->group_key,
                    'table_no'     => $r->table_no,
                    'check_number' => $r->check_number,
                    'completed_at' => $r->completed_at,
                ])
                ->all();

            // QR tamamlananlar bu ekranda gösterilmez.
            $qrCompleted = [];

            $completedTodayCount = DB::table('kitchen_pos_completions')
                ->whereDate('completed_at', today())
                ->count();

            return response()->json([
                'success'         => true,
                'orders'          => $activeOrders,
                'messages'        => $activeMessages,
                'completed'       => $qrCompleted,
                'completed_msgs'  => $completedMsgs,
                'completed_checks'=> $completedChecks,
                'completed_limit' => $completedLimit,
                'completed_today' => $completedTodayCount,
                'fetched_at'      => now()->format('H:i:s'),
                'count'           => count($activeOrders),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success'  => false,
                'message'  => 'KDS sorgu hatası: ' . $e->getMessage(),
                'orders'   => [],
                'messages' => [],
                'completed' => [],
                'completed_msgs' => [],
            ], 200);
        }
    }

    /**
     * Symphony POS hesabı veya checksiz mesajı tamamlandı olarak işaretle.
     * item_keys: servis anındaki item fingerprint listesi (JSON array) — ek sipariş tespiti için.
     */
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

        // Mevcut kayıt varsa served_item_keys'i birleştir (birden fazla "Onayla" yapılabilir)
        $existing = DB::table('kitchen_pos_completions')->where('group_key', $validated['group_key'])->first();
        $existingKeys = [];
        if ($existing && $existing->served_item_keys) {
            $existingKeys = json_decode($existing->served_item_keys, true) ?: [];
        }
        $newKeys = array_values(array_unique(array_merge(
            $existingKeys,
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

    /**
     * Tamamlamayı geri al. (legacy)
     */
    public function kitchenPosUncomplete(Request $request)
    {
        $validated = $request->validate(['group_key' => 'required|string|max:64']);

        $row = DB::table('kitchen_pos_completions')->where('group_key', $validated['group_key'])->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Kayıt bulunamadı.'], 404);
        }

        $undoWindowSeconds = (int) Setting::get('ready_undo_seconds', 30);
        $age = now()->diffInSeconds(\Carbon\Carbon::parse($row->completed_at));
        if ($age > $undoWindowSeconds) {
            return response()->json([
                'success' => false,
                'message' => 'Geri alma süresi doldu (' . $undoWindowSeconds . ' sn).',
            ], 422);
        }

        DB::table('kitchen_pos_completions')->where('group_key', $validated['group_key'])->delete();
        return response()->json(['success' => true]);
    }

    /**
     * QR siparisini KDS'de "Onayla" → kitchen_status=ready, bar ekranı “servise götür” şeridine düşer.
     */
    public function kitchenPosConfirmQr(Order $order)
    {
        $order->update([
            'kitchen_status'   => 'ready',
            'status'           => 'ready',
            'bar_status'       => 'approved',
            'kitchen_ready_at' => $order->kitchen_ready_at ?? now(),
            'kitchen_started_at' => $order->kitchen_started_at ?? now(),
            'completed_at'     => null,
        ]);
        return response()->json(['success' => true]);
    }

    /**
     * QR siparisini KDS'de geri al → kitchen_status=preparing.
     */
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

    /**
     * Ana Mutfak KDS ekranı (sadece görüntüleme, ayrı veritabanı — AKDS)
     */
    public function kitchenAna()
    {
        return view('admin.kitchen-ana');
    }

    /**
     * Ana Mutfak KDS API — AKDS sorgusu çalıştırır, kartlar halinde döndürür.
     * Sadece görüntüleme; onaylama/tamamlama işlemi yoktur.
     */
    public function kitchenAnaApi()
    {
        $host     = (string) Setting::get('mssql_akds_host', '');
        $port     = (string) Setting::get('mssql_akds_port', '1433');
        $database = (string) Setting::get('mssql_akds_database', '');
        $username = (string) Setting::get('mssql_akds_username', '');
        $password = (string) Setting::get('mssql_akds_password', '');
        $query    = trim((string) Setting::get('mssql_akds_query', ''));
        $rvcFilter = trim((string) Setting::get('mssql_akds_rvc_filter', ''));

        if ($query === '' || !$host || !$database || !$username) {
            return response()->json([
                'success' => false,
                'message' => 'Ana Mutfak (AKDS) MSSQL ayarları/sorgusu eksik. Admin → MSSQL Ayarları → Ana Mutfak (AKDS) sekmesinden tanımlayın.',
                'orders'  => [],
            ], 200);
        }

        // {{RVC}} placeholder'ı RVC filtre değeriyle değiştir
        if (str_contains($query, '{{RVC}}')) {
            if ($rvcFilter === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'SQL sorgusunda {{RVC}} placeholder var ama RVC Filtresi boş. Admin → MSSQL Ayarları → Ana Mutfak (AKDS) → RVC Filtresi alanını doldurun.',
                    'orders'  => [],
                ], 200);
            }
            // Güvenlik: sadece sayı ve virgüle izin ver (SQL injection engeli)
            // Tek değer: 43  |  Çoklu: 43, 44, 45  → IN ({{RVC}}) ile kullanılır
            if (!preg_match('/^\d+(\s*,\s*\d+)*$/', $rvcFilter)) {
                return response()->json([
                    'success' => false,
                    'message' => 'RVC Filtresi sadece sayısal değer veya virgülle ayrılmış liste olabilir (örn: 43 ya da 43, 44, 45).',
                    'orders'  => [],
                ], 200);
            }
            // Virgüller arasındaki boşlukları normalize et
            $safeRvc = implode(', ', array_map('trim', explode(',', $rvcFilter)));
            $query = str_replace('{{RVC}}', $safeRvc, $query);
        }

        // ODBC Driver 18: -- yorum satırları (özellikle Türkçe karakter içerenler)
        // ve \r\n satır sonları SQLSTATE[42000] "nonspecific error" verebiliyor.
        // Yorum satırlarını kaldır ve satır sonlarını normalize et.
        $lines = explode("\n", str_replace("\r\n", "\n", $query));
        $lines = array_filter($lines, fn($l) => !preg_match('/^\s*--/', $l));
        $query = implode("\n", $lines);

        try {
            $actualPassword = '';
            if ($password) {
                try { $actualPassword = decrypt($password); } catch (\Exception $e) { $actualPassword = $password; }
            }

            $pdo = new \PDO("sqlsrv:Server={$host},{$port};Database={$database};TrustServerCertificate=1", $username, $actualPassword);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // -- yorum sat., \r\n ve trailing ; ODBC Driver 18'de SQLSTATE[42000] verebilir
            $lines = explode("\n", str_replace("\r\n", "\n", $query));
            $query = rtrim(trim(implode("\n", array_filter($lines, fn($l) => !preg_match('/^\s*--/', $l)))), ';');

            $stmt = $pdo->query($query);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $checks = [];

            foreach ($rows as $row) {
                $get = function (array $candidates, $default = null) use ($row) {
                    foreach ($candidates as $c) {
                        foreach ([$c, strtoupper($c), strtolower($c)] as $k) {
                            if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                                return $row[$k];
                            }
                        }
                    }
                    return $default;
                };

                $checkNum  = $get(['CheckNumber', 'check_number', 'ChkNum'], null);
                $tableNo   = (string) $get(['TableNumber', 'table_number'], '');
                $orderTime = $get(['OrderTime', 'order_time'], null);
                $itemTime  = $get(['ItemTime', 'item_time'], null);
                $rvc       = $get(['RevenueCenter', 'revenue_center'], '');
                $covers    = (int) $get(['Covers', 'covers'], 0);
                $qty       = (int) $get(['Qty', 'qty', 'Quantity'], 1);
                $name      = (string) $get(['ProductName', 'product_name', 'Name'], '');
                $note      = (string) $get(['MessageNote', 'message_note', 'RefInfo'], '');
                $itemId    = $get(['ItemID', 'item_id'], null);

                $groupKey = $checkNum !== null && (int) $checkNum > 0
                    ? (string) $checkNum          // KDS ile aynı format (ör: "4000")
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

                // Daha eski order_time'ı koru
                if ($orderTime && (!$checks[$groupKey]['order_time'] || strcmp((string) $orderTime, (string) $checks[$groupKey]['order_time']) < 0)) {
                    $checks[$groupKey]['order_time'] = $orderTime;
                }

                // item_time: önce ItemTime, yoksa OrderTime (check açılış saatine fallback)
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

                // order_time = bu check'teki en erken ürün zamanı
                if ($effectiveItemTime && (
                    !$checks[$groupKey]['order_time'] ||
                    strcmp((string) $effectiveItemTime, (string) $checks[$groupKey]['order_time']) < 0
                )) {
                    $checks[$groupKey]['order_time'] = $effectiveItemTime;
                }
            }

            // order_time'ı ISO8601'e çevir (JS timezone bağımsız parse eder)
            foreach ($checks as &$chk) {
                if ($chk['order_time']) {
                    try {
                        $chk['order_time'] = \Carbon\Carbon::parse((string) $chk['order_time'], 'Europe/Istanbul')->toIso8601String();
                    } catch (\Exception $e) {}
                }
            }
            unset($chk);

            // Tamamlanmış Symphony hesaplarını filtrele.
            // item fingerprint (item_id veya dtl_seq|name) karşılaştırması — zaman karşılaştırması değil.
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
                        unset($checks[$k]);
                        continue;
                    }
                    $servedSet = array_flip($servedKeys);
                    $newItems = array_values(array_filter($chk['items'], function ($item) use ($servedSet) {
                        $key = ($item['item_id'] !== null && $item['item_id'] !== '') ? (string) $item['item_id'] : ($item['dtl_seq'] . '|' . $item['name']);
                        return !isset($servedSet[$key]);
                    }));
                    if (empty($newItems)) {
                        unset($checks[$k]);
                    } else {
                        $checks[$k]['items'] = $newItems;
                        $checks[$k]['is_addition'] = true;
                        $earliest = collect($newItems)->filter(fn($i) => !empty($i['item_time']))->min('item_time');
                        if ($earliest) $checks[$k]['order_time'] = $earliest;
                    }
                }
            }

            // En yeni sipariş önce
            uasort($checks, function ($a, $b) {
                return strcmp((string) ($b['order_time'] ?? ''), (string) ($a['order_time'] ?? ''));
            });

            return response()->json([
                'success'    => true,
                'orders'     => array_values($checks),
                'fetched_at' => now()->format('H:i:s'),
                'count'      => count($checks),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ana Mutfak sorgu hatası: ' . $e->getMessage(),
                'orders'  => [],
            ], 200);
        }
    }
}
