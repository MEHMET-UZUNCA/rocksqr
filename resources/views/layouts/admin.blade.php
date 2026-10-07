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
    <nav class="bg-primary text-white shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex justify-between items-center h-16">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 text-lg font-bold text-gold shrink-0">
                    @if(\App\Models\Setting::get('logo_svg'))
                        <div class="h-9 w-auto [&>svg]:max-h-full [&>svg]:w-auto">{!! \App\Models\Setting::get('logo_svg') !!}</div>
                    @else
                        <i class="fas fa-utensils"></i><span class="hidden sm:inline">Rocks QR Menü</span><span class="sm:hidden">Rocks</span>
                    @endif
                </a>

                <div class="hidden xl:flex items-stretch h-full">
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-1.5 px-3 text-sm border-b-2 transition {{ request()->routeIs('admin.dashboard') ? 'border-gold text-white' : 'border-transparent text-gray-300 hover:text-white hover:border-gold/60' }}">
                        <i class="fas fa-tachometer-alt text-xs text-gold/80"></i>Dashboard
                    </a>
                    <a href="{{ route('admin.categories.index') }}"
                       class="flex items-center gap-1.5 px-3 text-sm border-b-2 transition {{ request()->routeIs('admin.categories.*') ? 'border-gold text-white' : 'border-transparent text-gray-300 hover:text-white hover:border-gold/60' }}">
                        <i class="fas fa-folder text-xs text-gold/80"></i>Kategoriler
                    </a>
                    <a href="{{ route('admin.products.index') }}"
                       class="flex items-center gap-1.5 px-3 text-sm border-b-2 transition {{ request()->routeIs('admin.products.*') ? 'border-gold text-white' : 'border-transparent text-gray-300 hover:text-white hover:border-gold/60' }}">
                        <i class="fas fa-box text-xs text-gold/80"></i>Ürünler
                    </a>

                    <div class="relative flex items-stretch" id="screen-menu-wrap">
                        <button type="button" id="screen-menu-btn" aria-haspopup="true" aria-expanded="false"
                                class="flex items-center gap-1.5 px-3 text-sm border-b-2 transition cursor-pointer {{ request()->routeIs('bar', 'kitchen.pos', 'kitchen.ana', 'screen.pin') ? 'border-gold text-white' : 'border-transparent text-gray-300 hover:text-white hover:border-gold/60' }}">
                            <i class="fas fa-desktop text-xs text-gold/80"></i>Ekran Menüsü
                            <i id="screen-menu-chevron" class="fas fa-chevron-down text-[10px] transition-transform duration-200"></i>
                        </button>
                        <div id="screen-menu" class="hidden absolute right-0 top-full w-60 bg-white text-gray-800 rounded-xl shadow-xl border border-gray-100 py-2 z-50">
                            <a href="{{ route('bar') }}" class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-gold/10 hover:text-primary transition">
                                <i class="fas fa-wine-glass w-4 text-center text-purple-600"></i>Bar Ekranı
                            </a>
                            <a href="{{ route('kitchen.pos') }}" class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-gold/10 hover:text-primary transition">
                                <i class="fas fa-server w-4 text-center text-sky-600"></i>Kitchen - Symphony
                            </a>
                            <a href="{{ route('kitchen.ana') }}" class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-gold/10 hover:text-primary transition">
                                <i class="fas fa-fire-burner w-4 text-center text-teal-600"></i>Ana Mutfak (AKDS)
                            </a>
                        </div>
                    </div>

                    <a href="{{ route('admin.sync') }}"
                       class="flex items-center gap-1.5 px-3 text-sm border-b-2 transition {{ request()->routeIs('admin.sync') ? 'border-gold text-white' : 'border-transparent text-gray-300 hover:text-white hover:border-gold/60' }}">
                        <i class="fas fa-sync text-xs text-gold/80"></i>Sync
                    </a>
                    <a href="{{ route('admin.qr-codes.index') }}"
                       class="flex items-center gap-1.5 px-3 text-sm border-b-2 transition {{ request()->routeIs('admin.qr-codes.*') ? 'border-gold text-white' : 'border-transparent text-gray-300 hover:text-white hover:border-gold/60' }}">
                        <i class="fas fa-qrcode text-xs text-gold/80"></i>QR
                    </a>
                    <a href="{{ route('admin.mssql-settings') }}"
                       class="flex items-center gap-1.5 px-3 text-sm border-b-2 transition {{ request()->routeIs('admin.mssql-settings') ? 'border-gold text-white' : 'border-transparent text-gray-300 hover:text-white hover:border-gold/60' }}">
                        <i class="fas fa-server text-xs text-gold/80"></i>MSSQL
                    </a>
                    <a href="{{ route('admin.reports.kitchen') }}"
                       class="flex items-center gap-1.5 px-3 text-sm border-b-2 transition {{ request()->routeIs('admin.reports.*') ? 'border-gold text-white' : 'border-transparent text-gray-300 hover:text-white hover:border-gold/60' }}">
                        <i class="fas fa-chart-line text-xs text-gold/80"></i>Raporlar
                    </a>
                    <a href="{{ route('admin.settings') }}"
                       class="flex items-center gap-1.5 px-3 text-sm border-b-2 transition {{ request()->routeIs('admin.settings') ? 'border-gold text-white' : 'border-transparent text-gray-300 hover:text-white hover:border-gold/60' }}">
                        <i class="fas fa-cog text-xs text-gold/80"></i>Ayarlar
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="flex items-stretch">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-1.5 px-3 text-sm border-b-2 border-transparent text-gray-300 hover:text-red-300 hover:border-red-400/60 transition">
                            <i class="fas fa-sign-out-alt text-xs text-gold/80"></i>Çıkış
                        </button>
                    </form>
                </div>

                <button type="button" id="nav-toggle" aria-label="Menü" aria-expanded="false"
                        class="xl:hidden flex items-center justify-center w-11 h-11 rounded-lg text-gray-200 hover:text-gold hover:bg-white/5 transition">
                    <i id="nav-toggle-icon" class="fas fa-bars text-lg"></i>
                </button>
            </div>
        </div>

        <div id="mobile-nav" class="hidden xl:hidden border-t border-white/10 bg-light-primary max-h-[calc(100vh-4rem)] overflow-y-auto">
            <div class="px-4 py-4 space-y-5">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-gold/70 font-semibold mb-2 px-3">Menü</p>
                    <div class="space-y-0.5">
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-200 hover:bg-white/5 hover:text-white transition">
                            <i class="fas fa-tachometer-alt w-5 text-center text-gold/80"></i>Dashboard
                        </a>
                        <a href="{{ route('admin.categories.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-200 hover:bg-white/5 hover:text-white transition">
                            <i class="fas fa-folder w-5 text-center text-gold/80"></i>Kategoriler
                        </a>
                        <a href="{{ route('admin.products.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-200 hover:bg-white/5 hover:text-white transition">
                            <i class="fas fa-box w-5 text-center text-gold/80"></i>Ürünler
                        </a>
                    </div>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-gold/70 font-semibold mb-2 px-3">Ekranlar</p>
                    <div class="space-y-0.5">
                        <a href="{{ route('bar') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-200 hover:bg-white/5 hover:text-white transition">
                            <i class="fas fa-wine-glass w-5 text-center text-purple-400"></i>Bar Ekranı
                        </a>
                        <a href="{{ route('kitchen.pos') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-200 hover:bg-white/5 hover:text-white transition">
                            <i class="fas fa-server w-5 text-center text-sky-400"></i>Kitchen - Symphony
                        </a>
                        <a href="{{ route('kitchen.ana') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-200 hover:bg-white/5 hover:text-white transition">
                            <i class="fas fa-fire-burner w-5 text-center text-teal-400"></i>Ana Mutfak (AKDS)
                        </a>
                    </div>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-gold/70 font-semibold mb-2 px-3">Yönetim</p>
                    <div class="space-y-0.5">
                        <a href="{{ route('admin.sync') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-200 hover:bg-white/5 hover:text-white transition">
                            <i class="fas fa-sync w-5 text-center text-gold/80"></i>Sync
                        </a>
                        <a href="{{ route('admin.qr-codes.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-200 hover:bg-white/5 hover:text-white transition">
                            <i class="fas fa-qrcode w-5 text-center text-gold/80"></i>Masa QR
                        </a>
                        <a href="{{ route('admin.mssql-settings') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-200 hover:bg-white/5 hover:text-white transition">
                            <i class="fas fa-server w-5 text-center text-gold/80"></i>MSSQL
                        </a>
                        <a href="{{ route('admin.reports.kitchen') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-200 hover:bg-white/5 hover:text-white transition">
                            <i class="fas fa-chart-line w-5 text-center text-gold/80"></i>Raporlar
                        </a>
                        <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-200 hover:bg-white/5 hover:text-white transition">
                            <i class="fas fa-cog w-5 text-center text-gold/80"></i>Ayarlar
                        </a>
                    </div>
                </div>

                <div class="pt-3 border-t border-white/10">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-300 hover:bg-red-500/10 hover:text-red-200 transition">
                            <i class="fas fa-sign-out-alt w-5 text-center"></i>Çıkış Yap
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    @if($errors->any())
        <div class="max-w-7xl mx-auto mt-4 px-4">
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
        <div class="max-w-7xl mx-auto mt-4 px-4">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-green-900">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            </div>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="bg-gray-100 border-t mt-12 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-600 text-sm">
            <p>&copy; {{ date('Y') }} Rocks Hotel QR Menü Sistemi</p>
        </div>
    </footer>

    <script>
        (function () {
            var wrap = document.getElementById('screen-menu-wrap');
            var btn = document.getElementById('screen-menu-btn');
            var menu = document.getElementById('screen-menu');
            var chevron = document.getElementById('screen-menu-chevron');

            function closeScreenMenu() {
                if (!menu) return;
                menu.classList.add('hidden');
                chevron.classList.remove('rotate-180');
                btn.setAttribute('aria-expanded', 'false');
            }

            if (wrap && btn && menu && chevron) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    if (menu.classList.contains('hidden')) {
                        menu.classList.remove('hidden');
                        chevron.classList.add('rotate-180');
                        btn.setAttribute('aria-expanded', 'true');
                    } else {
                        closeScreenMenu();
                    }
                });
                document.addEventListener('click', function (e) {
                    if (!wrap.contains(e.target)) closeScreenMenu();
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') closeScreenMenu();
                });
            }

            var toggle = document.getElementById('nav-toggle');
            var panel = document.getElementById('mobile-nav');
            var icon = document.getElementById('nav-toggle-icon');

            if (toggle && panel && icon) {
                toggle.addEventListener('click', function () {
                    var open = !panel.classList.contains('hidden');
                    panel.classList.toggle('hidden', open);
                    icon.classList.toggle('fa-bars', open);
                    icon.classList.toggle('fa-xmark', !open);
                    toggle.setAttribute('aria-expanded', String(!open));
                });
            }
        })();
    </script>
</body>
</html>
