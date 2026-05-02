<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MssqlService
{
    public function connect(string $host, string $port, string $database, string $username, string $encryptedPassword): \PDO
    {
        $password = '';
        if ($encryptedPassword) {
            try {
                $password = decrypt($encryptedPassword);
            } catch (\Exception) {
                $password = $encryptedPassword;
            }
        }

        $pdo = new \PDO(
            "sqlsrv:Server={$host},{$port};Database={$database};TrustServerCertificate=1",
            $username,
            $password
        );
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    // ODBC Driver 18: -- yorum satırları ve \r\n satır sonları SQLSTATE[42000] verebilir
    public function cleanSql(string $query): string
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $query));
        $lines = array_filter($lines, fn($l) => !preg_match('/^\s*--/', $l));
        return rtrim(trim(implode("\n", $lines)), ';');
    }

    public function runQuery(\PDO $pdo, string $sql): array
    {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Case-insensitive alan okuyucu — Symphony SQL sonuçları PascalCase veya büyük harf döndürebilir
    public function getField(array $row, array $candidates, mixed $default = null): mixed
    {
        foreach ($candidates as $c) {
            foreach ([$c, strtoupper($c), strtolower($c), ucfirst(strtolower($c))] as $k) {
                if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                    return $row[$k];
                }
            }
        }
        return $default;
    }
}
