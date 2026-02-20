#!/usr/bin/env bash
set -euo pipefail

if sudo service mysql status >/dev/null 2>&1; then
  sudo service mysql start >/dev/null 2>&1 || true
elif sudo service mariadb status >/dev/null 2>&1; then
  sudo service mariadb start >/dev/null 2>&1 || true
else
  echo "MySQL/MariaDB service not found; skipping." >&2
fi

if sudo service redis-server status >/dev/null 2>&1; then
  sudo service redis-server start >/dev/null 2>&1 || true
elif sudo service redis status >/dev/null 2>&1; then
  sudo service redis start >/dev/null 2>&1 || true
else
  echo "Redis service not found; skipping." >&2
fi

echo "Services startup check complete."
