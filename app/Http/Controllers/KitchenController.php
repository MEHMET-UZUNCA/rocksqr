<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MapsOrders;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    use MapsOrders;

    public function kitchen()
    {
        return view('admin.kitchen');
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
                return response()->json(['success' => false, 'message' => 'Geri alma suresi doldu.'], 422);
            }
        }

        $payload = [
            'kitchen_status' => $validated['status'],
            'status'         => $validated['status'],
            'bar_status'     => 'approved',
            'completed_at'   => $validated['status'] === 'completed' ? now() : null,
        ];

        if ($validated['status'] === 'preparing' && $order->kitchen_started_at === null) {
            $payload['kitchen_started_at'] = now();
        }

        if ($validated['status'] === 'ready' && $order->kitchen_ready_at === null) {
            $payload['kitchen_ready_at'] = now();
        }

        if ($isUndoToPreparing) {
            $payload['kitchen_ready_at'] = null;
            $payload['completed_at']     = null;
        }

        $order->update($payload);
        return response()->json(['success' => true, 'status' => $order->kitchen_status]);
    }

    public function kitchenAckCancel(Order $order)
    {
        if ($order->kitchen_status !== 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Bu sipariş iptal durumunda değil.'], 422);
        }
        $order->update(['kitchen_status' => 'completed']);
        return response()->json(['success' => true]);
    }

    public function kitchenApiOrders()
    {
        $completedLimit = (int) Setting::get('kitchen_completed_display', 6);

        $activeOrders = Order::whereIn('kitchen_status', ['new', 'preparing'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($order) => $this->mapOrder($order));

        $cancelledOrders = Order::where('kitchen_status', 'cancelled')
            ->where('completed_at', '>=', now()->subMinutes(5))
            ->orderBy('completed_at', 'desc')
            ->get()
            ->map(fn ($order) => $this->mapOrder($order));

        $completedOrders = Order::where(function ($q) {
                $q->where('kitchen_status', 'ready')
                  ->orWhere(function ($q2) {
                      $q2->where('kitchen_status', 'completed')
                         ->where('status', 'cancelled');
                  });
            })
            ->orderByRaw('COALESCE(completed_at, kitchen_ready_at) DESC')
            ->limit($completedLimit)
            ->get()
            ->map(fn ($order) => $this->mapOrder($order));

        return response()->json([
            'orders'          => $activeOrders->values(),
            'cancelled'       => $cancelledOrders->values(),
            'completed'       => $completedOrders->values(),
            'completed_limit' => $completedLimit,
        ]);
    }

    public function kitchenSse()
    {
        return response()->stream(function () {
            $completedLimit = (int) Setting::get('kitchen_completed_display', 6);
            $timeout        = time() + 25;

            while (time() < $timeout) {
                $activeOrders = Order::whereIn('kitchen_status', ['new', 'preparing'])
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(fn ($order) => $this->mapOrder($order));

                $cancelledOrders = Order::where('kitchen_status', 'cancelled')
                    ->where('completed_at', '>=', now()->subMinutes(5))
                    ->orderBy('completed_at', 'desc')
                    ->get()
                    ->map(fn ($order) => $this->mapOrder($order));

                $completedOrders = Order::where(function ($q) {
                        $q->where('kitchen_status', 'ready')
                          ->orWhere(function ($q2) {
                              $q2->where('kitchen_status', 'completed')
                                 ->where('status', 'cancelled');
                          });
                    })
                    ->orderByRaw('COALESCE(completed_at, kitchen_ready_at) DESC')
                    ->limit($completedLimit)
                    ->get()
                    ->map(fn ($order) => $this->mapOrder($order));

                $payload = json_encode([
                    'orders'          => $activeOrders->values(),
                    'cancelled'       => $cancelledOrders->values(),
                    'completed'       => $completedOrders->values(),
                    'completed_limit' => $completedLimit,
                ]);

                echo "data: {$payload}\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                if (connection_aborted()) break;
                sleep(2);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}
