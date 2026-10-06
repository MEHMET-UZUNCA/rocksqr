<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Clock
{
    public static function source(): string
    {
        $v = Setting::get('screen_clock_source', 'server');
        return in_array($v, ['server', 'database', 'browser'], true) ? $v : 'server';
    }

    public static function dbNowIso(): ?string
    {
        try {
            $row = DB::selectOne('select UTC_TIMESTAMP() as t');
            return ($row && !empty($row->t)) ? Carbon::parse($row->t)->toIso8601String() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function nowIso(): string
    {
        if (self::source() === 'database') {
            return self::dbNowIso() ?? now()->toIso8601String();
        }
        return now()->toIso8601String();
    }
}
