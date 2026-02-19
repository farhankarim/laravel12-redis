#!/usr/bin/env bash
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive

if ! command -v mysql >/dev/null 2>&1; then
  sudo apt-get update
  sudo apt-get install -y default-mysql-server default-mysql-client
fi

if ! command -v redis-server >/dev/null 2>&1; then
  sudo apt-get update
  sudo apt-get install -y redis-server redis-tools
fi

if ! php -m | grep -qi pdo_mysql; then
  if command -v docker-php-ext-install >/dev/null 2>&1; then
    sudo docker-php-ext-install pdo_mysql
    sudo mkdir -p /usr/local/etc/php/conf.d
    echo 'extension=pdo_mysql' | sudo tee /usr/local/etc/php/conf.d/docker-php-ext-pdo_mysql.ini >/dev/null
  else
    sudo apt-get update
    sudo apt-get install -y php-mysql || sudo apt-get install -y php8.3-mysql || sudo apt-get install -y php8.2-mysql
  fi
fi

if sudo service mysql status >/dev/null 2>&1; then
  sudo service mysql start
elif sudo service mariadb status >/dev/null 2>&1; then
  sudo service mariadb start
else
  echo "MySQL/MariaDB service not found" >&2
  exit 1
fi

if sudo service redis-server status >/dev/null 2>&1; then
  sudo service redis-server start
elif sudo service redis status >/dev/null 2>&1; then
  sudo service redis start
else
  echo "Redis service not found" >&2
  exit 1
fi

DB_NAME="laravel"
DB_USER="laravel"
DB_PASSWORD="laravel"

sudo mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
DROP USER IF EXISTS '${DB_USER}'@'localhost';
DROP USER IF EXISTS '${DB_USER}'@'127.0.0.1';
CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
CREATE USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

if [ ! -f .env ]; then
  cp .env.example .env
fi

php artisan key:generate --force
php artisan migrate --force
