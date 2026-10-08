<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set($key, $value)
    {
        $old = static::where('key', $key)->value('value');
        $setting = static::updateOrCreate(['key' => $key], ['value' => $value]);

        // Admin panelinden yapilan degisiklikler denetim kaydina yazilir
        // (kim / ne zaman / hangi anahtar / eski -> yeni). Oturumsuz baglamlarda
        // (cron, ekran temizligi) kayit tutulmaz; akis asla bozulmaz.
        try {
            if ((string) $old !== (string) $value && auth()->check()) {
                DB::table('settings_audit_logs')->insert([
                    'user_email' => auth()->user()?->email,
                    'key_name'   => (string) $key,
                    'old_value'  => $old,
                    'new_value'  => (string) $value,
                    'created_at' => now(),
                ]);
            }
        } catch (\Throwable) {
        }

        return $setting;
    }
}
