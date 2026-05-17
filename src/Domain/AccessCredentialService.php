<?php

declare(strict_types=1);

namespace Xlx\Domain;

use PDO;
use Xlx\Support\Config;
use Xlx\Support\Security;

final class AccessCredentialService
{
    public function __construct(private readonly Config $config)
    {
    }

    public function enableForUser(PDO $pdo, int $userId, string $callsign): array
    {
        $length = (int) $this->config->get('security.dmr_password_length', 16);
        $password = Security::randomPassword($length);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $hint = Security::passwordHint($password);

        $sql = <<<'SQL'
INSERT INTO access_credentials (user_id, username, dmr_password_hash, dmr_password_hint, is_enabled, last_rotated_at)
VALUES (?, ?, ?, ?, 1, NOW())
ON DUPLICATE KEY UPDATE
    username = VALUES(username),
    dmr_password_hash = VALUES(dmr_password_hash),
    dmr_password_hint = VALUES(dmr_password_hint),
    is_enabled = 1,
    last_rotated_at = NOW()
SQL;

        $statement = $pdo->prepare($sql);
        $statement->execute([$userId, $callsign, $hash, $hint]);

        return [
            'username' => $callsign,
            'password' => $password,
            'password_hint' => $hint,
        ];
    }
}
