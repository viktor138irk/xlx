#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run as root: sudo bash scripts/install-system.sh"
  exit 1
fi

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PUBLIC_DIR="$REPO_ROOT/public"

detect_host() {
  if [[ -n "${XLX_PUBLIC_HOST:-}" ]]; then
    echo "$XLX_PUBLIC_HOST"
    return
  fi

  local fqdn
  fqdn="$(hostname -f 2>/dev/null || true)"
  if [[ -n "$fqdn" && "$fqdn" != "localhost" && "$fqdn" != "localhost.localdomain" ]]; then
    echo "$fqdn"
    return
  fi

  local ip
  ip="$(hostname -I 2>/dev/null | awk '{print $1}')"
  if [[ -n "$ip" ]]; then
    echo "$ip"
    return
  fi

  echo "127.0.0.1"
}

port_is_busy() {
  local port="$1"
  ss -ltnH "sport = :${port}" 2>/dev/null | grep -q .
}

detect_panel_port() {
  if [[ -n "${XLX_PANEL_PORT:-}" ]]; then
    echo "$XLX_PANEL_PORT"
    return
  fi

  for port in 80 8088 8090 8091; do
    if ! port_is_busy "$port"; then
      echo "$port"
      return
    fi
  done

  echo "8099"
}

random_token() {
  local token
  token="$(tr -dc 'A-Za-z0-9' </dev/urandom | head -c "${1:-40}" || true)"
  printf "%s" "$token"
}

php_escape() {
  printf "%s" "$1" | sed "s/\\\\/\\\\\\\\/g; s/'/\\\\'/g"
}

sql_escape() {
  printf "%s" "$1" | sed "s/'/''/g"
}

PUBLIC_HOST="$(detect_host)"
PUBLIC_SCHEME="${XLX_PUBLIC_SCHEME:-http}"
PANEL_PORT="$(detect_panel_port)"
if [[ "$PANEL_PORT" == "80" || "$PANEL_PORT" == "443" ]]; then
  BASE_URL="${XLX_BASE_URL:-${PUBLIC_SCHEME}://${PUBLIC_HOST}}"
else
  BASE_URL="${XLX_BASE_URL:-${PUBLIC_SCHEME}://${PUBLIC_HOST}:${PANEL_PORT}}"
fi
REFLECTOR_NAME="${XLX_REFLECTOR_NAME:-XLX000}"
SYSOP_CALLSIGN="${XLX_SYSOP_CALLSIGN:-N0CALL}"
SYSOP_EMAIL="${XLX_SYSOP_EMAIL:-admin@${PUBLIC_HOST%%:*}}"
COUNTRY="${XLX_COUNTRY:-RU}"
DB_NAME="${XLX_DB_NAME:-xlx_server}"
DB_USER="${XLX_DB_USER:-xlx_user}"
DB_PASSWORD="${XLX_DB_PASSWORD:-$(random_token 32)}"
ADMIN_TOKEN="${XLX_ADMIN_TOKEN:-$(random_token 48)}"
WEBHOOK_SECRET="${YOOKASSA_WEBHOOK_SECRET:-$(random_token 48)}"
DB_PASSWORD_SQL="$(sql_escape "$DB_PASSWORD")"

echo "Installing XLX system for host: $PUBLIC_HOST"
echo "Panel port: $PANEL_PORT"

apt-get update
apt-get install -y apache2 libapache2-mod-php php php-mysql mariadb-server git ca-certificates curl

systemctl enable --now mariadb

mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD_SQL}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD_SQL}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

mysql "$DB_NAME" < "$REPO_ROOT/database/schema.sql"

PHP_PUBLIC_HOST="$(php_escape "$PUBLIC_HOST")"
PHP_PUBLIC_SCHEME="$(php_escape "$PUBLIC_SCHEME")"
PHP_DB_NAME="$(php_escape "$DB_NAME")"
PHP_DB_USER="$(php_escape "$DB_USER")"
PHP_DB_PASSWORD="$(php_escape "$DB_PASSWORD")"
PHP_REFLECTOR_NAME="$(php_escape "$REFLECTOR_NAME")"
PHP_SYSOP_CALLSIGN="$(php_escape "$SYSOP_CALLSIGN")"
PHP_SYSOP_EMAIL="$(php_escape "$SYSOP_EMAIL")"
PHP_COUNTRY="$(php_escape "$COUNTRY")"
PHP_ADMIN_TOKEN="$(php_escape "$ADMIN_TOKEN")"
PHP_YOOKASSA_SHOP_ID="$(php_escape "${YOOKASSA_SHOP_ID:-change_me}")"
PHP_YOOKASSA_SECRET_KEY="$(php_escape "${YOOKASSA_SECRET_KEY:-change_me}")"
PHP_YOOKASSA_RETURN_URL="$(php_escape "${YOOKASSA_RETURN_URL:-${BASE_URL}/payment/return}")"
PHP_WEBHOOK_SECRET="$(php_escape "$WEBHOOK_SECRET")"

cat > "$REPO_ROOT/config/config.php" <<PHP
<?php

declare(strict_types=1);

\$_SERVER['HTTP_HOST'] = \$_SERVER['HTTP_HOST'] ?? '${PHP_PUBLIC_HOST}';
putenv('XLX_PUBLIC_SCHEME=${PHP_PUBLIC_SCHEME}');
putenv('XLX_DB_HOST=127.0.0.1');
putenv('XLX_DB_PORT=3306');
putenv('XLX_DB_NAME=${PHP_DB_NAME}');
putenv('XLX_DB_USER=${PHP_DB_USER}');
putenv('XLX_DB_PASSWORD=${PHP_DB_PASSWORD}');
putenv('XLX_REFLECTOR_NAME=${PHP_REFLECTOR_NAME}');
putenv('XLX_SYSOP_CALLSIGN=${PHP_SYSOP_CALLSIGN}');
putenv('XLX_SYSOP_EMAIL=${PHP_SYSOP_EMAIL}');
putenv('XLX_COUNTRY=${PHP_COUNTRY}');
putenv('XLX_ADMIN_TOKEN=${PHP_ADMIN_TOKEN}');
putenv('XLX_PANEL_PORT=${PANEL_PORT}');
putenv('YOOKASSA_SHOP_ID=${PHP_YOOKASSA_SHOP_ID}');
putenv('YOOKASSA_SECRET_KEY=${PHP_YOOKASSA_SECRET_KEY}');
putenv('YOOKASSA_RETURN_URL=${PHP_YOOKASSA_RETURN_URL}');
putenv('YOOKASSA_WEBHOOK_SECRET=${PHP_WEBHOOK_SECRET}');

return require __DIR__ . '/config.example.php';
PHP

mkdir -p /etc/xlx
cat > /etc/xlx/xlxd.env <<EOF
XLX_REFLECTOR_NAME=${REFLECTOR_NAME}
XLX_SERVER_HOST=${PUBLIC_HOST}
XLX_SYSOP_CALLSIGN=${SYSOP_CALLSIGN}
XLX_SYSOP_EMAIL=${SYSOP_EMAIL}
XLX_COUNTRY=${COUNTRY}
XLX_DASHBOARD_PORT=8080
XLX_DMR_PORT=62030
XLX_YSF_PORT=42000
XLX_DEFAULT_MODULE=A
XLX_MODULES=ABCDEFGHIJKLMNOPQRSTUVWXYZ
XLX_INSTALL_PATH=/xlxd
XLX_SOURCE_PATH=/usr/src/xlxd
XLX_LOG_PATH=/var/log/xlxd.log
XLX_SERVICE_NAME=xlxd
XLX_REPO_URL=https://github.com/LX3JL/xlxd.git
EOF

cat > /etc/apache2/ports.conf <<EOF
Listen ${PANEL_PORT}
EOF

sed \
  -e "s#__PUBLIC_DIR__#${PUBLIC_DIR}#g" \
  -e "s#__PANEL_PORT__#${PANEL_PORT}#g" \
  "$REPO_ROOT/deploy/apache/xlx-panel.conf" > /etc/apache2/sites-available/xlx-panel.conf
a2dissite 000-default.conf >/dev/null 2>&1 || true
a2ensite xlx-panel.conf
systemctl restart apache2

echo "Building and installing xlxd reflector. This can take a few minutes..."
bash "$REPO_ROOT/scripts/install-xlxd.sh"

XLXD_START_STATUS="not started"
if command -v timeout >/dev/null 2>&1; then
  if timeout 20s systemctl start xlxd; then
    XLXD_START_STATUS="started"
  else
    XLXD_START_STATUS="start failed or timed out; run: sudo systemctl status xlxd --no-pager"
  fi
else
  if systemctl start xlxd; then
    XLXD_START_STATUS="started"
  else
    XLXD_START_STATUS="start failed; run: sudo systemctl status xlxd --no-pager"
  fi
fi

cat <<EOF

XLX system installed.

Site:
  ${BASE_URL}/

Admin:
  ${BASE_URL}/admin

Admin token:
  ${ADMIN_TOKEN}

YooKassa webhook:
  ${BASE_URL}/api/webhooks/yookassa?token=${WEBHOOK_SECRET}

XLX reflector:
  ${XLXD_START_STATUS}

Database:
  name=${DB_NAME}
  user=${DB_USER}
  password=${DB_PASSWORD}

If you later attach a domain, open /admin and change "Домен", or rerun with:
  sudo XLX_PUBLIC_HOST=xlx.your-domain.ru bash scripts/install-system.sh
EOF
