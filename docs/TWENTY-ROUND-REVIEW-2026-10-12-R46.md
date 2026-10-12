# File12 R46 — twenty bounded plan/source checks then Future handoff/room corrective batch
**Date:** 12 October 2026; **exact frozen source parent** R45 draft PR#40 `b5e04a380aef62b7d83d15ef17079e5867d86528` (former exact-head GitHub Actions success on R45 only). GitHub `main` remains bootstrap `2b369ef2d609247896e1ab32118f1dc498407f51`. The new R46 source branch requires its own immutable CI, ZIP integrity and release decision.

## Governing source language
- Original supplied **12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11**: FR001–019; NFR001–010; 24 proposed features. **F12-FUT-005** scholarly anchors are private and tied to edition/page/text-quote/region. **F12-FUT-017** cross-device handoff must preserve `page/layout/zoom/anchor` and optimistic conflict semantics. **F12-FUT-019** reading-room should pass only `document/page/anchor` context to the canonical communications owner; File12 must not create a parallel chat backend.
- Supplied **Three Central Plans Consolidated Governing Master Plan**: File00 identity and entitlement owner; File05 course/lesson content; File06 encyclopedia/citations; File17 communication/messaging owner; File20 unified shell; File21 canonical posts; File23 publishing; File24 privacy/security; File25 public visual system; File26 search; no duplicate ownership. Companion owner contracts are source-intent; actual deployed adapter parity unverified.
- Twenty **bounded topical requirement-to-source checks** were carried out at the frozen R45 commit, each with concrete PHP source/behavior anchor. Rounds 1–18 mainly checked presence and relevant source rules; rounds 19–20 compared actual coercion/selector logic. This is **not** twenty comprehensive, independent full code reviews, nor live WordPress E2E.

## Frozen twenty checks before fixes
| # | Governing area | Actual inspected source | Frozen finding |
|---|---|---|---|
| 01 | FR001; File26 catalog | `PLDR_Search::search / encode_catalog_cursor` | present |
| 02 | FR002; CF04 PDF ingest | `PLDR_Ingest::validate_pdf / scan_file` | present |
| 03 | FR003; File24 rights cases | `PLDR_Rights::file_case / decide` | present |
| 04 | FR004 edition lineage | `PLDR_Core::current_edition / latest_edition` | present |
| 05 | FR005/FUT024 duplicate scan | `PLDR_Future_Fingerprint::candidates / compute_and_store` | present |
| 06 | FR006 encrypted original storage | `PLDR_Crypto encrypt / decrypt` | present |
| 07 | FR007 lawful OCR derivatives | `PLDR_Ingest::generate_ocr / generate_derivatives` | present |
| 08 | FR008 File00 authorization | `PLDR_Access::can_access_edition / strict_rights_timestamp` | present |
| 09 | FR009 signed Range tokens | `PLDR_Access::issue_token / deliver / parse_range` | present |
| 10 | FR010 File20/25 reader shell | `PLDR_Reader::reader_html / thumbnail_tokens` | present |
| 11 | FR011 private reading state | `PLDR_Reading::save_progress / state` | present |
| 12 | FR012 private notes | `PLDR_Reading::add_item / parse_stored_tags` | present |
| 13 | FR013 File26 OCR search | `PLDR_Search::ocr / encode_ocr_cursor` | present |
| 14 | FR014 File06 citations | `PLDR_Future_Citations::export` | present |
| 15 | FR015 File05/06 linked learning | `PLDR_Future_Context::lookup` | present |
| 16 | FR016 File19/21/23 publishing outbox | `PLDR_R21_Outbox::dispatch / register_contracts` | present |
| 17 | FR017 takedown/appeal | `PLDR_Rights::appeal / temporary_restrict` | present |
| 18 | FR018/FUT005 preservation and precise anchors | `PLDR_Future_Preservation::assess/read + PLDR_Future_Anchors::save` | present; anchor rejects SVG |
| 19 | FR019/FUT017 a11y and cross-device handoff | `PLDR_Future_A11y::inspect/verify + PLDR_Future_Handoff::save/anchor` | R46-D01: bad page/coordinates coerced |
| 20 | FUT019; File17 rooms communication | `PLDR_Future_Rooms::create/anchor vs Anchors/Handoff` | R46-D02: unreviewed SVG admitted |

## Corrected only after all 20 checks
**R46-D01 (FUT017, privacy/integrity):** `PLDR_Future_Handoff::save` used `absint($context['page'])` then bounded it to valid pages; so page `-4` became 4, decimals and malformed values were also coerced, and too-large pages silently changed destination. `anchor()['region']` cast untyped nested values to floats and clamped negative/outside-range coordinates to 0/1, changing the location across devices without user intent. Correction: native fail-closed exact positive page, valid edition-page range, typed text/anchor fields, bounded finite normalized region numbers/coordinates with explicit missing/wrong-type handling **before database write**, preserving accepted valid numbers and File00 existing optimistic version semantics.

**R46-D02 (FUT005/019, File17 communication adapter):** The scholarly Anchor and Cross-Device Handoff native classes intentionally reject `SvgSelector`, pending lossless, security-reviewed representation. File17 reading-room adapter still admitted `SvgSelector` and silently sanitized its payload before sending context to a separate provider; this was not a consistent, lossless ownership boundary. Correction: reject unreviewed SVG selector explicitly in `PLDR_Future_Rooms::anchor`; reject unknown or malformed selector type/text rather than silently dropping it; keep approved TextQuoteSelector, FragmentSelector and CssSelector support. No File17 backend ownership changed.

**Regression:** `tests/test-r46-handoff-room-selector-safety.php` exercises **real native PHP Handoff save/anchor** and **real native Room create/anchor** under isolated WordPress/DB simulation for positive/negative/null/fractional/huge pages, missing malformed anchors, bad coordinate formats/ranges, successful RTL handoff, SVG and unsupported typed reading-room selectors and no mutation side effects. CI should run PHP 8.1/8.3/8.4 plus frontend/package. **This alone is not real installed WordPress or File17 provider integration proof.**

**Source version** `1.1.0-rc.25`; **source DB schema/integration contract** `1.1.0` unchanged; no physical DB migration. No deployed code modified.

## Unverified release acceptance / live-first truth
Original written plugin internal folder `12-pdf-library-foundation` versus source ZIP root `pdf-library-foundation-12` remains Founder-governed install/rollback decision. Current deployed Hostinger File12 version/hash, installed File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 versions/contracts, physical MySQL tables/schema, `wp_options`, migrations, keyring/backup/restore, scanner/OCR, authenticated/anonymous IDOR, offline/Urdu RTL/accessible/mobile browsers, real File17 compensating provider/room, 2 independent fresh staging reviews, Founder approval, approved deployment, live re-test and parity still UNVERIFIED.

| Five required realities | State |
|---|---|
| Repository HEAD | Main bootstrap `2b369ef2d609247896e1ab32118f1dc498407f51`; frozen development parent R45 `b5e04a380aef62b7d83d15ef17079e5867d86528`; R46 final HEAD after QA |
| Deployed Version | UNVERIFIED |
| Actual DB Version | UNVERIFIED (source schema 1.1.0 is not live DB) |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
