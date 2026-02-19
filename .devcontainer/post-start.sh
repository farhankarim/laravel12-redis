#!/usr/bin/env bash
set -euo pipefail

if sudo service mysql status >/dev/null 2>&1; then
  sudo service mysql start
elif sudo service mariadb status >/dev/null 2>&1; then
  sudo service mariadb start
else
  echo "MySQL/MariaDB service not found; run post-create first." >&2
  exit 1
fi

if sudo service redis-server status >/dev/null 2>&1; then
  sudo service redis-server start
elif sudo service redis status >/dev/null 2>&1; then
  sudo service redis start
else
  echo "Redis service not found; run post-create first." >&2
  exit 1
fi
