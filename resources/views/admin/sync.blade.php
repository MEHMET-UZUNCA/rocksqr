@extends('layouts.admin')

@section('content')
@php
    $total      = $products->count();
    $withCode   = $products->filter(fn($p) => $p->mssql_id)->count();
    $noCode     = $total - $withCode;
    $matchPct   = $total > 0 ? round($withCode / $total * 100) : 0;
@endphp

<div class="py-8">
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                <i class="fas fa-box text-primary text-base"></i>
            </div>
            <div><div class="text-2xl font-bold text-gray-900">{{ $total }}</div><div class="text-xs text-gray-500 mt-0.5">Toplam Ürün</div></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-sky-100 flex items-center justify-center shrink-0">
                <i class="fas fa-link text-sky-600 text-base"></i>
            </div>
            <div><div class="text-2xl font-bold text-sky-700">{{ $withCode }}</div><div class="text-xs text-gray-500 mt-0.5">Product Code Var</div></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-orange-100 flex items-center justify-center shrink-0">
                <i class="fas fa-unlink text-orange-500 text-base"></i>
            </div>
            <div><div class="text-2xl font-bold text-orange-600">{{ $noCode }}</div><div class="text-xs text-gray-500 mt-0.5">Product Code Yok</div></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
                <i class="fas fa-percent text-emerald-600 text-base"></i>
            </div>
            <div><div class="text-2xl font-bold text-emerald-700">{{ $matchPct }}%</div><div class="text-xs text-gray-500 mt-0.5">Eşleşme Oranı</div></div>
        </div>
    </div>

    {{-- Main Panel --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Action & Search Bar --}}
        <div class="px-5 py-3.5 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <button onclick="openSymphonyImport()" id="btn-symphony"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold {{ $symphonyConfigured ? '' : 'opacity-50 cursor-not-allowed' }}"
                    {{ $symphonyConfigured ? '' : 'disabled' }}>
                    <i class="fas fa-file-import mr-1.5"></i> Symphony İmport
                </button>
                <button onclick="fetchFromMssql()" id="btn-mssql"
                    class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition text-sm font-semibold {{ $mssqlConfigured ? '' : 'opacity-50 cursor-not-allowed' }}"
                    {{ $mssqlConfigured ? '' : 'disabled' }}>
                    <i class="fas fa-rotate mr-1.5"></i> MSSQL Sync
                </button>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" id="search-input" placeholder="Ürün ara…" oninput="filterTable()"
                        class="pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 w-48">
                    <i class="fas fa-search absolute left-2.5 top-2 text-gray-400 text-xs"></i>
                </div>
                <select id="filter-code" onchange="filterTable()"
                    class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                    <option value="all">Tümü</option>
                    <option value="has">Kodu Var</option>
                    <option value="missing">Kodu Yok</option>
                </select>
            </div>
        </div>

        {{-- Contextual: rows selected --}}
        <div id="selection-bar" class="hidden px-5 py-2.5 bg-sky-50 border-b border-sky-200 flex items-center gap-3 text-sm">
            <span class="font-semibold text-sky-800" id="selection-label">0 satır seçildi</span>
            <button onclick="enterBulkMode()"
                class="px-3 py-1 bg-sky-600 text-white text-xs font-bold rounded-lg hover:bg-sky-700 transition">
                <i class="fas fa-edit mr-1"></i> Seçilenleri Düzenle
            </button>
            <button onclick="clearSelection()"
                class="px-3 py-1 bg-gray-400 text-white text-xs font-bold rounded-lg hover:bg-gray-500 transition">
                <i class="fas fa-times mr-1"></i> Seçimi Kaldır
            </button>
        </div>

        {{-- Contextual: bulk edit mode --}}
        <div id="edit-bar" class="hidden px-5 py-2.5 bg-amber-50 border-b border-amber-200 flex items-center gap-3 text-sm">
            <span class="font-semibold text-amber-800"><i class="fas fa-edit mr-1"></i> Düzenleme Modu</span>
            <button onclick="previewChanges()" id="btn-preview"
                class="px-4 py-1.5 bg-gold text-primary text-xs font-bold rounded-lg hover:bg-yellow-400 transition">
                <i class="fas fa-eye mr-1"></i> Değişiklikleri Önizle
            </button>
            <button onclick="cancelBulk()" id="btn-cancel"
                class="px-3 py-1.5 bg-gray-500 text-white text-xs font-bold rounded-lg hover:bg-gray-600 transition">
                <i class="fas fa-times mr-1"></i> İptal
            </button>
        </div>

        {{-- Messages --}}
        <div id="msg-success" class="hidden px-5 py-2.5 bg-green-100 border-b border-green-200 text-green-800 text-sm font-semibold"></div>
        <div id="msg-error"   class="hidden px-5 py-2.5 bg-red-100 border-b border-red-200 text-red-800 text-sm font-semibold"></div>

        {{-- Product Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="sync-table">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-600 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 w-10 text-center">
                            <input type="checkbox" id="th-check" class="rounded accent-sky-600" onchange="selectAll(this.checked)">
                        </th>
                        <th class="px-4 py-3 text-left w-14">ID</th>
                        <th class="px-4 py-3 text-left w-44">Product Code</th>
                        <th class="px-4 py-3 text-left">Ürün Adı</th>
                        <th class="px-4 py-3 text-left w-44">Kategori</th>
                        <th class="px-4 py-3 text-left w-32">Fiyat</th>
                        <th class="px-4 py-3 text-left w-20">Durum</th>
                        <th class="px-4 py-3 text-center w-20" id="th-actions">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($products as $product)
                    <tr class="hover:bg-gray-50 transition-colors"
                        data-id="{{ $product->id }}"
                        data-name="{{ strtolower($product->name) }}"
                        data-code="{{ $product->mssql_id ? 'has' : 'missing' }}">
                        <td class="px-4 py-2.5 text-center">
                            <input type="checkbox" class="row-check rounded accent-sky-600" value="{{ $product->id }}" onchange="onRowCheck()">
                        </td>
                        <td class="px-4 py-2.5 font-mono font-bold text-gold text-sm">{{ $product->id }}</td>
                        <td class="px-4 py-2.5 font-mono cell-mssql">
                            <span class="mssql-display">
                                @if($product->mssql_id)
                                    <span class="bg-sky-100 text-sky-800 px-2 py-0.5 rounded text-xs font-medium">{{ $product->mssql_id }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-orange-500 text-xs"><i class="fas fa-exclamation-circle text-[10px]"></i> Yok</span>
                                @endif
                            </span>
                            <input type="hidden" data-field="mssql_id" value="{{ $product->mssql_id }}" data-original="{{ $product->mssql_id }}">
                        </td>
                        <td class="px-4 py-2.5 cell-name">
                            <span class="view-mode font-medium text-gray-800">{{ $product->name }}</span>
                            <input type="text" class="edit-mode hidden w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                   data-field="name" value="{{ $product->name }}" data-original="{{ $product->name }}">
                        </td>
                        <td class="px-4 py-2.5 text-xs cell-category">
                            <span class="view-mode">
                                @if($product->category)
                                    @if($product->category->parent)
                                        <span class="text-gray-400 text-[10px] block leading-none mb-0.5">{{ $product->category->parent->name }}</span>
                                    @endif
                                    <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded">{{ $product->category->name }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </span>
                            <select class="edit-mode hidden w-full px-2 py-1 border border-gray-300 rounded text-xs"
                                    data-field="category_id" data-original="{{ $product->category_id }}">
                                <option value="">-- Kategori --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->parent ? $cat->parent->name . ' / ' . $cat->name : $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-2.5 cell-price">
                            <span class="view-mode font-semibold text-gray-800">{{ number_format($product->price, 2) }} ₺</span>
                            <input type="number" step="0.01" min="0" class="edit-mode hidden w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                   data-field="price" value="{{ $product->price }}" data-original="{{ $product->price }}">
                        </td>
                        <td class="px-4 py-2.5">
                            @if($product->is_available)
                                <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-semibold">Aktif</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-semibold">Pasif</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <button onclick="editMssqlInline({{ $product->id }})"
                                class="p-1.5 rounded text-sky-500 hover:text-sky-700 hover:bg-sky-50 transition" title="Product Code düzenle">
                                <i class="fas fa-qrcode text-sm"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-5 py-2.5 border-t border-gray-100 flex justify-between items-center">
            <span class="text-xs text-gray-400">{{ $total }} ürün</span>
            <span class="text-xs text-gray-400" id="filtered-label"></span>
        </div>
    </div>

    {{-- MSSQL Inline Comparison Panel --}}
    <div id="mssql-panel" class="hidden bg-white rounded-xl shadow-sm border-2 border-sky-300 overflow-hidden">
        <div class="px-5 py-3.5 bg-sky-600 text-white flex items-center justify-between">
            <h3 class="font-bold text-base"><i class="fas fa-rotate mr-2"></i>MSSQL Sync — Değişiklik Karşılaştırması</h3>
            <button onclick="closeMssqlPanel()" class="text-sky-200 hover:text-white"><i class="fas fa-times text-lg"></i></button>
        </div>
        <div class="px-5 py-2.5 bg-sky-50 border-b border-sky-200 flex flex-wrap gap-4 text-sm" id="mssql-stats"></div>
        <div class="px-5 py-2 border-b border-gray-200 bg-gray-50 flex items-center gap-3">
            <button onclick="mssqlSelectAll(true)" class="px-3 py-1 bg-sky-600 text-white text-xs font-bold rounded hover:bg-sky-700">
                <i class="fas fa-check-square mr-1"></i>Tümünü Seç
            </button>
            <button onclick="mssqlSelectAll(false)" class="px-3 py-1 bg-gray-400 text-white text-xs font-bold rounded hover:bg-gray-500">
                <i class="far fa-square mr-1"></i>Seçimi Kaldır
            </button>
            <span class="text-xs text-gray-500">Yalnızca değişiklik olan satırlar listelenmiştir. Otomatik olarak seçili gelir.</span>
        </div>
        <div class="p-4 overflow-x-auto" id="mssql-fetch-content"></div>
        <div class="px-5 py-3.5 border-t border-gray-200 bg-gray-50 flex justify-between items-center">
            <span class="text-sm font-semibold text-gray-700" id="mssql-fetch-count">0 satır seçildi</span>
            <div class="flex gap-3">
                <button onclick="closeMssqlPanel()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 text-sm transition">Kapat</button>
                <button onclick="applyMssqlChanges()" id="btn-apply-mssql" disabled
                    class="px-5 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition text-sm disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fas fa-check mr-1"></i> Seçilenleri Güncelle
                </button>
            </div>
        </div>
    </div>

</div>
</div>

<!-- Symphony Import Modal -->
<div id="symphony-modal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-7xl w-full max-h-[95vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 bg-emerald-600 text-white flex justify-between items-center">
            <h3 class="text-lg font-bold"><i class="fas fa-file-import mr-2"></i>Symphony'den İçe Aktar</h3>
            <button onclick="closeSymphonyModal()" class="text-emerald-200 hover:text-white"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="px-6 py-3 bg-emerald-50 border-b border-emerald-200 text-sm flex flex-wrap items-center gap-4" id="symphony-stats"></div>
        <div class="px-6 py-2 border-b border-gray-200 bg-gray-50 flex items-center gap-3">
            <button onclick="symphonySelectAll(true)" class="px-3 py-1 bg-emerald-600 text-white text-xs font-bold rounded hover:bg-emerald-700"><i class="fas fa-check-square mr-1"></i>Tümünü Seç</button>
            <button onclick="symphonySelectAll(false)" class="px-3 py-1 bg-gray-500 text-white text-xs font-bold rounded hover:bg-gray-600"><i class="far fa-square mr-1"></i>Seçimi Kaldır</button>
            <span class="text-xs text-gray-500 ml-2">Sadece <strong>Yeni</strong> ve <strong>Değişecek</strong> kayıtlar varsayılan olarak işaretli gelir.</span>
        </div>
        <div class="p-4 overflow-y-auto flex-1" id="symphony-content"></div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
            <p class="text-sm text-gray-600 font-medium" id="symphony-selected-count">0 ürün seçildi</p>
            <div class="flex gap-3">
                <button onclick="closeSymphonyModal()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 transition">Kapat</button>
                <button onclick="importSelected()" id="btn-import-selected" disabled
                        class="px-6 py-2 bg-emerald-600 text-white font-bold rounded hover:bg-emerald-700 transition disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fas fa-download mr-1"></i> Seçilenleri İçe Aktar
                </button>
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

<div id="mssql-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="px-6 py-4 bg-primary text-white flex justify-between items-center">
            <h3 class="text-lg font-bold"><i class="fas fa-server mr-2"></i>Product Code Guncelle</h3>
            <button onclick="closeMssqlModal()" class="text-gray-300 hover:text-white"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6">
            <input type="hidden" id="mssql-product-id">
            <div class="mb-4">
                <p class="text-sm text-gray-500 mb-1">Urun:</p>
                <p class="font-bold text-gray-800" id="mssql-product-name"></p>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-500 mb-1">Mevcut Product Code:</p>
                <p class="font-mono text-gray-600" id="mssql-old-value">-</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Yeni Product Code</label>
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


<script>
const csrfToken = '{{ csrf_token() }}';
let pendingUpdates = [];
let mssqlMatchedData = [];
let symphonyItemsMap = {};
let bulkEditIds = [];

// ─── Row selection & filter ──────────────────────────────────────────────────

function selectAll(checked) {
    document.querySelectorAll('#sync-table .row-check').forEach(cb => {
        const row = cb.closest('tr');
        if (!row.classList.contains('hidden')) cb.checked = checked;
    });
    onRowCheck();
}

function onRowCheck() {
    const visible = Array.from(document.querySelectorAll('#sync-table .row-check'))
        .filter(cb => !cb.closest('tr').classList.contains('hidden'));
    const checked = visible.filter(cb => cb.checked);
    const thCheck = document.getElementById('th-check');
    thCheck.checked = checked.length === visible.length && visible.length > 0;
    thCheck.indeterminate = checked.length > 0 && checked.length < visible.length;

    const bar = document.getElementById('selection-bar');
    const editBar = document.getElementById('edit-bar');
    if (!editBar.classList.contains('hidden')) return; // already editing
    if (checked.length > 0) {
        document.getElementById('selection-label').textContent = checked.length + ' satır seçildi';
        bar.classList.remove('hidden');
    } else {
        bar.classList.add('hidden');
    }
}

function clearSelection() {
    document.querySelectorAll('#sync-table .row-check').forEach(cb => cb.checked = false);
    document.getElementById('th-check').checked = false;
    document.getElementById('th-check').indeterminate = false;
    document.getElementById('selection-bar').classList.add('hidden');
}

function filterTable() {
    const q = document.getElementById('search-input').value.toLowerCase();
    const code = document.getElementById('filter-code').value;
    let visible = 0;
    document.querySelectorAll('#sync-table tbody tr').forEach(row => {
        const nameMatch = !q || row.dataset.name.includes(q);
        const codeMatch = code === 'all' || row.dataset.code === code;
        const show = nameMatch && codeMatch;
        row.classList.toggle('hidden', !show);
        if (show) visible++;
    });
    document.getElementById('filtered-label').textContent =
        q || code !== 'all' ? visible + ' sonuç gösteriliyor' : '';
    onRowCheck();
}


// ─── Symphony Import ──────────────────────────────────────────────────────────

function openSymphonyImport() {
    const btn = document.getElementById('btn-symphony');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Çekiliyor...';

    fetch('/admin/sync/symphony-fetch', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-file-import mr-1"></i> Symphony İmport';
        if (!data.success) { showMsg('error', data.error || 'Veri çekilemedi.'); return; }
        renderSymphonyModal(data);
        document.getElementById('symphony-modal').classList.remove('hidden');
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-file-import mr-1"></i> Symphony İmport';
        showMsg('error', 'Bağlantı hatası: ' + err.message);
    });
}

function renderSymphonyModal(data) {
    symphonyItemsMap = {};
    let totalNew = 0, totalChanged = 0, totalExists = 0;

    data.groups.forEach(g => g.items.forEach(i => {
        if (i.status === 'new') totalNew++;
        else if (i.status === 'changed') totalChanged++;
        else totalExists++;
    }));

    document.getElementById('symphony-stats').innerHTML =
        `<span class="text-gray-600 font-medium">${data.total} ürün &bull; ${data.total_groups} grup</span>` +
        `<span class="text-emerald-700 ml-2"><i class="fas fa-plus-circle mr-1"></i>${totalNew} yeni</span>` +
        `<span class="text-amber-700 ml-2"><i class="fas fa-edit mr-1"></i>${totalChanged} değişecek</span>` +
        `<span class="text-gray-500 ml-2"><i class="fas fa-check mr-1"></i>${totalExists} mevcut (değişmez)</span>`;

    let html = '';
    let itemIndex = 0;

    data.groups.forEach(group => {
        const groupKey = 'g' + group.name.replace(/[^a-zA-Z0-9]/g, '_');
        const catBadge = group.category_exists
            ? `<span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded"><i class="fas fa-check mr-1"></i>Kategori var</span>`
            : `<span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded"><i class="fas fa-plus mr-1"></i>Yeni kategori oluşturulacak</span>`;

        const defaultCheckedCount = group.items.filter(i => i.status !== 'exists').length;

        let itemsHtml = '';
        group.items.forEach(item => {
            const itemKey = 'i' + (itemIndex++);
            symphonyItemsMap[itemKey] = item;

            const statusBadge = item.status === 'new'
                ? `<span class="text-xs bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded font-bold">Yeni</span>`
                : item.status === 'changed'
                ? `<span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-bold">Değişecek</span>`
                : `<span class="text-xs bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded">Mevcut</span>`;

            const changesHtml = Object.entries(item.changes || {}).map(([field, vals]) =>
                `<span class="text-xs text-gray-500 ml-1">${field === 'price' ? 'Fiyat' : 'Ad'}:
                <span class="line-through text-red-500">${field === 'price' ? parseFloat(vals.old).toFixed(2) + ' TL' : vals.old}</span>
                → <span class="text-emerald-600 font-bold">${field === 'price' ? parseFloat(vals.new).toFixed(2) + ' TL' : vals.new}</span></span>`
            ).join('');

            const defaultChecked = item.status !== 'exists' ? 'checked' : '';

            itemsHtml += `
            <div class="flex items-start gap-3 py-2 border-b border-gray-100 last:border-0 pl-8 pr-4">
                <input type="checkbox" class="item-check mt-0.5 rounded accent-emerald-600"
                       data-group="${groupKey}" data-item-key="${itemKey}" ${defaultChecked}
                       onchange="updateGroupCheckbox('${groupKey}'); updateSelectedCount();">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-xs text-sky-600 bg-sky-50 px-1.5 py-0.5 rounded border border-sky-200">${item.mssql_id}</span>
                        <span class="text-sm text-gray-800">${item.name}</span>
                        ${statusBadge}
                    </div>
                    <div class="text-xs text-gray-400 mt-0.5">${parseFloat(item.price).toFixed(2)} TL${changesHtml}</div>
                </div>
            </div>`;
        });

        const groupChecked = defaultCheckedCount > 0 ? 'checked' : '';

        html += `
        <div class="mb-3 border border-gray-200 rounded-lg overflow-hidden">
            <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 hover:bg-gray-100 cursor-pointer select-none" onclick="toggleGroupCollapse('${groupKey}')">
                <input type="checkbox" class="group-check rounded accent-emerald-600" id="gc-${groupKey}" data-group="${groupKey}"
                       ${groupChecked} onclick="event.stopPropagation(); toggleAllInGroup('${groupKey}', this.checked);">
                <i class="fas fa-folder text-amber-500"></i>
                <span class="font-semibold text-gray-800 flex-1">${group.name}</span>
                ${catBadge}
                <span class="text-xs text-gray-400 ml-2">${group.items.length} ürün</span>
                <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform duration-200" id="chev-${groupKey}"></i>
            </div>
            <div id="gi-${groupKey}">${itemsHtml}</div>
        </div>`;
    });

    document.getElementById('symphony-content').innerHTML = html || '<div class="text-center py-12 text-gray-400">Veri bulunamadı.</div>';

    // Set indeterminate state on group checkboxes
    data.groups.forEach(group => {
        const groupKey = 'g' + group.name.replace(/[^a-zA-Z0-9]/g, '_');
        const itemChecks = document.querySelectorAll(`.item-check[data-group="${groupKey}"]`);
        const checked = Array.from(itemChecks).filter(c => c.checked).length;
        const cb = document.getElementById('gc-' + groupKey);
        if (cb && checked > 0 && checked < itemChecks.length) cb.indeterminate = true;
    });

    updateSelectedCount();
}

function toggleGroupCollapse(groupKey) {
    const el    = document.getElementById('gi-' + groupKey);
    const chev  = document.getElementById('chev-' + groupKey);
    el.classList.toggle('hidden');
    chev.style.transform = el.classList.contains('hidden') ? 'rotate(-90deg)' : '';
}

function toggleAllInGroup(groupKey, checked) {
    document.querySelectorAll(`.item-check[data-group="${groupKey}"]`).forEach(cb => cb.checked = checked);
    updateSelectedCount();
}

function updateGroupCheckbox(groupKey) {
    const itemChecks = document.querySelectorAll(`.item-check[data-group="${groupKey}"]`);
    const checkedCount = Array.from(itemChecks).filter(c => c.checked).length;
    const cb = document.getElementById('gc-' + groupKey);
    if (!cb) return;
    if (checkedCount === 0) { cb.checked = false; cb.indeterminate = false; }
    else if (checkedCount === itemChecks.length) { cb.checked = true; cb.indeterminate = false; }
    else { cb.checked = false; cb.indeterminate = true; }
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.item-check:checked').length;
    document.getElementById('symphony-selected-count').textContent = count + ' ürün seçildi';
    document.getElementById('btn-import-selected').disabled = count === 0;
}

function symphonySelectAll(checked) {
    document.querySelectorAll('.item-check').forEach(cb => cb.checked = checked);
    document.querySelectorAll('.group-check').forEach(cb => { cb.checked = checked; cb.indeterminate = false; });
    updateSelectedCount();
}

function importSelected() {
    const items = Array.from(document.querySelectorAll('.item-check:checked'))
        .map(cb => symphonyItemsMap[cb.dataset.itemKey])
        .filter(Boolean);
    if (items.length === 0) return;

    const btn = document.getElementById('btn-import-selected');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> İçe Aktarılıyor...';

    fetch('/admin/sync/symphony-import', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ items })
    })
    .then(r => r.json())
    .then(data => {
        closeSymphonyModal();
        if (data.success) {
            showMsg('success', data.message);
            setTimeout(() => location.reload(), 1800);
        } else {
            showMsg('error', data.message || 'İçe aktarma hatası.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download mr-1"></i> Seçilenleri İçe Aktar';
        showMsg('error', 'Hata: ' + err.message);
    });
}

function closeSymphonyModal() {
    document.getElementById('symphony-modal').classList.add('hidden');
}

// ─── Mevcut fonksiyonlar ─────────────────────────────────────────────────────

function showMsg(type, text) {
    const el = document.getElementById('msg-' + type);
    el.textContent = text;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 4000);
}

function enterBulkMode() {
    bulkEditIds = Array.from(document.querySelectorAll('#sync-table .row-check:checked')).map(cb => parseInt(cb.value));
    if (bulkEditIds.length === 0) {
        // No selection → edit all
        bulkEditIds = Array.from(document.querySelectorAll('#sync-table tbody tr:not(.hidden)')).map(r => parseInt(r.dataset.id));
    }
    bulkEditIds.forEach(id => {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (!row) return;
        row.querySelectorAll('.view-mode').forEach(el => el.classList.add('hidden'));
        row.querySelectorAll('.edit-mode').forEach(el => el.classList.remove('hidden'));
        row.classList.add('ring-1', 'ring-amber-300');
    });
    document.getElementById('selection-bar').classList.add('hidden');
    document.getElementById('edit-bar').classList.remove('hidden');
    document.getElementById('th-actions').textContent = '';
}

// URL'de ?bulk=1 varsa otomatik toplu g\u00fcncelle moduna ge\u00e7
if (new URLSearchParams(window.location.search).get('bulk') === '1') {
    window.addEventListener('DOMContentLoaded', () => enterBulkMode());
}

function cancelBulk() {
    bulkEditIds.forEach(id => {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (!row) return;
        row.querySelectorAll('.view-mode').forEach(el => el.classList.remove('hidden'));
        row.querySelectorAll('.edit-mode').forEach(el => {
            el.classList.add('hidden');
            el.value = el.dataset.original;
        });
        row.classList.remove('ring-1', 'ring-amber-300');
    });
    bulkEditIds = [];
    document.getElementById('edit-bar').classList.add('hidden');
    document.getElementById('th-actions').textContent = '\u0130\u015flem';
    clearSelection();
}
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
    const fieldLabels = { name: 'Urun Adi', price: 'Fiyat', mssql_id: 'Product Code', category_id: 'Kategori' };
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
    if (values.mssql_id !== undefined) {
        row.querySelector('.mssql-display').innerHTML = values.mssql_id ? `<span class="bg-sky-100 text-sky-800 px-2 py-1 rounded text-xs">${values.mssql_id}</span>` : '<span class="bg-gray-100 text-gray-400 px-2 py-1 rounded text-xs">-</span>';
    }
    if (values.category_id !== undefined) {
        const sel = row.querySelector('.cell-category select');
        const view = row.querySelector('.cell-category .view-mode');
        if (sel && view) {
            const opt = sel.querySelector(`option[value="${values.category_id}"]`);
            const label = opt ? opt.textContent.trim() : '-';
            view.innerHTML = `<span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded">${label}</span>`;
        }
    }
    row.querySelectorAll('.edit-mode').forEach(input => {
        const field = input.dataset.field;
        if (values[field] !== undefined) {
            input.value = values[field] || '';
            input.dataset.original = values[field] || '';
        }
    });
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
    const value = document.getElementById('mssql-new-value').value;
    fetch(`/admin/sync/mssql/${productId}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ mssql_id: value === '' ? null : value })
    }).then(r => r.json()).then(data => {
        closeMssqlModal();
        showMsg('success', 'Product Code guncellendi.');
        const row = document.querySelector(`tr[data-id="${productId}"]`);
        if (row) updateRowDisplay(row, { mssql_id: data.new_mssql_id });
    }).catch(err => showMsg('error', 'Hata: ' + err.message));
}

function fetchFromMssql() {
    const btn = document.getElementById('btn-mssql');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sorgulanıyor...';
    fetch('/admin/sync/fetch-mssql', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
    }).then(r => r.json()).then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-rotate mr-1.5"></i> MSSQL Sync';
        if (!data.success) return showMsg('error', data.error);
        mssqlMatchedData = data.matched.filter(item => item.has_changes);
        renderMssqlFetch(data);
        const panel = document.getElementById('mssql-panel');
        panel.classList.remove('hidden');
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }).catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-rotate mr-1.5"></i> MSSQL Sync';
        showMsg('error', 'MSSQL bağlantı hatası: ' + err.message);
    });
}

function renderMssqlFetch(data) {
    document.getElementById('mssql-stats').innerHTML = `<span class="text-sky-700">MSSQL: <strong>${data.total_mssql}</strong> urun</span><span class="text-blue-700">Eslesen: <strong>${data.total_matched}</strong></span><span class="text-green-700">Degisiklik: <strong>${data.total_with_changes}</strong></span><span class="text-gray-500">Eslesmeyen: <strong>${data.unmatched.length}</strong></span>${data.income_center_filter ? `<span class="text-emerald-700">RVC: <strong>${data.income_center_filter}</strong></span>` : ''}${data.custom_query_used ? `<span class="text-amber-700">Ozel SQL kullanildi</span>` : ''}`;

    let html = '';
    if (mssqlMatchedData.length > 0) {
        html += `
        <div class="overflow-x-auto border border-sky-200 rounded-lg">
          <table class="w-full text-sm">
            <thead class="bg-sky-100 text-sky-900">
              <tr>
                <th class="px-3 py-2 w-10 text-center"><input type="checkbox" id="mssql-th-check" class="rounded accent-sky-600" onclick="mssqlSelectAll(this.checked)" checked></th>
                <th class="px-3 py-2 text-left w-28">MSSQL ID</th>
                <th class="px-3 py-2 text-left">Ürün (Yerel)</th>
                <th class="px-3 py-2 text-left">Eski Ad / Fiyat</th>
                <th class="px-3 py-2 text-left">Yeni Ad / Fiyat (MSSQL)</th>
                <th class="px-3 py-2 text-left w-44">Grup / Gelir Merkezi</th>
              </tr>
            </thead>
            <tbody>` +
            mssqlMatchedData.map((item, idx) => {
                const oldName  = item.changes.name  ? item.changes.name.old  : item.local_name;
                const newName  = item.changes.name  ? item.changes.name.new  : item.local_name;
                const oldPrice = item.changes.price ? parseFloat(item.changes.price.old).toFixed(2) + ' TL' : '—';
                const newPrice = item.changes.price ? parseFloat(item.changes.price.new).toFixed(2) + ' TL' : '—';
                const nameCell = item.changes.name
                    ? `<span class="line-through text-red-500">${oldName}</span><br><span class="text-emerald-700 font-bold">${newName}</span>`
                    : `<span class="text-gray-400 text-xs">değişmiyor</span>`;
                const oldCell = `<div>${item.changes.name ? `<span class="line-through text-red-500">${oldName}</span>` : '<span class="text-gray-400">—</span>'}</div><div class="text-xs ${item.changes.price ? 'line-through text-red-500' : 'text-gray-400'}">${oldPrice}</div>`;
                const newCell = `<div>${item.changes.name ? `<span class="text-emerald-700 font-bold">${newName}</span>` : '<span class="text-gray-400">—</span>'}</div><div class="text-xs ${item.changes.price ? 'text-emerald-700 font-bold' : 'text-gray-400'}">${newPrice}</div>`;
                return `
                <tr class="border-t border-sky-100 hover:bg-sky-50">
                  <td class="px-3 py-2 text-center"><input type="checkbox" class="mssql-check rounded accent-sky-600" data-idx="${idx}" checked onchange="updateMssqlSelectedCount()"></td>
                  <td class="px-3 py-2"><span class="font-mono text-xs bg-sky-100 text-sky-800 px-1.5 py-0.5 rounded">${item.mssql_id}</span></td>
                  <td class="px-3 py-2"><div class="font-medium text-gray-800">${item.local_name}</div><div class="text-xs text-gray-400">#${item.local_id}</div></td>
                  <td class="px-3 py-2">${oldCell}</td>
                  <td class="px-3 py-2">${newCell}</td>
                  <td class="px-3 py-2"><div class="flex flex-col gap-1">${item.mssql_group ? `<span class="bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded text-xs">${item.mssql_group}</span>` : ''}${item.mssql_subgroup ? `<span class="bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded text-xs">${item.mssql_subgroup}</span>` : ''}${item.mssql_income_center ? `<span class="bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded text-xs">${item.mssql_income_center}</span>` : ''}</div></td>
                </tr>`;
            }).join('') +
            `</tbody></table></div>`;
    } else {
        html += '<div class="text-center py-8 text-gray-500"><p class="text-lg font-bold">Tum urunler guncel.</p></div>';
    }

    if (data.unmatched.length > 0) {
        html += '<h4 class="font-bold text-gray-900 mt-6 mb-3">Eşleşmeyen MSSQL Ürünleri</h4><div class="grid grid-cols-1 md:grid-cols-2 gap-2">';
        html += data.unmatched.map(item => `<div class="p-3 border border-gray-200 rounded bg-gray-50 text-sm"><div><span class="font-mono text-gray-500">${item.mssql_id}</span> - <span>${item.mssql_name}</span></div><div class="text-gray-400 mt-1">${parseFloat(item.mssql_price).toFixed(2)} TL</div><div class="flex flex-wrap gap-1 mt-2">${item.mssql_group ? `<span class="bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded text-xs">${item.mssql_group}</span>` : ''}${item.mssql_subgroup ? `<span class="bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded text-xs">${item.mssql_subgroup}</span>` : ''}${item.mssql_income_center ? `<span class="bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded text-xs">${item.mssql_income_center}</span>` : ''}</div></div>`).join('');
        html += '</div>';
    }

    document.getElementById('mssql-fetch-content').innerHTML = html;
    updateMssqlSelectedCount();
}

function mssqlSelectAll(checked) {
    document.querySelectorAll('.mssql-check').forEach(cb => cb.checked = checked);
    const th = document.getElementById('mssql-th-check');
    if (th) th.checked = checked;
    updateMssqlSelectedCount();
}

function updateMssqlSelectedCount() {
    const total = document.querySelectorAll('.mssql-check').length;
    const count = document.querySelectorAll('.mssql-check:checked').length;
    document.getElementById('mssql-fetch-count').textContent = total === 0
        ? 'Güncellenecek satır yok'
        : `${count} / ${total} satır seçildi`;
    document.getElementById('btn-apply-mssql').disabled = count === 0;
    const th = document.getElementById('mssql-th-check');
    if (th) {
        th.checked = (count === total && total > 0);
        th.indeterminate = (count > 0 && count < total);
    }
}

function closeMssqlPanel() {
    document.getElementById('mssql-panel').classList.add('hidden');
}

function closeMssqlFetch() { closeMssqlPanel(); }

function applyMssqlChanges() {
    const checkedIdx = Array.from(document.querySelectorAll('.mssql-check:checked')).map(cb => parseInt(cb.dataset.idx, 10));
    const updates = checkedIdx.map(idx => {
        const item = mssqlMatchedData[idx];
        const update = { local_id: item.local_id };
        if (item.changes.name) update.name = item.changes.name.new;
        if (item.changes.price) update.price = item.changes.price.new;
        return update;
    });
    if (updates.length === 0) return;
    fetch('/admin/sync/apply-mssql', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ updates })
    }).then(r => r.json()).then(data => {
        closeMssqlFetch();
        showMsg('success', `${updates.length} ürün güncellendi.`);
        data.results.forEach(item => {
            if (!item.changed) return;
            const row = document.querySelector(`tr[data-id="${item.id}"]`);
            if (row) updateRowDisplay(row, item.new);
        });
    }).catch(err => showMsg('error', 'MSSQL guncelleme hatasi: ' + err.message));
}
</script>
@endsection
