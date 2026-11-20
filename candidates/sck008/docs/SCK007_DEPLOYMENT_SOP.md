# Local UAT → VPS staging (requires approval)

1. Preserve SCK-006 and `sck_ci`/`sck_uat` unchanged; keep existing Windows UI006 backup.
2. For testing the SCK-007 code in Windows, copy the reviewed HTTP launcher. Change its candidate path to the SCK-007 successor and set `LOGIN_RATE_LIMIT_DIR` in that launcher only to a private pre-existing directory, with ACL limited to current Windows account. Do not edit or print DPAPI values.
3. Create the private directory with controlled ACL and verify PHP has write access BEFORE running HTTP. Keep the existing marker verifier, loopback 3316 and 8086 safeguards.
4. Run PHP syntax checks, unit/private-ref/guard tests and new throttle tests. Reconcile the exact source SHA with the final manifest.
5. For VPS staging, docroot must point ONLY to `public/`, PHP-FPM worker must read application code, use a least-privilege DB user, and set `APP_ORIGIN=https://...`, `FORCE_HTTPS=true`, `LOGIN_RATE_LIMIT_DIR` outside docroot.
6. Verify HTTPS and proxy PHP HTTPS detection without trusting arbitrary forwarded headers; test Secure/HttpOnly/SameSite cookies. Use WAF or trusted proxy client-IP configuration when appropriate.
7. Backup database before deployment. Rollback = restore previous code, not destructive SQL. Restore database only after explicit migration/incident decision.
8. Use staging with access restriction until all open gates and owner acceptance pass; no real cultural materials/students without authorization.
