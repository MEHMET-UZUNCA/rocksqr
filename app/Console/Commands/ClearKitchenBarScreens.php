<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;

class ClearKitchenBarScreens extends Command
{
    protected $signature = 'screens:clear';
    protected $description = 'Mutfak ve bar ekranlarını temizler (siparişleri tamamlanmış yapar)';

    public function handle()
    {
        $cleared = Order::whereIn('status', ['new', 'preparing', 'ready'])
            ->update(['status' => 'completed', 'completed_at' => now()]);
        $this->info("$cleared sipariş temizlendi.");
        return 0;
    }
}
