# File 12 — R42: twenty distinct plan/source boundary checks and Future-24 query hardening
**Date:** 11 October 2026 (PKT). **Exact parent:** R41 PR #36 `125be12011d727b85d659ce273d19843533dc0b4`. **Branch:** `review/file-12-r42-future-rest-strict-query-2026-10-11`. **Scope:** repository source only; no staging/live claims.

## Governing inputs and inspection standard
- Original amended File12 `v1.1 Future-24`: 19 FR, 10 NFR, 24 FUT requirements; specifically FR-001, FR-008, FR-010–016, NFR-001, NFR-004, FUT-001/015/021. This is **not** a replacement for the attached Word plan.
- Consolidated central v3.0 three-plan governing document and the supplied File05 Learn, File06 Encyclopedia, File21 Home/News, File23 Publishing Dashboard and File25 Public UI plans; companion File00 identity, File16 AI, File19 notification, File20 shell, File26 Search and CF04 ownership boundaries.
- Twenty **distinct scoped requirement-to-source inspections** were executed, using actual R41 PHP class source, method/contract anchor evidence and focused manual inspection of the owner boundary. They are **not** twenty whole-repository, line-by-line reviews or twenty browser/staging test runs. Presence must not be represented as operational completion.
- All 20 paths have the named source implementation anchors. R42-D01–D03 were proven in path 20 by direct route/method reading. No other **new proven** missing implementation is asserted from the other 19 source-presence checks.

## Twenty checked paths (freeze findings before corrective batch)
| Pass | Governing owner/IDs | Inspected concern | Actual source path | Finding |
|---|---|---|---|---|
| 01 | F12-FR-001; File21/26 | Catalog, access-filtered search and signed cursor | PLDR_Search::search / encode_catalog_cursor | Implementation source anchors present; E2E not verified |
| 02 | F12-FR-002 | PDF genuine-file and malware ingest gates | PLDR_Ingest::validate_pdf / scan_file | Implementation source anchors present; E2E not verified |
| 03 | F12-FR-003 | Rights provenance and complaint notice | PLDR_Rights::file_case / decide | Implementation source anchors present; E2E not verified |
| 04 | F12-FR-004 | Canonical document/edition identity | PLDR_Core::edition / current_edition | Implementation source anchors present; E2E not verified |
| 05 | F12-FR-005; FUT-024 | SHA/scan duplicate candidate, no automatic merge | PLDR_Future_Fingerprint | Implementation source anchors present; E2E not verified |
| 06 | F12-FR-006 | Encrypted object and range/crypto paths | PLDR_Crypto | Implementation source anchors present; E2E not verified |
| 07 | F12-FR-007 | Cover/derivative/OCR ingest path | PLDR_Ingest::generate_derivatives / generate_ocr | Implementation source anchors present; E2E not verified |
| 08 | F12-FR-008; File00 | Audience/embargo and entitlement | PLDR_Access::can_access_edition / update_policy | Implementation source anchors present; E2E not verified |
| 09 | F12-FR-009 | Signed operation/audience/object token delivery | PLDR_Access::issue_token / deliver / parse_range | Implementation source anchors present; E2E not verified |
| 10 | F12-FR-010 | Inline reader and thumbnails | PLDR_Reader::reader_html / thumbnail_tokens | Implementation source anchors present; E2E not verified |
| 11 | F12-FR-011 | Private cross-device progress | PLDR_Reading::save_progress / state / clear | Implementation source anchors present; E2E not verified |
| 12 | F12-FR-012 | Private bookmark/note tags, deletion | PLDR_Reading::add_item / parse_stored_tags | Implementation source anchors present; E2E not verified |
| 13 | F12-FR-013; File26 | OCR/facets/cursor search | PLDR_Search::ocr / encode_ocr_cursor | Implementation source anchors present; E2E not verified |
| 14 | F12-FR-014; File06 | Scholarly citation output and edition anchors | PLDR_Future_Citations::export | Implementation source anchors present; E2E not verified |
| 15 | F12-FR-015; File05/06 | Knowledge and course context provider boundary | PLDR_Future_Context::lookup | Implementation source anchors present; E2E not verified |
| 16 | F12-FR-016; File19/21/23 | Native interactions/outbox ownership | PLDR_R21_Outbox::dispatch | Implementation source anchors present; E2E not verified |
| 17 | F12-FR-017 | Takedown, decision, appeal and restore | PLDR_Rights::appeal / set_document_status | Implementation source anchors present; E2E not verified |
| 18 | F12-FR-018; NFR-007 | Schema locks and bounded legacy migration | PLDR_Schema::maybe_upgrade / migrate_legacy_batch | Implementation source anchors present; E2E not verified |
| 19 | F12-FR-019; NFR-005; File20/25 | Access quality and public UI boundary | PLDR_Future_A11y::inspect / verify | Implementation source anchors present; E2E not verified |
| 20 | F12-FUT-001/021; NFR-001/004; File16 | Future REST query/reading/shelf/corpus API shapes | PLDR_Future_REST::register/reflow/corpus/shelf_items | **R42-D01–D03 confirmed; corrected** |

## Frozen defects and one corrective batch **after** all twenty reviews
- **R42-D01:** Future reflow `page` used WordPress REST `sanitize_callback=absint`; negative, fractional or nested bad query values could be transformed before the owner validator. Removed pre-sanitizer and inserted raw typed validation **before** `PLDR_Future_Data::reflow`.
- **R42-D02:** Future private shelf `limit/cursor` and File16 AI corpus `limit/offset/cursor` were transformed at registration and again in handlers; negative numbers, fractional numbers and non-scalar cursor could silently alter pagination. Removed route pre-sanitizers; reject malformed raw text/numeric values before private data, entitlement/provider or SQL access. Corpus offset pagination is still explicitly prohibited.
- **R42-D03:** Additional Future GET handlers (edition comparison, citation export, annotation export, heatmap, preferences, insights, knowledge context) converted user query numbers or array-like text to safe-looking scalars. Added a shared, bounded nonnegative integral shape gate to **ten** Future GET entrypoints; reject boolean, object, array, negative, decimal and overflowing integral values without DB/provider work.

**Source changes:** `PLDR_Future_REST::validate_query_shape` plus registration and 10 handler preflight callsites in `class-pldr-future-rest.php`; new `tests/test-r42-future-rest-query-shapes.php` actual route registration / PHP handler negative-input fixture. Test derives WordPress/DB stubs from R36; it explicitly does **not** substitute for real environment tests. Existing ownership, File16 allowlist/entitlement and fail-closed protections remain in native owner paths.

**Software candidate:** `1.1.0-rc.21` (plugin/readme/workflow/installer aligned); source **DB schema** `1.1.0` and **integration contract** `1.1.0` unchanged. No physical migration attempted. Exact final CI, package SHA256 and checksum still require **new immutable HEAD success**; older R41 CI must not be reused to prove R42. Plugin folder discrepancy: written `12-pdf-library-foundation` vs source-built `pdf-library-foundation-12` remains founder-controlled migration/rollback decision, not silently renamed.

## Release gates outstanding
Fresh WordPress/Hostinger staging: physical plugin/DB/options/migrations; installed File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 versioned contracts; real OCR, malware scanner, keys, backup/decrypt/restore; public/entitled/unauthorized/IDOR/access cases; cursor/REST input via WordPress; Urdu/Arabic RTL and accessibility; private reading; PDF Range, offline rights, rollback; two independent staging review rounds; founder approval; production live smoke and checksum parity. **Not performed here.**

| Source-of-truth dimension | Status |
|---|---|
| GitHub main HEAD | `2b369ef2d609247896e1ab32118f1dc498407f51` bootstrap only |
| Source baseline | R41 parent `125be12011d727b85d659ce273d19843533dc0b4`; R42 final HEAD to be frozen after CI |
| Deployed plugin version/hash | UNVERIFIED |
| Physical DB version | UNVERIFIED (source contract 1.1.0 is not DB evidence) |
| Migration state | UNVERIFIED |
| Live verification | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
