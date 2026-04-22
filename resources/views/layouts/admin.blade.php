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
    <nav class="bg-primary text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold text-gold flex items-center gap-2">
                @if(\App\Models\Setting::get('logo_svg'))
                    <div class="h-8 w-auto [&>svg]:max-h-full [&>svg]:w-auto">{!! \App\Models\Setting::get('logo_svg') !!}</div>
                @else
                    <i class="fas fa-utensils mr-2"></i>Rocks QR Menü
                @endif
            </a>
            
            <div class="flex items-center gap-6">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-gold transition">
                    <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                </a>
                <a href="{{ route('admin.categories.index') }}" class="hover:text-gold transition">
                    <i class="fas fa-folder mr-1"></i> Kategoriler
                </a>
                <a href="{{ route('admin.products.index') }}" class="hover:text-gold transition">
                    <i class="fas fa-box mr-1"></i> Ürünler
                </a>
                <a href="{{ route('kitchen') }}" class="hover:text-gold transition">
                    <i class="fas fa-tv mr-1"></i> Mutfak
                </a>
                <a href="{{ route('admin.sync') }}" class="hover:text-gold transition">
                    <i class="fas fa-sync mr-1"></i> Sync
                </a>
                <a href="{{ route('admin.qr-codes.index') }}" class="hover:text-gold transition">
                    <i class="fas fa-qrcode mr-1"></i> QR
                </a>
                <a href="{{ route('admin.oracle-settings') }}" class="hover:text-gold transition">
                    <i class="fas fa-database mr-1"></i> Oracle
                </a>
                <a href="{{ route('admin.mssql-settings') }}" class="hover:text-gold transition">
                    <i class="fas fa-server mr-1"></i> MSSQL
                </a>
                <a href="{{ route('admin.settings') }}" class="hover:text-gold transition">
                    <i class="fas fa-cog mr-1"></i> Ayarlar
                </a>
                
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="hover:text-gold transition">
                        <i class="fas fa-sign-out-alt mr-1"></i> Çıkış
                    </button>
                </form>
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
</body>
</html>