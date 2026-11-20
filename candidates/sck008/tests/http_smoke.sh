#!/usr/bin/env bash
set -euo pipefail
HOST="${1:-http://127.0.0.1:8765}"
JAR="$(mktemp)"; PAGE="$(mktemp)"; HDR="$(mktemp)"
trap 'rm -f "$JAR" "$PAGE" "$HDR"' EXIT
curl --fail --silent --show-error "$HOST/index.php?p=home" -o "$PAGE"
grep -q 'Sulbar Culture Knowledge' "$PAGE"
echo 'PASS homepage HTTP 200'
curl --fail --silent --show-error -c "$JAR" -b "$JAR" "$HOST/index.php?p=login" -o "$PAGE"
TOKEN="$(sed -n 's/.*name="csrf" value="\([^"]*\)".*/\1/p' "$PAGE" | head -1)"
test -n "$TOKEN"
echo 'PASS login form exposes CSRF token'
CSRF_STATUS="$(curl --silent -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" -X POST "$HOST/index.php?p=login" --data 'action=login&email=admin%40example.test&password=bad')"
test "$CSRF_STATUS" = '403'
echo 'PASS missing CSRF blocked with 403'
LOGIN_STATUS="$(curl --silent -D "$HDR" -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" --data-urlencode 'action=login' --data-urlencode "csrf=$TOKEN" --data-urlencode "email=${ADMIN_EMAIL:-admin@example.test}" --data-urlencode "password=${ADMIN_PASSWORD:-Test-Admin-Password-2026}" "$HOST/index.php?p=login")"
test "$LOGIN_STATUS" = '303'
curl --fail --silent --show-error -b "$JAR" "$HOST/index.php?p=users" -o "$PAGE"
grep -q 'Kelola pengguna' "$PAGE"
echo 'PASS login and admin user management HTTP 200'
# An authenticated admin cannot impersonate the designated knowledge-holder.
if grep -q 'Persetujuan dan pencabutan' "$PAGE"; then echo 'FAIL admin role separation' >&2; exit 1; fi
echo 'PASS admin view is role separated'
