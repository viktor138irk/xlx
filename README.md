# XLX Server

Собственный XLX/DMR-сервер для пользователей Pi-Star.

## Что уже есть

- платная регистрация;
- проверка позывного по внутренней базе зарегистрированных пользователей;
- YooKassa;
- ручной перевод с подтверждением по чеку;
- автоматическая выдача внутреннего DMR ID;
- отдельный DMR-пароль для Pi-Star;
- установка и управление `xlxd`;
- главный пользовательский дашборд `/`;
- админка настроек `xlxd` `/admin`;
- графический редактор `/xlxd/xlxd.blacklist`, `xlxd.whitelist`, `xlxd.interlink`, `xlxd.terminal`;
- экспорт активных пользователей для будущего access gateway.

## Компоненты

- `xlxd` - мультипротокольный XLX reflector.
- `public/index.php` - PHP API и web routes.
- `public/assets` - стили и JS дашбордов.
- `src/Domain` - регистрация, проверка дублей позывного, оплата, выдача ID, настройки XLX.
- `database/schema.sql` - схема MariaDB/MySQL.
- `scripts/install-xlxd.sh` - установка и сборка `xlxd`.
- `scripts/xlx-control` - start/stop/status для systemd.

## Быстрый старт XLX

Для полной установки панели, базы, YooKassa-заготовки и самого `xlxd` используй общий установщик.

### Установка без домена

```bash
sudo apt update
sudo apt install -y git
git clone https://github.com/viktor138irk/xlx.git
cd xlx
sudo bash scripts/install-system.sh
```

Web-панель устанавливается в `/opt/xlx-server`, поэтому Apache не зависит от папки, где был выполнен `git clone`.

Установщик сам выберет адрес сервера:

1. `XLX_PUBLIC_HOST`, если переменная задана;
2. `hostname -f`;
3. первый IP из `hostname -I`;
4. `127.0.0.1`, если ничего не найдено.

В конце установки будут показаны:

- адрес сайта;
- адрес админки;
- admin token;
- webhook URL для YooKassa;
- логин и пароль БД.

### Установка с доменом

```bash
sudo apt update
sudo apt install -y git
git clone https://github.com/viktor138irk/xlx.git
cd xlx
sudo XLX_PUBLIC_HOST=xlx.example.ru bash scripts/install-system.sh
```

Если домен появится позже, его можно поменять в админке `/admin`.

### Установка с YooKassa

```bash
sudo \
  XLX_PUBLIC_HOST=xlx.example.ru \
  YOOKASSA_SHOP_ID=123456 \
  YOOKASSA_SECRET_KEY=secret_key \
  bash scripts/install-system.sh
```

Webhook для YooKassa установщик выведет в конце. Он выглядит так:

```text
http://адрес-сервера/api/webhooks/yookassa?token=секрет
```

Если сервер работает через HTTPS:

```bash
sudo \
  XLX_PUBLIC_SCHEME=https \
  XLX_PUBLIC_HOST=xlx.example.ru \
  YOOKASSA_SHOP_ID=123456 \
  YOOKASSA_SECRET_KEY=secret_key \
  bash scripts/install-system.sh
```

### После установки

Открой:

- `/` - главная страница и регистрация пользователей;
- `/admin` - админка настроек `xlxd`;
- `/api/health` - проверка API.

Для Pi-Star нужен UDP порт `62030`. Также обычно открывают:

```bash
sudo bash scripts/firewall-ufw.sh
```

### Управление XLX

```bash
sudo systemctl start xlxd
sudo systemctl stop xlxd
sudo systemctl restart xlxd
sudo systemctl status xlxd
sudo tail -f /var/log/xlxd.log
```

### Только установка reflector

Если нужна только сборка и запуск `xlxd` без полной панели:

```bash
git clone https://github.com/viktor138irk/xlx.git
cd xlx
sudo mkdir -p /etc/xlx
sudo cp config/xlxd.env.example /etc/xlx/xlxd.env
sudo nano /etc/xlx/xlxd.env
sudo bash scripts/install-xlxd.sh
sudo systemctl start xlxd
```

## Документация

- `docs/INSTALL_SYSTEM.md` - установка всей системы без привязки к домену или IP.
- `docs/INSTALL_XLXD.md` - установка reflector.
- `docs/PAYMENTS_AND_REGISTRATION.md` - регистрация, YooKassa и чеки.
- `docs/ADMIN_DASHBOARD.md` - главная и админка.

## Статус

v0.2.0-dev.

Следующий шаг - UDP access gateway для жесткой проверки оплаченного доступа перед `xlxd`.
