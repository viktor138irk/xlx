<?php

declare(strict_types=1);

namespace Xlx\Support;

final class Security
{
    public static function normalizeCallsign(string $callsign): string
    {
        return strtoupper(trim($callsign));
    }

    public static function isValidCallsign(string $callsign): bool
    {
        return (bool) preg_match('/^[A-Z0-9][A-Z0-9\/-]{2,18}$/', $callsign);
    }

    public static function randomPassword(int $length): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $password = '';
        $max = strlen($alphabet) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }

        return $password;
    }

    public static function passwordHint(string $password): string
    {
        return substr($password, 0, 3) . '...' . substr($password, -3);
    }

    public static function requireAdminToken(Config $config): void
    {
        $expected = (string) $config->get('admin.api_token', '');
        $actual = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';

        if ($expected === '' || !hash_equals($expected, $actual)) {
            Response::error('Admin token is missing or invalid.', 401);
        }
    }
}
