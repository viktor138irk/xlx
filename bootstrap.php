<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Xlx\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $path = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$configPath = __DIR__ . '/config/config.php';
if (!is_file($configPath)) {
    $configPath = __DIR__ . '/config/config.example.php';
}

$config = require $configPath;

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

return new Xlx\Support\Config($config);
