@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
@php
    $todayStr = now()->toDateString();
    $ordersToday = \App\Models\Order::whereDate('created_at', $todayStr)->where('status', '!=', 'cancelled');
    $todayOrderCount = (clone $ordersToday)->count();
    $todaySales = (clone $ordersToday)->sum('total_price');
    $monthSales = \App\Models\Order::whereMonth('created_at', now()->month)
        ->whereYear('created_at', now()->year)
        ->where('status', '!=', 'cancelled')
        ->sum('total_price');
    $pendingCalls = \App\Models\WaiterCall::where('status', 'pending')->count();
    $newOrders = \App\Models\Order::where('status', 'new')->count();
    $todayQrDone = \App\Models\Order::whereDate('kitchen_ready_at', $todayStr)
        ->where('status', '!=', 'cancelled')
        ->whereNull('auto_closed_at')
        ->count();
    $todaySymDone = \Illuminate\Support\Facades\DB::table('kitchen_pos_completions')
        ->whereDate('completed_at', $todayStr)
        ->where('kind', '!=', 'item')
        ->whereNull('auto_closed_at')
        ->count();
    $todayDone = $todayQrDone + $todaySymDone;
    $todayAvg = \Illuminate\Support\Facades\DB::table('kitchen_item_logs')
        ->whereDate('completed_at', $todayStr)
        ->whereNotNull('prep_seconds')
        ->avg('prep_seconds');
    if ($todayAvg === null) {
        $avgPrepText = '—';
    } else {
        $s = (int) round($todayAvg);
        $avgPrepText = $s >= 3600
            ? intdiv($s, 3600) . ' sa ' . sprintf('%02d', intdiv($s % 3600, 60)) . ' dk'
            : ($s >= 60 ? intdiv($s, 60) . ' dk ' . sprintf('%02d', $s % 60) . ' sn' : $s . ' sn');
    }
    $totalProducts = \App\Models\Product::count();

    // Son 14 gün: sipariş + ciro trendi (grafik)
    $trendRows = \App\Models\Order::where('created_at', '>=', now()->subDays(13)->startOfDay())
        ->where('status', '!=', 'cancelled')
        ->selectRaw('DATE(created_at) AS day, COUNT(*) AS total, SUM(total_price) AS revenue')
        ->groupBy('day')
        ->get()
        ->keyBy(fn ($r) => \Carbon\Carbon::parse($r->day)->toDateString());
    $trendLabels = [];
    $trendCounts = [];
    $trendRevenue = [];
    for ($i = 13; $i >= 0; $i--) {
        $d = now()->subDays($i);
        $row = $trendRows[$d->toDateString()] ?? null;
        $trendLabels[] = $d->format('d.m');
        $trendCounts[] = $row ? (int) $row->total : 0;
        $trendRevenue[] = $row ? round((float) $row->revenue) : 0;
    }

    // Saatlik yoğunluk: son 30 gün (grafik)
    $hourlyRows = \App\Models\Order::where('created_at', '>=', now()->subDays(30)->startOfDay())
        ->where('status', '!=', 'cancelled')
        ->selectRaw('HOUR(created_at) AS hour, COUNT(*) AS total')
        ->groupBy('hour')
        ->get();
    $hourlyMap = [];
    foreach ($hourlyRows as $r) {
        $hourlyMap[(int) $r->hour] = (int) $r->total;
    }
    $hourLabels = [];
    $hourCounts = [];
    for ($h = 0; $h < 24; $h++) {
        $hourLabels[] = sprintf('%02d', $h);
        $hourCounts[] = $hourlyMap[$h] ?? 0;
    }
@endphp

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- Başlık --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-4">
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="flex items-center justify-center w-10 h-10 rounded-lg bg-gold/15 text-gold"><i class="fas fa-tachometer-alt"></i></span>
                    Admin Dashboard
                </h2>
                <span class="hidden sm:inline-flex items-center gap-2 text-sm text-gray-500">
                    <i class="fas fa-calendar-day text-gray-300"></i> {{ now()->translatedFormat('d F Y, l') }}
                </span>
            </div>

            <div class="p-6 space-y-6">

                {{-- 8 istatistik kartı --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-blue-100">
                            <i class="fas fa-receipt text-blue-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">{{ $todayOrderCount }}</p>
                            <p class="text-xs lg:text-sm text-gray-500">Bugün Sipariş</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-green-100">
                            <i class="fas fa-calendar-day text-green-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">{{ number_format($todaySales, 2) }} ₺</p>
                            <p class="text-xs lg:text-sm text-gray-500">Günlük Satış</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-100">
                            <i class="fas fa-calendar-alt text-indigo-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">{{ number_format($monthSales, 2) }} ₺</p>
                            <p class="text-xs lg:text-sm text-gray-500">Aylık Satış</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-red-100">
                            <i class="fas fa-bell-concierge text-red-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">{{ $pendingCalls }}</p>
                            <p class="text-xs lg:text-sm text-gray-500">Bekleyen Çağrılar</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-amber-100">
                            <i class="fas fa-clock text-amber-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">{{ $newOrders }}</p>
                            <p class="text-xs lg:text-sm text-gray-500">Yeni Siparişler</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-teal-100">
                            <i class="fas fa-check-circle text-teal-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">{{ $todayDone }}</p>
                            <p class="text-xs lg:text-sm text-gray-500">Bugün Tamamlanan</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-emerald-100">
                            <i class="fas fa-stopwatch text-emerald-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">{{ $avgPrepText }}</p>
                            <p class="text-xs lg:text-sm text-gray-500">Bugün Ort. Hazırlık</p>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5 flex items-center gap-4">
                        <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-xl bg-purple-100">
                            <i class="fas fa-box text-purple-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xl lg:text-2xl font-bold text-gray-900">{{ $totalProducts }}</p>
                            <p class="text-xs lg:text-sm text-gray-500">Toplam Ürün</p>
                        </div>
                    </div>
                </div>

                {{-- Grafikler --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl p-5">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-chart-line text-gold"></i> Son 14 Gün
                            </h3>
                            <span class="text-xs text-gray-400">Sipariş adedi ve günlük ciro</span>
                        </div>
                        <div class="h-64">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl p-5">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-chart-simple text-gold"></i> Saatlik Yoğunluk
                            </h3>
                            <span class="text-xs text-gray-400">son 30 gün</span>
                        </div>
                        <div class="h-64">
                            <canvas id="hourChart"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Hızlı erişim + en çok satılanlar --}}
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
                            <a href="{{ route('admin.reports.kitchen') }}" class="flex items-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-light-primary transition text-sm font-semibold">
                                <i class="fas fa-chart-line mr-2 w-4 text-center text-gold"></i> Raporlar
                            </a>
                            <a href="{{ route('admin.profile') }}" class="flex items-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-light-primary transition text-sm font-semibold">
                                <i class="fas fa-id-badge mr-2 w-4 text-center text-gold"></i> Profilim
                            </a>
                            @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.categories.index') }}" class="flex items-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-light-primary transition text-sm font-semibold">
                                <i class="fas fa-folder mr-2 w-4 text-center text-gold"></i> Kategorileri Yönet
                            </a>
                            <a href="{{ route('admin.products.index') }}" class="flex items-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-light-primary transition text-sm font-semibold">
                                <i class="fas fa-box mr-2 w-4 text-center text-gold"></i> Ürünleri Yönet
                            </a>
                            <a href="{{ route('admin.qr-codes.index') }}" class="flex items-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-light-primary transition text-sm font-semibold">
                                <i class="fas fa-qrcode mr-2 w-4 text-center text-gold"></i> Masa QR Oluştur
                            </a>
                            <a href="{{ route('admin.settings') }}" class="flex items-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-light-primary transition text-sm font-semibold">
                                <i class="fas fa-gear mr-2 w-4 text-center text-gold"></i> Ayarlar
                            </a>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-trophy mr-2 text-gold"></i>En Çok Satılan Ürünler
                            <span class="text-xs font-normal text-gray-400 ml-2">(son 30 gün)</span>
                        </h3>
                        @php
                            $productCounts = [];
                            $itemsColumns = \App\Models\Order::query()
                                ->where('created_at', '>=', now()->subDays(30))
                                ->where('status', '!=', 'cancelled')
                                ->pluck('items_json');
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
                            <p class="text-gray-400 text-sm">Son 30 günde sipariş yok.</p>
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

                {{-- Çağrılan masalar + son ayar hareketleri --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-bell mr-2 text-red-500"></i>En Çok Garson Çağrılan Masalar
                            <span class="text-xs font-normal text-gray-400 ml-2">(son 30 gün)</span>
                        </h3>
                        @php
                            $topTables = \App\Models\WaiterCall::whereNotNull('table_no')
                                ->where('created_at', '>=', now()->subDays(30))
                                ->selectRaw('table_no, COUNT(*) as call_count')
                                ->groupBy('table_no')
                                ->orderByDesc('call_count')
                                ->limit(5)
                                ->get();
                        @endphp
                        @if($topTables->isEmpty())
                            <p class="text-gray-400 text-sm">Son 30 günde garson çağrısı yok.</p>
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

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-clock-rotate-left mr-2 text-gold"></i>Son Ayar Hareketleri
                            <span class="text-xs font-normal text-gray-400 ml-2">(son 8 değişiklik)</span>
                        </h3>
                        @php
                            $recentLogs = \Illuminate\Support\Facades\DB::table('settings_audit_logs')
                                ->orderByDesc('created_at')
                                ->orderByDesc('id')
                                ->limit(8)
                                ->get();
                        @endphp
                        @if($recentLogs->isEmpty())
                            <p class="text-gray-400 text-sm">Hareket kaydı yok.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($recentLogs as $log)
                                    <div class="flex items-center justify-between gap-4 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5">
                                        <div class="min-w-0">
                                            <span class="font-mono text-xs font-semibold text-gray-700">{{ $log->key_name }}</span>
                                            <span class="text-xs text-gray-500 ml-2">{{ \Illuminate\Support\Str::limit((string) $log->new_value, 60) }}</span>
                                        </div>
                                        <div class="shrink-0 text-xs text-gray-400">
                                            {{ $log->user_email ?? '—' }} · {{ \Carbon\Carbon::parse($log->created_at)->format('d.m.Y H:i') }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;
    Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif";
    Chart.defaults.color = '#6b7280';
    Chart.defaults.font.size = 11;

    var fmtTr = new Intl.NumberFormat('tr-TR');

    new Chart(document.getElementById('trendChart'), {
        type: 'bar',
        data: {
            labels: @json($trendLabels),
            datasets: [
                {
                    label: 'Sipariş',
                    data: @json($trendCounts),
                    backgroundColor: 'rgba(212, 175, 55, 0.5)',
                    borderColor: '#d4af37',
                    borderWidth: 1,
                    borderRadius: 6,
                    maxBarThickness: 28,
                    yAxisID: 'y'
                },
                {
                    label: 'Ciro (₺)',
                    data: @json($trendRevenue),
                    type: 'line',
                    borderColor: '#1a1a2e',
                    backgroundColor: 'rgba(26, 26, 46, 0.06)',
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return ctx.dataset.label === 'Ciro (₺)'
                                ? 'Ciro: ' + fmtTr.format(ctx.parsed.y) + ' ₺'
                                : 'Sipariş: ' + ctx.parsed.y;
                        }
                    }
                }
            },
            scales: {
                y:  { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f3f4f6' } },
                y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } },
                x:  { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('hourChart'), {
        type: 'bar',
        data: {
            labels: @json($hourLabels),
            datasets: [{
                label: 'Sipariş',
                data: @json($hourCounts),
                backgroundColor: 'rgba(26, 26, 46, 0.8)',
                hoverBackgroundColor: '#d4af37',
                borderRadius: 3,
                categoryPercentage: 0.9,
                barPercentage: 0.9
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (ctx) { return ctx.parsed.x + '.00 - ' + ctx.parsed.x + ':59 · ' + ctx.parsed.y + ' sipariş'; } } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f3f4f6' } },
                x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 12 } }
            }
        }
    });
});
</script>
@endsection
