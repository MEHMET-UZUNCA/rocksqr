@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-tachometer-alt mr-2 text-gold"></i>Admin Dashboard
                </h2>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-blue-100">
                            <i class="fas fa-receipt text-blue-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">{{ \App\Models\Order::count() }}</p>
                            <p class="text-xs lg:text-sm text-gray-500">Toplam Sipariş</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-amber-100">
                            <i class="fas fa-clock text-amber-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">{{ \App\Models\Order::where('status', 'new')->count() }}</p>
                            <p class="text-xs lg:text-sm text-gray-500">Yeni Siparişler</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-green-100">
                            <i class="fas fa-calendar-day text-green-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">
                                {{ number_format(\App\Models\Order::whereDate('created_at', now()->toDateString())->sum('total_price'), 2) }} ₺
                            </p>
                            <p class="text-xs lg:text-sm text-gray-500">Günlük Satış</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-100">
                            <i class="fas fa-calendar-alt text-indigo-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">
                                {{ number_format(\App\Models\Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_price'), 2) }} ₺
                            </p>
                            <p class="text-xs lg:text-sm text-gray-500">Aylık Satış</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-100">
                            <i class="fas fa-wallet text-emerald-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">
                                {{ number_format(\App\Models\Order::sum('total_price'), 2) }} ₺
                            </p>
                            <p class="text-xs lg:text-sm text-gray-500">Toplam Sipariş Tutarı</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-purple-100">
                            <i class="fas fa-box text-purple-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">{{ \App\Models\Product::count() }}</p>
                            <p class="text-xs lg:text-sm text-gray-500">Toplam Ürün</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-red-100">
                            <i class="fas fa-bell-concierge text-red-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">{{ \App\Models\WaiterCall::where('status', 'pending')->count() }}</p>
                            <p class="text-xs lg:text-sm text-gray-500">Bekleyen Çağrılar</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Hızlı Erişim</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <a href="{{ route('bar') }}" class="flex items-center px-4 py-3 bg-red-700 text-white rounded-lg hover:bg-red-800 transition text-sm font-semibold">
                                <i class="fas fa-wine-glass mr-2 w-4 text-center"></i> Bar Ekranı (BDS)
                            </a>
                            <a href="{{ route('kitchen.pos') }}" class="flex items-center px-4 py-3 bg-indigo-800 text-white rounded-lg hover:bg-indigo-900 transition text-sm font-semibold">
                                <i class="fas fa-server mr-2 w-4 text-center"></i> Symphony Mutfak (KDS)
                            </a>
                            <a href="{{ route('kitchen.ana') }}" class="flex items-center px-4 py-3 bg-teal-700 text-white rounded-lg hover:bg-teal-800 transition text-sm font-semibold">
                                <i class="fas fa-fire-burner mr-2 w-4 text-center"></i> Ana Mutfak (AKDS)
                            </a>
                            <a href="{{ route('admin.categories.index') }}" class="flex items-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-light-primary transition text-sm font-semibold">
                                <i class="fas fa-folder mr-2 w-4 text-center text-gold"></i> Kategorileri Yönet
                            </a>
                            <a href="{{ route('admin.products.index') }}" class="flex items-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-light-primary transition text-sm font-semibold">
                                <i class="fas fa-box mr-2 w-4 text-center text-gold"></i> Ürünleri Yönet
                            </a>
                            <a href="{{ route('admin.categories.create') }}" class="flex items-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-light-primary transition text-sm font-semibold">
                                <i class="fas fa-plus mr-2 w-4 text-center text-gold"></i> Yeni Kategori Ekle
                            </a>
                            <a href="{{ route('admin.products.create') }}" class="flex items-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-light-primary transition text-sm font-semibold">
                                <i class="fas fa-plus mr-2 w-4 text-center text-gold"></i> Yeni Ürün Ekle
                            </a>
                            <a href="{{ route('admin.qr-codes.index') }}" class="flex items-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-light-primary transition text-sm font-semibold">
                                <i class="fas fa-qrcode mr-2 w-4 text-center text-gold"></i> Masa QR Oluştur
                            </a>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-trophy mr-2 text-gold"></i>En Çok Satılan Ürünler
                        </h3>
                        @php
                            $productCounts = [];
                            $itemsColumns = \App\Models\Order::query()->pluck('items_json');
                            foreach ($itemsColumns as $itemsJson) {
                                $items = is_string($itemsJson) ? json_decode($itemsJson, true) : $itemsJson;
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
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="text-lg font-bold text-gold">{{ $loop->iteration }}.</span>
                                            <span class="font-medium text-gray-800 truncate">{{ $product->name }}</span>
                                        </div>
                                        <span class="shrink-0 bg-gold/20 text-yellow-800 px-3 py-1 rounded-full text-sm font-bold">{{ $qty }} adet</span>
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
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
