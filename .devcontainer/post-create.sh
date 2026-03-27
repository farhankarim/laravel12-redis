#!/usr/bin/env bash
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive

# ── Install MongoDB ────────────────────────────────────────────────────────────
if ! command -v mongod >/dev/null 2>&1; then
  sudo apt-get update
  sudo apt-get install -y gnupg curl
  curl -fsSL https://www.mongodb.org/static/pgp/server-7.0.asc | \
    sudo gpg -o /usr/share/keyrings/mongodb-server-7.0.gpg --dearmor
  echo "deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-7.0.gpg ] \
    https://repo.mongodb.org/apt/ubuntu jammy/mongodb-org/7.0 multiverse" | \
    sudo tee /etc/apt/sources.list.d/mongodb-org-7.0.list
  sudo apt-get update
  sudo apt-get install -y mongodb-org
fi

# ── Install Redis ──────────────────────────────────────────────────────────────
if ! command -v redis-server >/dev/null 2>&1; then
  sudo apt-get update
  sudo apt-get install -y redis-server redis-tools
fi

# ── Start services ─────────────────────────────────────────────────────────────
sudo mkdir -p /data/db
sudo mongod --fork --logpath /tmp/mongod.log --dbpath /data/db || true
sudo service redis-server start || sudo service redis start || true

# ── Install Node dependencies ──────────────────────────────────────────────────
npm install

# ── Copy .env if not present ───────────────────────────────────────────────────
if [ ! -f .env ]; then
  cp .env.example .env
  echo ""
  echo "⚠️  .env created from .env.example."
  echo "   Please edit .env and set JWT_SECRET, MAIL_HOST, MAIL_USER, MAIL_PASS, etc."
fi

echo ""
echo "✅ Setup complete! Run 'npm run start:dev' to start the application."
