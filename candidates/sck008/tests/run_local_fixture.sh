#!/bin/bash
set -euo pipefail
umask 077
app=$(cd "$(dirname "$0")/.." && pwd)
[[ ! -e "$app/config.php" ]] || { echo 'Use a clean copy without config.php' >&2; exit 2; }
root=${MARIADB_BASE:-/usr}
work=$(mktemp -d)
report=$work/evidence
mkdir "$report"
echo "Private fixture and evidence directory: $work"
export DB_DSN='mysql:host=127.0.0.1;port=33781;dbname=sck008_fixture;charset=utf8mb4'
export DB_USER=fixture
DB_PASS=$(python3 -c 'import secrets;print(secrets.token_hex(24))')
export DB_PASS MYSQL_PWD="$DB_PASS" FORCE_HTTPS=false HERITAGE_PUBLICATION_ENABLED=true
python3 - <<'GUARD'
import socket
for port in (33781,18768):
    with socket.socket() as test:
        test.bind(('127.0.0.1',port))
GUARD
"$root/bin/mariadb-install-db" --no-defaults --basedir="$root" --datadir="$work/db" --auth-root-authentication-method=socket --skip-test-db >"$work/init.log" 2>&1
cat > "$work/init.sql" <<SQL
CREATE DATABASE sck008_fixture CHARACTER SET utf8mb4;
CREATE USER 'fixture'@'127.0.0.1' IDENTIFIED BY '$DB_PASS';
GRANT ALL ON sck008_fixture.* TO 'fixture'@'127.0.0.1';
SQL
echo "CREATE DATABASE sck008_restore CHARACTER SET utf8mb4;" >> "$work/init.sql"
echo "GRANT ALL ON sck008_restore.* TO 'fixture'@'127.0.0.1';" >> "$work/init.sql"
"$root/sbin/mariadbd" --no-defaults --basedir="$root" --datadir="$work/db" --bind-address=127.0.0.1 --port=33781 --socket= --init-file="$work/init.sql" --pid-file="$work/db.pid" --log-error="$work/db.log" >"$work/start.log" 2>&1 &
db_pid=$!
trap 'kill "$db_pid" "${web_pid:-}" 2>/dev/null || :; wait || :' EXIT
db=("$root/bin/mariadb" --no-defaults --protocol=tcp -h127.0.0.1 -P33781 -ufixture)
for i in {1..90}; do "${db[@]}" sck008_fixture -Nse 'SELECT 1' >/dev/null 2>&1 && break; sleep .2; done
kill -0 "$db_pid"
actual=$("${db[@]}" sck008_fixture -Nse 'SELECT @@datadir')
[[ "$(realpath "$actual")" == "$(realpath "$work/db")" ]] || exit 2
export HERITAGE_MEDIA_DIR="$work/media" LOGIN_RATE_LIMIT_DIR="$work/throttle"
mkdir -m 700 "$work/media" "$work/throttle"
for f in mysql.sql 20261119_heritage_additive.sql 20261120_heritage_rights.sql 20261119_heritage_additive.sql 20261120_heritage_rights.sql; do "${db[@]}" sck008_fixture < "$app/database/$f"; done
"${db[@]}" sck008_fixture -Nse 'SELECT DATABASE(),VERSION(); SHOW TABLES; SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE();' > "$report/schema.txt"
export ADMIN_EMAIL=admin@example.test ADMIN_PASSWORD=$(python3 -c 'import secrets;print(secrets.token_hex(20))')
php "$app/bin/install-admin.php"
if php "$app/bin/install-admin.php" >/dev/null 2>&1; then exit 4; fi
php "$app/tests/sck008_fixture.php" | tee "$report/db-constraint.txt"
php "$app/ops/db_inventory.php" sck008_fixture > "$report/schema-detail.txt"
php -d upload_max_filesize=12M -d post_max_size=14M -d display_errors=0 -S 127.0.0.1:18768 -t "$app/public" >"$work/http.log" 2>&1 &
web_pid=$!
for i in {1..30}; do curl --noproxy '*' -fsS http://127.0.0.1:18768/heritage.php -o /dev/null 2>/dev/null && break; sleep .2; done
export SCK008_URL=http://127.0.0.1:18768
bash "$app/tests/http_smoke.sh" "$SCK008_URL" > "$report/legacy-smoke.txt"
python3 "$app/tests/sck008_http.py" | tee "$report/http-tests.txt"
"$root/bin/mariadb-dump" --no-defaults -h127.0.0.1 -P33781 -ufixture --single-transaction --skip-lock-tables sck008_fixture > "$work/backup.sql"
"${db[@]}" sck008_restore < "$work/backup.sql"
for schema in sck008_fixture sck008_restore; do
 "${db[@]}" "$schema" -Nse 'SELECT COUNT(*) FROM audit_events; SELECT COUNT(*) FROM heritage_rights; SELECT COUNT(*) FROM heritage_asset_grants; SELECT COUNT(*) FROM attempts; SELECT COUNT(*) FROM heritage_collections; SELECT COUNT(*) FROM heritage_assets;' > "$work/$schema.counts"
 "$root/bin/mariadb-dump" --no-defaults -h127.0.0.1 -P33781 -ufixture --no-create-info --skip-comments --order-by-primary --skip-lock-tables "$schema" > "$work/$schema.rows"
done
cmp "$work/sck008_fixture.rows" "$work/sck008_restore.rows"
cp -a "$work/media" "$work/restore-media"
diff -qr "$work/media" "$work/restore-media"
echo 'PASS dump/restoration on distinct disposable schema: ordered SQL row data identical; private fixture media copies identical.' > "$report/restore.txt"
if grep -E 'Fatal error|Uncaught|PHP Warning' "$work/http.log"; then exit 5; fi
python3 "$app/tests/sck008_browser.py" | tee "$report/browser-tests.txt"
echo "PASS fixture final checks. Evidence: $report"
