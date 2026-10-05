<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureScreenPin;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class ScreenPinController extends Controller
{
    public function show(string $screen)
    {
        $screen = strtolower($screen);

        if (!isset(EnsureScreenPin::SCREENS[$screen])) {
            abort(404);
        }

        // PIN kapalıysa gate gereksiz — doğrudan ekrana geç
        if (Setting::get("screen_pin_{$screen}_enabled", '') !== '1') {
            return redirect()->route(EnsureScreenPin::SCREENS[$screen]['route']);
        }

        return view('screen-pin', [
            'screen'     => $screen,
            'screenMeta' => EnsureScreenPin::SCREENS[$screen],
        ]);
    }

    public function unlock(Request $request, string $screen)
    {
        $screen = strtolower($screen);

        if (!isset(EnsureScreenPin::SCREENS[$screen])) {
            abort(404);
        }

        $request->validate([
            'pin' => 'required|digits_between:4,6',
        ], [
            'pin.required'       => 'PIN girin.',
            'pin.digits_between' => 'PIN 4-6 haneli olmalıdır.',
        ]);

        $hash = Setting::get("screen_pin_{$screen}", '');

        if ($hash === '' || !Hash::check($request->input('pin'), $hash)) {
            return back()->withErrors(['pin' => 'PIN hatalı. Tekrar deneyin.']);
        }

        // 30 gün geçerli, APP_KEY ile şifreli çerez — kiosk tarayıcı her açılışta PIN sormaz
        return redirect()
            ->route(EnsureScreenPin::SCREENS[$screen]['route'])
            ->withCookie(cookie('screen_auth_' . $screen, Crypt::encryptString($screen), 60 * 24 * 30));
    }
}
