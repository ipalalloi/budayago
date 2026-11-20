# UAT functional and security acceptance matrix — MVP v0.1

**Statuses:** `STATIC_PASS` = PHP syntax / unit checked; `AUTO_PENDING` = CI MySQL/HTTP not yet executed in verified environment; `UAT_PENDING` = requires independent human testing. Do not mark a scenario PASS on the basis of a code listing.

| ID | Persona | Scenario / expected result | Evidence to retain | Status |
|---|---|---|---|---|
| UAT-01 | Guest | Home page renders on desktop/mobile | HTML screenshot, console | AUTO_PENDING |
| UAT-02 | Guest | Login requires correct credentials | HTTP session/response | AUTO_PENDING |
| UAT-03 | Guest | Missing CSRF blocked with HTTP 403 | HTTP log | AUTO_PENDING |
| UAT-04 | Guest | No access to user admin | HTTP 403/redirect | UAT_PENDING |
| UAT-05 | Admin | Creates five role-specific accounts | DB & UI screenshot | UAT_PENDING |
| UAT-06 | User | Changes password; old password ceases working | Login retest | UAT_PENDING |
| UAT-07 | Admin | Can disable account; disabled session loses access | HTTP retest | UAT_PENDING |
| UAT-08 | Admin | Cannot disable own account | UI validation | UAT_PENDING |
| UAT-09 | Admin | Cannot deactivate holder with active approved knowledge | UI validation | UAT_PENDING |
| UAT-10 | Curator | Creates draft with documented source and designated holder | DB status draft | AUTO_PENDING |
| UAT-11 | Student | Cannot view draft / restricted content | HTTP 403 | AUTO_PENDING |
| UAT-12 | Curator | Submits draft for holder review | DB status submitted | AUTO_PENDING |
| UAT-13 | Other holder | Cannot approve content assigned to someone else | DB unchanged | AUTO_PENDING |
| UAT-14 | Admin | Cannot self-approve cultural content | DB unchanged | AUTO_PENDING |
| UAT-15 | Holder | Can reject with mandatory reason | Status / event log | UAT_PENDING |
| UAT-16 | Curator | Can revise rejected content and resubmit | DRAFT -> SUBMITTED | UAT_PENDING |
| UAT-17 | Holder | PUBLIC approval requires separate online and learning grants | UI/DB negative test | AUTO_PENDING |
| UAT-18 | Holder | AI/promo permissions default off and remain optional | DB evidence | AUTO_PENDING |
| UAT-19 | Admin | Publishes approved public knowledge only | HTTP page + status | AUTO_PENDING |
| UAT-20 | Admin | Cannot publish restricted item | DB unchanged | AUTO_PENDING |
| UAT-21 | Guest | Can read PUBLIC but not MEMBERS resources | HTTP response | UAT_PENDING |
| UAT-22 | Curator | Creates lesson only from publishable permitted source | DB + UI | AUTO_PENDING |
| UAT-23 | Student | Cannot see draft lessons | HTTP 403 | AUTO_PENDING |
| UAT-24 | Admin | Can publish lesson, student can view it | DB + screenshot | AUTO_PENDING |
| UAT-25 | Student | Correct/wrong answers evaluated and counts persist | DB + screenshots | AUTO_PENDING |
| UAT-26 | Student | Cannot view quiz correct answer in page source | Source review | UAT_PENDING |
| UAT-27 | Holder | Revokes consent; published source and lesson immediately invisible | HTTP 403 + DB | AUTO_PENDING |
| UAT-28 | Student | Cannot submit answer to revoked lesson | HTTP 403 / DB unchanged | AUTO_PENDING |
| UAT-29 | Admin | Audit page lists approvals/publishing/revocation | UI + DB | UAT_PENDING |
| UAT-30 | Security | XSS input encoded, SQL injection cannot change queries | Test vectors | UAT_PENDING |
| UAT-31 | Security | Cookies HttpOnly, SameSite and Secure over HTTPS | Response headers | UAT_PENDING |
| UAT-32 | Accessibility | Navigation, forms, status messages work with keyboard/screen reader | Human evidence | UAT_PENDING |
| UAT-33 | Operations | Restore from DB backup, redeploy and rollback tested | Restore log | UAT_PENDING |
| UAT-34 | Culture | Authorized representative verifies one real lesson and publication boundaries | Private signed approval reference only | UAT_PENDING |
| UAT-35 | Pilot | Teachers/students confirm practical learning activity, no fabricated gains | Consent-approved pilot protocol | UAT_PENDING |

## Release criteria

1. No critical security or permission leak; zero known critical open defects.
2. All relevant automated tests pass against the **actual supported MySQL version**, with SHA, PHP version, and timestamps recorded.
3. All UAT scenarios UAT-01...UAT-35 resolved as PASS or a documented justified NOT_APPLICABLE; no unreviewed SKIP.
4. HTTPS and backups checked on target hosting; named approver signs go-live decision.
5. Cultural material requires independent holder authorization and per-purpose consent.
6. An entirely new build dated November 2026 is not valid historical proof for BUDAYA GO! eligibility cutoff 31 March 2026.
