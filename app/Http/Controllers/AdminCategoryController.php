<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminCategoryController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['name', 'sort_order', 'products_count', 'is_active'];
        $sort         = in_array($request->get('sort'), $allowedSorts) ? $request->get('sort') : 'sort_order';
        $dir          = $request->get('dir') === 'desc' ? 'desc' : 'asc';
        $search       = trim((string) $request->get('search', ''));

        $perPageRaw = $request->get('per_page', 20);
        $perPage    = $perPageRaw === 'all'
            ? 9999
            : (in_array((int) $perPageRaw, [20, 50, 100, 200]) ? (int) $perPageRaw : 20);

        $categories = Category::withCount('products')
            ->with('parent')
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy($sort, $dir)
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.categories.index', compact('categories', 'sort', 'dir', 'search', 'perPageRaw'));
    }

    public function create()
    {
        $parentCategories = Category::whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.categories.form', compact('parentCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            // Sadece aktif (silinmemiş) kategorilerle unique kontrolü
            'name'        => ['required', 'string', 'max:255',
                              Rule::unique('categories', 'name')->whereNull('deleted_at')],
            'description' => 'nullable|string',
            'parent_id'   => 'nullable|exists:categories,id',
            'sort_order'  => 'integer|min:0',
        ]);

        // Aynı isimde soft-deleted kategori varsa geri yükle
        $trashed = Category::withTrashed()
            ->whereNotNull('deleted_at')
            ->where('name', $request->name)
            ->first();

        if ($trashed) {
            $trashed->restore();
            $trashed->update([
                'description' => $request->description,
                'parent_id'   => $request->parent_id ?: null,
                'sort_order'  => (int) $request->sort_order ?? 0,
                'is_active'   => $request->has('is_active'),
            ]);
            return redirect()->route('admin.categories.index')
                ->with('success', "'{$trashed->name}' kategorisi daha önce silinmişti, geri yüklendi ve güncellendi.");
        }

        $baseSlug = Str::slug($request->name) ?: 'kategori-' . uniqid();
        $slug     = $baseSlug;
        $suffix   = 1;
        while (Category::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $suffix++;
        }

        Category::create([
            'name'        => $request->name,
            'slug'        => $slug,
            'description' => $request->description,
            'parent_id'   => $request->parent_id ?: null,
            'sort_order'  => (int) ($request->sort_order ?? 0),
            'is_active'   => $request->has('is_active'),
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori başarıyla oluşturuldu.');
    }

    public function edit(Category $category)
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('sort_order')
            ->get();

        return view('admin.categories.form', compact('category', 'parentCategories'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255',
                              Rule::unique('categories', 'name')->ignore($category->id)->whereNull('deleted_at')],
            'description' => 'nullable|string',
            'parent_id'   => 'nullable|exists:categories,id',
            'sort_order'  => 'integer|min:0',
        ]);

        $baseSlug = Str::slug($request->name) ?: 'kategori-' . uniqid();
        $slug     = $baseSlug;
        $suffix   = 1;
        while (
            Category::withTrashed()
                ->where('slug', $slug)
                ->where('id', '!=', $category->id)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $suffix++;
        }

        $category->update([
            'name'        => $request->name,
            'slug'        => $slug,
            'description' => $request->description,
            'parent_id'   => $request->parent_id ?: null,
            'sort_order'  => (int) ($request->sort_order ?? 0),
            'is_active'   => $request->has('is_active'),
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori başarıyla güncellendi.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori başarıyla silindi.');
    }
}
