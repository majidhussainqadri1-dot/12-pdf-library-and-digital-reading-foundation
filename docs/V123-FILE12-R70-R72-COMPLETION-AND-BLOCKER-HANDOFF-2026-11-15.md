# File 12 — V12.3 auditable completion and remaining-work handoff
**Evidence frozen:** 2026-11-15. **Scope:** Sabri PDF Library and Digital Reading. **Release status:** STOP-SHIP / NO_GO. **No live deployment.**

## Frozen code and testing truth

| Reality | Evidence |
|---|---|
| Governing plan | `Sabri-Website-Master-Plan-V12.3.docx` File 12 p.41; 19 FR, 10 NFR, 24 FUT, 1 CEN + 12 M + 10 QA + AJ-14 = 77 acceptance IDs |
| GitHub main | `2b369ef2d609247896e1ab32118f1dc498407f51`, README-only |
| Original plugin source | Draft PR #57 `d7c0d9458402019a3c46c73b3a7563329e9be8f7`, `1.1.0-rc.43` |
| Parent reader correction | Draft PR #65, SHA `8ed340c0ffe61985bdbe8e49b369ef7c468200da`, `1.1.0-rc.44` |
| R70 reader Range integrity | Draft PR #66, SHA `7b58c4d8a13d8849277a72df0ae76ec6e863fdc9`, `1.1.0-rc.45`, 7/7 exact-source CI `43685437189` |
| R71 encrypted offline source integrity | Draft PR #67, SHA `c8cb21794288cef836d711339bb1ae0fc82248b4`, `1.1.0-rc.46`, 7/7 exact-source CI `43686124624` |
| R72 online owner-rights check | Draft PR #68, first complete source QA at SHA `68d6081463b71ee82174dd20c691728ee52c5851`, `1.1.0-rc.47`, 7/7 CI `43686569205` |
| Physical deployed File12 package, plugin directory, installed dependencies | **UNVERIFIED** |
| Hostinger physical DB, options, current schema, migrations | **UNVERIFIED** |
| Authorized Hostinger Staging + production Live acceptance | **UNVERIFIED**, **NOT PERFORMED** |

**Recent connection attempts:** WordPress.com listed Jetpack site `https://www.sabrihomeopathy.com` but site scoped tools reported `site_disconnected`. WPVibe has no registered connected site; attempted authorization path returned HTTP530. Neither symptom proves host-origin failure or a production File12 bug. Do not change host security configuration to 'fix' this without evidence.

## New defect and proof accounting

| ID | Source defect reproducible in test? | Draft correction? | Live fix? | CI |
|---|---|---|---|---|
| R70 | YES, illegal HTTP200 range; changed object digest on renewal unguarded | YES, exact 206+Content-Range+byte-count+identity | NO | RED `43684888800` / GREEN `43685437189` |
| R71 | YES, offline vault trusted advertised PDF hash without verifying captured plaintext | YES, exact 206+full SHA256 before store/open, bounded <=128MiB | NO | RED `43685678429` / GREEN `43686124624` |
| R72 | YES, current online rights recheck skipped if reconnect flag absent | YES, every online open requires owner recheck | NO | RED `43686398961` / GREEN `43686569205` |

Previously documented R64-R69: six independent source defects, all draft-fixed and zero live-verified; combined R64-R72: **nine source defects, nine source-candidate corrections, zero verified Live corrections**. Do not equate eleven older reviewed branches, CI green, or actual plugin presence on Live.

## The ten existing stop-ship blocker categories — each must acquire physical receipts

| Gate | Severity | Owner role/module, not an assigned person | Exact acceptance evidence needed |
|---|---|---|---|
| BLOCK-01 Source release scope | HIGH | File01B / Founder | Choose single immutable reviewed candidate SHA, reconcile draft PR parents/overlays, sign source scope decision; no silent merge |
| BLOCK-02 Deployed reality | CRITICAL | Hostinger operator / File24 | Authorized read-only active plugins directory; exact `PLDR_VERSION`, installed plugin folder, per-file SHA256, runtime WordPress/PHP/MySQL versions, redacted error logs |
| BLOCK-03 Physical schema | CRITICAL | File12 schema / Hostinger operator | Read-only table/column/index count, selected non-secret `wp_options`, migration markers, locks/checkpoint/errors; classify stale/deferred/partial upgrades before any root-cause label |
| BLOCK-04 Installation path compatibility | HIGH | File01B / File12 / Founder | Decide `12-pdf-library-foundation/` vs `pdf-library-foundation-12/` after checking real deployed paths, old bookmarks, cron, options and rollback |
| BLOCK-05 File26 Book Packs | HIGH | File26+File12 canonical owners | Versioned PDF plus `book_pack` owner typed contract or explicitly approved File26 PDF-only revision; list/fetch/rights and code installed evidence, independent signoff |
| BLOCK-06 File26 rights tombstones | CRITICAL | File26/File12/File24 | Live owner rights withdrawal -> event id/sequence -> File26 consumed ACK and index/cache deletion -> replay and timeout negative tests on staging |
| BLOCK-07 CF04 private storage | CRITICAL | CF04/File12/File24 | Real scanner, private encrypted storage, key rotation, object access, OCR sandbox, backup+restore evidence; 10 STO control receipts |
| BLOCK-08 Lawful original books | HIGH | File12 rights/editorial owner | At least one Founder-owned/public-domain/licensed PDF with documented source, edition/pages, author/publisher, file/edition hashes, rights and OCR provenance, human approval |
| BLOCK-09 Staging runtime/UX | CRITICAL | File24 and module owners | Exact SAME package SHA in Hostinger staging, 39 historic tests, 54 runtime requirements, 10 CF04, 77 V12.3 IDs and QA15-24 60 scenarios with redacted evidence, roles/devices, negative/retry and defect retest |
| BLOCK-10 Independent approval and Live | HIGH | Founder and two independent reviewers | Two actual reviews, capacity/performance and WCAG/RTL, rollback+restore rehearsal, GO/NO_GO, controlled release and separate Live parity/retest proof |

**Proof ledgers:** 39/39 historic Stage UNVERIFIED; 54/54 requirement runtime Stage UNVERIFIED; 10/10 CF04 Stage UNVERIFIED; 77/77 V12.3 Stage and 77/77 distinct Live UNVERIFIED; 60/60 detailed QA15-24 Stage/Live UNVERIFIED. These are not 240+ failures nor 240 proven bugs; controls overlap and remain unobserved.

## Ordered safe execution — no assumed person assignments

1. **Owner/authorized Hostinger operator** obtains read-only reality freeze; redact secrets, patient/identity info; capture source and DB evidence out of webroot, no production writes.
2. **File12 owner** reconciles main vs draft SHA vs stage/deploy checksum and physical schema, checks plugin-directory migration/rollback compatibility. On mismatch stop ordinary debugging and conduct Deployment-Parity Audit.
3. **Canonical File26 and CF04 module owners** negotiate versioned typed contracts, actual installation callback permissions, secrets/storage split and tombstone last-applied ACK. Any source changes on those owner repos require independent review and their own exact source QA.
4. **Editorial/rights owner** supplies lawfully licensed test book(s), not actual patient cases; inspect exact edition pagination, page OCR, rights withdrawal and secure download/offline window.
5. **File24 / staging QA** deploys only an approved candidate to isolated staging after rollback/backup readiness, runs all mapped acceptance scenarios on real browser/device/account classes and stores independent 2-review proof.
6. **Founder** makes final GO/NO_GO decision after source QA, security, privacy, rights, consent and cutover contracts. Do NOT merge to main as a release or deploy production before that decision.
7. **Authorized production operator** on GO performs fix -> test -> package -> deploy -> live re-test -> exact package+schema parity confirmation, then monitor and maintain rollback capability.

## Known deliberate functional limitations
- The R71 offline vault rejects files above **128 MiB** until memory-bounded incremental cryptographic stream verification is independently designed and tested. This does not replace other site 1 GB file-transfer policy.
- R70 download manager for files above 128MiB currently checks strict ranges and total bytes but **does not claim client-computed whole-file SHA-256**.
- File26 PDF connector is **proposed and dormant**; Book Pack owner compatibility / search projection and indexed rights revocation still blocked.
- Actual Hostinger responsive/UI usability and WCAG 2.2 AA, original PDF data/OCR, real user permissions, restore and incident lifecycle remain unverified.
- An actually disconnected browser cannot query current server rights until it reconnects; local offline-valid-until remains a documented policy trade-off.

**Final truth:** Repository HEAD `2b369ef2d609247896e1ab32118f1dc498407f51`; Deployed Version **UNVERIFIED**; DB Version **UNVERIFIED**; Migration State **UNVERIFIED**; Live Verification Status **NOT PERFORMED**. Release **NO_GO**.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
