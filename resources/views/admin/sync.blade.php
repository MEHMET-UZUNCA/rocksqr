@extends('layouts.admin')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-sync mr-2 text-gold"></i>Sync - Urun Senkronizasyonu
                    </h2>
                    <div class="flex flex-wrap gap-3">
                        <button onclick="fetchFromOracle()" id="btn-oracle" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600 transition text-sm font-bold {{ $oracleConfigured ? '' : 'opacity-50 cursor-not-allowed' }}" {{ $oracleConfigured ? '' : 'disabled title=Oracle ayarlari yapilandirilmamis' }}>
                            <i class="fas fa-database mr-1"></i> Oracle'dan Cek
                        </button>
                        <button onclick="fetchFromMssql()" id="btn-mssql" class="px-4 py-2 bg-sky-600 text-white rounded hover:bg-sky-700 transition text-sm font-bold {{ $mssqlConfigured ? '' : 'opacity-50 cursor-not-allowed' }}" {{ $mssqlConfigured ? '' : 'disabled title=MSSQL ayarlari yapilandirilmamis' }}>
                            <i class="fas fa-server mr-1"></i> MSSQL'den Cek
                        </button>
                        <button onclick="enterBulkMode()" id="btn-bulk" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-sm font-bold">
                            <i class="fas fa-edit mr-1"></i> Toplu Guncelle
                        </button>
                        <button onclick="cancelBulk()" id="btn-cancel" class="hidden px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition text-sm font-bold">
                            <i class="fas fa-times mr-1"></i> Iptal
                        </button>
                    </div>
                </div>

                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        Oracle ve Symphony Restaurant MSSQL kimliklerini tek tek guncelleyebilir veya <strong>Toplu Guncelle</strong> ile isim, fiyat, Oracle ID ve MSSQL ID degisikliklerini onizleyip onaylayabilirsiniz.
                    </p>
                </div>

                <div id="msg-success" class="hidden mb-4 p-3 bg-green-100 border border-green-300 text-green-800 rounded"></div>
                <div id="msg-error" class="hidden mb-4 p-3 bg-red-100 border border-red-300 text-red-800 rounded"></div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden" id="sync-table">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th class="px-3 py-3 text-left w-16">ID</th>
                                <th class="px-3 py-3 text-left min-w-48">Urun Adi</th>
                                <th class="px-3 py-3 text-left w-36">Kategori</th>
                                <th class="px-3 py-3 text-left w-40">Oracle ID</th>
                                <th class="px-3 py-3 text-left w-40">MSSQL ID</th>
                                <th class="px-3 py-3 text-left w-32">Fiyat</th>
                                <th class="px-3 py-3 text-left w-20">Durum</th>
                                <th class="px-3 py-3 text-center w-28" id="th-actions">Islem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr class="border-b border-gray-100 {{ $loop->even ? 'bg-gray-50' : '' }}" data-id="{{ $product->id }}">
                                <td class="px-3 py-3 font-mono font-bold text-gold">{{ $product->id }}</td>
                                <td class="px-3 py-3 cell-name">
                                    <span class="view-mode">{{ $product->name }}</span>
                                    <input type="text" class="edit-mode hidden w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                           data-field="name" value="{{ $product->name }}" data-original="{{ $product->name }}">
                                </td>
                                <td class="px-3 py-3 text-xs">
                                    @if($product->category)
                                        <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded">{{ $product->category->parent ? $product->category->parent->name : $product->category->name }}</span>
                                        @if($product->category->parent)
                                            <br><span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded mt-1 inline-block">{{ $product->category->name }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 font-mono cell-oracle">
                                    <span class="view-mode oracle-display">
                                        @if($product->oracle_id)
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">{{ $product->oracle_id }}</span>
                                        @else
                                            <span class="bg-gray-100 text-gray-400 px-2 py-1 rounded text-xs">-</span>
                                        @endif
                                    </span>
                                    <input type="text" class="edit-mode hidden w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                           data-field="oracle_id" value="{{ $product->oracle_id }}" data-original="{{ $product->oracle_id }}"
                                           placeholder="ORC-1234">
                                </td>
                                <td class="px-3 py-3 font-mono cell-mssql">
                                    <span class="view-mode mssql-display">
                                        @if($product->mssql_id)
                                            <span class="bg-sky-100 text-sky-800 px-2 py-1 rounded text-xs">{{ $product->mssql_id }}</span>
                                        @else
                                            <span class="bg-gray-100 text-gray-400 px-2 py-1 rounded text-xs">-</span>
                                        @endif
                                    </span>
                                    <input type="text" class="edit-mode hidden w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                           data-field="mssql_id" value="{{ $product->mssql_id }}" data-original="{{ $product->mssql_id }}"
                                           placeholder="SYM-1234">
                                </td>
                                <td class="px-3 py-3 cell-price">
                                    <span class="view-mode">{{ number_format($product->price, 2) }} TL</span>
                                    <input type="number" step="0.01" class="edit-mode hidden w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                           data-field="price" value="{{ $product->price }}" data-original="{{ $product->price }}">
                                </td>
                                <td class="px-3 py-3">
                                    @if($product->is_available)
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold">Aktif</span>
                                    @else
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold">Pasif</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <div class="view-mode flex items-center justify-center gap-3">
                                        <button onclick="editOracleInline({{ $product->id }})" class="inline-oracle-btn text-orange-600 hover:text-orange-800 text-xs" title="Oracle ID duzenle">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button onclick="editMssqlInline({{ $product->id }})" class="inline-mssql-btn text-sky-600 hover:text-sky-800 text-xs" title="MSSQL ID duzenle">
                                            <i class="fas fa-server"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-between items-center">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-database mr-1"></i> Toplam: {{ $products->count() }} urun
                    </p>
                    <button onclick="previewChanges()" id="btn-preview" class="hidden px-6 py-2 bg-gold text-primary font-bold rounded hover:bg-yellow-500 transition">
                        <i class="fas fa-eye mr-1"></i> Degisiklikleri Onizle
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="preview-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[80vh] overflow-hidden">
        <div class="px-6 py-4 bg-primary text-white flex justify-between items-center">
            <h3 class="text-lg font-bold"><i class="fas fa-eye mr-2"></i>Degisiklik Onizleme</h3>
            <button onclick="closePreview()" class="text-gray-300 hover:text-white"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[55vh]" id="preview-content"></div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
            <p class="text-sm text-gray-500" id="preview-count"></p>
            <div class="flex gap-3">
                <button onclick="closePreview()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 transition">Iptal</button>
                <button onclick="applyChanges()" class="px-6 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 transition">
                    <i class="fas fa-check mr-1"></i> Onayla ve Guncelle
                </button>
            </div>
        </div>
    </div>
</div>

<div id="oracle-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="px-6 py-4 bg-primary text-white flex justify-between items-center">
            <h3 class="text-lg font-bold"><i class="fas fa-link mr-2"></i>Oracle ID Guncelle</h3>
            <button onclick="closeOracleModal()" class="text-gray-300 hover:text-white"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6">
            <input type="hidden" id="oracle-product-id">
            <div class="mb-4">
                <p class="text-sm text-gray-500 mb-1">Urun:</p>
                <p class="font-bold text-gray-800" id="oracle-product-name"></p>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-500 mb-1">Mevcut Oracle ID:</p>
                <p class="font-mono text-gray-600" id="oracle-old-value">-</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Yeni Oracle ID</label>
                <input type="text" id="oracle-new-value" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold" placeholder="ORC-1234">
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 bg-gray-50">
            <button onclick="closeOracleModal()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 transition">Iptal</button>
            <button onclick="saveOracleId()" class="px-6 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 transition">
                <i class="fas fa-save mr-1"></i> Kaydet
            </button>
        </div>
    </div>
</div>

<div id="mssql-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="px-6 py-4 bg-primary text-white flex justify-between items-center">
            <h3 class="text-lg font-bold"><i class="fas fa-server mr-2"></i>MSSQL ID Guncelle</h3>
            <button onclick="closeMssqlModal()" class="text-gray-300 hover:text-white"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6">
            <input type="hidden" id="mssql-product-id">
            <div class="mb-4">
                <p class="text-sm text-gray-500 mb-1">Urun:</p>
                <p class="font-bold text-gray-800" id="mssql-product-name"></p>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-500 mb-1">Mevcut MSSQL ID:</p>
                <p class="font-mono text-gray-600" id="mssql-old-value">-</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Yeni MSSQL ID</label>
                <input type="text" id="mssql-new-value" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gold" placeholder="SYM-1234">
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3 bg-gray-50">
            <button onclick="closeMssqlModal()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 transition">Iptal</button>
            <button onclick="saveMssqlId()" class="px-6 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 transition">
                <i class="fas fa-save mr-1"></i> Kaydet
            </button>
        </div>
    </div>
</div>

<div id="oracle-fetch-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[85vh] overflow-hidden">
        <div class="px-6 py-4 bg-orange-500 text-white flex justify-between items-center">
            <h3 class="text-lg font-bold"><i class="fas fa-database mr-2"></i>Oracle Veri Karsilastirma</h3>
            <button onclick="closeOracleFetch()" class="text-orange-200 hover:text-white"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="px-6 py-3 bg-orange-50 border-b border-orange-200 flex gap-4 text-sm" id="oracle-stats"></div>
        <div class="p-6 overflow-y-auto max-h-[55vh]" id="oracle-fetch-content"></div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
            <p class="text-sm text-gray-500" id="oracle-fetch-count"></p>
            <div class="flex gap-3">
                <button onclick="closeOracleFetch()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 transition">Kapat</button>
                <button onclick="applyOracleChanges()" id="btn-apply-oracle" class="hidden px-6 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 transition">
                    <i class="fas fa-check mr-1"></i> Degisiklikleri Uygula
                </button>
            </div>
        </div>
    </div>
</div>

<div id="mssql-fetch-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[85vh] overflow-hidden">
        <div class="px-6 py-4 bg-sky-600 text-white flex justify-between items-center">
            <h3 class="text-lg font-bold"><i class="fas fa-server mr-2"></i>MSSQL Veri Karsilastirma</h3>
            <button onclick="closeMssqlFetch()" class="text-sky-200 hover:text-white"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="px-6 py-3 bg-sky-50 border-b border-sky-200 flex gap-4 text-sm" id="mssql-stats"></div>
        <div class="p-6 overflow-y-auto max-h-[55vh]" id="mssql-fetch-content"></div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
            <p class="text-sm text-gray-500" id="mssql-fetch-count"></p>
            <div class="flex gap-3">
                <button onclick="closeMssqlFetch()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 transition">Kapat</button>
                <button onclick="applyMssqlChanges()" id="btn-apply-mssql" class="hidden px-6 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 transition">
                    <i class="fas fa-check mr-1"></i> Degisiklikleri Uygula
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = '{{ csrf_token() }}';
let pendingUpdates = [];
let oracleMatchedData = [];
let mssqlMatchedData = [];

function showMsg(type, text) {
    const el = document.getElementById('msg-' + type);
    el.textContent = text;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 4000);
}

function enterBulkMode() {
    document.querySelectorAll('.view-mode').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.edit-mode').forEach(el => el.classList.remove('hidden'));
    document.getElementById('btn-bulk').classList.add('hidden');
    document.getElementById('btn-cancel').classList.remove('hidden');
    document.getElementById('btn-preview').classList.remove('hidden');
    document.getElementById('th-actions').textContent = '';
}

function cancelBulk() {
    document.querySelectorAll('.view-mode').forEach(el => el.classList.remove('hidden'));
    document.querySelectorAll('.edit-mode').forEach(el => {
        el.classList.add('hidden');
        el.value = el.dataset.original;
        el.classList.remove('border-yellow-400', 'bg-yellow-50');
        el.classList.add('border-gray-300');
    });
    document.getElementById('btn-bulk').classList.remove('hidden');
    document.getElementById('btn-cancel').classList.add('hidden');
    document.getElementById('btn-preview').classList.add('hidden');
    document.getElementById('th-actions').textContent = 'Islem';
}

function collectChanges() {
    const updates = [];
    document.querySelectorAll('#sync-table tbody tr').forEach(row => {
        const update = { id: parseInt(row.dataset.id, 10) };
        let hasChange = false;
        row.querySelectorAll('.edit-mode').forEach(input => {
            if (input.value !== input.dataset.original) {
                update[input.dataset.field] = input.value;
                hasChange = true;
            }
        });
        if (hasChange) updates.push(update);
    });
    return updates;
}

function previewChanges() {
    const updates = collectChanges();
    if (updates.length === 0) {
        showMsg('error', 'Herhangi bir degisiklik yapilmadi.');
        return;
    }

    fetch('/admin/sync/preview', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ updates })
    })
    .then(r => r.json())
    .then(data => {
        renderPreview(data.preview);
        document.getElementById('preview-count').textContent = data.total_changes + ' urunde degisiklik';
        document.getElementById('preview-modal').classList.remove('hidden');
        pendingUpdates = updates;
    })
    .catch(err => showMsg('error', 'Onizleme hatasi: ' + err.message));
}

function renderPreview(preview) {
    const fieldLabels = { name: 'Urun Adi', price: 'Fiyat', oracle_id: 'Oracle ID', mssql_id: 'MSSQL ID' };
    const html = preview.map(item => {
        const changesHtml = Object.entries(item.changes).map(([field, vals]) => {
            const label = fieldLabels[field] || field;
            const isPrice = field === 'price';
            const oldVal = vals.old || '-';
            const newVal = vals.new || '-';
            return `<div class="flex items-center gap-3 py-2 border-b border-gray-100 last:border-0"><span class="text-xs text-gray-500 w-24">${label}</span><span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs line-through">${isPrice ? parseFloat(oldVal).toFixed(2) + ' TL' : oldVal}</span><i class="fas fa-arrow-right text-gray-400 text-xs"></i><span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold">${isPrice ? parseFloat(newVal).toFixed(2) + ' TL' : newVal}</span></div>`;
        }).join('');
        return `<div class="mb-4 p-4 border border-gray-200 rounded-lg"><div class="flex items-center gap-2 mb-2"><span class="font-mono text-gold font-bold">#${item.id}</span><span class="font-semibold text-gray-800">${item.current_name}</span></div>${changesHtml}</div>`;
    }).join('');
    document.getElementById('preview-content').innerHTML = html;
}

function closePreview() {
    document.getElementById('preview-modal').classList.add('hidden');
}

function applyChanges() {
    fetch('/admin/sync/bulk-update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ updates: pendingUpdates })
    })
    .then(r => r.json())
    .then(data => {
        closePreview();
        showMsg('success', data.results.filter(r => r.changed).length + ' urun basariyla guncellendi!');
        data.results.forEach(r => {
            if (!r.changed) return;
            const row = document.querySelector(`tr[data-id="${r.id}"]`);
            if (row) updateRowDisplay(row, r.new);
        });
        cancelBulk();
    })
    .catch(err => showMsg('error', 'Guncelleme hatasi: ' + err.message));
}

function updateRowDisplay(row, values) {
    if (values.name !== undefined) row.querySelector('.cell-name .view-mode').textContent = values.name;
    if (values.price !== undefined) row.querySelector('.cell-price .view-mode').textContent = parseFloat(values.price).toFixed(2) + ' TL';
    if (values.oracle_id !== undefined) {
        row.querySelector('.oracle-display').innerHTML = values.oracle_id ? `<span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">${values.oracle_id}</span>` : '<span class="bg-gray-100 text-gray-400 px-2 py-1 rounded text-xs">-</span>';
    }
    if (values.mssql_id !== undefined) {
        row.querySelector('.mssql-display').innerHTML = values.mssql_id ? `<span class="bg-sky-100 text-sky-800 px-2 py-1 rounded text-xs">${values.mssql_id}</span>` : '<span class="bg-gray-100 text-gray-400 px-2 py-1 rounded text-xs">-</span>';
    }
    row.querySelectorAll('.edit-mode').forEach(input => {
        const field = input.dataset.field;
        if (values[field] !== undefined) {
            input.value = values[field] || '';
            input.dataset.original = values[field] || '';
        }
    });
}

function editOracleInline(productId) {
    const row = document.querySelector(`tr[data-id="${productId}"]`);
    document.getElementById('oracle-product-id').value = productId;
    document.getElementById('oracle-product-name').textContent = row.querySelector('.cell-name .view-mode').textContent.trim();
    const current = row.querySelector('input[data-field="oracle_id"]').dataset.original;
    document.getElementById('oracle-old-value').textContent = current || '-';
    document.getElementById('oracle-new-value').value = current || '';
    document.getElementById('oracle-modal').classList.remove('hidden');
}

function closeOracleModal() { document.getElementById('oracle-modal').classList.add('hidden'); }

function saveOracleId() {
    const productId = document.getElementById('oracle-product-id').value;
    fetch(`/admin/sync/oracle/${productId}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ oracle_id: document.getElementById('oracle-new-value').value || null })
    }).then(r => r.json()).then(data => {
        closeOracleModal();
        showMsg('success', 'Oracle ID guncellendi.');
        const row = document.querySelector(`tr[data-id="${productId}"]`);
        if (row) updateRowDisplay(row, { oracle_id: data.new_oracle_id });
    }).catch(err => showMsg('error', 'Hata: ' + err.message));
}

function editMssqlInline(productId) {
    const row = document.querySelector(`tr[data-id="${productId}"]`);
    document.getElementById('mssql-product-id').value = productId;
    document.getElementById('mssql-product-name').textContent = row.querySelector('.cell-name .view-mode').textContent.trim();
    const current = row.querySelector('input[data-field="mssql_id"]').dataset.original;
    document.getElementById('mssql-old-value').textContent = current || '-';
    document.getElementById('mssql-new-value').value = current || '';
    document.getElementById('mssql-modal').classList.remove('hidden');
}

function closeMssqlModal() { document.getElementById('mssql-modal').classList.add('hidden'); }

function saveMssqlId() {
    const productId = document.getElementById('mssql-product-id').value;
    fetch(`/admin/sync/mssql/${productId}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ mssql_id: document.getElementById('mssql-new-value').value || null })
    }).then(r => r.json()).then(data => {
        closeMssqlModal();
        showMsg('success', 'MSSQL ID guncellendi.');
        const row = document.querySelector(`tr[data-id="${productId}"]`);
        if (row) updateRowDisplay(row, { mssql_id: data.new_mssql_id });
    }).catch(err => showMsg('error', 'Hata: ' + err.message));
}

function fetchFromOracle() {
    const btn = document.getElementById('btn-oracle');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Baglaniyor...';
    fetch('/admin/sync/fetch-oracle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    }).then(r => r.json()).then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-database mr-1"></i> Oracle\'dan Cek';
        if (!data.success) return showMsg('error', data.error);
        oracleMatchedData = data.matched.filter(item => item.has_changes);
        renderOracleFetch(data);
        document.getElementById('oracle-fetch-modal').classList.remove('hidden');
    }).catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-database mr-1"></i> Oracle\'dan Cek';
        showMsg('error', 'Oracle baglanti hatasi: ' + err.message);
    });
}

function renderOracleFetch(data) {
    document.getElementById('oracle-stats').innerHTML = `<span class="text-orange-700">Oracle: <strong>${data.total_oracle}</strong> urun</span><span class="text-blue-700">Eslesen: <strong>${data.total_matched}</strong></span><span class="text-green-700">Degisiklik: <strong>${data.total_with_changes}</strong></span><span class="text-gray-500">Eslesmeyen: <strong>${data.unmatched.length}</strong></span>`;
    let html = '';
    if (oracleMatchedData.length > 0) {
        html += oracleMatchedData.map(item => `<div class="mb-3 p-4 border border-orange-200 rounded-lg bg-orange-50/50"><div class="flex items-center gap-2 mb-1"><span class="font-mono text-orange-600 font-bold text-xs">${item.oracle_id}</span><span class="font-semibold text-gray-800">${item.local_name}</span><span class="text-xs text-gray-400">(#${item.local_id})</span></div>${Object.entries(item.changes).map(([field, vals]) => `<div class="flex items-center gap-3 py-2 border-b border-gray-100 last:border-0"><span class="text-xs text-gray-500 w-20">${field === 'name' ? 'Urun Adi' : 'Fiyat'}</span><span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs line-through">${field === 'price' ? parseFloat(vals.old).toFixed(2) + ' TL' : vals.old}</span><i class="fas fa-arrow-right text-gray-400 text-xs"></i><span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold">${field === 'price' ? parseFloat(vals.new).toFixed(2) + ' TL' : vals.new}</span></div>`).join('')}</div>`).join('');
    }
    if (data.unmatched.length > 0) {
        html += '<h4 class="font-bold text-gray-900 mt-6 mb-3">Eslesmeyen Oracle Urunleri</h4><div class="grid grid-cols-1 md:grid-cols-2 gap-2">';
        html += data.unmatched.map(item => `<div class="p-2 border border-gray-200 rounded bg-gray-50 text-sm"><span class="font-mono text-gray-500">${item.oracle_id}</span> - <span>${item.oracle_name}</span> <span class="text-gray-400 ml-1">${parseFloat(item.oracle_price).toFixed(2)} TL</span></div>`).join('');
        html += '</div>';
    }
    if (html === '') html = '<div class="text-center py-8 text-gray-500"><p class="text-lg font-bold">Tum urunler guncel.</p></div>';
    document.getElementById('oracle-fetch-content').innerHTML = html;
    document.getElementById('oracle-fetch-count').textContent = data.total_with_changes + ' urunde guncelleme mevcut';
    document.getElementById('btn-apply-oracle').classList.toggle('hidden', oracleMatchedData.length === 0);
}

function closeOracleFetch() { document.getElementById('oracle-fetch-modal').classList.add('hidden'); }

function applyOracleChanges() {
    const updates = oracleMatchedData.map(item => {
        const update = { local_id: item.local_id };
        if (item.changes.name) update.name = item.changes.name.new;
        if (item.changes.price) update.price = item.changes.price.new;
        return update;
    });
    fetch('/admin/sync/apply-oracle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ updates })
    }).then(r => r.json()).then(data => {
        closeOracleFetch();
        showMsg('success', 'Oracle degisiklikleri uygulandi.');
        data.results.forEach(item => {
            if (!item.changed) return;
            const row = document.querySelector(`tr[data-id="${item.id}"]`);
            if (row) updateRowDisplay(row, item.new);
        });
    }).catch(err => showMsg('error', 'Oracle guncelleme hatasi: ' + err.message));
}

function fetchFromMssql() {
    const btn = document.getElementById('btn-mssql');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Baglaniyor...';
    fetch('/admin/sync/fetch-mssql', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    }).then(r => r.json()).then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-server mr-1"></i> MSSQL\'den Cek';
        if (!data.success) return showMsg('error', data.error);
        mssqlMatchedData = data.matched.filter(item => item.has_changes);
        renderMssqlFetch(data);
        document.getElementById('mssql-fetch-modal').classList.remove('hidden');
    }).catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-server mr-1"></i> MSSQL\'den Cek';
        showMsg('error', 'MSSQL baglanti hatasi: ' + err.message);
    });
}

function renderMssqlFetch(data) {
    document.getElementById('mssql-stats').innerHTML = `<span class="text-sky-700">MSSQL: <strong>${data.total_mssql}</strong> urun</span><span class="text-blue-700">Eslesen: <strong>${data.total_matched}</strong></span><span class="text-green-700">Degisiklik: <strong>${data.total_with_changes}</strong></span><span class="text-gray-500">Eslesmeyen: <strong>${data.unmatched.length}</strong></span>${data.income_center_filter ? `<span class="text-emerald-700">RVC: <strong>${data.income_center_filter}</strong></span>` : ''}${data.custom_query_used ? `<span class="text-amber-700">Ozel SQL kullanildi</span>` : ''}`;
    let html = '';
    if (mssqlMatchedData.length > 0) {
        html += mssqlMatchedData.map(item => `<div class="mb-3 p-4 border border-sky-200 rounded-lg bg-sky-50/50"><div class="flex items-center gap-2 mb-1"><span class="font-mono text-sky-700 font-bold text-xs">${item.mssql_id}</span><span class="font-semibold text-gray-800">${item.local_name}</span><span class="text-xs text-gray-400">(#${item.local_id})</span></div><div class="flex flex-wrap gap-2 mb-2">${item.mssql_group ? `<span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-xs">${item.mssql_group}</span>` : ''}${item.mssql_subgroup ? `<span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded text-xs">${item.mssql_subgroup}</span>` : ''}${item.mssql_income_center ? `<span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-xs">Gelir Merkezi: ${item.mssql_income_center}</span>` : ''}</div>${Object.entries(item.changes).map(([field, vals]) => `<div class="flex items-center gap-3 py-2 border-b border-gray-100 last:border-0"><span class="text-xs text-gray-500 w-20">${field === 'name' ? 'Urun Adi' : 'Fiyat'}</span><span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs line-through">${field === 'price' ? parseFloat(vals.old).toFixed(2) + ' TL' : vals.old}</span><i class="fas fa-arrow-right text-gray-400 text-xs"></i><span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold">${field === 'price' ? parseFloat(vals.new).toFixed(2) + ' TL' : vals.new}</span></div>`).join('')}</div>`).join('');
    }
    if (data.unmatched.length > 0) {
        html += '<h4 class="font-bold text-gray-900 mt-6 mb-3">Eslesmeyen MSSQL Urunleri</h4><div class="grid grid-cols-1 md:grid-cols-2 gap-2">';
        html += data.unmatched.map(item => `<div class="p-3 border border-gray-200 rounded bg-gray-50 text-sm"><div><span class="font-mono text-gray-500">${item.mssql_id}</span> - <span>${item.mssql_name}</span></div><div class="text-gray-400 mt-1">${parseFloat(item.mssql_price).toFixed(2)} TL</div><div class="flex flex-wrap gap-1 mt-2">${item.mssql_group ? `<span class="bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded text-xs">${item.mssql_group}</span>` : ''}${item.mssql_subgroup ? `<span class="bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded text-xs">${item.mssql_subgroup}</span>` : ''}${item.mssql_income_center ? `<span class="bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded text-xs">${item.mssql_income_center}</span>` : ''}</div></div>`).join('');
        html += '</div>';
    }
    if (html === '') html = '<div class="text-center py-8 text-gray-500"><p class="text-lg font-bold">Tum urunler guncel.</p></div>';
    document.getElementById('mssql-fetch-content').innerHTML = html;
    document.getElementById('mssql-fetch-count').textContent = data.total_with_changes + ' urunde guncelleme mevcut';
    document.getElementById('btn-apply-mssql').classList.toggle('hidden', mssqlMatchedData.length === 0);
}

function closeMssqlFetch() { document.getElementById('mssql-fetch-modal').classList.add('hidden'); }

function applyMssqlChanges() {
    const updates = mssqlMatchedData.map(item => {
        const update = { local_id: item.local_id };
        if (item.changes.name) update.name = item.changes.name.new;
        if (item.changes.price) update.price = item.changes.price.new;
        return update;
    });
    fetch('/admin/sync/apply-mssql', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ updates })
    }).then(r => r.json()).then(data => {
        closeMssqlFetch();
        showMsg('success', 'MSSQL degisiklikleri uygulandi.');
        data.results.forEach(item => {
            if (!item.changed) return;
            const row = document.querySelector(`tr[data-id="${item.id}"]`);
            if (row) updateRowDisplay(row, item.new);
        });
    }).catch(err => showMsg('error', 'MSSQL guncelleme hatasi: ' + err.message));
}
</script>
@endsection
