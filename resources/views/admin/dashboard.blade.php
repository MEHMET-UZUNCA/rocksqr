@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-tachometer-alt mr-2 text-gold"></i>Admin Dashboard
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-blue-600 text-sm font-semibold">Toplam Sipariş</p>
                        <p class="text-3xl font-bold text-blue-900">{{ \App\Models\Order::count() }}</p>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-yellow-600 text-sm font-semibold">Yeni Siparişler</p>
                        <p class="text-3xl font-bold text-yellow-900">{{ \App\Models\Order::where('status', 'new')->count() }}</p>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                        <p class="text-emerald-600 text-sm font-semibold">Toplam Sipariş Tutarı</p>
                        <p class="text-3xl font-bold text-emerald-900">{{ number_format(\App\Models\Order::sum('total_price'), 2) }} ₺</p>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="text-green-600 text-sm font-semibold">Günlük Satış Tutarı</p>
                        <p class="text-3xl font-bold text-green-900">
                            {{ number_format(\App\Models\Order::whereDate('created_at', now()->toDateString())->sum('total_price'), 2) }} ₺
                        </p>
                    </div>
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                        <p class="text-indigo-600 text-sm font-semibold">Aylık Satış Tutarı</p>
                        <p class="text-3xl font-bold text-indigo-900">
                            {{ number_format(\App\Models\Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_price'), 2) }} ₺
                        </p>
                    </div>
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                        <p class="text-purple-600 text-sm font-semibold">Toplam Ürün</p>
                        <p class="text-3xl font-bold text-purple-900">{{ \App\Models\Product::count() }}</p>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="text-green-600 text-sm font-semibold">Bekleyen Çağrılar</p>
                        <p class="text-3xl font-bold text-green-900">{{ \App\Models\WaiterCall::where('status', 'pending')->count() }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Hızlı Erişim</h3>
                        <div class="space-y-2">
                            <a href="{{ route('bar') }}" class="block px-4 py-3 bg-red-700 text-white rounded hover:bg-red-800 transition">
                                <i class="fas fa-wine-glass mr-2"></i> Bar Ekrani (KDS)
                            </a>
                            <a href="{{ route('kitchen') }}" class="block px-4 py-3 bg-primary text-white rounded hover:bg-light-primary transition">
                                <i class="fas fa-tv mr-2 text-gold"></i> Kitchen Ekrani (KDS)
                            </a>
                            <a href="{{ route('kitchen.pos') }}" class="block px-4 py-3 bg-indigo-800 text-white rounded hover:bg-indigo-900 transition">
                                <i class="fas fa-fire mr-2"></i> Symphony Mutfak (KDS)
                            </a>
                            <a href="{{ route('kitchen.ana') }}" class="block px-4 py-3 bg-teal-700 text-white rounded hover:bg-teal-800 transition">
                                <i class="fas fa-tv mr-2"></i> Ana Mutfak (AKDS)
                            </a>
                            <a href="{{ route('admin.categories.index') }}" class="block px-4 py-3 bg-primary text-white rounded hover:bg-light-primary transition">
                                <i class="fas fa-folder mr-2 text-gold"></i> Kategorileri Yönet
                            </a>
                            <a href="{{ route('admin.products.index') }}" class="block px-4 py-3 bg-primary text-white rounded hover:bg-light-primary transition">
                                <i class="fas fa-box mr-2 text-gold"></i> Ürünleri Yönet
                            </a>
                            <a href="{{ route('admin.categories.create') }}" class="block px-4 py-3 bg-primary text-white rounded hover:bg-light-primary transition">
                                <i class="fas fa-plus mr-2 text-gold"></i> Yeni Kategori Ekle
                            </a>
                            <a href="{{ route('admin.products.create') }}" class="block px-4 py-3 bg-primary text-white rounded hover:bg-light-primary transition">
                                <i class="fas fa-plus mr-2 text-gold"></i> Yeni Ürün Ekle
                            </a>
                            <a href="{{ route('admin.qr-codes.index') }}" class="block px-4 py-3 bg-primary text-white rounded hover:bg-light-primary transition">
                                <i class="fas fa-qrcode mr-2 text-gold"></i> Masa QR Oluştur
                            </a>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-trophy mr-2 text-gold"></i>En Çok Satılan Ürünler
                        </h3>
                        @php
                            $topProducts = collect();
                            $orders = \App\Models\Order::all();
                            $productCounts = [];
                            foreach ($orders as $order) {
                                $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;
                                if (is_array($items)) {
                                    foreach ($items as $item) {
                                        $id = $item['id'] ?? null;
                                        $qty = $item['quantity'] ?? 1;
                                        if ($id) {
                                            $productCounts[$id] = ($productCounts[$id] ?? 0) + $qty;
                                        }
                                    }
                                }
                            }
                            arsort($productCounts);
                            $topProducts = collect(array_slice($productCounts, 0, 5, true));
                        @endphp
                        @if($topProducts->isEmpty())
                            <p class="text-gray-400 text-sm">Henüz sipariş yok.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($topProducts as $productId => $qty)
                                    @php $product = \App\Models\Product::find($productId); @endphp
                                    @if($product)
                                    <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <span class="text-lg font-bold text-gold">{{ $loop->iteration }}.</span>
                                            <span class="font-medium text-gray-800">{{ $product->name }}</span>
                                        </div>
                                        <span class="bg-gold/20 text-yellow-800 px-3 py-1 rounded-full text-sm font-bold">{{ $qty }} adet</span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Most Called Tables -->
            <div class="mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-bell mr-2 text-red-500"></i>En Çok Garson Çağrılan Masalar
                </h3>
                @php
                    $topTables = \App\Models\WaiterCall::whereNotNull('table_no')
                        ->selectRaw('table_no, COUNT(*) as call_count')
                        ->groupBy('table_no')
                        ->orderByDesc('call_count')
                        ->limit(5)
                        ->get();
                @endphp
                @if($topTables->isEmpty())
                    <p class="text-gray-400 text-sm">Henüz garson çağrısı yok.</p>
                @else
                    <div class="space-y-2">
                        @foreach($topTables as $table)
                        <div class="flex items-center justify-between bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="text-lg font-bold text-red-500">{{ $loop->iteration }}.</span>
                                <span class="font-medium text-gray-800">Masa {{ $table->table_no }}</span>
                            </div>
                            <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-bold">{{ $table->call_count }} çağrı</span>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection