<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SyncMssqlPrices extends Command
{
    protected $signature = 'sync:mssql-prices {--force : Zaman kontrolunu atla}';
    protected $description = 'Ayar sikligina gore MSSQL fiyatlarini otomatik gunceller (yalniz eslesen urunler, yalniz fiyat)';

    public function handle()
    {
        $mode = (string) Setting::get('auto_price_sync_mode', 'off');

        if (!$this->option('force')) {
            if ($mode === 'off') {
                $this->info('Otomatik fiyat senkronu kapali.');
                return 0;
            }
            if (!$this->isDue($mode)) {
                $this->info('Senkron zamani henuz gelmedi.');
                return 0;
            }
        }

        $result = $this->runSync();

        if ($result['success']) {
            $this->info($result['message']);
            return 0;
        }

        $this->error($result['message']);
        return 1;
    }

    // ScreenCleaner ile ayni kural: kayitlar UTC, admin girdisi TR duvar saatiyle karsilastirilir
    private function isDue(string $mode): bool
    {
        $lastRun = (string) Setting::get('auto_price_sync_last_run', '');

        if ($mode === 'daily') {
            $time = trim((string) Setting::get('auto_price_sync_time', '03:00'));
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
                return false;
            }
            $now         = Carbon::now();
            $localNow    = Carbon::now('Europe/Istanbul');
            $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $localNow->format('Y-m-d') . ' ' . $time, 'Europe/Istanbul');
            if ($now->lt($scheduledAt)) {
                return false;
            }
            if ($lastRun !== '') {
                try {
                    if (Carbon::parse($lastRun)->setTimezone('Europe/Istanbul')->toDateString() === $localNow->toDateString()) {
                        return false;
                    }
                } catch (\Throwable) {
                }
            }
            return true;
        }

        // interval
        $minutes = max(5, (int) Setting::get('auto_price_sync_interval', 60));
        if ($lastRun === '') {
            return true;
        }
        try {
            return Carbon::parse($lastRun)->diffInSeconds(Carbon::now()) >= $minutes * 60;
        } catch (\Throwable) {
            return true;
        }
    }

    private function runSync(): array
    {
        $host               = Setting::get('mssql_host', '');
        $port               = Setting::get('mssql_port', '1433');
        $database           = Setting::get('mssql_database', '');
        $username           = Setting::get('mssql_username', '');
        $password           = Setting::get('mssql_password', '');
        $colId              = Setting::get('mssql_column_id', 'ID');
        $colPrice           = Setting::get('mssql_column_price', 'PRICE');
        $colIncomeCenter    = Setting::get('mssql_column_income_center', 'RVC');
        $incomeCenterFilter = trim((string) Setting::get('mssql_income_center_filter', ''));
        $customQuery        = trim((string) Setting::get('mssql_custom_query', ''));

        if (!$host || !$database || $customQuery === '') {
            $message = 'MSSQL baglanti ayarlari eksik veya ozel SQL sorgusu girilmemis.';
            Log::warning('sync:mssql-prices atlandi', ['reason' => $message]);
            Setting::set('auto_price_sync_last_stats', 'Hata: ' . $message);
            return ['success' => false, 'message' => $message];
        }

        // Yalniz mssql_id eslesen yerel urunler guncellenir; yeni urun olusturulmaz
        $localProducts = Product::whereNotNull('mssql_id')->where('mssql_id', '!=', '')->get();

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

            $mssqlMap = [];
            foreach ($mssqlRows as $row) {
                $exId = (string) $this->resolveMssqlValue($row, [$colId, 'external_id', 'id', 'mssql_id', 'product_id', 'menu_item_id', 'ProductCode', 'product_code'], '');
                if ($exId !== '') {
                    $mssqlMap[$exId] = $row;
                }
            }

            $total   = 0;
            $updated = 0;
            $same    = 0;
            $missing = 0;

            foreach ($localProducts as $local) {
                $total++;
                $productCode = (string) $local->mssql_id;

                if (!isset($mssqlMap[$productCode])) {
                    $missing++;
                    continue;
                }

                $externalPrice = (float) $this->resolveMssqlValue(
                    $mssqlMap[$productCode],
                    [$colPrice, 'price', 'mssql_price', 'sales_price', 'product_price', 'Price'],
                    0
                );

                if ($externalPrice > 0 && $externalPrice !== (float) $local->price) {
                    $local->update(['price' => $externalPrice]);
                    $updated++;
                } else {
                    $same++;
                }
            }

            $stats = "toplam {$total} · güncellenen {$updated} · aynı {$same} · eşleşmeyen {$missing}";
            Setting::set('auto_price_sync_last_run', Carbon::now()->format('Y-m-d H:i:s'));
            Setting::set('auto_price_sync_last_stats', $stats);
            Log::info('sync:mssql-prices tamamlandi', ['total' => $total, 'updated' => $updated, 'same' => $same, 'missing' => $missing]);

            return ['success' => true, 'message' => 'Fiyat senkronu tamamlandi: ' . $stats];
        } catch (\PDOException $e) {
            $message = 'MSSQL baglanti hatasi: ' . $e->getMessage();
            Log::error('sync:mssql-prices PDO hatasi', ['error' => $e->getMessage()]);
            Setting::set('auto_price_sync_last_stats', 'Hata: ' . $e->getMessage());
            return ['success' => false, 'message' => $message];
        } catch (\Exception $e) {
            $message = 'Hata: ' . $e->getMessage();
            Log::error('sync:mssql-prices hatasi', ['error' => $e->getMessage()]);
            Setting::set('auto_price_sync_last_stats', 'Hata: ' . $e->getMessage());
            return ['success' => false, 'message' => $message];
        }
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

    // Ayni external_id icin en yuksek HierStrucID/PriceLevelID onceliklidir (RVC > Property > Enterprise)
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
