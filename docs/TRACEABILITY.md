# File 12 — requirements-to-code traceability

Governing requirement IDs: `F12-FR-001` through `F12-FR-019` and `F12-NFR-001` through `F12-NFR-010`.

| Requirement | Implementation anchor | Automated evidence |
|---|---|---|
| F12-FR-001 Catalog | `PLDR_Core`, `PLDR_Search`, normalized documents/editions | `tests/test-plan-contract.php` |
| F12-FR-002 PDF ingest | `PLDR_Ingest::validate_pdf`, scanner adapter, SHA-256 | contract/static tests |
| F12-FR-003 Rights/source | edition rights fields, restricted rights cases | contract/static tests |
| F12-FR-004 Edition/version | `pldr_editions`, version/supersession | schema test |
| F12-FR-005 Duplicate detection | exact SHA + ISBN/title-author candidate gate | source test |
| F12-FR-006 Object encryption | chunked AES-256-GCM, key IDs, SPL2 migration reader | crypto smoke/tamper/range test |
| F12-FR-007 Cover/preview/OCR | encrypted derivatives, Imagick/adapters, OCR table | source/schema test; runtime provider pending staging |
| F12-FR-008 Access policy | versioned policy table and entitlement adapter | source test |
| F12-FR-009 Signed delivery | hashed short-lived audience/object/operation token + Range | crypto/range + static test |
| F12-FR-010 Inline reader | responsive toolbar, page/zoom/fit/thumbnails/keyboard/fullscreen/recovery | JS syntax + static UI assertions; browser acceptance pending |
| F12-FR-011 Progress | private cross-device state + clear/export | schema/source test |
| F12-FR-012 Bookmarks/notes | private page anchors/tags/export/delete | schema/source test |
| F12-FR-013 Search | metadata normalization + entitlement-filtered OCR search | source test |
| F12-FR-014 Citations | stable document/edition/page styles | source test |
| F12-FR-015 Related content | File 05/06/16/21/26 adapter/filter/event boundaries | integration contract test |
| F12-FR-016 Interactions | canonical interaction HTML/event adapter; no duplicate comments/reactions backend | source negative assertion |
| F12-FR-017 Takedown/dispute | report/restrict/decide/appeal/restore/remove/revoke | source test |
| F12-FR-018 Preservation | checksums, integrity sampling/quarantine, backup evidence contract, key recovery gates | crypto test + health source test |
| F12-FR-019 Accessibility metadata | language/title/author/pages/OCR quality/thumbnail count/fallback | source/UI assertions |
| F12-NFR-001 Authorization | native object/policy revalidation and adapter | source test |
| F12-NFR-002 Privacy | WP exporter/eraser, legal hold, no public reading state | source test |
| F12-NFR-003 Reliability | idempotency, outbox retry/dead-letter, background derivatives/migration | source/schema test |
| F12-NFR-004 Performance | bounded search/page sizes, HTTP range, background heavy work | static test; measured p75/p95 pending staging |
| F12-NFR-005 Accessibility | semantic controls, focus, 44px targets, RTL, reduced motion | CSS/JS assertions; assistive-tech evidence pending staging |
| F12-NFR-006 Observability | privacy-safe audit, health report, outbox/dead-letter | source test |
| F12-NFR-007 Migration/rollback | versioned schema, lock, bounded legacy import, non-destructive uninstall | source/schema test; rollback rehearsal pending staging |
| F12-NFR-008 Operability | health + safe repairs + integrity sample | source test |
| F12-NFR-009 Compatibility | WP 7.0 / PHP >=8.1 plugin header, CI PHP 8.1/8.3/8.4 | CI matrix |
| F12-NFR-010 Localization | gettext strings, Unicode/Urdu/Arabic accepted, RTL CSS | static test; locale UI acceptance pending staging |

## Central-plan additions

- Global rights-aware PDF Reader: implemented in File 12 reader UI and delivery contract.
- Universal Download Manager for File 12 PDFs: chunked byte-range queue with pause/resume, access revalidation, expected checksum and client verification up to a bounded memory threshold; larger downloads retain authenticated-range/object-integrity evidence and expected SHA-256.
- Homeopathic Book Content Packs: versioned manifest registration with rights, provenance, checksum and update-manifest validation; no parallel library backend or embedded large binary.

## Truth boundary

This traceability establishes implementation presence and automated repository evidence. It does **not** claim Hostinger staging acceptance, provider configuration, real backup/restore proof, browser/device accessibility acceptance, production deployment or live operational correctness.
