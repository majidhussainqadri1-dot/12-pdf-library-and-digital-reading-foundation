# File 12 — R33 new twenty-focus code/plan and cross-owner verification

**Date:** 2026-10-11. **Frozen baseline:** Draft PR #22 exact HEAD `d33e7e4871806988d2119cb4b593c7e12e0929e2`. **Divergent correction source:** Draft PR #23 exact HEAD `c9456ea249101dd26565a338f210ae49eae5bc5f`. Each had its own successful GitHub CI but they contained **different** `1.1.0-rc.11` code. Their shared parent was R31 PR #21.

## Direct governing texts and review scope
- The attached File 12 v1.1 amended Future-24 Word master plan explicitly requires 19 F12-FR, 10 F12-NFR, and 24 F12-FUT features, including genuine uploaded PDF validation (FR-002), rights/online/download/embargo (FR-008), lawful OCR search (FR-013), physical encrypted delivery, privacy and clean rollback.
- The attached three consolidated central governing master plans demand canonical owners and versioned cross-module DTO/events, strict no-permission-without-evidence, no silent privacy or security downgrades, and source/staging/production truth separation.
- Companion File05 Learn, File06 Encyclopedia, File21 Home/Feed, File23 Publishing Dashboard and File25 unified visual master plans were considered at their specified ownership boundaries. The File00/01/16/17/19/20/24/26 and CF-04 contracts were examined **from File12-side source adapters**; their current independent repository builds and installed plugins were not certified.
- 20 **separate focused source-to-requirement checks** are recorded below, frozen before the R33 corrective code batch. These are NOT 20 independent complete all-file line-by-line audits, WordPress executions, or 20 staging/live deployments. The retained `tests/test-r28-twenty-full-plan-sweeps.php` independently runs 20 repeated static matrices of 53 FR/NFR/FUT requirement IDs + 12 adapter-source anchors (1,300 source-presence checks), not proof of functional completeness.

## Twenty reviewed areas and findings

| Round | Requirement domain | Code / plan comparison | Frozen outcome |
|---:|---|---|---|
| 01 | Exact source history and deployment parity | R32 Draft PR #22 d33e7e4 vs #23 c9456ea diverged at R31; main 2b369ef2 untouched | Two different rc.11 candidate packages; no live artifact/DB proof |
| 02 | Owner model and 16 types/categories | File12 FR-001; PLDR_Core::DOCUMENT_TYPES/CATEGORIES + canonical editions/objects | Source owner model present; real existing data not verified |
| 03 | True PDF physical-byte validation | F12-FR-002; PLDR_Ingest::validate_pdf, R32 physical fixture | PR22 size mismatch / physical bytes guard preserved |
| 04 | Encrypted temp cleanup and atomic ingestion | F12-FR-006; PLDR_Ingest encrypt_file + delete temp, PLDR_Storage | PR22 immediate exception/failure cleanup preserved; actual disk faults pending |
| 05 | Optional cover/derived binary integrity | F12-FR-007; ingest cover preflight & store_cover | PR22 pre-commit cover check preserved; real multipart provider pending |
| 06 | Ingest scalar, list, malformed upload metadata | F12-FR-001/002; PLDR_Ingest early shape gate; R31 fixture | R31 47 malformed/valid method tests retained |
| 07 | Uploaded access-rights flags | F12-FR-008; PLDR_Ingest download_allowed/print_allowed/offline_allowed | R33-D01: PR22 still treated explicit text 'false' as true/allowed |
| 08 | Patient-case consent and distinct-scan confirmation | F12-FR-002/005, NFR-002; PLDR_Ingest consent/confirm_distinct_scan | Same D01 also affected acknowledgements; independent patient clearance still mandatory |
| 09 | Policy update and revocation | F12-FR-008/009, NFR-001; PLDR_Access::update_policy | D01 affected policy update; R33-D02 nested audience/embargo/entitlement input could be cast/warn before DB |
| 10 | External File00 authorization, public and restricted | PLDR_Core::authorize and PLDR_Access::can_access_edition | Source guest/manager checks and R26/R30 regressions present; real File00 roles unverified |
| 11 | Embargo, rights evidence, takedown | F12-FR-003/017, class-pldr-rights.php / rights-policy | Source controls present; real legal-case workflows pending |
| 12 | Encrypted original & range delivery | F12-FR-006/009, PLDR_Crypto::encrypt_file/stream_range/verify_file | Source and crypto tests exist; actual key restore/provider pending |
| 13 | Signed grants and current object binding | F12-FR-009, PLDR_Access::issue_token/deliver/current_delivery_binding | R28 real fixture retained; HTTP/DB race unverified |
| 14 | Catalog facets & lawful OCR, File26 | F12-FR-013, PLDR_Search::search/ocr, R29/R30 facet fixtures | Source filters and cursor code present; global File26 publisher/search index unverified |
| 15 | Reading state, notes, privacy (File24) | F12-FR-011/012 and NFR-002, PLDR_Reading + PLDR_Privacy erase/export/legal_hold | Source paths present; actual actors/retention/legal hold pending |
| 16 | Future-24 and accessible digital reader | F12-FUT-001..024, PLDR_Future registry, IIIF, a11y inspector | 24 source markers present; real browser RTL/offline/assistive devices pending |
| 17 | Course & encyclopedia native owner adapters | File05/06 plans, PLDR_Future_Context provider interface | Owner adapter present; exact installed File05/06 consumers not proven |
| 18 | AI corpus, context / knowledge provenance | File16 owner, PLDR_Future_Corpus explicit allowlist and patient-case gate | Source deny-by-default path present; real AI provider unverified |
| 19 | Rooms/outbox and shared navigation | File17/19/21/23, File01/20/25; PLDR_Future_Rooms/R21_Outbox/route/slot | Source event and provider hooks present; real consumers and contracts unverified |
| 20 | Schema, release identity, backup/rollback | F12-NFR-007, PLDR_Schema locks, CI ZIP, Readme/Manifest | R33-D03: divergent rc.11 builds; unique rc.12 fixes identity. Folder naming mismatch and staging remain |

## Corrections after source finding freeze

**R33-D01 — explicit false rights and consent flags:** PR22 had no equivalent of PR23's `PLDR_Core::policy_flag` parser. `PLDR_Ingest::ingest` and `PLDR_Access::update_policy` relied on PHP `empty()` to parse five input flags (download/print/offline, patient consent, duplicate scan). PHP interprets text `'false'`, `'no'`, `'off'` as nonempty: this could *grant download/print/offline rights contrary to the submission* and distort consent/scan acknowledgements. Patient-case publication also had an independent verified clearance provider, so no unconditional patient-case publication bypass is asserted. Copied the known-good explicit `PLDR_Core::policy_flag` 1/0/null parser and canonicalization calls into the PR22 code while preserving its PDF/cover/crypto corrections. Invalid values fail before mutation; true and false strings map correctly.

**R33-D02 — malformed policy metadata:** `PLDR_Access::update_policy` attempted to cast untrusted audience/entitlement/embargo values before checking their shapes, which could create warnings or synthetic rights values from nested arrays/objects. Added a fail-closed early shape gate for `audience`, `entitlement_key` and `embargo_until`; malformed inputs return `pldr_policy_metadata_shape` before canonical DB queries. The ordinary role check still runs first.

**R33-D03 — divergent package identity:** PR22 and PR23 separately called different code `rc.11`. R33 is a new descendant of PR22 with selectively integrated PR23 fixes and a distinct immutable software/package identifier `1.1.0-rc.12`. DB schema/contract source versions remain `1.1.0`. A passing PR22 or PR23 CI cannot substitute for exact-HEAD R33 CI.

**Regression evidence:** The new `tests/test-r33-policy-flags.php` loads actual `PLDR_Core`, `PLDR_Ingest`, `PLDR_Access` methods using a controlled WordPress DB fixture, tests explicit booleans, malformed values, real persisted permissions, fail-before-DB side effects, consent text and nested access-policy metadata. Prior `tests/test-r32-pdf-physical-integrity.php`, R31 ingest shape, R30 embargo/audience, R29 catalog and R28 20x matrix are preserved. New CI must be verified for the exact final HEAD; legacy green checks do not authorize R33.

## Not coding-proof / still required

1. **Production/live reality**: active Hostinger plugin runtime/version, deployed ZIP/files/checksum, PHP/WP installed versions, physical DB tables/columns/rows/wp_options/migration, logs/config and encryption keys are **not available/verified** in this repository review. A repo/CI result must never be called a live fix.
2. **Real cross-file integration**: deployed File00 identity/guardian/suspension, File01 routes, File05/06 content owners, File16 AI, File17 rooms, File19/21 outbox consumers, File20 shell, File23 publishing, File24 assurance, File25 UI tokens, File26 indexing, CF04 central media; their actual versions, contracts and business journeys need staging.
3. **Staging and release**: real PDF/cover multipart validation and size mismatch, fail-safe encryption cleanup, rights/consent/IDOR, real browser Urdu/RTL/keyboard/a11y/offline, keyring backup/restore, user/privacy export/erasure, rollback/migration and two corrective staging reviews before Founder approval and live deploy/re-test.
4. **Open change control**: File12 written plan names installation ZIP root `12-pdf-library-foundation`, but WordPress plugin still packages folder `pdf-library-foundation-12`. Renaming it without a staged migration/installed-plugin compatibility/rollback would be unsafe. Leave unresolved pending Founder approval.
5. The next phase is exact-final-HEAD CI, deterministic ZIP and source checksum. Do not claim live parity without deployed artifact proof.

### Five independent evidence fields

Repository HEAD: check final R33 SHA and matching GitHub CI.
Deployed Version: unverified.
DB Version: unverified (source target `1.1.0` only).
Migration State: unverified.
Live Verification Status: NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
