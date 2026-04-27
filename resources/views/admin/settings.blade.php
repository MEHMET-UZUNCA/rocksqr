@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-cog mr-2 text-gold"></i>Ayarlar
                </h2>

                @if(session('success'))
                    <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-800 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Logo SVG -->
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
                                    Logoyu kaldır
                                </label>
                            </div>
                        @else
                            <p class="text-sm text-gray-400 mb-2">Henüz logo yüklenmedi.</p>
                        @endif

                        <input type="file" name="logo_svg" accept=".svg"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-gold/20 file:text-yellow-800 hover:file:bg-gold/30">
                        <p class="text-xs text-gray-400 mt-1">Sadece SVG formatı, maks 512KB</p>
                    </div>

                    <!-- Site Title -->
                    <div class="mb-6">
                        <label for="site_title" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-heading mr-1"></i>Site Başlığı (Title)
                        </label>
                        <input type="text" name="site_title" id="site_title"
                               value="{{ old('site_title', $settings['site_title']) }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                               placeholder="Rocks Hotel QR Menü">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="bar_screen_title" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-wine-glass mr-1"></i>Bar Ekranı Başlığı
                            </label>
                            <input type="text" name="bar_screen_title" id="bar_screen_title"
                                   value="{{ old('bar_screen_title', $settings['bar_screen_title']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="KDS - Bar Ekrani">
                        </div>
                        <div>
                            <label for="kitchen_screen_title" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-utensils mr-1"></i>Mutfak Ekranı Başlığı
                            </label>
                            <input type="text" name="kitchen_screen_title" id="kitchen_screen_title"
                                   value="{{ old('kitchen_screen_title', $settings['kitchen_screen_title']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="POOL Mutfak Ekrani">
                        </div>
                    </div>

                    <!-- Meta Description -->
                    <div class="mb-6">
                        <label for="meta_description" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-align-left mr-1"></i>Meta Açıklama (Description)
                        </label>
                        <textarea name="meta_description" id="meta_description" rows="3"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                  placeholder="Site açıklaması...">{{ old('meta_description', $settings['meta_description']) }}</textarea>
                        <p class="text-xs text-gray-400 mt-1">Maks 500 karakter</p>
                    </div>

                    <!-- Meta Keywords -->
                    <div class="mb-6">
                        <label for="meta_keywords" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-tags mr-1"></i>Meta Anahtar Kelimeler (Keywords)
                        </label>
                        <input type="text" name="meta_keywords" id="meta_keywords"
                               value="{{ old('meta_keywords', $settings['meta_keywords']) }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                               placeholder="hotel, menü, qr, rocks">
                        <p class="text-xs text-gray-400 mt-1">Virgülle ayırın</p>
                    </div>

                    <!-- Product Unique IDs Info -->
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="font-semibold text-blue-800 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>Ürün Benzersiz ID Bilgisi
                        </h3>
                        <p class="text-sm text-blue-700">
                            Her ürünün veritabanında benzersiz bir <code class="bg-blue-100 px-1 rounded">id</code> değeri vardır.
                            <code class="bg-blue-100 px-1 rounded">mssql_id</code> alanı harici sistem entegrasyonu için kullanılabilir.
                        </p>
                    </div>

                    <button type="submit"
                            class="w-full py-3 bg-primary text-white font-bold rounded-lg hover:bg-light-primary transition">
                        <i class="fas fa-save mr-2"></i>Kaydet
                    </button>
                </form>

                <form action="{{ route('admin.settings.update') }}" method="POST" class="mt-8">
                    <input type="hidden" name="_display_only" value="bar">
                    @csrf
                    @method('PUT')

                    <h3 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                            <i class="fas fa-wine-glass text-amber-600"></i>
                        </span>
                        Bar Ekran Ayarları
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="bar_screen_title" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-heading mr-1"></i>Bar Ekranı Başlığı
                            </label>
                            <input type="text" name="bar_screen_title" id="bar_screen_title_bar"
                                   value="{{ old('bar_screen_title', $settings['bar_screen_title']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="KDS - Bar Ekrani">
                        </div>
                        <div>
                            <label for="bar_completed_display" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-check-circle mr-1"></i>Tamamlanan Sipariş Sayısı
                            </label>
                            <input type="number" min="1" max="100" step="1" name="bar_completed_display" id="bar_completed_display"
                                   value="{{ old('bar_completed_display', $settings['bar_completed_display']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold">
                            <p class="text-xs text-gray-400 mt-1">"Sipariş Hazır" ve "Tamamlanan" alanında kaç sipariş görüneceğini belirler (1–100).</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="order_ready_display" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-list-check mr-1"></i>Sipariş Hazır Alanı: Görüntülenecek Adet
                            </label>
                            <input type="number" min="1" max="200" step="1" name="order_ready_display" id="order_ready_display"
                                   value="{{ old('order_ready_display', $settings['order_ready_display']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold">
                            <p class="text-xs text-gray-400 mt-1">Sipariş hazır/tamamlandı alanında kaç sipariş listeleneceğini belirler.</p>
                        </div>
                        <div>
                            <label for="order_profit_display" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-chart-line mr-1"></i>Sipariş Karı: Görüntülenecek Adet
                            </label>
                            <input type="number" min="1" max="200" step="1" name="order_profit_display" id="order_profit_display"
                                   value="{{ old('order_profit_display', $settings['order_profit_display']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold">
                            <p class="text-xs text-gray-400 mt-1">Sipariş kar/gelir raporunda kaç kayıt gösterileceğini belirler.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="bar_cards_per_page" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-layer-group mr-1"></i>Bar KDS: Sayfa Başına Kart Sayısı
                            </label>
                            <input type="number" min="1" max="50" step="1" name="bar_cards_per_page" id="bar_cards_per_page"
                                   value="{{ old('bar_cards_per_page', $settings['bar_cards_per_page']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold">
                            <p class="text-xs text-gray-400 mt-1">Bar ekranında bir sayfada kaç kart gösterileceği (1–50). Fazlası sonraki sayfalara taşınır.</p>
                        </div>
                    </div>

                    <button type="submit"
                            class="py-3 px-6 bg-amber-500 text-white font-bold rounded-lg hover:bg-amber-600 transition">
                        <i class="fas fa-save mr-2"></i>Bar Ekran Ayarlarını Kaydet
                    </button>
                </form>

                <hr class="my-8 border-gray-200">

                <form action="{{ route('admin.settings.update') }}" method="POST">
                    <input type="hidden" name="_display_only" value="kitchen">
                    @csrf
                    @method('PUT')

                    <h3 class="text-base font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                            <i class="fas fa-utensils text-orange-600"></i>
                        </span>
                        Kitchen Ekran Ayarları
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="kitchen_screen_title" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-heading mr-1"></i>Mutfak Ekranı Başlığı
                            </label>
                            <input type="text" name="kitchen_screen_title" id="kitchen_screen_title_settings"
                                   value="{{ old('kitchen_screen_title', $settings['kitchen_screen_title']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="POOL Mutfak Ekrani">
                        </div>
                        <div>
                            <label for="kitchen_completed_display" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-check-circle mr-1"></i>Tamamlanan Son Sipariş Sayısı
                            </label>
                            <input type="number" min="1" max="100" step="1" name="kitchen_completed_display" id="kitchen_completed_display"
                                   value="{{ old('kitchen_completed_display', $settings['kitchen_completed_display']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold">
                            <p class="text-xs text-gray-400 mt-1">"Hazırlandı" olarak işaretlenen son kaç sipariş görüneceğini belirler (1–100).</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="waiter_call_display" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-bell mr-1"></i>Garson Çağrıları: Görüntülenecek Adet
                            </label>
                            <input type="number" min="1" max="200" step="1" name="waiter_call_display" id="waiter_call_display"
                                   value="{{ old('waiter_call_display', $settings['waiter_call_display']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold">
                            <p class="text-xs text-gray-400 mt-1">Garson çağrıları listesinde kaç kayıt gösterileceğini belirler.</p>
                        </div>
                        <div>
                            <label for="ready_undo_seconds" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-rotate-left mr-1"></i>Geri Alma Süresi (saniye)
                            </label>
                            <input type="number" min="5" max="600" step="1" name="ready_undo_seconds" id="ready_undo_seconds"
                                   value="{{ old('ready_undo_seconds', $settings['ready_undo_seconds']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold">
                            <p class="text-xs text-gray-400 mt-1">"Geri Al" butonu bu süre boyunca çalışır. Sonrasında kayıt kalıcılaşır.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="kitchen_cards_per_page" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-layer-group mr-1"></i>Mutfak KDS: Sayfa Başına Kart Sayısı
                            </label>
                            <input type="number" min="1" max="50" step="1" name="kitchen_cards_per_page" id="kitchen_cards_per_page"
                                   value="{{ old('kitchen_cards_per_page', $settings['kitchen_cards_per_page']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold">
                            <p class="text-xs text-gray-400 mt-1">Bir sayfada kaç hesap kartı gösterileceği (1–50). Fazlası sonraki sayfalara taşınır.</p>
                        </div>
                    </div>

                    <button type="submit"
                            class="py-3 px-6 bg-orange-500 text-white font-bold rounded-lg hover:bg-orange-600 transition">
                        <i class="fas fa-save mr-2"></i>Kitchen Ekran Ayarlarını Kaydet
                    </button>
                </form>

                <!-- Screen Clear Time Separate Form -->
                <form action="{{ route('admin.settings.update') }}" method="POST" class="mt-8 flex items-end gap-4">
                    <input type="hidden" name="_clear_time_only" value="1">
                    @csrf
                    @method('PUT')
                    <div class="flex-1">
                        <label for="screen_clear_time" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-clock mr-1"></i>Ekran Temizleme Saati (Mutfak/Bar)
                        </label>
                        <input type="time" name="screen_clear_time" id="screen_clear_time"
                               value="{{ old('screen_clear_time', $settings['screen_clear_time'] ?? '14:00') }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                               required>
                        <p class="text-xs text-gray-400 mt-1">Her gün bu saatte mutfak ve bar ekranları otomatik temizlenir.</p>
                    </div>
                    <button type="submit"
                            class="py-3 px-6 bg-gold text-white font-bold rounded-lg hover:bg-yellow-500 transition whitespace-nowrap">
                        <i class="fas fa-save mr-2"></i>Temizleme Saatini Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
