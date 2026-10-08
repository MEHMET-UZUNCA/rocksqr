@extends('layouts.admin')

@section('content')
@php
function fmtSecs(?int $s): string {
    if ($s === null) return '—';
    $h = intdiv($s, 3600);
    $m = intdiv($s % 3600, 60);
    $sec = $s % 60;
    if ($h > 0) return sprintf('%d sa %02d dk', $h, $m);
    if ($m > 0) return sprintf('%d dk %02d sn', $m, $sec);
    return $sec . ' sn';
}
function tl($v): string {
    return number_format((float) $v, 0, ',', '.') . ' ₺';
}
@endphp

<div class="py-10">
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

    {{-- Başlık + sekmeler + filtre --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900"><i class="fas fa-chart-line mr-2 text-amber-500"></i>Satış Raporu</h2>
            <p class="text-sm text-gray-500 mt-0.5">QR menü siparişleri — ciro, yoğunluk ve garson çağrıları</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex rounded-lg overflow-hidden border border-gray-200 text-sm">
                <a href="{{ route('admin.reports.kitchen') }}" class="px-3 py-1.5 bg-white text-gray-600 hover:bg-gray-50 transition">Mutfak Hazırlık</a>
                <a href="{{ route('admin.reports.durations') }}" class="px-3 py-1.5 bg-white text-gray-600 hover:bg-gray-50 transition">Süre Raporu</a>
                <span class="px-3 py-1.5 bg-amber-100 text-amber-800 font-bold">Satış Raporu</span>
            </div>
            <form method="GET" action="{{ route('admin.reports.sales') }}" class="flex items-center gap-2">
                <label class="text-sm text-gray-600 font-medium">Dönem:</label>
                <select name="range" onchange="this.form.submit()"
                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                    @foreach(['1'=>'Bugün','7'=>'Son 7 gün','30'=>'Son 30 gün','90'=>'Son 90 gün','all'=>'Tüm zamanlar'] as $val=>$label)
                        <option value="{{ $val }}" {{ $range == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- Özet kartlar --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @php
        $cards = [
            ['label' => 'Dönem Sipariş', 'value' => (int) ($summary->total_orders ?? 0), 'icon' => 'fa-receipt', 'color' => 'text-blue-600'],
            ['label' => 'Dönem Ciro', 'value' => tl($summary->revenue ?? 0), 'icon' => 'fa-coins', 'color' => 'text-emerald-600'],
            ['label' => 'Ort. Sepet', 'value' => tl($summary->avg_basket ?? 0), 'icon' => 'fa-basket-shopping', 'color' => 'text-purple-600'],
            ['label' => 'İptal Edilen', 'value' => (int) $cancelled, 'icon' => 'fa-ban', 'color' => 'text-red-600'],
        ];
        @endphp
        @foreach($cards as $card)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas {{ $card['icon'] }} {{ $card['color'] }} text-lg"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-500">{{ $card['label'] }}</div>
                    <div class="text-xl font-bold text-gray-900">{{ $card['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Günlük ciro + saatlik yoğunluk --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-bar-chart mr-2 text-emerald-500"></i>Günlük Ciro</h3>
            @if($daily->isEmpty())
                <p class="text-gray-400 text-sm text-center py-8">Bu dönemde sipariş yok.</p>
            @else
                @php $maxRev = $daily->max('revenue') ?: 1; @endphp
                <div class="space-y-1.5 max-h-72 overflow-y-auto pr-1">
                    @foreach($daily as $d)
                        @php $pct = min(100, round($d->revenue / $maxRev * 100)); @endphp
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-20 text-gray-500 flex-shrink-0">{{ \Carbon\Carbon::parse($d->day)->format('d M') }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-4 relative">
                                <div class="bg-emerald-400 h-4 rounded-full transition-all" style="width:{{ $pct }}%"></div>
                            </div>
                            <span class="w-24 text-right font-semibold text-gray-700">{{ tl($d->revenue) }}</span>
                            <span class="w-10 text-right text-gray-400">{{ $d->total }}x</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-clock mr-2 text-sky-500"></i>Saatlik Yoğunluk</h3>
            @if($hourly->isEmpty())
                <p class="text-gray-400 text-sm text-center py-8">Bu dönemde sipariş yok.</p>
            @else
                @php
                    $hourMap = $hourly->pluck('total', 'hour')->all();
                    $maxHour = max(1, (int) ($hourly->max('total') ?? 1));
                @endphp
                <div class="space-y-1 max-h-72 overflow-y-auto pr-1">
                    @for($h = 0; $h < 24; $h++)
                        @php $cnt = (int) ($hourMap[$h] ?? 0); @endphp
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-12 text-gray-500 flex-shrink-0 font-mono">{{ sprintf('%02d:00', $h) }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-3.5 relative">
                                <div class="{{ $cnt > 0 ? 'bg-sky-400' : '' }} h-3.5 rounded-full transition-all" style="width:{{ min(100, round($cnt / $maxHour * 100)) }}%"></div>
                            </div>
                            <span class="w-10 text-right font-semibold {{ $cnt > 0 ? 'text-gray-700' : 'text-gray-300' }}">{{ $cnt }}x</span>
                        </div>
                    @endfor
                </div>
            @endif
        </div>
    </div>

    {{-- En çok satılan ürünler + konum bazlı ciro --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-2"><i class="fas fa-burger mr-2 text-orange-500"></i>En Çok Satılan 30 Ürün</h3>
            <p class="text-xs text-gray-400 mb-3">Sipariş anındaki fiyatlarla adet ve ciro</p>
            @if($topProducts->isEmpty())
                <p class="text-gray-400 text-sm text-center py-8">Bu dönemde sipariş kalemi yok.</p>
            @else
                <div class="overflow-y-auto max-h-80 border border-gray-100 rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b sticky top-0">
                            <tr class="text-xs text-gray-500 uppercase">
                                <th class="px-3 py-2 text-left">Ürün</th>
                                <th class="px-3 py-2 text-right">Adet</th>
                                <th class="px-3 py-2 text-right">Ciro</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @foreach($topProducts as $p)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-1.5 text-gray-700 text-xs font-medium">{{ $p['name'] }}</td>
                                <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ (int) $p['qty'] }}</td>
                                <td class="px-3 py-1.5 text-right text-xs font-semibold text-gray-700 whitespace-nowrap">{{ tl($p['revenue']) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-2"><i class="fas fa-chair mr-2 text-sky-500"></i>Konum Bazlı Ciro</h3>
            <p class="text-xs text-gray-400 mb-3">Masa ve oda bazında sipariş adedi ve ciro</p>
            @if(count($byLoc) === 0)
                <p class="text-gray-400 text-sm text-center py-8">Bu dönemde konum verisi yok.</p>
            @else
                <div class="overflow-x-auto max-h-80 overflow-y-auto border border-gray-100 rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b sticky top-0">
                            <tr class="text-xs text-gray-500 uppercase">
                                <th class="px-3 py-2 text-left">Konum</th>
                                <th class="px-3 py-2 text-right">Sipariş</th>
                                <th class="px-3 py-2 text-right">Ciro</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @foreach($byLoc as $loc)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-1.5 text-gray-700 text-xs font-medium">{{ $loc['label'] }}</td>
                                <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ $loc['total'] }}x</td>
                                <td class="px-3 py-1.5 text-right text-xs font-semibold text-gray-700 whitespace-nowrap">{{ tl($loc['revenue']) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Garson çağrıları --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-bell-concierge mr-2 text-amber-500"></i>Garson Çağrıları</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-3">
                <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
                    <span class="text-sm text-gray-600"><i class="fas fa-bell mr-2 text-amber-500"></i>Toplam Çağrı</span>
                    <span class="text-xl font-bold text-gray-900">{{ (int) ($callSummary->total ?? 0) }}</span>
                </div>
                <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-3">
                    <span class="text-sm text-gray-600"><i class="fas fa-stopwatch mr-2 text-blue-500"></i>Ort. Karşılama Süresi</span>
                    <span class="text-xl font-bold text-gray-900">{{ fmtSecs($callSummary->avg_wait !== null ? (int) $callSummary->avg_wait : null) }}</span>
                </div>
                <p class="text-xs text-gray-400"><i class="fas fa-circle-info mr-1"></i>Karşılama süresi, çağrının oluşturulmasından "Karşılandı" işaretlenmesine kadar geçen zamandır.</p>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-2">En Çok Çağrılan 10 Masa</h4>
                @if($topCallTables->isEmpty())
                    <p class="text-gray-400 text-sm text-center py-6">Bu dönemde garson çağrısı yok.</p>
                @else
                    <div class="overflow-y-auto max-h-64 border border-gray-100 rounded-lg">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b sticky top-0">
                                <tr class="text-xs text-gray-500 uppercase">
                                    <th class="px-3 py-2 text-left">Masa</th>
                                    <th class="px-3 py-2 text-right">Çağrı</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach($topCallTables as $t)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-1.5 text-gray-700 text-xs font-medium">Masa {{ $t->table_no }}</td>
                                    <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ $t->call_count }}x</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>
</div>
@endsection
