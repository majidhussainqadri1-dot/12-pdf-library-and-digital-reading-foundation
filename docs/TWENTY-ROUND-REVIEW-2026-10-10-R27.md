# File 12 — R27 New Twenty-Focus Source/Contract Audit (2026-10-10)

## Exact baseline and epistemic boundary

- Prior latest repository candidate: Draft PR #13, `audit/file-12-20-pass-cross-plan-2026-10-10`, exact baseline `510754c9cb92b39cb43ff4ff7a0e4430e48a713e`, code candidate `1.1.0-rc.5`.
- Prior Draft PR #12 (`d8b37264...`) diverged from PR #13; neither was merged into `main`. R27 deliberately descends from PR #13, not from the older Draft PR #12.
- Governing documents: latest approved consolidated central platform plan, File 12 PDF Library & Digital Reading v1.1 amended Future-24 (11 August 2026), and related File 05/06 learning/knowledge plans and companion File 00/01/16/17/19/20/21/24/25/26 contract boundaries.
- Required coverage: 19 core FR, 10 NFR, Future-24 IDs, centralized free/rights/privacy/accessibility/non-duplicated-owner rules.
- **Meaning of round:** 20 separate focused source-and-requirement inspections. They do **not** mean 20 full end-to-end WordPress test-suite executions, 20 Hostinger staging checks, or an assertion of no remaining bugs. Identified defects were recorded before corrective changes.

## The twenty inspections and frozen findings

| Round | Compared requirement/contract | Repository source inspected | Frozen result |
|---:|---|---|---|
| 01 | Source/release truth, branch lineage and exact HEAD | GitHub open PRs/branches, comparison PR12 vs PR13, latest CI | PR #13 was the newer baseline; PR #12 divergent. Do not infer staging/live deployment. |
| 02 | Central/F12 canonical ownership | `class-pldr-core.php`, `class-pldr-schema.php`, File12 charter | Document, edition, binary and reading state owned by File12; no new duplicate canonical owner proven in inspected paths |
| 03 | File00 identity/doctor/curator role contract | `PLDR_Core::authorize`, `PLDR_Access::can_access_edition` | R26 anonymous sentinel enforcement present; real File00/guardian/suspension matrix pending |
| 04 | File01 route metadata and File20/25 layout/visual scope | `class-pldr-plugin.php`, reader CSS/assets, File12 plan | Adapter-level route and design boundaries present; real browser and File20 integration pending |
| 05 | F12-FR-001/004/005; 16 governed categories, versions, hash dedupe | `PLDR_Core`, `PLDR_Ingest`, File12 plan | Source pathways present; catalog/edition/data acceptance pending |
| 06 | F12-FR-002/003/017; malware/PDF/rights checks, takedown and expiry | `class-pldr-ingest.php`, `class-pldr-rights.php` | Scan/rights/transition checks located; external scanner and real rights workflows pending |
| 07 | F12-FR-008/009; signed token operations, audience binding, expiry | `PLDR_Access::issue_token`, `deliver` | R26 explicit-anonymous protection remains present |
| 08 | F12-FR-009; token-to-edition/object **at consumption time** | `PLDR_Access::derivative_belongs`, `issue_token`, `deliver`; `PLDR_Ingest::generate_thumbnail_batch` | **DEFECT R27-01:** attachment checked when grant issued but not on delivery. Thumbnail regeneration uses `REPLACE` on derivative association without necessarily quarantining earlier available object. An unexpired old grant could still deliver a now-detached thumbnail. |
| 09 | F12-FR-006/009/018; private encryption, range integrity, key lifecycle | `class-pldr-crypto.php`, `class-pldr-storage.php`, `PLDR_Access::deliver` | Encryption/delivery paths exist; real key/restore/range acceptance pending |
| 10 | F12-FR-007/013 and Future OCR/reflow/heatmap | `PLDR_Future_Data::ocr_pages`, `PLDR_Future_Search`, `PLDR_Ingest` | Derived OCR and bounded scan paths present; external OCR/provider, quality and large file tests pending |
| 11 | F12-FR-001/013, File26 search entitlement and cursor fairness | `PLDR_Search::search`, catalog cursor methods, File26 boundary | Source filtering/immutable cursor seen; global File26 projector is external/unverified |
| 12 | F12-FR-011/012; private progress/notes, shelves/handoff/insights | `PLDR_Reading`, `PLDR_Future_Shelves`, `PLDR_Future_Handoff`, `PLDR_Future_Insights` | Private scoped/version controls located; concurrency/identity real-user tests pending |
| 13 | F12-FR-014/015, citation anchors, edition/page provenance | `PLDR_Future_Citations`, `PLDR_Future_Annotations`, `PLDR_Future_OCR_Lab` | Source-provenance/notes logic present; actual citation renderer/content proof pending |
| 14 | Files05/06/16 educational/knowledge/AI provenance, corpus privacy | `PLDR_Future_Context`, `PLDR_Future_Corpus`, `PLDR_Future_Derived_Text` | Explicit provider and allowlist gates; actual File05/06/16 consumer integration pending |
| 15 | Files17/19/21: room contexts and reliable events, no duplicate chat | `PLDR_Future_Rooms`, `PLDR_R21_Outbox`, `PLDR_Core::emit` | Owner-boundary adapter/outbox present; actual event/compensation/consumer operations pending |
| 16 | F12-FUT-001..024 reflow, IIIF, offline, fingerprint, accessibility | Future REST/data/IIIF/a11y/preservation/fingerprint classes | Feature code identifiers present; browser/offline/provider/human tests pending |
| 17 | F12-NFR-001/002 and File24 privacy: erase/export/retention | `class-pldr-privacy.php`, Future REST, File12 plan | Private lifecycle methods present; real legal hold, deletion propagation and File24 assurance pending |
| 18 | F12-NFR-007/008; schema lock, rollback, backup/preservation | `class-pldr-schema.php`, `class-pldr-future-schema.php`, preservation logic | Source guards present; no deployed DB/migration/isolated restore evidence |
| 19 | F12-NFR-005/010; Urdu/Arabic RTL, File20 shell, File25 visual tokens | Reader/Future CSS & JS, File12/F05/F06 visual boundaries | Static assets present; no device/screen-reader/a11y/slow network runtime proof |
| 20 | Release exactness, CI & package/README | PHP plugin header, `README.md`, `STATUS.md`, `MANIFEST.md`, GitHub workflow | **DEFECT R27-02 (documentation):** inherited README still declared `rc.3` despite preceding `rc.5` code. Fixed by unifying a newly versioned `rc.6` candidate, tests and artifact. |

## Corrections made after source inspection

### R27-01 — revalidate signed object binding when serving ciphertext

The old `issue_token()` verified a full PDF original (`edition.object_id`) or an available derivative row. But `deliver()` checked only audience/rights and `objects.object_status='available'`. A stale bearer grant might outlive the derivative association if a thumbnail is regenerated (`$wpdb->replace` creates a new derivative row) while old object storage remains available.

**Remedy:** added `PLDR_Access::current_delivery_binding(edition_id,object_id)` and invoked it inside `deliver()` immediately before private storage is resolved. It checks the edition's current original object or the still-current available derivative. Unrelated, deleted/detached or failed DB ownership checks deny delivery; no fallback to "it was attached when the token was issued."

**Test:** `tests/test-twenty-round-review-r27.php` loads real `PLDR_Access` and exercises valid original, attached derivative, detached derivative, mismatched/deleted edition and failed edition/derivative DB reads. The test also asserts the actual delivery path calls the new gate.

### R27-02 — identifiable build and documentation

Updated `README.md` (previously erroneously describing `rc.3`), plugin/readme.txt, `MANIFEST.md`, Future-24/staging headings, source version assertions and exact package/CI names to `1.1.0-rc.6`. The repository DB/schema and contract markers stay `1.1.0`; R27 did not introduce a migration.

## Review counts and execution status

- 20 **focused source/contract dimensions** complete.
- Confirmed new code defect: **1**, in Round **08**, corrected and regression tested.
- Confirmed candidate documentation/version drift: **1**, in Round **20**, corrected.
- Other 18 inspections: **no additional proven defect detected by that scoped reading**, *not* 18 exhaustive clean staging rounds.
- Historical PR13 green tests do not certify R27. Require fresh exact-final-HEAD GitHub CI: PHP 8.1/8.3/8.4, all R1–R27 regression tests, JS syntax, checksum and deterministic `rc.6` package.
- Mandatory separate gates: real File00/05/06/16/17/19/20/21/24/25/26 integration, real roles, PDF scanner, key restore, QA for rights/IDOR, real browser/RTL/accessibility/offline/weak-network and consent, backup/restore/rollback, Founder acceptance, deployment and live re-test.

## Mandatory five-part release / incident truth

1. **Repository HEAD:** final SHA of `review/file-12-r27-twenty-round-2026-10-10` once all source files and CI are finalized.
2. **Deployed Version:** unverified; repository candidate is not deployed proof.
3. **DB Version:** unverified on live/staging; repository declares `1.1.0`.
4. **Migration State:** unverified on live/staging.
5. **Live Verification Status:** not performed; no deploy from this PR.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
