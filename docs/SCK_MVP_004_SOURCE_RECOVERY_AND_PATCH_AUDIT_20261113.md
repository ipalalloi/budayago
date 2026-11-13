# SCK-MVP-004 — Original Source Recovery and Candidate Patch Audit (2026-11-13)

## Verified original source

An original MVP ZIP was supplied as a ChatGPT attachment in the SCK-004 continuation. ZIP SHA-256 **matches** checkpoint SCK-MVP-003:

`c846f277bb32679361f233653621c2bcac9c1f58703f8330b29303b8076cbbce`.

- ZIP integrity check: PASS; 19 entries.
- All 18 internal manifest SHA-256 entries: PASS.
- Source archive contains 8 PHP files, schema, unit, integration, HTTP smoke, CI YAML, UAT matrix and documentation.
- No real cultural content or credentials are intentionally included; public GitHub import still requires an independent private-data/license review.
- Source code is **not yet committed to this development branch**. This record is audit evidence, not a source import.

## Test execution evidence

| Gate | Actual observation | Status |
| --- | --- | --- |
| Original ZIP hash and per-file manifest | Matched exact original SHA256 | PASS |
| Original PHP lint | 8 / 8 files on PHP CLI 8.4.24 | PASS |
| Original unit tests | 19 / 19 | PASS |
| Candidate PHP lint | 10 / 10 files on PHP CLI 8.4.24 | PASS |
| Candidate unit tests | 28 / 28 | PASS |
| Candidate test DSN negative checks | Wrong database name and missing allow flag refused with exit 2 | PASS (negative guard only) |
| Actual MySQL 8 integration | PHP PDO_MYSQL/MySQL server unavailable in authoring environment | NOT_RUN |
| Actual HTTP smoke | No MySQL-backed HTTP runtime executed | NOT_RUN |
| UAT-01…UAT-35 | No human acceptance evidence | BLOCKED |
| VPS staging and live release | No verified deployment | HOLD / NO-GO |

The owner-supplied Windows preflight separately indicated PHP CLI 8.2.12, pdo_mysql=true, and a MySQL command-line client. This **does not establish active MySQL server connectivity**.

## Findings and prepared candidate remediation

1. **P0: destructive test guard**. Original integration script deleted rows after a weak substring check of DSN. Candidate requires exact local `sck_ci` DSN, live database-name verification, explicit disposable database sentinel, seven-table schema completeness, and refuses when `config.php` would override test env.
2. **P1: login brute force**. Original login lockout was per-session. Candidate adds database-backed shared rate limits keyed with HMAC account/IP identifiers using deployment-specific `AUTH_THROTTLE_KEY`. Requires additive `login_throttles` migration/config; MySQL behavior remains unverified until the test DB is available.
3. **P1: withdrawn material visibility**. Original code allowed some privileged read-through and unpublished lesson editor previews after withdrawal. Candidate blocks detailed source views/previews and removes withdrawn content from main listing. Unit assertions added.
4. **OPEN design limitations**. MEMBERS covers all authenticated users, not school cohorts; holder authority/consents require independent evidence; no complete student data retention policy, external immutable audit or independent restore drill.

## Candidate artifacts in ChatGPT session

- `BUDAYAGO_SCK004_MVP_CANDIDATE_20261113.zip`, SHA256 `2ad55f6d74a1e87ed89e7803fd80aae3eeecb13488c2d9fa595a1bf415ba87e4`. **Not a production release.**
- `BUDAYAGO_SCK004_REVIEW_KIT_20261113.zip`, SHA256 `ea8fc105e6e1035374ee4c185e08027ec2f828f005ec86fd98821ccc2d1b713d`. Includes audit report, blocked UAT ledger, safe new-clone PowerShell script, and local MySQL runbook.
- `BUDAYAGO_SCK004_SHA256SUMS.txt`.

User environment workflow: extract candidate in a **new** directory; verify hashes, perform local review, run real disposable MySQL 8 tests, then sync reviewed source through a new Git clone based on `development/sck-mvp-20261113` and let CI run. Never overwrite workspace or LICENSE, merge main, or deploy staging without gates.

## Final decision

**SOURCE_RECOVERED: YES. CODE_CANDIDATE: PREPARED. MySQL TEST: NOT_RUN. UAT: BLOCKED. VPS STAGING: HOLD. PRODUCTION: NO-GO.**

The November 2026 artifact cannot establish BUDAYA GO! eligibility by 31 March 2026.
