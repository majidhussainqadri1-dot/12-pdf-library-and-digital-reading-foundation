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
