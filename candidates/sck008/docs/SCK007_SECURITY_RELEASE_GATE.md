# BUDAYAGO SCK-007 security release gate — 18 November 2026

Status: **SOURCE CANDIDATE; NOT PRODUCTION AUTHORIZED**.

## Scoped change
- Added `app/LoginThrottle.php`, a shared, file-backed authentication failure limiter (5 failures / 15 minutes per IP and per email).
- `public/index.php` calls it for login; unrelated knowledge and lesson workflows unchanged.
- Added isolated CLI unit tests for cross-session/IP/email behavior. No MySQL connections are used by these tests.

## Mandatory configuration before ANY login
- Set `LOGIN_RATE_LIMIT_DIR` to a writable **private directory outside `public/`**, e.g. `D:\DB_SANDBOX\BUDAYAGO_SCK007_LOGIN_THROTTLE` for local UAT. Create with restricted Windows ACL, never expose over HTTP or bundle its contents in the ZIP.
- For VPS: use a private directory owned/writable by the PHP-FPM user and unavailable through Nginx (e.g. `/var/lib/budayago/login-throttle`). Set PHP-FPM environment variable `LOGIN_RATE_LIMIT_DIR` or return an equivalent key from a private `config.php` outside the web root. Test access permissions.
- **Single-server only**: a multi-node deployment requires shared Redis/DB/WAF throttling; do not rely on local files across hosts.
- On a misconfigured path, authentication fails closed. Failures may be presented with a generic application message.
- Source IP comes from `REMOTE_ADDR`. Behind a reverse proxy, this may be proxy IP, causing shared limits for all users; configure trusted real-IP at web server level ONLY after validating trusted proxy headers. Do not trust X-Forwarded-For directly in PHP.

## Still required, no synthetic PASS
1. Real PHP/MySQL local UAT with the configured private directory, using a **copy** of the SCK-006 launcher pointing at SCK-007 successor and a reviewed explicit env variable.
2. Browser smoke: admin/student/holder; positive and negative login; verify rate limiting across fresh sessions, lock duration and no credentials in logs. Do NOT lock real accounts; synthetic UAT only.
3. Complete UAT-25 correct answer, UAT-26 actual HTML for active synthetic lesson, UAT-28 rejected authenticated CSRF POST for revoked lesson with attempts unchanged.
4. Audit production reverse proxy TLS, Secure/SameSite cookie, rate limiting (WAF optional), backups & tested restore, CSP, rollback and owner approval.
5. Before broader public exposure: independent security review and rights-holder consent checks. No real student accounts in the demo until authorized.

## Existing provenance
Prior CI & UAT outputs supplied in chat belong to prior SCK-006 candidate. They are historical evidence; **do not label this new SCK-007 source as runtime tested** until actual UAT is executed.
