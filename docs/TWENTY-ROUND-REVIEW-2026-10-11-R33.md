# File 12 R33 — Twenty New Focused Reviews, Divergent R32 Security Reconciliation
Date: 11 October 2026, Pakistan.

## Exact source evidence
- Repository `main` exact source HEAD: `2b369ef2d609247896e1ab32118f1dc498407f51`, initial README only; it is not an installed-artifact fingerprint.
- Source basis: R32 PR #22 branch `review/file-12-r32-twenty-audit-2026-10-11`, exact HEAD `d33e7e4871806988d2119cb4b593c7e12e0929e2`, CI #430 success. Preserves R31 malformed-ingest, R32 physical-size/cover-precommit/encrypt-failure cleanup.
- Independent divergent R32 PR #23 HEAD `c9456ea249101dd26565a338f210ae49eae5bc5f`, CI #426 success; both PR22 and PR23 had claimed software `1.1.0-rc.11` for **different source**. Comparing commits showed branches diverged from R31 `55005aef...`. Therefore PR23 flag security was **not** automatically in PR22.
- R33 uses R32 PR22 as base and ports narrowly the proven PR23 rights/consent input fix and its actual PHP fixtures. Unique software release candidate `1.1.0-rc.12`; no database schema or integration contract migration.

## Governing source hierarchy
Reviewed the attached File12 `PDF Library and Digital Reading Complete Master Plan v1.1 Future-24 Amended`, `Three Central Plans Consolidated Governing Master Plan`, and attached File05 Learn, File06 Encyclopedia, File21 Home/News Feed, File23 Doctor/Founder Publishing Dashboard and File25 Public UI plans. Major applicable obligations: `F12-FR-001..019`, `F12-NFR-001..010`, `F12-FUT-001..024`, rights-aware public/private access, consent/verification, bounded normalized metadata, non-silent user policy, signed delivery, canonical owner separation, real staging/backup/rollback and Founder-approved release. No File12 code may override File05 course status, File06 encyclopedia truth, File16 AI content policy, File17 chat rooms, File20 shell, File21 feed, File23 publishing workflow or File25 design system.

## Review discipline and scope
**20 separate targeted source/code-and-governing-plan focus reviews** plus a new 20-item source-anchor inspection were performed on R33 after recording the R32 exact baseline and divergent PR. This is not twenty complete human line-by-line audits or twenty WordPress/staging/live end-to-end tests. The retained automated R28 repeated complete contract matrix tests 53 FR/NFR/FUT requirement anchors and 12 File12-side companion adapter anchors **twenty times**, i.e. 1,300 repeated static assertions plus 20 additional invariants; source presence tests do not prove behavioral completeness. The defect ledger was identified first, corrected as a batch, and followed by actual PHP tests. No code was merged or deployed.

| Round | Examined requirement and exact source owner | Source finding and disposition |
|---:|---|---|
| **01** | Exact GitHub lineage, `main`, R31 vs two R32 PRs | **R33-D01:** two divergent distinct `rc.11` code candidates, so single release identity was ambiguous; unified this code as unique `rc.12` |
| 02 | F12-FR-001, document categories/types and File12 canonical facts | `PLDR_Core::DOCUMENT_TYPES`, `CATEGORIES`; source anchors present; real DB collection unverified |
| 03 | F12-FR-002, metadata/schema/required PDF | `PLDR_Ingest` R31 strict input shape; real multipart WordPress staging pending |
| 04 | F12-FR-002/005 physical file size and SHA, hash/scan/dedupe | R32 `filesize` and crypto/duplicate source guards present; real-file/provider acceptance pending |
| 05 | F12-FR-002/007 malware/derived media | `PLDR_Ingest::scan_file` provider source present; actual external provider unverified |
| 06 | F12-FR-007 bad or partial cover before PDF commit | R32 `pldr_cover_upload_preflight` source and R31 entrypoint tests retained |
| 07 | F12-FR-006 temp ciphertext cleanup and encryption failure | R32 `PLDR_Storage::delete($temp)` on false/exception and storage shutdown fallback retained |
| **08** | F12-FR-003/008, File00/24 consent: `PLDR_Ingest` rights, patient-case and duplicate confirmation booleans | **R33-D02:** `empty('false')` evaluated as true policy/consent/confirmation. Canonical `PLDR_Core::policy_flag` enforces explicit 0/1, rejects invalid values and normalizes all five ingest flags before side effects |
| **09** | F12-FR-008, `PLDR_Access::update_policy` download/print/offline permissions | **R33-D03:** `empty('false')` could persist allow flags as 1. Canonical parser gates inputs before DB read/write, uses normalized 0/1 downstream |
| **10** | F12-NFR-001, rights mutation JSON audience/entitlement/embargo | **R33-D04:** malformed array/object/bool JSON values risk invalid string casts and ambiguous failure. Enforced scalar-only boundary before current-state DB lookup |
| 11 | Rights/expiry/embargo; licensed source and takedown | Known audience and malformed embargo fail-closed from R30 present; real rights-case workflow unverified |
| 12 | Delivery token, current edition binding and revocation | `PLDR_Access::current_delivery_binding` and signed operation checks source-present; real WordPress HTTP/IDOR pending |
| 13 | Private object storage and authenticated range encryption | `PLDR_Crypto::stream_range`, `PLDR_Storage::cleanup_temporary_paths` source-present; key restore proof unavailable |
| 14 | Private reader progress, notes, citation, documents | `PLDR_Reader`, owner-based state paths and associated source suite; actual cross-device persistence pending |
| 15 | Privacy data erasure/retention/legal hold (File24) | `PLDR_Privacy` exporter/eraser source-present; real legal hold and key-rotation lifecycle unverified |
| 16 | F12-FR-013 library search, metadata, cursor, OCR | R29/R30 secured search and signed cursor source/fixture retained; actual File26 indexing consumer unverified |
| 17 | Native File05 lessons and File06 encyclopedia | `PLDR_Future_Context` File05/File06 links are source-side adapters only, not native owner substitution |
| 18 | File16 AI corpus access and patient privacy | `PLDR_Future_Corpus` allowlist source-present; consumer/scanner/permission parity unverified |
| 19 | File17/19/20/21/23/25 cross-module rooms/outbox/feed/shell/dashboard/UI | `PLDR_Future_Rooms`, `PLDR_R21_Outbox` and UI adapters source-present; deployed companions and browsers unverified |
| 20 | F12-NFR-007, migration/schema, release/Hostinger parity | `PLDR_Schema::maybe_upgrade` exists; actual deployed PHP, DB tables/options, stored object checksums, release folder name mismatch and backup/rollback still unknown |

## Remediation and executable tests
- R33-D01: preserve both distinct R32 improvements in one linear branch: R32 PR22 physical upload integrity + R32 PR23 explicit rights/consent flags. New standalone software identity `1.1.0-rc.12`; DB/schema and integration contract remain `1.1.0`.
- R33-D02: add `PLDR_Core::policy_flag($value): ?int` (1/0/null); accepts only defined boolean, integer or textual allow/deny values; `PLDR_Ingest` checks/normalizes download/print/offline, patient-case consent acknowledgement and distinct-scan flag before metadata/DB/file work. Independently verified patient case anonymization/consent clearance remains mandatory.
- R33-D03: `PLDR_Access::update_policy` now validates and normalizes all access flags before reading or persisting current policy; text `false/off/no` cannot become enabled grants.
- R33-D04: explicitly reject nested and boolean `audience`, `entitlement_key` and `embargo_until` policy fields before DB reads, preventing unexpected input shape coercion.
- `tests/test-r33-policy-flags.php`: calls **actual** `PLDR_Core::policy_flag`, `PLDR_Ingest::ingest`, and `PLDR_Access::update_policy`, exercises truthy/falsy values, malformed values, patient-case consent, bad nested/boolean policy fields, real persisted rights flag values and verifies invalid inputs cause no DB reads/writes. Run on PHP 8.1/8.3/8.4 in GitHub Actions.
- Retain `tests/test-r32-pdf-physical-integrity.php`, `tests/test-r31-ingest-input-shapes.php`, real R29 catalog/cursor, R30 embargo/unknown audience, and R28 complete repeated matrix, all earlier crypto/range/rights tests.

## Release/production blockers (do NOT claim they are source code defects)
- File12 amended plan names plugin ZIP inner root `12-pdf-library-foundation` while historical source candidate uses `pdf-library-foundation-12`. No unapproved rename because it could activate a duplicate WordPress plugin or strand schema/options during upgrade. Founder-approved dated compatibility/migration/rollback plan required.
- Installed File00/01/05/06/16/17/19/20/21/23/24/25/26 and CF04 versions/consumers/event/data/visual route contracts require their exact deployed source and integration acceptance.
- Live reality freeze not available: active plugin files/package/checksum, physical WordPress DB version/tables/rows/options/migration status, real PHP/runtime/error log/config/crypto keys/dependencies unverified. Staging roles/anonymous/guardian/doctor, scanner, PDF uploads, encryption restore, offline, RTL/accessibility, backup & rollback and two fresh staging review/fix cycles remain mandatory.
- No merge to `main`; no live deployment; green Actions alone do not establish installed code parity or operational resolution.

## Five-field report
- **Repository HEAD:** final R33 PR #24 exact SHA; `main` `2b369ef2d609247896e1ab32118f1dc498407f51` unchanged.
- **Deployed Version:** UNVERIFIED.
- **DB Version:** UNVERIFIED (source-target `1.1.0` only).
- **Migration State:** UNVERIFIED.
- **Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
