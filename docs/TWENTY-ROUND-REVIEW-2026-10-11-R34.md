# File 12 R34 — Twenty focused governing-plan/source inspections and corrected access-consistency release
Date: 11 October 2026, Pakistan. Based on File 12 PDF Library v1.1 Future24 amended 2026-08-11 and the three central governing plans; reviewed the supplied companion File05 Learn, File06 Encyclopedia, File21 Home/Feed, File23 Publishing Dashboard, and File25 Public UI plans.

## Truth freeze and scope
- **Source repository R33 frozen parent:** Draft PR #24 exact HEAD `f7b1828ceb932837f9031e65f4d1dc44cc43b9e7` with GitHub Actions #437 SUCCESS. Repo `main` is bootstrap README at `2b369ef2d609247896e1ab32118f1dc498407f51`, neither of them automatically establishes installed Hostinger code.
- R34 is a **source-only** corrective candidate. Nothing has been merged or deployed. Do not call a production defect resolved from source QA.
- Reference requirements: `F12-FR-001..019`, `F12-NFR-001..010`, `F12-FUT-001..024`; particularly **FR-008 (licensed audience/download/print/offline)**, **FR-009 (short-lived audience/object/operation-bound tokens)**, **NFR-001 (object/field authorization/IDOR)**, **NFR-003 (reliable failures)** and **NFR-007 (safe migration/rollback)**.
- Inspections of File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 are strictly source-side adapters in File12, not evidence of the actual installed companion plugins.

## 20 separate targeted inspection rounds — frozen finding ledger before corrections
These are *distinct source-plan focal checks*, supplemented by the existing automatic **20 repeated full 53 FR/NFR/FUT requirement + 12 companion adapter static matrix passes (1300 source-presence checks, plus 20 extra invariants)**. Do not misrepresent these checks as 20 exhaustive human file-by-file audits or 20 staging/live E2E runs.

| Review | Governing component / examined actual source | Finding |
|---:|---|---|
| **01** | File12 FR-008, `PLDR_Core::public_document_dto` catalog flags | **R34-D02:** legacy persisted `'false'`/`'off'` was truthy under `!empty()`; public API could advertise rights not granted in text |
| 02 | File12 NFR-007, `PLDR_Schema::maybe_upgrade` | Upgrade orchestration source present; actual DB/options/migration unverified |
| 03 | FR-002, `PLDR_Ingest::validate_pdf` | R32 physical byte-count/hash/check guards retained; real Hostinger HTTP upload not tested |
| **04** | **FR-008/NFR-001, `PLDR_Access::can_access_edition`** | **R34-D01:** persisted/legacy string `'false'`/`'off'` permitted forbidden download/print/offline due PHP `empty()`. Mutation-time R33 flags were insufficient to protect old records |
| **05** | **FR-009, `PLDR_Access::issue_token`** | **R34-D03:** explicit anonymous sentinel `-1` was stored as `user_id=-1` and hashed as `user:-1`, whereas `deliver()` interprets all non-positive stored users as `public`. This makes otherwise valid public tokens fail their audience-binding check |
| 06 | FR-009, `PLDR_Access::deliver` | Current bearer, audience, rights, object, range source verification present; fixed issuer to match existing delivery truth |
| 07 | FR-001/013, `PLDR_Search::search` | Rights-filtered catalog, signed cursor and bounded filters present; actual DB scale/sort testing pending |
| 08 | FR-001/010, `PLDR_Reader::library_html` | Catalog filters and next-page navigation present; browser/RTL/a11y verification pending |
| 09 | FR-013, `PLDR_Search::ocr` | OCR query/cursor source present; lawful provider indexing not verified |
| 10 | NFR-001, `PLDR_REST::register` | Route auth/idempotency source present; real WP capability, nonce/roles/IDOR acceptance pending |
| 11 | FR-003/016, `PLDR_Rights::file_case/decide/appeal` | Actual rights/dispute methods exist; term `takedown` need not be the literal name; real legal journey pending |
| 12 | FR-006, `PLDR_Crypto::encrypt_file` | Authenticated encryption source present; operational master key backup/restore unverified |
| 13 | FR-006/NFR-003, `PLDR_Storage::cleanup_temporary_paths` | Eager + shutdown cleanup source present; real disk-error/rollback exercise pending |
| 14 | NFR-002, `PLDR_Privacy` | Erasure/export/legal hold source present; downstream backup/retention assurance pending |
| 15 | FUT-020, `PLDR_Future_Context` + File05/06 owner plans | Source-only links to owner-managed course/encyclopedia sources; no duplicate canonical store; companion runtime unverified |
| 16 | FUT-021, `PLDR_Future_Corpus` + File16 | Deny-by-default/corpus manifest source present; File16 consumer verification pending |
| 17 | FUT-019, `PLDR_Future_Rooms` + File17 | Source-side messaging/room provider abstraction present; no real provider tested |
| 18 | File21/23, `PLDR_R21_Outbox` | Reliable events and dispatch source present; actual feed/dashboard consumer success unknown |
| 19 | FUT-001..024, `PLDR_Future` | Future24 registration/contract anchors present; evidence of code presence is not all feature journeys accepted |
| 20 | File01/20/25 shared routes/shell and central plan release law | File12-side route/visual owner integration is present; version and WordPress ZIP-root naming conflict plus full staging/live parity still unresolved |

## Defect corrections after the complete source review batch
**R34-D01 — persisted permission enforcement:** `PLDR_Access::can_access_edition` now checks supported read/preview/download/print/offline operation allowlist, and for the three restricted operations uses existing canonical `PLDR_Core::policy_flag` parser; only explicitly true/1 grants are permitted. Invalid historic policy values fail closed and are audited without disclosing user-sensitive content.

**R34-D02 — public projection truth:** `PLDR_Core::public_document_dto` now displays `permissions.download/print/offline` true only when the same canonical parser returns exactly 1. Text `'false'`, `'off'`, malformed or missing legacy values cannot be advertised as granted.

**R34-D03 — explicit anonymous token binding:** `PLDR_Access::issue_token` preserves `user_id<0` as the explicit anonymous security-check identity, but stores the issued grant with `user_id=0` and matching public audience/hash, as `deliver()` requires. Authenticated user grants retain bound positive user ID/hashes.

**Real regression fixture:** `tests/test-r34-access-grants.php` runs actual `PLDR_Core::public_document_dto`, `PLDR_Access::can_access_edition`, and `PLDR_Access::issue_token` against a deterministic WordPress/DB stub with legacy false/valid true values across download/print/offline, invalid operations, anonymous public and authenticated signed audience hashes. Wired into all PHP 8.1, 8.3, 8.4 CI jobs. Prior 50 real ingest cases, R32 real PDF validator, R33 rights flags, R29 catalog and R30 policy tests remain.

## Release and owner truth boundaries
- Software release candidate **`1.1.0-rc.13`**, no R34 source DB/schema/contract changes (all remain `1.1.0`). A new candidate version is necessary because source bytes differ from R33's rc.12.
- **Plan-vs-package folder conflict still pending Founder change control:** written File12 package root `12-pdf-library-foundation` vs source install ZIP inner root `pdf-library-foundation-12`. Never silently rename existing plugin, which can strand WordPress site data and active plugin identity.
- Need actual deployed File12 ZIP/active version/checksum, physical DB tables/columns/rows, `wp_options` and migration state, PHP/runtime logs/config/providers/dependencies; actual installed Files00/01/05/06/16/17/19/20/21/23/24/25/26/CF04; rights/IDOR/anonymous/doctor/guardian cases, PDF scanning/key restore, browser RTL/a11y/offline, backup/restore/rollback and two fresh staged review cycles. Founder approval precedes merge/live deployment. A green PR is **not** Live verification.

## Mandatory five-field reality report
**Repository HEAD:** R34 Draft PR #26 latest exact HEAD; `main` is still `2b369ef2d609247896e1ab32118f1dc498407f51`.  
**Deployed Version:** UNVERIFIED.  
**DB Version:** UNVERIFIED; source target 1.1.0 only.  
**Migration State:** UNVERIFIED.  
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
