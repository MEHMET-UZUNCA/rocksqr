@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-server mr-2 text-sky-600"></i>MSSQL Veritabanı Ayarları
                </h2>

                <div class="mb-6 p-4 bg-sky-50 border border-sky-200 rounded-lg">
                    <p class="text-sm text-sky-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        Symphony Restaurant MSSQL veritabanısından ürün, ürün grubu, fiyat ve gelir merkezi verilerini çekmek için bağlantı bilgilerini girin.
                        Şifre şifreli olarak saklanır. Sync sayfasından önizleme ile veri karşılaştırabilirsiniz.
                    </p>
                </div>

                <form action="{{ route('admin.mssql-settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <h3 class="text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-server mr-1"></i>Bağlantı Bilgileri
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="mssql_host" class="block text-sm font-semibold text-gray-700 mb-2">Host / IP</label>
                            <input type="text" name="mssql_host" id="mssql_host" value="{{ old('mssql_host', $settings['mssql_host']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="192.168.0.9">
                        </div>
                        <div>
                            <label for="mssql_port" class="block text-sm font-semibold text-gray-700 mb-2">Port</label>
                            <input type="text" name="mssql_port" id="mssql_port" value="{{ old('mssql_port', $settings['mssql_port']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="1433">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="mssql_database" class="block text-sm font-semibold text-gray-700 mb-2">Veritabanı</label>
                        <input type="text" name="mssql_database" id="mssql_database" value="{{ old('mssql_database', $settings['mssql_database']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="Datastore">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="mssql_username" class="block text-sm font-semibold text-gray-700 mb-2">Kullanıcı Adı</label>
                            <input type="text" name="mssql_username" id="mssql_username" value="{{ old('mssql_username', $settings['mssql_username']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="rocks">
                        </div>
                        <div>
                            <label for="mssql_password" class="block text-sm font-semibold text-gray-700 mb-2">Şifre</label>
                            <input type="password" name="mssql_password" id="mssql_password" value="{{ old('mssql_password', $settings['mssql_password']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="••••••••">
                        </div>
                    </div>

                    <hr class="my-6 border-gray-200">

                    <h3 class="text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-table mr-1"></i>Tablo ve Kolon Eşleme
                    </h3>

                    <div class="mb-6">
                        <label for="mssql_table" class="block text-sm font-semibold text-gray-700 mb-2">Tablo / View Adı</label>
                        <input type="text" name="mssql_table" id="mssql_table" value="{{ old('mssql_table', $settings['mssql_table']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="dbo.Products">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="mssql_column_id" class="block text-sm font-semibold text-gray-700 mb-2">ID Kolonu</label>
                            <input type="text" name="mssql_column_id" id="mssql_column_id" value="{{ old('mssql_column_id', $settings['mssql_column_id']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="ID">
                        </div>
                        <div>
                            <label for="mssql_column_name" class="block text-sm font-semibold text-gray-700 mb-2">Ürün Adı Kolonu</label>
                            <input type="text" name="mssql_column_name" id="mssql_column_name" value="{{ old('mssql_column_name', $settings['mssql_column_name']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="NAME">
                        </div>
                        <div>
                            <label for="mssql_column_price" class="block text-sm font-semibold text-gray-700 mb-2">Fiyat Kolonu</label>
                            <input type="text" name="mssql_column_price" id="mssql_column_price" value="{{ old('mssql_column_price', $settings['mssql_column_price']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="PRICE">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label for="mssql_column_group" class="block text-sm font-semibold text-gray-700 mb-2">Ürün Grubu Kolonu</label>
                            <input type="text" name="mssql_column_group" id="mssql_column_group" value="{{ old('mssql_column_group', $settings['mssql_column_group']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="PRODUCT_GROUP">
                        </div>
                        <div>
                            <label for="mssql_column_subgroup" class="block text-sm font-semibold text-gray-700 mb-2">Alt Grup Kolonu</label>
                            <input type="text" name="mssql_column_subgroup" id="mssql_column_subgroup" value="{{ old('mssql_column_subgroup', $settings['mssql_column_subgroup']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="SUBGROUP">
                        </div>
                        <div>
                            <label for="mssql_column_income_center" class="block text-sm font-semibold text-gray-700 mb-2">Gelir Merkezi / RVC Kolonu</label>
                            <input type="text" name="mssql_column_income_center" id="mssql_column_income_center" value="{{ old('mssql_column_income_center', $settings['mssql_column_income_center']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="RVC">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="mssql_income_center_filter" class="block text-sm font-semibold text-gray-700 mb-2">RVC Filtresi</label>
                            <input type="text" name="mssql_income_center_filter" id="mssql_income_center_filter" value="{{ old('mssql_income_center_filter', $settings['mssql_income_center_filter']) }}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold" placeholder="POOLBAR">
                            <p class="text-xs text-gray-400 mt-1">Tablo yapısı uygunsa sadece bu RVC'ye ait ürün ve fiyatlar çekilir.</p>
                        </div>
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                            <p class="text-sm text-amber-800 font-semibold mb-1">Karmaşık Symphony yapısı için</p>
                            <p class="text-xs text-amber-700">Eğer ürün, fiyat ve RVC farklı tablolardaysa aşağıdaki özel sorgu alanını kullanın. Sorgu sonucu en az <strong>external_id</strong>, <strong>name</strong> ve <strong>price</strong> döndürmeli.</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="mssql_custom_query" class="block text-sm font-semibold text-gray-700 mb-2">Özel SQL Sorgusu</label>
                        <textarea name="mssql_custom_query" id="mssql_custom_query" rows="8" class="w-full border border-gray-300 rounded-lg px-4 py-2 font-mono text-sm focus:ring-2 focus:ring-gold focus:border-gold" placeholder="SELECT item_id AS external_id, item_name AS name, price AS price, major_group AS product_group, sub_group AS subgroup, rvc AS rvc FROM ... WHERE rvc = 'POOLBAR'">{{ old('mssql_custom_query', $settings['mssql_custom_query']) }}</textarea>
                        <p class="text-xs text-gray-400 mt-1">Alias destekleri: <strong>external_id</strong>, <strong>name</strong>, <strong>price</strong>, <strong>product_group</strong>, <strong>subgroup</strong>, <strong>rvc</strong>.</p>
                    </div>

                    <button type="submit" class="w-full py-3 bg-sky-600 text-white font-bold rounded-lg hover:bg-sky-700 transition">
                        <i class="fas fa-save mr-2"></i>MSSQL Ayarlarını Kaydet
                    </button>
                </form>

                <hr class="my-6 border-gray-200">

                <div>
                    <button type="button" id="testConnectionBtn" onclick="testConnection()" class="w-full py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-plug mr-2"></i>Bağlantıyı Test Et
                    </button>
                    <div id="testResult" class="mt-4 hidden">
                        <div id="testResultContent" class="p-4 rounded-lg text-sm font-medium"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testConnection() {
    const btn = document.getElementById('testConnectionBtn');
    const resultDiv = document.getElementById('testResult');
    const resultContent = document.getElementById('testResultContent');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Test ediliyor...';
    resultDiv.classList.add('hidden');

    fetch('{{ route("admin.mssql-settings.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            mssql_host: document.getElementById('mssql_host').value,
            mssql_port: document.getElementById('mssql_port').value,
            mssql_database: document.getElementById('mssql_database').value,
            mssql_username: document.getElementById('mssql_username').value,
            mssql_password: document.getElementById('mssql_password').value
        })
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.classList.remove('hidden');
        if (data.success) {
            resultContent.className = 'p-4 rounded-lg text-sm font-medium bg-green-50 border border-green-300 text-green-800';
            resultContent.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
        } else {
            resultContent.className = 'p-4 rounded-lg text-sm font-medium bg-red-50 border border-red-300 text-red-800';
            resultContent.innerHTML = '<i class="fas fa-times-circle mr-2"></i>' + data.message;
        }
    })
    .catch(() => {
        resultDiv.classList.remove('hidden');
        resultContent.className = 'p-4 rounded-lg text-sm font-medium bg-red-50 border border-red-300 text-red-800';
        resultContent.innerHTML = '<i class="fas fa-times-circle mr-2"></i>Bağlantı testi sırasında hata oluştu.';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug mr-2"></i>Bağlantıyı Test Et';
    });
}
</script>
@endsection