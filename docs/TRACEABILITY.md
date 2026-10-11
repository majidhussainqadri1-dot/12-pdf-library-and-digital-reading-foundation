# File 12 — requirements-to-code traceability

Core governing requirement IDs: `F12-FR-001` through `F12-FR-019` and `F12-NFR-001` through `F12-NFR-010`.

| Requirement | Implementation anchor | Automated evidence |
|---|---|---|
| F12-FR-001 Catalog | `PLDR_Core`, normalized documents/editions, `PLDR_Reader` bounded catalog cursor | `tests/test-plan-contract.php`, R24 regression |
| F12-FR-002 PDF ingest | `PLDR_Ingest::validate_pdf`, scanner adapter, SHA-256, current-rights precondition | contract/static tests, R24 regression |
| F12-FR-003 Rights/source | edition rights fields, `PLDR_Rights_Policy`, restricted rights cases | contract/static tests, R24 regression |
| F12-FR-004 Edition/version | `pldr_editions`, version/supersession | schema/static test |
| F12-FR-005 Duplicate detection | exact SHA + ISBN/title-author candidate gate | source test |
| F12-FR-006 Object encryption | chunked AES-256-GCM, key IDs, SPL2 migration reader | crypto smoke/tamper/range test |
| F12-FR-007 Cover/preview/OCR | encrypted derivatives, fail-closed scanner state, OCR table | source/schema test; runtime provider pending staging |
| F12-FR-008 Access policy | versioned policy table and entitlement adapter | source test |
| F12-FR-009 Signed delivery | hashed short-lived audience/object/operation token + Range | crypto/range + static test |
| F12-FR-010 Inline reader | responsive toolbar, page/zoom/fit/thumbnails/keyboard/fullscreen/recovery | JS syntax + static UI assertions; browser acceptance pending |
| F12-FR-011 Progress | private cross-device state + strictly monotonic optimistic revision + clear/export | schema/source + R24 regression |
| F12-FR-012 Bookmarks/notes | private page anchors/tags/export/delete | schema/source test |
| F12-FR-013 Search | metadata normalization + entitlement-filtered OCR search + signed continuation state | source + R24 regression |
| F12-FR-014 Citations | stable document/edition/page styles | source test |
| F12-FR-015 Related content | File 05/06/16/21/26 adapter/filter/event boundaries | integration contract test |
| F12-FR-016 Interactions | canonical interaction HTML/event adapter; no duplicate comments/reactions backend | source negative assertion |
| F12-FR-017 Takedown/dispute | report/restrict/decide/appeal/restore/remove/revoke; published transition rechecks rights policy | source + R24 regression |
| F12-FR-018 Preservation | checksums, integrity sampling/quarantine, backup evidence contract, key recovery gates | crypto test + health source test |
| F12-FR-019 Accessibility metadata | language/title/author/pages/OCR quality/thumbnail count/fallback | source/UI assertions |
| F12-NFR-001 Authorization | native object/policy revalidation and adapter | source + R24 test |
| F12-NFR-002 Privacy | WP exporter/eraser, legal hold, no public reading state | source test |
| F12-NFR-003 Reliability | idempotency, single governed R21 outbox retry/dead-letter, duplicate-safe fingerprint jobs | source/schema + R24 test |
| F12-NFR-004 Performance | bounded catalog/private-workspace keyset pagination, HTTP range, background heavy work | static + R24 test; measured p75/p95 pending staging |
| F12-NFR-005 Accessibility | semantic controls, focus, 44px targets, RTL, reduced motion, shared Sabri visual tokens | CSS/JS assertions; assistive-tech evidence pending staging |
| F12-NFR-006 Observability | privacy-safe audit, health report, outbox/dead-letter | source test |
| F12-NFR-007 Migration/rollback | versioned schema, lock, bounded legacy import, non-destructive uninstall | source/schema test; rollback rehearsal pending staging |
| F12-NFR-008 Operability | health + safe repairs + integrity sample | source test |
| F12-NFR-009 Compatibility | WP 7.0 / PHP >=8.1 plugin header, CI PHP 8.1/8.3/8.4 | CI matrix |
| F12-NFR-010 Localization | gettext strings, Unicode/Urdu/Arabic accepted, RTL CSS | static test; locale UI acceptance pending staging |

## Central-plan additions

- Global rights-aware PDF Reader: implemented in File 12 reader UI and delivery contract.
- File 01 route registry: File 12 registers `/library/`, detail/read, governed `/library/manage/`, and private reading workspace routes with owner/visibility metadata.
- Universal Download Manager for File 12 PDFs: chunked byte-range queue with pause/resume, access revalidation, expected checksum and bounded client verification.
- Homeopathic Book Content Packs: versioned manifest registration with rights, provenance, checksum and update-manifest validation; no parallel library backend or embedded large binary.
- File 20/25 visual boundary: module styles inherit the shared Sabri primary token, with `#087A4E` as the local fallback rather than a competing module brand system.

## Future Digital Reading Intelligence 24 traceability

| ID | Feature | Primary code anchors |
|---|---|---|
| F12-FUT-001 | Advanced Reflow Reading Mode | `PLDR_Future_Data::reflow`, `assets/future-reader.js`, `assets/future-reader.css` |
| F12-FUT-002 | Read Aloud / TTS | `assets/future-reader.js` Web Speech client over lawful reflow text |
| F12-FUT-003 | Smart TOC / Outline Recovery | `PLDR_Future_Data::outline`, `future-reader-scholar.js` |
| F12-FUT-004 | Edition Comparison Laboratory | `PLDR_Future_Data::compare`, `future-reader-scholar.js` |
| F12-FUT-005 | Precise Scholarly Anchors | `PLDR_Future_Anchors`, Future REST `/anchor` |
| F12-FUT-006 | Citation Export Center | `PLDR_Future_Citations`, `/citation-export` |
| F12-FUT-007 | Bibliographic Authority Enrichment | `PLDR_Future_Authority`, `pldr_authority_lookup`, provenance cache |
| F12-FUT-008 | OCR Quality Laboratory | `PLDR_Future_OCR_Lab`, `ocr_corrections`, review API |
| F12-FUT-009 | Portable Annotation Standard | `PLDR_Future_Annotations`, JSON-LD/Web-Annotation-style import/export |
| F12-FUT-010 | IIIF Interoperability | `PLDR_Future_IIIF`, rights-aware manifest endpoint |
| F12-FUT-011 | Inside-Book Search Heatmap | `PLDR_Future_Search`, `future-reader-scholar.js` |
| F12-FUT-012 | Encrypted Offline Reading Vault | `future-reader-vault.js`, offline delivery grant, authenticated `/future/offline-authorization/{edition}` reconnect check |
| F12-FUT-013 | Ultra-Low-Bandwidth Reader | `future-reader.js` connection/data-saver text-first behavior |
| F12-FUT-014 | Multiple Reading Layouts | `future-reader.js`, `future-reader.css` |
| F12-FUT-015 | Personal Smart Shelves | `PLDR_Future_Shelves`, `shelves`, `shelf_items` |
| F12-FUT-016 | Private Non-Gamified Reading Insights | `PLDR_Future_Insights`, `reading_events` |
| F12-FUT-017 | Cross-Device Session Handoff | `PLDR_Future_Handoff`, `session_handoffs` optimistic versioning |
| F12-FUT-018 | Accessibility Quality Inspector | `PLDR_Future_A11y`, `a11y_audits` |
| F12-FUT-019 | Scholarly Reading Rooms | `PLDR_Future_Rooms`, `room_contexts`; companion communication adapter only |
| F12-FUT-020 | Knowledge Context Sidebar | `PLDR_Future_Context`; companion provider adapter only |
| F12-FUT-021 | AI-Ready Corpus Manifest | `PLDR_Future_Corpus`; deny-by-default allowlist/entitlement gate |
| F12-FUT-022 | Translation & Transliteration Overlay | authenticated Future POST + `PLDR_Future_Derived_Text`; labeled derived-provider output |
| F12-FUT-023 | Digital Preservation Laboratory | `PLDR_Future_Preservation`, `preservation_records` |
| F12-FUT-024 | Visual Duplicate / Scan Fingerprints | `PLDR_Future_Fingerprint`, duplicate-safe cron scheduling, `scan_fingerprints`; no auto merge |

Automated presence/negative-boundary checks are implemented in `tests/test-future-24-contract.php`; R24-specific invariants are permanently covered by `tests/test-twenty-round-review-r24.php`. PHP syntax, JavaScript syntax, secret scan, retained corrective-review regressions and deterministic package integrity remain in the GitHub Actions quality gate.

## Truth boundary

This traceability establishes implementation presence and automated repository evidence. It does **not** claim Hostinger staging acceptance, external provider configuration, real backup/restore proof, browser/device/accessibility/offline-vault acceptance, production deployment or live operational correctness.


## R27 cross-plan corrective trace — 10 October 2026

| Requirement / shared contract | Finding / actual source change | Retained check | Acceptance still outstanding |
|---|---|---|---|
| F12-FR-009 signed delivery, F12-FR-010 reader, F12-NFR-003 reliability | R27-D01: Move all reader-manifest policy, thumbnail, private state and accessibility read/error guards **ahead of** original bearer grant issuance in `PLDR_REST::reader_manifest` | R27 grant-order regression | Failure-injection under real WP/MySQL; bearer-token row count remains unchanged on 503 |
| F12-FUT-010 IIIF, F12-FR-008 access policy | R27-D02: Move rights/access-policy DB check **ahead of** `PLDR_Future_IIIF` preview token loop | R27 IIIF pregrant regression | Real staging rights provider/DB failure tests |
| F12-FR-001..019, F12-NFR-001..010, F12-FUT-001..024 / File 05/06/16/17/19/20/21/23/24/25/26 | Twenty separate plan-to-source focus passes of the R26 baseline; feature/source anchors only; no unsupported claim of companion operational acceptance | `tests/test-twenty-round-review-r27.php` | Real counterpart version/endpoint/role contract journeys |
| File 12 package-folder constitution | Written `12-pdf-library-foundation` vs source candidate `pdf-library-foundation-12` persists historically; **no silent rename**. | R27 review register | Founder-approved dated change request, staging compatibility/migration plan |
| Versioned release | Candidate software `1.1.0-rc.6` / DB `1.1.0` / contract `1.1.0`, package checksum controlled separately | CI exact-head build | Deployed parity + staging/live acceptance |

R27 green CI (if obtained) proves only the exact GitHub candidate. It cannot prove deployed source checksum, actual schema/migration, live UI, backup/restore, rollback, or installed companion owner contracts.


## R28 requirement-to-code and adjacent-plan audit (2026-10-11)
- **Entire governing matrix:** F12-FR-001–019 (19), F12-NFR-001–010 (10), F12-FUT-001–024 (24) = 53 unique IDs; 12 source-side external adapter anchors. All appear in `tests/test-r28-twenty-full-plan-sweeps.php` and are rechecked for 20 complete source regression passes, with 20 distinct extra invariants.
- **F12-FR-009 / File 24:** merged R27-B `PLDR_Access::current_delivery_binding()` and delivery-time fail-closed association recheck; tests `tests/test-r28-detached-grant-binding.php`.
- **F12-FR-010:** direct HTML-reader `PLDR_Reading::state`, `PLDR_Core::public_document_dto` and `PLDR_Core::policy` checked before any initial PDF/thumbnail bearer grant. Test `tests/test-r28-reader-html-pregrant.php`.
- **F12-FR-009/F12-FUT-010:** inherited and retained R27-A core REST reader / IIIF policy-before-grant sequencing; earlier tests retained.
- **File 05/06/16/17/19/20/21/23/24/25/26 and CF-04:** only File 12 source adapters inspected. External source/deployed contracts, native roles, version parity, File 23 native edit-route status and CF-04 secure-media activation must be independently tested. No duplicate domain ownership authorized.
- **Release identity:** distinct source candidate `1.1.0-rc.7`, schema & contract still `1.1.0`; root/plugin README and package markers aligned. Written planned ZIP folder vs longstanding candidate folder discrepancy is intentionally a dated change-control blocker, not silently rewritten.
- **Evidence boundary:** 20 automated repeated static source checks + specific executable PHP regressions are repository claims, not complete running-product/staging/live validation. Full details in `docs/TWENTY-ROUND-REVIEW-2026-10-11-R28.md`.


## R29 (2026-10-11): actual File 12 written-plan catalog/search and shared UI correction

| Plan provision | Previously verified gap | R29 exact code remedy | New test | Remaining live gate |
|---|---|---|---|---|
| F12-FR-001, clause 2.1: title, author, source, edition, license and access-mode catalog search | `PLDR_Search` handled only q/type/category/language | Canonical-current-edition author/source/edition/ISBN/license/year and authorized DTO access_mode facets; bounded/invalid input fail-closed | `tests/test-r29-catalog-facets.php` | Real rights/large-catalog/role journeys in staging |
| F12-FR-013, File26 search contract | Missing filters and cursor query binding | All selected filters + actor ID hashed into signed catalog cursor context; prevent incompatible filter replay | same fixture test, cursor query mismatch denial | File26 real consumer and index freshness |
| F12-NFR-004 / File20/25 accessible public navigation | `next_cursor` existed in source but `/library` page gave no Next navigation | UI filters and accessible `First results`/`Next eligible documents` links keeping only safe scalar facets | same test HTML/REST parity | RTL, keyboard, 320–1920px, very long multilingual strings |
| File 05, File 06, File 21, File 23, File 25 owner boundaries | Source adapter only; counterpart versions and real deployment not established | No duplicate course/knowledge/feed/dashboard/visual owner created; File 12 remains source document owner | Retained static R28 owner contract suite | Versioned connected provider/consumer journey |

**Unverified dependencies:** Auth File00 claims, real WordPress staging, File26 indexing, privacy/guardian/rights providers and all cross-repository deployments. Package directory name conflict remains change-control, not fixed by unrelated source patches. No live-resolution claim.

## R30 Catalog-safe input and policy parity (2026-10-11)

| Source requirement | Code/test evidence | Remaining acceptance |
|---|---|---|
| F12-FR-001, F12-FR-013, F12-NFR-004 | `PLDR_Search::search` rejects non-scalar/bool/null typed catalog parameters before query casts or filters; augmented `tests/test-r29-catalog-facets.php` with 17 structured/invalid scenarios | Actual WordPress REST and browser query error semantics, File26 index parity and high-volume entitlement pagination |
| F12-FR-008/009, File00 security, CEN-PRI-001 | `PLDR_Access::can_access_edition` re-integrates tested malformed embargo and unknown-audience denial; `tests/test-r30-policy-fail-closed.php` real-class 23 assertions | Actual physical DB corrupted rows, current File00 entitlement provider and logged-in/anonymous/suspended journeys |
| F12-NFR-009, central release status | New unique `1.1.0-rc.9` source/package; R26/27/28 and repeated 20× whole-plan static suite retained | New exact-HEAD CI and separately deployed runtime/DB/package hash verification |

## R31 (11 October 2026): File 12 / central governed ingest validation

| Plan requirement | Proven previous source gap | Changed source and regression | Pending acceptance |
|---|---|---|---|
| F12-FR-001/002, central API structural-validation law | Required and optional scalar metadata could coerce an array/object into the literal string `Array`, or throw before returning a stable error | `PLDR_Ingest::ingest` now validates known metadata/flags before DB or uploaded-file side effects; `tests/test-r31-ingest-input-shapes.php` actual method fixtures | Real WP REST/nonce/role/HTTP upload on staging |
| F12-FR-001 subjects & collections | Nested array/object JSON lists were not rejected before string conversion | Bounded indexed list and JSON list checks, invalid nested input fails closed | Actual large multilingual metadata/provider tests |
| F12-FR-002, AT-01/AT-06 | PDF/Cover nested `tmp_name` and filename could reach `is_uploaded_file`, causing TypeError | Verify file parameter array, path/name strings and scalar size/status before file operations | Real multipart hostile shape/scan on staging |
| F12-NFR-001/007 and central owner law | Not an excuse to alter schema or other owners | Source-only `1.1.0-rc.10`; same `1.1.0` DB/contract; all canonical owners preserved | Hostinger exact artifact/DB/migration, external File00/05/06/16/20/24/25 and CF04 integration, backup/rollback and Founder acceptance |

R31 is a source QA candidate, not proof of operational resolution. See `docs/TWENTY-ROUND-REVIEW-2026-10-11-R31.md`.

## R32 access policy scalar flags and patient consent (2026-10-11)

| Governing requirements | Implementation & actual code evidence | Independent acceptance |
|---|---|---|
| F12-FR-008, F12-NFR-001: explicit enable/disable in PDF ingest/access policies | `PLDR_Core::policy_flag` canonical 1/0/null; `PLDR_Ingest::ingest` and `PLDR_Access::update_policy` call before later `empty()`/persistence; `tests/test-r32-policy-flags.php` | Real WordPress REST API, authenticated/suspended roles, DB transaction, revoke, and browser requests pending |
| F12-FR-002/005 and F12-NFR-002: patient consent and distinct scan acknowledgment | `patient_case_consent` and `confirm_distinct_scan` share canonical false/true parser; independent patient-case provider clearance remains mandatory | Actual File00/24 clinical consent and legally verified clearance + real distinct scan workflow pending |
| Central release & NFR-009 | Unique rc.11 software and exact-HEAD PHP/JS/deterministic ZIP required; source schema stays 1.1.0 | Physical deployed plugin SHA, schema/migration and live retest not yet verified |
