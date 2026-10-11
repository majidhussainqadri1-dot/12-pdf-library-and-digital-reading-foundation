# File 12 R40 — Twenty separately scoped File12/central/companion source checks
Date 11 October 2026. Exact source baseline **R39 Draft PR #34 HEAD `c572bef9de1d6aa71c370a9a73e35235b9878c75`**, all GitHub Actions #480 green; GitHub main `2b369ef2d609247896e1ab32118f1dc498407f51` is bootstrap-only. No Hostinger active plugin state has been read.

## Governing original material
Checked actual uploaded amended `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx` **including its embedded requirement table**, rather than using a paraphrased feature inventory alone: 19 must-have FRs, 10 release-gating NFRs and Future-24. Compared consolidated `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`, plus companion uploaded File05 Learn, File06 Encyclopedia, File21 Home/News Feed, File23 Publishing Dashboard and File25 Public Visual Experience plans.

Specific applicable text: F12-FR-001 “Document catalog — 16 governed categories/types, bibliographic metadata, languages, subjects and collections”; F12-FR-003 “Rights/source — Author/publisher/source, license/public-domain/permission evidence, territory/expiry and takedown contact”; F12-FR-004 edition/version; F12-FR-008 public/account/entitled/assigned, online/download embargo; F12-NFR-001 per-object/field authorization; F12-NFR-004 bounded queries/pagination and measured p75/p95; F12-NFR-007 fresh/upgrade/idempotency/concurrency/reconciliation/rollback tests. Central plan requires File12 alone own PDF binary/edition and rights source; Files05/06 own learning/encyclopedia, File21 feed discovery, File23 publishing, File25 visuals, File26 federated discovery, File00 claims and CF04 binary services by versioned consumer contracts. An adapter source anchor is NOT evidence that a different installed plugin implements the contract.

## Twenty DISTINCT focused source/plan inspection passes — freeze completed BEFORE remediation
These are 20 separately named *requirements-to-source owner inspections* and static presence checks, not 20 whole-repository line-by-line human audits, 20 actual WordPress acceptance scenarios or 20 live/staging runs. R28 separately has 20 repeated automatic sweeps × (53 FR/NFR/FUT IDs +12 external File12-side adapter anchors)=1300 static source-presence checks; source-presence is not behavioral completeness.

| Pass | Exact File12 governing area and corresponding source owner | Frozen R39 finding / test or limitation |
|---:|---|---|
| 01 | **F12-FR-001 catalog**, `PLDR_Search::search` in class-pldr-reader.php | **R40-D03:** catalog page/per_page accepted numeric negatives/decimal/0 and silently changed via `absint/max`; source now rejects before DB scan |
| 02 | **F12-FR-002 PDF ingest**, `PLDR_Ingest::ingest` | Existing genuine-HTTP PDF requirement/size/scan controls present; scanner and WordPress upload E2E unverified |
| 03 | **F12-FR-003 rights/source expiry**, `PLDR_Ingest::date_or_null` | **R40-D01:** R39 strictly fixed Access policy/authorization dates, but fresh PDF-ingest source still used `strtotime` on invalid non-calendar dates, including Feb31; fixed to use the R39 single canonical strict timestamp API |
| 04 | F12-FR-004 edition and canonical family versions, `PLDR_Core::edition` | Existing source identity/optimistic version anchor present; source-only until physical DB verified |
| 05 | F12-FR-005 duplicate detection, `PLDR_Ingest` | Exact SHA/metadata similarity source anchor; historical corpus and true scan comparison unknown |
| 06 | F12-FR-006 encrypted object `PLDR_Crypto` | AES GCM source/call path; real keyring/escrow/recovery pending |
| 07 | F12-FR-007 derived cover/OCR, `PLDR_Ingest::store_cover` | Source preflight/scanner exists; actual media/OCR providers unverified |
| 08 | F12-FR-008 policy embargo, `PLDR_Access::update_policy` | R39 calendar/UTC public-rights validator present. **R40-D01** fixes inconsistency with different ingest creator |
| 09 | F12-FR-009 signed delivery, `PLDR_Access::issue_token` | Existing bound token/revocation source present, external live HTTP access not verified |
| 10 | F12-FR-010 accessible inline reader, `PLDR_Reader::document_html` | Source interaction/config exists, File25 deployed UI/browser/RTL not tested |
| 11 | F12-FR-011 progress, `PLDR_Reading::save_progress` | Versioned private progress source and prior R36 tests retained |
| 12 | F12-FR-012 annotations, `PLDR_Reading::add_item` | Source private add/read/delete and R35–R37 tests retained |
| 13 | F12-FR-013 OCR search, `PLDR_Search::ocr` | R38 GET-boundary validation source preserved, installed search indexes File26 unknown |
| 14 | F12-FR-014 stable citation, `PLDR_Reader::citation` | R38 immutable edition citation source retained, consumer presentation unverified |
| 15 | F12-FR-015 File05/06 related content, `PLDR_Future_Context` | File12 source adapter exists, File05/06 installed compatible versions unverified |
| 16 | F12-FR-016 reader comments, `pldr_interaction_controls_html` | Read-only File12 hook exists, File17/21 native interaction and media contracts not installed-verified |
| 17 | F12-FR-017 takedown, `PLDR_Rights::file_case` | Existing state/transitions and evidence-source path, real external reviewer/safety workflow pending |
| 18 | F12-FR-018 preservation, `PLDR_Privacy::erase` | Data rights/retention source exists, backup/escrow live restore unknown |
| 19 | F12-FR-019 accessibility metadata, `PLDR_REST::accessibility_metadata` | Source reports OCR/thumbnail fallbacks, actual screen-reader/RTL and quality tests pending |
| 20 | F12-NFR-007 migrations, File00/01/16/17/19/20/21/23/24/25/26/CF04 central contracts, `PLDR_Schema::maybe_upgrade` | **R40-D02:** separate pending edition path in `PLDR_Ingest::ingest` treated omitted policy grants as explicitly disabled and omitted embargo as cleared, producing spurious "policy changed" errors. Now omitted inputs inherit current policy and stored legacy positive grants parse with canonical `PLDR_Core::policy_flag`; strict actual HTTP upload/staging verification is still required |

## Batch corrections after entire focus freeze
- **R40-D01 — date provenance parity:** expose existing R39 `PLDR_Access::strict_rights_timestamp` as common public parser for actual Ingest source, preserve validity checking and UTC normalization on fresh rights expiry and embargo. R39 Access read/mutation checks remain unchanged. Invalid ingest rights dates now reject before PDF attachment or DB writes; no silent strtotime calendar rollover.
- **R40-D02 — nonpublished edition nonmutation:** when comparing submitted pending edition to current family policy, omitted flags are no longer treated as requested “off”; omitted embargo no longer treated as cleared. A truly explicitly changed field remains a conflict. Current malformed flags fail closed. Covered by two exact source semantic invariants; **NOT** claimed actual post-upload pending branch execution because genuine HTTP `is_uploaded_file` is required.
- **R40-D03 — catalog pagination:** strict positive whole-number values, bounded input length and no Boolean/negative/decimal coercion before catalog DB access. Existing boolean-filter denial remains a separate correct error code.
- Software candidate uniquely `1.1.0-rc.19`, source schema/contract `1.1.0` unchanged, no migration.
- **R40 executable fixture** `tests/test-r40-ingest-date-catalog-pagination.php` calls actual `PLDR_Ingest::ingest`, `PLDR_Search::search`, shared R39 `PLDR_Access::strict_rights_timestamp` in mock WordPress/DB; invalid dates and paginator inputs no DB, valid dates still enter ordinary missing-PDF gate, valid pagination accepted, pending branch two source invariants. Included PHP 8.1/8.3/8.4. All prior R28–R39 security tests retained.

## Release / live-first boundaries
- Written source plan inner plugin folder `12-pdf-library-foundation` differs from generated source/install folder `pdf-library-foundation-12`; Founder-approved compatibility migration + backups/rollback still required.
- Exact live installed File12 PHP/JS/version/checksum, physical DB schema/tables/rows/`wp_options`/migration state, runtime PHP logs/config/keys/scanner/OCR/CF04, installed File00/01/05/06/16/17/19/20/21/23/24/25/26 actual owner interfaces all **UNVERIFIED**.
- External WordPress roles/IDOR/guardian, live upload, pending edition nonmutation, legal rights, off/online, browser/RTL/a11y, encrypted offline, backup+restore/key recovery, two fresh staged audit/fix cycles, Founder approval, merge/deploy, live retest **NOT PERFORMED**.

**Repository HEAD**: verify R40 last immutable Draft PR #35 exact SHA and completed Actions on THAT SHA; `main` bootstrap `2b369ef2d609247896e1ab32118f1dc498407f51`.  
**Deployed Version**: UNVERIFIED.  
**DB Version**: UNVERIFIED (repository source declares schema 1.1.0).  
**Migration State**: UNVERIFIED.  
**Live Verification Status**: NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
