<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Setting;
use App\Models\WaiterCall;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ScreenCleaner
{
    // SON serit kesme noktasi: bu andan once tamamlananlar ekran gecmisinde gosterilmez
    public static function clearedAt(): ?Carbon
    {
        $value = (string) Setting::get('screen_cleared_at', '');
        if ($value === '') {
            return null;
        }
        try {
            $clearedAt = Carbon::parse($value);
            return $clearedAt->isPast() ? $clearedAt : null;
        } catch (\Throwable) {
            return null;
        }
    }

    // Ayarlanan saatte (screen_clear_time) ekranlari temizler — kayit silmez, sadece durum isaretler.
    // Bar/mutfak ekran anketleri tetikler; gunde bir kez calisir. Ayrica cron/komut ile de calisabilir.
    public static function clearIfDue(): bool
    {
        try {
            // Kayit damgalari uygulama saatiyle (UTC); admin'in girdigi saat TR duvar saatiyle karsilastirilir
            $now       = Carbon::now();
            $localNow  = Carbon::now('Europe/Istanbul');
            $clearTime = (string) Setting::get('screen_clear_time', '14:00');
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $clearTime)) {
                return false;
            }

            $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $localNow->format('Y-m-d') . ' ' . $clearTime, 'Europe/Istanbul');
            if ($now->lt($scheduledAt)) {
                return false;
            }
            if (Setting::get('screen_clear_last_run_date', '') === $localNow->toDateString()) {
                return false;
            }

            // 1) Ekranlarda bekleyen QR siparisleri "tamamlanmadan kapatildi" olarak kapat
            //    (waiting dahil: bar onayi bekleyen GELEN siparisleri de kapsar)
            Order::whereIn('kitchen_status', ['waiting', 'new', 'preparing', 'ready'])
                ->update([
                    'status'         => 'completed',
                    'bar_status'     => 'approved',
                    'kitchen_status' => 'completed',
                    'completed_at'   => $now,
                    'auto_closed_at' => $now,
                ]);

            // 2) Bar "servise gotur" seridindeki Symphony onaylari teslim edilmis isaretle
            DB::table('kitchen_pos_completions')
                ->whereNull('delivered_at')
                ->update([
                    'delivered_at'   => $now,
                    'auto_closed_at' => $now,
                ]);

            // 3) Yanitsiz garson cagrilarini yanitlandi isaretle
            WaiterCall::where('status', 'pending')
                ->update([
                    'status'      => 'attended',
                    'attended_at' => $now,
                ]);

            Setting::set('screen_clear_last_run_date', $now->toDateString());
            // Kesme noktasi en son yazilir: az once kapatilanlar SON seritlerine dusmez
            Setting::set('screen_cleared_at', Carbon::now()->format('Y-m-d H:i:s'));
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
