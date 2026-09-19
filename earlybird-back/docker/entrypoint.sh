#!/bin/sh
# Materializes the JWT key pair from env vars when the files in config/jwt/
# aren't already present in the image (e.g. on Render, where they're kept
# out of git entirely -- see DEPLOY.md section 5). Locally,
# docker-compose mounts the real files from disk and these env vars are
# unset, so this is a no-op there.
#
# Keys are passed base64-encoded (JWT_PRIVATE_KEY_PEM_B64 / JWT_PUBLIC_KEY_PEM_B64)
# because pasting a raw multi-line PEM into a plain env var field (e.g. Render's
# dashboard) is prone to silently losing its line breaks, which corrupts the key.
# Base64 collapses to one line naturally, so there's nothing to lose in transit.
set -e

if [ -n "$JWT_PRIVATE_KEY_PEM_B64" ] && [ ! -f config/jwt/private.pem ]; then
    mkdir -p config/jwt
    printf '%s' "$JWT_PRIVATE_KEY_PEM_B64" | base64 -d > config/jwt/private.pem
elif [ -n "$JWT_PRIVATE_KEY_PEM" ] && [ ! -f config/jwt/private.pem ]; then
    mkdir -p config/jwt
    printf '%s' "$JWT_PRIVATE_KEY_PEM" > config/jwt/private.pem
fi

if [ -n "$JWT_PUBLIC_KEY_PEM_B64" ] && [ ! -f config/jwt/public.pem ]; then
    mkdir -p config/jwt
    printf '%s' "$JWT_PUBLIC_KEY_PEM_B64" | base64 -d > config/jwt/public.pem
elif [ -n "$JWT_PUBLIC_KEY_PEM" ] && [ ! -f config/jwt/public.pem ]; then
    mkdir -p config/jwt
    printf '%s' "$JWT_PUBLIC_KEY_PEM" > config/jwt/public.pem
fi

exec "$@"
