@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-folder mr-2 text-gold"></i>Kategoriler
                </h2>
                <a href="{{ route('admin.categories.create') }}" class="px-4 py-2 bg-primary text-white rounded hover:bg-light-primary transition">
                    <i class="fas fa-plus mr-1"></i> Yeni Kategori
                </a>
            </div>

            @if($categories->isEmpty())
                <div class="p-6 text-center text-gray-500">
                    Kategori bulunamadı. <a href="{{ route('admin.categories.create') }}" class="text-gold font-semibold">Yeni oluştur</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Ad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Üst Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Ürün Sayısı</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Sıra</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Durum</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($categories as $category)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-gray-900 font-medium">{{ $category->name }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $category->parent ? $category->parent->name : '—' }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $category->products_count }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $category->sort_order }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-xs {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $category->is_active ? 'Aktif' : 'Pasif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 space-x-2">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="text-blue-600 hover:underline">
                                            <i class="fas fa-edit"></i> Düzenle
                                        </a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?')">
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
                    {{ $categories->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection