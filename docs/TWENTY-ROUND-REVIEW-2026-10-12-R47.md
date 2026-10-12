# File12 R47 — twenty scoped source/plan checks and File17 Room page/region corrections
**Review date:** 12 October 2026 (PKT). **Frozen inspected source HEAD:** R46 Draft PR #41 commit `4da7fd0c6a4276efd9ba2013113f84fb4a8a3754`, green historical run `38210230586`. `main` SHA `2b369ef2d609247896e1ab32118f1dc498407f51` is README bootstrap only. None is the verified Hostinger deployed artifact.

## Attached governing content used
1. The supplied **File 12 PDF Library and Digital Reading Complete Master Plan v1.1 Future-24 amended 2026-08-11**: 19 FR, 10 NFR and 24 FUT. Clause **F12-FUT-019** says File12 provides only document/page/anchor context while messages/participants/calls belong to canonical File17. FUT017 requires page/layout/zoom/anchor handoff across devices without silent value changes; FUT005 anchors are source-page bound. Data architecture §11.4: `room_contexts` stores document/page/anchor/provider_ref, not chat. §11.5 rechecks current edition rights. §11.6 prohibits private state leaking into public index/cache.
2. Supplied **Sabri Social Homeopathy Platform Three Central Plans Consolidated Governing Master Plan**: File00 is identity and entitlement owner; File05 education, File06 knowledge/citation, File17 communication, File20 shell, File21 publishing, File23 dashboard, File24 security/privacy, File25 visual system, File26 discovery, CF04 media. These companion boundaries are governing intent, not live adapter proof.
3. Earlier R46 correction of native Handoff validates exact positive edition pages and finite normalized region coordinates; the R46 reading-room implementation still had an independent clamping/casting discrepancy.

## Frozen source checks — ALL completed before edits
These **twenty distinct bounded topical inspections** tested specific requirement-to-native-method anchors and separately read FUT019's real page and region code. They are NOT twenty whole-repository, independently complete manual audits, nor real WordPress E2E/staging checks. Presence of a method does not establish full functional completeness.

| Review | Specification and owner | Exact implementation anchor checked | Result |
|---|---|---|---|
| 01 | FR001 File26 catalog and access | `PLDR_Search::search/encode_catalog_cursor` | Source path observed |
| 02 | FR002 CF04 scanned ingest | `PLDR_Ingest::validate_pdf/scan_file` | Source path observed |
| 03 | FR003 File24 rights and provenance | `PLDR_Rights::file_case/decide` | Source path observed |
| 04 | FR004 canonical editions | `PLDR_Core::current_edition/latest_edition` | Source path observed |
| 05 | FR005 deduplication | `PLDR_Future_Fingerprint::candidates/compute_and_store` | Source path observed |
| 06 | FR006 cryptographic original | `PLDR_Crypto encrypt/decrypt` | Source path observed |
| 07 | FR007 lawful OCR/derivative | `PLDR_Ingest::generate_ocr/generate_derivatives` | Source path observed |
| 08 | FR008 File00 access policies | `PLDR_Access::can_access_edition/strict_rights_timestamp` | Source path observed |
| 09 | FR009 signed Range delivery | `PLDR_Access::issue_token/deliver/parse_range` | Source path observed |
| 10 | FR010 File20/25 reader presentation | `PLDR_Reader::reader_html/thumbnail_tokens` | Source path observed |
| 11 | FR011 account-private reading progress | `PLDR_Reading::save_progress/state` | Source path observed |
| 12 | FR012 private item integrity | `PLDR_Reading::add_item/parse_stored_tags` | Source path observed |
| 13 | FR013 File26 OCR search | `PLDR_Search::ocr/encode_ocr_cursor` | Source path observed |
| 14 | FR014 File06 citations | `PLDR_Future_Citations::export` | Source path observed |
| 15 | FR015 File05/06 knowledge links | `PLDR_Future_Context::lookup` | Source path observed |
| 16 | FR016 File21/23 outbox | `PLDR_R21_Outbox::dispatch/register_contracts` | Source path observed |
| 17 | FR017 takedown/appeal | `PLDR_Rights::appeal/temporary_restrict` | Source path observed |
| 18 | FR018/019 preservation and accessibility | `PLDR_Future_Preservation::assess/read + prior File12 A11y owner` | Preservation path observed, A11y owner previously verified |
| 19 | FUT017 File00 private cross-device sessions | `PLDR_Future_Handoff::validate_handoff_input/save` | Strict page and region preflight observed |
| 20 | FUT019 File17 reading-room data contract | `PLDR_Future_Rooms::create/anchor` | R47-D01/D02: page and region clamping/float casts observed before fix |

## Proven source defects and after-review correction batch
- **R47-D01: silent room page rebinding.** In `PLDR_Future_Rooms::create`, `$page=max(1,min((int)$edition['pages'],$page))` changed non-existent page references like 0 or 13 (in a 12-page edition) to another authorized page. FUT019 requires preserving exact **document/page** identity before invoking a separate File17 provider. The corrective gate returns `pldr_room_page` for out-of-edition page references before DB inserts/provider calls.
- **R47-D02: silent room-region rebinding and shape loss.** `PLDR_Future_Rooms::anchor` used nested `(float)` casts and `max/min` to coerce nonnumeric/boolean/negative/>1 coordinates and silently ignored wrong-shaped region values. This differed from the source-validating FUT005 scholarly anchor and newly strict FUT017 Handoff. The corrective `valid_region_coordinate` accepts only explicit finite normalized real numbers (or bounded decimal strings), requires bounded `w/h`, rejects array/object/boolean/null/out-of-range/cross-page regions; stores valid numeric locations without transformation. Unknown/unsafe SVG restrictions from R46 remain.

**Native regression:** `tests/test-r47-reading-room-page-region-integrity.php` invokes the actual File12 `PLDR_Future_Rooms::create/anchor` in the established isolated R46 WP/DB fixture with rejected page cases, malformed/overflow/NaN/INF/nested regions, zero-area and cross-page cases, audit of zero writes on rejected input, and correct valid normalized rectangles. Both standalone source and inherited R46 tests must pass exact-HEAD PHP 8.1/8.3/8.4; complete CI and reproducible package digest **pending until final GitHub Actions result**.

**Software candidate:** `1.1.0-rc.26`. Source DB schema/interface contract `1.1.0` unchanged. No migration or live/staging deployment.

## Mandatory staging, owner and parity gates — STILL UNVERIFIED
- Original document plugin inner path `12-pdf-library-foundation` conflicts with historical build `pdf-library-foundation-12`; explicit Founder release/rollback decision required, no silent rename.
- Hostinger installed active File12 plugin version/files/checksum; WordPress and PHP runtime configuration; physical DB tables/columns/`wp_options`, schema version/migration state and deployed dependency versions File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04; live error logs.
- Staging actual File17 provider page/anchor handoff, current rights and File00 permissions, cross-device privacy/IDOR/concurrent changes, offline vault, mobile/RTL/a11y, scanner/OCR/crypto backup/restore, two fresh staging rounds, Founder acceptance, production deploy and live re-test.

| Required truth | Status |
|---|---|
| Repository HEAD | R46 parent `4da7fd0c6a4276efd9ba2013113f84fb4a8a3754`; R47 final commit and CI require separate exact confirmation |
| Deployed Version | UNVERIFIED |
| DB Version | UNVERIFIED physical DB (source schema 1.1.0 only) |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
