# SCK-004 — Windows preflight v2 evidence update (2026-11-13)

**Evidence class:** User-provided PowerShell transcript, not independently executed on the model's Windows environment.

## Preflight v2 observed output
- timestamp_utc: `2026-11-13T13:23:34.9792358Z`
- workspace: `D:\PROJEK\BUDAYAGO`
- workspace_exists: `true`
- git_repository: `false`
- nested_git_candidates: `[]`
- allowed_origin: `false` (not a mismatch; no root Git repository to check)
- local_head/current_branch: `null`
- candidate_zip_present: `null`
- archive_path_evaluated: `false` (**ZIP path not supplied; absence NOT demonstrated**)
- zip_verified: `null`
- php_version: `8.2.12`
- php_pdo_mysql: `true`
- php_modules_exit_code: `0`
- mysql_cli_available: `true` (client presence only, not DB server connectivity)
- integration_result: `NOT_RUN`
- uat_result: `BLOCKED`

The PHP extension check is now conclusive for the **CLI** environment; PHP-FPM/web SAPI extension parity is not checked. Do not conflate installed MySQL CLI with running MySQL server.

## Next gates
1. Discover the original November 13 MVP archive in local workspace/Downloads, compute SHA-256, and compare with canonical `c846f277bb32679361f233653621c2bcac9c1f58703f8330b29303b8076cbbce`. Do not extract unless exact match.
2. List ZIP contents safely; inspect PHP source, `tests/integration.php`, schema, `docs/UAT_MATRIX.md`, configuration and secrets before importing.
3. Protect original `D:\PROJEK\BUDAYAGO` contents. If Git checkout is needed, clone into a **new empty sibling directory** after verifying no collision; do not initialize existing workspace or overwrite the SCK-004 tools. Keep development branch, never merge to main without release gates.
4. Verify dedicated disposable MySQL 8 test DB and least-privilege credentials via secure local environment variable/file mechanism; no secrets in chat/logs/Git. Run integration only after reviewing test side effects and DB target.
5. Retest HTTP, RBAC, per-purpose permissions, revocation, student access and UAT-01–35 with timestamped logs. VPS staging remains HOLD; production NO-GO.

## Decision
**SCK-004 PRECHECK PASS (local CLI prerequisites)**; **SOURCE GATE BLOCKED**; **INTEGRATION NOT RUN**; **UAT BLOCKED**; **STAGING HOLD**; **PRODUCTION NO-GO**. No source patch or release is claimed.
