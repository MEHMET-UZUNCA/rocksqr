<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function kitchen(Request $request)
    {
        $range = $request->get('range', '7');  // gün sayısı veya 'all'

        $query = DB::table('kitchen_pos_completions')
            ->whereNotNull('prep_seconds');

        if ($range !== 'all') {
            $days = max(1, min(365, (int) $range));
            $query->where('completed_at', '>=', now()->subDays($days)->startOfDay());
        }

        // Özet istatistikler
        $stats = (clone $query)->selectRaw('
            COUNT(*)                          AS total,
            ROUND(AVG(prep_seconds))          AS avg_seconds,
            MAX(prep_seconds)                 AS max_seconds,
            MIN(prep_seconds)                 AS min_seconds
        ')->first();

        // Bugün özeti
        $today = DB::table('kitchen_pos_completions')
            ->whereNotNull('prep_seconds')
            ->whereDate('completed_at', today())
            ->selectRaw('COUNT(*) AS total, ROUND(AVG(prep_seconds)) AS avg_seconds, MAX(prep_seconds) AS max_seconds')
            ->first();

        // Günlük ortalama (son 30 gün) — grafik için
        $daily = DB::table('kitchen_pos_completions')
            ->whereNotNull('prep_seconds')
            ->where('completed_at', '>=', now()->subDays(30)->startOfDay())
            ->selectRaw('DATE(completed_at) AS day, COUNT(*) AS total, ROUND(AVG(prep_seconds)) AS avg_seconds, MAX(prep_seconds) AS max_seconds')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // En yavaş tamamlanan 50 hesap
        $slowest = (clone $query)
            ->select('group_key', 'check_number', 'table_no', 'kind', 'completed_at', 'first_seen_at', 'prep_seconds')
            ->orderByDesc('prep_seconds')
            ->limit(50)
            ->get();

        // En yavaş hesapların içerikleri (kitchen_item_logs — ürün listesi)
        $slowKeys = $slowest->pluck('group_key')->filter()->unique()->values()->all();
        $slowContents = $slowKeys === [] ? [] : DB::table('kitchen_item_logs')
            ->whereIn('group_key', $slowKeys)
            ->selectRaw('group_key, GROUP_CONCAT(CONCAT(name, IF(qty > 1, CONCAT(" x", qty), "")) ORDER BY id SEPARATOR ", ") AS items_list')
            ->groupBy('group_key')
            ->pluck('items_list', 'group_key')
            ->all();

        // Son 100 tamamlanan (zaman sıralı)
        $recent = (clone $query)
            ->select('group_key', 'check_number', 'table_no', 'kind', 'completed_at', 'first_seen_at', 'prep_seconds')
            ->orderByDesc('completed_at')
            ->limit(100)
            ->get();

        return view('admin.reports.kitchen', compact('stats', 'today', 'daily', 'slowest', 'slowContents', 'recent', 'range'));
    }

    public function durations(Request $request)
    {
        $range = $request->get('range', '7');

        $applyRange = function ($query, string $column) use ($range) {
            if ($range !== 'all') {
                $days = max(1, min(365, (int) $range));
                $query->where($column, '>=', now()->subDays($days)->startOfDay());
            }
            return $query;
        };

        $agg = function (array $vals): array {
            $vals = array_values(array_filter($vals, fn ($v) => $v !== null));
            if ($vals === []) {
                return ['count' => 0, 'avg' => null, 'max' => null];
            }
            return [
                'count' => count($vals),
                'avg'   => (int) round(array_sum($vals) / count($vals)),
                'max'   => (int) max($vals),
            ];
        };

        // ---- QR siparişleri: created_at → (bar_approved_at) → kitchen_started_at → kitchen_ready_at → completed_at ----
        // bar_approved_at QR akışında dolmayabilir (Onayla butonu kaldırıldı) — opsiyonel aşama.
        // Otomatik ekran temizlemesinde kapatılanlar (auto_closed_at) istatistik dışıdır.
        $qrOrders = $applyRange(
            Order::where('kitchen_status', 'completed')
                ->whereNotNull('completed_at')
                ->whereNull('auto_closed_at'),
            'completed_at'
        )->orderByDesc('completed_at')->limit(100)->get();

        $qrRows = [];
        $qrBarWait = $qrStartWait = $qrPrep = $qrReadyWait = $qrTotal = [];
        foreach ($qrOrders as $o) {
            $barWait   = $o->bar_approved_at ? (int) abs($o->created_at->diffInSeconds($o->bar_approved_at)) : null;
            $startWait = $o->kitchen_started_at ? (int) abs(($o->bar_approved_at ?? $o->created_at)->diffInSeconds($o->kitchen_started_at)) : null;
            $prep      = $o->kitchen_started_at && $o->kitchen_ready_at ? (int) abs($o->kitchen_started_at->diffInSeconds($o->kitchen_ready_at)) : null;
            $readyWait = $o->kitchen_ready_at ? (int) abs($o->kitchen_ready_at->diffInSeconds($o->completed_at)) : null;
            $total     = (int) abs($o->created_at->diffInSeconds($o->completed_at));

            $qrRows[] = [
                'table_no'  => $o->table_no,
                'room_no'   => $o->room_no,
                'created'   => $o->created_at,
                'approved'  => $o->bar_approved_at,
                'started'   => $o->kitchen_started_at,
                'ready'     => $o->kitchen_ready_at,
                'completed' => $o->completed_at,
                'bar_wait'   => $barWait,
                'start_wait' => $startWait,
                'prep'       => $prep,
                'ready_wait' => $readyWait,
                'total'      => $total,
            ];

            $qrBarWait[]   = $barWait;
            $qrStartWait[] = $startWait;
            $qrPrep[]      = $prep;
            $qrReadyWait[] = $readyWait;
            $qrTotal[]     = $total;
        }

        // ---- Symphony: first_seen_at → completed_at (hazırlık) → delivered_at (bar bekleme) ----
        // Otomatik ekran temizlemesinde teslim işaretlenenler istatistik dışıdır.
        $symQuery = DB::table('kitchen_pos_completions')
            ->whereNotNull('delivered_at')
            ->whereNotNull('prep_seconds')
            ->whereNull('auto_closed_at');
        $applyRange($symQuery, 'completed_at');

        $symOrders = (clone $symQuery)->orderByDesc('delivered_at')->limit(100)->get();

        $symRows = [];
        $symBarWait = [];
        foreach ($symOrders as $row) {
            $completed = \Carbon\Carbon::parse($row->completed_at);
            $delivered = \Carbon\Carbon::parse($row->delivered_at);
            $barWait   = (int) abs($completed->diffInSeconds($delivered));

            $symRows[] = [
                'check_number' => $row->check_number,
                'group_key'    => $row->group_key,
                'table_no'     => $row->table_no,
                'kind'         => $row->kind,
                'completed'    => $completed,
                'delivered'    => $delivered,
                'prep'         => (int) $row->prep_seconds,
                'bar_wait'     => $barWait,
            ];
            $symBarWait[] = $barWait;
        }

        // ---- Günlük ortalama teslim süresi (QR, son 30 gün) ----
        $daily = $applyRange(
            Order::where('kitchen_status', 'completed')
                ->whereNotNull('completed_at')
                ->whereNull('auto_closed_at'),
            'completed_at'
        )->selectRaw('DATE(completed_at) AS day, COUNT(*) AS total, ROUND(AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at))) AS avg_seconds')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // ---- Ürüne göre hazırlık (kitchen_item_logs — mutfak onay geçmişi) ----
        $itemQuery = DB::table('kitchen_item_logs');
        $applyRange($itemQuery, 'completed_at');

        $itemByProduct = (clone $itemQuery)
            ->selectRaw('name, COUNT(*) AS confirmations, SUM(qty) AS pieces, ROUND(AVG(prep_seconds)) AS avg_seconds, MAX(prep_seconds) AS max_seconds')
            ->groupBy('name')
            ->orderByDesc('confirmations')
            ->limit(30)
            ->get();

        // ---- En geç hazırlanan ürünler (ortalama süreye göre) ----
        $slowProducts = (clone $itemQuery)
            ->whereNotNull('prep_seconds')
            ->selectRaw('name, COUNT(*) AS confirmations, SUM(qty) AS pieces, ROUND(AVG(prep_seconds)) AS avg_seconds, MAX(prep_seconds) AS max_seconds')
            ->groupBy('name')
            ->orderByDesc('avg_seconds')
            ->orderByDesc('max_seconds')
            ->limit(20)
            ->get();

        // En uzun süren kayıt referansı (ürün adına göre tek satır)
        $slowRefs = [];
        if ($slowProducts->isNotEmpty()) {
            $refRows = (clone $itemQuery)
                ->whereIn('name', $slowProducts->pluck('name')->all())
                ->whereNotNull('prep_seconds')
                ->orderByDesc('prep_seconds')
                ->get(['name', 'group_key', 'check_number', 'table_no', 'room_no', 'prep_seconds', 'completed_at']);
            foreach ($refRows as $r) {
                $slowRefs[$r->name] ??= $r;
            }
        }

        // ---- Masaya göre kırılım ----
        $symByTable = (clone $itemQuery)
            ->whereNotNull('table_no')
            ->selectRaw("table_no, COUNT(*) AS total, ROUND(AVG(prep_seconds)) AS avg_prep,
                ROUND(AVG(CASE WHEN delivered_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, completed_at, delivered_at) END)) AS avg_bar_wait")
            ->groupBy('table_no')
            ->orderByDesc('total')
            ->get();

        $qrByLoc = $applyRange(
            Order::where('kitchen_status', 'completed')
                ->whereNotNull('completed_at')
                ->whereNull('auto_closed_at'),
            'completed_at'
        )->selectRaw("CONCAT('M:', IFNULL(table_no, CONCAT('O:', room_no))) AS loc_key, COUNT(*) AS total, ROUND(AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at))) AS avg_total")
            ->groupBy('loc_key')
            ->get();

        $byLoc = [];
        foreach ($symByTable as $r) {
            $byLoc['M:' . $r->table_no] = [
                'label'        => 'Masa ' . $r->table_no,
                'sym_n'        => (int) $r->total,
                'sym_prep'     => $r->avg_prep !== null ? (int) $r->avg_prep : null,
                'sym_bar_wait' => $r->avg_bar_wait !== null ? (int) $r->avg_bar_wait : null,
                'qr_n'         => 0,
                'qr_total'     => null,
            ];
        }
        foreach ($qrByLoc as $r) {
            $key = $r->loc_key;
            if ($key === null || $key === '') {
                continue;
            }
            if (!isset($byLoc[$key])) {
                $byLoc[$key] = [
                    'label'        => str_starts_with($key, 'O:') ? 'Oda ' . substr($key, 2) : 'Masa ' . substr($key, 2),
                    'sym_n'        => 0,
                    'sym_prep'     => null,
                    'sym_bar_wait' => null,
                    'qr_n'         => 0,
                    'qr_total'     => null,
                ];
            }
            $byLoc[$key]['qr_n']     = (int) $r->total;
            $byLoc[$key]['qr_total'] = $r->avg_total !== null ? (int) $r->avg_total : null;
        }
        usort($byLoc, fn ($a, $b) => ($b['sym_n'] + $b['qr_n']) <=> ($a['sym_n'] + $a['qr_n']));

        // "Tamamlanmadan kapananlar" — otomatik ekran temizlemesinde kapatılanlar (istatistik dışı)
        $autoClosedQr  = (int) $applyRange(Order::whereNotNull('auto_closed_at'), 'completed_at')->count();
        $autoClosedSym = (int) $applyRange(DB::table('kitchen_pos_completions')->whereNotNull('auto_closed_at'), 'completed_at')->count();

        return view('admin.reports.durations', [
            'range'         => $range,
            'qrRows'        => $qrRows,
            'qrBarWait'     => $agg($qrBarWait),
            'qrStartWait'   => $agg($qrStartWait),
            'qrPrep'        => $agg($qrPrep),
            'qrReadyWait'   => $agg($qrReadyWait),
            'qrTotal'       => $agg($qrTotal),
            'symRows'       => $symRows,
            'symPrep'       => (clone $symQuery)->selectRaw('COUNT(*) AS total, ROUND(AVG(prep_seconds)) AS avg_seconds, MAX(prep_seconds) AS max_seconds')->first(),
            'symBarWait'    => $agg($symBarWait),
            'daily'         => $daily,
            'itemByProduct' => $itemByProduct,
            'slowProducts'  => $slowProducts,
            'slowRefs'      => $slowRefs,
            'byLoc'         => $byLoc,
            'autoClosedQr'  => $autoClosedQr,
            'autoClosedSym' => $autoClosedSym,
        ]);
    }

    public function sales(Request $request)
    {
        $range = $request->get('range', '30');

        $applyRange = function ($query, string $column) use ($range) {
            if ($range !== 'all') {
                $days = max(1, min(365, (int) $range));
                $query->where($column, '>=', now()->subDays($days)->startOfDay());
            }
            return $query;
        };

        // İptal edilenler ciro/sipariş istatistiklerine girmez
        $orders = fn () => $applyRange(Order::where('status', '!=', 'cancelled'), 'created_at');

        // Dönem özeti
        $summary = $orders()
            ->selectRaw('COUNT(*) AS total_orders, COALESCE(SUM(total_price), 0) AS revenue, COALESCE(ROUND(AVG(total_price)), 0) AS avg_basket')
            ->first();
        $cancelled = (int) $applyRange(Order::where('status', 'cancelled'), 'created_at')->count();

        // Günlük ciro (grafik)
        $daily = $orders()
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS total, SUM(total_price) AS revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Saatlik yoğunluk (dönem geneli)
        $hourly = $orders()
            ->selectRaw('HOUR(created_at) AS hour, COUNT(*) AS total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Ürün bazlı adet + ciro (items_json — sipariş anındaki fiyat anlık görüntüsü)
        $productAgg = [];
        foreach ($orders()->orderBy('created_at')->pluck('items_json') as $itemsJson) {
            $items = is_string($itemsJson) ? json_decode($itemsJson, true) : $itemsJson;
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                $name = trim((string) ($item['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $qty   = max(1, (int) ($item['quantity'] ?? 1));
                $price = (float) ($item['price'] ?? 0);
                if (!isset($productAgg[$name])) {
                    $productAgg[$name] = ['name' => $name, 'qty' => 0, 'revenue' => 0.0];
                }
                $productAgg[$name]['qty']     += $qty;
                $productAgg[$name]['revenue'] += $price * $qty;
            }
        }
        $topProducts = collect($productAgg)->sortByDesc('qty')->take(30)->values();

        // Konum bazlı ciro (masa / oda)
        $locRows = $orders()
            ->selectRaw("CONCAT('M:', IFNULL(table_no, CONCAT('O:', room_no))) AS loc_key, COUNT(*) AS total, SUM(total_price) AS revenue")
            ->groupBy('loc_key')
            ->get();
        $byLoc = [];
        foreach ($locRows as $r) {
            $key = (string) $r->loc_key;
            if (str_starts_with($key, 'O:')) {
                $room = substr($key, 2);
                if ($room === '') {
                    continue;
                }
                $label = 'Oda ' . $room;
            } elseif ($key === 'M:') {
                continue;
            } else {
                $label = 'Masa ' . substr($key, 2);
            }
            $byLoc[] = [
                'label'   => $label,
                'total'   => (int) $r->total,
                'revenue' => (float) $r->revenue,
            ];
        }
        usort($byLoc, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        // Garson çağrıları
        $callSummary = $applyRange(\App\Models\WaiterCall::query(), 'created_at')
            ->selectRaw('COUNT(*) AS total, ROUND(AVG(CASE WHEN attended_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, created_at, attended_at) END)) AS avg_wait')
            ->first();
        $topCallTables = $applyRange(\App\Models\WaiterCall::query(), 'created_at')
            ->whereNotNull('table_no')
            ->selectRaw('table_no, COUNT(*) AS call_count')
            ->groupBy('table_no')
            ->orderByDesc('call_count')
            ->limit(10)
            ->get();

        return view('admin.reports.sales', [
            'range'         => $range,
            'summary'       => $summary,
            'cancelled'     => $cancelled,
            'daily'         => $daily,
            'hourly'        => $hourly,
            'topProducts'   => $topProducts,
            'byLoc'         => $byLoc,
            'callSummary'   => $callSummary,
            'topCallTables' => $topCallTables,
        ]);
    }
}
