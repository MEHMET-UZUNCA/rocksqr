@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-box mr-2 text-gold"></i>Ürünler
                </h2>
                <a href="{{ route('admin.products.create') }}" class="px-4 py-2 bg-primary text-white rounded hover:bg-light-primary transition">
                    <i class="fas fa-plus mr-1"></i> Yeni Ürün
                </a>
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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Ürün Adı</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Fiyat</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase">Aktif/Pasif</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($products as $product)
                                <tr class="hover:bg-gray-50">
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
                                        <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ $product->description }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $product->category->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-700 font-semibold">{{ number_format($product->price, 2) }} ₺</td>
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
@endsection