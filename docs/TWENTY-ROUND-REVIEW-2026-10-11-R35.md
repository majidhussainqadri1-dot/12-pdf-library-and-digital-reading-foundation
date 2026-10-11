# File 12 R35 — New 20-focus Governing Plan, Code and Cross-owner Audit

**Date:** 2026-10-11, Pakistan local time. **Exact frozen base:** Draft R34 PR #27 HEAD `79ef7659ba816f7959313ddf4ded047236685202`. **Unique divergent sibling:** Draft R34 PR #26 HEAD `335952e2f7b47cd7bf95cd184cfa6de7059917ea`. Both green only for own source HEAD and both named software rc.13. `main`: `2b369ef2d609247896e1ab32118f1dc498407f51`, initial README-only; neither main nor either PR establishes actual Hostinger deployed plugin/DB.

## Governing evidence
Reviewed the actually uploaded `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx`, the `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx` and companion File05 Learn, File06 Encyclopedia, File21 Home/News Feed, File23 Publishing Dashboard, and File25 Public Profile/UI plans. File12 is canonical for PDF objects, reader and rights, reading progress, bookmarks/private notes; cross-module consumers must use native versioned interfaces/events and not silently duplicate or override ownership. File12 tables include 19 functional (`F12-FR`), 10 nonfunctional (`F12-NFR`) and 24 future (`F12-FUT`) IDs. File00/01/16/17/19/20/24/26/CF04 were checked only through File12's source-side owner-provider hooks, **not** their independent current HEAD or installed integrations.

**Scope discipline:** 20 genuinely separate *focused* requirement/source inspections were completed and their findings frozen *before* the corrective code changes. This is **not** twenty independent full-depth, every-line project-wide reviews or twenty real WordPress/staging/live end-to-end tests. Existing `tests/test-r28-twenty-full-plan-sweeps.php` separately executes twenty repeated **static source-presence** passes over 53 requirement IDs + 12 adapter anchors (1,300 markers plus 20 invariants). Static presence is not acceptance.

## 20-round frozen finding register

| Round | Requirement and owner boundary | File examined | Finding |
|---:|---|---|---|
| 1 | FR-001 canonical document, categories, metadata | `class-pldr-core.php` | DOCUMENT_TYPES / CATEGORIES / public_document_dto: source present; DB data unverified |
| 2 | FR-002 lawful PDF upload, signature, cover | `class-pldr-ingest.php` | validate_pdf / plird? physical filesize & cover preflight preserved; actual multipart unverified |
| 3 | FR-003 document-rights provenance/case | `class-pldr-rights.php` | file_case / decide / appeal source present; independent evidence review pending |
| 4 | FR-006 encrypted objects, authenticated byte ranges | `class-pldr-crypto.php` | encrypt_file / stream_range / verify_file; real key rotation/restore unverified |
| 5 | FR-008 published permissions: historical string records | `class-pldr-access.php` | R35-D01 PROVEN: PHP empty('false') did not deny download, print, offline when DB legacy string present; fixed |
| 6 | FR-008 public reader DTO permissions | `class-pldr-core.php` | R35-D02 PROVEN: !empty('false') projected public allowed=true inconsistent with intended denied state; fixed |
| 7 | FR-009 explicit anonymous signed grants | `class-pldr-access.php` | R35-D03 PROVEN: sentinel -1 stored as token user_id=-1 with user:-1 hash; deliver verifies public, causing mismatch; fixed |
| 8 | FR-009 current edition/object binding & revocation | `class-pldr-access.php` | deliver/current_delivery_binding/revoke_document present; real races pending |
| 9 | FR-012 private notes/bookmarks, anchors and tags | `class-pldr-reader.php` | PR27 R34 malformed nested input and tags hardening retained; actual WordPress users pending |
| 10 | FR-011 reading progress concurrency | `class-pldr-reader.php` | save_progress and optimistic conflict plus outbox present; real simultaneous actors pending |
| 11 | FR-013 catalog/OCR signed cursors | `class-pldr-reader.php` | search, ocr, decode_catalog_cursor paths present; File26 index propagation pending |
| 12 | FR-017 rights appeals, withdrawal and policy | `class-pldr-rights-policy.php` | rest_guard/admin_decision_guard/check present; real legal workflows pending |
| 13 | NFR-002 privacy export, erasure, legal hold | `class-pldr-privacy.php` | export, erase, legal_hold paths; actual retention backup/erasure pending |
| 14 | NFR-007 DB schema upgrade and migration lock | `class-pldr-schema.php` | maybe_upgrade/migrate_legacy_batch/acquire_migration_lock present; installed DB/options unknown |
| 15 | FUT-001..024 governed advanced reading | `class-pldr-future.php` | F12-FUT-001 and 024 plus reader_controls present; device/a11y/IIIF journeys pending |
| 16 | File05/06 course/encyclopedia canonical links | `class-pldr-future-context.php` | File05/06 source-side knowledge provider hook present; current peer build absent |
| 17 | File16 AI corpus rights and patient-case privacy | `class-pldr-future-corpus.php` | explicit corpus/consumer/patient_case gates source present; external AI owner untested |
| 18 | File17 rooms external messaging ownership | `class-pldr-future-rooms.php` | provider and source_bound references exist; actual room service untested |
| 19 | File19/21 reliable events, File23 publications | `class-pldr-r21-outbox.php` | dispatch/retry/dead_letter source present; actual events accepted by peers untested |
| 20 | File01/20/25 shell routes, File24/26/CF04 contracts; release | `class-pldr-rights.php + workflow` | route, slot, component hooks present; R35-D04 two different rc.13 sources and unverified package folder/staging |

## Source root causes and corrective batch after all inspections

**R35-D01 (security):** previously stored/imported rights values such as `download_allowed='false'`, `print_allowed='off'`, or `offline_allowed='no'` could pass `PLDR_Access::can_access_edition()` because PHP `empty('false')` is false (not empty), so the method failed to block a disabled right. Mutation-time R33 normalization did not protect old/unmigrated records. Restored and verified PR26's exact supported operation allowlist and read-time `PLDR_Core::policy_flag` 1/0/null decision for all three modes, auditable denial for invalid stored grants.

**R35-D02 (DTO permission truth):** `PLDR_Core::public_document_dto` similarly used `!empty()` for `permissions.download/print/offline`, displaying legacy `'false'`/garbage as granted. Corrected DTO projection to `1===PLDR_Core::policy_flag(...)`, aligned with enforcement. Verified PR26's independently green fixture source.

**R35-D03 (signed anonymous grant correctness):** `PLDR_Access::issue_token` could receive explicit anonymous sentinel -1. Its old path kept -1 as persisted user_id and derived audience hash `user:-1|edition|operation`. Later `deliver()` interprets nonpositive user_id as `public`, producing binding mismatch and rejecting otherwise authorized public grant. Reconciled issuer so authorization keeps the explicit -1 sentinel, while token storage and audience hash use canonical public user_id=0; named user grants remain bound to the real user.

**R35-D04 (release identity):** R34 Draft PR26 and PR27 have different code while both report candidate rc.13. R35 preserves PR27's important private note/tag input safety and explicit-null policy handling, selectively merges PR26's unique access/DTO/anonymous grant changes with its real regression fixture, then advances unique software/ZIP/readme/manifest/test identifiers to **`1.1.0-rc.14`**. Source DB/schema and integration contract remain `1.1.0` with no new migration.

**Regression proof required:** `tests/test-r35-access-grants.php` invokes actual `PLDR_Core::public_document_dto`, `PLDR_Access::can_access_edition`, and `issue_token` under a stub WP DB, with legacy false/on, invalid operations, both public and bound-user audience hash checks. Existing `tests/test-r34-reading-item-input-shapes.php` retains actual private-state tests; PHP 8.1/8.3/8.4, R1–R34 regressions, JS/checksums/deterministic package and 20×65 static source matrix must all pass on the **exact unchanged FINAL R35 HEAD**.

## Outstanding non-source and change-controlled gates

- **Deployed artifact and schema**: actual active WordPress plugin release/files/checksum, runtime PHP/WP, real DB tables/columns/rows `wp_options` migration lock/version/options and logs/config/dependencies not retrieved; no staging/live parity claims.
- **Native owners**: actual File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 deployed/installed versions, real entitlement/guardian/suspension/medical/privacy/rights and database ownership and versioned provider contracts still need staged joint validation.
- **End-to-end**: real malformed legacy permission DB fixtures, signed GET/HEAD/ranges, real scanner/OCR, private encrypted object key backup/restore, rights/IDOR, real File26 search, File20/25 RTL/mobile/a11y/offline/browser, outbox consumer delivery, migration and rollback need documented staging QA, two follow-up review cycles and Founder acceptance.
- **Known installation directory discrepancy**: written File12 plan calls ZIP plugin root `12-pdf-library-foundation`; actual candidate retains `pdf-library-foundation-12`. No unsafe silent rename; require Founder-approved migration/upgrade/rollback compatibility.
- Code in a green draft does not mean live fix is deployed, and source-only patches do not authorize `main` merge or live deployment.

## Five-field production/live truth
**Repository HEAD:** verify exact final R35 SHA/CI after latest commit.
**Deployed Version:** UNVERIFIED.
**DB Version:** UNVERIFIED (source target `1.1.0` only).
**Migration State:** UNVERIFIED.
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
