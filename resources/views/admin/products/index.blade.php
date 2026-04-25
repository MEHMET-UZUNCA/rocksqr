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
                    <button type="button" onclick="enterBulkMode()" id="btn-bulk" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        <i class="fas fa-edit mr-1"></i> Toplu Güncelle
                    </button>
                    <button type="button" onclick="cancelBulk()" id="btn-cancel" class="hidden px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition">
                        <i class="fas fa-times mr-1"></i> İptal
                    </button>
                    <button type="button" onclick="previewChanges()" id="btn-preview" class="hidden px-4 py-2 bg-gold text-primary font-bold rounded hover:bg-yellow-500 transition">
                        <i class="fas fa-eye mr-1"></i> Değişiklikleri Önizle
                    </button>
                    <a href="{{ route('admin.products.create') }}" class="px-4 py-2 bg-primary text-white rounded hover:bg-light-primary transition">
                        <i class="fas fa-plus mr-1"></i> Yeni Ürün
                    </a>
                </div>
            </div>

            @if($products->isEmpty())
                <div class="p-6 text-center text-gray-500">
                    Ürün bulunamadı. <a href="{{ route('admin.products.create') }}" class="text-gold font-semibold">Yeni oluştur</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Görsel</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Product Code</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Ürün Adı</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Fiyat</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Aktif/Pasif</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($products as $product)
                                <tr class="hover:bg-gray-50" data-id="{{ $product->id }}">
                                    <td class="px-4 py-3">
                                        @if($product->photo_path)
                                            <img src="{{ asset('storage/' . $product->photo_path) }}" alt="{{ $product->name }}" class="w-12 h-12 object-cover rounded">
                                        @else
                                            <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400"></i>
                                            </div>
                                        @endif
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

function previewChanges() {
    const updates = collectChanges();
    if (updates.length === 0) {
        showBulkMsg('error', 'Herhangi bir değişiklik yapılmadı.');
        return;
    }
    fetch('/admin/sync/preview', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ updates })
    })
    .then(r => r.json())
    .then(data => {
        renderPreview(data.preview);
        document.getElementById('preview-count').textContent = data.total_changes + ' üründe değişiklik';
        document.getElementById('preview-modal').classList.remove('hidden');
        pendingUpdates = updates;
    })
    .catch(err => showBulkMsg('error', 'Önizleme hatası: ' + err.message));
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
</script>
@endsection