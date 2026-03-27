#!/usr/bin/env bash
set -euo pipefail

# ── Start MongoDB ──────────────────────────────────────────────────────────────
if command -v mongod >/dev/null 2>&1; then
  sudo mkdir -p /data/db
  sudo mongod --fork --logpath /tmp/mongod.log --dbpath /data/db 2>/dev/null || true
fi

# ── Start Redis ────────────────────────────────────────────────────────────────
if sudo service redis-server status >/dev/null 2>&1; then
  sudo service redis-server start 2>/dev/null || true
elif sudo service redis status >/dev/null 2>&1; then
  sudo service redis start 2>/dev/null || true
fi

echo "Services startup check complete."
