<?php

declare(strict_types=1);

namespace Xlx\Domain;

use RuntimeException;
use Xlx\Support\Config;

final class XlxdConfigFileService
{
    private const FILES = [
        'blacklist' => [
            'name' => 'Blacklist',
            'filename' => 'xlxd.blacklist',
            'description' => 'Позывные и узлы, которым запрещен доступ.',
        ],
        'whitelist' => [
            'name' => 'Whitelist',
            'filename' => 'xlxd.whitelist',
            'description' => 'Позывные и узлы, которым разрешен доступ.',
        ],
        'interlink' => [
            'name' => 'Interlink',
            'filename' => 'xlxd.interlink',
            'description' => 'Связи с другими XLX reflectors.',
        ],
        'terminal' => [
            'name' => 'Terminal',
            'filename' => 'xlxd.terminal',
            'description' => 'Разрешенные терминалы и узлы.',
        ],
    ];

    public function __construct(private readonly Config $config)
    {
    }

    public function list(): array
    {
        $files = [];
        foreach (array_keys(self::FILES) as $key) {
            $files[] = $this->read($key);
        }

        return $files;
    }

    public function read(string $key): array
    {
        $meta = $this->meta($key);
        $path = $this->path($key);
        $exists = is_file($path);

        return [
            'key' => $key,
            'name' => $meta['name'],
            'filename' => $meta['filename'],
            'path' => $path,
            'description' => $meta['description'],
            'exists' => $exists,
            'writable' => $exists ? is_writable($path) : is_writable(dirname($path)),
            'size' => $exists ? filesize($path) : 0,
            'updated_at' => $exists ? date('Y-m-d H:i:s', filemtime($path)) : null,
            'content' => $exists ? (string) file_get_contents($path) : '',
        ];
    }

    public function save(string $key, string $content): array
    {
        $path = $this->path($key);
        $directory = dirname($path);
        if (!is_dir($directory)) {
            throw new RuntimeException('XLX directory does not exist: ' . $directory);
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $content);
        if ($normalized !== '' && !str_ends_with($normalized, "\n")) {
            $normalized .= "\n";
        }

        if (file_put_contents($path, $normalized, LOCK_EX) === false) {
            throw new RuntimeException('Cannot write XLX config file: ' . $path);
        }

        return $this->read($key);
    }

    private function meta(string $key): array
    {
        if (!array_key_exists($key, self::FILES)) {
            throw new RuntimeException('Unknown XLX config file.');
        }

        return self::FILES[$key];
    }

    private function path(string $key): string
    {
        $meta = $this->meta($key);
        $installPath = rtrim((string) $this->config->get('xlx.install_path', '/xlxd'), '/');
        $path = $installPath . '/' . $meta['filename'];

        $realDirectory = realpath($installPath);
        if ($realDirectory !== false) {
            $candidate = $realDirectory . DIRECTORY_SEPARATOR . $meta['filename'];
            if (!str_starts_with($candidate, $realDirectory . DIRECTORY_SEPARATOR)) {
                throw new RuntimeException('Invalid XLX config path.');
            }
            return $candidate;
        }

        return $path;
    }
}
