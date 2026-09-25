#!/bin/sh
set -eu

if [ "${APP_ENV:-}" != "production" ]; then
    echo "This image requires APP_ENV=production." >&2
    exit 1
fi

export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-}}"

if [ -z "${APP_KEY:-}" ] || [ -z "${APP_URL:-}" ] || [ -z "${DB_URL:-}" ] || [ -z "${MAIL_FROM_ADDRESS:-}" ]; then
    echo "Missing APP_KEY, APP_URL, DB_URL, or MAIL_FROM_ADDRESS." >&2
    exit 1
fi

if [ "${DB_CONNECTION:-}" != "pgsql" ] || [ "${MAIL_MAILER:-}" != "smtp" ] || [ "${APP_DEBUG:-}" != "false" ]; then
    echo "Production requires PostgreSQL, SMTP, and APP_DEBUG=false." >&2
    exit 1
fi

if [ "${QUEUE_CONNECTION:-}" != "sync" ] || [ "${SESSION_SECURE_COOKIE:-}" != "true" ]; then
    echo "Production requires synchronous jobs and secure session cookies on the free service." >&2
    exit 1
fi

case "$APP_URL" in
    https://*) ;;
    *) echo "Production APP_URL must use HTTPS." >&2; exit 1 ;;
esac

if [ -z "${MAIL_HOST:-}" ] || [ -z "${MAIL_PORT:-}" ] || [ -z "${MAIL_USERNAME:-}" ] || [ -z "${MAIL_PASSWORD:-}" ]; then
    echo "Missing SMTP host, port, username, or password." >&2
    exit 1
fi

php artisan migrate --force --no-interaction
php artisan optimize --no-interaction

exec frankenphp run --config /app/Caddyfile
