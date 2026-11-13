# SCK-MVP-003 — Instruksi Chat Baru Finalisasi (13 November 2026)

**CONTINUE EXISTING PROJECT — DO NOT RESTART.** Goal: functional MVP fully validated on real MySQL & HTTP, independent 35-case UAT, then VPS staging testing and owner-approved production only.

## Current canonical source
- App code package is **not yet in this repository**. ZIP prepared in conversation: `BUDAYAGO_SULBAR_CULTURE_KNOWLEDGE_MVP_v0.1_20261113.zip`; SHA256 `c846f277bb32679361f233653621c2bcac9c1f58703f8330b29303b8076cbbce`. Extract only into new path under `D:\PROJEK\BUDAYAGO`; do not overwrite local files.
- Existing branch: `development/sck-mvp-20261113` (Draft PR #3) contains docs/checkpoints, not the application's full source. Separate culture/rules audit: Draft PR #1.
- Verified as of 2026-11-13: ZIP integrity, PHP CLI unit test `19/19 PASS`. MySQL integration, HTTP smoke, 35 UAT, real cultural consent and VPS deployment are **not yet evidenced**.
- The candidate build is dated November 2026. It is **not** historical proof of functional software by the BUDAYA GO! competition's 31 March 2026 cutoff.

## Order of work
1. Inventory local workspace and Git SHA, compare ZIP and remote without destructive operations; preserve any untracked assets, history and original LICENSE attribution.
2. Review source PHP 8.2+/MySQL 8 security and cross-role workflows. Identify and remediate P0/P1 vulnerabilities: per-session login throttle, scope of MEMBERS access (currently all authenticated roles), child data retention, absence of tamper-evident audit, configuration/secrets protections.
3. Import source into development branch only after checking each file and policies; create traceable changes and CI verification.
4. Use **disposable isolated MySQL** and `tests/integration.php`, `tests/http_smoke.sh`. Record actual output, execution environment, timestamp, code SHA, and failure diagnoses.
5. Complete all `UAT-01` through `UAT-35` from ZIP `docs/UAT_MATRIX.md`: authentication, 5 roles, consent, approval/rejection/publication, revocation hiding content/lessons, learner quizzes, CSRF/XSS/SQLi, accessibility, backup/restore, community authorization and pilot checks. Mark PASS/FAIL/BLOCKED/NA based solely on evidence.
6. Prepare VPS **staging** in a separate hostname/database with HTTPS, PHP-FPM 8.2+, PDO MySQL, least-privilege DB user, private config and document root `public/`. Server identity, domain and hosting panel are **not yet confirmed**. Do not reuse HMS/other project hosting by assumption.
7. Test staged release and rollback. No genuine pupil records or restricted cultural content before legal authority and specific permission are checked.
8. Only after independent acceptance and owner GO, promote the exact tested release to segregated production with immutable SHA/backup and a verified rollback path.
9. Report as-is, gaps P0-P3, code diffs and rollback, actual tests, security evidence, and named owner gates at each checkpoint.

## Scope limitations
AI chat, multimedia ingestion, PWA/offline, multi-school isolation, payment and public sign-up are **not implemented** in MVP v0.1. Do not claim them as working; scope changes require explicit decision.

The full self-contained new-chat prompt was exported in this conversation as `BUDAYAGO_MASTER_CONTINUATION_SCK_FINALIZATION_20261113.md`. Attach that document and the original ZIP to the new chat when original conversation assets are not directly available.
