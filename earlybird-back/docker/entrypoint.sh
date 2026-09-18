#!/bin/sh
# Materializes the JWT key pair from env vars when the files in config/jwt/
# aren't already present in the image (e.g. on Render, where they're no
# longer committed to git for security reasons -- see DEPLOY.md). Locally,
# docker-compose mounts the real files from disk and these env vars are
# unset, so this is a no-op there.
set -e

if [ -n "$JWT_PRIVATE_KEY_PEM" ] && [ ! -f config/jwt/private.pem ]; then
    mkdir -p config/jwt
    printf '%s' "$JWT_PRIVATE_KEY_PEM" > config/jwt/private.pem
fi

if [ -n "$JWT_PUBLIC_KEY_PEM" ] && [ ! -f config/jwt/public.pem ]; then
    mkdir -p config/jwt
    printf '%s' "$JWT_PUBLIC_KEY_PEM" > config/jwt/public.pem
fi

exec "$@"
