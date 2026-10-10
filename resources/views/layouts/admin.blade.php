<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', \App\Models\Setting::get('site_title', 'Admin Panel - Rocks QR Menü'))</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a1a2e',
                        gold: '#d4af37',
                        'light-primary': '#2a2a4e',
                    },
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-poppins">
    @php
        $screenBg        = \App\Models\Setting::get('screen_bg_image_admin', '');
        $screenBgOpacity = (int) \App\Models\Setting::get('screen_bg_opacity_admin', 30);
        $screenBgSize    = (int) \App\Models\Setting::get('screen_bg_size_admin', 60);
    @endphp
    @if($screenBg)
    <div style="position:fixed;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;user-select:none">
        <img src="{{ asset('images/' . $screenBg) }}?v={{ @filemtime(public_path('images/' . $screenBg)) }}" alt=""
             style="max-height:{{ $screenBgSize }}vh;max-width:{{ $screenBgSize }}vw;object-fit:contain;opacity:{{ round($screenBgOpacity / 100, 2) }};filter:brightness(0.45)">
    </div>
    @endif

    <div id="sidebar-backdrop" class="hidden fixed inset-0 bg-black/50 z-40 xl:hidden"></div>

    <aside id="sidebar"
           class="fixed inset-y-0 left-0 w-64 bg-primary text-white shadow-xl z-50 transform -translate-x-full xl:translate-x-0 transition-transform duration-200 flex flex-col">
        <div class="h-16 flex items-center gap-2.5 px-5 border-b border-white/10 shrink-0">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 text-lg font-bold text-gold min-w-0">
                @if(\App\Models\Setting::get('logo_svg'))
                    <span class="h-8 w-auto inline-flex items-center [&>svg]:max-h-full [&>svg]:w-auto">{!! \App\Models\Setting::get('logo_svg') !!}</span>
                @else
                    <i class="fas fa-utensils"></i>
                    <span class="truncate">Rocks QR Menü</span>
                @endif
            </a>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-5">
            <div class="space-y-0.5">
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.dashboard') ? 'bg-gold/15 text-gold font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                    <i class="fas fa-tachometer-alt w-5 text-center {{ request()->routeIs('admin.dashboard') ? 'text-gold' : 'text-gold/70' }}"></i>Dashboard
                </a>
                <a href="{{ route('admin.categories.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.categories.*') ? 'bg-gold/15 text-gold font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                    <i class="fas fa-folder w-5 text-center {{ request()->routeIs('admin.categories.*') ? 'text-gold' : 'text-gold/70' }}"></i>Kategoriler
                </a>
                <a href="{{ route('admin.products.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.products.*') ? 'bg-gold/15 text-gold font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                    <i class="fas fa-box w-5 text-center {{ request()->routeIs('admin.products.*') ? 'text-gold' : 'text-gold/70' }}"></i>Ürünler
                </a>
            </div>

            <div>
                <p class="px-3 text-[11px] uppercase tracking-[0.2em] text-gold/70 font-semibold mb-1.5">Ekranlar</p>
                <div class="space-y-0.5">
                    <a href="{{ route('bar') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('bar') ? 'bg-gold/15 text-gold font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                        <i class="fas fa-wine-glass w-5 text-center {{ request()->routeIs('bar') ? 'text-gold' : 'text-purple-400' }}"></i>Bar Ekranı
                    </a>
                    <a href="{{ route('kitchen.pos') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('kitchen.pos') ? 'bg-gold/15 text-gold font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                        <i class="fas fa-server w-5 text-center {{ request()->routeIs('kitchen.pos') ? 'text-gold' : 'text-sky-400' }}"></i>Kitchen - Symphony
                    </a>
                    <a href="{{ route('kitchen.ana') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('kitchen.ana') ? 'bg-gold/15 text-gold font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                        <i class="fas fa-fire-burner w-5 text-center {{ request()->routeIs('kitchen.ana') ? 'text-gold' : 'text-teal-400' }}"></i>Ana Mutfak (AKDS)
                    </a>
                </div>
            </div>

            <div>
                <p class="px-3 text-[11px] uppercase tracking-[0.2em] text-gold/70 font-semibold mb-1.5">Entegrasyon</p>
                <div class="space-y-0.5">
                    <a href="{{ route('admin.sync') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.sync') ? 'bg-gold/15 text-gold font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                        <i class="fas fa-sync w-5 text-center {{ request()->routeIs('admin.sync') ? 'text-gold' : 'text-gold/70' }}"></i>Sync
                    </a>
                    <a href="{{ route('admin.qr-codes.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.qr-codes.*') ? 'bg-gold/15 text-gold font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                        <i class="fas fa-qrcode w-5 text-center {{ request()->routeIs('admin.qr-codes.*') ? 'text-gold' : 'text-gold/70' }}"></i>Masa QR
                    </a>
                    <a href="{{ route('admin.mssql-settings') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.mssql-settings*') ? 'bg-gold/15 text-gold font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                        <i class="fas fa-server w-5 text-center {{ request()->routeIs('admin.mssql-settings*') ? 'text-gold' : 'text-gold/70' }}"></i>MSSQL
                    </a>
                </div>
            </div>

            <div>
                <p class="px-3 text-[11px] uppercase tracking-[0.2em] text-gold/70 font-semibold mb-1.5">Yönetim</p>
                <div class="space-y-0.5">
                    <a href="{{ route('admin.reports.kitchen') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.reports.*') ? 'bg-gold/15 text-gold font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                        <i class="fas fa-chart-line w-5 text-center {{ request()->routeIs('admin.reports.*') ? 'text-gold' : 'text-gold/70' }}"></i>Raporlar
                    </a>
                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.users.*') ? 'bg-gold/15 text-gold font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                        <i class="fas fa-users w-5 text-center {{ request()->routeIs('admin.users.*') ? 'text-gold' : 'text-gold/70' }}"></i>Kullanıcılar
                    </a>
                    <a href="{{ route('admin.settings') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('admin.settings') ? 'bg-gold/15 text-gold font-medium' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                        <i class="fas fa-cog w-5 text-center {{ request()->routeIs('admin.settings') ? 'text-gold' : 'text-gold/70' }}"></i>Ayarlar
                    </a>
                </div>
            </div>
        </nav>

        <div class="border-t border-white/10 p-3 shrink-0">
            <div class="px-3 pb-2 text-xs text-gray-400 truncate" title="{{ auth()->user()->email ?? '' }}">
                <i class="fas fa-user-circle mr-1.5"></i>{{ auth()->user()->email ?? '' }}
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-red-300 hover:bg-red-500/10 hover:text-red-200 transition">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i>Çıkış Yap
                </button>
            </form>
        </div>
    </aside>

    <div class="xl:pl-64 flex flex-col min-h-screen">
        <header class="h-16 bg-primary text-white shadow-lg sticky top-0 z-30 flex items-center gap-3 px-4">
            <button type="button" id="sidebar-toggle" aria-label="Menü" aria-expanded="false"
                    class="xl:hidden flex items-center justify-center w-11 h-11 rounded-lg text-gray-200 hover:text-gold hover:bg-white/5 transition">
                <i id="sidebar-toggle-icon" class="fas fa-bars text-lg"></i>
            </button>
            <h1 class="text-sm sm:text-base font-semibold text-gray-100 truncate">
                @yield('title', 'Admin Panel - Rocks QR Menü')
            </h1>
        </header>

        @if($errors->any())
            <div class="max-w-7xl mx-auto mt-4 px-4 w-full">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <ul class="list-disc list-inside space-y-1 text-red-900">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="max-w-7xl mx-auto mt-4 px-4 w-full">
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-900">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto mt-4 px-4 w-full">
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-900">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
                </div>
            </div>
        @endif

        <main class="flex-1">
            @yield('content')
        </main>

        <footer class="bg-gray-100 border-t mt-12 py-6">
            <div class="max-w-7xl mx-auto px-4 text-center text-gray-600 text-sm">
                <p>&copy; {{ date('Y') }} Rocks Hotel QR Menü Sistemi</p>
            </div>
        </footer>
    </div>

    <script>
        (function () {
            var sidebar = document.getElementById('sidebar');
            var toggle = document.getElementById('sidebar-toggle');
            var backdrop = document.getElementById('sidebar-backdrop');
            var icon = document.getElementById('sidebar-toggle-icon');
            if (!sidebar || !toggle || !backdrop || !icon) return;

            function isOpen() {
                return !sidebar.classList.contains('-translate-x-full') || window.innerWidth >= 1280;
            }

            function syncIcon() {
                var open = isOpen();
                icon.classList.toggle('fa-bars', !open);
                icon.classList.toggle('fa-xmark', open);
                toggle.setAttribute('aria-expanded', String(open));
            }

            toggle.addEventListener('click', function () {
                if (window.innerWidth >= 1280) return;
                sidebar.classList.toggle('-translate-x-full');
                backdrop.classList.toggle('hidden');
                syncIcon();
            });

            backdrop.addEventListener('click', function () {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
                syncIcon();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && window.innerWidth < 1280) {
                    sidebar.classList.add('-translate-x-full');
                    backdrop.classList.add('hidden');
                    syncIcon();
                }
            });
        })();
    </script>
</body>
</html>
