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
            'waiter_call_display' => (int) Setting::get('waiter_call_display', 10),
            'order_ready_display' => (int) Setting::get('order_ready_display', 10),
            'order_profit_display' => (int) Setting::get('order_profit_display', 20),

            // Subdomain aliases
            'subdomain_bar'     => Setting::get('subdomain_bar', ''),
            'subdomain_kitchen' => Setting::get('subdomain_kitchen', ''),
            'subdomain_ana'     => Setting::get('subdomain_ana', ''),
            // Sayaç renk eşikleri (dakika cinsinden)
            // Aktif sipariş sayacı (QR)
            'timer_qr_yellow'  => (int) Setting::get('timer_qr_yellow', 5),
            'timer_qr_orange'  => (int) Setting::get('timer_qr_orange', 10),
            'timer_qr_red'     => (int) Setting::get('timer_qr_red', 15),
            // Aktif sipariş sayacı (SYM)
            'timer_sym_yellow' => (int) Setting::get('timer_sym_yellow', 3),
            'timer_sym_orange' => (int) Setting::get('timer_sym_orange', 6),
            'timer_sym_red'    => (int) Setting::get('timer_sym_red', 10),
            // Hazır sipariş sayacı
            'timer_ready_yellow' => (int) Setting::get('timer_ready_yellow', 3),
            'timer_ready_orange' => (int) Setting::get('timer_ready_orange', 7),
            'timer_ready_red'    => (int) Setting::get('timer_ready_red', 12),
            // Garson çağrı sayacı
            'timer_waiter_yellow' => (int) Setting::get('timer_waiter_yellow', 2),
            'timer_waiter_orange' => (int) Setting::get('timer_waiter_orange', 5),
            'timer_waiter_red'    => (int) Setting::get('timer_waiter_red', 10),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {

        // Hangi formdan geldiğini ayırt et
        if ($request->has('_timer_only')) {
            $request->validate([
                'timer_qr_yellow'     => 'required|integer|min:1|max:120',
                'timer_qr_orange'     => 'required|integer|min:1|max:120',
                'timer_qr_red'        => 'required|integer|min:1|max:120',
                'timer_sym_yellow'    => 'required|integer|min:1|max:120',
                'timer_sym_orange'    => 'required|integer|min:1|max:120',
                'timer_sym_red'       => 'required|integer|min:1|max:120',
                'timer_ready_yellow'  => 'required|integer|min:1|max:120',
                'timer_ready_orange'  => 'required|integer|min:1|max:120',
                'timer_ready_red'     => 'required|integer|min:1|max:120',
                'timer_waiter_yellow' => 'required|integer|min:1|max:120',
                'timer_waiter_orange' => 'required|integer|min:1|max:120',
                'timer_waiter_red'    => 'required|integer|min:1|max:120',
            ]);
            foreach ([
                'timer_qr_yellow','timer_qr_orange','timer_qr_red',
                'timer_sym_yellow','timer_sym_orange','timer_sym_red',
                'timer_ready_yellow','timer_ready_orange','timer_ready_red',
                'timer_waiter_yellow','timer_waiter_orange','timer_waiter_red',
            ] as $key) {
                Setting::set($key, (int) $request->input($key));
            }
            return back()->with('success', 'Sayaç renk eşikleri güncellendi.');
        } elseif ($request->has('_subdomain_only')) {
            $request->validate([
                'subdomain_bar'     => ['nullable', 'string', 'max:63', 'regex:/^[a-z0-9-]*$/i'],
                'subdomain_kitchen' => ['nullable', 'string', 'max:63', 'regex:/^[a-z0-9-]*$/i'],
                'subdomain_ana'     => ['nullable', 'string', 'max:63', 'regex:/^[a-z0-9-]*$/i'],
            ]);
            Setting::set('subdomain_bar',     strtolower(trim($request->subdomain_bar ?? '')));
            Setting::set('subdomain_kitchen', strtolower(trim($request->subdomain_kitchen ?? '')));
            Setting::set('subdomain_ana',     strtolower(trim($request->subdomain_ana ?? '')));
            return back()->with('success', 'Subdomain ayarları güncellendi.');
        } elseif ($request->has('_clear_time_only')) {            $request->validate([
                'screen_clear_time' => ['required', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
            ]);
            Setting::set('screen_clear_time', $request->screen_clear_time);
            return back()->with('success', 'Ekran temizleme saati güncellendi.');
        } elseif ($request->has('_display_only')) {
            $section = $request->input('_display_only');

            if ($section === 'bar') {
                $request->validate([
                    'bar_screen_title'       => 'nullable|string|max:255',
                    'bar_completed_display'  => 'required|integer|min:1|max:100',
                    'order_ready_display'    => 'required|integer|min:1|max:200',
                    'order_profit_display'   => 'required|integer|min:1|max:200',
                ]);
                Setting::set('bar_screen_title', $request->bar_screen_title ?: 'KDS - Bar Ekrani');
                Setting::set('bar_completed_display', $request->bar_completed_display);
                Setting::set('order_ready_display', $request->order_ready_display);
                Setting::set('order_profit_display', $request->order_profit_display);
                return back()->with('success', 'Bar ekran ayarları güncellendi.');
            }

            // kitchen
            $request->validate([
                'kitchen_screen_title'       => 'nullable|string|max:255',
                'kitchen_completed_display'  => 'required|integer|min:1|max:100',
                'waiter_call_display'        => 'required|integer|min:1|max:200',
                'ready_undo_seconds'         => 'required|integer|min:5|max:600',
            ]);
            Setting::set('kitchen_screen_title', $request->kitchen_screen_title ?: 'POOL Mutfak Ekrani');
            Setting::set('kitchen_completed_display', $request->kitchen_completed_display);
            Setting::set('waiter_call_display', $request->waiter_call_display);
            Setting::set('ready_undo_seconds', $request->ready_undo_seconds);
            return back()->with('success', 'Kitchen ekran ayarları güncellendi.');
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
            // Bar/kitchen titles are managed in the screen settings form — don't overwrite here
        }

        if ($request->hasFile('logo_svg')) {
            $file       = $request->file('logo_svg');
            $svgContent = file_get_contents($file->getRealPath());

            if (stripos($svgContent, '<svg') === false) {
                return back()->withErrors(['logo_svg' => 'Geçersiz SVG dosyası.']);
            }

            $sanitized = $this->sanitizeSvg($svgContent);
            if ($sanitized === null) {
                return back()->withErrors(['logo_svg' => 'SVG dosyası işlenemedi. Farklı bir dosya deneyin.']);
            }

            Setting::set('logo_svg', $sanitized);
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

            // KDS (Mutfak ekranı — Symphony bağlantısı)
            'mssql_kds_host' => Setting::get('mssql_kds_host', ''),
            'mssql_kds_port' => Setting::get('mssql_kds_port', '1433'),
            'mssql_kds_database' => Setting::get('mssql_kds_database', ''),
            'mssql_kds_username' => Setting::get('mssql_kds_username', ''),
            'mssql_kds_password' => Setting::get('mssql_kds_password', '') ? '********' : '',
            'mssql_kds_query' => Setting::get('mssql_kds_query', ''),
            'mssql_kds_rvc_filter' => Setting::get('mssql_kds_rvc_filter', ''),

            // BDS (Bar ekranı — Symphony bağlantısı)
            'mssql_bds_host' => Setting::get('mssql_bds_host', ''),
            'mssql_bds_port' => Setting::get('mssql_bds_port', '1433'),
            'mssql_bds_database' => Setting::get('mssql_bds_database', ''),
            'mssql_bds_username' => Setting::get('mssql_bds_username', ''),
            'mssql_bds_password' => Setting::get('mssql_bds_password', '') ? '********' : '',
            'mssql_bds_query' => Setting::get('mssql_bds_query', ''),
            'mssql_bds_rvc_filter' => Setting::get('mssql_bds_rvc_filter', ''),

            // AKDS (Ana Mutfak — sadece görüntüleme ekranı, ayrı veritabanı)
            'mssql_akds_host' => Setting::get('mssql_akds_host', ''),
            'mssql_akds_port' => Setting::get('mssql_akds_port', '1433'),
            'mssql_akds_database' => Setting::get('mssql_akds_database', ''),
            'mssql_akds_username' => Setting::get('mssql_akds_username', ''),
            'mssql_akds_password' => Setting::get('mssql_akds_password', '') ? '********' : '',
            'mssql_akds_query' => Setting::get('mssql_akds_query', ''),
            'mssql_akds_rvc_filter' => Setting::get('mssql_akds_rvc_filter', ''),
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
                'mssql_kds_rvc_filter' => 'nullable|string|max:255',
            ]);

            Setting::set('mssql_kds_host', trim((string) ($request->mssql_kds_host ?? '')));
            Setting::set('mssql_kds_port', trim((string) ($request->mssql_kds_port ?: '1433')));
            Setting::set('mssql_kds_database', trim((string) ($request->mssql_kds_database ?? '')));
            Setting::set('mssql_kds_username', trim((string) ($request->mssql_kds_username ?? '')));
            Setting::set('mssql_kds_query', (string) ($request->mssql_kds_query ?? ''));
            Setting::set('mssql_kds_rvc_filter', trim((string) ($request->mssql_kds_rvc_filter ?? '')));
            if ($request->mssql_kds_password && $request->mssql_kds_password !== '********') {
                Setting::set('mssql_kds_password', encrypt($request->mssql_kds_password));
            }

            return redirect()->route('admin.mssql-settings')
                ->with('success', 'Symphony Mutfak (KDS) ayarları güncellendi.')
                ->with('active_tab', 'kds');
        }

        if ($section === 'bds') {
            $request->validate([
                'mssql_bds_host' => 'nullable|string|max:255',
                'mssql_bds_port' => 'nullable|string|max:10',
                'mssql_bds_database' => 'nullable|string|max:255',
                'mssql_bds_username' => 'nullable|string|max:255',
                'mssql_bds_password' => 'nullable|string|max:255',
                'mssql_bds_query' => 'nullable|string',
                'mssql_bds_rvc_filter' => 'nullable|string|max:255',
            ]);

            Setting::set('mssql_bds_host', trim((string) ($request->mssql_bds_host ?? '')));
            Setting::set('mssql_bds_port', trim((string) ($request->mssql_bds_port ?: '1433')));
            Setting::set('mssql_bds_database', trim((string) ($request->mssql_bds_database ?? '')));
            Setting::set('mssql_bds_username', trim((string) ($request->mssql_bds_username ?? '')));
            Setting::set('mssql_bds_query', (string) ($request->mssql_bds_query ?? ''));
            Setting::set('mssql_bds_rvc_filter', trim((string) ($request->mssql_bds_rvc_filter ?? '')));
            if ($request->mssql_bds_password && $request->mssql_bds_password !== '********') {
                Setting::set('mssql_bds_password', encrypt($request->mssql_bds_password));
            }

            return redirect()->route('admin.mssql-settings')
                ->with('success', 'Symphony Bar (BDS) ayarları güncellendi.')
                ->with('active_tab', 'bds');
        }

        if ($section === 'akds') {
            $request->validate([
                'mssql_akds_host'       => 'nullable|string|max:255',
                'mssql_akds_port'       => 'nullable|string|max:10',
                'mssql_akds_database'   => 'nullable|string|max:255',
                'mssql_akds_username'   => 'nullable|string|max:255',
                'mssql_akds_password'   => 'nullable|string|max:255',
                'mssql_akds_query'      => 'nullable|string',
                'mssql_akds_rvc_filter' => 'nullable|string|max:255',
            ]);

            Setting::set('mssql_akds_host',       trim((string) ($request->mssql_akds_host ?? '')));
            Setting::set('mssql_akds_port',       trim((string) ($request->mssql_akds_port ?: '1433')));
            Setting::set('mssql_akds_database',   trim((string) ($request->mssql_akds_database ?? '')));
            Setting::set('mssql_akds_username',   trim((string) ($request->mssql_akds_username ?? '')));
            Setting::set('mssql_akds_query',      (string) ($request->mssql_akds_query ?? ''));
            Setting::set('mssql_akds_rvc_filter', trim((string) ($request->mssql_akds_rvc_filter ?? '')));
            if ($request->mssql_akds_password && $request->mssql_akds_password !== '********') {
                Setting::set('mssql_akds_password', encrypt($request->mssql_akds_password));
            }

            return redirect()->route('admin.mssql-settings')
                ->with('success', 'Ana Mutfak (AKDS) ayarları güncellendi.')
                ->with('active_tab', 'akds');
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
            'kds'  => 'mssql_kds_password',
            'bds'  => 'mssql_bds_password',
            'akds' => 'mssql_akds_password',
            default => 'mssql_password',
        };
    }

    // SVG dosyasını DOMDocument ile sanitize eder.
    // Tehlikeli taglar (script, foreignObject vb.) ve event/javascript attribute'ları kaldırır.
    // Auto-size için width/height attribute'ları çıkarılır.
    private function sanitizeSvg(string $svg): ?string
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadXML($svg, LIBXML_NONET | LIBXML_NOERROR)) {
            libxml_clear_errors();
            return null;
        }
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Tehlikeli tagları kaldır
        $dangerous = ['script', 'foreignObject', 'iframe', 'object', 'embed', 'use', 'animate', 'set'];
        foreach ($dangerous as $tag) {
            foreach (iterator_to_array($xpath->query("//{$tag}") ?: []) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        // Tüm elementlerde tehlikeli attribute'ları kaldır
        $eventAttrs = [
            'onload','onclick','onerror','onmouseover','onmouseout','onfocus','onblur',
            'onchange','onkeypress','onkeydown','onkeyup','onsubmit','onreset',
            'onabort','onscroll','onresize','ondblclick','oncontextmenu',
        ];
        foreach (iterator_to_array($xpath->query('//*') ?: []) as $el) {
            /** @var \DOMElement $el */
            foreach ($eventAttrs as $attr) {
                $el->removeAttribute($attr);
            }
            // href / xlink:href / src ile javascript: veya data: URI engellensin
            foreach (['href', 'xlink:href', 'src', 'action'] as $attr) {
                $val = $el->getAttribute($attr);
                if ($val !== '' && preg_match('/^\s*(javascript|data):/i', $val)) {
                    $el->removeAttribute($attr);
                }
            }
        }

        // SVG root element: width/height kaldır (auto-size için)
        $svgEls = $dom->getElementsByTagName('svg');
        if ($svgEls->length > 0) {
            /** @var \DOMElement $svgEl */
            $svgEl = $svgEls->item(0);
            $svgEl->removeAttribute('width');
            $svgEl->removeAttribute('height');
        }

        return $dom->saveXML($dom->documentElement) ?: null;
    }
}
