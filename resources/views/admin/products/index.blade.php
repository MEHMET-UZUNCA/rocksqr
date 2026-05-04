@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-box mr-2 text-gold"></i>Ürünler
                </h2>
                <div class="flex flex-wrap gap-3">
                    <button type="button" onclick="openReorderModal()" id="btn-reorder" class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600 transition">
                        <i class="fas fa-sort mr-1"></i> Sıralama
                    </button>
                    <button type="button" onclick="enterBulkMode()" id="btn-bulk" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        <i class="fas fa-edit mr-1"></i> Toplu Güncelle
                    </button>
                    <button type="button" onclick="cancelBulk()" id="btn-cancel" class="hidden px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-1"></i> İptal
                    </button>
                    <button type="button" onclick="saveChanges()" id="btn-preview" class="hidden px-4 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 transition">
                        <i class="fas fa-save mr-1"></i> Kaydet
                    </button>
                    <a href="{{ route('admin.products.create') }}" class="px-4 py-2 bg-primary text-white rounded hover:bg-light-primary transition">
                        <i class="fas fa-plus mr-1"></i> Yeni Ürün
                    </a>
                </div>
            </div>

            {{-- Filtre Bar --}}
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/60">
                <form method="GET" action="{{ route('admin.products.index') }}" class="flex flex-wrap gap-2 items-center">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="dir"  value="{{ $dir }}">

                    <div class="relative flex-1 min-w-[180px]">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        <input type="text" name="search" value="{{ $search }}"
                               placeholder="Ürün adı veya kod..."
                               class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none bg-white">
                    </div>

                    <select name="category" onchange="this.form.submit()"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none bg-white min-w-[160px]">
                        <option value="">Tüm Kategoriler</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $filterCategory == $cat->id ? 'selected' : '' }}>
                                {{ $cat->parent ? $cat->parent->name . ' / ' . $cat->name : $cat->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="per_page" onchange="this.form.submit()"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none bg-white">
                        <option value="20"  @selected((string)$perPageRaw === '20')>20</option>
                        <option value="50"  @selected((string)$perPageRaw === '50')>50</option>
                        <option value="100" @selected((string)$perPageRaw === '100')>100</option>
                        <option value="200" @selected((string)$perPageRaw === '200')>200</option>
                        <option value="500" @selected((string)$perPageRaw === '500')>500</option>
                        <option value="all" @selected($perPageRaw === 'all')>Tümü</option>
                    </select>

                    <button type="submit" class="px-4 py-1.5 bg-amber-500 text-white rounded-lg text-sm font-semibold hover:bg-amber-600 transition">
                        <i class="fas fa-filter mr-1"></i> Filtrele
                    </button>

                    @if($search || $filterCategory)
                        <a href="{{ route('admin.products.index', ['sort' => $sort, 'dir' => $dir]) }}"
                           class="px-3 py-1.5 bg-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-300 transition">
                            <i class="fas fa-times mr-1"></i> Temizle
                        </a>
                    @endif

                    <span class="ml-auto text-xs text-gray-400 font-medium">
                        {{ number_format($products->total()) }} ürün
                        @if($search || $filterCategory) <span class="text-amber-500">(filtreli)</span> @endif
                    </span>
                </form>
            </div>

            @if($products->isEmpty())
                <div class="p-8 text-center text-gray-500">
                    @if($search || $filterCategory)
                        <i class="fas fa-search text-3xl text-gray-300 mb-3 block"></i>
                        <p class="font-semibold mb-2">Aramanıza uygun ürün bulunamadı.</p>
                        <a href="{{ route('admin.products.index', ['sort' => $sort, 'dir' => $dir]) }}"
                           class="text-amber-600 hover:underline text-sm"><i class="fas fa-times mr-1"></i> Filtreyi temizle</a>
                    @else
                        Ürün bulunamadı. <a href="{{ route('admin.products.create') }}" class="text-gold font-semibold">Yeni oluştur</a>
                    @endif
                </div>
            @else
                {{-- Selection Bar --}}
                <div id="selection-bar" class="hidden mx-6 mb-3 px-4 py-2 bg-blue-50 border border-blue-200 rounded-lg flex items-center gap-3">
                    <span class="text-sm font-semibold text-blue-800"><span id="selected-count">0</span> satır seçildi</span>
                    <div class="flex-1"></div>
                    <button onclick="confirmBulkDelete()" class="px-3 py-1 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700 transition">
                        <i class="fas fa-trash mr-1"></i> Seçilenleri Sil
                    </button>
                    <button onclick="clearSelection()" class="px-3 py-1 bg-gray-200 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-300 transition">
                        <i class="fas fa-times mr-1"></i> Seçimi Kaldır
                    </button>
                </div>

                @php
                    $sortIcon = function(string $col) use ($sort, $dir): string {
                        if ($sort === $col) {
                            return $dir === 'asc'
                                ? '<i class="fas fa-sort-up text-amber-500 ml-1 text-[10px]"></i>'
                                : '<i class="fas fa-sort-down text-amber-500 ml-1 text-[10px]"></i>';
                        }
                        return '<i class="fas fa-sort text-gray-300 ml-1 text-[10px] opacity-0 group-hover:opacity-100 transition-opacity"></i>';
                    };
                    $sortUrl = fn(string $col) => route('admin.products.index', [
                        'sort'     => $col,
                        'dir'      => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc',
                        'search'   => $search ?: null,
                        'category' => $filterCategory ?: null,
                        'per_page' => ($perPageRaw && $perPageRaw != 20) ? $perPageRaw : null,
                    ]);
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-3 py-3 text-center w-10">
                                    <a href="{{ $sortUrl('id') }}" class="group inline-flex items-center justify-center text-xs font-medium text-gray-500 uppercase hover:text-gray-800">
                                        N {!! $sortIcon('id') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-center w-8">
                                    <input type="checkbox" id="select-all" onchange="selectAll(this)" class="rounded cursor-pointer">
                                </th>
                                <th class="px-3 py-3 text-center w-12">
                                    <a href="{{ $sortUrl('sort_order') }}" class="group inline-flex items-center text-xs font-medium text-amber-600 uppercase hover:text-amber-800">
                                        # {!! $sortIcon('sort_order') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Görsel</th>
                                <th class="px-4 py-3 text-left">
                                    <a href="{{ $sortUrl('mssql_id') }}" class="group inline-flex items-center text-xs font-medium text-gray-700 uppercase hover:text-gray-900">
                                        Product Code {!! $sortIcon('mssql_id') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left">
                                    <a href="{{ $sortUrl('name') }}" class="group inline-flex items-center text-xs font-medium text-gray-700 uppercase hover:text-gray-900">
                                        Ürün Adı {!! $sortIcon('name') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left">
                                    <a href="{{ $sortUrl('category_id') }}" class="group inline-flex items-center text-xs font-medium text-gray-700 uppercase hover:text-gray-900">
                                        Kategori {!! $sortIcon('category_id') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left">
                                    <a href="{{ $sortUrl('price') }}" class="group inline-flex items-center text-xs font-medium text-gray-700 uppercase hover:text-gray-900">
                                        Fiyat {!! $sortIcon('price') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-center">
                                    <a href="{{ $sortUrl('is_available') }}" class="group inline-flex items-center text-xs font-medium text-gray-700 uppercase hover:text-gray-900">
                                        Aktif/Pasif {!! $sortIcon('is_available') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($products as $product)
                                <tr class="hover:bg-gray-50" data-id="{{ $product->id }}">
                                    <td class="px-3 py-3 text-center">
                                        <span class="text-xs text-gray-500 font-semibold font-mono select-none">{{ $products->firstItem() + $loop->index }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" class="row-check rounded cursor-pointer" value="{{ $product->id }}" onchange="onRowCheck()">
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <span class="inline-block min-w-[1.6rem] px-1.5 py-0.5 rounded text-xs font-mono font-bold bg-amber-50 text-amber-600 border border-amber-200">{{ $rankMap[$product->id] ?? '?' }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="w-12 h-12 rounded overflow-hidden bg-gray-100 flex items-center justify-center">
                                            <img src="{{ $product->photo_url }}"
                                                 alt="{{ $product->name }}"
                                                 class="w-full h-full {{ $product->has_photo ? 'object-cover' : 'object-contain p-1' }}"
                                                 onerror="this.src='{{ asset('images/product-placeholder.svg') }}';this.classList.remove('object-cover');this.classList.add('object-contain','p-1');">
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($product->mssql_id)
                                            <span class="px-2 py-1 rounded text-xs font-mono bg-sky-100 text-sky-800">{{ $product->mssql_id }}</span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 cell-name">
                                        <div class="view-mode">
                                            <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                            <div class="text-xs text-gray-500 truncate max-w-xs">{{ $product->description }}</div>
                                            <div class="mt-1 flex gap-1">
                                                @if($product->show_in_kitchen)
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800" title="Mutfak ekranında görünür"><i class="fas fa-utensils"></i> KDS</span>
                                                @endif
                                                @if($product->show_in_bar)
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800" title="Bar ekranında görünür"><i class="fas fa-wine-glass"></i> BAR</span>
                                                @endif
                                            </div>
                                        </div>
                                        <input type="text" class="edit-mode hidden w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                               data-field="name" value="{{ $product->name }}" data-original="{{ $product->name }}">
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 cell-category">
                                        <span class="view-mode">{{ $product->category->name ?? '—' }}</span>
                                        <select class="edit-mode hidden w-full px-2 py-1 border border-gray-300 rounded text-sm"
                                                data-field="category_id" data-original="{{ $product->category_id }}">
                                            <option value="">-- Kategori --</option>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>
                                                    {{ $cat->parent ? $cat->parent->name . ' / ' . $cat->name : $cat->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 font-semibold cell-price">
                                        <span class="view-mode">{{ number_format($product->price, 2) }} ₺</span>
                                        <input type="number" step="0.01" min="0" class="edit-mode hidden w-28 px-2 py-1 border border-gray-300 rounded text-sm"
                                               data-field="price" value="{{ $product->price }}" data-original="{{ $product->price }}">
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <form method="POST" action="{{ route('admin.products.toggle', $product) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $product->is_available ? 'bg-green-500' : 'bg-gray-300' }}">
                                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $product->is_available ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                            </button>
                                            <div class="text-xs mt-1 {{ $product->is_available ? 'text-green-600' : 'text-red-500' }}">
                                                {{ $product->is_available ? 'Aktif' : 'Pasif' }}
                                            </div>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 space-x-2">
                                        <a href="{{ route('admin.products.edit', $product) }}" class="text-blue-600 hover:underline">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </a>
                                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?')">
                                                <i class="fas fa-trash"></i> Sil
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Sıralama Düzenle Modalı --}}
<div id="reorder-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-sort mr-2 text-amber-500"></i>Sıralama Düzenle</h3>
            <button onclick="closeReorderModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto p-6" id="reorder-content">
            <div class="text-center py-10 text-gray-400"><i class="fas fa-spinner fa-spin text-3xl"></i></div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
            <p class="text-xs text-gray-400"><i class="fas fa-grip-vertical mr-1"></i> Sürükle-bırak ile sırala</p>
            <div class="flex gap-3">
                <button onclick="closeReorderModal()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 transition">İptal</button>
                <button id="btn-save-reorder" onclick="saveReorder()" class="px-6 py-2 bg-amber-500 text-white font-bold rounded hover:bg-amber-600 transition">
                    <i class="fas fa-save mr-1"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Toplu Sil Modalı --}}
<div id="bulk-delete-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-sm w-full">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                <i class="fas fa-trash text-red-600"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-900">Ürünleri Sil</h3>
                <p class="text-sm text-gray-500"><span id="delete-count">0</span> ürün silinecek</p>
            </div>
        </div>
        <div class="px-6 py-4">
            <p class="text-sm text-red-600 font-semibold"><i class="fas fa-exclamation-triangle mr-1"></i> Bu işlem geri alınamaz.</p>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
            <button onclick="closeBulkDeleteModal()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 transition">İptal</button>
            <button onclick="executeBulkDelete()" class="px-4 py-2 bg-red-600 text-white font-bold rounded hover:bg-red-700 transition">
                <i class="fas fa-trash mr-1"></i> Evet, Sil
            </button>
        </div>
    </div>
</div>

{{-- Toplu Güncelle Önizleme Modalı --}}
<div id="preview-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[80vh] overflow-hidden flex flex-col">
        <div class="px-6 py-4 bg-primary text-white flex justify-between items-center">
            <h3 class="text-lg font-bold"><i class="fas fa-eye mr-2"></i>Değişiklik Önizleme</h3>
            <button type="button" onclick="closePreview()" class="text-gray-300 hover:text-white"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 overflow-y-auto flex-1" id="preview-content"></div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
            <p class="text-sm text-gray-500" id="preview-count"></p>
            <div class="flex gap-3">
                <button type="button" onclick="closePreview()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 transition">İptal</button>
                <button type="button" onclick="applyChanges()" class="px-6 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 transition">
                    <i class="fas fa-check mr-1"></i> Onayla ve Güncelle
                </button>
            </div>
        </div>
    </div>
</div>

<div id="bulk-msg-success" class="hidden fixed top-4 right-4 z-50 px-4 py-3 bg-green-100 border border-green-300 text-green-800 rounded shadow-lg"></div>
<div id="bulk-msg-error" class="hidden fixed top-4 right-4 z-50 px-4 py-3 bg-red-100 border border-red-300 text-red-800 rounded shadow-lg"></div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
let pendingUpdates = [];

function enterBulkMode() {
    document.querySelectorAll('.view-mode').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.edit-mode').forEach(el => el.classList.remove('hidden'));
    document.getElementById('btn-bulk').classList.add('hidden');
    document.getElementById('btn-cancel').classList.remove('hidden');
    document.getElementById('btn-preview').classList.remove('hidden');
}

function cancelBulk() {
    document.querySelectorAll('.view-mode').forEach(el => el.classList.remove('hidden'));
    document.querySelectorAll('.edit-mode').forEach(el => {
        el.classList.add('hidden');
        el.value = el.dataset.original;
    });
    document.getElementById('btn-bulk').classList.remove('hidden');
    document.getElementById('btn-cancel').classList.add('hidden');
    document.getElementById('btn-preview').classList.add('hidden');
}

function collectChanges() {
    const updates = [];
    document.querySelectorAll('tr[data-id]').forEach(row => {
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

function showBulkMsg(type, msg) {
    const el = document.getElementById('bulk-msg-' + type);
    el.textContent = msg;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 4000);
}

function saveChanges() {
    const updates = collectChanges();
    if (updates.length === 0) {
        showBulkMsg('error', 'Herhangi bir değişiklik yapılmadı.');
        return;
    }
    const btn = document.getElementById('btn-preview');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Kaydediliyor...';
    fetch('/admin/sync/bulk-update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ updates })
    })
    .then(r => r.json())
    .then(data => {
        const changedCount = data.results.filter(r => r.changed).length;
        showBulkMsg('success', changedCount + ' ürün başarıyla güncellendi! Sayfa yenileniyor...');
        setTimeout(() => location.reload(), 1200);
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Kaydet';
        showBulkMsg('error', 'Güncelleme hatası: ' + err.message);
    });
}

function renderPreview(preview) {
    const fieldLabels = { name: 'Ürün Adı', price: 'Fiyat', category_id: 'Kategori' };
    const html = preview.map(item => {
        const changesHtml = Object.entries(item.changes).map(([field, vals]) => {
            const label = fieldLabels[field] || field;
            const isPrice = field === 'price';
            const oldVal = vals.old ?? '-';
            const newVal = vals.new ?? '-';
            const fmt = v => isPrice ? parseFloat(v).toFixed(2) + ' ₺' : v;
            return `<div class="flex items-center gap-3 py-2 border-b border-gray-100 last:border-0"><span class="text-xs text-gray-500 w-24">${label}</span><span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs line-through">${fmt(oldVal)}</span><i class="fas fa-arrow-right text-gray-400 text-xs"></i><span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold">${fmt(newVal)}</span></div>`;
        }).join('');
        return `<div class="mb-4 p-4 border border-gray-200 rounded-lg"><div class="flex items-center gap-2 mb-2"><span class="font-mono text-gold font-bold">#${item.id}</span><span class="font-semibold text-gray-800">${item.current_name}</span></div>${changesHtml}</div>`;
    }).join('');
    document.getElementById('preview-content').innerHTML = html || '<p class="text-gray-500 text-center py-6">Değişiklik bulunamadı.</p>';
}

function closePreview() {
    document.getElementById('preview-modal').classList.add('hidden');
}

function applyChanges() {
    fetch('/admin/sync/bulk-update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ updates: pendingUpdates })
    })
    .then(r => r.json())
    .then(data => {
        closePreview();
        const changedCount = data.results.filter(r => r.changed).length;
        showBulkMsg('success', changedCount + ' ürün başarıyla güncellendi! Sayfa yenileniyor...');
        setTimeout(() => location.reload(), 1200);
    })
    .catch(err => showBulkMsg('error', 'Güncelleme hatası: ' + err.message));
}

// Auto bulk mode if ?bulk=1
if (new URLSearchParams(window.location.search).get('bulk') === '1') {
    window.addEventListener('DOMContentLoaded', () => enterBulkMode());
}

// ── Sıralama (Reorder) ──────────────────────────────────────────────────────
let sortableInstances = [];

function openReorderModal() {
    document.getElementById('reorder-modal').classList.remove('hidden');
    document.getElementById('reorder-content').innerHTML =
        '<div class="text-center py-10 text-gray-400"><i class="fas fa-spinner fa-spin text-3xl"></i></div>';
    const btn = document.getElementById('btn-save-reorder');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save mr-1"></i> Kaydet';

    fetch('{{ route("admin.products.for-reorder") }}')
        .then(r => r.json())
        .then(data => renderReorderModal(data.products))
        .catch(() => {
            document.getElementById('reorder-content').innerHTML =
                '<p class="text-red-500 text-center py-6">Yükleme hatası.</p>';
        });
}

function renderReorderModal(products) {
    const groups = {};
    products.forEach(p => {
        const catName = p.category ? p.category.name : 'Kategorisiz';
        const catId   = p.category_id || 0;
        if (!groups[catId]) groups[catId] = { name: catName, items: [] };
        groups[catId].items.push(p);
    });

    let html = '';
    Object.values(groups).forEach(group => {
        html += `<div class="mb-5">
            <div class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2 px-1">${group.name} <span class="font-normal text-gray-300">(${group.items.length})</span></div>
            <ul class="sortable-list space-y-1">`;
        group.items.forEach((item, idx) => {
            html += `<li class="flex items-center gap-3 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg cursor-move select-none hover:bg-amber-50 hover:border-amber-200 transition" data-id="${item.id}">
                <span class="pos-num w-7 h-7 flex items-center justify-center rounded-full bg-amber-100 text-amber-700 text-xs font-bold flex-shrink-0">${idx + 1}</span>
                <i class="fas fa-grip-vertical text-gray-300 flex-shrink-0 text-xs"></i>
                <span class="text-sm font-medium text-gray-800 flex-1">${item.name}</span>
                <span class="text-[10px] text-gray-300 font-mono">so:${item.sort_order}</span>
            </li>`;
        });
        html += '</ul></div>';
    });

    document.getElementById('reorder-content').innerHTML = html || '<p class="text-gray-400 text-center py-6">Ürün bulunamadı.</p>';

    sortableInstances.forEach(s => s.destroy());
    sortableInstances = [];

    document.querySelectorAll('.sortable-list').forEach(list => {
        sortableInstances.push(Sortable.create(list, {
            animation: 150,
            ghostClass: 'opacity-40',
            chosenClass: 'bg-amber-100',
            onEnd: function(evt) {
                evt.to.querySelectorAll('li[data-id]').forEach((li, idx) => {
                    const numEl = li.querySelector('.pos-num');
                    if (numEl) numEl.textContent = idx + 1;
                });
            }
        }));
    });
}

function closeReorderModal() {
    document.getElementById('reorder-modal').classList.add('hidden');
}

function saveReorder() {
    const items = [];
    document.querySelectorAll('.sortable-list').forEach(list => {
        list.querySelectorAll('li[data-id]').forEach((li, idx) => {
            items.push({ id: parseInt(li.dataset.id), sort_order: idx * 10 });
        });
    });

    const btn = document.getElementById('btn-save-reorder');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Kaydediliyor...';

    fetch('{{ route("admin.products.reorder") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ items })
    })
    .then(r => r.json())
    .then(data => {
        closeReorderModal();
        showBulkMsg('success', `${data.updated} ürün sırası güncellendi.`);
        setTimeout(() => location.reload(), 1000);
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-1"></i> Kaydet';
        showBulkMsg('error', 'Kaydetme hatası: ' + err.message);
    });
}

function selectAll(masterCb) {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = masterCb.checked);
    onRowCheck();
}

function onRowCheck() {
    const checked = document.querySelectorAll('.row-check:checked');
    const total = document.querySelectorAll('.row-check');
    const masterCb = document.getElementById('select-all');
    masterCb.indeterminate = checked.length > 0 && checked.length < total.length;
    masterCb.checked = total.length > 0 && checked.length === total.length;
    document.getElementById('selected-count').textContent = checked.length;
    document.getElementById('selection-bar').classList.toggle('hidden', checked.length === 0);
}

function clearSelection() {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
    const masterCb = document.getElementById('select-all');
    masterCb.checked = false;
    masterCb.indeterminate = false;
    document.getElementById('selection-bar').classList.add('hidden');
}

function confirmBulkDelete() {
    const ids = Array.from(document.querySelectorAll('.row-check:checked')).map(cb => cb.value);
    if (ids.length === 0) return;
    document.getElementById('delete-count').textContent = ids.length;
    document.getElementById('bulk-delete-modal').classList.remove('hidden');
}

function closeBulkDeleteModal() {
    document.getElementById('bulk-delete-modal').classList.add('hidden');
}

function executeBulkDelete() {
    const ids = Array.from(document.querySelectorAll('.row-check:checked')).map(cb => parseInt(cb.value));
    closeBulkDeleteModal();
    fetch('{{ route("admin.sync.bulk-delete") }}', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ ids })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { showBulkMsg('error', data.message || 'Silme başarısız.'); return; }
        ids.forEach(id => {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) row.remove();
        });
        clearSelection();
        showBulkMsg('success', `${data.deleted} ürün silindi.`);
    })
    .catch(err => showBulkMsg('error', 'Silme hatası: ' + err.message));
}
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
@endsection