# File 12 — R36: New 20-round Code/Plan Review and Private Reader Parity Corrections

**Date:** 11 October 2026. **Exact frozen source baseline:** Draft R35 PR #28 SHA `90dd4768ded28842b93c3b953a48e0a747f32890`. **Divergent alternative** Draft R35 PR #29 SHA `834d635aa70d7c2de78fe0e6b54d67d3cca3d11e`. Both separately green but carry different code under `1.1.0-rc.14`. No assumption that either is the deployed WordPress plugin; repository `main` at review start is still `2b369ef2d609247896e1ab32118f1dc498407f51`.

## Direct governing basis and limitations
Physically inspected the mounted original Word DOCX `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx`, including:
- **F12-FR-012**: "Bookmarks/private notes — Page anchors، note text، tags، export/delete; never public by default." Invalid, unauthorized and degraded journeys are explicit acceptance conditions.
- **F12-FR-011**: private cross-device reading progress/clear/export.
- **F12-NFR-001/002/003/007**: per-object/field authorization, private lifecycle/export/erasure, reliability and migration/rollback.
- Full 19 FR, 10 NFR and 24 Future enhancement requirement IDs. The published plan requires WordPress packaging root `12-pdf-library-foundation`, while existing source packages `pdf-library-foundation-12` (unsafe to silently rename).

Also inspected actual attached `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`, companion File05 Learn, File06 Encyclopedia, File21 Feed and File25 UI source plans; and in-source native owner hooks for File00/01/16/17/19/20/23/24/26/CF04. Other repositories' **exact latest HEADs and deployed source/runtime** were NOT verified by File12 code anchors.

Twenty entries below are **distinct focused source/plan checks frozen before corrective work**, not twenty complete deep reviews of every source line nor 20 WordPress/staging E2E runs. The retained R28 suite separately runs 20×(53 governed requirement IDs + 12 cross-owner source anchors) = **1,300 static checks**, plus 20 supplemental source invariants. Anchors do not demonstrate live functional acceptance.

## Twenty source reviews and frozen findings
| Review | Requirement/owner focus | Actual source area | Finding |
|---:|---|---|---|
| 01 | F12-FR-001 canonical 16 types/categories | `PLDR_Core::DOCUMENT_TYPES/CATEGORIES` | Source taxonomy present; populated DB and cross-search unverified |
| 02 | F12-FR-002 genuine PDF upload/cover | `PLDR_Ingest::validate_pdf & cover preflight` | Physical PDF/size/header/cover preflight source and R31/R32 fixtures retained |
| 03 | F12-FR-003/004 rights, edition provenance | `PLDR_Rights + PLDR_Ingest` | Source licensing/version safeguards present; real legal evidence pending |
| 04 | F12-FR-005 deduplication | `PLDR_Ingest::similar_candidate` | Hash/similarity/source paths present; actual historical dataset pending |
| 05 | F12-FR-006 encryption and range | `PLDR_Crypto::encrypt_file/stream_range` | Private encrypted object source; real key escrow/restore pending |
| 06 | F12-FR-007 previews/OCR | `PLDR_Ingest derivative code` | Conditional OCR/derived rights source; scanners and OCR provider pending |
| 07 | F12-FR-008 persisted access rules | `PLDR_Access::can_access_edition and Core DTO` | R34 legacy false/off/no parsing retained on chosen PR28 baseline |
| 08 | F12-FR-009 audience/object-bound token | `PLDR_Access::issue_token/current_delivery_binding` | Explicit anonymous-0 public token and detached binding code retained |
| 09 | F12-FR-010 inline reader | `PLDR_Reader::reader_html, REST manifest` | Source reader controls/pregrant present; RTL, accessibility and actual browser pending |
| 10 | F12-FR-011 private progress | `PLDR_Reading::save_progress` | Optimistic revision and event source; real device race and staging pending |
| 11 | F12-FR-012 private note/tag writer | `PLDR_Reading::add_item` | Malformatted input fail-closed previous R34/R35 fixes retained |
| 12 | F12-FR-012 private historic-tag native reader | `PLDR_Reading::items` | Previous PR28 validated flat string-list tags; integrated shared predicate |
| 13 | F12-FR-012 private historic-tag REST collection | `PLDR_REST::reading_items` | R36-D01: only is_array check accepted associative/nested/overfull/typed stored tags; fixed shared predicate |
| 14 | F12-FR-012 owner-scoped item deletion | `PLDR_Reading::delete_item` | R36-D02: wpdb::delete=0 produced true, incorrectly reporting successful private erasure; fixed |
| 15 | F12-FR-013/014 metadata/OCR/citation | `PLDR_Search / PLDR_REST / PLDR_Reader` | Facets/signed pagination/citation hooks exist; real OCR/indexing pending |
| 16 | F12-FR-017/018/019, NFR-002 rights/preservation/privacy | `PLDR_Rights, PLDR_Privacy, Future modules` | Source appeals, audit, erasure/legal hold and preservation; actual key/backup/a11y pending |
| 17 | F12-FUT-001..024 governed future suite | `PLDR_Future + IIIF + A11y` | 24 source IDs and adapter anchors present, not 24 human-accepted experiences |
| 18 | File05/06/16 native knowledge/corpus owner | `PLDR_Future_Context / Future_Corpus` | Owner-respecting source hooks; actual companion installed providers unverified |
| 19 | File17/19/21/23 messaging/feed/publishing | `PLDR_Future_Rooms / PLDR_R21_Outbox` | Source-side room and event hooks; real outbox consumer contract unverified |
| 20 | File00/01/20/24/25/26/CF04 & release parity | `PLDR_Core/rights/schema/storage/workflow` | R36-D03: R35 PR28 and PR29 differ despite same rc.14; new rc.15; plugin folder incompatibility and live parity unresolved |

## Root cause and batch remediation after twenty reviews

**R36-D01 — REST private reading collection did not apply historical tag integrity policy.** R35 PR28's native `PLDR_Reading::items` already rejected nested, associative, non-string and more than 30 historical stored tags; however `PLDR_REST::reading_items` only required `is_array(json_decode(...))`. A well-formed JSON *map*, nested list or mixed-type list could be returned through the paginated API despite the private reading state owner's stricter contract. Added **`PLDR_Reading::valid_stored_tags`**, one shared list-only/string-only/max-30 predicate; native and REST callers now use exactly this predicate, audit `reading_item_tags_corrupt` and fail before returning partial private state.

**R36-D02 — false private-note deletion success.** `PLDR_Reading::delete_item` returned `false !== $wpdb->delete(...)`. WordPress's database delete returns **0** when no owned item was deleted, and this code falsely said true. Changed to **strict exactly-one-row deleted** and denied invalid/nonpositive user/item identifiers before the DB call. The separately authoritative versioned `PLDR_REST::delete_reading_item_owned` already verified affected row count and optimistic version, so its behavior remains unchanged.

**R36-D03 — different source builds under same rc.14.** R35 PR28 and PR29 are divergent Draft candidates. Selected PR28 as baseline due to unique historical private-tag runtime fixture/read guard, while preserving its legacy permission, anonymous token and real reader protection lineage. Source advanced to new `1.1.0-rc.15` software/ZIP/workflow/document markers. Repository DB/schema and integration contract **remain `1.1.0`** (no R36 SQL migration). A green earlier PR28/PR29 cannot prove the corrected HEAD.

**New actual PHP fixture:** `tests/test-r36-private-reader-rest-parity.php`, based on PR28's prior actual reader fixtures, loads true `PLDR_Reading` and `PLDR_REST` classes with an isolated WordPress DB/request stub; checks invalid and valid private tags through both read endpoints, strict deletion outcomes, user ownership, and before-write negative checks. It is wired to all PHP 8.1/8.3/8.4 CI jobs, plus the earlier R28–R35 fixtures and deterministic source checksums/ZIP gate.

## Remaining acceptance conditions (not code completion claims)
1. Freeze actual Hostinger deployed active plugin version/PHP/ZIP SHA and plugin folder, physically deployed files, DB/schema/table rows/`wp_options` and migration version/lock, runtime logs/config and keyring. Deployed version, DB version and migration state are still unverified; repository code does not imply production parity.
2. Actual installed partner contracts File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04; test guardian, doctor/reader entitlements, suspended users, private IDOR, logout, File24 export/erasure, File26 index, outbox consumers and clinical patient-case consent.
3. Genuine WordPress/Hostinger staging: malformed historic private JSON rows through native/private REST readers, AJAX/REST positive/negative deletion, PDF upload/scanner/OCR, key backup/restore, rights revocation/GET+HEAD/ranges, browser Urdu/Arabic RTL, offline/keyboard/screen reader and accessibility.
4. Founder-approved plugin folder migration decision, backup/rollback and two new staging review/fix cycles before controlled deployment, live re-test and exact package SHA parity. No `main` merge or production action is authorized by this source QA.

## Five production-truth fields
**Repository HEAD:** check final R36 immutable SHA and exact SHA CI.
**Deployed Version:** UNVERIFIED.
**DB Version:** UNVERIFIED (source contract 1.1.0 only).
**Migration State:** UNVERIFIED.
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
