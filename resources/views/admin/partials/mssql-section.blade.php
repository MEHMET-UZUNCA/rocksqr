{{-- 
    Generic MSSQL bağlantı/sorgu bölümü.
    Parametreler:
        $section   string  - 'product' | 'kds' | 'bds'
        $prefix    string  - input id/name prefix (örn. 'mssql', 'mssql_kds', 'mssql_bds')
        $title     string  - başlık
        $icon      string  - fontawesome ikon class
        $color     string  - renk adı (sadece bilgilendirme amaçlı)
        $showRvc   bool    - ürün için RVC filtresi gösterilsin mi
        $queryHint string  - sorgu altında gösterilecek ipucu
        $aliasList bool    - ürün alias açıklamasını göster
        $settings  array   - tüm settings dizisi
--}}
<h3 class="text-lg font-bold text-gray-800 mb-4">
    <i class="fas {{ $icon }} mr-2 text-sky-600"></i>{{ $title }}
</h3>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <div>
        <label for="{{ $prefix }}_host" class="block text-sm font-semibold text-gray-700 mb-2">Host / IP</label>
        <input type="text" name="{{ $prefix }}_host" id="{{ $prefix }}_host"
            value="{{ old($prefix.'_host', $settings[$prefix.'_host'] ?? '') }}"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
            placeholder="192.168.0.9">
    </div>
    <div>
        <label for="{{ $prefix }}_port" class="block text-sm font-semibold text-gray-700 mb-2">Port</label>
        <input type="text" name="{{ $prefix }}_port" id="{{ $prefix }}_port"
            value="{{ old($prefix.'_port', $settings[$prefix.'_port'] ?? '1433') }}"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
            placeholder="1433">
    </div>
</div>

<div class="mb-4">
    <label for="{{ $prefix }}_database" class="block text-sm font-semibold text-gray-700 mb-2">Veritabanı</label>
    <input type="text" name="{{ $prefix }}_database" id="{{ $prefix }}_database"
        value="{{ old($prefix.'_database', $settings[$prefix.'_database'] ?? '') }}"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
        placeholder="Datastore">
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <div>
        <label for="{{ $prefix }}_username" class="block text-sm font-semibold text-gray-700 mb-2">Kullanıcı Adı</label>
        <input type="text" name="{{ $prefix }}_username" id="{{ $prefix }}_username"
            value="{{ old($prefix.'_username', $settings[$prefix.'_username'] ?? '') }}"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
            placeholder="rocks">
    </div>
    <div>
        <label for="{{ $prefix }}_password" class="block text-sm font-semibold text-gray-700 mb-2">Şifre</label>
        <input type="password" name="{{ $prefix }}_password" id="{{ $prefix }}_password"
            value="{{ old($prefix.'_password', $settings[$prefix.'_password'] ?? '') }}"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
            placeholder="••••••••">
    </div>
</div>

@if($showRvc ?? false)
    <hr class="my-5 border-gray-200">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <label for="mssql_income_center_filter" class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="fas fa-filter mr-1 text-emerald-600"></i>RVC / Gelir Merkezi Filtresi
                <span class="ml-1 text-xs font-normal text-gray-400">(opsiyonel)</span>
            </label>
            <input type="text" name="mssql_income_center_filter" id="mssql_income_center_filter"
                value="{{ old('mssql_income_center_filter', $settings['mssql_income_center_filter'] ?? '') }}"
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                placeholder="Boş = filtre yok">
            <p class="text-xs text-gray-500 mt-1">
                <strong>Boş bırakırsanız</strong> filtre çalışmaz, tüm RVC'ler getirilir.<br>
                <strong>Yazarsanız</strong> sadece eşleşen RVC değerine sahip ürünler getirilir.<br>
                <span class="text-emerald-700">Joker karakter:</span>
                <code class="bg-gray-100 px-1 rounded">POOL*</code>,
                <code class="bg-gray-100 px-1 rounded">*BAR</code>,
                <code class="bg-gray-100 px-1 rounded">*POOL*</code>
            </p>
        </div>
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
            <p class="text-sm text-emerald-800 font-semibold mb-1"><i class="fas fa-lightbulb mr-1"></i>İpucu</p>
            <p class="text-xs text-emerald-700">Tüm ürün/fiyat/grup verisi aşağıdaki <strong>Özel SQL Sorgusu</strong> ile çekilir.</p>
        </div>
    </div>
@endif

<div class="mb-4">
    <label for="{{ $prefix }}_{{ $section === 'product' ? 'custom_query' : 'query' }}" class="block text-sm font-semibold text-gray-700 mb-2">
        <i class="fas fa-code mr-1 text-sky-600"></i>SQL Sorgusu
        @if($section === 'product')<span class="text-red-500 ml-1">*</span>@endif
    </label>
    @php
        $queryName = $section === 'product' ? $prefix.'_custom_query' : $prefix.'_query';
    @endphp
    <textarea name="{{ $queryName }}" id="{{ $queryName }}" rows="10"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 font-mono text-sm focus:ring-2 focus:ring-gold focus:border-gold"
        placeholder="SELECT ...">{{ old($queryName, $settings[$queryName] ?? '') }}</textarea>
    <p class="text-xs text-gray-500 mt-1">{{ $queryHint ?? '' }}</p>

    @if($aliasList ?? false)
        <div class="mt-2 p-3 bg-gray-50 border border-gray-200 rounded text-xs text-gray-600">
            <p class="font-semibold mb-1">Sorgu sonucunda dönmesi beklenen kolonlar (alias):</p>
            <ul class="space-y-0.5 ml-4 list-disc">
                <li><code class="bg-white px-1 rounded">external_id</code> / <code class="bg-white px-1 rounded">ProductCode</code> — ürün benzersiz kimliği (zorunlu)</li>
                <li><code class="bg-white px-1 rounded">name</code> / <code class="bg-white px-1 rounded">ProductName</code> — ürün adı (zorunlu)</li>
                <li><code class="bg-white px-1 rounded">price</code> / <code class="bg-white px-1 rounded">Price</code> — fiyat (zorunlu)</li>
                <li><code class="bg-white px-1 rounded">family_group</code> / <code class="bg-white px-1 rounded">FamilyGroup</code> — kategori adı</li>
                <li><code class="bg-white px-1 rounded">rvc</code> / <code class="bg-white px-1 rounded">PriceLevel</code> — gelir merkezi (filtre için)</li>
            </ul>
        </div>
    @endif
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
    <button type="button" id="{{ $prefix }}_testBtn"
        onclick="testConnection('{{ $section }}', '{{ $prefix }}')"
        class="w-full py-2.5 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition">
        <i class="fas fa-plug mr-2"></i>Bağlantıyı Test Et
    </button>
    <button type="button" id="{{ $prefix }}_previewBtn"
        onclick="previewQuery('{{ $section }}', '{{ $prefix }}', '{{ $queryName }}')"
        class="w-full py-2.5 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition">
        <i class="fas fa-eye mr-2"></i>Sorguyu Önizle
    </button>
</div>
<div id="{{ $prefix }}_testResult" class="mt-3 hidden">
    <div id="{{ $prefix }}_testResultContent" class="p-4 rounded-lg text-sm font-medium"></div>
</div>
