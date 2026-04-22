@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Kitchen Screen</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($orders as $order)
                <div class="bg-white rounded-lg shadow-lg overflow-hidden {{ $order->status === 'new' ? 'border-4 border-red-500' : 'border-4 border-yellow-500' }}">
                    <div class="bg-gray-100 px-6 py-4 border-b border-gray-200">
                        <p class="text-sm text-gray-600">Order #{{ $order->id }}</p>
                        <p class="text-2xl font-bold text-gray-900">Table {{ $order->table_no }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $order->created_at->diffForHumans() }}</p>
                    </div>

                    <div class="px-6 py-4">
                        <h4 class="font-semibold text-gray-900 mb-3">Items:</h4>
                        <div class="space-y-2 mb-4">
                            @foreach($order->items as $item)
                                <div class="flex justify-between items-center text-sm">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $item['name'] }}</p>
                                        <p class="text-gray-600">Qty: {{ $item['quantity'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($order->order_note)
                            <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                                <p class="text-sm text-yellow-900"><strong>Note:</strong> {{ $order->order_note }}</p>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="space-y-3">
                            @csrf
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded" onchange="this.form.submit()">
                                <option value="new" {{ $order->status === 'new' ? 'selected' : '' }}>New</option>
                                <option value="preparing" {{ $order->status === 'preparing' ? 'selected' : '' }}>Preparing</option>
                                <option value="ready" {{ $order->status === 'ready' ? 'selected' : '' }}>Ready</option>
                                <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-12 text-gray-500">
                    <p>No orders to prepare</p>
                </div>
            @endforelse
        </div>

        <script>
            setInterval(() => {
                location.reload();
            }, 15000); // Refresh every 15 seconds
        </script>
    </div>
</div>
@endsection