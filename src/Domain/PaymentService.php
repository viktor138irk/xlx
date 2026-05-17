<?php

declare(strict_types=1);

namespace Xlx\Domain;

use PDO;
use RuntimeException;
use Xlx\Support\Config;

final class PaymentService
{
    public function __construct(
        private readonly Config $config,
        private readonly IdAllocator $idAllocator,
        private readonly AccessCredentialService $accessCredentialService,
        private readonly ?YooKassaClient $yooKassaClient = null,
    ) {
    }

    public function createPendingPayment(PDO $pdo, int $userId): ?array
    {
        $tariff = $pdo->query('SELECT * FROM tariffs WHERE is_active = 1 ORDER BY id ASC LIMIT 1')->fetch();
        if (!$tariff) {
            return null;
        }

        $provider = (string) $this->config->get('billing.mode', 'manual');
        $statement = $pdo->prepare('INSERT INTO payments (user_id, tariff_id, amount, currency, provider, status) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->execute([
            $userId,
            $tariff['id'],
            $tariff['price'],
            $tariff['currency'],
            $provider,
            'pending',
        ]);

        $paymentId = (int) $pdo->lastInsertId();
        $result = [
            'id' => $paymentId,
            'amount' => (float) $tariff['price'],
            'currency' => $tariff['currency'],
            'tariff' => $tariff['name'],
            'provider' => $provider,
        ];

        if ($provider === 'yookassa' && $this->yooKassaClient !== null) {
            $user = $this->findUser($pdo, $userId);
            $created = $this->yooKassaClient->createPayment([
                'id' => $paymentId,
                'amount' => $tariff['price'],
                'currency' => $tariff['currency'],
            ], $user, $tariff);

            $confirmationUrl = $created['confirmation']['confirmation_url'] ?? null;
            $pdo->prepare('UPDATE payments SET provider_payment_id = ?, confirmation_url = ? WHERE id = ?')
                ->execute([$created['id'] ?? null, $confirmationUrl, $paymentId]);

            $result['provider_payment_id'] = $created['id'] ?? null;
            $result['confirmation_url'] = $confirmationUrl;
        }

        if ((bool) $this->config->get('billing.manual_transfer_enabled', false)) {
            $result['manual_transfer'] = [
                'enabled' => true,
                'instructions' => $this->config->get('billing.manual_transfer_instructions'),
            ];
        }

        return $result;
    }

    public function confirm(PDO $pdo, int $paymentId, ?int $adminUserId = null): array
    {
        $pdo->beginTransaction();
        try {
            $paymentStatement = $pdo->prepare(
                'SELECT p.*, t.duration_days, u.callsign FROM payments p JOIN users u ON u.id = p.user_id LEFT JOIN tariffs t ON t.id = p.tariff_id WHERE p.id = ? FOR UPDATE'
            );
            $paymentStatement->execute([$paymentId]);
            $payment = $paymentStatement->fetch();
            if (!$payment) {
                throw new RuntimeException('Payment was not found.');
            }
            if ($payment['status'] === 'paid') {
                $pdo->commit();
                return $this->currentAccess($pdo, (int) $payment['user_id']);
            }
            if ($payment['status'] !== 'pending') {
                throw new RuntimeException('Payment is not pending.');
            }

            $durationDays = (int) ($payment['duration_days'] ?? $this->config->get('billing.default_duration_days', 30));
            $expiresAt = (new \DateTimeImmutable('now'))->modify('+' . $durationDays . ' days')->format('Y-m-d H:i:s');

            $pdo->prepare('UPDATE payments SET status = ?, paid_at = NOW() WHERE id = ?')->execute(['paid', $paymentId]);
            $pdo->prepare('UPDATE users SET status = ?, access_expires_at = ? WHERE id = ?')->execute(['active', $expiresAt, $payment['user_id']]);

            $dmrId = $this->idAllocator->allocate($pdo, (int) $payment['user_id']);
            $credential = $this->accessCredentialService->enableForUser($pdo, (int) $payment['user_id'], $payment['callsign']);

            $pdo->prepare(
                'INSERT INTO audit_logs (actor_user_id, action, target_type, target_id, payload_json) VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $adminUserId,
                'payment.confirmed',
                'payment',
                $paymentId,
                json_encode(['user_id' => (int) $payment['user_id'], 'dmr_id' => $dmrId], JSON_UNESCAPED_SLASHES),
            ]);

            $pdo->commit();

            return [
                'user_id' => (int) $payment['user_id'],
                'callsign' => $payment['callsign'],
                'dmr_id' => $dmrId,
                'access_expires_at' => $expiresAt,
                'connection' => [
                    'server_host' => $this->config->get('xlx.server_host'),
                    'dmr_port' => (int) $this->config->get('xlx.dmr_port', 62030),
                    'default_module' => $this->config->get('xlx.default_module', 'A'),
                    'username' => $credential['username'],
                    'password' => $credential['password'],
                ],
            ];
        } catch (\Throwable $throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $throwable;
        }
    }

    public function submitReceipt(PDO $pdo, int $paymentId, int $userId, string $receiptReference, ?string $comment): array
    {
        $payment = $pdo->prepare('SELECT id, user_id, status FROM payments WHERE id = ? AND user_id = ? LIMIT 1');
        $payment->execute([$paymentId, $userId]);
        $row = $payment->fetch();
        if (!$row) {
            throw new RuntimeException('Payment was not found for this user.');
        }
        if ($row['status'] !== 'pending') {
            throw new RuntimeException('Only pending payments can receive receipts.');
        }
        if (trim($receiptReference) === '') {
            throw new RuntimeException('Receipt reference is required.');
        }

        $statement = $pdo->prepare(
            'INSERT INTO payment_receipts (payment_id, user_id, receipt_reference, comment) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$paymentId, $userId, trim($receiptReference), $comment]);

        return [
            'receipt_id' => (int) $pdo->lastInsertId(),
            'payment_id' => $paymentId,
            'status' => 'pending',
        ];
    }

    public function approveReceipt(PDO $pdo, int $receiptId, ?int $adminUserId = null): array
    {
        $receipt = $pdo->prepare('SELECT * FROM payment_receipts WHERE id = ? LIMIT 1');
        $receipt->execute([$receiptId]);
        $row = $receipt->fetch();
        if (!$row) {
            throw new RuntimeException('Receipt was not found.');
        }
        if ($row['status'] !== 'pending') {
            throw new RuntimeException('Receipt is not pending.');
        }

        $result = $this->confirm($pdo, (int) $row['payment_id'], $adminUserId);

        $pdo->prepare('UPDATE payment_receipts SET status = ?, reviewed_by_user_id = ?, reviewed_at = NOW() WHERE id = ?')
            ->execute(['approved', $adminUserId, $receiptId]);

        return $result;
    }

    public function confirmProviderPayment(PDO $pdo, string $providerPaymentId): array
    {
        if ($this->yooKassaClient !== null) {
            $providerPayment = $this->yooKassaClient->fetchPayment($providerPaymentId);
            if (($providerPayment['status'] ?? null) !== 'succeeded' || ($providerPayment['paid'] ?? false) !== true) {
                throw new RuntimeException('YooKassa payment is not succeeded.');
            }
        }

        $statement = $pdo->prepare('SELECT id FROM payments WHERE provider = ? AND provider_payment_id = ? LIMIT 1');
        $statement->execute(['yookassa', $providerPaymentId]);
        $paymentId = $statement->fetchColumn();
        if ($paymentId === false) {
            throw new RuntimeException('Local payment was not found.');
        }

        return $this->confirm($pdo, (int) $paymentId);
    }

    private function findUser(PDO $pdo, int $userId): array
    {
        $statement = $pdo->prepare('SELECT id, callsign, email FROM users WHERE id = ? LIMIT 1');
        $statement->execute([$userId]);
        $user = $statement->fetch();
        if (!$user) {
            throw new RuntimeException('User was not found.');
        }

        return $user;
    }

    private function currentAccess(PDO $pdo, int $userId): array
    {
        $statement = $pdo->prepare(
            'SELECT u.id AS user_id, u.callsign, u.access_expires_at, d.dmr_id, c.username, c.dmr_password_hint
             FROM users u
             LEFT JOIN dmr_ids d ON d.user_id = u.id
             LEFT JOIN access_credentials c ON c.user_id = u.id
             WHERE u.id = ?
             LIMIT 1'
        );
        $statement->execute([$userId]);
        $row = $statement->fetch();
        if (!$row) {
            throw new RuntimeException('User was not found.');
        }

        return [
            'user_id' => (int) $row['user_id'],
            'callsign' => $row['callsign'],
            'dmr_id' => $row['dmr_id'] !== null ? (int) $row['dmr_id'] : null,
            'access_expires_at' => $row['access_expires_at'],
            'connection' => [
                'server_host' => $this->config->get('xlx.server_host'),
                'dmr_port' => (int) $this->config->get('xlx.dmr_port', 62030),
                'default_module' => $this->config->get('xlx.default_module', 'A'),
                'username' => $row['username'],
                'password_hint' => $row['dmr_password_hint'],
            ],
        ];
    }
}
