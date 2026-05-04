<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncController extends Controller
{
    public function index()
    {
        $products = Product::with('category.parent')->orderBy('id')->get();
        $categories = Category::with('parent')->orderBy('parent_id')->orderBy('sort_order')->orderBy('name')->get();

        $mssqlConfigured = Setting::get('mssql_host', '') !== '' && Setting::get('mssql_database', '') !== '' && trim((string) Setting::get('mssql_custom_query', '')) !== '';
        $symphonyConfigured = Setting::get('mssql_host', '') !== '' && Setting::get('mssql_database', '') !== '';

        return view('admin.sync', compact('products', 'categories', 'mssqlConfigured', 'symphonyConfigured'));
    }

    public function apiProducts()
    {
        $products = Product::orderBy('id')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'mssql_id' => $product->mssql_id,
                'is_available' => $product->is_available,
            ];
        });

        return response()->json(['products' => $products]);
    }

    public function updateMssqlId(Request $request, Product $product)
    {
        $request->validate([
            'mssql_id' => 'nullable|string|max:255',
        ]);

        $old = $product->mssql_id;
        $product->update(['mssql_id' => $request->mssql_id]);

        return response()->json([
            'success' => true,
            'old_mssql_id' => $old,
            'new_mssql_id' => $product->mssql_id,
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1|max:200',
            'ids.*' => 'integer|exists:products,id',
        ]);

        $deleted = Product::whereIn('id', $request->ids)->delete();

        return response()->json(['success' => true, 'deleted' => $deleted]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.id' => 'required|exists:products,id',
            'updates.*.name' => 'nullable|string|max:255',
            'updates.*.price' => 'nullable|numeric|min:0',
            'updates.*.mssql_id' => 'nullable|string|max:255',
            'updates.*.category_id' => 'nullable|exists:categories,id',
        ]);

        $results = [];
        foreach ($request->updates as $update) {
            $product = Product::find($update['id']);
            $old = [
                'name' => $product->name,
                'price' => $product->price,
                'mssql_id' => $product->mssql_id,
                'category_id' => $product->category_id,
            ];

            $data = [];
            if (isset($update['name']) && $update['name'] !== '') {
                $data['name'] = $update['name'];
            }
            if (isset($update['price']) && $update['price'] !== '') {
                $data['price'] = $update['price'];
            }
            if (array_key_exists('mssql_id', $update)) {
                $data['mssql_id'] = $update['mssql_id'];
            }
            if (isset($update['category_id']) && $update['category_id'] !== '' && (int)$update['category_id'] !== (int)$product->category_id) {
                $data['category_id'] = (int)$update['category_id'];
            }

            if (!empty($data)) {
                $product->update($data);
                $product->refresh();
            }

            $results[] = [
                'id' => $product->id,
                'old' => $old,
                'new' => [
                    'name' => $product->name,
                    'price' => $product->price,
                    'mssql_id' => $product->mssql_id,
                    'category_id' => $product->category_id,
                ],
                'changed' => !empty($data),
            ];
        }

        return response()->json(['success' => true, 'results' => $results]);
    }

    public function previewBulk(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.id' => 'required|exists:products,id',
            'updates.*.name' => 'nullable|string|max:255',
            'updates.*.price' => 'nullable|numeric|min:0',
            'updates.*.mssql_id' => 'nullable|string|max:255',
            'updates.*.category_id' => 'nullable|exists:categories,id',
        ]);

        $preview = [];
        foreach ($request->updates as $update) {
            $product = Product::find($update['id']);
            $changes = [];

            if (isset($update['name']) && $update['name'] !== '' && $update['name'] !== $product->name) {
                $changes['name'] = ['old' => $product->name, 'new' => $update['name']];
            }
            if (isset($update['price']) && $update['price'] !== '' && (float)$update['price'] !== (float)$product->price) {
                $changes['price'] = ['old' => $product->price, 'new' => (float)$update['price']];
            }
            if (array_key_exists('mssql_id', $update) && $update['mssql_id'] !== $product->mssql_id) {
                $changes['mssql_id'] = ['old' => $product->mssql_id, 'new' => $update['mssql_id']];
            }
            if (isset($update['category_id']) && $update['category_id'] !== '' && (int)$update['category_id'] !== (int)$product->category_id) {
                $oldCat = $product->category ? ($product->category->parent ? $product->category->parent->name . ' / ' . $product->category->name : $product->category->name) : '-';
                $newCatModel = Category::with('parent')->find($update['category_id']);
                $newCat = $newCatModel ? ($newCatModel->parent ? $newCatModel->parent->name . ' / ' . $newCatModel->name : $newCatModel->name) : '-';
                $changes['category_id'] = ['old' => $oldCat, 'new' => $newCat];
            }

            if (!empty($changes)) {
                $preview[] = [
                    'id' => $product->id,
                    'current_name' => $product->name,
                    'changes' => $changes,
                ];
            }
        }

        return response()->json(['preview' => $preview, 'total_changes' => count($preview)]);
    }

    public function fetchMssql()
    {
        $host = Setting::get('mssql_host', '');
        $port = Setting::get('mssql_port', '1433');
        $database = Setting::get('mssql_database', '');
        $username = Setting::get('mssql_username', '');
        $password = Setting::get('mssql_password', '');
        $colId = Setting::get('mssql_column_id', 'ID');
        $colName = Setting::get('mssql_column_name', 'NAME');
        $colPrice = Setting::get('mssql_column_price', 'PRICE');
        $colGroup = Setting::get('mssql_column_group', 'PRODUCT_GROUP');
        $colIncomeCenter = Setting::get('mssql_column_income_center', 'RVC');
        $incomeCenterFilter = trim((string) Setting::get('mssql_income_center_filter', ''));
        $customQuery = trim((string) Setting::get('mssql_custom_query', ''));

        if (!$host || !$database || $customQuery === '') {
            return response()->json(['success' => false, 'error' => 'MSSQL bağlantı ayarları eksik. Özel SQL sorgusu girilmemiş.'], 422);
        }

        // Local products that have a product code (mssql_id)
        $localProducts = Product::whereNotNull('mssql_id')->where('mssql_id', '!=', '')->orderBy('name')->get();

        if ($localProducts->isEmpty()) {
            return response()->json([
                'success' => true,
                'items' => [],
                'stats' => ['total' => 0, 'changed' => 0, 'same' => 0, 'not_found' => 0],
                'income_center_filter' => $incomeCenterFilter,
            ]);
        }

        try {
            $decryptedPassword = '';
            if ($password) {
                try {
                    $decryptedPassword = decrypt($password);
                } catch (\Exception $e) {
                    $decryptedPassword = $password;
                }
            }

            $pdo = new \PDO("sqlsrv:Server={$host},{$port};Database={$database};TrustServerCertificate=1", $username, $decryptedPassword);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare($customQuery);
            $stmt->execute();
            $mssqlRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($incomeCenterFilter !== '') {
                $mssqlRows = $this->applyIncomeCenterFilter($mssqlRows, $colIncomeCenter, $incomeCenterFilter);
            }

            $mssqlRows = $this->dedupeByExternalId($mssqlRows, $colId);

            // Build MSSQL lookup map: product_code → row
            $mssqlMap = [];
            foreach ($mssqlRows as $row) {
                $exId = (string) $this->resolveMssqlValue($row, [$colId, 'external_id', 'id', 'mssql_id', 'product_id', 'menu_item_id', 'ProductCode', 'product_code'], '');
                if ($exId !== '') {
                    $mssqlMap[$exId] = $row;
                }
            }

            $items = [];
            $stats = ['total' => 0, 'changed' => 0, 'same' => 0, 'not_found' => 0];

            foreach ($localProducts as $local) {
                $stats['total']++;
                $productCode = (string) $local->mssql_id;

                if (!isset($mssqlMap[$productCode])) {
                    $stats['not_found']++;
                    $items[] = [
                        'local_id'     => $local->id,
                        'product_code' => $productCode,
                        'local_name'   => $local->name,
                        'local_price'  => (float) $local->price,
                        'status'       => 'not_found',
                        'mssql_name'   => null,
                        'mssql_price'  => null,
                        'mssql_group'  => null,
                        'changes'      => [],
                    ];
                    continue;
                }

                $row          = $mssqlMap[$productCode];
                $externalName = (string) $this->resolveMssqlValue($row, [$colName, 'product_name', 'name', 'mssql_name', 'item_name', 'ProductName'], '');
                $externalPrice = (float) $this->resolveMssqlValue($row, [$colPrice, 'price', 'mssql_price', 'sales_price', 'product_price', 'Price'], 0);
                $externalGroup = (string) $this->resolveMssqlValue($row, [$colGroup, 'product_group', 'group_name', 'major_group', 'main_group', 'FamilyGroup'], '');

                $changes = [];
                if ($externalName && $externalName !== $local->name) {
                    $changes['name'] = ['old' => $local->name, 'new' => $externalName];
                }
                if ($externalPrice > 0 && $externalPrice !== (float) $local->price) {
                    $changes['price'] = ['old' => (float) $local->price, 'new' => $externalPrice];
                }

                $status = empty($changes) ? 'same' : 'changed';
                $stats[$status]++;

                $items[] = [
                    'local_id'     => $local->id,
                    'product_code' => $productCode,
                    'local_name'   => $local->name,
                    'local_price'  => (float) $local->price,
                    'status'       => $status,
                    'mssql_name'   => $externalName,
                    'mssql_price'  => $externalPrice,
                    'mssql_group'  => $externalGroup,
                    'changes'      => $changes,
                ];
            }

            // Sort: changed first, then not_found, then same
            usort($items, function ($a, $b) {
                $order = ['changed' => 0, 'not_found' => 1, 'same' => 2];
                return $order[$a['status']] <=> $order[$b['status']];
            });

            return response()->json([
                'success'              => true,
                'items'                => $items,
                'stats'                => $stats,
                'income_center_filter' => $incomeCenterFilter,
            ]);
        } catch (\PDOException $e) {
            return response()->json(['success' => false, 'error' => 'MSSQL bağlantı hatası: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Hata: ' . $e->getMessage()], 500);
        }
    }

    public function applyMssql(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.local_id' => 'required|exists:products,id',
            'updates.*.name' => 'nullable|string|max:255',
            'updates.*.price' => 'nullable|numeric|min:0',
        ]);

        $results = [];
        foreach ($request->updates as $update) {
            $product = Product::find($update['local_id']);
            $old = ['name' => $product->name, 'price' => $product->price];
            $data = [];

            if (!empty($update['name'])) {
                $data['name'] = $update['name'];
            }
            if (isset($update['price']) && $update['price'] !== null) {
                $data['price'] = $update['price'];
            }

            if (!empty($data)) {
                $product->update($data);
                $product->refresh();
            }

            $results[] = [
                'id' => $product->id,
                'old' => $old,
                'new' => ['name' => $product->name, 'price' => $product->price],
                'changed' => !empty($data),
            ];
        }

        return response()->json(['success' => true, 'results' => $results]);
    }

    public function symphonyFetch()
    {
        $host               = Setting::get('mssql_host', '');
        $port               = Setting::get('mssql_port', '1433');
        $database           = Setting::get('mssql_database', '');
        $username           = Setting::get('mssql_username', '');
        $password           = Setting::get('mssql_password', '');
        $table              = Setting::get('mssql_table', '');
        $colId              = Setting::get('mssql_column_id', 'ID');
        $colName            = Setting::get('mssql_column_name', 'NAME');
        $colPrice           = Setting::get('mssql_column_price', 'PRICE');
        $colGroup           = Setting::get('mssql_column_group', 'PRODUCT_GROUP');
        $colSubgroup        = Setting::get('mssql_column_subgroup', 'SUBGROUP');
        $colIncomeCenter    = Setting::get('mssql_column_income_center', 'RVC');
        $incomeCenterFilter = trim((string) Setting::get('mssql_income_center_filter', ''));
        $customQuery        = trim((string) Setting::get('mssql_custom_query', ''));

        if (!$host || !$database || $customQuery === '') {
            return response()->json(['success' => false, 'error' => 'MSSQL ayarları eksik. Özel SQL sorgusu girilmemiş.'], 422);
        }

        try {
            $decryptedPassword = '';
            if ($password) {
                try { $decryptedPassword = decrypt($password); } catch (\Exception $e) { $decryptedPassword = $password; }
            }

            $pdo = new \PDO("sqlsrv:Server={$host},{$port};Database={$database};TrustServerCertificate=1", $username, $decryptedPassword);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare($customQuery);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if ($incomeCenterFilter !== '') {
                $rows = $this->applyIncomeCenterFilter($rows, $colIncomeCenter, $incomeCenterFilter);
            }

            // Dedupe: aynı ProductCode birden fazla fiyat seviyesi (HierStrucID) ile gelirse,
            // en yüksek HierStrucID (RVC > Property > Enterprise) önceliklidir.
            $rows = $this->dedupeByExternalId($rows, $colId);

            $localProducts = Product::whereNotNull('mssql_id')->where('mssql_id', '!=', '')->with('category')->get()->keyBy('mssql_id');
            $existingCatNames = Category::whereNull('deleted_at')->pluck('name')
                ->map(fn ($n) => strtolower(trim($n)))->flip()->toArray();

            $groups = [];
            foreach ($rows as $row) {
                $externalId    = (string)  $this->resolveMssqlValue($row, [$colId, 'external_id', 'id', 'mssql_id', 'product_id', 'ProductCode', 'product_code'], '');
                $externalName  = (string)  $this->resolveMssqlValue($row, [$colName, 'name', 'product_name', 'item_name', 'ProductName'], '');
                $externalPrice = (float)   $this->resolveMssqlValue($row, [$colPrice, 'price', 'sales_price', 'Price'], 0);
                $externalGroup = (string)  $this->resolveMssqlValue($row, [$colGroup, 'family_group', 'product_group', 'group_name', 'major_group', 'FamilyGroup'], '');
                $externalSub   = (string)  $this->resolveMssqlValue($row, [$colSubgroup, 'family_group', 'subgroup', 'sub_group', 'FamilyGroup'], '');

                // family_group = subgroup if present, else group
                $categoryName = trim($externalSub ?: $externalGroup ?: 'Kategorisiz');

                if ($externalId === '' || $externalName === '') continue;

                $status  = 'new';
                $localId = null;
                $changes = [];

                if ($localProducts->has($externalId)) {
                    $local   = $localProducts->get($externalId);
                    $localId = $local->id;
                    if ($externalName !== $local->name) {
                        $changes['name'] = ['old' => $local->name, 'new' => $externalName];
                    }
                    if ($externalPrice > 0 && abs($externalPrice - (float) $local->price) > 0.001) {
                        $changes['price'] = ['old' => (float) $local->price, 'new' => $externalPrice];
                    }
                    $status = empty($changes) ? 'exists' : 'changed';
                }

                if (!isset($groups[$categoryName])) {
                    $groups[$categoryName] = [
                        'name'             => $categoryName,
                        'category_exists'  => isset($existingCatNames[strtolower($categoryName)]),
                        'items'            => [],
                    ];
                }

                $groups[$categoryName]['items'][] = [
                    'mssql_id'     => $externalId,
                    'name'         => $externalName,
                    'price'        => $externalPrice,
                    'family_group' => $categoryName,
                    'status'       => $status,
                    'local_id'     => $localId,
                    'changes'      => $changes,
                ];
            }

            // Sort: groups with new/changed items first
            uasort($groups, function ($a, $b) {
                $aHas = count(array_filter($a['items'], fn ($i) => $i['status'] !== 'exists'));
                $bHas = count(array_filter($b['items'], fn ($i) => $i['status'] !== 'exists'));
                return $bHas <=> $aHas;
            });

            return response()->json([
                'success'       => true,
                'groups'        => array_values($groups),
                'total'         => count($rows),
                'total_groups'  => count($groups),
            ]);
        } catch (\PDOException $e) {
            return response()->json(['success' => false, 'error' => 'MSSQL bağlantı hatası: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Hata: ' . $e->getMessage()], 500);
        }
    }

    public function symphonyImport(Request $request)
    {
        $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.mssql_id'     => 'required|string|max:255',
            'items.*.name'         => 'required|string|max:255',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.family_group' => 'required|string|max:255',
        ]);

        $createdProducts   = 0;
        $updatedProducts   = 0;
        $createdCategories = 0;
        $categoryCache     = [];
        $categorySort      = [];

        try {
        foreach ($request->items as $item) {
            $groupName = trim($item['family_group']);

            if (!isset($categoryCache[$groupName])) {
                // MySQL UNIQUE ignores soft-delete — exact + case-insensitive lookup, both withTrashed
                $category = Category::withTrashed()->where('name', $groupName)->first()
                    ?? Category::withTrashed()->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($groupName))])->first();

                if ($category) {
                    if ($category->trashed()) $category->restore();
                } else {
                    $baseSlug = Str::slug($groupName) ?: 'kategori-' . uniqid();
                    $slug     = $baseSlug;
                    $suffix   = 1;
                    // slug kontrolü de withTrashed — slug UNIQUE kısıtlı
                    while (Category::withTrashed()->where('slug', $slug)->exists()) {
                        $slug = $baseSlug . '-' . $suffix++;
                    }
                    try {
                        $category = Category::create([
                            'name'       => $groupName,
                            'slug'       => $slug,
                            'is_active'  => true,
                            'sort_order' => 0,
                        ]);
                        $createdCategories++;
                    } catch (\Illuminate\Database\QueryException $e) {
                        if ((int) ($e->errorInfo[1] ?? 0) !== 1062) throw $e;
                        // Unique ihlali — son kez tüm yollarla ara ve restore et
                        $category = Category::withTrashed()->where('name', $groupName)->first()
                            ?? Category::withTrashed()->where('slug', $slug)->first();
                        if (!$category) throw $e;
                        if ($category->trashed()) $category->restore();
                    }
                }
                $categoryCache[$groupName] = $category->id;
            }

            $categoryId = $categoryCache[$groupName];

            if (!isset($categorySort[$groupName])) {
                $categorySort[$groupName] = 0;
            }
            $importSortOrder = $categorySort[$groupName]++ * 10;

            // withTrashed: mssql_id UNIQUE kısıtlı — soft-deleted kayıt varsa unique constraint patlar
            $existing = Product::withTrashed()->where('mssql_id', $item['mssql_id'])->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update([
                    'name'        => $item['name'],
                    'price'       => $item['price'],
                    'category_id' => $categoryId,
                ]);
                $updatedProducts++;
            } else {
                Product::create([
                    'name'         => $item['name'],
                    'price'        => $item['price'],
                    'mssql_id'     => $item['mssql_id'],
                    'category_id'  => $categoryId,
                    'is_available' => true,
                    'sort_order'   => $importSortOrder,
                ]);
                $createdProducts++;
            }
        }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'İçe aktarma hatası: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success'            => true,
            'created_products'   => $createdProducts,
            'updated_products'   => $updatedProducts,
            'created_categories' => $createdCategories,
            'message'            => "{$createdProducts} yeni ürün eklendi, {$updatedProducts} ürün güncellendi, {$createdCategories} yeni kategori oluşturuldu.",
        ]);
    }

    private function applyIncomeCenterFilter(array $rows, string $colIncomeCenter, string $filter): array
    {
        $needle  = strtolower(trim($filter));
        $isWild  = str_contains($needle, '*');
        $pattern = $isWild ? '/^' . str_replace('\\*', '.*', preg_quote($needle, '/')) . '$/iu' : null;

        return array_values(array_filter($rows, function ($row) use ($colIncomeCenter, $needle, $isWild, $pattern) {
            $value = strtolower(trim((string) $this->resolveMssqlValue(
                $row,
                [$colIncomeCenter, 'rvc', 'income_center', 'revenue_center', 'revenue_centre', 'PriceLevel'],
                ''
            )));
            if ($value === '') return false;
            return $isWild ? (bool) preg_match($pattern, $value) : ($value === $needle);
        }));
    }

    /**
     * Aynı external_id (ProductCode) için birden fazla satır geldiğinde,
     * en yüksek HierStrucID/PriceLevelID önceliklidir (RVC > Property > Enterprise).
     * Symphony fiyat sorgusu her seviye için ayrı satır döndürdüğü için gereklidir.
     */
    private function dedupeByExternalId(array $rows, string $colId): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $id = (string) $this->resolveMssqlValue(
                $row,
                [$colId, 'external_id', 'id', 'mssql_id', 'product_id', 'menu_item_id', 'ProductCode', 'product_code'],
                ''
            );
            if ($id === '') continue;

            $level = (int) $this->resolveMssqlValue(
                $row,
                ['PriceLevelID', 'price_level_id', 'HierStrucID', 'hier_struc_id'],
                0
            );

            if (!isset($byId[$id]) || $level > $byId[$id]['_level']) {
                $row['_level'] = $level;
                $byId[$id] = $row;
            }
        }

        return array_values(array_map(function ($r) {
            unset($r['_level']);
            return $r;
        }, $byId));
    }

    private function quoteSqlServerIdentifier(string $identifier): string
    {
        return '[' . str_replace(']', ']]', trim($identifier)) . ']';
    }

    private function quoteSqlServerTable(string $table): string
    {
        $parts = array_map('trim', explode('.', $table));

        return implode('.', array_map(fn (string $part) => $this->quoteSqlServerIdentifier($part), $parts));
    }

    private function resolveMssqlValue(array $row, array $candidates, mixed $default = null): mixed
    {
        foreach ($candidates as $candidate) {
            foreach ([$candidate, strtoupper($candidate), strtolower($candidate)] as $key) {
                if (array_key_exists($key, $row) && $row[$key] !== null) {
                    return $row[$key];
                }
            }
        }

        return $default;
    }
}
