<?php

declare(strict_types=1);

namespace Xlx\Domain;

use PDO;
use RuntimeException;
use Xlx\Support\Config;

final class XlxdSettingsService
{
    private const FIELD_MAP = [
        'reflector_name' => 'XLX_REFLECTOR_NAME',
        'server_host' => 'XLX_SERVER_HOST',
        'sysop_callsign' => 'XLX_SYSOP_CALLSIGN',
        'sysop_email' => 'XLX_SYSOP_EMAIL',
        'country' => 'XLX_COUNTRY',
        'dashboard_port' => 'XLX_DASHBOARD_PORT',
        'dmr_port' => 'XLX_DMR_PORT',
        'ysf_port' => 'XLX_YSF_PORT',
        'default_module' => 'XLX_DEFAULT_MODULE',
        'modules' => 'XLX_MODULES',
        'install_path' => 'XLX_INSTALL_PATH',
        'source_path' => 'XLX_SOURCE_PATH',
        'log_path' => 'XLX_LOG_PATH',
        'service_name' => 'XLX_SERVICE_NAME',
        'repo_url' => 'XLX_REPO_URL',
    ];

    public function __construct(private readonly Config $config)
    {
    }

    public function current(PDO $pdo): array
    {
        $settings = [
            'reflector_name' => $this->config->get('xlx.reflector_name', 'XLX000'),
            'server_host' => $this->config->get('xlx.server_host', 'xlx.example.com'),
            'sysop_callsign' => $this->config->get('xlx.sysop_callsign', 'N0CALL'),
            'sysop_email' => $this->config->get('xlx.sysop_email', 'admin@example.com'),
            'country' => $this->config->get('xlx.country', 'RU'),
            'dashboard_port' => (string) $this->config->get('xlx.dashboard_port', 8080),
            'dmr_port' => (string) $this->config->get('xlx.dmr_port', 62030),
            'ysf_port' => (string) $this->config->get('xlx.ysf_port', 42000),
            'default_module' => $this->config->get('xlx.default_module', 'A'),
            'modules' => $this->config->get('xlx.modules', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'),
            'install_path' => $this->config->get('xlx.install_path', '/xlxd'),
            'source_path' => $this->config->get('xlx.source_path', '/usr/src/xlxd'),
            'log_path' => $this->config->get('xlx.log_path', '/var/log/xlxd.log'),
            'service_name' => $this->config->get('xlx.service_name', 'xlxd'),
            'repo_url' => 'https://github.com/LX3JL/xlxd.git',
        ];

        $rows = $pdo->query("SELECT setting_key, setting_value FROM server_settings WHERE setting_key LIKE 'xlxd.%'")->fetchAll();
        foreach ($rows as $row) {
            $key = substr((string) $row['setting_key'], 5);
            if (array_key_exists($key, $settings)) {
                $settings[$key] = (string) $row['setting_value'];
            }
        }

        return $settings;
    }

    public function save(PDO $pdo, array $input, bool $apply): array
    {
        $settings = $this->current($pdo);
        foreach (self::FIELD_MAP as $key => $_) {
            if (array_key_exists($key, $input)) {
                $settings[$key] = $this->sanitize($key, (string) $input[$key]);
            }
        }

        $statement = $pdo->prepare(
            'INSERT INTO server_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );
        foreach ($settings as $key => $value) {
            $statement->execute(['xlxd.' . $key, $value]);
        }

        $env = $this->renderEnv($settings);
        $applied = false;
        $message = 'Settings saved to database.';
        if ($apply) {
            $path = rtrim((string) $this->config->get('xlx.config_path', '/etc/xlx'), '/') . '/xlxd.env';
            $this->writeEnvFile($path, $env);
            $applied = true;
            $message = 'Settings saved and xlxd.env updated.';
        }

        return [
            'settings' => $settings,
            'env' => $env,
            'applied' => $applied,
            'message' => $message,
        ];
    }

    public function renderEnv(array $settings): string
    {
        $lines = [];
        foreach (self::FIELD_MAP as $key => $envKey) {
            $lines[] = $envKey . '=' . $this->escapeEnv((string) ($settings[$key] ?? ''));
        }

        return implode("\n", $lines) . "\n";
    }

    private function sanitize(string $key, string $value): string
    {
        $value = trim($value);
        if (in_array($key, ['dashboard_port', 'dmr_port', 'ysf_port'], true)) {
            $port = (int) $value;
            if ($port < 1 || $port > 65535) {
                throw new RuntimeException('Invalid port for ' . $key . '.');
            }
            return (string) $port;
        }
        if ($key === 'reflector_name' && !preg_match('/^XLX[A-Z0-9]{3}$/', $value)) {
            throw new RuntimeException('Reflector name must look like XLX138.');
        }
        if ($key === 'default_module' && !preg_match('/^[A-Z]$/', $value)) {
            throw new RuntimeException('Default module must be A-Z.');
        }
        if ($key === 'modules' && !preg_match('/^[A-Z]+$/', $value)) {
            throw new RuntimeException('Modules must contain only letters A-Z.');
        }

        return $value;
    }

    private function writeEnvFile(string $path, string $env): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Cannot create config directory: ' . $directory);
        }
        if (file_put_contents($path, $env, LOCK_EX) === false) {
            throw new RuntimeException('Cannot write xlxd env file: ' . $path);
        }
    }

    private function escapeEnv(string $value): string
    {
        if ($value === '' || preg_match('/\s/', $value) || strpbrk($value, '"\'\\$`') !== false) {
            return '"' . str_replace(['\\', '"', '$', '`'], ['\\\\', '\\"', '\\$', '\\`'], $value) . '"';
        }

        return $value;
    }
}
