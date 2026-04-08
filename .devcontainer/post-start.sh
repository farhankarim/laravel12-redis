#!/usr/bin/env bash
set -euo pipefail

if [ -f /etc/init.d/mysql ]; then
  sudo service mysql start >/dev/null 2>&1 || true
elif [ -f /etc/init.d/mariadb ]; then
  sudo service mariadb start >/dev/null 2>&1 || true
else
  echo "MySQL/MariaDB service not found; skipping." >&2
fi

if [ -f /etc/init.d/redis-server ]; then
  sudo service redis-server start >/dev/null 2>&1 || true
elif [ -f /etc/init.d/redis ]; then
  sudo service redis start >/dev/null 2>&1 || true
else
  echo "Redis service not found; skipping." >&2
fi

echo "Services startup check complete."
