# Установка всей системы

`scripts/install-system.sh` ставит сразу:

- Apache + PHP;
- MariaDB;
- базу и пользователя БД;
- web-панель `/`;
- админку `/admin`;
- `xlxd`;
- systemd service для reflector;
- `/etc/xlx/xlxd.env`.

## Без домена

На чистом Ubuntu/Debian сервере:

```bash
git clone https://github.com/viktor138irk/xlx.git
cd xlx
sudo bash scripts/install-system.sh
```

Установщик сам выберет адрес:

1. `XLX_PUBLIC_HOST`, если переменная задана;
2. `hostname -f`, если он похож на реальное имя;
3. первый IP из `hostname -I`;
4. `127.0.0.1` как последний fallback.

Если порт `80` уже занят другим сервисом, панель автоматически будет поставлена на `8088`. Порт можно задать вручную:

```bash
sudo XLX_PANEL_PORT=8090 bash scripts/install-system.sh
```

В конце он покажет:

- URL сайта;
- URL админки;
- admin token;
- webhook URL для YooKassa;
- логин и пароль БД.

## С доменом

Если домен уже есть:

```bash
sudo XLX_PUBLIC_HOST=xlx.example.ru bash scripts/install-system.sh
```

Если домен появится позже, открой `/admin` и поменяй поле `Домен`. Это обновит настройки `xlxd` без ручного редактирования конфигов.

## YooKassa

Можно передать ключи сразу:

```bash
sudo \
  XLX_PUBLIC_HOST=xlx.example.ru \
  YOOKASSA_SHOP_ID=123456 \
  YOOKASSA_SECRET_KEY=secret \
  bash scripts/install-system.sh
```

Webhook будет такого вида:

```text
http://адрес-сервера/api/webhooks/yookassa?token=секрет
```

Если включишь HTTPS, укажи:

```bash
sudo XLX_PUBLIC_SCHEME=https XLX_PUBLIC_HOST=xlx.example.ru bash scripts/install-system.sh
```

## Повторный запуск

Скрипт можно запускать повторно. Он:

- обновит `config/config.php`;
- обновит `/etc/xlx/xlxd.env`;
- применит SQL-схему;
- пересоберет `xlxd`;
- перезагрузит Apache.

Перед повторным запуском с существующей БД лучше передать старый пароль:

```bash
sudo XLX_DB_PASSWORD='старый-пароль' bash scripts/install-system.sh
```
