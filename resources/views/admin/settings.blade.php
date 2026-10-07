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
            <nav class="flex flex-wrap border-b border-gray-200">
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
                <a href="{{ route('admin.settings') }}?tab=pin"
                   class="flex items-center gap-2 px-5 py-3 text-sm font-medium transition border-b-2 -mb-px {{ $activeTab === 'pin' ? 'border-purple-500 text-purple-700 bg-purple-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    <i class="fas fa-lock text-xs"></i> Ekran PIN
                </a>
                <a href="{{ route('admin.settings') }}?tab=oda"
                   class="flex items-center gap-2 px-5 py-3 text-sm font-medium transition border-b-2 -mb-px {{ $activeTab === 'oda' ? 'border-emerald-500 text-emerald-700 bg-emerald-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    <i class="fas fa-door-open text-xs"></i> Oda Numaraları
                </a>
                <a href="{{ route('admin.settings') }}?tab=diger"
                   class="flex items-center gap-2 px-5 py-3 text-sm font-medium transition border-b-2 -mb-px {{ $activeTab === 'diger' ? 'border-slate-500 text-slate-700 bg-slate-100' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    <i class="fas fa-keyboard text-xs"></i> Diğer Ayarlar
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
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-filter mr-1"></i>Bar Ekrani Kategori Filtreleri</label>
                        @foreach (\App\Support\KitchenFilter::BAR_RVCS as $barRvcId => $barRvcName)
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg mb-3">
                            <p class="text-xs font-bold text-amber-700 mb-2">{{ strtoupper($barRvcName) }} <span class="font-normal text-amber-400">(Symphony RVC {{ $barRvcId }})</span></p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach (\App\Support\KitchenFilter::GROUPS as $barMg => [$barLabel, $barDesc, $barIcon])
                                <label class="flex items-start gap-2 text-sm font-semibold text-gray-700 cursor-pointer">
                                    <input type="checkbox" name="bar_show_{{ $barRvcId }}_{{ $barMg }}" value="1" class="rounded mt-0.5"
                                           @checked(old("bar_show_{$barRvcId}_{$barMg}", $settings["bar_show_{$barRvcId}_{$barMg}"]))>
                                    <span><i class="fas {{ $barIcon }} mr-1"></i>{{ $barLabel }}
                                        <span class="block text-xs font-normal text-gray-400">{{ $barDesc }}</span>
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                        <p class="text-xs text-gray-400">İşaretiniz kaldırılan satırlar HAZIRLANAN alanındaki kartlarda gösterilmez; GELEN SİPARİŞLER kolonu filtresizdir. QR siparişlerindeki içecekler "İçecek" tick'ine bağlıdır. Tick'ler mutfak ekranından bağımsızdır.</p>
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
                            <p class="text-xs text-gray-400 mt-1">Kitchen Pos alt şeridinde gösterilecek son kaç tamamlanan (1-100)</p>
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
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-filter mr-1"></i>Mutfak Ekrani Kategori Filtreleri</label>
                        @foreach (\App\Support\KitchenFilter::RVCS as $kitchenRvcId => $kitchenRvcName)
                        <div class="p-3 bg-orange-50 border border-orange-200 rounded-lg mb-3">
                            <p class="text-xs font-bold text-orange-700 mb-2">{{ strtoupper($kitchenRvcName) }} <span class="font-normal text-orange-400">(Symphony RVC {{ $kitchenRvcId }})</span></p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach (\App\Support\KitchenFilter::GROUPS as $kitchenMg => [$kitchenLabel, $kitchenDesc, $kitchenIcon])
                                <label class="flex items-start gap-2 text-sm font-semibold text-gray-700 cursor-pointer">
                                    <input type="checkbox" name="kitchen_show_{{ $kitchenRvcId }}_{{ $kitchenMg }}" value="1" class="rounded mt-0.5"
                                           @checked(old("kitchen_show_{$kitchenRvcId}_{$kitchenMg}", $settings["kitchen_show_{$kitchenRvcId}_{$kitchenMg}"]))>
                                    <span><i class="fas {{ $kitchenIcon }} mr-1"></i>{{ $kitchenLabel }}
                                        <span class="block text-xs font-normal text-gray-400">{{ $kitchenDesc }}</span>
                                    </span>
                                </label>
                                @endforeach
                            </div>
                            <div class="mt-3 pt-3 border-t border-orange-100">
                                <label for="kitchen_fg_hide_{{ $kitchenRvcId }}" class="block text-xs font-bold text-orange-700 mb-1"><i class="fas fa-eye-slash mr-1"></i>Hariç Tutulacak Family Grupları</label>
                                <p class="text-[11px] text-gray-500 mb-2">Mutfak ekranına yazdırılmayacak Symphony FamilyGroup kodları; virgül veya + ile eklenebilir (örn: 1205,1206 ya da 1205+1206). Boş bırakılırsa hiçbir grup gizlenmez.</p>
                                <input type="text" id="kitchen_fg_hide_{{ $kitchenRvcId }}" name="kitchen_fg_hide_{{ $kitchenRvcId }}"
                                       value="{{ old('kitchen_fg_hide_' . $kitchenRvcId, \App\Support\KitchenFilter::familyHideList($kitchenRvcId)) }}"
                                       class="w-full max-w-xs border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400"
                                       placeholder="Örn: 1205">
                            </div>
                        </div>
                        @endforeach
                        <p class="text-xs text-gray-400">İşaretiniz kaldırılan Symphony satırları mutfak ekranına gelmez. Filtre yalnızca Symphony (POS) satırları için geçerlidir; kapatılıp yeniden açıldığında süre sayaçları kaldığı yerden devam eder.</p>
                    </div>
                    <button type="submit" class="py-2.5 px-5 bg-orange-500 text-white font-bold rounded-lg hover:bg-orange-600 transition text-sm">
                        <i class="fas fa-save mr-2"></i>Kitchen Ayarlarini Kaydet
                    </button>
                </form>

                <hr class="border-gray-100 mb-6">

                <div class="mb-2 flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
                        <i class="fas fa-clock text-emerald-600"></i>
                    </div>
                    <h3 class="text-base font-bold text-gray-800">Sunucu Saati</h3>
                </div>
                <form action="{{ route('admin.settings.update') }}?tab=ekran" method="POST" class="mb-8">
                    <input type="hidden" name="_clock_only" value="1">
                    @csrf
                    @method('PUT')
                    @php
                        $dbNowIso    = \App\Support\Clock::dbNowIso();
                        $clockSource = old('screen_clock_source', $settings['screen_clock_source'] ?? 'server');
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-center">
                            <p class="text-xs text-emerald-700 font-semibold mb-1">Sunucu Saati</p>
                            <p id="admin-server-clock" data-server-now="{{ now()->setTimezone('Europe/Istanbul')->toIso8601String() }}" class="text-2xl font-extrabold text-emerald-800 tabular-nums">--:--:--</p>
                            @if($dbNowIso)
                            <p class="text-xs text-emerald-600 mt-1">Veritabanı: <span id="admin-db-clock" data-db-now="{{ $dbNowIso }}" class="font-semibold tabular-nums">--:--:--</span></p>
                            @endif
                            <p class="text-xs text-emerald-600 mt-0.5">Dilim: Europe/Istanbul</p>
                        </div>
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
                            <p class="text-xs text-gray-600 font-semibold mb-1">Tarayıcı Saati</p>
                            <p id="admin-browser-clock" class="text-2xl font-extrabold text-gray-800 tabular-nums">--:--:--</p>
                            <p id="admin-clock-diff" class="text-xs text-gray-500 mt-1">Fark: hesaplanıyor…</p>
                        </div>
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <label class="block text-xs font-bold text-blue-700 mb-1"><i class="fas fa-satellite-dish mr-1"></i>Ekran Saatleri Kaynağı</label>
                            <select name="screen_clock_source" class="w-full border border-blue-300 rounded-lg px-3 py-2 text-sm font-semibold text-gray-700 bg-white focus:ring-2 focus:ring-blue-400">
                                <option value="server" {{ $clockSource === 'server' ? 'selected' : '' }}>Sunucu (sistem saati)</option>
                                <option value="database" {{ $clockSource === 'database' ? 'selected' : '' }}>Veritabanı saati</option>
                                <option value="browser" {{ $clockSource === 'browser' ? 'selected' : '' }}>Tarayıcı saati</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Mutfak/bar ekranlarındaki saat seçilen kaynaktan okunur. Sipariş sayaçları her durumda sunucu zamanını esas alır.</p>
                        </div>
                    </div>
                    <button type="submit" class="py-2.5 px-5 bg-emerald-500 text-white font-bold rounded-lg hover:bg-emerald-600 transition text-sm">
                        <i class="fas fa-save mr-2"></i>Saat Ayarini Kaydet
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
                        <p class="text-xs text-gray-400 mt-1">Her gün bu saatte mutfak ve bar ekranlari otomatik temizlenir. Sadece ekranlardaki bekleyenler kapatilir; kayitlar ve rapor verileri korunur.</p>
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

                @if($activeTab === 'pin')
                <div class="mb-4">
                    <p class="text-sm text-gray-500">
                        Ekran uygulamaları (BDS, Kitchen POS, AKDS) ilk açılışta PIN sorar.
                        Doğru PIN girilince tarayıcıda 30 gün hatırlanır. Admin paneli girişi etkilenmez.
                    </p>
                </div>
                <form action="{{ route('admin.settings.update') }}?tab=pin" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_pin_only" value="1">
                    @php
                    $pinScreens = [
                        ['key' => 'bar',  'label' => 'Bar Ekranı (BDS)',       'icon' => 'fa-wine-glass',  'dot' => 'bg-amber-500'],
                        ['key' => 'kpos',    'label' => 'Kitchen POS (Symphony)',  'icon' => 'fa-server',      'dot' => 'bg-blue-500'],
                        ['key' => 'ana',     'label' => 'Ana Mutfak (AKDS)',       'icon' => 'fa-fire-burner', 'dot' => 'bg-teal-500'],
                    ];
                    @endphp
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        @foreach($pinScreens as $ps)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="inline-block w-2.5 h-2.5 rounded-full {{ $ps['dot'] }}"></span>
                                <i class="fas {{ $ps['icon'] }} text-gray-500 text-sm"></i>
                                <h4 class="text-sm font-bold text-gray-800">{{ $ps['label'] }}</h4>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700 mb-3 cursor-pointer">
                                <input type="checkbox" name="screen_pin_{{ $ps['key'] }}_enabled" value="1" class="rounded"
                                       @checked(old('screen_pin_'.$ps['key'].'_enabled', $settings['screen_pin_'.$ps['key'].'_enabled']))>
                                Bu ekranda PIN istensin
                            </label>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">PIN (4-6 hane)</label>
                            <input type="password" inputmode="numeric" maxlength="6" name="screen_pin_{{ $ps['key'] }}"
                                   value="{{ old('screen_pin_'.$ps['key']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 tracking-[0.4em] font-mono text-sm focus:ring-2 focus:ring-purple-400"
                                   placeholder="{{ $settings['screen_pin_'.$ps['key'].'_set'] ? '••••••' : 'PIN atanmadı' }}">
                            <p class="text-xs mt-1 {{ $settings['screen_pin_'.$ps['key'].'_set'] ? 'text-gray-400' : 'text-red-400' }}">
                                {{ $settings['screen_pin_'.$ps['key'].'_set'] ? 'Mevcut PIN kayıtlı — boş bırakılırsa değişmez.' : 'Henüz PIN atanmadı.' }}
                            </p>
                            @error('screen_pin_'.$ps['key'])<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        @endforeach
                    </div>
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-3 mb-4 text-xs text-purple-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Nasıl çalışır:</strong> Ekran ilk açılışta PIN sorar; doğru PIN girilince 30 gün boyunca bu tarayıcıda hatırlanır.
                        PIN değiştirmek için yeni PIN yazıp kaydedin; boş bırakmak mevcut PIN'i korur.
                        Bir ekranı tekrar kilitlemek için o tarayıcıdaki <code class="bg-purple-100 px-1 rounded">screen_auth_*</code> çerezini silin.
                    </div>
                    <button type="submit" class="py-2.5 px-5 bg-purple-600 text-white font-bold rounded-lg hover:bg-purple-700 transition text-sm">
                        <i class="fas fa-save mr-2"></i>Ekran PIN Ayarlarını Kaydet
                    </button>
                </form>
                @endif

                @if($activeTab === 'oda')
                @php
                    $roomCount = collect(explode(',', $settings['room_numbers']))->filter(fn ($r) => trim($r) !== '')->count();
                @endphp
                <div class="mb-4">
                    <p class="text-sm text-gray-500">
                        Oda servisi QR menüsünden sipariş verilebilecek oda numaralarını tanımlayın.
                        Müşteri sipariş gönderirken bu listeden bir oda numarası girmek zorundadır.
                    </p>
                </div>
                <form action="{{ route('admin.settings.update') }}?tab=oda" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_rooms_only" value="1">
                    <div class="mb-4">
                        <label for="room_numbers" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-door-open mr-1"></i>Oda Numaraları
                        </label>
                        <textarea name="room_numbers" id="room_numbers" rows="8"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-3 font-mono text-sm focus:ring-2 focus:ring-emerald-400"
                                  placeholder="101, 102, 103, 201, 202&#10;301&#10;302">{{ old('room_numbers', $settings['room_numbers']) }}</textarea>
                        <p class="text-xs text-gray-400 mt-1">Virgül veya alt satır ile ayırın. Maks 16 karakter her oda. Listeyi boş kaydederseniz özellik kapanır (siparişlerde oda sorulmaz).</p>
                    </div>
                    <div class="mb-4 p-4 rounded-lg {{ $roomCount > 0 ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-gray-50 border border-gray-200 text-gray-500' }}">
                        <i class="fas fa-info-circle mr-1"></i>
                        @if($roomCount > 0)
                            Şu anda <strong>{{ $roomCount }}</strong> oda tanımlı — QR menüde sipariş aşamasında oda numarası isteniyor.
                        @else
                            Henüz oda tanımlı değil — QR menüde sipariş aşamasında oda numarası istenmez.
                        @endif
                    </div>
                    <button type="submit" class="py-2.5 px-5 bg-emerald-600 text-white font-bold rounded-lg hover:bg-emerald-700 transition text-sm">
                        <i class="fas fa-save mr-2"></i>Oda Numaralarını Kaydet
                    </button>
                </form>
                @endif

                @if($activeTab === 'diger')
                @php
                    $kbdRows = [
                        ['key' => 'home',        'label' => 'HOME',            'desc' => 'En baştaki kartı seçer'],
                        ['key' => 'end',         'label' => 'END',             'desc' => 'En sondaki kartı seçer'],
                        ['key' => 'card_prev',   'label' => 'SOL YÖN',         'desc' => 'Bir önceki karta geçer'],
                        ['key' => 'card_next',   'label' => 'SAĞ YÖN',         'desc' => 'Bir sonraki karta geçer'],
                        ['key' => 'ok',          'label' => 'OK',              'desc' => 'Kartın içine girer, ürün seçimi başlar'],
                        ['key' => 'cancel',      'label' => 'CANCEL',          'desc' => 'Ürün seçiminden çıkar / seçimi tamamen iptal eder'],
                        ['key' => 'item_prev',   'label' => 'SELECT PREVIOUS', 'desc' => 'Bir önceki ürünü seçer'],
                        ['key' => 'item_next',   'label' => 'SELECT NEXT',     'desc' => 'Bir sonraki ürünü seçer'],
                        ['key' => 'done1',       'label' => 'DONE 1',          'desc' => '1. ürün için Hazır basar'],
                        ['key' => 'done2',       'label' => 'DONE 2',          'desc' => '2. ürün için Hazır basar'],
                        ['key' => 'done3',       'label' => 'DONE 3',          'desc' => '3. ürün için Hazır basar'],
                        ['key' => 'done4',       'label' => 'PARK',            'desc' => '4. ürün için Hazır basar'],
                        ['key' => 'select_done', 'label' => 'SELECT/DONE',     'desc' => 'Ürün seçiliyse ona Hazır, kart seçiliyse kartı komple Hazır yapar'],
                        ['key' => 'recall',      'label' => 'RECALL',          'desc' => 'Son Hazır işaretini süre içinde geri çağırır'],
                    ];
                    $kbdDefaults = [
                        'home' => 'a', 'end' => '1', 'card_prev' => 'c', 'card_next' => '3',
                        'item_prev' => '2', 'item_next' => 'b', 'ok' => '7', 'cancel' => '6',
                        'done1' => 'f', 'done2' => 'g', 'done3' => 'h', 'done4' => 'ı',
                        'select_done' => 'j', 'recall' => '5',
                    ];
                @endphp
                <div class="mb-4">
                    <p class="text-sm text-gray-500">
                        Micros MBB-20 klavye tuşlarının Mutfak Ekranı (Symphony POS) aksiyonlarıyla eşleşmesini yönetin.
                        Klavye USB ile bağlıyken her tuş tek karakter gönderir; aşağıdaki alanlara tıklayıp yeni tuşa basarak eşleşmeyi değiştirebilirsiniz.
                    </p>
                </div>
                <form action="{{ route('admin.settings.update') }}?tab=diger" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_kbd_only" value="1">
                    <div class="rounded-lg border border-gray-200 overflow-hidden mb-4">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 w-44">Tuş (MBB-20)</th>
                                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500">İşlev</th>
                                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500 w-28">Karakter</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($kbdRows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 font-semibold text-gray-800 whitespace-nowrap">{{ $row['label'] }}</td>
                                    <td class="px-4 py-2 text-gray-500 text-xs">{{ $row['desc'] }}</td>
                                    <td class="px-4 py-2">
                                        <input type="text" name="sc_{{ $row['key'] }}" id="sc_{{ $row['key'] }}"
                                               data-default="{{ $kbdDefaults[$row['key']] }}"
                                               value="{{ old('sc_'.$row['key'], $settings['kitchen_sc_'.$row['key']]) }}"
                                               maxlength="10" readonly
                                               class="kbd-capture w-full border border-gray-300 rounded px-2 py-1 text-center font-mono font-bold text-sm cursor-pointer focus:ring-2 focus:ring-slate-400 bg-gray-50">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="sc_recall_window" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-rotate-left mr-1"></i>RECALL Geri Çağırma Süresi (saniye)
                            </label>
                            <input type="number" name="sc_recall_window" id="sc_recall_window" min="5" max="600"
                                   value="{{ old('sc_recall_window', $settings['kitchen_sc_recall_window']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-slate-400">
                            <p class="text-xs text-gray-400 mt-1">Bu süre geçtikten sonra son yapılan Hazır işlemi geri çağrılamaz.</p>
                        </div>
                        <div class="flex items-end">
                            <button type="button" id="kbd-reset" class="py-2 px-4 border border-gray-300 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">
                                <i class="fas fa-arrow-rotate-left mr-1"></i>Tuşları Varsayılana Döndür
                            </button>
                        </div>
                    </div>
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 mb-4 text-xs text-slate-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Nasıl çalışır:</strong> Bir karakter alanına tıklayın, sonra MBB-20 üzerindeki tuşa basın — gönderilen karakter alana yazılır.
                        Kaydettiğinizde Mutfak Ekranı bu tuşlarla çalışır. TABLE DETAIL, ORDER STARTED, ALLDAY ve ALLDAY CONDS kullanılmadığı için eşleştirilmedi.
                    </div>
                    <button type="submit" class="py-2.5 px-5 bg-slate-600 text-white font-bold rounded-lg hover:bg-slate-700 transition text-sm">
                        <i class="fas fa-save mr-2"></i>Mutfak Kısayollarını Kaydet
                    </button>
                </form>
                <script>
                    (function () {
                        document.querySelectorAll('.kbd-capture').forEach(function (inp) {
                            inp.addEventListener('keydown', function (e) {
                                if (e.key === 'Tab') return;
                                e.preventDefault();
                                if (e.ctrlKey || e.altKey || e.metaKey) return;
                                if (e.key === 'Escape') { inp.blur(); return; }
                                inp.value = e.key.length === 1 ? e.key.toLocaleLowerCase('tr') : e.key;
                            });
                            inp.addEventListener('click', function () { inp.select(); });
                        });
                        var resetBtn = document.getElementById('kbd-reset');
                        if (resetBtn) resetBtn.addEventListener('click', function () {
                            document.querySelectorAll('.kbd-capture').forEach(function (inp) { inp.value = inp.dataset.default; });
                        });
                    })();
                </script>
                @endif

            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const serverEl = document.getElementById('admin-server-clock');
        if (!serverEl) return;
        const browserEl = document.getElementById('admin-browser-clock');
        const diffEl    = document.getElementById('admin-clock-diff');
        const dbEl       = document.getElementById('admin-db-clock');
        const dbEpoch    = (dbEl && dbEl.dataset.dbNow) ? (Date.parse(dbEl.dataset.dbNow) || null) : null;
        const serverEpoch = Date.parse(serverEl.dataset.serverNow) || Date.now();
        const pageLoad    = Date.now();
        const fmt = d => d.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const tick = () => {
            const now = Date.now();
            const serverNow = new Date(serverEpoch + (now - pageLoad));
            serverEl.textContent = fmt(serverNow);
            if (dbEl && dbEpoch) dbEl.textContent = fmt(new Date(dbEpoch + (now - pageLoad)));
            if (browserEl) browserEl.textContent = fmt(new Date(now));
            if (diffEl) {
                const s = Math.round((serverNow.getTime() - now) / 1000);
                const a = Math.abs(s);
                const txt = a >= 60 ? Math.floor(a / 60) + ' dk ' + (a % 60) + ' sn' : a + ' sn';
                diffEl.textContent = 'Fark: ' + (s >= 0 ? '+' : '−') + txt;
                diffEl.classList.toggle('text-red-600', a >= 30);
                diffEl.classList.toggle('text-emerald-600', a < 30);
            }
        };
        tick();
        setInterval(tick, 1000);
    })();
</script>
@endsection
