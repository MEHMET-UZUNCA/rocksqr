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

            {{-- Filtre Bar --}}
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/60">
                <form method="GET" action="{{ route('admin.categories.index') }}" class="flex flex-wrap gap-2 items-center">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="dir"  value="{{ $dir }}">

                    <div class="relative flex-1 min-w-[180px]">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        <input type="text" name="search" value="{{ $search }}"
                               placeholder="Kategori adı..."
                               class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none bg-white">
                    </div>

                    <select name="per_page" onchange="this.form.submit()"
                            class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none bg-white">
                        <option value="20"  @selected((string)$perPageRaw === '20')>20</option>
                        <option value="50"  @selected((string)$perPageRaw === '50')>50</option>
                        <option value="100" @selected((string)$perPageRaw === '100')>100</option>
                        <option value="200" @selected((string)$perPageRaw === '200')>200</option>
                        <option value="all" @selected($perPageRaw === 'all')>Tümü</option>
                    </select>

                    <button type="submit" class="px-4 py-1.5 bg-amber-500 text-white rounded-lg text-sm font-semibold hover:bg-amber-600 transition">
                        <i class="fas fa-filter mr-1"></i> Filtrele
                    </button>

                    @if($search)
                        <a href="{{ route('admin.categories.index', ['sort' => $sort, 'dir' => $dir]) }}"
                           class="px-3 py-1.5 bg-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-300 transition">
                            <i class="fas fa-times mr-1"></i> Temizle
                        </a>
                    @endif

                    <span class="ml-auto text-xs text-gray-400 font-medium">
                        {{ number_format($categories->total()) }} kategori
                        @if($search) <span class="text-amber-500">(filtreli)</span> @endif
                    </span>
                </form>
            </div>

            @if($categories->isEmpty())
                <div class="p-8 text-center text-gray-500">
                    @if($search)
                        <i class="fas fa-search text-3xl text-gray-300 mb-3 block"></i>
                        <p class="font-semibold mb-2">Aramanıza uygun kategori bulunamadı.</p>
                        <a href="{{ route('admin.categories.index', ['sort' => $sort, 'dir' => $dir]) }}"
                           class="text-amber-600 hover:underline text-sm"><i class="fas fa-times mr-1"></i> Filtreyi temizle</a>
                    @else
                        Kategori bulunamadı. <a href="{{ route('admin.categories.create') }}" class="text-gold font-semibold">Yeni oluştur</a>
                    @endif
                </div>
            @else
                @php
                    $sortIcon = function(string $col) use ($sort, $dir): string {
                        if ($sort === $col) {
                            return $dir === 'asc'
                                ? '<i class="fas fa-sort-up text-amber-500 ml-1 text-[10px]"></i>'
                                : '<i class="fas fa-sort-down text-amber-500 ml-1 text-[10px]"></i>';
                        }
                        return '<i class="fas fa-sort text-gray-300 ml-1 text-[10px] opacity-0 group-hover:opacity-100 transition-opacity"></i>';
                    };
                    $sortUrl = fn(string $col) => route('admin.categories.index', [
                        'sort'     => $col,
                        'dir'      => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc',
                        'search'   => $search ?: null,
                        'per_page' => ($perPageRaw && $perPageRaw != 20) ? $perPageRaw : null,
                    ]);
                @endphp
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-center w-10 text-xs font-medium text-gray-500 uppercase">N</th>
                                <th class="px-6 py-3 text-left">
                                    <a href="{{ $sortUrl('name') }}" class="group inline-flex items-center text-xs font-medium text-gray-700 uppercase hover:text-gray-900">
                                        Ad {!! $sortIcon('name') !!}
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Üst Kategori</th>
                                <th class="px-6 py-3 text-left">
                                    <a href="{{ $sortUrl('products_count') }}" class="group inline-flex items-center text-xs font-medium text-gray-700 uppercase hover:text-gray-900">
                                        Ürün Sayısı {!! $sortIcon('products_count') !!}
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left">
                                    <a href="{{ $sortUrl('sort_order') }}" class="group inline-flex items-center text-xs font-medium text-amber-600 uppercase hover:text-amber-800">
                                        Sıra {!! $sortIcon('sort_order') !!}
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left">
                                    <a href="{{ $sortUrl('is_active') }}" class="group inline-flex items-center text-xs font-medium text-gray-700 uppercase hover:text-gray-900">
                                        Durum {!! $sortIcon('is_active') !!}
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($categories as $category)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 text-center">
                                        <span class="text-xs text-gray-500 font-semibold font-mono select-none">{{ $categories->firstItem() + $loop->index }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-900 font-medium">{{ $category->name }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $category->parent ? $category->parent->name : '—' }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $category->products_count }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-block min-w-[1.6rem] px-1.5 py-0.5 rounded text-xs font-mono font-bold bg-amber-50 text-amber-600 border border-amber-200">{{ $category->sort_order }}</span>
                                    </td>
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
