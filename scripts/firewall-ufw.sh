#!/usr/bin/env bash
set -euo pipefail

ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 8880/udp
ufw allow 10001/udp
ufw allow 10002/udp
ufw allow 30001/udp
ufw allow 30051/udp
ufw allow 42000/udp
ufw allow 62030/udp

ufw status verbose
