<?php

namespace App\Http\Controllers;

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
}
