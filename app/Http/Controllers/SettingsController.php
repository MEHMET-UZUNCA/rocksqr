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
        } else {
            $request->validate([
                'site_title' => 'required|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'meta_keywords' => 'nullable|string|max:500',
                'logo_svg' => 'nullable|file|mimes:svg|max:512',
            ]);
            Setting::set('site_title', $request->site_title);
            Setting::set('meta_description', $request->meta_description);
            Setting::set('meta_keywords', $request->meta_keywords);
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

    public function oracleIndex()
    {
        $settings = [
            'oracle_host' => Setting::get('oracle_host', ''),
            'oracle_port' => Setting::get('oracle_port', '1521'),
            'oracle_service' => Setting::get('oracle_service', ''),
            'oracle_username' => Setting::get('oracle_username', ''),
            'oracle_password' => Setting::get('oracle_password', '') ? '********' : '',
            'oracle_table' => Setting::get('oracle_table', ''),
            'oracle_column_id' => Setting::get('oracle_column_id', 'ID'),
            'oracle_column_name' => Setting::get('oracle_column_name', 'NAME'),
            'oracle_column_price' => Setting::get('oracle_column_price', 'PRICE'),
            'oracle_column_category' => Setting::get('oracle_column_category', 'CATEGORY'),
            'oracle_column_subcategory' => Setting::get('oracle_column_subcategory', 'SUBCATEGORY'),
        ];

        return view('admin.oracle-settings', compact('settings'));
    }

    public function oracleUpdate(Request $request)
    {
        $request->validate([
            'oracle_host' => 'nullable|string|max:255',
            'oracle_port' => 'nullable|string|max:10',
            'oracle_service' => 'nullable|string|max:255',
            'oracle_username' => 'nullable|string|max:255',
            'oracle_password' => 'nullable|string|max:255',
            'oracle_table' => 'nullable|string|max:255',
            'oracle_column_id' => 'nullable|string|max:255',
            'oracle_column_name' => 'nullable|string|max:255',
            'oracle_column_price' => 'nullable|string|max:255',
            'oracle_column_category' => 'nullable|string|max:255',
            'oracle_column_subcategory' => 'nullable|string|max:255',
        ]);

        Setting::set('oracle_host', $request->oracle_host ?? '');
        Setting::set('oracle_port', $request->oracle_port ?: '1521');
        Setting::set('oracle_service', $request->oracle_service ?? '');
        Setting::set('oracle_username', $request->oracle_username ?? '');
        Setting::set('oracle_table', $request->oracle_table ?? '');
        Setting::set('oracle_column_id', $request->oracle_column_id ?: 'ID');
        Setting::set('oracle_column_name', $request->oracle_column_name ?: 'NAME');
        Setting::set('oracle_column_price', $request->oracle_column_price ?: 'PRICE');
        Setting::set('oracle_column_category', $request->oracle_column_category ?: 'CATEGORY');
        Setting::set('oracle_column_subcategory', $request->oracle_column_subcategory ?: 'SUBCATEGORY');

        if ($request->oracle_password && $request->oracle_password !== '********') {
            Setting::set('oracle_password', encrypt($request->oracle_password));
        }

        return redirect()->route('admin.oracle-settings')->with('success', 'Oracle ayarları güncellendi.');
    }

    public function oracleTest(Request $request)
    {
        $host = $request->input('oracle_host', '');
        $port = $request->input('oracle_port', '1521');
        $service = $request->input('oracle_service', '');
        $username = $request->input('oracle_username', '');
        $password = $request->input('oracle_password', '');

        if (!$host || !$service || !$username) {
            return response()->json([
                'success' => false,
                'message' => 'Oracle bağlantı bilgileri eksik. Lütfen host, service ve kullanıcı adı alanlarını doldurun.'
            ]);
        }

        try {
            // If password is masked, use saved password from DB
            $actualPassword = '';
            if ($password && $password !== '********') {
                $actualPassword = $password;
            } else {
                $savedPassword = Setting::get('oracle_password', '');
                if ($savedPassword) {
                    try {
                        $actualPassword = decrypt($savedPassword);
                    } catch (\Exception $e) {
                        $actualPassword = $savedPassword;
                    }
                }
            }

            $tns = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$host})(PORT={$port}))(CONNECT_DATA=(SERVICE_NAME={$service})))";
            $pdo = new \PDO("oci:dbname={$tns};charset=AL32UTF8", $username, $actualPassword);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Quick query to verify connection
            $stmt = $pdo->query('SELECT 1 FROM DUAL');
            $stmt->fetch();

            $pdo = null;

            return response()->json([
                'success' => true,
                'message' => 'Oracle bağlantısı başarılı! Host: ' . $host . ', Service: ' . $service
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bağlantı başarısız: ' . $e->getMessage()
            ]);
        }
    }

    public function mssqlIndex()
    {
        $settings = [
            'mssql_host' => Setting::get('mssql_host', '192.168.0.9'),
            'mssql_port' => Setting::get('mssql_port', '1433'),
            'mssql_database' => Setting::get('mssql_database', 'Datastore'),
            'mssql_username' => Setting::get('mssql_username', ''),
            'mssql_password' => Setting::get('mssql_password', '') ? '********' : '',
            'mssql_table' => Setting::get('mssql_table', ''),
            'mssql_column_id' => Setting::get('mssql_column_id', 'ID'),
            'mssql_column_name' => Setting::get('mssql_column_name', 'NAME'),
            'mssql_column_price' => Setting::get('mssql_column_price', 'PRICE'),
            'mssql_column_group' => Setting::get('mssql_column_group', 'PRODUCT_GROUP'),
            'mssql_column_subgroup' => Setting::get('mssql_column_subgroup', 'SUBGROUP'),
            'mssql_column_income_center' => Setting::get('mssql_column_income_center', 'RVC'),
            'mssql_income_center_filter' => Setting::get('mssql_income_center_filter', ''),
            'mssql_custom_query' => Setting::get('mssql_custom_query', ''),
        ];

        return view('admin.mssql-settings', compact('settings'));
    }

    public function mssqlUpdate(Request $request)
    {
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

        Setting::set('mssql_host', $request->mssql_host ?? '');
        Setting::set('mssql_port', $request->mssql_port ?: '1433');
        Setting::set('mssql_database', $request->mssql_database ?? '');
        Setting::set('mssql_username', $request->mssql_username ?? '');
        Setting::set('mssql_table', $request->mssql_table ?? '');
        Setting::set('mssql_column_id', $request->mssql_column_id ?: 'ID');
        Setting::set('mssql_column_name', $request->mssql_column_name ?: 'NAME');
        Setting::set('mssql_column_price', $request->mssql_column_price ?: 'PRICE');
        Setting::set('mssql_column_group', $request->mssql_column_group ?: 'PRODUCT_GROUP');
        Setting::set('mssql_column_subgroup', $request->mssql_column_subgroup ?: 'SUBGROUP');
        Setting::set('mssql_column_income_center', $request->mssql_column_income_center ?: 'RVC');
        Setting::set('mssql_income_center_filter', $request->mssql_income_center_filter ?? '');
        Setting::set('mssql_custom_query', $request->mssql_custom_query ?? '');

        if ($request->mssql_password && $request->mssql_password !== '********') {
            Setting::set('mssql_password', encrypt($request->mssql_password));
        }

        return redirect()->route('admin.mssql-settings')->with('success', 'MSSQL ayarları güncellendi.');
    }

    public function mssqlTest(Request $request)
    {
        $host = $request->input('mssql_host', '');
        $port = $request->input('mssql_port', '1433');
        $database = $request->input('mssql_database', '');
        $username = $request->input('mssql_username', '');
        $password = $request->input('mssql_password', '');

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
                $savedPassword = Setting::get('mssql_password', '');
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
}
