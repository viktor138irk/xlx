<?php

declare(strict_types=1);

namespace Xlx\Domain;

use PDO;
use RuntimeException;
use Xlx\Support\Config;
use Xlx\Support\Security;

final class RegistrationService
{
    public function __construct(
        private readonly Config $config,
        private readonly PaymentService $paymentService,
    ) {
    }

    public function checkCallsign(PDO $pdo, string $callsign): array
    {
        $callsign = Security::normalizeCallsign($callsign);
        if (!Security::isValidCallsign($callsign)) {
            return [
                'callsign' => $callsign,
                'available' => false,
                'status' => 'invalid_format',
                'message' => 'Invalid callsign format.',
            ];
        }

        $statement = $pdo->prepare('SELECT id FROM users WHERE callsign = ? LIMIT 1');
        $statement->execute([$callsign]);

        return [
            'callsign' => $callsign,
            'available' => $statement->fetch() === false,
            'status' => 'internal_registry',
            'message' => 'Checked against local registered users.',
        ];
    }

    public function register(PDO $pdo, array $input): array
    {
        $callsign = Security::normalizeCallsign((string) ($input['callsign'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');

        if (!Security::isValidCallsign($callsign)) {
            throw new RuntimeException('Invalid callsign format.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email address.');
        }
        if (strlen($password) < (int) $this->config->get('security.password_min_length', 10)) {
            throw new RuntimeException('Password is too short.');
        }

        $callsignCheck = $this->checkCallsign($pdo, $callsign);
        if (!$callsignCheck['available']) {
            throw new RuntimeException('Callsign is already registered.');
        }

        $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $emailCheck->execute([$email]);
        if ($emailCheck->fetch()) {
            throw new RuntimeException('Email is already registered.');
        }

        $verification = [
            'status' => 'verified',
            'source' => 'internal_registry',
            'payload' => [
                'message' => 'Callsign is not present in local registered users.',
            ],
        ];
        $status = 'payment_pending';

        $pdo->beginTransaction();
        try {
            $insertUser = $pdo->prepare(
                'INSERT INTO users (callsign, email, password_hash, status) VALUES (?, ?, ?, ?)'
            );
            $insertUser->execute([$callsign, $email, password_hash($password, PASSWORD_DEFAULT), $status]);
            $userId = (int) $pdo->lastInsertId();

            $insertCheck = $pdo->prepare(
                'INSERT INTO callsign_checks (user_id, callsign, source, status, response_json, checked_at) VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $insertCheck->execute([
                $userId,
                $callsign,
                $verification['source'],
                $verification['status'],
                json_encode($verification['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            $payment = null;
            if ($status === 'payment_pending') {
                $payment = $this->paymentService->createPendingPayment($pdo, $userId);
            }

            $pdo->commit();

            return [
                'user_id' => $userId,
                'callsign' => $callsign,
                'status' => $status,
                'verification' => [
                    'status' => $verification['status'],
                    'source' => $verification['source'],
                ],
                'payment' => $payment,
            ];
        } catch (\Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $throwable;
        }
    }
}
