<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'XLX Server',
        'env' => 'production',
        'debug' => false,
        'base_url' => 'https://xlx.example.com',
        'timezone' => 'Europe/Amsterdam',
    ],

    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'xlx_server',
        'user' => 'xlx_user',
        'password' => 'change_me',
        'charset' => 'utf8mb4',
    ],

    'xlx' => [
        'reflector_name' => 'XLX000',
        'server_host' => 'xlx.example.com',
        'dashboard_port' => 8080,
        'dmr_port' => 62030,
        'default_module' => 'A',
        'config_path' => '/etc/xlx',
        'service_name' => 'xlxd',
    ],

    'id_allocator' => [
        'min_id' => 9000001,
        'max_id' => 9099999,
    ],

    'billing' => [
        'mode' => 'manual',
        'currency' => 'RUB',
    ],

    'security' => [
        'session_name' => 'xlx_session',
        'password_min_length' => 10,
        'dmr_password_length' => 16,
    ],
];
