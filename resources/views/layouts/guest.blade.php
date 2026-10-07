<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        @php
            $screenBg        = \App\Models\Setting::get('screen_bg_image_login', '');
            $screenBgOpacity = (int) \App\Models\Setting::get('screen_bg_opacity_login', 30);
            $screenBgSize    = (int) \App\Models\Setting::get('screen_bg_size_login', 60);
        @endphp
        @if($screenBg)
        <div style="position:fixed;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;user-select:none">
            <img src="{{ asset('images/' . $screenBg) }}?v={{ @filemtime(public_path('images/' . $screenBg)) }}" alt=""
                 style="max-height:{{ $screenBgSize }}vh;max-width:{{ $screenBgSize }}vw;object-fit:contain;opacity:{{ round($screenBgOpacity / 100, 2) }};filter:brightness(0.45)">
        </div>
        @endif
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg relative">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
