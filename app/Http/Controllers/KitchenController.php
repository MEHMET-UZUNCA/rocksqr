<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\WaiterCall;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    public function index()
    {
        $orders = Order::whereIn('status', ['new', 'preparing'])
            ->orderBy('created_at', 'desc')
            ->get();

        $waiterCalls = WaiterCall::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.kitchen', compact('orders', 'waiterCalls'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:preparing,ready,completed',
        ]);

        $order->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'completed' ? now() : $order->completed_at,
        ]);

        return response()->json(['success' => true, 'status' => $order->status]);
    }

    public function attendWaiterCall(WaiterCall $waiterCall)
    {
        $waiterCall->markAsAttended();
        return response()->json(['success' => true]);
    }

    public function apiOrders()
    {
        $orders = Order::whereIn('status', ['new', 'preparing'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'table_no' => $order->table_no,
                    'items' => $order->items,
                    'total_price' => $order->total_price,
                    'order_note' => $order->order_note,
                    'status' => $order->status,
                    'created_at' => $order->created_at->format('H:i:s'),
                    'seconds_ago' => (int) $order->created_at->diffInSeconds(now()),
                    'confirmed_seconds' => $order->status !== 'new'
                        ? (int) $order->created_at->diffInSeconds($order->updated_at)
                        : null,
                ];
            });

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
            'orders' => $orders,
            'waiter_calls' => $waiterCalls,
        ]);
    }
}
