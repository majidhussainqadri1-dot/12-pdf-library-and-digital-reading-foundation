# File 12 — R32 fresh twenty-focus plan and code audit (2026-10-11)

## Frozen reality and governing documents

- Baseline: Draft PR #21 HEAD `55005aef64841ab2f23254051f1db44e14594163`; source candidate `1.1.0-rc.10`. Its exact-HEAD prior GitHub Actions run `38101698454` succeeded, but is **historical** relative to R32.
- `main` at review start: `2b369ef2d609247896e1ab32118f1dc498407f51`; development PRs are not merged. **No assumption that either equals deployed Hostinger files.**
- Reviewed actual File12 DOCX `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx` (including FR/NFR tables and Future-24 clauses); `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`; and companion File05, File06, File21, File25 plans. File23 supplied plan and File00/01/16/17/19/20/24/26/CF04 integration contracts are checked only as exposed by File12's own source.
- These are **20 distinct focused plan-to-source inspections**, performed before R32 fixes. They are not twenty independent full-code line-by-line audits, twenty actual WordPress runtime journeys, or twenty fresh staging/production trials. The existing `tests/test-r28-twenty-full-plan-sweeps.php` repeats 53 stable requirements + 12 source adapter anchors over 20 static passes (1,300 presence checks); presence does **not** prove functional correctness.

## Twenty-round frozen finding ledger

| Round | Governing/ownership topic | Actual source reviewed | Frozen result |
|---:|---|---|---|
| 1 | Exact HEAD, PR ancestry, main versus deployment | PR #21 exact base 55005aef... rc.10 green; main 2b369ef... separate; live plugin/checksum unknown | Source baseline verified; no live proof |
| 2 | File12 F12-FR-001..019 / F12-NFR-001..010 / FUT-001..024 | Actual amended v1.1 DOCX FR/NFR tables and Future-24 clauses compared to R31 source and existing 20x static matrix | Static requirements mapping present; no WordPress acceptance inferred |
| 3 | Canonical File12 ownership and governed taxonomy | PLDR_Core::DOCUMENT_TYPES/CATEGORIES, document/edition/object model | Owner source present; outside owner repositories unverified |
| 4 | File00 authorization, public vs restricted audience | PLDR_Core::authorize, explicit anonymous sentinel, PLDR_Access::can_access_edition | Source guards present; File00 installed roles/guardian unverified |
| 5 | F12-FR-002 metadata/upload shapes and patient-case consent | PLDR_Ingest::ingest scalar/lists/upload checks plus patient_case_consent | R32-01: nonempty text 'false' was interpreted true by empty() check, though independent provider clearance remained mandatory |
| 6 | F12-FR-005 duplicate scan confirmation | PLDR_Ingest::ingest similar_candidate and confirm_distinct_scan truthiness | R32-01 also affected duplicate prompt: 'false' could suppress confirmation |
| 7 | F12-FR-008 upload rights flags download/print/offline | PLDR_Ingest::ingest DB access_policies insert and pending-edition policy comparison | R32-01: 'false'/'off'/'no' became enabled (1), contrary to explicit submitted value |
| 8 | F12-FR-008 policy update and revocation | PLDR_Access::update_policy input→DB row, event, revoke_document | Same R32-01: 'false' download/print/offline persisted as enabled; missing strict value validation |
| 9 | Rights cases/license/expiry/embargo | PLDR_Rights, Rights_Policy, PLDR_Access validity and R30 tests | Previously corrected embargo/audience guards retained; real rights cases pending |
| 10 | True PDF/malware/signature/polyglot | PLDR_Ingest::validate_pdf/scan_file and source/rights gates | Source exists, live scanner and hostile file tests not evidenced |
| 11 | Private object encryption and byte-range security | PLDR_Crypto::encrypt_file, verify_file, stream_range, PLDR_Storage atomic_commit | Crypto paths and prior tests present; key restore/actual Hostinger storage unknown |
| 12 | Signed grants/expired and detached derivative binding | PLDR_Access::issue_token/deliver/current_delivery_binding and R28 fixture | Source reauthorization retained; real HTTP races/IDOR unverified |
| 13 | Catalog, lawful OCR, File26 indexing & signed cursors | PLDR_Search::search/ocr, R29/R30 malformed filters, File26 boundary | Source facet/security tests retained; actual large dataset/global projection pending |
| 14 | Reader HTML/REST/IIIF grant preflight | PLDR_Reader::reader_html, PLDR_REST::reader_manifest, PLDR_Future_IIIF::manifest | Pregrant validation present; browser and injected DB failure tests outstanding |
| 15 | Private progress/bookmark/notes and File24 privacy | PLDR_Reading state/add_item, PLDR_Privacy export/erase/legal_hold | Source owner/eraser code present, real-user consent/deletion propagation pending |
| 16 | Future-24 advanced experience/IIIF/offline/a11y | PLDR_Future 24 ID registry, IIIF, offline vault, human accessibility inspector | Static markers present; device/RTL/TTS/offline acceptance pending |
| 17 | File05/06/16 knowledge, course and AI provenance | PLDR_Future_Context and Corpus allowlisted consumer hook | File12 provider-boundary evidence only; companion plugins not runtime verified |
| 18 | File17 rooms and File19/21/23 event/publishing | PLDR_Future_Rooms external owner, PLDR_R21_Outbox, PDFBookPackRegistered.v1 | Source events/adapters present; real consumers and exact versions unknown |
| 19 | File01/20/25 shell, routes and shared visual tokens | spf_register_route, suas_register_slot, spui_register_component_provider | Source adapter anchors present; composite UI/RTL not tested |
| 20 | Schema/backup/rollback/package and CI/governance | PLDR_Schema/Future_Schema locks, manifest, workflow, root folder mismatch | rc.11 is separate candidate; plan root folder conflict and staging/live acceptance outstanding |

## Root cause R32-01 — rights and consent flags interpreted with PHP truthiness

**Plan basis:** F12-FR-002 (lawful ingest), F12-FR-005 (distinct scan), F12-FR-008 (online/download/offline access policy), F12-NFR-001 (per-action authorization), F12-NFR-002 (privacy/consent) and central governing requirement for safe input and verified canonical ownership.

**Exact original source:**
- `PLDR_Ingest::ingest` accepted arbitrary scalar values for `download_allowed`, `print_allowed`, `offline_allowed`, `patient_case_consent`, `confirm_distinct_scan`, then used `empty()` or `!empty()` to decide whether to persist a grant or bypass a confirmation. A string `'false'` is **truthy** in PHP, so the input 'false' could be stored as **1/allowed**. Patient-case publication still had an independent provider clearance gate; this report does not claim that clearance could be bypassed.
- `PLDR_Access::update_policy` likewise used `empty($changes['download_allowed']) ? 0 : 1` and equivalent print/offline expressions; a valid manager could unknowingly enable these permissions by sending the text 'false'.
- The result is not merely an input warning; it is incorrect persisted rights state and possible unintended download/print/offline delivery authorization.

**Correction after all source reviews:**
1. Added a single canonical `PLDR_Core::policy_flag` parser returning 1, 0 or null (invalid). Accepted true values: true, 1, `'1'`, `'true'`, `'yes'`, `'on'` (case/whitespace normalized). Accepted false values: false, 0, `'0'`, `'false'`, `'no'`, `'off'`, `''`. Other values including nested arrays/objects, unknown strings, numeric values outside 0–1, floats and null are rejected rather than guessed.
2. `PLDR_Ingest::ingest` now canonicalizes all five accepted policy/consent/scan-confirmation flags to 1/0 **before** duplicate/consent/DB/file mutation checks; malformed values fail closed with `pldr_ingest_policy_flag` (existing nested-shape failure preserved).
3. `PLDR_Access::update_policy` canonicalizes download/print/offline inputs before any DB mutation; invalid values fail with `pldr_policy_flag`. Existing optimistic version, transactional write, revoke and event ownership remain unchanged.
4. New `tests/test-r32-policy-flags.php` exercises the actual `PLDR_Core`, `PLDR_Ingest` and `PLDR_Access` against an isolated PHP/WordPress DB fixture. It validates truth/false and malformed cases, fail-before-DB behavior, stored 0 versus 1 decisions, ordinary PDF-path continuation, and the patient-consent false-string case.
5. Candidate version advanced `1.1.0-rc.10` → **`1.1.0-rc.11`**. Source DB schema and integration contract unchanged `1.1.0`; no R32 physical migration is claimed.

## Evidence gates remaining

- R32 regression must pass against the **exact unmodified final R32 HEAD** across PHP 8.1/8.3/8.4, all earlier R1–R31 tests including R28 repeated 20× matrix, JS syntax/secret scan and deterministic ZIP. Prior R31 green does not prove R32.
- Exact installed Hostinger plugin/source/ZIP checksum, WordPress/PHP state, physical DB/wp_options/schema/migration, active keyring/backup and runtime errors remain unknown. Do not infer deployment from repository CI.
- File00 roles/guardian/suspension, Files05/06/16/17/19/20/21/23/24/25/26 and CF04 actual runtime owner/provider contracts; real malformed flag REST/HTML requests, rights/IDOR/embargo and user-consent journeys; private storage, key restoration, browser RTL/offline/a11y; isolated rollback and two staging corrective cycles need independent verification.
- The written File12 plan requires ZIP root `12-pdf-library-foundation`; existing code is `pdf-library-foundation-12`. This is a known **Founder change-control/migration gate**, not safely resolved by automatically renaming a WordPress plugin.
- Keep candidate Draft, no merge to `main` and no live deployment until real staging, founder approval, deploy, live re-test and checksum parity are completed.

## Mandatory five-field reality accounting

**Repository HEAD:** verify final R32 immutable SHA separately on GitHub.  
**Deployed Version:** unverified.  
**DB Version:** unverified (source declares `1.1.0`).  
**Migration State:** unverified.  
**Live Verification Status:** not performed.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
