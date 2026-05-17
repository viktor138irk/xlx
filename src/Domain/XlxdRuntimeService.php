<?php

declare(strict_types=1);

namespace Xlx\Domain;

use PDO;
use RuntimeException;
use Xlx\Support\Config;

final class XlxdRuntimeService
{
    private const ACTIONS = ['start', 'stop', 'restart', 'status'];

    public function __construct(private readonly Config $config)
    {
    }

    public function dashboard(PDO $pdo): array
    {
        return [
            'service' => $this->status(),
            'counters' => $this->counters($pdo),
            'latest_events' => $this->latestEvents(20),
        ];
    }

    public function status(): array
    {
        $service = $this->serviceName();
        $active = trim($this->run('systemctl is-active ' . escapeshellarg($service), false)['output']);
        $enabled = trim($this->run('systemctl is-enabled ' . escapeshellarg($service), false)['output']);

        return [
            'service_name' => $service,
            'active' => $active !== '' ? $active : 'unknown',
            'enabled' => $enabled !== '' ? $enabled : 'unknown',
            'log_path' => $this->logPath(),
            'checked_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function control(string $action): array
    {
        if (!in_array($action, self::ACTIONS, true)) {
            throw new RuntimeException('Unsupported xlxd action.');
        }

        $service = $this->serviceName();
        $command = $action === 'status'
            ? 'systemctl status ' . escapeshellarg($service) . ' --no-pager'
            : 'sudo -n systemctl ' . escapeshellarg($action) . ' ' . escapeshellarg($service);

        $result = $this->run($command, false);

        return [
            'action' => $action,
            'exit_code' => $result['exit_code'],
            'output' => $result['output'],
            'service' => $this->status(),
        ];
    }

    public function latestEvents(int $limit = 20): array
    {
        $path = $this->logPath();
        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $lines = array_values(array_filter($lines, static fn (string $line): bool => trim($line) !== ''));
        $lines = array_slice($lines, -$limit);

        return array_map(static function (string $line): array {
            return [
                'time' => self::extractTime($line),
                'callsign' => self::extractCallsign($line),
                'message' => $line,
            ];
        }, array_reverse($lines));
    }

    private function counters(PDO $pdo): array
    {
        return [
            'users_total' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'users_active' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
            'payments_pending' => (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn(),
            'payments_paid' => (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'paid'")->fetchColumn(),
        ];
    }

    private function run(string $command, bool $throwOnFailure = true): array
    {
        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);
        $text = implode("\n", $output);

        if ($throwOnFailure && $exitCode !== 0) {
            throw new RuntimeException($text !== '' ? $text : 'Command failed.');
        }

        return [
            'exit_code' => $exitCode,
            'output' => $text,
        ];
    }

    private function serviceName(): string
    {
        return preg_replace('/[^A-Za-z0-9_.@-]/', '', (string) $this->config->get('xlx.service_name', 'xlxd')) ?: 'xlxd';
    }

    private function logPath(): string
    {
        return (string) $this->config->get('xlx.log_path', '/var/log/xlxd.log');
    }

    private static function extractTime(string $line): ?string
    {
        if (preg_match('/(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})/', $line, $match)) {
            return $match[1];
        }
        if (preg_match('/([A-Z][a-z]{2}\s+\d{1,2}\s+\d{2}:\d{2}:\d{2})/', $line, $match)) {
            return $match[1];
        }

        return null;
    }

    private static function extractCallsign(string $line): ?string
    {
        if (preg_match('/\b([A-Z0-9]{2,8}[-\/]?[A-Z0-9]{0,4})\b/', strtoupper($line), $match)) {
            return $match[1];
        }

        return null;
    }
}
