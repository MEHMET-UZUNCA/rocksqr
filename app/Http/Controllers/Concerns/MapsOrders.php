<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Order;
use App\Models\Setting;

trait MapsOrders
{
    private function mapOrder(Order $order): array
    {
        $preparingSeconds    = null;
        $readySeconds        = null;
        $readySinceSeconds   = null;
        $canUndoReady        = false;
        $undoRemainingSeconds = 0;
        $undoWindowSeconds   = (int) Setting::get('ready_undo_seconds', 30);

        if ($order->kitchen_started_at !== null && $order->kitchen_status === 'preparing') {
            $preparingSeconds = (int) $order->kitchen_started_at->diffInSeconds(now());
        }

        if ($order->kitchen_started_at !== null && $order->kitchen_ready_at !== null) {
            $readySeconds = (int) $order->kitchen_started_at->diffInSeconds($order->kitchen_ready_at);
        }

        if ($order->kitchen_status === 'ready' && $order->kitchen_ready_at !== null) {
            $readySinceSeconds    = (int) $order->kitchen_ready_at->diffInSeconds(now());
            $undoRemainingSeconds = max(0, $undoWindowSeconds - $readySinceSeconds);
            $canUndoReady         = $undoRemainingSeconds > 0;
        }

        return [
            'id'                    => $order->id,
            'table_no'              => $order->table_no,
            'items'                 => $order->items,
            'total_price'           => $order->total_price,
            'order_note'            => $order->order_note,
            'status'                => $order->status,
            'bar_status'            => $order->bar_status,
            'kitchen_status'        => $order->kitchen_status,
            'created_at'            => $order->created_at->format('H:i:s'),
            'order_time'            => $order->created_at->toIso8601String(),
            'seconds_ago'           => (int) $order->created_at->diffInSeconds(now()),
            'preparing_seconds'     => $preparingSeconds,
            'ready_seconds'         => $readySeconds,
            'ready_since_seconds'   => $readySinceSeconds,
            'can_undo_ready'        => $canUndoReady,
            'undo_remaining_seconds'=> $undoRemainingSeconds,
        ];
    }
}
