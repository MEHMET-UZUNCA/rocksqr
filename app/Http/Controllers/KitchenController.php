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

        // Aktif grid: yeni gelenler + onaylanip mutfakta hazirlananlar
        // (mutfak hazir/tamamlandi olunca asagidaki ready/completed bolumlerine gecer)
        $orders = Order::where(function ($q) {
                $q->where('bar_status', 'new')
                  ->orWhere(function ($q2) {
                      $q2->where('bar_status', 'approved')
                         ->whereNotIn('kitchen_status', ['ready', 'completed']);
                  });
            })
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
            ->map(fn ($order) => $this->mapOrder($order));

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

        return response()->json([
            'orders' => $orders->values(),
            'ready_orders' => array_values($readyOrders),
            'ready_orders_limit' => $completedLimit,
            'completed_orders' => $completedOrders->values(),
            'completed_orders_limit' => $completedLimit,
            'waiter_calls' => $waiterCalls,
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

            $stmt = $pdo->prepare($query);
            $stmt->execute();
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
                    try { $secondsAgo = max(0, (int) \Carbon\Carbon::parse($g['order_time'])->diffInSeconds(now())); }
                    catch (\Exception $e) { $secondsAgo = 0; }
                }
                $out[] = [
                    'source'       => 'symphony',
                    'group_key'    => $g['group_key'],
                    'table_no'     => $g['table_no'],
                    'check_number' => $g['check_number'],
                    'order_time'   => $g['order_time'],
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
            $stmt = $pdo->prepare($query);
            $stmt->execute();
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

            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $checks  = [];
            $checkless = [];

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

                $checkNum   = $get(['CheckNumber', 'check_number', 'ChkNum'], null);
                $itemId     = $get(['ItemID', 'item_id'], null);
                $tableNo    = (string) $get(['TableNumber', 'table_number'], '');
                $orderTime  = $get(['OrderTime', 'order_time'], null);
                $itemTime   = $get(['ItemTime', 'item_time'], null);
                $rvc        = $get(['RevenueCenter', 'revenue_center'], '');
                $rvcId      = (int) $get(['RevenueCenterID', 'revenue_center_id'], 0);
                $covers     = (int) $get(['Covers', 'covers'], 0);
                $status     = (string) $get(['Status', 'status'], '');
                $qty        = (int) $get(['Qty', 'qty', 'Quantity'], 1);
                $name       = (string) $get(['ProductName', 'product_name', 'Name'], '');
                $note       = (string) $get(['MessageNote', 'message_note', 'RefInfo'], '');
                $majGrp     = (int) $get(['MajGrp', 'maj_grp'], 0);
                $dtlSeq     = (int) $get(['DtlSeq', 'dtl_seq'], 0);

                // MajGrp=1 yiyecek, MajGrp=99 mutfak mesajı/yorum
                $isMessage  = ($majGrp === 99);
                $hasCheck   = $checkNum !== null && (int) $checkNum > 0;

                // Mesajlar için benzersiz item_id (Symphony bazen ItemID döndürmez → tüm mesajlar
                // aynı group_key'i alır ve toplu onaylama/filtreleme bozulur). Fallback hash üret.
                if ($isMessage && (!$itemId || (string) $itemId === '0')) {
                    $itemId = 'm-' . substr(md5(($tableNo ?? '') . '|' . ($checkNum ?? '') . '|' . $dtlSeq . '|' . $name . '|' . ($note ?? '') . '|' . ($itemTime ?? '')), 0, 16);
                }

                $item = [
                    'item_id'    => $itemId,
                    'dtl_seq'    => $dtlSeq,
                    'qty'        => $qty,
                    'name'       => $name,
                    'note'       => $note,
                    'is_message' => $isMessage,
                    'item_time'  => $itemTime,
                    'maj_grp'    => $majGrp,
                ];

                if ($isMessage && !$hasCheck) {
                    $checkless[] = array_merge($item, [
                        'table_no'      => $tableNo,
                        'rvc'           => $rvc,
                        'rvc_id'        => $rvcId,
                    ]);
                    continue;
                }

                $key = (string) $checkNum ?: ('T' . $tableNo);
                if (!isset($checks[$key])) {
                    $checks[$key] = [
                        'check_number' => $checkNum,
                        'table_no'     => $tableNo,
                        'rvc'          => $rvc,
                        'rvc_id'       => $rvcId,
                        'order_time'   => $orderTime,
                        'covers'       => $covers,
                        'status'       => $status,
                        'items'        => [],
                        'messages'     => [],
                    ];
                }

                if ($isMessage) {
                    $checks[$key]['messages'][] = $item;
                } else {
                    $checks[$key]['items'][] = $item;
                }
            }

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

            // Tamamlanmış Symphony hesaplarını filtrele (group_key = check_number veya 'T'+table_no)
            $completedCheckKeys = DB::table('kitchen_pos_completions')
                ->where('kind', 'check')
                ->pluck('group_key')
                ->all();
            if (!empty($completedCheckKeys)) {
                $completedCheckKeys = array_flip($completedCheckKeys);
                foreach ($checks as $k => $chk) {
                    if (isset($completedCheckKeys[$k])) {
                        unset($checks[$k]);
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

            // QR (yerel) siparişleri ekle: kitchen ekranında gösterilecek (bar onaylı + mutfak yeni/preparing)
            $kitchenProductIds = Product::where('show_in_kitchen', true)->pluck('id')->all();
            $kitchenProductMap = Product::whereIn('id', $kitchenProductIds)->pluck('name', 'id')->all();

            $qrOrders = Order::whereIn('kitchen_status', ['new', 'preparing'])
                ->where('bar_status', 'approved')
                ->orderBy('created_at', 'asc')
                ->get();

            $qrCards = [];
            foreach ($qrOrders as $o) {
                $items = [];
                foreach (($o->items ?? []) as $it) {
                    $pid = (int) ($it['id'] ?? 0);
                    if (!in_array($pid, $kitchenProductIds, true)) continue;
                    $items[] = [
                        'item_id'    => 'qr-' . $o->id . '-' . $pid,
                        'dtl_seq'    => 0,
                        'qty'        => (int) ($it['quantity'] ?? 1),
                        'name'       => $kitchenProductMap[$pid] ?? ('Urun #' . $pid),
                        'note'       => '',
                        'is_message' => false,
                        'item_time'  => optional($o->created_at)->format('Y-m-d H:i:s'),
                        'maj_grp'    => 0,
                    ];
                }
                if (empty($items)) continue;

                $qrCards[] = [
                    'check_number'         => null,
                    'qr_order_id'          => $o->id,
                    'source'               => 'qr',
                    'table_no'             => (string) $o->table_no,
                    'rvc'                  => 'QR Menu',
                    'rvc_id'               => 0,
                    'order_time'           => optional($o->created_at)->format('Y-m-d H:i:s'),
                    'covers'               => 0,
                    'status'               => $o->kitchen_status,
                    'items'                => $items,
                    'messages'             => [],
                    'order_note'           => $o->order_note,
                    'kitchen_status'       => $o->kitchen_status,
                ];
            }

            // QR + Symphony birleşik aktif liste (en yeni sipariş ilk sırada)
            $activeOrders = array_merge($qrCards, $symphonyOrders);
            usort($activeOrders, function ($a, $b) {
                return strcmp((string) ($b['order_time'] ?? ''), (string) ($a['order_time'] ?? ''));
            });

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

            // QR tamamlananlar (kitchen_status=ready) — “servise götür” listesinin ön izlemesi
            $qrCompleted = Order::whereIn('kitchen_status', ['ready', 'completed'])
                ->where('bar_status', 'approved')
                ->orderByDesc(DB::raw('COALESCE(kitchen_ready_at, completed_at, updated_at)'))
                ->limit($completedLimit)
                ->get()
                ->map(function ($o) use ($kitchenProductMap, $kitchenProductIds) {
                    $items = [];
                    foreach (($o->items ?? []) as $it) {
                        $pid = (int) ($it['id'] ?? 0);
                        if (!in_array($pid, $kitchenProductIds, true)) continue;
                        $items[] = [
                            'qty'  => (int) ($it['quantity'] ?? 1),
                            'name' => $kitchenProductMap[$pid] ?? ('Urun #' . $pid),
                        ];
                    }
                    if (empty($items)) return null;
                    return [
                        'qr_order_id'        => $o->id,
                        'source'             => 'qr',
                        'check_number'       => null,
                        'table_no'           => (string) $o->table_no,
                        'kitchen_status'     => $o->kitchen_status,
                        'completed_at'       => optional($o->kitchen_ready_at ?? $o->completed_at)->format('Y-m-d H:i:s'),
                        'items'              => $items,
                        'messages'           => [],
                    ];
                })
                ->filter()
                ->values()
                ->all();

            return response()->json([
                'success'         => true,
                'orders'          => $activeOrders,
                'messages'        => $activeMessages,
                'completed'       => $qrCompleted,
                'completed_msgs'  => $completedMsgs,
                'completed_checks'=> $completedChecks,
                'completed_limit' => $completedLimit,
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
     * Symphony POS hesabı veya checksiz mesajı tamamlandı olarak işaretle. (legacy)
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
        ]);

        DB::table('kitchen_pos_completions')->updateOrInsert(
            ['group_key' => $validated['group_key']],
            [
                'kind'         => $validated['kind'],
                'check_number' => $validated['check_number'] ?? null,
                'table_no'     => $validated['table_no'] ?? null,
                'name'         => $validated['name'] ?? null,
                'note'         => $validated['note'] ?? null,
                'qty'          => $validated['qty'] ?? 1,
                'completed_at' => now(),
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
}
