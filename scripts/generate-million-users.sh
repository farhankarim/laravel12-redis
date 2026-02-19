#!/usr/bin/env bash
set -euo pipefail

TOTAL="${TOTAL_USERS:-1000000}"
CHUNK="${CHUNK_SIZE:-1000}"
QUEUE_NAME="${QUEUE_NAME:-user-imports}"
QUEUE_CONNECTION="${QUEUE_CONNECTION:-redis}"
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

php artisan users:queue-generate \
  --total="${TOTAL}" \
  --chunk="${CHUNK}" \
  --connection="${QUEUE_CONNECTION}" \
  --queue="${QUEUE_NAME}"

if [ "${WORKERS}" -eq 1 ]; then
  php artisan queue:work "${QUEUE_CONNECTION}" \
    --queue="${QUEUE_NAME}" \
    --sleep=1 \
    --tries=1 \
    --timeout=120 \
    --stop-when-empty
  exit 0
fi

echo "Starting ${WORKERS} queue workers..."

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
