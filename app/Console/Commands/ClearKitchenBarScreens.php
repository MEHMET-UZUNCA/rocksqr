<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class ClearKitchenBarScreens extends Command
{
    protected $signature = 'screens:clear-if-due';
    protected $description = 'Ayar saatine gore mutfak ve bar ekranlarini cron ile temizler';

    public function handle()
    {
        $now = Carbon::now();
        $clearTime = Setting::get('screen_clear_time', '14:00');
        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $clearTime);
        $lastRunDate = Setting::get('screen_clear_last_run_date', '');

        if ($now->lt($scheduledAt)) {
            $this->info('Temizleme saati henuz gelmedi.');
            return 0;
        }

        if ($lastRunDate === $now->toDateString()) {
            $this->info('Bugun zaten temizleme yapildi.');
            return 0;
        }

        $cleared = Order::whereIn('kitchen_status', ['new', 'preparing', 'ready'])
            ->update([
                'status' => 'completed',
                'bar_status' => 'approved',
                'kitchen_status' => 'completed',
                'completed_at' => now(),
            ]);

        Setting::set('screen_clear_last_run_date', $now->toDateString());
        $this->info("$cleared sipariş temizlendi.");
        return 0;
    }
}
