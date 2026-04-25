<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'site_title' => Setting::get('site_title', 'Rocks Hotel QR Menü'),
            'meta_description' => Setting::get('meta_description', ''),
            'meta_keywords' => Setting::get('meta_keywords', ''),
            'logo_svg' => Setting::get('logo_svg', ''),
            'screen_clear_time' => Setting::get('screen_clear_time', '14:00'),
            'kitchen_completed_display' => (int) Setting::get('kitchen_completed_display', 6),
            'bar_completed_display' => (int) Setting::get('bar_completed_display', 6),
            'ready_undo_seconds' => (int) Setting::get('ready_undo_seconds', 30),
            'bar_screen_title' => Setting::get('bar_screen_title', 'KDS - Bar Ekrani'),
            'kitchen_screen_title' => Setting::get('kitchen_screen_title', 'POOL Mutfak Ekrani'),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {

        // Hangi formdan geldiğini ayırt et
        if ($request->has('_clear_time_only')) {
            $request->validate([
                'screen_clear_time' => ['required', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            ]);
            Setting::set('screen_clear_time', $request->screen_clear_time);
            return back()->with('success', 'Ekran temizleme saati güncellendi.');
        } elseif ($request->has('_display_only')) {
            $request->validate([
                'kitchen_completed_display' => 'required|integer|in:3,6,12,24',
                'bar_completed_display' => 'required|integer|in:3,6,12,24',
                'ready_undo_seconds' => 'required|integer|min:5|max:600',
            ]);
            Setting::set('kitchen_completed_display', $request->kitchen_completed_display);
            Setting::set('bar_completed_display', $request->bar_completed_display);
            Setting::set('ready_undo_seconds', $request->ready_undo_seconds);
            return back()->with('success', 'Ekran görünüm ayarları güncellendi.');
        } else {
            $request->validate([
                'site_title' => 'required|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'meta_keywords' => 'nullable|string|max:500',
                'bar_screen_title' => 'nullable|string|max:255',
                'kitchen_screen_title' => 'nullable|string|max:255',
                'logo_svg' => 'nullable|file|mimes:svg|max:512',
            ]);
            Setting::set('site_title', $request->site_title);
            Setting::set('meta_description', $request->meta_description);
            Setting::set('meta_keywords', $request->meta_keywords);
            Setting::set('bar_screen_title', $request->bar_screen_title ?: 'KDS - Bar Ekrani');
            Setting::set('kitchen_screen_title', $request->kitchen_screen_title ?: 'POOL Mutfak Ekrani');
        }

        if ($request->hasFile('logo_svg')) {
            $file = $request->file('logo_svg');
            $svgContent = file_get_contents($file->getRealPath());

            // Basic SVG validation - check it starts with valid SVG markup
            if (stripos($svgContent, '<svg') === false) {
                return back()->withErrors(['logo_svg' => 'Geçersiz SVG dosyası.']);
            }

            // Remove any script tags for security
            $svgContent = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svgContent);

            // Auto-size SVG: remove fixed width/height, ensure viewBox exists
            $svgContent = preg_replace('/(<svg[^>]*)\s+width\s*=\s*"[^"]*"/i', '$1', $svgContent);
            $svgContent = preg_replace('/(<svg[^>]*)\s+height\s*=\s*"[^"]*"/i', '$1', $svgContent);

            Setting::set('logo_svg', $svgContent);
        }

        if ($request->has('remove_logo') && $request->remove_logo) {
            Setting::set('logo_svg', '');
        }

        return redirect()->route('admin.settings')->with('success', 'Ayarlar güncellendi.');
    }

    public function mssqlIndex()
    {
        $settings = [
            // Ürün (Symphony) bağlantısı
            'mssql_host' => Setting::get('mssql_host', '192.168.0.9'),
            'mssql_port' => Setting::get('mssql_port', '1433'),
            'mssql_database' => Setting::get('mssql_database', 'Datastore'),
            'mssql_username' => Setting::get('mssql_username', 'rocks'),
            'mssql_password' => Setting::get('mssql_password', '') ? '********' : 'Protel2026++',
            'mssql_table' => Setting::get('mssql_table', ''),
            'mssql_column_id' => Setting::get('mssql_column_id', 'ID'),
            'mssql_column_name' => Setting::get('mssql_column_name', 'NAME'),
            'mssql_column_price' => Setting::get('mssql_column_price', 'PRICE'),
            'mssql_column_group' => Setting::get('mssql_column_group', 'PRODUCT_GROUP'),
            'mssql_column_subgroup' => Setting::get('mssql_column_subgroup', 'SUBGROUP'),
            'mssql_column_income_center' => Setting::get('mssql_column_income_center', 'RVC'),
            'mssql_income_center_filter' => Setting::get('mssql_income_center_filter', ''),
            'mssql_custom_query' => Setting::get('mssql_custom_query', ''),

            // KDS (Mutfak ekranı) bağlantısı
            'mssql_kds_host' => Setting::get('mssql_kds_host', ''),
            'mssql_kds_port' => Setting::get('mssql_kds_port', '1433'),
            'mssql_kds_database' => Setting::get('mssql_kds_database', ''),
            'mssql_kds_username' => Setting::get('mssql_kds_username', ''),
            'mssql_kds_password' => Setting::get('mssql_kds_password', '') ? '********' : '',
            'mssql_kds_query' => Setting::get('mssql_kds_query', ''),
        ];

        return view('admin.mssql-settings', compact('settings'));
    }

    public function mssqlUpdate(Request $request)
    {
        $section = $request->input('section', 'product');

        if ($section === 'kds') {
            $request->validate([
                'mssql_kds_host' => 'nullable|string|max:255',
                'mssql_kds_port' => 'nullable|string|max:10',
                'mssql_kds_database' => 'nullable|string|max:255',
                'mssql_kds_username' => 'nullable|string|max:255',
                'mssql_kds_password' => 'nullable|string|max:255',
                'mssql_kds_query' => 'nullable|string',
            ]);

            Setting::set('mssql_kds_host', trim((string) ($request->mssql_kds_host ?? '')));
            Setting::set('mssql_kds_port', trim((string) ($request->mssql_kds_port ?: '1433')));
            Setting::set('mssql_kds_database', trim((string) ($request->mssql_kds_database ?? '')));
            Setting::set('mssql_kds_username', trim((string) ($request->mssql_kds_username ?? '')));
            Setting::set('mssql_kds_query', (string) ($request->mssql_kds_query ?? ''));
            if ($request->mssql_kds_password && $request->mssql_kds_password !== '********') {
                Setting::set('mssql_kds_password', encrypt($request->mssql_kds_password));
            }

            return redirect()->route('admin.mssql-settings')
                ->with('success', 'KDS ayarları güncellendi.')
                ->with('active_tab', 'kds');
        }

        // Default: product (Symphony)
        $request->validate([
            'mssql_host' => 'nullable|string|max:255',
            'mssql_port' => 'nullable|string|max:10',
            'mssql_database' => 'nullable|string|max:255',
            'mssql_username' => 'nullable|string|max:255',
            'mssql_password' => 'nullable|string|max:255',
            'mssql_table' => 'nullable|string|max:255',
            'mssql_column_id' => 'nullable|string|max:255',
            'mssql_column_name' => 'nullable|string|max:255',
            'mssql_column_price' => 'nullable|string|max:255',
            'mssql_column_group' => 'nullable|string|max:255',
            'mssql_column_subgroup' => 'nullable|string|max:255',
            'mssql_column_income_center' => 'nullable|string|max:255',
            'mssql_income_center_filter' => 'nullable|string|max:255',
            'mssql_custom_query' => 'nullable|string',
        ]);

        Setting::set('mssql_host', trim((string) ($request->mssql_host ?? '')));
        Setting::set('mssql_port', trim((string) ($request->mssql_port ?: '1433')));
        Setting::set('mssql_database', trim((string) ($request->mssql_database ?? '')));
        Setting::set('mssql_username', trim((string) ($request->mssql_username ?? '')));
        Setting::set('mssql_table', trim((string) ($request->mssql_table ?? '')));
        Setting::set('mssql_column_id', trim((string) ($request->mssql_column_id ?: 'ID')));
        Setting::set('mssql_column_name', trim((string) ($request->mssql_column_name ?: 'NAME')));
        Setting::set('mssql_column_price', trim((string) ($request->mssql_column_price ?: 'PRICE')));
        Setting::set('mssql_column_group', trim((string) ($request->mssql_column_group ?: 'PRODUCT_GROUP')));
        Setting::set('mssql_column_subgroup', trim((string) ($request->mssql_column_subgroup ?: 'SUBGROUP')));
        Setting::set('mssql_column_income_center', trim((string) ($request->mssql_column_income_center ?: 'RVC')));
        Setting::set('mssql_income_center_filter', trim((string) ($request->mssql_income_center_filter ?? '')));
        Setting::set('mssql_custom_query', (string) ($request->mssql_custom_query ?? ''));

        if ($request->mssql_password && $request->mssql_password !== '********') {
            Setting::set('mssql_password', encrypt($request->mssql_password));
        }

        return redirect()->route('admin.mssql-settings')
            ->with('success', 'Ürün (Symphony) ayarları güncellendi.')
            ->with('active_tab', 'product');
    }

    public function mssqlTest(Request $request)
    {
        $host = $request->input('mssql_host', '');
        $port = $request->input('mssql_port', '1433');
        $database = $request->input('mssql_database', '');
        $username = $request->input('mssql_username', '');
        $password = $request->input('mssql_password', '');
        $section  = $request->input('section', 'product'); // product|kds|bds

        if (!$host || !$database || !$username) {
            return response()->json([
                'success' => false,
                'message' => 'MSSQL bağlantı bilgileri eksik. Lütfen host, veritabanı ve kullanıcı adı alanlarını doldurun.'
            ]);
        }

        try {
            $actualPassword = '';
            if ($password && $password !== '********') {
                $actualPassword = $password;
            } else {
                $passwordKey = $this->resolvePasswordKey($section);
                $savedPassword = Setting::get($passwordKey, '');
                if ($savedPassword) {
                    try {
                        $actualPassword = decrypt($savedPassword);
                    } catch (\Exception $e) {
                        $actualPassword = $savedPassword;
                    }
                }
            }

            $pdo = new \PDO("sqlsrv:Server={$host},{$port};Database={$database};TrustServerCertificate=1", $username, $actualPassword);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->query('SELECT TOP 1 1 AS test_value');
            $stmt->fetch();
            $pdo = null;

            return response()->json([
                'success' => true,
                'message' => 'MSSQL bağlantısı başarılı! Host: ' . $host . ', DB: ' . $database
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bağlantı başarısız: ' . $e->getMessage()
            ]);
        }
    }

    public function mssqlPreview(Request $request)
    {
        $host     = $request->input('mssql_host', '');
        $port     = $request->input('mssql_port', '1433');
        $database = $request->input('mssql_database', '');
        $username = $request->input('mssql_username', '');
        $password = $request->input('mssql_password', '');
        $query    = trim((string) $request->input('mssql_custom_query', ''));
        $section  = $request->input('section', 'product');
        $limit    = (int) $request->input('limit', 50);
        if ($limit < 1)   $limit = 1;
        if ($limit > 500) $limit = 500;

        if ($query === '') {
            return response()->json(['success' => false, 'message' => 'Önizleme için Özel SQL Sorgusu boş olamaz.']);
        }
        if (!$host || !$database || !$username) {
            return response()->json(['success' => false, 'message' => 'MSSQL bağlantı bilgileri eksik (host/db/kullanıcı).']);
        }

        // Sadece SELECT sorgularına izin ver — yorum satırlarını ve baştaki boşlukları atla
        $stripped = $query;
        // Çok satırlı yorumları kaldır
        $stripped = preg_replace('/\/\*.*?\*\//s', '', $stripped);
        // Tek satırlı yorumları kaldır
        $stripped = preg_replace('/--[^\r\n]*/', '', $stripped);
        $stripped = ltrim((string) $stripped);
        $firstWord = strtoupper((string) preg_replace('/^([a-zA-Z]+).*$/s', '$1', $stripped));
        if (!in_array($firstWord, ['SELECT', 'WITH'], true)) {
            return response()->json(['success' => false, 'message' => 'Önizleme yalnızca SELECT/WITH sorgularını destekler. (Algılanan: "' . substr($firstWord, 0, 20) . '")']);
        }

        try {
            $actualPassword = '';
            if ($password && $password !== '********') {
                $actualPassword = $password;
            } else {
                $passwordKey = $this->resolvePasswordKey($section);
                $savedPassword = Setting::get($passwordKey, '');
                if ($savedPassword) {
                    try { $actualPassword = decrypt($savedPassword); } catch (\Exception $e) { $actualPassword = $savedPassword; }
                }
            }

            $pdo = new \PDO("sqlsrv:Server={$host},{$port};Database={$database};TrustServerCertificate=1", $username, $actualPassword);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare($query);
            $stmt->execute();

            $rows    = [];
            $count   = 0;
            $columns = [];
            while ($count < $limit && ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
                if (empty($columns)) $columns = array_keys($row);
                $rows[] = $row;
                $count++;
            }

            return response()->json([
                'success'   => true,
                'columns'   => $columns,
                'rows'      => $rows,
                'row_count' => count($rows),
                'limit'     => $limit,
                'message'   => 'Önizleme başarılı: ' . count($rows) . ' satır gösteriliyor (max ' . $limit . ').',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sorgu hatası: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Section -> şifre setting key eşleştirmesi.
     */
    private function resolvePasswordKey(string $section): string
    {
        return match ($section) {
            'kds' => 'mssql_kds_password',
            default => 'mssql_password',
        };
    }
}
