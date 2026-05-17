<?php

declare(strict_types=1);

use Xlx\Domain\AccessCredentialService;
use Xlx\Domain\AccessExportService;
use Xlx\Domain\IdAllocator;
use Xlx\Domain\PaymentService;
use Xlx\Domain\RegistrationService;
use Xlx\Domain\XlxdSettingsService;
use Xlx\Domain\YooKassaClient;
use Xlx\Support\Database;
use Xlx\Support\Input;
use Xlx\Support\Response;
use Xlx\Support\Security;

$config = require __DIR__ . '/../bootstrap.php';
$pdo = (new Database($config))->pdo();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

try {
    if ($method === 'GET' && ($path === '/' || $path === '/dashboard')) {
        require __DIR__ . '/views/home.php';
        exit;
    }

    if ($method === 'GET' && $path === '/admin') {
        require __DIR__ . '/views/admin.php';
        exit;
    }

    if ($method === 'GET' && $path === '/api/health') {
        Response::json(['ok' => true, 'service' => 'xlx-panel']);
    }

    if ($method === 'GET' && $path === '/api/callsigns/check') {
        $paymentService = new PaymentService($config, new IdAllocator($config), new AccessCredentialService($config), new YooKassaClient($config));
        $service = new RegistrationService($config, $paymentService);
        Response::json(['ok' => true, 'data' => $service->checkCallsign($pdo, (string) ($_GET['callsign'] ?? ''))]);
    }

    if ($method === 'POST' && $path === '/api/register') {
        $paymentService = new PaymentService($config, new IdAllocator($config), new AccessCredentialService($config), new YooKassaClient($config));
        $service = new RegistrationService($config, $paymentService);
        Response::json(['ok' => true, 'data' => $service->register($pdo, Input::json())], 201);
    }

    if ($method === 'POST' && $path === '/api/payments/receipt') {
        $input = Input::json();
        $service = new PaymentService($config, new IdAllocator($config), new AccessCredentialService($config), new YooKassaClient($config));
        Response::json(['ok' => true, 'data' => $service->submitReceipt(
            $pdo,
            (int) ($input['payment_id'] ?? 0),
            (int) ($input['user_id'] ?? 0),
            (string) ($input['receipt_reference'] ?? ''),
            isset($input['comment']) ? (string) $input['comment'] : null,
        )], 201);
    }

    if ($method === 'POST' && $path === '/api/admin/payments/confirm') {
        Security::requireAdminToken($config);
        $input = Input::json();
        $service = new PaymentService($config, new IdAllocator($config), new AccessCredentialService($config), new YooKassaClient($config));
        Response::json(['ok' => true, 'data' => $service->confirm($pdo, (int) ($input['payment_id'] ?? 0), $input['admin_user_id'] ?? null)]);
    }

    if ($method === 'POST' && $path === '/api/admin/receipts/approve') {
        Security::requireAdminToken($config);
        $input = Input::json();
        $service = new PaymentService($config, new IdAllocator($config), new AccessCredentialService($config), new YooKassaClient($config));
        Response::json(['ok' => true, 'data' => $service->approveReceipt($pdo, (int) ($input['receipt_id'] ?? 0), $input['admin_user_id'] ?? null)]);
    }

    if ($method === 'POST' && $path === '/api/webhooks/yookassa') {
        $token = $_SERVER['HTTP_X_WEBHOOK_TOKEN'] ?? ($_GET['token'] ?? '');
        if (!hash_equals((string) $config->get('billing.yookassa.webhook_secret'), (string) $token)) {
            Response::error('Webhook token is missing or invalid.', 401);
        }

        $input = Input::json();
        if (($input['event'] ?? '') !== 'payment.succeeded') {
            Response::json(['ok' => true, 'ignored' => true]);
        }

        $providerPaymentId = (string) ($input['object']['id'] ?? '');
        $service = new PaymentService($config, new IdAllocator($config), new AccessCredentialService($config), new YooKassaClient($config));
        Response::json(['ok' => true, 'data' => $service->confirmProviderPayment($pdo, $providerPaymentId)]);
    }

    if ($method === 'GET' && $path === '/api/admin/access/export') {
        Security::requireAdminToken($config);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="xlx-active-users.csv"');
        echo (new AccessExportService())->activeUsersCsv($pdo);
        exit;
    }

    if ($method === 'GET' && $path === '/api/admin/xlxd/settings') {
        Security::requireAdminToken($config);
        $service = new XlxdSettingsService($config);
        $settings = $service->current($pdo);
        Response::json([
            'ok' => true,
            'data' => [
                'settings' => $settings,
                'env' => $service->renderEnv($settings),
            ],
        ]);
    }

    if ($method === 'POST' && $path === '/api/admin/xlxd/settings') {
        Security::requireAdminToken($config);
        $input = Input::json();
        $service = new XlxdSettingsService($config);
        Response::json(['ok' => true, 'data' => $service->save($pdo, $input['settings'] ?? [], (bool) ($input['apply'] ?? false))]);
    }

    Response::error('Route not found.', 404);
} catch (Throwable $throwable) {
    Response::error($throwable->getMessage(), 400);
}
