<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts   = ['id', 'sort_order', 'name', 'price', 'category_id', 'is_available', 'mssql_id'];
        $sort           = in_array($request->get('sort'), $allowedSorts) ? $request->get('sort') : 'sort_order';
        $dir            = $request->get('dir') === 'desc' ? 'desc' : 'asc';
        $search         = trim((string) $request->get('search', ''));
        $filterCategory = (int) $request->get('category', 0);

        $perPageRaw = $request->get('per_page', 20);
        $perPage    = $perPageRaw === 'all'
            ? 9999
            : (in_array((int) $perPageRaw, [20, 50, 100, 200, 500]) ? (int) $perPageRaw : 20);

        $products = Product::with('category.parent')
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")
                   ->orWhere('mssql_id', 'like', "%{$search}%");
            }))
            ->when($filterCategory, fn ($q) => $q->where('category_id', $filterCategory))
            ->orderBy($sort, $dir)
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        $categories = Category::with('parent')->orderBy('parent_id')->orderBy('sort_order')->orderBy('name')->get();

        // Per-category rank based on sort_order — independent of current filters/sort
        $rankMap     = [];
        $catCounters = [];
        Product::orderBy('category_id')->orderBy('sort_order')->orderBy('id')
            ->get(['id', 'category_id'])
            ->each(function ($p) use (&$rankMap, &$catCounters) {
                $catCounters[$p->category_id] = ($catCounters[$p->category_id] ?? 0) + 1;
                $rankMap[$p->id] = $catCounters[$p->category_id];
            });

        return view('admin.products.index', compact(
            'products', 'categories', 'sort', 'dir', 'rankMap', 'search', 'filterCategory', 'perPageRaw'
        ));
    }

    public function create()
    {
        $categories = Category::orderBy('sort_order')->get();
        return view('admin.products.form', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'photo'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sort_order'  => 'integer|min:0',
            'mssql_id'    => ['nullable', 'string', 'max:255',
                              Rule::unique('products', 'mssql_id')->whereNull('deleted_at')],
        ]);

        $validated['is_available'] = $request->has('is_available');
        $validated['show_in_kitchen'] = $request->has('show_in_kitchen');
        $validated['show_in_bar'] = $request->has('show_in_bar');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('products', 'public');
        }

        unset($validated['photo']);
        Product::create($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Ürün başarıyla oluşturuldu.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('sort_order')->get();
        return view('admin.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'photo'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sort_order'  => 'integer|min:0',
            'mssql_id'    => ['nullable', 'string', 'max:255',
                              Rule::unique('products', 'mssql_id')->ignore($product->id)->whereNull('deleted_at')],
        ]);

        $validated['is_available'] = $request->has('is_available');
        $validated['show_in_kitchen'] = $request->has('show_in_kitchen');
        $validated['show_in_bar'] = $request->has('show_in_bar');

        if ($request->hasFile('photo')) {
            if ($product->photo_path) {
                Storage::disk('public')->delete($product->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('products', 'public');
        }

        unset($validated['photo']);
        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Ürün başarıyla güncellendi.');
    }

    public function destroy(Product $product)
    {
        if ($product->photo_path) {
            Storage::disk('public')->delete($product->photo_path);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Ürün başarıyla silindi.');
    }

    public function forReorder(): JsonResponse
    {
        $products = Product::with('category:id,name')
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'category_id', 'sort_order']);

        return response()->json(['products' => $products]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.id'         => 'required|integer|exists:products,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->items as $item) {
            Product::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['success' => true, 'updated' => count($request->items)]);
    }

    public function toggle(Product $product)
    {
        $product->update(['is_available' => !$product->is_available]);

        $status = $product->is_available ? 'aktif' : 'pasif';
        return redirect()->route('admin.products.index')
            ->with('success', "{$product->name} artık {$status}.");
    }
}
