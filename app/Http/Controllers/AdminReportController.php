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

        // Son 100 tamamlanan (zaman sıralı)
        $recent = (clone $query)
            ->select('group_key', 'check_number', 'table_no', 'kind', 'completed_at', 'first_seen_at', 'prep_seconds')
            ->orderByDesc('completed_at')
            ->limit(100)
            ->get();

        return view('admin.reports.kitchen', compact('stats', 'today', 'daily', 'slowest', 'recent', 'range'));
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

        // ---- QR siparişleri: created_at → bar_approved_at → kitchen_started_at → kitchen_ready_at → completed_at ----
        $qrOrders = $applyRange(
            Order::where('kitchen_status', 'completed')
                ->whereNotNull('completed_at')
                ->whereNotNull('bar_approved_at'),
            'completed_at'
        )->orderByDesc('completed_at')->limit(100)->get();

        $qrRows = [];
        $qrBarWait = $qrStartWait = $qrPrep = $qrReadyWait = $qrTotal = [];
        foreach ($qrOrders as $o) {
            $barWait   = (int) abs($o->created_at->diffInSeconds($o->bar_approved_at));
            $startWait = $o->kitchen_started_at ? (int) abs($o->bar_approved_at->diffInSeconds($o->kitchen_started_at)) : null;
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
        $symQuery = DB::table('kitchen_pos_completions')
            ->whereNotNull('delivered_at')
            ->whereNotNull('prep_seconds');
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
                ->whereNotNull('bar_approved_at'),
            'completed_at'
        )->selectRaw('DATE(completed_at) AS day, COUNT(*) AS total, ROUND(AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at))) AS avg_seconds')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('admin.reports.durations', [
            'range'       => $range,
            'qrRows'      => $qrRows,
            'qrBarWait'   => $agg($qrBarWait),
            'qrStartWait' => $agg($qrStartWait),
            'qrPrep'      => $agg($qrPrep),
            'qrReadyWait' => $agg($qrReadyWait),
            'qrTotal'     => $agg($qrTotal),
            'symRows'     => $symRows,
            'symPrep'     => (clone $symQuery)->selectRaw('COUNT(*) AS total, ROUND(AVG(prep_seconds)) AS avg_seconds, MAX(prep_seconds) AS max_seconds')->first(),
            'symBarWait'  => $agg($symBarWait),
            'daily'       => $daily,
        ]);
    }
}
