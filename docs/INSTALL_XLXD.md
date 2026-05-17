# Установка XLX reflector

Проект состоит из двух частей:

- `xlxd` - сам мультипротокольный XLX reflector;
- PHP-панель - регистрация, YooKassa, ручной чек, выдача DMR ID и данных для Pi-Star.

## Требования

- Ubuntu 22.04/24.04 или Debian 12.
- Публичный статический IP.
- DNS-имя, например `xlx.example.com`.
- Открытый UDP `62030` для Pi-Star MMDVM.
- Root-доступ на сервер.

## Настройка XLX

```bash
git clone https://github.com/viktor138irk/xlx.git
cd xlx
sudo mkdir -p /etc/xlx
sudo cp config/xlxd.env.example /etc/xlx/xlxd.env
sudo nano /etc/xlx/xlxd.env
```

Минимально поменяй:

```bash
XLX_REFLECTOR_NAME=XLX138
XLX_SERVER_HOST=xlx.example.com
XLX_SYSOP_CALLSIGN=R0XXX
XLX_SYSOP_EMAIL=admin@example.com
XLX_COUNTRY=RU
```

## Установка сервера

```bash
sudo bash scripts/install-xlxd.sh
sudo ./scripts/firewall-ufw.sh
sudo systemctl start xlxd
sudo systemctl status xlxd
```

Логи:

```bash
sudo tail -f /var/log/xlxd.log
```

## Управление

```bash
sudo systemctl start xlxd
sudo systemctl stop xlxd
sudo systemctl restart xlxd
sudo systemctl status xlxd
```

## Pi-Star

После оплаты панель выдаст:

- сервер: `XLX_SERVER_HOST`;
- порт: `62030`;
- логин: позывной;
- пароль: отдельный DMR-пароль;
- DMR ID: выданный системой ID;
- модуль: `A` по умолчанию.

Полный DMR-пароль показывается один раз. Если пользователь потерял пароль, нужно выпустить новый.
