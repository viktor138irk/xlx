<?php

declare(strict_types=1);

namespace Xlx\Support;

final class Input
{
    public static function json(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            Response::error('Invalid JSON request body.', 400);
        }

        return $decoded;
    }
}
