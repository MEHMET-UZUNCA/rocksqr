<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\WaiterCall;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->with('activeProducts')
            ->orderBy('sort_order')
            ->get();

        $tableNo = null;

        return view('customer.menu', compact('categories', 'tableNo'));
    }

    public function show(int $tableNo)
    {
        if ($tableNo < 1 || $tableNo > 100) {
            abort(404);
        }

        $categories = Category::where('is_active', true)
            ->with('activeProducts')
            ->orderBy('sort_order')
            ->get();

        return view('customer.menu', compact('categories', 'tableNo'));
    }

    public function placeOrder(Request $request, int $tableNo)
    {
        $items = $request->input('items');
        if (is_string($items)) {
            $items = json_decode($items, true);
            $request->merge(['items' => $items]);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'order_note' => 'nullable|string|max:500',
        ]);

        $order = Order::create([
            'table_no' => $tableNo,
            'total_price' => $validated['total_price'],
            'order_note' => $validated['order_note'] ?? null,
            'status' => 'new',
            'items_json' => json_encode($validated['items']),
        ]);

        return redirect()->route('order.success', ['order' => $order->id]);
    }

    public function placeOrderPublic(Request $request)
    {
        $items = $request->input('items');
        if (is_string($items)) {
            $items = json_decode($items, true);
            $request->merge(['items' => $items]);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'order_note' => 'nullable|string|max:500',
        ]);

        $order = Order::create([
            'table_no' => null,
            'total_price' => $validated['total_price'],
            'order_note' => $validated['order_note'] ?? null,
            'status' => 'new',
            'items_json' => json_encode($validated['items']),
        ]);

        return redirect()->route('order.success', ['order' => $order->id]);
    }

    public function orderSuccess(Order $order)
    {
        return view('customer.order-success', compact('order'));
    }

    public function callWaiter(Request $request, int $tableNo)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:200',
        ]);

        WaiterCall::create([
            'table_no' => $tableNo,
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Waiter called successfully!']);
    }

    public function callWaiterPublic(Request $request)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:200',
        ]);

        WaiterCall::create([
            'table_no' => null,
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Waiter called successfully!']);
    }
}