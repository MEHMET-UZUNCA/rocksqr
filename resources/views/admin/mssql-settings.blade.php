@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-server mr-2 text-sky-600"></i>MSSQL Veritabanı Ayarları
                </h2>

                <div class="mb-6 p-4 bg-sky-50 border border-sky-200 rounded-lg">
                    <p class="text-sm text-sky-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        İki ayrı bağlantı yapılandırılır: <strong>Ürün</strong> (Symphony menüsü) ve <strong>KDS</strong> (Mutfak ekranı sorguları).
                        Her bağlantı için ayrı host, kullanıcı ve özel SQL sorgusu girilebilir. Şifreler şifreli saklanır.
                    </p>
                </div>

                <!-- Tab Nav -->
                <div class="flex flex-wrap gap-1 mb-6 border-b border-gray-200">
                    <button type="button" data-tab="product" class="tab-btn active-tab px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 border-sky-600 text-sky-700 bg-sky-50">
                        <i class="fas fa-utensils mr-1"></i>Ürün (Symphony)
                    </button>
                    <button type="button" data-tab="kds" class="tab-btn px-4 py-2 text-sm font-semibold rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-sky-600 hover:bg-sky-50">
                        <i class="fas fa-fire mr-1"></i>KDS (Mutfak)
                    </button>
                </div>

                {{-- ============ ÜRÜN (Symphony) ============ --}}
                <div data-section="product" class="tab-pane">
                    <form action="{{ route('admin.mssql-settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="product">
                        @include('admin.partials.mssql-section', [
                            'section'    => 'product',
                            'prefix'     => 'mssql',
                            'title'      => 'Ürün (Symphony)',
                            'icon'       => 'fa-utensils',
                            'color'      => 'sky',
                            'showRvc'    => true,
                            'queryHint'  => 'Sorgu sonucundaki kolonlar (alias) önemlidir.',
                            'aliasList'  => true,
                            'settings'   => $settings,
                        ])
                        <button type="submit" class="w-full py-3 bg-sky-600 text-white font-bold rounded-lg hover:bg-sky-700 transition mt-6">
                            <i class="fas fa-save mr-2"></i>Ürün Ayarlarını Kaydet
                        </button>
                    </form>
                </div>

                {{-- ============ KDS ============ --}}
                <div data-section="kds" class="tab-pane hidden">
                    <form action="{{ route('admin.mssql-settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="section" value="kds">
                        @include('admin.partials.mssql-section', [
                            'section'    => 'kds',
                            'prefix'     => 'mssql_kds',
                            'title'      => 'KDS (Mutfak Ekranı)',
                            'icon'       => 'fa-fire',
                            'color'      => 'orange',
                            'showRvc'    => false,
                            'queryHint'  => 'KDS için açık siparişleri/üretim hattını döndüren SQL sorgusu.',
                            'aliasList'  => false,
                            'settings'   => $settings,
                        ])
                        <button type="submit" class="w-full py-3 bg-sky-600 text-white font-bold rounded-lg hover:bg-sky-700 transition mt-6">
                            <i class="fas fa-save mr-2"></i>KDS Ayarlarını Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Önizleme Modal -->
<div id="previewModal" class="fixed inset-0 z-50 hidden bg-black/60 p-2 sm:p-4">
    <div class="bg-white rounded-xl shadow-2xl flex flex-col mx-auto" style="width:98vw;height:96vh;max-width:none;">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 shrink-0">
            <h3 class="text-lg font-bold text-gray-800">
                <i class="fas fa-table mr-2 text-indigo-600"></i>SQL Sorgu Önizlemesi
                <span id="previewMeta" class="ml-2 text-sm font-normal text-gray-500"></span>
            </h3>
            <button type="button" onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-4 overflow-auto flex-1 min-h-0">
            <div id="previewLoading" class="text-center py-12 text-gray-500 hidden">
                <i class="fas fa-spinner fa-spin text-3xl mb-3"></i>
                <p>Sorgu çalıştırılıyor...</p>
            </div>
            <div id="previewError" class="hidden bg-red-50 border border-red-300 text-red-800 p-4 rounded-lg text-sm"></div>
            <div id="previewTableWrap" class="hidden overflow-auto border border-gray-200 rounded-lg w-full"></div>
        </div>
        <div class="p-3 border-t border-gray-200 flex justify-end gap-2 bg-gray-50 rounded-b-xl shrink-0">
            <button type="button" onclick="closePreviewModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Kapat</button>
        </div>
    </div>
</div>

<script>
// ===== Tab geçişleri =====
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active-tab', 'border-sky-600', 'text-sky-700', 'bg-sky-50');
            b.classList.add('border-transparent', 'text-gray-500');
        });
        btn.classList.add('active-tab', 'border-sky-600', 'text-sky-700', 'bg-sky-50');
        btn.classList.remove('border-transparent', 'text-gray-500');
        document.querySelectorAll('.tab-pane').forEach(p => {
            p.classList.toggle('hidden', p.dataset.section !== tab);
        });
    });
});

// Kayıt sonrası ilgili sekmeye dön
@if(session('active_tab'))
    document.querySelector('.tab-btn[data-tab="{{ session('active_tab') }}"]')?.click();
@endif

// ===== Test Bağlantısı =====
function testConnection(section, prefix) {
    const btn = document.getElementById(prefix + '_testBtn');
    const resultDiv = document.getElementById(prefix + '_testResult');
    const resultContent = document.getElementById(prefix + '_testResultContent');

    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Test ediliyor...';
    resultDiv.classList.add('hidden');

    fetch('{{ route("admin.mssql-settings.test") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            section: section,
            mssql_host: document.getElementById(prefix + '_host').value,
            mssql_port: document.getElementById(prefix + '_port').value,
            mssql_database: document.getElementById(prefix + '_database').value,
            mssql_username: document.getElementById(prefix + '_username').value,
            mssql_password: document.getElementById(prefix + '_password').value
        })
    })
    .then(r => r.json())
    .then(data => {
        resultDiv.classList.remove('hidden');
        resultContent.className = 'p-4 rounded-lg text-sm font-medium ' +
            (data.success ? 'bg-green-50 border border-green-300 text-green-800' : 'bg-red-50 border border-red-300 text-red-800');
        resultContent.innerHTML = (data.success ? '<i class="fas fa-check-circle mr-2"></i>' : '<i class="fas fa-times-circle mr-2"></i>') + (data.message || '');
    })
    .catch(() => {
        resultDiv.classList.remove('hidden');
        resultContent.className = 'p-4 rounded-lg text-sm font-medium bg-red-50 border border-red-300 text-red-800';
        resultContent.innerHTML = '<i class="fas fa-times-circle mr-2"></i>Bağlantı testi sırasında hata oluştu.';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
    });
}

// ===== Sorgu Önizleme =====
function previewQuery(section, prefix, queryFieldId) {
    const btn = document.getElementById(prefix + '_previewBtn');
    const modal = document.getElementById('previewModal');
    const loading = document.getElementById('previewLoading');
    const errorBox = document.getElementById('previewError');
    const tableWrap = document.getElementById('previewTableWrap');
    const meta = document.getElementById('previewMeta');

    const query = (document.getElementById(queryFieldId)?.value || '').trim();
    if (!query) {
        alert('Önce SQL Sorgusu alanına bir sorgu yazın.');
        return;
    }

    modal.classList.remove('hidden');
    loading.classList.remove('hidden');
    errorBox.classList.add('hidden');
    tableWrap.classList.add('hidden');
    tableWrap.innerHTML = '';
    meta.textContent = '';
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Yükleniyor...';

    fetch('{{ route("admin.mssql-settings.preview") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({
            section: section,
            mssql_host: document.getElementById(prefix + '_host').value,
            mssql_port: document.getElementById(prefix + '_port').value,
            mssql_database: document.getElementById(prefix + '_database').value,
            mssql_username: document.getElementById(prefix + '_username').value,
            mssql_password: document.getElementById(prefix + '_password').value,
            mssql_custom_query: query,
            limit: 100
        })
    })
    .then(r => r.json())
    .then(data => {
        loading.classList.add('hidden');
        if (!data.success) {
            errorBox.textContent = data.message || 'Bilinmeyen hata.';
            errorBox.className = 'bg-red-50 border border-red-300 text-red-800 p-4 rounded-lg text-sm';
            errorBox.classList.remove('hidden');
            return;
        }
        meta.textContent = `(${data.row_count} satır, max ${data.limit})`;
        if (!data.rows || data.rows.length === 0) {
            errorBox.textContent = 'Sorgu sonuç döndürmedi.';
            errorBox.className = 'bg-yellow-50 border border-yellow-300 text-yellow-800 p-4 rounded-lg text-sm';
            errorBox.classList.remove('hidden');
            return;
        }
        const cols = data.columns || Object.keys(data.rows[0]);
        let html = '<table class="min-w-full text-xs"><thead class="bg-gray-100 sticky top-0"><tr>';
        cols.forEach(c => {
            html += `<th class="px-3 py-2 text-left font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap">${escapeHtml(c)}</th>`;
        });
        html += '</tr></thead><tbody>';
        data.rows.forEach((row, idx) => {
            html += `<tr class="${idx % 2 ? 'bg-gray-50' : 'bg-white'} hover:bg-indigo-50">`;
            cols.forEach(c => {
                const v = row[c];
                const txt = (v === null || v === undefined) ? '<span class="text-gray-400 italic">NULL</span>' : escapeHtml(String(v));
                html += `<td class="px-3 py-1.5 border-b border-gray-100 whitespace-nowrap">${txt}</td>`;
            });
            html += '</tr>';
        });
        html += '</tbody></table>';
        tableWrap.innerHTML = html;
        tableWrap.classList.remove('hidden');
    })
    .catch(err => {
        loading.classList.add('hidden');
        errorBox.textContent = 'İstek başarısız: ' + err.message;
        errorBox.className = 'bg-red-50 border border-red-300 text-red-800 p-4 rounded-lg text-sm';
        errorBox.classList.remove('hidden');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
    });
}

function closePreviewModal() {
    document.getElementById('previewModal').classList.add('hidden');
}

function escapeHtml(s) {
    return s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]);
}
</script>
@endsection
