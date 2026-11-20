#!/usr/bin/env bash
# Read only. Run as the staging site user from the unpacked release.
set -eu
if (( $# != 2 )); then echo "Usage: bash ops/preflight.sh staging.example.org /absolute/release/public" >&2; exit 2; fi
domain=$1
docroot=$2
[[ "$domain" =~ ^[[:alnum:]][[:alnum:].-]+$ && "$domain" == *.* && "$docroot" == /*/public ]] || exit 2
[[ -d "$docroot" && -f "$docroot/heritage.php" ]] || exit 2
printf 'Host inventory for %s UTC=%s\n' "$domain" "$(date -u +%FT%TZ)"
awk -F= '$1=="PRETTY_NAME"{print}' /etc/os-release
df -h "$docroot"
df -i "$docroot"
stat -c '%a %U:%G %n' "$docroot" "$(dirname "$docroot")"
command -v quota >/dev/null && quota -s || true
command -v clpctl >/dev/null && echo 'clpctl available; verify installed version in CloudPanel' || true
for cmd in nginx mariadb mysql ssh sshd ufw; do command -v "$cmd" || true; done
command -v nginx >/dev/null && nginx -v || true
getent ahosts "$domain" | sort -u
php -r 'echo "CLI ",PHP_VERSION,PHP_EOL; foreach(["pdo_mysql","fileinfo","mbstring","session"] as $x){echo $x,"=",extension_loaded($x)?"yes":"MISSING",PHP_EOL;if(!extension_loaded($x))$bad=true;} foreach(["upload_max_filesize","post_max_size","memory_limit"] as $x) echo $x,"=",ini_get($x),PHP_EOL; exit(!empty($bad)?1:0);'
for path in / /manifest.webmanifest /sw.js; do
  curl --proto '=https' --max-time 15 --silent --show-error --output /dev/null --write-out '%{http_code} %{content_type} TLS_verify=%{ssl_verify_result}\n' "https://$domain$path"
done
echo 'Complete the FPM, database, DNS, firewall, backup and rollback rows in ops/CLOUDPANEL.md with actual evidence.'
