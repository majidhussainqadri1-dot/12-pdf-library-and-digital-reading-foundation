# File 12 R36 — Twenty Plan/Repository Source Checks; Shared Private REST Integrity + Strict Rights/Progress Mutation Guard
**Date:** 11 October 2026 (PKT). **Scope:** GitHub source candidate only. NOT a Hostinger, staging, deployed or database assertion.

## Actual governing documents consulted
Direct text extraction of attached original Word master plans was reviewed: `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx`; `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`; attached File05 Learn, File06 Encyclopedia, File21 Home and News Feed, File23 Doctor/Founder Publishing Dashboard and File25 Public Visual Experience plans.

The File12 owner/contract calls for 19 FRs, 10 NFRs and 24 FUT capabilities; notably:
- `F12-FR-011` Reading progress: private last page/percentage/update version, cross-device optimistic concurrency, export/clear.
- `F12-FR-012` Bookmarks/private notes: page anchors, note text, tags, export/delete, never public by default.
- `F12-FR-017` Rights/takedown/appeals: validated rights reports, cases, reversible/sensitive restrictions and accountable adjudication.
- `F12-NFR-001` object/field authorization: File00 claims and native role/state checks on every access.
- `F12-NFR-002` privacy lifecycle: private note, tags/reading progress outside public index/analytics and respecting export/erase/legal hold.
- `F12-NFR-007` safe migration, rollback, staging/restore and no silent data loss.
Companion Files05/06 own course/encyclopedia facts, File16 AI answers, File17 communication, File20 shell, File21 feed discovery, File23 publishing workflow, File24 assurance, File25 visual design; File12 supplies only owner-approved source adapters, not duplicates.

## Source truth freeze
R35 Draft PR #28 HEAD `90dd4768ded28842b93c3b953a48e0a747f32890`, Actions #451 completed GREEN. GitHub main `2b369ef2d609247896e1ab32118f1dc498407f51` remains bootstrap README. Additional separately green R35 PR #29 `834d635aa70d7c2de78fe0e6b54d67d3cca3d11e` diverges from #28; inspected its unique diff and found it mirrors earlier native rights/private checks but does NOT repair the new paginated REST stored tags bypass or malformed REST mutation routes. Thus #29 was **not** silently treated as merged/deployed or integrated into R36.

## 20 new focused inspection passes — source-frozen ledger, then remediation batch
These are **twenty different scoped manual comparisons of governing requirements with actual source owner paths**, not twenty full line-by-line forensic audits, twenty actual deployed runs or twenty Hostinger/staging E2E tests. Kept separate from R28's twenty repeated static matrix of 53 F12 FR/NFR/FUT IDs + 12 source companion adapter anchors: 1,300 automated *presence* assertions plus 20 invariants. Presence does not prove functionality.

| Pass | Governing plan + source owner compared | Frozen source finding / evidence boundary |
|---:|---|---|
| 01 | F12-FR-001 catalog 16 types/categories; `PLDR_Core::DOCUMENT_TYPES` | Source present; real catalog/index and public metadata acceptance not checked |
| 02 | F12-FR-002 genuine/private ingest; `PLDR_Ingest::validate_pdf` | Physical byte-count/signature/EOF checks retained; scanner/environment staging outstanding |
| 03 | F12-FR-003 rights source/evidence, `PLDR_Ingest` | Rights reference/evidence source fields exist; real evidence/permissions not audited |
| 04 | F12-FR-004 edition/version; `PLDR_Ingest` | Expected canonical family version and conflict rejection source present |
| 05 | F12-FR-005 duplicate/scan; `PLDR_Ingest` | Exact hash and explicit distinct scan confirmation source present; real scan corpus unverified |
| 06 | F12-FR-006 encrypted storage; `PLDR_Crypto` | AES-256-GCM crypto and key ring source present; master key backup/restore unverified |
| 07 | F12-FR-007 cover/derivatives; `PLDR_Ingest` | Precommit HTTP cover check source present; physical OCR/thumbnail providers unverified |
| 08 | F12-FR-008 rights/access; `PLDR_Access` | R33/R34 hardened policy parser and historical flags retained; File00 deployed guardian/entitlement not verified |
| 09 | F12-FR-009 signed delivery; `PLDR_Access` | Current edition/object binding and explicit anonymous grant parity source present; HTTP range/IDOR staging pending |
| 10 | F12-FR-010 library/reader, `PLDR_Reader` | Catalog/reader navigation/source markup present; real RTL/keyboard/low-bandwidth audits outstanding |
| **11** | **F12-FR-011 reading-progress REST; `PLDR_REST::save_progress`** | **R36-D02:** JSON Array/boolean/decimal `page` or malformed `edition_id` converted with `absint()`, potentially mutating legitimate private page state after coercion. `expected_updated_at` nested values could cast into `Array`. Reject malformed REST mutation shape before any parent DB lookup, using strict optional scalar+unsigned-decimal rules |
| 12 | F12-FR-012 native private stored tags; `PLDR_Reading::items` | R35 strictly checked index/simple-list/size after `json_decode`, but this is not the REST route |
| **13** | **F12-FR-012 / NFR-002 paginated private `GET /pldr/v1/reading/items`; `PLDR_REST::reading_items`** | **R36-D01:** own SQL and independent `is_array` allowed historical JSON object maps, nested values and over-limit private tags to pass to REST clients, bypassing R35 hardened direct reader. One `PLDR_Reading::parse_stored_tags` now authoritatively validates both code paths (indexed list of <=30 string tags, <=16K JSON bytes) and denies entire page/list with privacy-safe machine error on corruption |
| 14 | F12-FR-013 searchable lawful OCR and signed cursor, `PLDR_Search` | R29/R30 signed search filters remain; File26 deployed index/ranking not tested |
| 15 | F12-FR-014 citation rights and edition access; `PLDR_REST::citation` | Object-state/permission checks source present, real client citation precision unverified |
| **16** | **F12-FR-017 / NFR-001 rights report/appeal routes, `PLDR_REST::rights_case`** | **R36-D03:** user-supplied nested `document_id`, `reason` and evidence objects cast to `Array` / array without shape check before DB lookup/idempotency, risking ambiguous case input. Strict REST shape guard added |
| 17 | File16 AI corpus, `PLDR_Future_Corpus` | Allowlisted consumer manifest owner checks source present; real File16 installed version/AI corpus provider unverified |
| 18 | File17 rooms and File19 notification adapter, `PLDR_Future_Rooms` | Source-only non-owner room adapter present; native route/version/consumer unverified |
| 19 | File21/23 publishing/event outbox, `PLDR_R21_Outbox` | Dispatch source exists, File21/23 native event ack/publishing feed unverified |
| 20 | F12-NFR-007 lifecycle/migrations, `PLDR_Schema::maybe_upgrade` | Schema source exists; actual DB/wp_options/version/deployed plugin/source hash/restore/rollback **unverified** |

After full source finding freeze, R36-D03 also covers `rights_decision` and `rights_appeal` nested note/decision/evidence/expected_version; `add_reading_item` and `delete_reading_item` input IDs/versions also protected against noninteger coercion (same input-boundary defect class).

## Source corrections — bounded scope
1. **R36-D01:** central `PLDR_Reading::parse_stored_tags(string $json):?array` enforces indexed string-list, at most 30 tags, no >16KB/invalid/nested/associative JSON. Both direct `PLDR_Reading::items` and paginated `PLDR_REST::reading_items` now invoke it; each fails closed and audits malformed stored rows rather than returning partial private DTO.
2. **R36-D02:** `PLDR_REST::validate_mutation_shape` rejects nested/bool/noninteger page, edition ID and invalid expected update shape **before private DB read and idempotent claim**; valid missing optional timestamp semantics retained.
3. **R36-D03:** the same preflight rejects malformed `rights_case`, `rights_decision`, `rights_appeal` document IDs/reasons/reviewer notes/evidence arrays/expected versions; and malformed numeric IDs on reading-item add/delete. Existing native rights case state transitions, review authority, appeal max depth, evidence sanitation, optimistic versioning and event outbox remain unchanged.
4. **Real executable fixture** `tests/test-r36-rest-privacy-mutation.php`: invokes actual `PLDR_REST::reading_items` on corrupted historic JSON map/nested/mixed/oversize plus positive valid array; invokes actual six REST mutation handlers with malformed JSON. Each negative case checks machine code and no fake DB read/write. Running on PHP 8.1/8.3/8.4 CI gate. Retains R35 real native private reading 61 cases, R34 66 real grants, R33 109 flag, R31 50 ingest, R32 5 real PDF, older crypto/access/reader tests and 20×65 static requirement anchors.
5. Software ZIP uniquely `1.1.0-rc.15`, source DB/schema/interface contract remains `1.1.0`, no new schema migration.

## Operational release blockers (still mandatory)
- Plan specified WordPress plugin inner root `12-pdf-library-foundation`, but source ZIP uses `pdf-library-foundation-12`; **Founder-controlled deployment/upgrade/rollback compatibility decision required**. No silent rename, duplicate install or live edit.
- Need exact deployed File12 plugin PHP/JS/ZIP/checksum + active version; physical WP DB schema/options/migration/rows; logs/config/keys/protection and CF04, malware/OCR providers; actual other owner installed versions File00/01/05/06/16/17/19/20/21/23/24/25/26, their auth/entitlement/revocation contracts and staging E2E. Also host browser RTL/keyboard/a11y, actual anonymous and doctor/guardian roles, privacy export/erase, backup+key restore/rollback and two fresh staged review/fix rounds before Founder approval, merge, deploy and live retest.
- Repeated static test anchors and green GitHub CI do not mean all 53 actual feature paths or external owner contracts are operational.

## Live-first five-field incident/release report
Repository HEAD: verify R36 exact final PR #31 SHA and completed matching Actions run (GitHub main `2b369ef2d609247896e1ab32118f1dc498407f51` remains bootstrap).  
Deployed Version: UNVERIFIED.  
DB Version: UNVERIFIED (repository source declares `1.1.0`).  
Migration State: UNVERIFIED.  
Live Verification Status: NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
