#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
  echo "Run as root: sudo bash scripts/install-xlxd.sh"
  exit 1
fi

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_TARGET="/etc/xlx/xlxd.env"

mkdir -p /etc/xlx
if [[ ! -f "$ENV_TARGET" ]]; then
  cp "$REPO_ROOT/config/xlxd.env.example" "$ENV_TARGET"
  echo "Created $ENV_TARGET. Edit it before production use."
fi

# shellcheck disable=SC1090
source "$ENV_TARGET"

XLX_REPO_URL="${XLX_REPO_URL:-https://github.com/LX3JL/xlxd.git}"
XLX_SOURCE_PATH="${XLX_SOURCE_PATH:-/usr/src/xlxd}"
XLX_INSTALL_PATH="${XLX_INSTALL_PATH:-/xlxd}"
XLX_SERVICE_NAME="${XLX_SERVICE_NAME:-xlxd}"

apt-get update
apt-get install -y git build-essential make g++ apache2 php libapache2-mod-php wget ca-certificates

if [[ -d "$XLX_SOURCE_PATH/.git" ]]; then
  git -C "$XLX_SOURCE_PATH" pull --ff-only
else
  rm -rf "$XLX_SOURCE_PATH"
  git clone "$XLX_REPO_URL" "$XLX_SOURCE_PATH"
fi

make -C "$XLX_SOURCE_PATH/src" clean
make -C "$XLX_SOURCE_PATH/src"
make -C "$XLX_SOURCE_PATH/src" install

mkdir -p "$XLX_INSTALL_PATH"
if [[ -x "$XLX_SOURCE_PATH/src/xlxd" ]]; then
  install -m 0755 "$XLX_SOURCE_PATH/src/xlxd" "$XLX_INSTALL_PATH/xlxd"
fi

if [[ -d "$XLX_SOURCE_PATH/dashboard" ]]; then
  rm -rf /var/www/html/xlxd
  cp -R "$XLX_SOURCE_PATH/dashboard" /var/www/html/xlxd
  chown -R www-data:www-data /var/www/html/xlxd
fi

install -m 0755 "$REPO_ROOT/scripts/xlx-control" /usr/local/sbin/xlx-control
install -m 0644 "$REPO_ROOT/deploy/systemd/xlxd.service" "/etc/systemd/system/${XLX_SERVICE_NAME}.service"

/usr/local/sbin/xlx-control render-modules
systemctl daemon-reload
systemctl enable "${XLX_SERVICE_NAME}.service"

cat <<EOF
XLX reflector installed.

Next commands:
  sudo systemctl start ${XLX_SERVICE_NAME}
  sudo systemctl status ${XLX_SERVICE_NAME}
  sudo tail -f ${XLX_LOG_PATH:-/var/log/xlxd.log}

Open firewall/NAT for at least UDP ${XLX_DMR_PORT:-62030} for Pi-Star MMDVM.
EOF
