# File 12 — R34 Twenty Focused Plan-vs-Source Reviews (2026-10-11)

## Exact code reality and written governing input

GitHub repository: `majidhussainqadri1-dot/12-pdf-library-and-digital-reading-foundation`. Baseline is Draft PR #24 source HEAD `f7b1828ceb932837f9031e65f4d1dc44cc43b9e7`, green only for its own historical CI. Parallel Draft PR #25 source HEAD `370bfa70972cd37eb31c3b09d0ad03653574f24a` is a *diverged* different code candidate under the same `1.1.0-rc.12` version. Neither has been merged into `main` (`2b369ef2d609247896e1ab32118f1dc498407f51`) or proven deployed. R34 descends from PR24 to preserve its PDF integrity, signed grants and rights safeguard lineage, with a selected PR25-like rejection of explicit null access audience/entitlement.

Original governing documents **read in the mounted container during R34**:
- `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx`, including the actual requirements-table row `F12-FR-012`: **“Bookmarks/private notes — Page anchors، note text، tags، export/delete; never public by default.”** The acceptance row requires authorized workflows and secure failure of unauthorized/invalid/degraded cases. `F12-NFR-001` requires per-object and field authorization; `F12-NFR-002` requires privacy, consent/minimization, export, erasure and legal hold. This source requires valid private notes as well as rejecting malformed inputs.
- `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`: canonical ownership, 00 identity, reliable events, privacy and no silent live/repository parity claim.
- Supplied current File05 Learn, File06 Encyclopedia, File21 Home/News, File23 Publishing Dashboard and File25 Public UI source plans: File12 alone owns PDF objects and private reading state. Files 05/06/21/23/25 link to native PDF documents, not duplicate canonical tables.
- Other File00/01/16/17/19/20/24/26/CF04 ownership considered **from File12 source hooks only**. Their installed source versions and actual runtime not retrieved.

We performed **20 different source/requirement/owner-focus inspections**, **not** 20 exhaustive independent line-by-line reviews or 20 real WordPress/staging E2E suites. In the current workflow, an inherited test independently repeats 20 complete **static** source matrices (53 plan requirement IDs + 12 cross-owner adapter anchors = 1,300 presence checks). Neither proves deployed code or functional completeness. Findings were frozen **before** this R34 corrective batch.

## Twenty inspected foci — actual frozen ledger

| # | Plan requirement and focus | Source inspected | Finding at baseline |
|---:|---|---|---|
| 1 | F12-FR-001: Canonical bibliographic data/types/categories | PLDR_Core metadata/DTO | Source definitions present; current installed DB not verified |
| 2 | F12-FR-002: Upload metadata and physical PDF/cover preflight | PLDR_Ingest, R31/R32 tests | Existing shape, physical size and early cover checks retained |
| 3 | F12-FR-003: License and lawful publication rights | PLDR_Rights, PLDR_Ingest | Rights state logic present; live evidence and real authorization pending |
| 4 | F12-FR-006: Encrypted original/range/read integrity | PLDR_Crypto and PLDR_Storage | Source encryption and tamper regression present; real keys/restore pending |
| 5 | F12-FR-008: Rights audiences, embargo, policy flags | PLDR_Access::can_access_edition/update_policy | R30/R33 safeguards present; R34 explicit null audience/entitlement accepted as omitted (fixed) |
| 6 | F12-FR-009: Signed token, revoked and detached original/derivative | PLDR_Access::issue_token/current_delivery_binding/deliver | Source checks present, actual WordPress HTTP race unverified |
| 7 | F12-FR-011: Private reading progress and cross-device conflict | PLDR_Reading::save_progress | Optimistic updated_at conflict and outbox present; real concurrency pending |
| 8 | F12-FR-012: Private note/type/page/anchor input shapes | PLDR_Reading::add_item | R34-D01: array/object metadata converted to synthetic text or coercion (fixed) |
| 9 | F12-FR-012: Bookmarks/notes tags as JSON/CSV/lists | PLDR_Core::sanitize_json_list, PLDR_Reading::add_item | R34-D02: nested/associative JSON tags silently accepted as synthetic 'Array' or reinterpreted (fixed) |
| 10 | F12-FR-013: Metadata, OCR search and signed pagination | PLDR_Search::search/ocr | R29/R30 controlled filter tests retained; File26 real index unknown |
| 11 | F12-FR-017: Takedown, rights decisions and appeals | PLDR_Rights + PLDR_Rights_Policy | Source workflows present, legal evidence staging acceptance missing |
| 12 | F12-NFR-001: Per object/field ownership and File00 claims | PLDR_Core::authorize and PLDR_Access | Caller guards present, actual File00 claims/guardian/roles unknown |
| 13 | F12-NFR-002: Private reading state export, erase, legal hold | PLDR_Privacy | Exporter/eraser/hold source present; real physical backup propagation unverified |
| 14 | F12-NFR-007: Schema migration + backup/rollback | PLDR_Schema | Lock/retry/upgrade source present; actual tables/options/live migration unknown |
| 15 | F12-FUT-001..024: 24 futures incl IIIF/OCR/accessibility | PLDR_Future + IIIF/A11y modules | All 24 IDs source-visible; browser/provider tests pending |
| 16 | File05/06: Education/encyclopedia external canonical owners | PLDR_Future_Context | Own-side provider hooks; companion exact deployed versions unverified |
| 17 | File16: AI corpus and patient-case allowlist | PLDR_Future_Corpus | Source consumer rights gates present, real AI owner unverified |
| 18 | File17/19/21/23: Messaging, publishing, reliable events and discovery | PLDR_Future_Rooms / PLDR_R21_Outbox | Source owner/event hooks present, real consumers unverified |
| 19 | File01/20/24/25/26/CF04: Routes, shell, privacy, visual tokens, search, shared media | PLDR_Rights, PLDR_Privacy, PLDR_Storage | Source adapters exist; cross-repository runtime integration unverified |
| 20 | CEN-REL / versioning: Source branch, package lineage, ZIP root and deployment parity | PR24/PR25 diffs, Manifest, workflow | Two different rc.12 builds; unique rc.13 candidate. Folder naming and staging release unresolved |

## Corrective batch after all source inspections

**R34-D01 — private note/bookmark request shapes.** `PLDR_Reading::add_item()` originally performed `sanitize_key((string)($data['type']...))`, `absint($data['page'])`, `sanitize_textarea_field((string)$data['note'])`, and `sanitize_text_field((string)$data['anchor'])` without first rejecting array/object/boolean/invalid-scalar request values. PHP could stringify structured values to `Array` and persist corrupted private notes/anchors or use coerced page numbers. The source now validates explicit types/page integer text and bounded private note input before any `reading_items` insert; malformed values produce `pldr_item_input_shape` or `pldr_item_input_size`.

**R34-D02 — nested/private JSON tags.** File12's general `PLDR_Core::sanitize_json_list` converts nested array values by string cast; malformed or associative JSON could be reinterpreted or silently turned into synthetic tags. `PLDR_Reading::add_item` now rejects nested arrays/objects, associative lists, oversized lists/strings, malformed JSON and JSON object shapes before calling the existing sanitizer. It accepts ordinary indexed tag arrays, valid JSON lists and comma-separated tags, retaining 30 stored tags and 80-character per-tag projection. Explicit machine error: `pldr_item_tags_shape`.

**R34-D03 — cross-PR policy metadata precision.** PR24's rights policy metadata shape guard allowed *explicit* `null` for `audience` or `entitlement_key`; `??` could silently replace the value with the currently stored audience/key. PR25 had stronger rejection of these explicit nulls, while `embargo_until=null` legitimately clears a date. R34 now rejects explicit null audience/key before DB reads while preserving null embargo clearing. Extended actual R33 `PLDR_Access::update_policy` fixture with four new assertions.

**Release identity:** PR24 and PR25 are two green but non-identical `rc.12` candidates. R34 uses a unique `1.1.0-rc.13` identity (plugin/readme/manifest/workflow/ZIP/source-test markers aligned). Source schema and integration contract remain `1.1.0`, with no SQL migration asserted.

**Regression:** `tests/test-r34-reading-item-input-shapes.php` invokes actual `PLDR_Reading::add_item` with malformed note/tag/page/anchor fixtures, checks no DB insert for invalid states and accepted positive scenarios; PHP 8.1–8.4 plus R33 policy fixture and R32 physical PDF checks must be run **on the exact corrected final HEAD**. Existing R28 20× source matrix, R29 catalog and R30 rights tests are retained.

## Outstanding gates that are not 'fixed code' claims

- Deployed WordPress plugin active source, exact ZIP/checksum, PHP runtime, real Hostinger DB/schema `wp_options`, migration version/locks/partially applied migrations, logs and environment are not verified.
- Real cross-file instances File00 identity/guardian/suspension, File05/06 content providers, File16 AI, File17 rooms, File19/21/23 events, File20 shell, File24 privacy assurance, File25 UI and File26 Search and CF04 require actual staging integration.
- Real user/IDOR and malformed REST JSON, test migrations/backup/restore/rollback, genuine PDFs/rights, Urdu RTL/mobiles, screen readers, offline vault, encryption keys, external events and legal holds require controlled staging and founder sign-off.
- Written File12 ZIP root `12-pdf-library-foundation` conflicts with the existing plugin `pdf-library-foundation-12`; a blind rename may break installed WordPress plugin identity and requires controlled upgrade/rollback and founder approval.
- Follow strict freeze -> staging parity -> founder-approved deployment -> live re-test -> exact installed checksum parity. Do not merge `main` or deploy without approval.

## Five-part production truth

**Repository HEAD:** determine final R34 exact SHA and verify GitHub CI only for that SHA.
**Deployed Version:** UNVERIFIED.
**DB Version:** UNVERIFIED (repository schema contract declares `1.1.0`).
**Migration State:** UNVERIFIED.
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
