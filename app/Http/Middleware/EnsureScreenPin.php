<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class EnsureScreenPin
{
    /**
     * slug => [label, route]
     */
    public const SCREENS = [
        'bar'  => ['label' => 'Bar Ekranı (BDS)',      'route' => 'bar'],
        'kpos' => ['label' => 'Kitchen POS (Symphony)', 'route' => 'kitchen.pos'],
        'ana'  => ['label' => 'Ana Mutfak (AKDS)',      'route' => 'kitchen.ana'],
    ];

    public function handle(Request $request, Closure $next, string $screen): Response
    {
        $screen = strtolower($screen);

        if (!isset(self::SCREENS[$screen])) {
            abort(404);
        }

        if (Setting::get("screen_pin_{$screen}_enabled", '') !== '1') {
            return $next($request);
        }

        $cookie = $request->cookie('screen_auth_' . $screen);

        if (is_string($cookie) && $cookie !== '') {
            try {
                if (Crypt::decryptString($cookie) === $screen) {
                    return $next($request);
                }
            } catch (\Throwable) {
                // Bozuk/süresi geçmiş çerez → PIN ekranına düş
            }
        }

        return redirect()->route('screen.pin', ['screen' => $screen]);
    }
}
