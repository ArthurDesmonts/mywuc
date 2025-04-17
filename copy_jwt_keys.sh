#!/bin/sh

mkdir -p /app/config/jwt

echo "$JWT_PRIVATE_B64" | base64 -d > /app/config/jwt/private.pem
echo "$JWT_PUBLIC_B64" | base64 -d > /app/config/jwt/public.pem
chmod 600 /app/config/jwt/private.pem /app/config/jwt/public.pem
