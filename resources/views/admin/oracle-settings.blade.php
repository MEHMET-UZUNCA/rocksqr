@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-database mr-2 text-orange-500"></i>Oracle Veritabanı Ayarları
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

                <div class="mb-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                    <p class="text-sm text-orange-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        Oracle POS sistemiyle senkronizasyon için bağlantı bilgilerini girin.
                        Şifre şifreli olarak saklanır. Sync sayfasından Oracle'dan veri çekebilirsiniz.
                    </p>
                </div>

                <form action="{{ route('admin.oracle-settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <h3 class="text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-server mr-1"></i>Bağlantı Bilgileri
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="oracle_host" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-server mr-1"></i>Host / IP
                            </label>
                            <input type="text" name="oracle_host" id="oracle_host"
                                   value="{{ old('oracle_host', $settings['oracle_host']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="192.168.1.100">
                        </div>
                        <div>
                            <label for="oracle_port" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-plug mr-1"></i>Port
                            </label>
                            <input type="text" name="oracle_port" id="oracle_port"
                                   value="{{ old('oracle_port', $settings['oracle_port']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="1521">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="oracle_service" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-cogs mr-1"></i>Service Name (SID)
                        </label>
                        <input type="text" name="oracle_service" id="oracle_service"
                               value="{{ old('oracle_service', $settings['oracle_service']) }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                               placeholder="ORCL">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="oracle_username" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user mr-1"></i>Kullanıcı Adı
                            </label>
                            <input type="text" name="oracle_username" id="oracle_username"
                                   value="{{ old('oracle_username', $settings['oracle_username']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="db_user">
                        </div>
                        <div>
                            <label for="oracle_password" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-lock mr-1"></i>Şifre
                            </label>
                            <input type="password" name="oracle_password" id="oracle_password"
                                   value="{{ old('oracle_password', $settings['oracle_password']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <hr class="my-6 border-gray-200">

                    <h3 class="text-sm font-bold text-gray-700 mb-3">
                        <i class="fas fa-table mr-1"></i>Tablo ve Kolon Eşleme
                    </h3>

                    <div class="mb-6">
                        <label for="oracle_table" class="block text-sm font-semibold text-gray-700 mb-2">Tablo Adı</label>
                        <input type="text" name="oracle_table" id="oracle_table"
                               value="{{ old('oracle_table', $settings['oracle_table']) }}"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                               placeholder="PRODUCTS">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="oracle_column_id" class="block text-sm font-semibold text-gray-700 mb-2">ID Kolonu</label>
                            <input type="text" name="oracle_column_id" id="oracle_column_id"
                                   value="{{ old('oracle_column_id', $settings['oracle_column_id']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="ID">
                        </div>
                        <div>
                            <label for="oracle_column_name" class="block text-sm font-semibold text-gray-700 mb-2">Ürün Adı Kolonu</label>
                            <input type="text" name="oracle_column_name" id="oracle_column_name"
                                   value="{{ old('oracle_column_name', $settings['oracle_column_name']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="NAME">
                        </div>
                        <div>
                            <label for="oracle_column_price" class="block text-sm font-semibold text-gray-700 mb-2">Fiyat Kolonu</label>
                            <input type="text" name="oracle_column_price" id="oracle_column_price"
                                   value="{{ old('oracle_column_price', $settings['oracle_column_price']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="PRICE">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="oracle_column_category" class="block text-sm font-semibold text-gray-700 mb-2">Ana Kategori Kolonu</label>
                            <input type="text" name="oracle_column_category" id="oracle_column_category"
                                   value="{{ old('oracle_column_category', $settings['oracle_column_category']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="CATEGORY">
                        </div>
                        <div>
                            <label for="oracle_column_subcategory" class="block text-sm font-semibold text-gray-700 mb-2">Alt Kategori Kolonu</label>
                            <input type="text" name="oracle_column_subcategory" id="oracle_column_subcategory"
                                   value="{{ old('oracle_column_subcategory', $settings['oracle_column_subcategory']) }}"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-gold focus:border-gold"
                                   placeholder="SUBCATEGORY">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full py-3 bg-orange-500 text-white font-bold rounded-lg hover:bg-orange-600 transition">
                        <i class="fas fa-save mr-2"></i>Oracle Ayarlarını Kaydet
                    </button>
                </form>

                <hr class="my-6 border-gray-200">

                <!-- Test Connection -->
                <div>
                    <button type="button" id="testConnectionBtn" onclick="testConnection()"
                            class="w-full py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition">
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

    fetch('{{ route("admin.oracle-settings.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            oracle_host: document.getElementById('oracle_host').value,
            oracle_port: document.getElementById('oracle_port').value,
            oracle_service: document.getElementById('oracle_service').value,
            oracle_username: document.getElementById('oracle_username').value,
            oracle_password: document.getElementById('oracle_password').value
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
    .catch(error => {
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
