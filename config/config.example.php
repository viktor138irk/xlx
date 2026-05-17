<?php

declare(strict_types=1);

$host = getenv('XLX_PUBLIC_HOST') ?: ($_SERVER['HTTP_HOST'] ?? '127.0.0.1');
$scheme = getenv('XLX_PUBLIC_SCHEME') ?: ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
$baseUrl = getenv('XLX_BASE_URL') ?: $scheme . '://' . $host;
$hostWithoutPort = preg_replace('/:\d+$/', '', $host);

return [
    'app' => [
        'name' => 'XLX Server',
        'env' => getenv('XLX_APP_ENV') ?: 'production',
        'debug' => (bool) (getenv('XLX_DEBUG') ?: false),
        'base_url' => $baseUrl,
        'timezone' => getenv('XLX_TIMEZONE') ?: 'Europe/Moscow',
        'panel_port' => (int) (getenv('XLX_PANEL_PORT') ?: 80),
    ],

    'database' => [
        'host' => getenv('XLX_DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('XLX_DB_PORT') ?: 3306),
        'name' => getenv('XLX_DB_NAME') ?: 'xlx_server',
        'user' => getenv('XLX_DB_USER') ?: 'xlx_user',
        'password' => getenv('XLX_DB_PASSWORD') ?: 'change_me',
        'charset' => 'utf8mb4',
    ],

    'xlx' => [
        'reflector_name' => getenv('XLX_REFLECTOR_NAME') ?: 'XLX000',
        'server_host' => getenv('XLX_PUBLIC_HOST') ?: $host,
        'sysop_callsign' => getenv('XLX_SYSOP_CALLSIGN') ?: 'N0CALL',
        'sysop_email' => getenv('XLX_SYSOP_EMAIL') ?: 'admin@' . $hostWithoutPort,
        'country' => getenv('XLX_COUNTRY') ?: 'RU',
        'dashboard_port' => (int) (getenv('XLX_DASHBOARD_PORT') ?: 8080),
        'dmr_port' => (int) (getenv('XLX_DMR_PORT') ?: 62030),
        'ysf_port' => (int) (getenv('XLX_YSF_PORT') ?: 42000),
        'default_module' => getenv('XLX_DEFAULT_MODULE') ?: 'A',
        'modules' => getenv('XLX_MODULES') ?: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'config_path' => getenv('XLX_CONFIG_PATH') ?: '/etc/xlx',
        'install_path' => getenv('XLX_INSTALL_PATH') ?: '/xlxd',
        'source_path' => getenv('XLX_SOURCE_PATH') ?: '/usr/src/xlxd',
        'log_path' => getenv('XLX_LOG_PATH') ?: '/var/log/xlxd.log',
        'service_name' => getenv('XLX_SERVICE_NAME') ?: 'xlxd',
    ],

    'id_allocator' => [
        'min_id' => (int) (getenv('XLX_MIN_ID') ?: 9000001),
        'max_id' => (int) (getenv('XLX_MAX_ID') ?: 9099999),
    ],

    'billing' => [
        'mode' => getenv('XLX_BILLING_MODE') ?: 'yookassa',
        'currency' => getenv('XLX_CURRENCY') ?: 'RUB',
        'default_duration_days' => (int) (getenv('XLX_DEFAULT_DURATION_DAYS') ?: 30),
        'manual_transfer_enabled' => (bool) (getenv('XLX_MANUAL_TRANSFER_ENABLED') ?: true),
        'manual_transfer_instructions' => getenv('XLX_MANUAL_TRANSFER_INSTRUCTIONS') ?: 'Переведите сумму тарифа и отправьте номер или ссылку чека на проверку.',
        'yookassa' => [
            'shop_id' => getenv('YOOKASSA_SHOP_ID') ?: 'change_me',
            'secret_key' => getenv('YOOKASSA_SECRET_KEY') ?: 'change_me',
            'return_url' => getenv('YOOKASSA_RETURN_URL') ?: $baseUrl . '/payment/return',
            'webhook_secret' => getenv('YOOKASSA_WEBHOOK_SECRET') ?: 'change_this_webhook_token',
            'capture' => (bool) (getenv('YOOKASSA_CAPTURE') ?: true),
        ],
    ],

    'security' => [
        'session_name' => getenv('XLX_SESSION_NAME') ?: 'xlx_session',
        'password_min_length' => (int) (getenv('XLX_PASSWORD_MIN_LENGTH') ?: 10),
        'dmr_password_length' => (int) (getenv('XLX_DMR_PASSWORD_LENGTH') ?: 16),
    ],

    'admin' => [
        'api_token' => getenv('XLX_ADMIN_TOKEN') ?: 'change_this_long_random_admin_token',
    ],
];
