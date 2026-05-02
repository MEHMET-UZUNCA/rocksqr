@extends('layouts.admin')

@section('content')
@php $activeTab = request('tab', 'genel'); @endphp
<div class="py-8">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

        <div class="mb-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-yellow-100 flex items-center justify-center">
                <i class="fas fa-cog text-yellow-600 text-lg"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Ayarlar</h1>
                <p class="text-xs text-gray-400">Sistem yapılandırması</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-800 rounded-lg flex items-center gap-2">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded-lg">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <nav class="flex border-b border-gray-200">
                <a href="{{ route('admin.settings') }}?tab=genel"
                   class="flex items-center gap-2 px-5 py-3 text-sm font-medium transition border-b-2 -mb-px {{ $activeTab === 'genel' ? 'border-yellow-500 text-yellow-700 bg-yellow-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    <i class="fas fa-sliders-h text-xs"></i> Genel Ayarlar
                </a>
                <a href="{{ route('admin.settings') }}?tab=ekran"
                   class="flex items-center gap-2 px-5 py-3 text-sm font-medium transition border-b-2 -mb-px {{ $activeTab === 'ekran' ? 'border-blue-500 text-blue-700 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    <i class="fas fa-tv text-xs"></i> Ekran Ayarları
                </a>
                <a href="{{ route('admin.settings') }}?tab=subdomain"
                   class="flex items-center gap-2 px-5 py-3 text-sm font-medium transition border-b-2 -mb-px {{ $activeTab === 'subdomain' ? 'border-indigo-500 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    <i class="fas fa-globe text-xs"></i> Subdomain
                </a>
            </nav>

            <div class="p-6">

                @if($activeTab === 'genel')
                <form action="{{ route('admin.settings.update') }}?tab=genel" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-image mr-1"></i>Logo (SVG)
                        </label>
                        @if($settings['logo_svg'])
                            <div class="mb-3 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                <p class="text-xs text-gray-500 mb-2">Mevcut Logo:</p>
                                <div class="w-48 h-16 flex items-center [&>svg]:max-w-full [&>svg]:max-h-full [&>svg]:w-auto [&>svg]:h-auto">
                                    {!! $settings['logo_svg'] !!}
                                </div>
                                <label class="mt-3 flex items-center gap-2 text-sm text-red-600 cursor-pointer">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded">
                                    Logoyu kaldir
                                </label>
                            </div>
                        @else
                            <p class="text-sm text-gray-400 mb-2">Henüz logo yüklenmedi.</p>
                        @endif
                        <input type="file" name="logo_svg" accept=".svg"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-yellow-50 file:text-yellow-800 hover:file:bg-yellow-100">
                        <p class="text-xs text-gray-400 mt-1">Sadece SVG formati, maks 512KB</p>
                    </div>
                    <div class="mb-6">
                        <label for="site_title" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-1"></i>Site Basligi (Title)
                        </label>
                        <input type="text" name="site_title" id="site_title"
                               value="{{ old('site_title', $settings['site_title']) }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-yellow-400"
                               placeholder="Rocks Hotel QR Menu">
                    </div>
                    <div class="mb-6">
                        <label for="meta_description" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-align-left mr-1"></i>Meta Aciklama (Description)
                        </label>
                        <textarea name="meta_description" id="meta_description" rows="3"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-yellow-400"
                                  placeholder="Site aciklamasi...">{{ old('meta_description', $settings['meta_description']) }}</textarea>
                        <p class="text-xs text-gray-400 mt-1">Maks 500 karakter</p>
                    </div>
                    <div class="mb-6">
                        <label for="meta_keywords" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-tags mr-1"></i>Meta Anahtar Kelimeler (Keywords)
                        </label>
                        <input type="text" name="meta_keywords" id="meta_keywords"
                               value="{{ old('meta_keywords', $settings['meta_keywords']) }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-yellow-400"
                               placeholder="hotel, menu, qr, rocks">
                        <p class="text-xs text-gray-400 mt-1">Virgülle ayirin</p>
                    </div>
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="font-semibold text-blue-800 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>Ürün Benzersiz ID Bilgisi
                        </h3>
                        <p class="text-sm text-blue-700">
                            Her ürünün veritabaninda benzersiz bir <code class="bg-blue-100 px-1 rounded">id</code> degeri vardir.
                            <code class="bg-blue-100 px-1 rounded">mssql_id</code> alani harici sistem entegrasyonu icin kullanilabilir.
                        </p>
                    </div>
                    <button type="submit" class="w-full py-3 bg-primary text-white font-bold rounded-lg hover:bg-light-primary transition">
                        <i class="fas fa-save mr-2"></i>Genel Ayarlari Kaydet
                    </button>
                </form>
                @endif

                @if($activeTab === 'ekran')

                <div class="mb-2 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
                        <i class="fas fa-wine-glass text-amber-600"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-800">Bar Ekran Ayarlari</h3>
                </div>
                <form action="{{ route('admin.settings.update') }}?tab=ekran" method="POST" class="mb-8">
                    <input type="hidden" name="_display_only" value="bar">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-heading mr-1"></i>Bar Ekrani Basligi</label>
                            <input type="text" name="bar_screen_title" value="{{ old('bar_screen_title', $settings['bar_screen_title']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-400" placeholder="KDS - Bar Ekrani">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-check-circle mr-1"></i>Tamamlanan Siparis Sayisi</label>
                            <input type="number" min="1" max="100" name="bar_completed_display" value="{{ old('bar_completed_display', $settings['bar_completed_display']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-400">
                            <p class="text-xs text-gray-400 mt-1">Siparis Hazir ve Tamamlanan alani (1-100)</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-list-check mr-1"></i>Siparis Hazir: Görüntülenecek Adet</label>
                            <input type="number" min="1" max="200" name="order_ready_display" value="{{ old('order_ready_display', $settings['order_ready_display']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-chart-line mr-1"></i>Siparis Kari: Görüntülenecek Adet</label>
                            <input type="number" min="1" max="200" name="order_profit_display" value="{{ old('order_profit_display', $settings['order_profit_display']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-400">
                        </div>
                    </div>
                    <button type="submit" class="py-2.5 px-5 bg-amber-500 text-white font-bold rounded-lg hover:bg-amber-600 transition text-sm">
                        <i class="fas fa-save mr-2"></i>Bar Ayarlarini Kaydet
                    </button>
                </form>

                <hr class="border-gray-100 mb-6">

                <div class="mb-2 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center shrink-0">
                        <i class="fas fa-utensils text-orange-600"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-800">Kitchen Ekran Ayarlari</h3>
                </div>
                <form action="{{ route('admin.settings.update') }}?tab=ekran" method="POST" class="mb-8">
                    <input type="hidden" name="_display_only" value="kitchen">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-heading mr-1"></i>Mutfak Ekrani Basligi</label>
                            <input type="text" name="kitchen_screen_title" value="{{ old('kitchen_screen_title', $settings['kitchen_screen_title']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-400" placeholder="POOL Mutfak Ekrani">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-check-circle mr-1"></i>Tamamlanan Son Siparis Sayisi</label>
                            <input type="number" min="1" max="100" name="kitchen_completed_display" value="{{ old('kitchen_completed_display', $settings['kitchen_completed_display']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-400">
                            <p class="text-xs text-gray-400 mt-1">Hazirlandı olarak isaretlenen son kac siparis (1-100)</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-bell mr-1"></i>Garson Çagrilari: Görüntülenecek Adet</label>
                            <input type="number" min="1" max="200" name="waiter_call_display" value="{{ old('waiter_call_display', $settings['waiter_call_display']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-400">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-rotate-left mr-1"></i>Geri Alma Süresi (saniye)</label>
                            <input type="number" min="5" max="600" name="ready_undo_seconds" value="{{ old('ready_undo_seconds', $settings['ready_undo_seconds']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-orange-400">
                            <p class="text-xs text-gray-400 mt-1">Geri Al butonu bu süre çalisir</p>
                        </div>
                    </div>
                    <button type="submit" class="py-2.5 px-5 bg-orange-500 text-white font-bold rounded-lg hover:bg-orange-600 transition text-sm">
                        <i class="fas fa-save mr-2"></i>Kitchen Ayarlarini Kaydet
                    </button>
                </form>

                <hr class="border-gray-100 mb-6">

                <div class="mb-2 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-sky-100 flex items-center justify-center shrink-0">
                        <i class="fas fa-clock text-sky-600"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-800">Ekran Temizleme Saati</h3>
                </div>
                <form action="{{ route('admin.settings.update') }}?tab=ekran" method="POST" class="mb-8 flex items-end gap-4">
                    <input type="hidden" name="_clear_time_only" value="1">
                    @csrf
                    @method('PUT')
                    <div class="flex-1">
                        <input type="time" name="screen_clear_time" value="{{ old('screen_clear_time', $settings['screen_clear_time'] ?? '14:00') }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-sky-400" required>
                        <p class="text-xs text-gray-400 mt-1">Her gün bu saatte mutfak ve bar ekranlari otomatik temizlenir.</p>
                    </div>
                    <button type="submit" class="py-2.5 px-5 bg-sky-500 text-white font-bold rounded-lg hover:bg-sky-600 transition text-sm whitespace-nowrap">
                        <i class="fas fa-save mr-2"></i>Kaydet
                    </button>
                </form>

                <hr class="border-gray-100 mb-6">

                <div class="mb-3 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center shrink-0">
                        <i class="fas fa-stopwatch text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-800">Sayac Renk Esikleri</h3>
                        <p class="text-xs text-gray-400">Yesil (baslangic) → Sari → Turuncu → Kirmizi (dakika cinsinden)</p>
                    </div>
                </div>
                <form action="{{ route('admin.settings.update') }}?tab=ekran" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_timer_only" value="1">
                    @php
                    $timerRows = [
                        ['key' => 'qr',     'label' => 'QR Siparis',    'icon' => 'fa-mobile-screen', 'color' => 'text-orange-600'],
                        ['key' => 'sym',    'label' => 'SYM (Symphony)','icon' => 'fa-server',        'color' => 'text-blue-600'],
                        ['key' => 'ready',  'label' => 'Hazir Siparis', 'icon' => 'fa-concierge-bell','color' => 'text-emerald-600'],
                        ['key' => 'waiter', 'label' => 'Garson Cagrisi','icon' => 'fa-bell',          'color' => 'text-red-600'],
                    ];
                    @endphp
                    <div class="rounded-lg border border-gray-200 overflow-hidden mb-4">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 w-44">Sayac</th>
                                    <th class="px-3 py-2 text-xs font-semibold text-yellow-600 text-center"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-yellow-500 mr-1 align-middle"></span>Sari (dk)</th>
                                    <th class="px-3 py-2 text-xs font-semibold text-orange-600 text-center"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-orange-500 mr-1 align-middle"></span>Turuncu (dk)</th>
                                    <th class="px-3 py-2 text-xs font-semibold text-red-600 text-center"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-red-600 mr-1 align-middle"></span>Kirmizi (dk)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($timerRows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2.5 font-medium {{ $row['color'] }}">
                                        <i class="fas {{ $row['icon'] }} mr-1.5 text-xs"></i>{{ $row['label'] }}
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <input type="number" name="timer_{{ $row['key'] }}_yellow" min="1" max="120"
                                               value="{{ old('timer_'.$row['key'].'_yellow', $settings['timer_'.$row['key'].'_yellow']) }}"
                                               class="w-full border border-gray-300 rounded px-2 py-1 text-sm text-center focus:ring-2 focus:ring-yellow-400">
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <input type="number" name="timer_{{ $row['key'] }}_orange" min="1" max="120"
                                               value="{{ old('timer_'.$row['key'].'_orange', $settings['timer_'.$row['key'].'_orange']) }}"
                                               class="w-full border border-gray-300 rounded px-2 py-1 text-sm text-center focus:ring-2 focus:ring-orange-400">
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <input type="number" name="timer_{{ $row['key'] }}_red" min="1" max="120"
                                               value="{{ old('timer_'.$row['key'].'_red', $settings['timer_'.$row['key'].'_red']) }}"
                                               class="w-full border border-gray-300 rounded px-2 py-1 text-sm text-center focus:ring-2 focus:ring-red-400">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="py-2.5 px-5 bg-purple-600 text-white font-bold rounded-lg hover:bg-purple-700 transition text-sm">
                        <i class="fas fa-save mr-2"></i>Sayac Esiklerini Kaydet
                    </button>
                </form>
                @endif

                @if($activeTab === 'subdomain')
                <div class="mb-4">
                    <p class="text-sm text-gray-500">
                        Her ekrana özel subdomain alias tanimlayin. Sunucunuzda bu subdomain'leri ayni IP'ye yönlendirmeniz yeterlidir.<br>
                        <span class="text-xs text-gray-400">Örnek: <code class="bg-gray-100 px-1 rounded">poolbds.rockshotel.com</code> &rarr; Bar KDS</span>
                    </p>
                </div>
                <form action="{{ route('admin.settings.update') }}?tab=subdomain" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_subdomain_only" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-cocktail mr-1 text-amber-500"></i>Bar KDS Subdomain</label>
                            <input type="text" name="subdomain_bar" value="{{ old('subdomain_bar', $settings['subdomain_bar']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-400 font-mono text-sm" placeholder="poolbds">
                            <p class="text-xs text-gray-400 mt-1">&rarr; <code class="bg-gray-100 px-1 rounded">/bar</code></p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-utensils mr-1 text-orange-500"></i>Mutfak KDS Subdomain</label>
                            <input type="text" name="subdomain_kitchen" value="{{ old('subdomain_kitchen', $settings['subdomain_kitchen']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-400 font-mono text-sm" placeholder="poolkds">
                            <p class="text-xs text-gray-400 mt-1">&rarr; <code class="bg-gray-100 px-1 rounded">/kitchen-pos</code></p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-fire-burner mr-1 text-teal-500"></i>Ana Mutfak (AKDS) Subdomain</label>
                            <input type="text" name="subdomain_ana" value="{{ old('subdomain_ana', $settings['subdomain_ana']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-400 font-mono text-sm" placeholder="mainbds">
                            <p class="text-xs text-gray-400 mt-1">&rarr; <code class="bg-gray-100 px-1 rounded">/kitchen-ana</code></p>
                        </div>
                    </div>
                    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 mb-4 text-xs text-indigo-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>DNS / Sunucu Ayari:</strong> Subdomain'leri wildcard veya tek tek A/CNAME kaydi olarak sunucu IP'sine yönlendirin. Apache/Nginx'te ayni VirtualHost'u tüm subdomainler icin dinleyin.
                    </div>
                    <button type="submit" class="py-2.5 px-5 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition text-sm">
                        <i class="fas fa-save mr-2"></i>Subdomain Ayarlarini Kaydet
                    </button>
                </form>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection
