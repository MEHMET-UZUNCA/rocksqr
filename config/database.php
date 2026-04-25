<?php

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'waiter_calls_connection' => env('WAITER_CALLS_DB_CONNECTION', 'mysql'),

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

        'waiter_calls' => [
            'driver' => 'mysql',
            'host' => env('WAITER_CALLS_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('WAITER_CALLS_DB_PORT', env('DB_PORT', 3306)),
            'database' => env('WAITER_CALLS_DB_DATABASE', env('DB_DATABASE', 'qr_menu')),
            'username' => env('WAITER_CALLS_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('WAITER_CALLS_DB_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('WAITER_CALLS_DB_SOCKET', env('DB_SOCKET', '')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ],

    ],

    'migrations' => 'migrations',
];