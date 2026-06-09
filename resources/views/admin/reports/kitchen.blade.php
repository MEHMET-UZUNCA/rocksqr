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
function prepBadge(?int $s): string {
    if ($s === null) return '<span class="text-gray-400">—</span>';
    $cls = $s > 900 ? 'bg-red-100 text-red-800' : ($s > 480 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800');
    return '<span class="px-2 py-0.5 rounded-full text-xs font-bold ' . $cls . '">' . fmtSecs($s) . '</span>';
}
@endphp

<div class="py-10">
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

    {{-- Başlık + filtre --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900"><i class="fas fa-chart-line mr-2 text-amber-500"></i>Mutfak Hazırlık Raporu</h2>
            <p class="text-sm text-gray-500 mt-0.5">Symphony POS — Kitchen Pos tamamlama süreleri</p>
        </div>
        <form method="GET" action="{{ route('admin.reports.kitchen') }}" class="flex items-center gap-2">
            <label class="text-sm text-gray-600 font-medium">Dönem:</label>
            <select name="range" onchange="this.form.submit()"
                    class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                @foreach(['1'=>'Bugün','7'=>'Son 7 gün','30'=>'Son 30 gün','90'=>'Son 90 gün','all'=>'Tüm zamanlar'] as $val=>$label)
                    <option value="{{ $val }}" {{ $range == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Özet kartlar --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @php
        $cards = [
            ['label' => 'Bugün Tamamlanan', 'value' => $today->total ?? 0, 'icon' => 'fa-check-circle', 'color' => 'text-emerald-600'],
            ['label' => 'Bugün Ort. Süre',   'value' => fmtSecs((int)($today->avg_seconds ?? 0) ?: null), 'icon' => 'fa-stopwatch', 'color' => 'text-blue-600'],
            ['label' => "Dönem Tamamlanan",  'value' => $stats->total ?? 0, 'icon' => 'fa-receipt', 'color' => 'text-purple-600'],
            ['label' => 'Dönem Ort. Süre',   'value' => fmtSecs((int)($stats->avg_seconds ?? 0) ?: null), 'icon' => 'fa-clock', 'color' => 'text-amber-600'],
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

    {{-- İki kolon: günlük grafik + bugün en kötü --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Günlük ort. hazırlık süresi (bar chart) --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-bar-chart mr-2 text-amber-500"></i>Son 30 Gün — Günlük Ortalama</h3>
            @if($daily->isEmpty())
                <p class="text-gray-400 text-sm text-center py-8">Henüz veri yok.</p>
            @else
                @php $maxAvg = $daily->max('avg_seconds') ?: 1; @endphp
                <div class="space-y-1.5 max-h-72 overflow-y-auto pr-1">
                    @foreach($daily as $d)
                        @php
                            $pct = min(100, round($d->avg_seconds / $maxAvg * 100));
                            $barColor = $d->avg_seconds > 900 ? 'bg-red-400' : ($d->avg_seconds > 480 ? 'bg-yellow-400' : 'bg-emerald-400');
                        @endphp
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-20 text-gray-500 flex-shrink-0">{{ \Carbon\Carbon::parse($d->day)->format('d M') }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-4 relative">
                                <div class="{{ $barColor }} h-4 rounded-full transition-all" style="width:{{ $pct }}%"></div>
                            </div>
                            <span class="w-20 text-right font-semibold text-gray-700">{{ fmtSecs((int)$d->avg_seconds) }}</span>
                            <span class="w-10 text-right text-gray-400">{{ $d->total }}x</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Bugünkü en geç hesaplar --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-triangle-exclamation mr-2 text-red-500"></i>Bugün En Uzun Süren (İlk 10)</h3>
            @php
                $todaySlowest = \Illuminate\Support\Facades\DB::table('kitchen_pos_completions')
                    ->whereNotNull('prep_seconds')
                    ->whereDate('completed_at', today())
                    ->orderByDesc('prep_seconds')
                    ->limit(10)
                    ->get();
            @endphp
            @if($todaySlowest->isEmpty())
                <p class="text-gray-400 text-sm text-center py-8">Bugün henüz veri yok.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-gray-500 border-b">
                            <th class="py-1 text-left">Hesap</th>
                            <th class="py-1 text-left">Masa</th>
                            <th class="py-1 text-right">Süre</th>
                            <th class="py-1 text-right">Tamamlandı</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                        @foreach($todaySlowest as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="py-1.5 font-mono text-xs text-sky-700">{{ $row->check_number ? 'Chk #'.$row->check_number : $row->group_key }}</td>
                                <td class="py-1.5 text-gray-700">{{ $row->table_no ? 'Masa '.$row->table_no : '—' }}</td>
                                <td class="py-1.5 text-right">{!! prepBadge($row->prep_seconds) !!}</td>
                                <td class="py-1.5 text-right text-gray-500 text-xs">{{ \Carbon\Carbon::parse($row->completed_at)->format('H:i:s') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- En yavaş tamamlanan (tüm dönem) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <h3 class="font-bold text-gray-800 mb-4">
            <i class="fas fa-ranking-star mr-2 text-red-500"></i>En Yavaş Tamamlanan 50 Hesap
            <span class="text-xs font-normal text-gray-400 ml-2">({{ $range === 'all' ? 'tüm zamanlar' : 'son '.$range.' gün' }})</span>
        </h3>
        @if($slowest->isEmpty())
            <p class="text-gray-400 text-sm text-center py-6">Bu dönemde hazırlık süresi kaydedilmiş tamamlama yok.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr class="text-xs text-gray-500 uppercase">
                            <th class="px-3 py-2 text-center w-8">#</th>
                            <th class="px-3 py-2 text-left">Hesap No</th>
                            <th class="px-3 py-2 text-left">Masa</th>
                            <th class="px-3 py-2 text-left">Tür</th>
                            <th class="px-3 py-2 text-right">Hazırlık Süresi</th>
                            <th class="px-3 py-2 text-right">İlk Görüldü</th>
                            <th class="px-3 py-2 text-right">Tamamlandı</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @foreach($slowest as $i => $row)
                        <tr class="hover:bg-gray-50 {{ $row->prep_seconds > 900 ? 'bg-red-50/40' : ($row->prep_seconds > 480 ? 'bg-yellow-50/40' : '') }}">
                            <td class="px-3 py-2 text-center text-xs text-gray-400 font-mono">{{ $i + 1 }}</td>
                            <td class="px-3 py-2 font-mono text-xs text-sky-700">{{ $row->check_number ? 'Chk #'.$row->check_number : $row->group_key }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $row->table_no ? 'Masa '.$row->table_no : '—' }}</td>
                            <td class="px-3 py-2">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $row->kind === 'check' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ $row->kind === 'check' ? 'Hesap' : 'Mesaj' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right">{!! prepBadge($row->prep_seconds) !!}</td>
                            <td class="px-3 py-2 text-right text-xs text-gray-500">
                                {{ $row->first_seen_at ? \Carbon\Carbon::parse($row->first_seen_at)->format('d.m H:i') : '—' }}
                            </td>
                            <td class="px-3 py-2 text-right text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($row->completed_at)->format('d.m H:i') }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Son tamamlananlar --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <h3 class="font-bold text-gray-800 mb-4">
            <i class="fas fa-clock-rotate-left mr-2 text-gray-500"></i>Son 100 Tamamlanan
        </h3>
        @if($recent->isEmpty())
            <p class="text-gray-400 text-sm text-center py-6">Kayıt yok.</p>
        @else
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b sticky top-0">
                        <tr class="text-xs text-gray-500 uppercase">
                            <th class="px-3 py-2 text-left">Hesap No</th>
                            <th class="px-3 py-2 text-left">Masa</th>
                            <th class="px-3 py-2 text-right">Hazırlık Süresi</th>
                            <th class="px-3 py-2 text-right">Tamamlandı</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @foreach($recent as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-1.5 font-mono text-xs text-sky-700">{{ $row->check_number ? 'Chk #'.$row->check_number : $row->group_key }}</td>
                            <td class="px-3 py-1.5 text-gray-700 text-xs">{{ $row->table_no ? 'Masa '.$row->table_no : '—' }}</td>
                            <td class="px-3 py-1.5 text-right">{!! prepBadge($row->prep_seconds) !!}</td>
                            <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ \Carbon\Carbon::parse($row->completed_at)->format('d.m.Y H:i') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
</div>
@endsection
