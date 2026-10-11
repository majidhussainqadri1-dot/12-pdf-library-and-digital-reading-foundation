# File 12 — R45: 20 source/plan scope reviews and portable annotation integrity
**Date:** 11 October 2026. **Frozen parent:** R44 PR#39, commit `59b7e8e73c139a8444fa3aa50a345af28e989c79`, whose older exact-head GitHub Actions run was successful. **Main HEAD:** bootstrap-only `2b369ef2d609247896e1ab32118f1dc498407f51`. R45's OWN exact-head CI evidence still required.

## Governing sources and owner law
- Supplied File12 *PDF Library and Digital Reading Complete Master Plan 2026 v1.1 Future-24 amended 2026-08-11*: 19 FR, 10 NFR, 24 FUT. Explicit `F12-FUT-009` governs private Web-Annotation-style JSON-LD export/import for bookmarks, notes and highlight anchors; strict account ownership and edition access; `F12-FR-012` governs private reading state.
- Supplied *Sabri Social Homeopathy Platform Three Central Plans Consolidated Governing Master Plan 2026*: canonical ownership, C1 account-private controls, export/erasure, zero duplicate owner, File24 security, File26 ACL-filtered search, File20 shell, File25 presentation and independent staging/live release gates.
- Supplied companion plans actually inspected: File05 Learn (PDF document truth stays File12; educational progress is File05), File06 Encyclopedia (citation and knowledge owner; PDF truth File12), File21 Home/News (PDF book discovery consumer), File23 Publishing Dashboard (PDF asset/source status with native edit route), File25 public visual experience (original PDF remains File12). Installed companion versions and actual contracts are NOT verified.
- 20 separately scoped requirement-to-source checks were completed against R44's exact PHP source; 19 checks verified the existence of required owner method anchors; one compared native/private REST behavior to the Future annotation export implementation. They are **not** twenty exhaustive independent end-to-end audits, and no source-presence result claims all functions fully correct.

## Frozen 20 findings before correction
| Round | Requirement/owner | Review focus | Exact source method anchor | Frozen finding |
|---|---|---|---|---|
| 01 | F12-FR-001; File26 | ACL-filtered catalog and signed cursor | `PLDR_Search::search / encode_catalog_cursor` | Source method anchors present; native E2E unverified |
| 02 | F12-FR-002; CF04 | PDF source upload/scan gate | `PLDR_Ingest::validate_pdf / scan_file` | Source method anchors present; native E2E unverified |
| 03 | F12-FR-003; File24 | rights provenance/cases | `PLDR_Rights::file_case / decide` | Source method anchors present; native E2E unverified |
| 04 | F12-FR-004 | canonical edition lifecycle | `PLDR_Core::current_edition / latest_edition` | Source method anchors present; native E2E unverified |
| 05 | F12-FR-005; FUT024 | scan fingerprint and dedupe | `PLDR_Future_Fingerprint::candidates / compute_and_store` | Source method anchors present; native E2E unverified |
| 06 | F12-FR-006 | original-object cryptography | `PLDR_Crypto encrypt / decrypt` | Source method anchors present; native E2E unverified |
| 07 | F12-FR-007 | lawful OCR, derivatives | `PLDR_Ingest::generate_ocr / generate_derivatives` | Source method anchors present; native E2E unverified |
| 08 | F12-FR-008; File00 | policy, current rights, entitlement | `PLDR_Access::can_access_edition / strict_rights_timestamp` | Source method anchors present; native E2E unverified |
| 09 | F12-FR-009 | token and HTTP-Range | `PLDR_Access::issue_token / deliver / parse_range` | Source method anchors present; native E2E unverified |
| 10 | F12-FR-010; Files20/25 | inline reader and thumbnails | `PLDR_Reader::reader_html / thumbnail_tokens` | Source method anchors present; native E2E unverified |
| 11 | F12-FR-011; File00 | private progress/state | `PLDR_Reading::save_progress / state` | Source method anchors present; native E2E unverified |
| 12 | F12-FR-012 | canonical private tag parsing | `PLDR_Reading::add_item / parse_stored_tags / delete_item` | Source method anchors present; native E2E unverified |
| 13 | F12-FR-013; File26 | OCR search/cursor | `PLDR_Search::ocr / encode_ocr_cursor` | Source method anchors present; native E2E unverified |
| 14 | F12-FR-014; File06 | stable citations | `PLDR_Future_Citations::export` | Source method anchors present; native E2E unverified |
| 15 | F12-FR-015; Files05/06 | learning/knowledge provider context | `PLDR_Future_Context::lookup` | Source method anchors present; native E2E unverified |
| 16 | F12-FR-016; Files19/21/23 | outbox, reader-content integration | `PLDR_R21_Outbox::dispatch / register_contracts` | Source method anchors present; native E2E unverified |
| 17 | F12-FR-017; File24 | takedown/appeal and rights | `PLDR_Rights::appeal / temporary_restrict` | Source method anchors present; native E2E unverified |
| 18 | F12-FR-018 | preservation audits | `PLDR_Future_Preservation::assess / read` | Source method anchors present; native E2E unverified |
| 19 | F12-FR-019; Files20/25 | a11y quality/human review | `PLDR_Future_A11y::inspect / verify` | Source method anchors present; native E2E unverified |
| 20 | F12-FUT-009; F12-FR-012; central C1 privacy | Web Annotation JSON-LD export/private notes parity | `PLDR_Future_Annotations::export vs PLDR_Reading::parse_stored_tags` | **Defect R45-D01; corrected after twenty checks** |

## Proven defect and single post-review correction batch
**R45-D01:** `PLDR_Reading::items` and paginated `PLDR_REST::reading_items` already use shared `PLDR_Reading::parse_stored_tags` and refuse historical invalid JSON maps `{}`, associative tag JSON, nested lists, wrong scalars, overlong tag arrays and corrupt JSON, with no partial private projection. `PLDR_Future_Annotations::export` SELECTed **the same** user-private `tags_json` records but did not validate them when building portable W3C/JSON-LD annotation items. Thus a historically corrupt row could appear as successfully exported, conflicting with the canonical reader privacy/integrity contract.

**Exact correction:** W3C export now verifies `tags_json` is a string and delegates to **the existing** `PLDR_Reading::parse_stored_tags` on every selected row before projecting the item. When invalid, records an owner-scoped audit `annotation_export_tags_corrupt` and returns machine error `pldr_annotation_export_corrupt` with item ID, **not a partial export**. Valid `[]` and string-list historical data remain accepted. No DB migrations, retroactive edits, destructive cleanup, public identity disclosures, File05/06 modifications or other plugins changed.

**Executable regression:** `tests/test-r45-annotation-export-integrity.php` calls actual PHP native parser and actual `PLDR_Future_Annotations::export` with isolated private WordPress/DB fixtures. Covers canonical valid tags, JSON object vs list, malformed data, oversized/nested records, no partial export even when first row valid, private output shaping and audit. This **does not** replace authenticated real-WordPress/Hostinger tests.

**Software:** `1.1.0-rc.24` source candidate only. DB/schema/integration contract `1.1.0` unchanged; no physical DB migrations attempted. Plugin-root conflict between documented `12-pdf-library-foundation` and built `pdf-library-foundation-12` remains Founder-controlled release planning. New exact HEAD CI and ZIP digest must be obtained only after final source changes.

## Unverified live/staging gates
Installed active File12 and companion File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 plugin versions/hashes; Hostinger PHP and `wp_options`, physical schema/rows and migration state, actual keyring/backup/restore, scanning/OCR providers, real WordPress browser/API and multi-user IDOR/RTL/accessible/low-bandwidth/portable annotations, two independent staging reviews/fixes, Founder acceptance, deployment and live smoke/parity are **UNVERIFIED**.

| Required truth | State |
|---|---|
| Repository HEAD | Frozen parent R44 `59b7e8e73c139a8444fa3aa50a345af28e989c79`; R45 branch HEAD and CI to be frozen after build |
| Deployed Version | UNVERIFIED |
| DB Version | UNVERIFIED physically (source schema 1.1.0) |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
