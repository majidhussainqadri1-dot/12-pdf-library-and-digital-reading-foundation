# File 12 R37 — twenty governing-plan/source inspection foci and exact source corrections
Date: 11 October 2026, Pakistan. Candidate SOFTWARE 1.1.0-rc.16; DB schema + interface contract 1.1.0 unchanged.

## Evidence freeze and source hierarchy
- Original uploaded `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx`: exactly 19 F12-FR, 10 F12-NFR and Future24 governing requirements, including F12-FR-008 access grants, F12-FR-010 reader interface, F12-FR-012 private bookmarks/notes/tag delete and F12-NFR-001/002 access/privacy, NFR-007 controlled release/migration.
- Uploaded `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`: canonical owner controls source of truth, consumer projections must match actual rights/role checks, private reading state never public/index/analytics, backup/rollback and strict live/deployed truth.
- Related uploaded File05 Learn, File06 Encyclopedia, File21 Home+News Feed, File23 Doctor/Founder Publishing Dashboard and File25 UI plans. File12 may expose **source-only** links/adapters but never claims deployment parity or duplicates native owner data.
- Frozen base **R36 Draft PR #31** `eeafd8b29189a13dd961a96a3bfa3cd80625c9da` (Actions #462 green). Actual GitHub main `2b369ef2d609247896e1ab32118f1dc498407f51` bootstrap-only, NOT deployed Hostinger truth.
- **Divergent R36 Draft PR #30** `8dd37ee8d08d9fcdbc3d5187da76b8f73c94cdfa`: another different code candidate *also labelled* rc.15 and built from R35. Its actual native deletion fix was independently inspected and reconciled on top of PR31's more comprehensive REST/privacy/rights source. No silent PR merge, no main or live deployment.

## Twenty distinct scoped source-plan inspections — frozen before fix batch
Twenty different source paths/requirements inspected on exact R36 PR31 HEAD. **Not twenty exhaustive end-to-end user journeys, twenty full line-by-line manual source audits or twenty live/staging tests.** Automated previous R28 retained a separate 20×(53 File12 FR/NFR/FUT requirement IDs + 12 source-side companion adapter anchor IDs) = 1,300 static presence checks plus 20 invariants. Presence ≠ operational completeness.

| Review | Source path and File12/central/companion requirement | Actual finding, not an invented implementation claim |
|---:|---|---|
| 01 | F12-FR-001 16 types/categories; `PLDR_Core::DOCUMENT_TYPES` | Source catalog owner exists; live indexing/filters unverified |
| 02 | F12-FR-002 ingest validated PDF; `PLDR_Ingest` | R32 physical-byte/format checks retained; real scanner provider unverified |
| 03 | F12-FR-003 source/rights evidence | Rights provenance fields source-present; copyright evidence lifecycle unverified |
| 04 | F12-FR-004 version/edition | Optimistic document-family conflict owner code present; DB migration unverified |
| 05 | F12-FR-005 duplicate and scan confirmation | Hash/duplicate and canonical confirmation parser present; historic catalog parity unknown |
| 06 | F12-FR-006 encrypted private storage | AES-GCM and signed range source; actual keys, disk/backup/restore unverified |
| 07 | F12-FR-007 cover/http uploads | R32 preflight code retained; real derived scan/OCR provider unverified |
| 08 | F12-FR-008 access/read/download/print/offline | R33/R34 `PLDR_Access::can_access_edition` canonical `policy_flag` denies historic `'false'`/`'off'` persisted flags; compare all consumer presentations |
| 09 | F12-FR-009 signed audience/object binding | R34 anonymous public hash and R36 signed REST guard source retained; real HTTP revocation pending |
| **10** | **F12-FR-010, F12-FR-008, File25 UI: `PLDR_Reader::document_html` JS bootstrap** | **R37-D02:** Reader HTML/JS config `canDownload/canPrint/canOffline` used PHP `!empty()`; stored `'false'`/`'off'` could advertise allowed operation while Access would deny. Fixed exact canonical parser `PLDR_Core::policy_flag(...)===1`; assertion of source boot config invariant, live browser acceptance pending |
| 11 | F12-FR-011 cross-device reading progress; `PLDR_Reading::save_progress` | Optimistic revision/event source and R36 REST strict pages retained; staging race test unverified |
| **12** | **F12-FR-012 native private delete: `PLDR_Reading::delete_item`** | **R37-D01:** `$wpdb->delete()` returns integer 0 on nonexistent/wrong-owner row and `false` on SQL error; old code `false !== 0` misleadingly returned true even if no note was deleted. Guard invalid IDs and require exactly 1 owned affected row. Real 0/1/DB-error/no-side-effect test |
| 13 | F12-FR-012 REST deletion: `PLDR_REST::delete_reading_item_owned` | Different REST owner path already versioned and exact-one-row, retained; no unapproved migration |
| 14 | F12-FR-012 historic private tag read: `PLDR_REST::reading_items` | R36 strict shared `PLDR_Reading::parse_stored_tags` retained, real corrupt-tag source QA retained |
| 15 | F12-FR-013 catalog/OCR cursor and File26 | Bound search cursor source retained; actual File26 installed/search index/access parity unverified |
| 16 | F12-FR-017 reports/appeals and File19 events | `PLDR_Rights::file_case/decide/appeal`, R36 REST validation present; legal workflow and event consumers unverified |
| 17 | F12-NFR-002 privacy export/erase/legal hold | `PLDR_Privacy` source present; real retention, GDPR/health data exports and backups unverified |
| 18 | File05/06 Future contextual links | File12-side `PLDR_Future_Context` File05/06 owner adapter anchors present; installed course/encyclopedia truth unverified |
| 19 | File16/17/19/21/23/25 companion owner contracts | Source adapters for corpus, rooms and reliable File21 event outbox present; real deployed native owners and visual/a11y acceptance unverified |
| 20 | F12-NFR-007 source DB schema and upgrade | `PLDR_Schema::maybe_upgrade` source present; actual Hostinger DB tables/options, staged restore and plugin folder mismatch unverified |

**Additional projection inspection under review 08/10** — separate REST `PLDR_REST::reader_manifest`:
- **R37-D03** reader manifest `permissions.download/print/offline` used `!empty()` and contradicted real `PLDR_Access` and fixed R34 `PLDR_Core::public_document_dto` on historical `false`, `off`, malformed and null values. Changed to require parser exactly 1. This is part of the 20 scoped source inspections, not an invented 21st full audit.
- **R37-D04 (release identity)** two distinct R36 PR30/31 both named software rc.15; R37 uses exact R36 PR31 as parent and selectively includes PR30 native deletion truth without discarding PR31 strict REST/rights fixes. New unique candidate rc.16; no new schema/contract migration.

## Actual source corrections and regression evidence
- `PLDR_Reading::delete_item` native source returns success **only** for `1 === $wpdb->delete(...)`, owner-scoped by item + user; invalid user or item short circuit.
- `PLDR_Reader::document_html` JS bootstrap and `PLDR_REST::reader_manifest` both now use the same canonical explicit right parsing as `PLDR_Access::can_access_edition` and `PLDR_Core::public_document_dto`.
- New `tests/test-r37-reader-permission-delete.php` calls **actual `PLDR_REST::reader_manifest` with the real `PLDR_Core::policy_flag`** on multiple historic true/false/malformed values, calls actual `PLDR_Reading::delete_item` with 0 affected, one affected and DB error/no-ID owner case, and asserts JS source boot config uses same parser. JS source check is NOT an end-to-end browser render.
- CI runs new test across PHP 8.1, 8.3, 8.4, plus retains real R36 paginated privacy/rights REST tests (76), R35 native privacy (61), R34 token access (66), R33 rights flag (109), R31 ingestion (50), R32 physical PDF (5), R28 1,300 static repeated requirement/adapter checks and preceding crypto/range/search/rights tests. All expected results subject to final immutable SHA CI.
- Source-only software `1.1.0-rc.16`; database schema target and external contract stay `1.1.0`. No DB migrations or plugin folder renames proposed.

## Strict release boundary and outstanding owner integration
- Written plan inner WordPress plugin ZIP folder `12-pdf-library-foundation` vs present source/build inner folder `pdf-library-foundation-12`; migration and compatibility decision, backup/rollback mandatory from Founder, NOT silently changed.
- Deployed File12 active plugin build/version/checksum, real DB table/column/row/wp_options/schema/migration logs/config/key ring, malware/OCR providers and exact other installed File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 code and consumer event-contract versions **NOT VERIFIED**.
- WordPress staged role/guardian/suspension/anonymous/privacy ownership/IDOR/permission UI, real PDF upload/reader, RTL/320px/400% zoom/keyboard/assistive tech/low bandwidth/offline, backup/recovery/key restore and two fresh staged review cycles **NOT RUN**. No merge to main, no live deploy, no Founder signoff or live re-test.
- A source anchor or successful CI does not establish production completeness.

## Five-field required live-first truth ledger
- **Repository HEAD:** new R37 Draft PR #32 exact final SHA and matching completed GitHub Actions; GitHub `main` `2b369ef2d609247896e1ab32118f1dc498407f51`.
- **Deployed Version:** UNVERIFIED.
- **DB Version:** UNVERIFIED; source target 1.1.0 only.
- **Migration State:** UNVERIFIED.
- **Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
