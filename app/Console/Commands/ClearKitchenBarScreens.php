<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Support\ScreenCleaner;

class ClearKitchenBarScreens extends Command
{
    protected $signature = 'screens:clear-if-due';
    protected $description = 'Ayar saatine gore mutfak ve bar ekranlarini temizler';

    public function handle()
    {
        $ran = [];
        foreach (['bar', 'kpos', 'ana'] as $screen) {
            if (ScreenCleaner::clearIfDue($screen)) {
                $ran[] = $screen;
            }
        }
        $this->info($ran === []
            ? 'Temizleme saati henuz gelmedi veya bugun zaten yapildi.'
            : 'Temizlenen ekranlar: ' . implode(', ', $ran) . '.');
        return 0;
    }
}
