# Регистрация и оплата

## Сценарий пользователя

1. Пользователь отправляет позывной, email и пароль кабинета.
2. Система нормализует позывной и проверяет, что такого позывного еще нет во внутренней базе.
3. Если позывной принят, создается платеж.
4. Пользователь выбирает:
   - оплату через YooKassa;
   - перевод с отправкой чека на ручную проверку.
5. После подтверждения оплаты система:
   - активирует аккаунт;
   - назначает внутренний DMR ID;
   - генерирует отдельный DMR-пароль;
   - отдает данные подключения Pi-Star.

## YooKassa

В `config/config.php`:

```php
'billing' => [
    'mode' => 'yookassa',
    'yookassa' => [
        'shop_id' => '...',
        'secret_key' => '...',
        'return_url' => 'https://xlx.example.com/payment/return',
        'webhook_secret' => 'long-random-token',
        'capture' => true,
    ],
],
```

Webhook URL:

```text
https://xlx.example.com/api/webhooks/yookassa?token=long-random-token
```

Обрабатывается событие:

```text
payment.succeeded
```

Повторный webhook безопасен: если платеж уже оплачен, система вернет текущий доступ без повторной выдачи ID.

## Перевод с чеком

Если пользователь оплатил переводом, он отправляет номер или ссылку чека:

```http
POST /api/payments/receipt
Content-Type: application/json

{
  "payment_id": 1,
  "user_id": 1,
  "receipt_reference": "https://bank.example/receipt/123",
  "comment": "Оплата за R0XXX"
}
```

Админ подтверждает чек:

```http
POST /api/admin/receipts/approve
X-Admin-Token: change_this_long_random_admin_token
Content-Type: application/json

{
  "receipt_id": 1,
  "admin_user_id": 1
}
```

## Основные API

Регистрация:

```http
POST /api/register
Content-Type: application/json

{
  "callsign": "R0XXX",
  "email": "user@example.com",
  "password": "strong-password"
}
```

Подтверждение платежа админом:

```http
POST /api/admin/payments/confirm
X-Admin-Token: change_this_long_random_admin_token
Content-Type: application/json

{
  "payment_id": 1,
  "admin_user_id": 1
}
```

Экспорт активных пользователей для gateway:

```http
GET /api/admin/access/export
X-Admin-Token: change_this_long_random_admin_token
```

## Pi-Star и пароль

`xlxd` поднимает сам reflector и принимает MMDVM/DMR на UDP `62030`. Персональная проверка логина/пароля на уровне Pi-Star требует отдельного access gateway/proxy или auth-слоя перед reflector.

В проекте уже есть:

- хранение отдельного DMR-пароля;
- включение/выключение доступа;
- экспорт активных пользователей;
- единая активация после YooKassa или ручного чека.

Следующий слой - UDP gateway, который будет читать экспорт активных пользователей и пропускать к `xlxd` только оплаченные подключения.
