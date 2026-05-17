<?php

declare(strict_types=1);

namespace Xlx\Domain;

use RuntimeException;
use Xlx\Support\Config;

final class YooKassaClient
{
    private const API_URL = 'https://api.yookassa.ru/v3';

    public function __construct(private readonly Config $config)
    {
    }

    public function createPayment(array $payment, array $user, array $tariff): array
    {
        $payload = [
            'amount' => [
                'value' => number_format((float) $payment['amount'], 2, '.', ''),
                'currency' => $payment['currency'],
            ],
            'capture' => (bool) $this->config->get('billing.yookassa.capture', true),
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => $this->config->get('billing.yookassa.return_url'),
            ],
            'description' => sprintf('XLX доступ для %s, платеж #%d', $user['callsign'], $payment['id']),
            'metadata' => [
                'payment_id' => (string) $payment['id'],
                'user_id' => (string) $user['id'],
                'callsign' => $user['callsign'],
                'tariff_id' => (string) $tariff['id'],
            ],
        ];

        return $this->request('POST', '/payments', $payload, 'xlx-payment-' . $payment['id']);
    }

    public function fetchPayment(string $providerPaymentId): array
    {
        return $this->request('GET', '/payments/' . rawurlencode($providerPaymentId));
    }

    private function request(string $method, string $path, ?array $payload = null, ?string $idempotenceKey = null): array
    {
        $shopId = (string) $this->config->get('billing.yookassa.shop_id');
        $secretKey = (string) $this->config->get('billing.yookassa.secret_key');
        if ($shopId === '' || $shopId === 'change_me' || $secretKey === '' || $secretKey === 'change_me') {
            throw new RuntimeException('YooKassa credentials are not configured.');
        }

        $headers = [
            'Authorization: Basic ' . base64_encode($shopId . ':' . $secretKey),
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if ($idempotenceKey !== null) {
            $headers[] = 'Idempotence-Key: ' . substr($idempotenceKey, 0, 64);
        }

        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers) . "\r\n",
                'ignore_errors' => true,
                'timeout' => 15,
            ],
        ];
        if ($payload !== null) {
            $options['http']['content'] = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $body = @file_get_contents(self::API_URL . $path, false, stream_context_create($options));
        if ($body === false) {
            throw new RuntimeException('YooKassa request failed.');
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('YooKassa returned invalid JSON.');
        }
        if (isset($decoded['type']) && $decoded['type'] === 'error') {
            throw new RuntimeException((string) ($decoded['description'] ?? 'YooKassa error.'));
        }

        return $decoded;
    }
}
