# File 12 — R43 Twenty Scoped Source/Plan Reviews and Strict Future Mutation Integrity
**Date:** 11 October 2026 (PKT). **Original exact source parent:** R42 draft PR #37 SHA `35780dacdc0bb83ca40aa547b1b42752c40eff49`, previously successful GitHub Actions run `38127983234`. No installed Hostinger/source parity established.

## Governing attached inputs
1. Original amended File12 `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx`: FR001–019, NFR001–010 and F12-FUT001–024. The attached Word plan is the intended specification, not proof of implementation.
2. Consolidated three Central Master Plans governing File00 identity, versioned contracts, source-of-truth, privacy, medical/Islamic safety, staging/release; connected supplied File05 Learn, File06 Encyclopedia, File21 Home/News, File23 Publishing Dashboard, File25 Public UI, with File16 AI, File19 Notifications, File20 Shell, File24 security and File26 Search ownership contracts.
3. All twenty reviews separately checked a concrete requirement-to-owner PHP implementation anchor at the exact source parent. These were **twenty bounded topical inspections, not twenty independent exhaustive code or browser E2E audits**. No claim that the original or companion Word plans were exhaustively code-complete, installed, or running is made.

## Twenty completed source inspections, all findings frozen before fixes
| Pass | Requirement | Source/companion topic | Implementation owner anchor checked | Evidence |
|---|---|---|---|---|
| 01 | F12-FR-001 | Document catalog/search, File26 consumer | `PLDR_Search::search` | Owner source anchors found; no new proven missing method from this bounded check |
| 02 | F12-FR-002 | Verified PDF ingest/scan | `PLDR_Ingest::validate_pdf / scan_file` | Owner source anchors found; no new proven missing method from this bounded check |
| 03 | F12-FR-003 | Rights/provenance | `PLDR_Rights::file_case / decide` | Owner source anchors found; no new proven missing method from this bounded check |
| 04 | F12-FR-004 | Edition identity | `PLDR_Core::current_edition / latest_edition` | Owner source anchors found; no new proven missing method from this bounded check |
| 05 | F12-FR-005 | Duplicate/hash/scan fingerprint | `PLDR_Future_Fingerprint::candidates / compute_and_store` | Owner source anchors found; no new proven missing method from this bounded check |
| 06 | F12-FR-006 | AES-GCM object crypto | `PLDR_Crypto` | Owner source anchors found; no new proven missing method from this bounded check |
| 07 | F12-FR-007 | OCR/derivatives/covers | `PLDR_Ingest::generate_ocr / generate_derivatives` | Owner source anchors found; no new proven missing method from this bounded check |
| 08 | F12-FR-008 | Current rights, File00 eligibility | `PLDR_Access::can_access_edition / update_policy` | Owner source anchors found; no new proven missing method from this bounded check |
| 09 | F12-FR-009 | Signed delivery/range, operation binding | `PLDR_Access::deliver / issue_token / parse_range` | Owner source anchors found; no new proven missing method from this bounded check |
| 10 | F12-FR-010 | Inline reader/accessibility navigation | `PLDR_Reader::reader_html / thumbnail_tokens` | Owner source anchors found; no new proven missing method from this bounded check |
| 11 | F12-FR-011 | Reading progress, private cross-device | `PLDR_Reading::save_progress / state` | Owner source anchors found; no new proven missing method from this bounded check |
| 12 | F12-FR-012 | Notes/bookmarks/tags, File00 privacy | `PLDR_Reading::add_item / parse_stored_tags` | Owner source anchors found; no new proven missing method from this bounded check |
| 13 | F12-FR-013 | OCR search, File26 projection only | `PLDR_Search::ocr / encode_ocr_cursor` | Owner source anchors found; no new proven missing method from this bounded check |
| 14 | F12-FR-014 | Book citation, File06 knowledge | `PLDR_Future_Citations::export` | Owner source anchors found; no new proven missing method from this bounded check |
| 15 | F12-FR-015 | Learning/knowledge context, Files05/06 | `PLDR_Future_Context::lookup` | Owner source anchors found; no new proven missing method from this bounded check |
| 16 | F12-FR-016 | Native File21/23 publishing and File19 outbox | `PLDR_R21_Outbox::dispatch` | Owner source anchors found; no new proven missing method from this bounded check |
| 17 | F12-FR-017 | Rights dispute and appeal | `PLDR_Rights::appeal / temporary_restrict` | Owner source anchors found; no new proven missing method from this bounded check |
| 18 | F12-FR-018 | Preservation/integrity, operator governance | `PLDR_Future_Preservation::assess / read` | Owner source anchors found; no new proven missing method from this bounded check |
| 19 | F12-FR-019 | Accessibility, File20/25 owner boundary | `PLDR_Future_A11y::inspect / verify` | Owner source anchors found; no new proven missing method from this bounded check |
| 20 | F12-FUT-005/007/008/009/012/015/016/017/019/022/023 + F12-NFR-001/002/004 + central File00/05/06/16/20/21/23/24/25/26 | Future POST/DELETE raw mutation shape integrity | `PLDR_Future_REST::anchor/ocr_correction/shelf_add/handoff_save/derive_text` | New source defects found (see D01–D04) |

## R43 frozen source defect groups — corrected only after 20 checks
- **R43-D01 — numeric identity and page shape:** Future mutation handlers used `absint` on raw body `edition_id`, `page`, `duration_seconds`, `expected_version`, potentially changing negative/fractional/structured input into a different owned object/page or version before authorization, optimistic locking and idempotency.
- **R43-D02 — typed structured data and text:** User note/OCR/correction/translation/context/annotation/shelf and private preference values were cast with `(string)`/`(array)` before type validation. Nested JSON/string crossover could be accepted or misinterpreted. Guard before provider or owner reads.
- **R43-D03 — explicit policy flags:** Future authority forced refresh (`force`) and preservation verification (`verify`) used truthy values (`!empty`) rather than strictly recognized booleans/0/1; misleading `"false"`/other strings could enable elevated operations.
- **R43-D04 — cross-file integration/authorization preflight:** Separate pre-owner mutation checks needed for File05/06-contextual reading rooms, File16-related derived text, File00 private shelves, OCR reviews, offline grants and cross-device handoffs. These are **input shape** fixes, not claims that installed owner contracts/rights are verified.

**Single correction batch:** `PLDR_Future_REST::validate_mutation_shape` rejects malformed JSON/form text, numbers, arrays and boolean flags before owner selection, authorization/provider lookup, idempotency or SQL. Applied to **18 source mutation entrypoints**: anchor, authority, ocr_correction, ocr_review, annotations_import, offline_grant, preferences_save, shelf_create, shelf_add, shelf_rename, shelf_delete, shelf_remove_item, insight_event, handoff_save, a11y_verify, reading_room, derive_text, preservation_refresh. Native owner validation and authorization remain authoritative; this input guard does not replace File00 or native checks.

**Executable regression:** `tests/test-r43-future-mutation-shapes.php` runs actual PHP Future REST handlers in the previous R42 isolated WordPress fixture with DB read/write counters; tests each typed mutation field with negative, decimal, overflowing, boolean/object/array malformed values. The suite cannot establish real WordPress/Hostinger route/role/DB integration by itself.

**Software:** `1.1.0-rc.22`; **source DB schema 1.1.0** and integration contract **1.1.0 unchanged**. No physical DB migration performed or asserted. CI and deterministic ZIP digest must be confirmed **at the final immutable R43 SHA**, not inherited from R42 green. Written inner installation folder `12-pdf-library-foundation` vs built `pdf-library-foundation-12` remains a founder-controlled compatibility/release gate, not silently renamed.

## Unverified release acceptance
Physical Hostinger deployed PHP/assets checksum, current active plugin/runtime versions, wp_options + physical tables/schema, migration locks/state, File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 installed integrations, real data/keys/scanner/OCR, real authenticated/private/guest/IDOR HTTP requests, mobile/RTL/a11y/offline tests, consistent backup/decrypt/restore and rollback, two fresh staging review/fix cycles, Founder approval, production smoke tests and live parity all remain **UNVERIFIED**.

| Live-first required truth | Status |
|---|---|
| GitHub `main` | `2b369ef2d609247896e1ab32118f1dc498407f51` (bootstrap README only) |
| Source parent | R42 PR37 `35780dacdc0bb83ca40aa547b1b42752c40eff49` |
| Source candidate | R43 branch; exact HEAD/CI pending final validation |
| Deployed Version | UNVERIFIED |
| Actual DB Version | UNVERIFIED (source schema 1.1.0 is not physical DB evidence) |
| Migration State | UNVERIFIED |
| Live Verification | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
