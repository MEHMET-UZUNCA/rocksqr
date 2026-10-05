<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\WaiterCall;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    private const MAX_TABLE_NO = 500;

    public const MAX_ITEM_QUANTITY = 99;

    public function index()
    {
        $categories = Category::where('is_active', true)
            ->with('activeProducts')
            ->orderBy('sort_order')
            ->get();

        $tableNo = null;
        $roomList = $this->roomList();

        return view('customer.menu', compact('categories', 'tableNo', 'roomList'));
    }

    public function show(int $tableNo)
    {
        if ($tableNo < 1 || $tableNo > self::MAX_TABLE_NO) {
            abort(404);
        }

        $categories = Category::where('is_active', true)
            ->with('activeProducts')
            ->orderBy('sort_order')
            ->get();

        $roomList = $this->roomList();

        return view('customer.menu', compact('categories', 'tableNo', 'roomList'));
    }

    public function placeOrder(Request $request, int $tableNo)
    {
        if ($tableNo < 1 || $tableNo > self::MAX_TABLE_NO) {
            abort(404);
        }

        return $this->storeOrder($request, $tableNo);
    }

    public function placeOrderPublic(Request $request)
    {
        return $this->storeOrder($request, null);
    }

    public function orderSuccess(Order $order)
    {
        return view('customer.order-success', compact('order'));
    }

    public function callWaiter(Request $request, int $tableNo)
    {
        if ($tableNo < 1 || $tableNo > self::MAX_TABLE_NO) {
            abort(404);
        }

        $roomList = $this->roomList();

        $validated = $request->validate([
            'note'    => 'nullable|string|max:200',
            'room_no' => empty($roomList)
                ? ['nullable', 'string', 'max:16']
                : ['nullable', 'string', 'max:16', Rule::in($roomList)],
        ], [
            'room_no.in' => 'Oda numarası hatalı, lütfen kontrol edin.',
        ]);

        WaiterCall::create([
            'table_no' => $tableNo,
            'room_no'  => ($validated['room_no'] ?? '') !== '' ? $validated['room_no'] : null,
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Waiter called successfully!']);
    }

    public function callWaiterPublic(Request $request)
    {
        $roomList = $this->roomList();

        $validated = $request->validate([
            'note'    => 'nullable|string|max:200',
            'room_no' => empty($roomList)
                ? ['nullable', 'string', 'max:16']
                : ['nullable', 'string', 'max:16', Rule::in($roomList)],
        ], [
            'room_no.in' => 'Oda numarası hatalı, lütfen kontrol edin.',
        ]);

        WaiterCall::create([
            'table_no' => null,
            'room_no'  => ($validated['room_no'] ?? '') !== '' ? $validated['room_no'] : null,
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['success' => true, 'message' => 'Waiter called successfully!']);
    }

    private function storeOrder(Request $request, ?int $tableNo)
    {
        $items = $request->input('items');
        if (is_string($items)) {
            $items = json_decode($items, true);
            $request->merge(['items' => $items]);
        }
        $request->merge(['room_no' => trim((string) $request->input('room_no', ''))]);

        $roomList = $this->roomList();

        $rules = [
            'items'            => 'required|array',
            'items.*.id'       => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:' . self::MAX_ITEM_QUANTITY,
            'order_note'       => 'nullable|string|max:500',
        ];

        // Oda listesi admin panelinde tanimliysa oda numarasi zorunlu ve listede olmali.
        $rules['room_no'] = empty($roomList)
            ? ['nullable', 'string', 'max:16']
            : ['required', 'string', 'max:16', Rule::in($roomList)];

        $validated = $request->validate($rules, [
            'items.*.quantity.max' => 'En fazla sipariş limitine ulaşıldı.',
            'room_no.required'     => 'Lütfen oda numaranızı girin.',
            'room_no.in'           => 'Oda numarası hatalı, lütfen kontrol edin.',
        ]);

        // Tutar yalnızca DB fiyatlarından hesaplanır; client'tan gelen total_price kullanılmaz.
        $products = Product::query()
            ->whereIn('id', collect($validated['items'])->pluck('id'))
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        $orderItems = [];
        $totalPrice = 0;

        foreach ($validated['items'] as $item) {
            $product = $products->get($item['id']);
            if ($product === null) {
                continue;
            }
            $orderItems[] = [
                'id'       => $product->id,
                'name'     => $product->name,
                'price'    => (float) $product->price,
                'quantity' => (int) $item['quantity'],
            ];
            $totalPrice += (float) $product->price * (int) $item['quantity'];
        }

        if (empty($orderItems)) {
            return back()->withErrors(['items' => 'Sepetinizdeki ürünler artık mevcut değil.']);
        }

        $order = Order::create([
            'table_no'       => $tableNo,
            'room_no'        => ($validated['room_no'] ?? '') !== '' ? $validated['room_no'] : null,
            'total_price'    => $totalPrice,
            'order_note'     => $validated['order_note'] ?? null,
            'status'         => 'new',
            'bar_status'     => 'new',
            'kitchen_status' => 'waiting',
            'items_json'     => json_encode($orderItems),
        ]);

        return redirect()->route('order.success', ['order' => $order->id]);
    }

    private function roomList(): array
    {
        return collect(explode(',', (string) Setting::get('room_numbers', '')))
            ->map(fn ($room) => trim($room))
            ->filter(fn ($room) => $room !== '')
            ->unique()
            ->values()
            ->all();
    }
}
