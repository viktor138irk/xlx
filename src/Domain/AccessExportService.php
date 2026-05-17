<?php

declare(strict_types=1);

namespace Xlx\Domain;

use PDO;

final class AccessExportService
{
    public function activeUsersCsv(PDO $pdo): string
    {
        $rows = $pdo->query(
            "SELECT u.callsign, d.dmr_id, c.username, c.dmr_password_hint, u.access_expires_at
             FROM users u
             JOIN dmr_ids d ON d.user_id = u.id
             JOIN access_credentials c ON c.user_id = u.id
             WHERE u.status = 'active'
               AND c.is_enabled = 1
               AND (u.access_expires_at IS NULL OR u.access_expires_at > NOW())
             ORDER BY u.callsign"
        )->fetchAll();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['callsign', 'dmr_id', 'username', 'password_hint', 'access_expires_at']);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);

        return (string) stream_get_contents($handle);
    }
}
