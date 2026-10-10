# File 12 — R26 Twenty-Focus Cross-Plan Audit and Corrective Closure

Date: 2026-10-10 (PKT). Baseline exact R25 candidate: `a7a3c72597e8c289fa4591d2992366ca4fd55248`.
Audit branch: `audit/file-12-20-pass-cross-plan-2026-10-10`.
Parent plans: File 12 v1.1 Future-24 amended (11 August 2026), consolidated governing plan, File 05/06/21/23/25 companion plans and current source manifest.

## Audit method and evidence limitation

Twenty **distinct focused source/contract inspections** were completed against the R25 candidate. An inspection is not the same as an end-to-end runtime test or twenty repeated exhaustive whole-tree reviews. Positive static presence is labeled as such, never as staging or live approval. The inspections were completed and their findings frozen **before** implementing the corrective batch. The code/QA/docs changes below are the post-inspection corrective batch.

## Twenty focused inspections — closed findings

| # | Area / cross-file boundary | Source inspected | Result at audit freeze |
|---|---|---|---|
| 1 | Canonical File 12 document/edition/object ownership | `class-pldr-core.php`, `class-pldr-schema.php` | STATIC PRESENT; no duplicate canonical business owner identified in inspected code |
| 2 | 16 document types and governed categories | `PLDR_Core::DOCUMENT_TYPES`, `PLDR_Core::CATEGORIES` | STATIC PRESENT; independent real-catalog acceptance outstanding |
| 3 | File 00 founder/doctor claims and authorization | `PLDR_Core::authorize`, `PLDR_REST` | STATIC PRESENT; **cross-audience defect discovered in round 16** |
| 4 | File 01 canonical route registry | `PLDR_Plugin`, File 12 route metadata | STATIC PRESENT; externally deployed route registry unverified |
| 5 | File 05 learning/context consumption | `PLDR_Future_Context`, Future REST | Adapter present, real File 05 provider journey unverified |
| 6 | File 06 encyclopedia/source-link consumption | `PLDR_Future_Context`, catalog citations | Adapter present, real File 06 provider journey unverified |
| 7 | File 16 AI corpus / patient-case policy | `PLDR_Future_Corpus` | Deny-by-default / consumer / patient-case gates present; provider entitlement journey unverified |
| 8 | File 17 communication ownership / rooms | `PLDR_Future_Rooms` | Context-only adapter present; no new messaging backend found in inspected class |
| 9 | File 19 notifications / reliable event dispatch | `PLDR_Core::emit`, `PLDR_R21_Outbox` | Owner event/outbox present; consumer contract runtime test outstanding |
| 10 | File 21 publishing/discovery link | File 12 published-document events and integrations | Static event bridge present; external File 21 workflow acceptance outstanding |
| 11 | Files 20/25 shell, visual tokens and UI | `PLDR_Plugin`, reader assets | Adapter/shared Sabri Green fallback present; human browser/RTL accessibility unverified |
| 12 | File 24 privacy/retention/security assurance | `PLDR_Privacy`, storage and rights modules | Native privacy/export/erasure present; File 24 operational assurance unverified |
| 13 | File 26 eligible search projections | `PLDR_Search`, reader/REST rights checks | Local entitlement filtering/cursors present; external File 26 index freshness unverified |
| 14 | F12-FR-002/003/005 PDF authenticity/ingest/dedupe/rights | `PLDR_Ingest`, rights-policy classes | Static implementations present; live malware/OCR/rights-provider tests not demonstrated |
| 15 | F12-FR-006 private encryption, key/range integrity | `PLDR_Crypto`, `PLDR_Storage`, baseline crypto CI | Prior R25 CI successful; R26 exact-head CI and real key restore separately required |
| 16 | F12-FR-009 and F12-NFR-001 signed anonymous delivery | `PLDR_Access::deliver/can_access_edition`, `PLDR_Core::authorize` | **PROVEN CODE DEFECT**: -1 anonymous sentinel converted to 0; downstream `authorize(...,0)` fell back to signed-in Founder/curator identity; could permit formerly public bearer URL under privileged browser despite embargo/restriction/rights expiry |
| 17 | F12-FR-010/011/012 private reader progress, notes, citations | `PLDR_Reader`, REST | Static mechanisms present; real-browser concurrency/private-state journeys not yet demonstrated |
| 18 | F12-FUT-001…024 expansion | Future registry/data/REST/OCR/IIIF/offline/a11y/rooms/preservation/fingerprints | All 24 feature IDs and code anchors present; external providers, device and human tests remain acceptance gates |
| 19 | Migration, schema readiness, cleanup and rollback | `PLDR_Schema`, `PLDR_R21_Readiness`, safe uninstall | Static paths present; deployed DB/schema, migration status and isolated restore/rollback NOT verified |
| 20 | Release, version, manifest, QA and documentation parity | `main`, R25 branch, workflow, STATUS, readme, staging checklist, traceability | **PROVEN RELEASE EVIDENCE DEFECT**: R25 source `1.1.0-rc.4` but STATUS/readme still R24/`rc.3`; staging checklist header `rc.1`; `main` contains only bootstrap README, not implementation |

## Corrections made only after all twenty focused inspections

1. Make `PLDR_Core::authorize` reject the explicit negative anonymous sentinel without consulting the logged-in browser user or external privileged provider.
2. Preserve the negative anonymous sentinel through `PLDR_Access::can_access_edition` internal privilege checks; reject non-positive identities before account/entitlement evaluation.
3. Add executable `tests/test-twenty-round-review-r26.php` exercising public, restricted, embargoed, expired-rights and account-only scenarios while the browser is logged in as the Founder.
4. Align the changed candidate runtime version to `1.1.0-rc.5` (DB schema/contract remain `1.1.0`), package/workflow markers, R25 retained static expectations and status/readme/staging documents. Historical R24/R25 evidence remains preserved as historical.
5. Integrate R26 executable regression into the quality workflow; do not claim exact-head green until a new run completes successfully.

## Deferred gates — intentionally not called fixed

- Missing verified deployed source/ZIP checksum, live WordPress/plugin versions and installed schema/migration state.
- Real-actor File 00/05/06/16/17/19/20/21/24/25/26 integration acceptance.
- Staging browser/screen reader/RTL/offline/rights providers, external malware/OCR, real backup/key restore/rollback and Founder sign-off.
- `main` merge: prior R25 PR remains Draft/Unmerged, and R26 must not bypass staging or the existing lineage.

## Truth table (at audit freeze)

- **Repository `main` HEAD:** `2b369ef2d609247896e1ab32118f1dc498407f51` (bootstrap README only).
- **R25 code baseline HEAD:** `a7a3c72597e8c289fa4591d2992366ca4fd55248`.
- **R26 code branch HEAD:** determined by the final GitHub commit after all corrections.
- **Software candidate:** `1.1.0-rc.5`; **schema/contract candidate:** `1.1.0`.
- **Deployed Version:** UNVERIFIED.
- **DB Version:** UNVERIFIED (candidate specification 1.1.0 is not deployed evidence).
- **Migration State:** UNVERIFIED.
- **Live Verification Status:** NOT VERIFIED; no live change performed.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
