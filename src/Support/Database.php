<?php

declare(strict_types=1);

namespace Xlx\Support;

use PDO;

final class Database
{
    public function __construct(private readonly Config $config)
    {
    }

    public function pdo(): PDO
    {
        $charset = $this->config->get('database.charset', 'utf8mb4');
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config->get('database.host', '127.0.0.1'),
            (int) $this->config->get('database.port', 3306),
            $this->config->get('database.name'),
            $charset
        );

        return new PDO($dsn, (string) $this->config->get('database.user'), (string) $this->config->get('database.password'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
