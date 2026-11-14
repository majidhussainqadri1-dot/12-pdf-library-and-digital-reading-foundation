# File 12 — V12.3 execution audit and release truth (14 November 2026)

**Status: SOURCE-GOVERNANCE-IN-PROGRESS / PRODUCTION NO_GO.** This document is the first executable audit receipt for the Word master plan `Sabri-File12-V12.3-Complete-Implementation-Plan-2026-11-14.docx` (WP-00…WP-13), governed by **Sabri Website Master Plan V12.3**. It does not assert that WordPress was modified or the PDF Library was installed.

## 1. Five realities — verified vs unknown

| Reality | Evidence and limitation |
|---|---|
| Governing requirement | Sabri Website Master Plan V12.3, File 12 and common M01–M12/QA-15–24, CEN-REL-001, CEN-PUB-001; WP-00–WP-13 implementation plan |
| Repository `main` | `2b369ef2d609247896e1ab32118f1dc498407f51`; README-only, **not** a plugin release |
| Frozen source candidate | Draft PR #57 exact source SHA `d7c0d9458402019a3c46c73b3a7563329e9be8f7`, software `1.1.0-rc.43`; declared source DB/contract `1.1.0`; GitHub Actions `43379718214` (4/4 jobs GREEN) |
| Hostinger deployed File 12 artifact | **UNVERIFIED**: active/inactive status, install directory, software version, file list, SHA256, current companion installations |
| Physical DB and migrations | **UNVERIFIED**: actual tables, columns, indexes, selected nonsecret `wp_options`, migration version, locks, stale/partial/deferred upgrade |
| WordPress runtime and live acceptance | **UNVERIFIED**: actual PHP/WordPress/MySQL, plugin errors and current user journeys; no live/production retest |

On 2026-11-14, the WordPress.com/Jetpack connector identified the site `www.sabrihomeopathy.com` but returned `site_disconnected` for site-scoped tools. WPVibe reported no existing account connection; a connection attempt returned **HTTP 530**, without a root-cause diagnosis. Do not infer that Hostinger is down, that the site's security layer caused it, or that any File12 plugin is installed. No production write occurred.

**Mandatory diagnostic disclosure:** “Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔”

## 2. Concrete source-only findings, not live-bug allegations

1. **PR #57 remains Draft/unmerged**. Its inherited source documentation describes File12 core catalog, ingest, rights, signed PDF access/range, reader, private notes, preservation, and Future-24 implementation anchors. Presence is not operational acceptance.
2. `pdf-library-foundation-12/includes/class-pldr-file26-connector.php` declares a **proposed, dormant File26 public PDF connector**, with `manifest().status='proposed'` and `entity_types=['pdf']`; its own cutover guard requires versioned rights tombstone agreement. A real `book_pack` list/fetch provider is not advertised. **Integration work remains**; do not flip it on from source alone.
3. `pdf-library-foundation-12/includes/class-pldr-r21-outbox.php` has File26 consumer declarations for access changed/unpublished/restricted events. Local outbox `sent` is not evidence of File26 indexed tombstone ACK, last applied sequence, cache/index purge, or replay safety. Existing Issue #53 tracks the blocker.
4. **Canonical plugin folder naming conflict** remains subject to an authorized compatibility decision: candidate `pdf-library-foundation-12/` versus historic `12-pdf-library-foundation/`. This is not solved by renaming the folder without checking deployed paths, options and links.
5. Existing release ledgers show **39/39 Hostinger staging gates UNVERIFIED**, **54/54 requirement runtime gates UNVERIFIED**, and **10/10 CF04 shared-security gates UNVERIFIED**. These are **not** 103 failed tests or 103 proven bugs.
6. **R64 and R65:** two reproduced *source-level release-gate validation* defects were corrected in unmerged PR #57. Fake observed statuses and missing defect retest must cause NO_GO. CI green is source-only; no stage/live fix is proven.
7. This audit adds a separate **77-row V12.3 mapping** (54 old stable IDs + 12 M + 10 QA + AJ-14) without deleting, re-numbering or marking any existing gate PASS. The source anchor column is a reported implementation anchor, **not an independently completed security/code review**.

## 3. V12.3 work-package execution registry

| Package | Scope and acceptance output | Current verified state | Next gate |
|---|---|---|---|
| WP-00 | Versioned ID→owner→source→test→environment matrix, defect ledger, decision log | **PARTIAL SOURCE DELIVERABLE**: 77-row V12.3 mapping and PHP structural test added on audit branch | Independent V12.3 line-by-line matrix review and authorization of final scope |
| WP-01 | Read-only live freeze: deployed plugin bytes, WP/PHP/DB/options/migration/logs | **BLOCKED**: Jetpack disconnected; WPVibe HTTP 530/no site connection | Authorized Hostinger/SSH read-only evidence or restored authenticated site integration |
| WP-02 | GitHub main/PR/stage/live ZIP checksum and schema parity; rollback preflight | **BLOCKED BY WP-01** | Compare exact stage/live package SHA, tables/options; approve folder/path decision |
| WP-03 | 54 IDs, 24 Futures, 12 M, 10 QA, AJ-14 defect matrix | **PARTIAL SOURCE-ONLY**: coverage claims and evidence categories mapped | Inspect all code paths and test each against real exact deployed runtime after parity |
| WP-04 | File26 PDF+BookPack, File26 indexed tombstone ACK, CF04 and other owner contracts | **BLOCKED BY OWNER AGREEMENT** | Typed versioned contract and real consumer installed-version evidence; keep connector dormant |
| WP-05 | Catalog/rights/ingest/scanner/crypto/private objects/delivery/preservation | **SOURCE ANCHORS PRESENT, RUNTIME UNKNOWN** | Authorized sample licensed books, real scanner/key/OCR/private storage and rights test |
| WP-06 | Reader/private notes/progress/Future-24, responsive, offline | **SOURCE ANCHORS PRESENT, RUNTIME UNKNOWN** | Browser, role, immutable original, offline-expiry and accessibility evidence |
| WP-07 | File05/06/16/17/19/21/26 canonical integration | **SOURCE CODE PROPOSED/PARTIAL** | Compatible installed consumers, permission negative tests and retraction propagation |
| WP-08 | Object/field authorization, data minimization, privacy, abuse/recovery | **SOURCE CLAIMS, NO LIVE PROOF** | IDOR/session/revocation/restore and retention tests |
| WP-09 | V12.3 visual/RTL/en-US/keyboard/WCAG 2.2 AA | **NEW DELTA MAPPED, UNTESTED** | 320/390/768/1024/1440, 200%/400% zoom, 5 formative/12–18 usability participants |
| WP-10 | Performance/observability/operations and backup RPO/RTO | **UNVERIFIED** | Baseline, eligible p75 field, load, exact restore/recovery proof |
| WP-11 | 39+54+10 staging controls and 2 independent complete reviews | **UNVERIFIED** | Real Hostinger staging receipts on the approved final SHA |
| WP-12 | Founder GO/NO_GO, package, controlled prod, live retest/parity | **NO_GO** | All stop-ship gates passed, signed decision and verifiable rollout |
| WP-13 | Postrelease monitoring, incident runbooks, updates | **NOT ACTIVATED** | Only after approved live deployment |

None of these rows should be treated as an assignment to a named person; the named **File##** owners refer to canonical modules and approval roles, not evidence of their availability.

## 4. File 12 V12.3 acceptance matrix, with original identities preserved

- File12: **19 FR + 10 NFR + 24 FUT + 1 CEN = 54**.
- Common web acceptance: **M01–M12 = 12**, **QA-15–QA-24 = 10**.
- Reader journey: **AJ-14 = 1**.
- Total new crosswalk: **77 immutable, nonduplicated rows**, `docs/V123-FILE12-77-REQUIREMENT-AND-ACCEPTANCE-GATES.csv`.
- Original R57–R59 evidence ledgers: **39 + 54 + 10** rows, retained as separate runtime proof requirements; do **not** add their counts to the 77 unique requirement rows as if they were independent features.
- `tests/test-v123-evidence-matrix.php`: checks unique identity/counts, source-to-acceptance fields and anti-fabricated PASS constraints, and retains original 39/54/10 register dimensions. Integrated with existing PHP CI. Passing this test is *not* Staging or Live GO.

## 5. Stop-ship and ownership decisions requiring real evidence

**P0 Blockers**: (A) exact installed package, (B) physical DB/schema/migration, (C) plugin directory compatibility, (D) File26 typed PDF + Book Pack owner contract, (E) File26 rights-withdrawn tombstone receipt and cache/index purge, (F) CF04 private storage/scanner/key/restore, (G) approved original content and licence, (H) staging 39/54/10, (I) two independent postfinal-SHA reviews, (J) Founder approval and actual live retest. A partial or disabled optional feature may remain outside active scope only with explicit user-facing state, verified isolation and a recorded open issue.

Do not reinterpret `WPCode/CI`, historical CI screenshots, or source manifests as live WP status. Do not label a defect `Resolved` until installed SHA + physical migration + actual live reproduction/retest establish the outcome.

## 6. Required next evidence collection (no secrets or private clinical content)

1. Authorized host operator runs or independently reviews `scripts/file12-live-reality-freeze-readonly.sh` **read-only** on correct live/staging targets; check exact script safety and SSH/DB privileges before execution. Record UTC, target, command/tool versions and result SHA. Avoid collecting credentials, raw medical content, full logs or backup keys.
2. Freeze WordPress `active_plugins`/plugin slug and manifest, relevant `wp_options` *version keys only*, physical `information_schema` columns/indexes/table counts and migration locks/last failure markers. Do not use a GitHub declared DB version to fill a physical DB value.
3. Capture approved install ZIP SHA256 and compare to staged/live deployed files; separately capture the current File26 and CF04 installed versions and their owner-owned interface versions.
4. If mismatch, STOP ordinary live diagnosis and perform the dedicated Deployment-Parity Audit first. If parity cannot be inspected, keep `deployed_code=UNVERIFIED`.
5. Obtain a rights-cleared test book/edition with source page hashes, then exercise T-01–T-39 and the 54+10 gates; V12.3 QA-15–24 remain additional proof obligations.
6. Review A covers complete lawful user journeys/privacy and source provenance; after repairs, an independent Review B covers privilege, revocation, concurrent requests, degraded provider, migration/restore. Both are on exact final tested artifact.
7. Founder GO/NO_GO only after gate ledger and independent approval; monitor controlled release, check live parity and hold defect resolution until live retest.

## 7. Citable decision receipts and live incident final template

- `Repository HEAD:` main `2b369ef2...`; audited frozen source `d7c0d945...`; new V12.3 audit branch changes **not merged**.
- `Deployed Version:` UNVERIFIED.
- `DB Version:` UNVERIFIED (candidate source declaration `1.1.0` is separate).
- `Migration State:` UNVERIFIED.
- `Live Verification Status:` NOT PERFORMED.
- `Code defects discovered by this audit:` 0 **newly demonstrated runtime bugs** (source-only blockers recorded); preexisting R64/R65 2 source validation defects corrected in Draft.
- `Staging gates:` 39 unverified; `Requirement gates:` 54 unverified; `CF04 owner gates:` 10 unverified; V12.3 12+10+AJ-14 additional mapping unverified.
- `Action:` keep release **NO_GO**; do not merge this evidence-only branch as a claim of deployment.
