# File 12 R41 — Twenty distinct governing-plan/source reviews and cross-owner checks
**Date:** 2026-10-11, Pakistan. **Scope:** GitHub repository source, not Hostinger or staging.

## Frozen evidence and plan precedence
- Source parent R40 Draft PR #35 exact SHA `b1338d4fb7d041cc6c46c3d7bac4814a8f07b584`, GitHub Actions #488 green (PHP 8.1/8.3/8.4 plus package). GitHub `main` is bootstrap-only `2b369ef2d609247896e1ab32118f1dc498407f51`. Repository source is **not** automatically deployed source.
- Original uploaded Word plan **`12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx`** physically inspected: exactly 19 mandatory File12 FR IDs, 10 NFR release gates and 24 Future requirements. Specifically, FR-001 catalog, FR-008 access, FR-010 reader, FR-012 bookmarks/private notes, NFR-001 field/object auth, NFR-002 privacy and NFR-004 bounded query semantics.
- Original uploaded **`Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`** inspected: central canonical ownership, public/private noindex/no-cache and File00 claims, File20 shell, File25 visual tokens, File12 owning document/edition/rights/reading, File05 courses, File06 encyclopedia, File21 feed/interaction, File23 publishing and File16 AI answer boundaries. Related uploaded File05 Learn, File06 Encyclopedia, File21 Home + News, File23 Publishing Dashboard and File25 Public UI/Timeline plans inspected for linkage intent. Their **installed plugin versions and actual linked behavior remain unverified**.
- Reviewed **20 separately scoped requirement/source focal paths**, not 20 independent full line-by-line audits, 20 production journeys or 20 staged review/fix cycles. The older R28 20×(53 File12 FR/NFR/FUT ID anchors +12 File12-side adapter anchors) =1,300 *static presence checks*, not feature/production acceptance.

## 20 targeted R41 source/plan paths (all source anchors found; presence does not establish E2E correctness)

| Pass | Actual plan owner requirement and source inspected | Evidence / qualified result |
|---:|---|---|
| **01** | **F12-FR-001, NFR-004 catalog REST route; `PLDR_REST::register`** | **R41-D01:** WordPress REST `page/per_page` args had `sanitize_callback='absint'`, changing `-2` to `2` before R40 strict `PLDR_Search::search` boundary. Removed preemptive numeric sanitizer so raw values reach canonical validator; actual route registration and REST catalog method regression added |
| 02 | F12-FR-001 type/category taxonomy, `PLDR_Core::DOCUMENT_TYPES` | Sixteen governed catalog metadata/type source mapping still present; actual catalog index, facets, RTL and load tests not performed |
| 03 | F12-FR-002 genuine PDF upload, `PLDR_Ingest::validate_pdf` | R31/R32 real-method/physical sample source tests retained; scanner/Hostinger HTTP upload unverified |
| 04 | F12-FR-003 lawful rights/source expiry, `PLDR_Ingest::date_or_null` | R40 canonical strict real ISO calendar validator retained; actual publisher/license provenance and live rows unverified |
| 05 | F12-FR-004 edition and canonical family, `PLDR_Core::current_edition` | Source revision/edition identifier behavior exists; physical current production DB unknown |
| 06 | F12-FR-005 exact hash/different scan, `PLDR_Ingest` | Hash, duplication and distinct-scan confirmation source retained; actual OCR/corpus corpus not tested |
| 07 | F12-FR-006 authenticated encrypted object, `PLDR_Crypto` | Encryption/key envelope source present, provider backup/restore/rotation unverified |
| 08 | F12-FR-007 cover/thumbnail/OCR derivatives, `PLDR_Ingest::store_cover` | Source cover preflight/checksum and derivative path; providers and real delivery unverified |
| 09 | F12-FR-008 audience/embargo flags, `PLDR_Access::update_policy` | Canonical stored flag and calendar rights fixes preserved; File00 installed roles/entitlement provider unknown |
| 10 | F12-FR-009 signed operation/object delivery, `PLDR_Access::current_delivery_binding` | Source binding/expiry/revocation present; real HTTP/Range/IDOR/keys unknown |
| **11** | **F12-FR-010 reader manifest; `PLDR_REST::reader_manifest`** | **R41-D02:** unlike R38 GET handlers, `reader_manifest` converted malformed, negative, boolean and nested edition values with `absint` before DB lookup. Same strict early `PLDR_REST::validate_query_shape` invoked before any edition access/read |
| 12 | F12-FR-011 private optimistic reading progress, `PLDR_Reading::save_progress` | Signed private optimistic revision/source path retained; real cross-device staged race pending |
| **13** | **F12-FR-012, NFR-002 historic private note tags; `PLDR_Reading::parse_stored_tags`** | **R41-D03:** `json_decode($json,true)` represented a valid empty list `[]` AND forbidden empty JSON map `{}` as the same PHP array. Parser now decodes preserving JSON Object vs Array identities; only lists of ≤30 strings accepted |
| 14 | F12-FR-012 paginated private reading REST, `PLDR_REST::reading_items` | Same canonical strict parser called from native/REST reading, both deny historical `{}` and nested/maps, no partial private DTO; real method regression added |
| 15 | F12-FR-013 lawful access-filtered OCR, `PLDR_Search::ocr` | R38 query/curation filters retained; File26 federated index/OCR provider staged parity unknown |
| 16 | F12-FR-014 stable edition/page citations, `PLDR_Reader::citation` | R38 immutable edition ID and basic style truth retained; citation reader copy/paste and external File06 scholarly consumer unverified |
| 17 | F12-FR-015 File05/06 linked canonical context, `PLDR_Future_Context::lookup` | File12 consumer adapter/source boundary exists; real File05/06 installed data API versions unknown |
| 18 | F12-FR-016 File21/23 feed/reader interactions, `PLDR_R21_Outbox` | Source event dispatch/outbox exists; File21/23 consumer ack/version deployed parity unverified |
| 19 | F12-FR-017 takedown, restricted case and appeal, `PLDR_Rights::appeal` | Rights source machine/appeal flow present; real rights team/notifications/provider/roles unverified |
| 20 | F12-FR-018/019, NFR-002/007 privacy/reading accessibility/migration, `PLDR_Privacy::erase`, `PLDR_Schema::maybe_upgrade` | Owner source/adapter present, actual physical privacy export/erase/rollback, screen reader/OCR-quality/RTL, migration state **unverified** |

## Source correction batch and executable quality gate
After freezing the 20 scope areas, three **proven source defects** were corrected:
- **R41-D01:** remove pre-handler WP REST `absint` for catalog `page/per_page` so R40 fails negative and decimal requests before DB, instead of WordPress normalizing them to another catalog page. Actual `PLDR_REST::register` route metadata verified and `PLDR_REST::library` called with malformed values, no DB read.
- **R41-D02:** `reader_manifest` applies the existing R38 `validate_query_shape` to `edition` first; real malformed method calls check `pldr_query_input_shape` and no DB reads. Authorized valid manifest flow stays under R37 tests.
- **R41-D03:** shared native+REST private stored tags decoder uses nonassociative `json_decode` to preserve empty JSON Object `{}` versus valid empty list `[]`. Invalid map/nested/scalar rejects; valid indexed string lists stay accepted. Real `PLDR_REST::reading_items`, `PLDR_Reading::items` and `PLDR_Reading::parse_stored_tags` tested.
- New **`tests/test-r41-rest-route-private-tags.php`** executes real PHP methods and actual REST route registration in isolated WordPress mock fixture (reuses R36 fixture). Runs on CI PHP8.1/8.3/8.4 with every old R28–R40 test and frontend/package.
- Unique source/package software release candidate **`1.1.0-rc.20`**; source DB/schema/integration contract stays **`1.1.0`** with no new migration.

## Release/Hostinger reality — not independently verified
- Plan WordPress plugin inner root `12-pdf-library-foundation` remains different from source/install build `pdf-library-foundation-12`: **Founder-approved** compatibility, migration, backup and rollback required. No silent rename/deploy.
- Exact active Hostinger File12 plugin version/files/package checksum, DB tables/columns/rows/`wp_options`/migration, PHP errors/keyring, true File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 installed artifacts and adapters, real OCR/malware scan, privacy/no-cache, actual WordPress REST sanitation, signed token and browser/RTL/a11y/offline acceptance remain UNVERIFIED.
- Two fresh staged review/fix cycles, restore/rollback exercises, Founder release sign-off and final live post-deploy retest NOT PERFORMED. Source green cannot mean site complete.

## Five mandatory independent evidence fields
**Repository HEAD:** R41 Draft PR #36 final SHA and matching completed Actions/ZIP proof; GitHub main bootstrap `2b369ef2d609247896e1ab32118f1dc498407f51`.  
**Deployed Version:** UNVERIFIED.  
**DB Version:** UNVERIFIED (source target 1.1.0 only).  
**Migration State:** UNVERIFIED.  
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
