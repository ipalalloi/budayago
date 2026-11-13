# SCK-MVP-004 — Source Reconciliation, Technical Audit & MySQL Integration Gate
Date: 2026-11-13 (Asia/Makassar)
Task: SCK-004
Status: **HOLD FOR STAGING / NO-GO FOR PRODUCTION**

## 1. Verified remote facts
- Repository: `ipalalloi/budayago` (public).
- `main` prior/current reference: `06b383f11896af7f7717c6babfeb1565265bd383` (no application code).
- `development/sck-mvp-20261113` audited before this report at `93c6e2940e6c2746ddeeea3d196424a826c8fce7`: LICENSE, README.md, and SCK_MVP_001/002/003 checkpoint documents only. No individual PHP sources, test files, schema, or CI workflow are committed.
- Draft PR #1: OPEN, unmerged, `review/budayago-eligibility-20261113`. Contains separate eligibility/culture governance audit, provisional .gitignore and read-only audit fetch script.
- Draft PR #3: OPEN, unmerged, `development/sck-mvp-20261113`. Source archive is explicitly excluded from its changes.
- GitHub Actions workflow runs: 0 returned during SCK-004 check. This is not a CI PASS.
- Original repository LICENSE identifies 2026 IzandBox as copyright holder. Ownership and cultural content permissions are separate questions; do not overwrite licensing metadata without provenance.

## 2. Source archive and execution environment
Expected original ZIP: `BUDAYAGO_SULBAR_CULTURE_KNOWLEDGE_MVP_v0.1_20261113.zip`
Expected SHA-256: `c846f277bb32679361f233653621c2bcac9c1f58703f8330b29303b8076cbbce`.

The original ZIP was **not available in this execution workspace**. Search of currently accessible conversation/library files did not retrieve it. Prior checksum and 19/19 unit PASS are checkpoint records, not fresh rerun evidence. Local Windows workspace `D:\PROJEK\BUDAYAGO` cannot be read by this execution environment.

Runtime inspection here found PHP CLI 8.4.24 with PDO but no `pdo_mysql` extension, no mysql client, and no Docker. Consequently the real MySQL integration test cannot be run on source or in this runtime. **Do not treat this as application test failure**: tests were not run.

## 3. Technical audit status
| Area | State | Evidence/remaining work |
| --- | --- | --- |
| GitHub provenance | VERIFIED (remote metadata only) | Branch tree and PR metadata observed |
| Original source SHA | HISTORICAL ONLY | Obtain exact ZIP bytes and recompute checksum |
| Static source audit & patch | BLOCKED | PHP/source absent; no code-level diagnosis or diff |
| PHP unit tests | HISTORICAL PASS 19/19 | No rerun against a new commit |
| MySQL 8 integration | BLOCKED | Source, schema, test DB and driver unavailable |
| HTTP smoke | BLOCKED | Source, target and staging URL unverified |
| UAT-01–35 | BLOCKED (35/35) | Original matrix unavailable; none executed here |
| Security testing | NOT_EXECUTED | Needs source and test environment |
| Independent cultural validation | NOT_VERIFIED | Human authorization & scope evidence needed |
| Staging VPS | HOLD | Target not identified/approved or tested |
| Production | NO-GO | All release gates unmet |

Risks already flagged in SCK-MVP-003 are investigation candidates (not reproduced vulnerabilities): session-only login throttling, broad MEMBERS access scope, protection and retention of student data, audit log tamper-resistance. Add SQLi/XSS/CSRF, server-side authorization, per-purpose consent, revocation, transaction integrity, and backup/restore to code and UAT review.

## 4. Safe next execution
1. On Windows, inventory project folder and Git status without checkout, pull, clean, reset or overwrites.
2. Retrieve original source ZIP from earlier conversation/local storage; require exact hash match.
3. Inspect contents and extract into a NEW `incoming/` subdirectory. Never import through blind overwrite.
4. Review application, tests, schema, config and `docs/UAT_MATRIX.md`. Build a source-file inventory and secrets/license review.
5. Import approved individual files on a **new local import branch derived from development** after preserving local changes; prepare reviewed PR/diff to the development branch, not `main`.
6. Use a dedicated disposable MySQL 8 DB, least-privilege user and test-config guard. Run actual integration/HTTP/role checks, then 35 UAT cases with timestamp/commit/tool versions and logs. Never test against live DB.
7. Confirm staging VPS owner, isolate DNS/docroot/DB and synthetic fixtures; prove HTTPS, rollback and restore. Promote only after independent cultural validation and owner GO.

### Local SCK-004 execution kit
Separate downloadable operational kit produced during this checkpoint contains `SCK004_SAFE_RECONCILE.ps1`, `SCK004_EXECUTION_GUIDE.md`, `SCK004_UAT_EVIDENCE_TEMPLATE.csv`, `SCK004_STATUS_LEDGER.json` and `SHA256SUMS.txt`. The kit itself is **not** the source archive or a deployable application. Its PowerShell script has not been executed on a Windows machine.

## 5. Decision
**HOLD staging; NO-GO production.** This checkpoint records verified repository state, evidence gaps and safe next steps; it does not assert that application or MySQL testing passed. Do not infer historical BUDAYA GO! 2026 eligibility from the November 2026 source.
