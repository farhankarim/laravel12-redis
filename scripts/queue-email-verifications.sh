#!/usr/bin/env bash
set -euo pipefail

CHUNK="${CHUNK_SIZE:-1000}"
QUEUE_NAME="${QUEUE_NAME:-email-verifications}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-redis}"
ID_COLUMN="${ID_COLUMN:-user_id}"
EMAIL_COLUMN="${EMAIL_COLUMN:-email_address}"
EMAIL_LIKE="${EMAIL_LIKE:-}"
LIMIT_USERS="${LIMIT_USERS:-0}"
ONLY_UNVERIFIED="${ONLY_UNVERIFIED:-0}"
WORKERS="${WORKERS:-4}"

if ! [[ "${WORKERS}" =~ ^[0-9]+$ ]] || [ "${WORKERS}" -lt 1 ]; then
  echo "WORKERS must be a positive integer"
  exit 1
fi

sudo service mariadb start >/dev/null 2>&1 || true
sudo service mysql start >/dev/null 2>&1 || true
sudo service redis-server start >/dev/null 2>&1 || true
sudo service redis start >/dev/null 2>&1 || true

php artisan config:clear >/dev/null

php artisan users:queue-email-verifications \
  --chunk="${CHUNK}" \
  --connection="${QUEUE_CONNECTION}" \
  --queue="${QUEUE_NAME}" \
  --id-column="${ID_COLUMN}" \
  --email-column="${EMAIL_COLUMN}" \
  --email-like="${EMAIL_LIKE}" \
  --limit="${LIMIT_USERS}" \
  --only-unverified="${ONLY_UNVERIFIED}"

if [ "${WORKERS}" -eq 1 ]; then
  php artisan queue:work "${QUEUE_CONNECTION}" \
    --queue="${QUEUE_NAME}" \
    --sleep=1 \
    --tries=1 \
    --timeout=120 \
    --stop-when-empty
  exit 0
fi

echo "Starting ${WORKERS} verification workers..."

pids=()

cleanup() {
  for pid in "${pids[@]:-}"; do
    kill "${pid}" >/dev/null 2>&1 || true
  done
}

trap cleanup EXIT INT TERM

for worker in $(seq 1 "${WORKERS}"); do
  php artisan queue:work "${QUEUE_CONNECTION}" \
    --queue="${QUEUE_NAME}" \
    --sleep=1 \
    --tries=1 \
    --timeout=120 \
    --stop-when-empty &

  pids+=("$!")
  echo "Worker ${worker} started (pid ${pids[-1]})."
done

status=0
for pid in "${pids[@]}"; do
  if ! wait "${pid}"; then
    status=1
  fi
done

exit "${status}"
