@extends('layouts.admin')

@section('content')
<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-box mr-2 text-gold"></i>
                    {{ isset($product) ? 'Ürün Düzenle' : 'Yeni Ürün' }}
                </h2>

                <form method="POST" 
                      action="{{ isset($product) ? route('admin.products.update', $product) : route('admin.products.store') }}"
                      enctype="multipart/form-data"
                      class="space-y-6">
                    @csrf
                    @if(isset($product))
                        @method('PUT')
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                        <select name="category_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('category_id') border-red-500 @enderror">
                            <option value="">Kategori seçin</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                        {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->parent ? '— ' . $category->name : $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ürün Adı</label>
                        <input type="text" name="name" required 
                               value="{{ old('name', $product->name ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Açıklama</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">{{ old('description', $product->description ?? '') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fiyat (₺)</label>
                        <input type="number" name="price" step="0.01" required 
                               value="{{ old('price', $product->price ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent @error('price') border-red-500 @enderror">
                        @error('price')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fotoğraf</label>
                        @if(isset($product) && $product->photo_path)
                            <div class="mb-4">
                                <img src="{{ $product->photo_url }}" alt="{{ $product->name }}" class="w-40 h-40 object-cover rounded">
                                <p class="text-xs text-gray-500 mt-2">Mevcut fotoğraf</p>
                            </div>
                        @endif
                        <input type="file" name="photo" accept="image/*"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Maks 2MB. Formatlar: JPEG, PNG, JPG, GIF</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sıralama</label>
                        <input type="number" name="sort_order" min="0" 
                               value="{{ old('sort_order', $product->sort_order ?? 0) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Oracle ID <span class="text-gray-400 text-xs">(harici sistem entegrasyonu)</span></label>
                        <input type="text" name="oracle_id" 
                               value="{{ old('oracle_id', $product->oracle_id ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                               placeholder="Örn: ORC-1234">
                        <p class="text-xs text-gray-500 mt-1">Oracle POS ürün ID'si. Sync işleminde eşleştirme için kullanılır.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">MSSQL ID <span class="text-gray-400 text-xs">(Symphony entegrasyonu)</span></label>
                        <input type="text" name="mssql_id"
                               value="{{ old('mssql_id', $product->mssql_id ?? '') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                               placeholder="Örn: SYM-1234">
                        <p class="text-xs text-gray-500 mt-1">Symphony Restaurant MSSQL ürün ID'si. Sync işleminde eşleştirme için kullanılır.</p>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_available" value="1" 
                                   {{ old('is_available', $product->is_available ?? true) ? 'checked' : '' }}
                                   class="rounded">
                            <span class="ml-2 text-sm text-gray-700">Satışta</span>
                        </label>
                    </div>

                    <div class="flex gap-4">
                        <button type="submit" class="px-6 py-2 bg-primary text-white rounded hover:bg-light-primary transition">
                            <i class="fas fa-save mr-1"></i> {{ isset($product) ? 'Güncelle' : 'Oluştur' }}
                        </button>
                        <a href="{{ route('admin.products.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition">
                            İptal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection