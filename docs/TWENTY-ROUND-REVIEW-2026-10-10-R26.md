# File 12 — R26 Twenty-Focus Plan-to-Code Audit and Corrections — 2026-10-10

## Baseline / governing scope

- Original exact R25 Draft PR #11 HEAD: `a7a3c72597e8c289fa4591d2992366ca4fd55248`.
- Candidate: software `1.1.0-rc.4`, core/Future-24 contract `1.1.0`.
- Sources: latest consolidated central governing plan, File 12 PDF Library and Digital Reading v1.1 Future-24 amended (11 August 2026), and companion File 05/06/21 plans and native owner/identity/search/secure-delivery boundaries.
- Plan coverage: `F12-FR-001..019`, `F12-NFR-001..010`, `F12-FUT-001..024`, applicable central CEN/CV/AJ requirements and cross-file contracts.
- Methodology: **20 independent thematic source audits**, read against the exact reviewed candidate. They are not twenty complete staging/production WordPress regression suites. Findings were verified against relevant real method bodies before source corrections. False certainty from earlier historical CI/PR is prohibited.

## Numbered audit log

| Round | Focus (actual reviewed files/interfaces) | Finding and limitation |
|---:|---|---|
| 01 | Requirement hierarchy and exact build identity: File12 plan, central plan, `STATUS.md`, `README.md`, `pdf-library-digital-reading.php` | **R26-02 DOC DRIFT:** STATUS/README retained R24/rc.3 although actual R25 candidate was rc.4. Documentation updated; source identity unchanged. |
| 02 | Canonical document/edition/object/reader ownership: `class-pldr-core.php`, `class-pldr-future.php`, `docs/TRACEABILITY.md` | No additional confirmed duplicate-owner code in focused inspection; real downstream owners remain external. |
| 03 | File00 founder/capability/guardian/suspension interface: `PLDR_Core::authorize` and File12 plan | Found default-user semantic hazard linked to Round 06; File00 real assertions/role integration not proven until staging. |
| 04 | File01 route registry and Files20/25 layout/visual slots: `class-pldr-plugin.php`, reader UI/CSS | Adapter/source boundaries present; visual and routing staging acceptance pending. |
| 05 | Catalog, ingest, malware, PDF signature/polyglot, duplicate/edition rights: `class-pldr-ingest.php`, `class-pldr-rights.php` | Current validation paths inspected; genuine scanner and licensed content provider staging pending. |
| 06 | Signed access reauthorization, anonymous versus curator identity: `class-pldr-access.php`, `PLDR_Core::authorize` | **R26-01 CONFIRMED CODE DEFECT:** the R25 anonymous -1 sentinel mapped to zero, but nested `Core::authorize(..., 0)` interpreted zero as the current logged-in account. Explicit anonymous grants could inherit curator exceptions in a privileged session. Corrected with positive-user-only capability gate. |
| 07 | Edition/object association and private storage paths: `class-pldr-storage.php`, `class-pldr-access.php` | Path and object grant checks inspected; real private-storage/decryption proof pending. |
| 08 | AES-GCM/range delivery, integrity and key rotation: `class-pldr-crypto.php`, `class-pldr-access.php` | Stream/range/rotating key paths exist; CI crypto test is not substitute for real key restore. |
| 09 | Rights/embargo/restriction/takedown/appeal/expiry: `class-pldr-rights.php`, `class-pldr-rights-policy.php` | State transitions and revocation inspected; real rights and crash recovery testing pending. |
| 10 | Search, OCR pagination and File26 global discovery ownership: `class-pldr-reader.php`, `class-pldr-future-search.php` | Bounded/local search path present; global ranking remains File26. Large-data/role tests pending. |
| 11 | Private progress, bookmarks, notes and cross-device conflict: `class-pldr-reader.php`, `class-pldr-rest.php` | Optimistic revision and owner gating inspected; concurrent device/real DB proof pending. |
| 12 | Citations/annotations/page provenance: `class-pldr-future-citations.php`, `class-pldr-future-annotations.php` | Edition-specific citation/import controls inspected; bibliographic style & real-rights QA pending. |
| 13 | Future-24 reflow, TTS, outlines and multiple layouts: `class-pldr-future-data.php`, reader JS/CSS | Owner-preserving derived/text-reader paths present; browsers/RTL/TTS and source-provider QA pending. |
| 14 | IIIF signed previews and encrypted offline vault: `class-pldr-future-iiif.php`, `future-reader-vault.js` | Issued grants and rights gates inspected; logout/offline/weak-network tests pending. |
| 15 | File16 AI corpus manifest, derived text and contextual lookups: `class-pldr-future-corpus.php`, `class-pldr-future-context.php`, `class-pldr-future-derived-text.php` | Deny-by-default provider gates and provenance reviewed; File16 real consumer acceptance pending. |
| 16 | Private shelves, insights and session handoff: `class-pldr-future-shelves.php`, `class-pldr-future-insights.php`, `class-pldr-future-handoff.php` | User/version-scoped state inspected; real account/privacy tests pending. |
| 17 | Companion File05/06 knowledge, File17 rooms, File19/21 events, File26 projection: `class-pldr-future-rooms.php`, `class-pldr-r21-outbox.php`, contract registry | Adapter rather than copied-domain model inspected; actual multi-plugin integration pending. |
| 18 | File24/privacy assurance, legal hold, erasure, minor-specific boundaries: `class-pldr-privacy.php`, `class-pldr-future-rest.php` | Existing export/erase and private endpoints inspected; real File00/24 consent/legal-hold matrix pending. |
| 19 | Core/Future schema and migrations, preservation, fingerprints, repair: `class-pldr-schema.php`, `class-pldr-future-schema.php`, `class-pldr-future-preservation.php`, `class-pldr-future-fingerprint.php` | Source pathways located; schema inspection/backup/rollback on deployed database unverified. |
| 20 | Test/CI/package and release truth: `file-12-quality.yml`, regressions R1..R25, new R26 test | Source regressions exist; R26 test added and included by wildcard CI suite. Exact new-HEAD CI still required after final commit. Staging/live remains separate. |

### Defect R26-01 — anonymous public grant must not gain session privileges

**Reproduction path (source-based):**

1. A public grant records `user_id=0`.
2. R25 delivery correctly passes an explicit anonymous sentinel `-1` to `PLDR_Access::can_access_edition()`.
3. That function converts sentinel to `0`, but its draft/restricted/rights-expiry/embargo exceptions called `PLDR_Core::authorize($action, $document_id, 0)`.
4. `PLDR_Core::authorize` resolves zero to `get_current_user_id()`; with a logged-in curator, these checks could incorrectly use the curator's privileges rather than the public grant's audience.

**Correction:** Local `$may` gate in `can_access_edition()` never invokes `Core::authorize` for an intentionally anonymous audience. Positively named user-bound grants retain curator semantics. This is limited to File12 and does not alter the shared Core authorize API for existing callers.

**Test:** `tests/test-twenty-round-review-r26.php` loads the actual File12 access policy class with an isolated privileged-session fixture. Checks public published read, account-only denial, draft, restricted, removed, expired rights and embargo under anonymous/public grant versus explicitly named curator.

### Documentation correction R26-02

The candidate runtime `1.1.0-rc.4` was already demonstrated on R25; `STATUS.md` and `README.md` still described `1.1.0-rc.3` R24. Updated to reflect the current candidate and the new R26 review. No schema/contract version bump or production-release assertion.

## Evidence and scope disposition

- **20 focused source review dimensions completed**. Confirmed new product defect: **1** (Round 06); confirmed status-document drift: **1** (Round 01). Other reviewed dimensions disclosed no additional proven source defect in this scoped reading, but they are **not** each a verified clean runtime round.
- Source patches: one File12 access check and a dedicated actual-logic PHP negative-path regression, plus status/README/traceability and review evidence.
- No real Hostinger runtime, DB, active plugin, private keyring, deployment checksum or current migration state was accessible here. Earlier CI is historic evidence only.
- The corrected exact HEAD must successfully complete PHP 8.1/8.3/8.4 quality gate, tests and package before `Automated QA: GREEN` is claimed.
- Staging fresh/upgrade, File00/05/06/16/17/19/20/21/24/25/26 providers, browser/RTL/accessibility, malicious PDF, concurrency, user consent, key restore, privacy/IDOR, backup, rollback, Founder acceptance, deploy and live retest remain separate gates.

## Incident release footer

Repository HEAD: read the final R26 branch after completion.
Deployed Version: **unverified**.
DB Version: **unverified** (repo declares `1.1.0`).
Migration State: **unverified**.
Live Verification Status: **not performed**.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
