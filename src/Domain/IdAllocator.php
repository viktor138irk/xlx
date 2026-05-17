<?php

declare(strict_types=1);

namespace Xlx\Domain;

use PDO;
use RuntimeException;
use Xlx\Support\Config;

final class IdAllocator
{
    public function __construct(private readonly Config $config)
    {
    }

    public function allocate(PDO $pdo, int $userId): int
    {
        $existing = $pdo->prepare('SELECT dmr_id FROM dmr_ids WHERE user_id = ? LIMIT 1');
        $existing->execute([$userId]);
        $dmrId = $existing->fetchColumn();
        if ($dmrId !== false) {
            return (int) $dmrId;
        }

        $min = (int) $this->config->get('id_allocator.min_id', 9000001);
        $max = (int) $this->config->get('id_allocator.max_id', 9099999);

        $next = $pdo->query('SELECT COALESCE(MAX(dmr_id), 0) + 1 FROM dmr_ids')->fetchColumn();
        $candidate = max($min, (int) $next);

        while ($candidate <= $max) {
            try {
                $insert = $pdo->prepare('INSERT INTO dmr_ids (user_id, dmr_id, status) VALUES (?, ?, ?)');
                $insert->execute([$userId, $candidate, 'active']);
                return $candidate;
            } catch (\PDOException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $candidate++;
            }
        }

        throw new RuntimeException('DMR ID range is exhausted.');
    }
}
