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
        $ran = ScreenCleaner::clearIfDue();
        $this->info($ran ? 'Mutfak ve bar ekranlari temizlendi.' : 'Temizleme saati henuz gelmedi veya bugun zaten yapildi.');
        return 0;
    }
}
