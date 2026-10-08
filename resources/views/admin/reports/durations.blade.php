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
function durBadge(?int $s): string {
    if ($s === null) return '<span class="text-gray-400">—</span>';
    $cls = $s > 900 ? 'bg-red-100 text-red-800' : ($s > 480 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800');
    return '<span class="px-2 py-0.5 rounded-full text-xs font-bold ' . $cls . '">' . fmtSecs($s) . '</span>';
}
function stageRow(string $label, array $agg): string {
    $avg = durBadge($agg['avg']);
    $max = $agg['max'] !== null ? '<span class="text-[11px] text-gray-400 ml-2">en uzun ' . fmtSecs($agg['max']) . '</span>' : '';
    $cnt = $agg['count'] > 0 ? '<span class="text-[11px] text-gray-400 ml-auto whitespace-nowrap">' . $agg['count'] . 'x</span>' : '';
    return '<div class="flex items-center gap-2 py-2 border-b border-gray-100 last:border-0">
        <span class="text-sm text-gray-700 font-medium">' . $label . '</span>' . $max . '
        <span class="ml-auto">' . $avg . '</span>' . $cnt . '</div>';
}
function locLabel($tableNo, $roomNo): string {
    $parts = [];
    if ($tableNo !== null && $tableNo !== '') $parts[] = 'Masa ' . e($tableNo);
    if ($roomNo !== null && $roomNo !== '') $parts[] = 'Oda ' . e($roomNo);
    return $parts ? implode(' · ', $parts) : '—';
}
@endphp

<div class="py-10">
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

    {{-- Başlık + sekmeler + filtre --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900"><i class="fas fa-hourglass-half mr-2 text-amber-500"></i>Süre Raporu</h2>
            <p class="text-sm text-gray-500 mt-0.5">QR siparişler + Symphony POS — aşama aşama süreler ve ortalamalar</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex rounded-lg overflow-hidden border border-gray-200 text-sm">
                <a href="{{ route('admin.reports.kitchen') }}" class="px-3 py-1.5 bg-white text-gray-600 hover:bg-gray-50 transition">Mutfak Hazırlık</a>
                <span class="px-3 py-1.5 bg-amber-100 text-amber-800 font-bold">Süre Raporu</span>
                <a href="{{ route('admin.reports.sales') }}" class="px-3 py-1.5 bg-white text-gray-600 hover:bg-gray-50 transition">Satış Raporu</a>
            </div>
            <form method="GET" action="{{ route('admin.reports.durations') }}" class="flex items-center gap-2">
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
            ['label' => 'Dönem QR Teslimi',      'value' => $qrTotal['count'], 'icon' => 'fa-mobile-screen', 'color' => 'text-purple-600'],
            ['label' => 'QR Ort. Toplam Süre',   'value' => fmtSecs($qrTotal['avg']), 'icon' => 'fa-stopwatch', 'color' => 'text-blue-600'],
            ['label' => 'Dönem Symphony Teslimi', 'value' => $symPrep->total ?? 0, 'icon' => 'fa-receipt', 'color' => 'text-emerald-600'],
            ['label' => 'Symphony Ort. Bar Bekleme', 'value' => fmtSecs($symBarWait['avg']), 'icon' => 'fa-bell-concierge', 'color' => 'text-amber-600'],
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

    {{-- Tamamlanmadan kapananlar --}}
    @if(($autoClosedQr ?? 0) + ($autoClosedSym ?? 0) > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-2.5 text-xs text-amber-800">
        <i class="fas fa-broom mr-1"></i>
        Tamamlanmadan kapanan: <strong>{{ $autoClosedQr }}</strong> QR sipariş, <strong>{{ $autoClosedSym }}</strong> Symphony hesap — otomatik ekran temizlemesinde kapatıldı, toplam/ortalama sürelere dahil edilmez.
    </div>
    @endif

    {{-- Aşama ortalamaları --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-2"><i class="fas fa-mobile-screen mr-2 text-purple-500"></i>QR Sipariş Aşamaları</h3>
            <p class="text-xs text-gray-400 mb-2">Sipariş girişinden bara teslime kadar ortalama süreler</p>
            {!! stageRow('Bar Onay Bekleme', $qrBarWait) !!}
            {!! stageRow('Mutfak Başlama Beklemesi', $qrStartWait) !!}
            {!! stageRow('Mutfak Hazırlık', $qrPrep) !!}
            {!! stageRow('Hazırdan Teslime Bekleme', $qrReadyWait) !!}
            <div class="flex items-center gap-2 pt-3 mt-1 border-t-2 border-gray-200">
                <span class="text-sm font-bold text-gray-900">Toplam (Giriş → Teslim)</span>
                <span class="ml-auto">{!! durBadge($qrTotal['avg']) !!}</span>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-2"><i class="fas fa-receipt mr-2 text-sky-500"></i>Symphony POS Aşamaları</h3>
            <p class="text-xs text-gray-400 mb-2">KDS onayından bara teslime kadar ortalama süreler</p>
            {!! stageRow('Mutfak Hazırlık', ['count' => (int) ($symPrep->total ?? 0), 'avg' => (int) ($symPrep->avg_seconds ?? 0) ?: null, 'max' => (int) ($symPrep->max_seconds ?? 0) ?: null]) !!}
            {!! stageRow('Hazırdan Bara Teslim Bekleme', $symBarWait) !!}
            <div class="mt-3 pt-3 border-t border-gray-100 text-xs text-gray-400">
                <i class="fas fa-circle-info mr-1"></i>Mutfak hazırlığı ilk KDS görülmesinden (first seen) KDS onayına kadar ölçülür.
            </div>
        </div>
    </div>

    {{-- Ürüne göre + Masaya göre --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-2"><i class="fas fa-burger mr-2 text-orange-500"></i>Ürüne Göre Hazırlık</h3>
            <p class="text-xs text-gray-400 mb-3">Hangi ürün mutfakta ne kadar sürüyor — en çok onaylanan 30 ürün</p>
            @if($itemByProduct->isEmpty())
                <p class="text-gray-400 text-sm text-center py-8">Bu dönemde ürün onayı yok. Veriler mutfak ekranındaki ürün onaylarıyla birikir.</p>
            @else
                <div class="overflow-y-auto max-h-80 border border-gray-100 rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b sticky top-0">
                            <tr class="text-xs text-gray-500 uppercase">
                                <th class="px-3 py-2 text-left">Ürün</th>
                                <th class="px-3 py-2 text-right">Adet</th>
                                <th class="px-3 py-2 text-right">Ort. Hazırlık</th>
                                <th class="px-3 py-2 text-right">En Uzun</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @foreach($itemByProduct as $p)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-1.5 text-gray-700 text-xs font-medium">{{ $p->name }}</td>
                                <td class="px-3 py-1.5 text-right text-xs text-gray-500 whitespace-nowrap">{{ (int) $p->pieces }} adet<span class="text-gray-300"> ({{ (int) $p->confirmations }}x)</span></td>
                                <td class="px-3 py-1.5 text-right">{!! durBadge($p->avg_seconds !== null ? (int) $p->avg_seconds : null) !!}</td>
                                <td class="px-3 py-1.5 text-right text-xs text-gray-500 whitespace-nowrap">{{ fmtSecs($p->max_seconds !== null ? (int) $p->max_seconds : null) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 pt-2 border-t border-gray-100 text-xs text-gray-400">
                    <i class="fas fa-circle-info mr-1"></i>Symphony'de ürün bazlı "Hazır" onayları ölçülür; QR ürünleri sipariş onay ortalamasını alır.
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-2"><i class="fas fa-chair mr-2 text-sky-500"></i>Masaya Göre</h3>
            <p class="text-xs text-gray-400 mb-3">Konum bazında mutfak hazırlıkları ve QR teslimleri</p>
            @if(count($byLoc) === 0)
                <p class="text-gray-400 text-sm text-center py-8">Bu dönemde konum verisi yok.</p>
            @else
                <div class="overflow-x-auto max-h-80 overflow-y-auto border border-gray-100 rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b sticky top-0">
                            <tr class="text-xs text-gray-500 uppercase">
                                <th class="px-3 py-2 text-left">Konum</th>
                                <th class="px-3 py-2 text-right" title="Symphony ürün onayı">SYM</th>
                                <th class="px-3 py-2 text-right">Ort. Hazırlık</th>
                                <th class="px-3 py-2 text-right">Ort. Bar Bekleme</th>
                                <th class="px-3 py-2 text-right" title="QR siparişi">QR</th>
                                <th class="px-3 py-2 text-right">Ort. Toplam</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                        @foreach($byLoc as $loc)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-1.5 text-gray-700 text-xs font-medium">{{ $loc['label'] }}</td>
                                <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ $loc['sym_n'] > 0 ? $loc['sym_n'].'x' : '—' }}</td>
                                <td class="px-3 py-1.5 text-right">{!! durBadge($loc['sym_prep']) !!}</td>
                                <td class="px-3 py-1.5 text-right text-xs text-gray-500 whitespace-nowrap">{{ fmtSecs($loc['sym_bar_wait']) }}</td>
                                <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ $loc['qr_n'] > 0 ? $loc['qr_n'].'x' : '—' }}</td>
                                <td class="px-3 py-1.5 text-right text-xs text-gray-500 whitespace-nowrap">{{ fmtSecs($loc['qr_total']) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- En geç hazırlanan ürünler --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <h3 class="font-bold text-gray-800 mb-2"><i class="fas fa-triangle-exclamation mr-2 text-red-500"></i>En Geç Hazırlanan Ürünler</h3>
        <p class="text-xs text-gray-400 mb-3">Ortalama hazırlık süresine göre en yavaş 20 ürün — en uzun kaydın hesabıyla birlikte</p>
        @if($slowProducts->isEmpty())
            <p class="text-gray-400 text-sm text-center py-8">Bu dönemde hazırlık süresi kaydedilmiş ürün yok.</p>
        @else
            <div class="overflow-x-auto max-h-96 overflow-y-auto border border-gray-100 rounded-lg">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b sticky top-0">
                        <tr class="text-xs text-gray-500 uppercase">
                            <th class="px-3 py-2 text-left">Ürün</th>
                            <th class="px-3 py-2 text-right">Onay / Adet</th>
                            <th class="px-3 py-2 text-right">Ort. Hazırlık</th>
                            <th class="px-3 py-2 text-right">En Uzun</th>
                            <th class="px-3 py-2 text-left">En Uzun Kayıt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @foreach($slowProducts as $p)
                        @php $ref = $slowRefs[$p->name] ?? null; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-1.5 text-gray-700 text-xs font-medium">{{ $p->name }}</td>
                            <td class="px-3 py-1.5 text-right text-xs text-gray-500 whitespace-nowrap">{{ (int) $p->confirmations }}x · {{ (int) $p->pieces }} adet</td>
                            <td class="px-3 py-1.5 text-right">{!! durBadge($p->avg_seconds !== null ? (int) $p->avg_seconds : null) !!}</td>
                            <td class="px-3 py-1.5 text-right">{!! durBadge($p->max_seconds !== null ? (int) $p->max_seconds : null) !!}</td>
                            <td class="px-3 py-1.5 text-xs text-gray-500 whitespace-nowrap">
                                @if($ref)
                                    {{ $ref->check_number ? 'Chk #'.$ref->check_number : $ref->group_key }}
                                    @if($ref->table_no) · Masa {{ $ref->table_no }} @endif
                                    @if($ref->room_no) · Oda {{ $ref->room_no }} @endif
                                    <span class="text-gray-400">· {{ \Carbon\Carbon::parse($ref->completed_at)->format('d.m H:i') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 pt-2 border-t border-gray-100 text-xs text-gray-400">
                <i class="fas fa-circle-info mr-1"></i>"En Uzun Kayıt", ürünün tek seferde en uzun hazırlandığı hesabı gösterir — gecikmenin hangi siparişte yaşandığını buradan görebilirsiniz.
            </div>
        @endif
    </div>

    {{-- Günlük ortalama QR teslim süresi --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-bar-chart mr-2 text-amber-500"></i>QR — Günlük Ortalama Teslim Süresi</h3>
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

    {{-- Son QR siparişleri --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <h3 class="font-bold text-gray-800 mb-4">
            <i class="fas fa-clock-rotate-left mr-2 text-gray-500"></i>Son {{ count($qrRows) }} QR Siparişi — Aşama Zaman Damgaları
        </h3>
        @if(count($qrRows) === 0)
            <p class="text-gray-400 text-sm text-center py-6">Bu dönemde teslim edilmiş QR siparişi yok.</p>
        @else
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b sticky top-0">
                        <tr class="text-xs text-gray-500 uppercase">
                            <th class="px-3 py-2 text-left">Konum</th>
                            <th class="px-3 py-2 text-right">Giriş</th>
                            <th class="px-3 py-2 text-right">Bar Onayı</th>
                            <th class="px-3 py-2 text-right">Mutfak Başladı</th>
                            <th class="px-3 py-2 text-right">Hazır</th>
                            <th class="px-3 py-2 text-right">Teslim</th>
                            <th class="px-3 py-2 text-right">Toplam</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @foreach($qrRows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-1.5 text-gray-700 text-xs font-medium">{{ locLabel($row['table_no'], $row['room_no']) }}</td>
                            <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ $row['created']->format('d.m H:i') }}</td>
                            <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ $row['approved'] ? $row['approved']->format('d.m H:i') : '—' }}</td>
                            <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ $row['started'] ? $row['started']->format('d.m H:i') : '—' }}</td>
                            <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ $row['ready'] ? $row['ready']->format('d.m H:i') : '—' }}</td>
                            <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ $row['completed'] ? $row['completed']->format('d.m H:i') : '—' }}</td>
                            <td class="px-3 py-1.5 text-right">{!! durBadge($row['total']) !!}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Son Symphony teslimleri --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
        <h3 class="font-bold text-gray-800 mb-4">
            <i class="fas fa-clock-rotate-left mr-2 text-gray-500"></i>Son {{ count($symRows) }} Symphony Teslimi
        </h3>
        @if(count($symRows) === 0)
            <p class="text-gray-400 text-sm text-center py-6">Bu dönemde teslim edilmiş Symphony hesabı yok.</p>
        @else
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b sticky top-0">
                        <tr class="text-xs text-gray-500 uppercase">
                            <th class="px-3 py-2 text-left">Hesap No</th>
                            <th class="px-3 py-2 text-left">Masa</th>
                            <th class="px-3 py-2 text-right">Hazır (KDS Onayı)</th>
                            <th class="px-3 py-2 text-right">Teslim (Bar Tamam)</th>
                            <th class="px-3 py-2 text-right">Hazırlık</th>
                            <th class="px-3 py-2 text-right">Bar Bekleme</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @foreach($symRows as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-1.5 font-mono text-xs text-sky-700">{{ $row['check_number'] ? 'Chk #'.$row['check_number'] : $row['group_key'] }}</td>
                            <td class="px-3 py-1.5 text-gray-700 text-xs">{{ $row['table_no'] ? 'Masa '.$row['table_no'] : '—' }}</td>
                            <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ $row['completed']->format('d.m H:i') }}</td>
                            <td class="px-3 py-1.5 text-right text-xs text-gray-500">{{ $row['delivered']->format('d.m H:i') }}</td>
                            <td class="px-3 py-1.5 text-right">{!! durBadge($row['prep']) !!}</td>
                            <td class="px-3 py-1.5 text-right">{!! durBadge($row['bar_wait']) !!}</td>
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
