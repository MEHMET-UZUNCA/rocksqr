<?php

return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'qr_menu'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],

        'oracle' => [
            'driver' => 'oracle',
            'tns' => 'TNS_NAME',
            'host' => env('ORACLE_DB_HOST', '192.168.0.10'),
            'port' => env('ORACLE_DB_PORT', 1521),
            'database' => env('ORACLE_DB_DATABASE', 'ORCL'),
            'username' => env('ORACLE_DB_USERNAME'),
            'password' => env('ORACLE_DB_PASSWORD'),
            'charset' => 'UTF8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],
    ],

    'migrations' => 'migrations',
];