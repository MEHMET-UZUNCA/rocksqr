<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class SyncController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->orderBy('id')->get();

        $oracleConfigured = Setting::get('oracle_host', '') !== '' && Setting::get('oracle_table', '') !== '';
        $mssqlConfigured = Setting::get('mssql_host', '') !== '' && Setting::get('mssql_database', '') !== '' && Setting::get('mssql_table', '') !== '';

        return view('admin.sync', compact('products', 'oracleConfigured', 'mssqlConfigured'));
    }

    public function apiProducts()
    {
        $products = Product::orderBy('id')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'oracle_id' => $product->oracle_id,
                'mssql_id' => $product->mssql_id,
                'is_available' => $product->is_available,
            ];
        });

        return response()->json(['products' => $products]);
    }

    /**
     * Oracle'dan ürün verilerini çek ve mevcut ürünlerle eşleştir
     */
    public function fetchOracle()
    {
        $host = Setting::get('oracle_host', '');
        $port = Setting::get('oracle_port', '1521');
        $service = Setting::get('oracle_service', '');
        $username = Setting::get('oracle_username', '');
        $password = Setting::get('oracle_password', '');
        $table = Setting::get('oracle_table', '');
        $colId = Setting::get('oracle_column_id', 'ID');
        $colName = Setting::get('oracle_column_name', 'NAME');
        $colPrice = Setting::get('oracle_column_price', 'PRICE');
        $colCategory = Setting::get('oracle_column_category', 'CATEGORY');
        $colSubcategory = Setting::get('oracle_column_subcategory', 'SUBCATEGORY');

        if (!$host || !$service || !$table) {
            return response()->json(['success' => false, 'error' => 'Oracle bağlantı ayarları eksik. Ayarlar sayfasından yapılandırın.'], 422);
        }

        try {
            // Decrypt password
            $decryptedPassword = '';
            if ($password) {
                try {
                    $decryptedPassword = decrypt($password);
                } catch (\Exception $e) {
                    $decryptedPassword = $password;
                }
            }

            // Dynamic Oracle connection
            Config::set('database.connections.oracle_sync', [
                'driver' => 'oracle',
                'host' => $host,
                'port' => $port,
                'database' => '',
                'service_name' => $service,
                'username' => $username,
                'password' => $decryptedPassword,
                'charset' => 'AL32UTF8',
                'prefix' => '',
            ]);

            // Try OCI8 PDO connection directly
            $tns = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$host})(PORT={$port}))(CONNECT_DATA=(SERVICE_NAME={$service})))";
            $pdo = new \PDO("oci:dbname={$tns};charset=AL32UTF8", $username, $decryptedPassword);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT {$colId}, {$colName}, {$colPrice}, {$colCategory}, {$colSubcategory} FROM {$table}";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $oracleProducts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Match with local products by oracle_id
            $localProducts = Product::whereNotNull('oracle_id')->where('oracle_id', '!=', '')->get()->keyBy('oracle_id');

            $matched = [];
            $unmatched = [];

            foreach ($oracleProducts as $op) {
                $oracleId = (string)($op[$colId] ?? $op[strtoupper($colId)] ?? $op[strtolower($colId)] ?? '');
                $oracleName = $op[$colName] ?? $op[strtoupper($colName)] ?? $op[strtolower($colName)] ?? '';
                $oraclePrice = (float)($op[$colPrice] ?? $op[strtoupper($colPrice)] ?? $op[strtolower($colPrice)] ?? 0);
                $oracleCategory = $op[$colCategory] ?? $op[strtoupper($colCategory)] ?? $op[strtolower($colCategory)] ?? '';
                $oracleSubcategory = $op[$colSubcategory] ?? $op[strtoupper($colSubcategory)] ?? $op[strtolower($colSubcategory)] ?? '';

                if ($localProducts->has($oracleId)) {
                    $local = $localProducts->get($oracleId);
                    $changes = [];

                    if ($oracleName && $oracleName !== $local->name) {
                        $changes['name'] = ['old' => $local->name, 'new' => $oracleName];
                    }
                    if ($oraclePrice > 0 && (float)$oraclePrice !== (float)$local->price) {
                        $changes['price'] = ['old' => (float)$local->price, 'new' => $oraclePrice];
                    }

                    $matched[] = [
                        'local_id' => $local->id,
                        'oracle_id' => $oracleId,
                        'local_name' => $local->name,
                        'oracle_name' => $oracleName,
                        'local_price' => (float)$local->price,
                        'oracle_price' => $oraclePrice,
                        'oracle_category' => $oracleCategory,
                        'oracle_subcategory' => $oracleSubcategory,
                        'has_changes' => !empty($changes),
                        'changes' => $changes,
                    ];
                } else {
                    $unmatched[] = [
                        'oracle_id' => $oracleId,
                        'oracle_name' => $oracleName,
                        'oracle_price' => $oraclePrice,
                        'oracle_category' => $oracleCategory,
                        'oracle_subcategory' => $oracleSubcategory,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'matched' => $matched,
                'unmatched' => $unmatched,
                'total_oracle' => count($oracleProducts),
                'total_matched' => count($matched),
                'total_with_changes' => count(array_filter($matched, fn($m) => $m['has_changes'])),
            ]);

        } catch (\PDOException $e) {
            return response()->json(['success' => false, 'error' => 'Oracle bağlantı hatası: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Hata: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Oracle'dan gelen verileri uygula
     */
    public function applyOracle(Request $request)
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

    public function updateOracleId(Request $request, Product $product)
    {
        $request->validate([
            'oracle_id' => 'nullable|string|max:255',
        ]);

        $old = $product->oracle_id;
        $product->update(['oracle_id' => $request->oracle_id]);

        return response()->json([
            'success' => true,
            'old_oracle_id' => $old,
            'new_oracle_id' => $product->oracle_id,
        ]);
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

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*.id' => 'required|exists:products,id',
            'updates.*.name' => 'nullable|string|max:255',
            'updates.*.price' => 'nullable|numeric|min:0',
            'updates.*.oracle_id' => 'nullable|string|max:255',
            'updates.*.mssql_id' => 'nullable|string|max:255',
        ]);

        $results = [];
        foreach ($request->updates as $update) {
            $product = Product::find($update['id']);
            $old = [
                'name' => $product->name,
                'price' => $product->price,
                'oracle_id' => $product->oracle_id,
                'mssql_id' => $product->mssql_id,
            ];

            $data = [];
            if (isset($update['name']) && $update['name'] !== '') {
                $data['name'] = $update['name'];
            }
            if (isset($update['price']) && $update['price'] !== '') {
                $data['price'] = $update['price'];
            }
            if (array_key_exists('oracle_id', $update)) {
                $data['oracle_id'] = $update['oracle_id'];
            }
            if (array_key_exists('mssql_id', $update)) {
                $data['mssql_id'] = $update['mssql_id'];
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
                    'oracle_id' => $product->oracle_id,
                    'mssql_id' => $product->mssql_id,
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
            'updates.*.oracle_id' => 'nullable|string|max:255',
            'updates.*.mssql_id' => 'nullable|string|max:255',
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
            if (array_key_exists('oracle_id', $update) && $update['oracle_id'] !== $product->oracle_id) {
                $changes['oracle_id'] = ['old' => $product->oracle_id, 'new' => $update['oracle_id']];
            }
            if (array_key_exists('mssql_id', $update) && $update['mssql_id'] !== $product->mssql_id) {
                $changes['mssql_id'] = ['old' => $product->mssql_id, 'new' => $update['mssql_id']];
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
        $table = Setting::get('mssql_table', '');
        $colId = Setting::get('mssql_column_id', 'ID');
        $colName = Setting::get('mssql_column_name', 'NAME');
        $colPrice = Setting::get('mssql_column_price', 'PRICE');
        $colGroup = Setting::get('mssql_column_group', 'PRODUCT_GROUP');
        $colSubgroup = Setting::get('mssql_column_subgroup', 'SUBGROUP');
        $colIncomeCenter = Setting::get('mssql_column_income_center', 'RVC');
        $incomeCenterFilter = trim((string) Setting::get('mssql_income_center_filter', ''));
        $customQuery = trim((string) Setting::get('mssql_custom_query', ''));

        if (!$host || !$database || !$table) {
            return response()->json(['success' => false, 'error' => 'MSSQL bağlantı ayarları eksik. MSSQL ayarları sayfasından yapılandırın.'], 422);
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

            if ($customQuery !== '') {
                $stmt = $pdo->prepare($customQuery);
            } else {
                $quotedTable = $this->quoteSqlServerTable($table);
                $sql = sprintf(
                    'SELECT %s, %s, %s, %s, %s, %s FROM %s',
                    $this->quoteSqlServerIdentifier($colId),
                    $this->quoteSqlServerIdentifier($colName),
                    $this->quoteSqlServerIdentifier($colPrice),
                    $this->quoteSqlServerIdentifier($colGroup),
                    $this->quoteSqlServerIdentifier($colSubgroup),
                    $this->quoteSqlServerIdentifier($colIncomeCenter),
                    $quotedTable
                );

                if ($incomeCenterFilter !== '') {
                    $sql .= sprintf(
                        ' WHERE LTRIM(RTRIM(CONVERT(NVARCHAR(255), %s))) = :income_center_filter',
                        $this->quoteSqlServerIdentifier($colIncomeCenter)
                    );
                }

                $stmt = $pdo->prepare($sql);

                if ($incomeCenterFilter !== '') {
                    $stmt->bindValue(':income_center_filter', $incomeCenterFilter);
                }
            }

            $stmt->execute();
            $mssqlProducts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $localProducts = Product::whereNotNull('mssql_id')->where('mssql_id', '!=', '')->get()->keyBy('mssql_id');

            $matched = [];
            $unmatched = [];

            foreach ($mssqlProducts as $row) {
                $externalId = (string) $this->resolveMssqlValue($row, [$colId, 'external_id', 'id', 'mssql_id', 'product_id', 'menu_item_id'], '');
                $externalName = (string) $this->resolveMssqlValue($row, [$colName, 'product_name', 'name', 'mssql_name', 'item_name'], '');
                $externalPrice = (float) $this->resolveMssqlValue($row, [$colPrice, 'price', 'mssql_price', 'sales_price', 'product_price'], 0);
                $externalGroup = (string) $this->resolveMssqlValue($row, [$colGroup, 'product_group', 'group_name', 'major_group', 'main_group'], '');
                $externalSubgroup = (string) $this->resolveMssqlValue($row, [$colSubgroup, 'subgroup', 'sub_group', 'family_group'], '');
                $externalIncomeCenter = (string) $this->resolveMssqlValue($row, [$colIncomeCenter, 'rvc', 'income_center', 'revenue_center', 'revenue_centre'], '');

                if ($localProducts->has($externalId)) {
                    $local = $localProducts->get($externalId);
                    $changes = [];

                    if ($externalName && $externalName !== $local->name) {
                        $changes['name'] = ['old' => $local->name, 'new' => $externalName];
                    }
                    if ($externalPrice > 0 && $externalPrice !== (float) $local->price) {
                        $changes['price'] = ['old' => (float) $local->price, 'new' => $externalPrice];
                    }

                    $matched[] = [
                        'local_id' => $local->id,
                        'mssql_id' => $externalId,
                        'local_name' => $local->name,
                        'mssql_name' => $externalName,
                        'local_price' => (float) $local->price,
                        'mssql_price' => $externalPrice,
                        'mssql_group' => $externalGroup,
                        'mssql_subgroup' => $externalSubgroup,
                        'mssql_income_center' => $externalIncomeCenter,
                        'has_changes' => !empty($changes),
                        'changes' => $changes,
                    ];
                } else {
                    $unmatched[] = [
                        'mssql_id' => $externalId,
                        'mssql_name' => $externalName,
                        'mssql_price' => $externalPrice,
                        'mssql_group' => $externalGroup,
                        'mssql_subgroup' => $externalSubgroup,
                        'mssql_income_center' => $externalIncomeCenter,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'matched' => $matched,
                'unmatched' => $unmatched,
                'total_mssql' => count($mssqlProducts),
                'total_matched' => count($matched),
                'total_with_changes' => count(array_filter($matched, fn ($item) => $item['has_changes'])),
                'income_center_filter' => $incomeCenterFilter,
                'custom_query_used' => $customQuery !== '',
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
