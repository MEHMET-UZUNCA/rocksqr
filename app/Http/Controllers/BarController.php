<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MapsOrders;
use App\Models\Order;
use App\Models\Setting;
use App\Models\WaiterCall;
use App\Services\MssqlService;
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
        return response()->json(['success' => true]);
    }

    public function barApiOrders()
    {
        $completedLimit    = (int) Setting::get('bar_completed_display', 6);
        $undoWindowSeconds = (int) Setting::get('ready_undo_seconds', 30);

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

        // Symphony KDS onayları — delivered_at IS NULL olanlar bar "servise götür" şeridinde
        $symphonyReady = DB::table('kitchen_pos_completions')
            ->whereNull('delivered_at')
            ->orderByDesc('completed_at')
            ->limit($completedLimit)
            ->get();

        foreach ($symphonyReady as $row) {
            $completedAt       = \Carbon\Carbon::parse($row->completed_at);
            $readySinceSeconds = (int) $completedAt->diffInSeconds(now());
            $undoRemaining     = max(0, $undoWindowSeconds - $readySinceSeconds);
            $itemName          = trim((string) ($row->name ?? ''));
            $qty               = (int) ($row->qty ?? 1);

            $itemsArr = $row->kind === 'check'
                ? [['id' => null, 'name' => 'Adisyon #' . ($row->check_number ?: '-'), 'quantity' => 1]]
                : [['id' => null, 'name' => $itemName !== '' ? $itemName : 'Mutfak mesajı', 'quantity' => max(1, $qty)]];

            $readyOrders[] = [
                'id'                  => 0,
                'source'              => 'symphony',
                'group_key'           => $row->group_key,
                'kind'                => $row->kind,
                'table_no'            => $row->table_no,
                'items'               => $itemsArr,
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
        $readyOrders = array_slice($readyOrders, 0, $completedLimit);

        $completedOrders = Order::whereIn('kitchen_status', ['completed', 'cancelled'])
            ->orderBy('completed_at', 'desc')
            ->limit($completedLimit)
            ->get()
            ->map(fn ($order) => $this->mapOrder($order))
            ->values()
            ->all();

        // Symphony servis edilenleri tamamlananlara ekle
        $symphonyDelivered = DB::table('kitchen_pos_completions')
            ->whereNotNull('delivered_at')
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
            'ready_orders_limit'    => $completedLimit,
            'completed_orders'      => collect($completedOrders)->values(),
            'completed_orders_limit'=> $completedLimit,
            'waiter_calls'          => $waiterCalls,
            'attended_calls'        => $attendedCalls,
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

            $groups = [];
            foreach ($rows as $row) {
                $tableNo    = (string) $this->mssql->getField($row, ['TableNo', 'TableNumber', 'MASA', 'table_no'], '');
                $checkNum   = $this->mssql->getField($row, ['CheckNumber', 'CheckNum', 'ADISYON', 'check_number'], null);
                $itemName   = (string) $this->mssql->getField($row, ['ItemName', 'ProductName', 'Name', 'item_name'], '');
                $qty        = (int) $this->mssql->getField($row, ['Qty', 'Quantity', 'ADET', 'qty'], 1);
                $orderTime  = $this->mssql->getField($row, ['OrderTime', 'ItemTime', 'Time', 'order_time'], null);
                $note       = (string) $this->mssql->getField($row, ['Note', 'RefInfo', 'MessageNote', 'note'], '');
                $waiterName = trim(
                    (string) $this->mssql->getField($row, ['WaiterName', 'waiter_name'], '') . ' ' .
                    (string) $this->mssql->getField($row, ['WaiterSurname', 'waiter_surname'], '')
                );

                $key = $checkNum !== null && $checkNum !== '' ? 'C' . $checkNum : 'T' . $tableNo;
                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'group_key'    => $key,
                        'table_no'     => $tableNo,
                        'check_number' => $checkNum,
                        'order_time'   => $orderTime,
                        'waiter_name'  => $waiterName,
                        'items'        => [],
                    ];
                }
                if ($orderTime && (!$groups[$key]['order_time'] || strcmp((string) $orderTime, (string) $groups[$key]['order_time']) < 0)) {
                    $groups[$key]['order_time'] = $orderTime;
                }
                $groups[$key]['items'][] = ['name' => $itemName, 'qty' => max(1, $qty), 'note' => $note];
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
                    'items'        => array_values($g['items']),
                ];
            }

            return response()->json(['success' => true, 'orders' => $out, 'count' => count($out)]);
        } catch (\Exception $e) {
            Log::error('BDS MSSQL sorgu hatası', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'BDS bağlantı hatası oluştu.', 'orders' => []]);
        }
    }
}
