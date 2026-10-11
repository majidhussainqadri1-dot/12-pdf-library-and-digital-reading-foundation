# R38 — File 12: Twenty focused governing-plan/source checks and read-only REST/citation corrections
Date: 11 October 2026. Scope: **repository source** only, NOT a live WordPress diagnosis.

## Source freeze and governing basis
- R37 Draft PR #32 `8f7f140c3686f228ef4c6656cc3b4d7e27b2912a`, exact Actions #468 all green.
- GitHub `main` `2b369ef2d609247896e1ab32118f1dc498407f51` bootstrap only. A Draft PR, a completed GitHub CI, and a plugin ZIP do NOT prove an active Hostinger plugin build or database state.
- Physically consulted uploaded `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx`; the consolidated governing `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`; companion File05 Learn, File06 Encyclopedia, File21 Home/News Feed, File23 Publishing Dashboard, File25 Public Visual plans.
- File12 actual requirement language: **F12-FR-012** private bookmarks, page anchors, notes and tags; **F12-FR-013** lawful/OCR search access filtering; **F12-FR-014** stable document/edition/page citations; **F12-NFR-001** native identity and ownership checks per read/write, safe invalid case; **F12-NFR-002** private data lifecycle; **F12-NFR-004** bounded queries; FUT-006 expanded citation-export owner contract. Central plans forbid duplicate course, encyclopedia, AI, messaging, shell, security, search and visual owners.
- New source version `1.1.0-rc.17` exclusively identifies R38. Source DB/schema/contract remain `1.1.0`.

## 20 distinct focused source-to-plan inspections (source review ledger)
These are twenty separate **requirements/code-path checks**, each anchored in actual source. They do **not** constitute 20 exhaustive line-by-line independent audits or 20 real staging/live runs. Existing R28 automated repeated source matrix separately checks 20×(53 File12 FR/NFR/FUT IDs + 12 File12-side adapter anchors)=1,300 static source presence assertions, plus 20 invariants; that is neither a verified external deployment nor every feature's behavioral test.

| Round | Governed requirement and source path | Source review outcome / limitations |
|---:|---|---|
| 01 | FR001 catalog query malformed filter; `PLDR_Search::search` | Strict catalog query shapes already present. Live search quality unknown |
| 02 | FR002 genuine PDF ingress; `PLDR_Ingest::validate_pdf` | R32 true byte/signature checks retained; scanner/Hostinger upload unknown |
| 03 | FR003 copyright/rights report; `PLDR_Rights::file_case` | Native owner source present; external legal evidence and installed users unverified |
| 04 | FR004 persistent edition ID; `PLDR_Core::public_document_dto` | Edition ID is available in canonical source, enabling true edition-specific citation |
| 05 | FR005 distinct PDF scan flag; `PLDR_Ingest` | Existing scan dedupe declaration present; real content index unverified |
| 06 | FR006 private encrypted delivery; `PLDR_Crypto::encrypt_file` | Crypto source present; actual keys/backup/restore unverified |
| 07 | FR007 cover HTTP upload preflight; `PLDR_Ingest` | Source rejects partial cover upload; actual scanner and worker unknown |
| 08 | FR008 rights inheritance; `PLDR_Access::can_access_edition` | R34/R37 persisted false rights fail-closed retained; actual File00 roles unverified |
| 09 | FR009 signed delivery; `PLDR_Access::current_delivery_binding` | Revalidation source present; real HTTP ranges and revocation need staging |
| 10 | FR010 reader UI; `PLDR_Reader::document_html` | Reader/rights UI source present; actual File20 shell and File25 visual/RTL tests pending |
| 11 | FR011 progress concurrency; `PLDR_Reading::save_progress` | Optimistic revision exists; two-device staging race unverified |
| **12** | **FR012/NFR001/NFR004 paginated private reading GET; `PLDR_REST::reading_items`** | **R38-D01:** invalid array/bool/negative/decimal edition_id, limit/offset and nested cursor previously cast with `absint`/`(string)` before native access and DB; now early strict query-shape rejection |
| **13** | **FR013 OCR GET; `PLDR_REST::ocr_search`** | **R38-D02:** nested q/cursor and invalid limit/edition could become text `Array` or wrong numeric query. Strict query-shape check before `PLDR_Search::ocr` |
| **14** | **FR014 citations; `PLDR_REST::citation`, `PLDR_Reader::citation`** | **R38-D03:** negative/decimal/nested page and style previously coerced into incorrect scholarly location or false style label. **R38-D04:** source citation text omitted immutable edition ID, ambiguous if same canonical document URL represents multiple editions. Fix strict page/edition/style shape, clear unsupported style error, explicit edition ID in actual Sabri/APA/MLA outputs |
| 15 | FR015 File05/06 related content; `PLDR_Future_Context` | Source adapter exists; installed File05/06 knowledge/course native consumer unknown |
| 16 | FR016 comments/reactions; `pldr_interaction_controls_html` | Source-side hook exists; File21/17 actual interaction owner not installed/tested here |
| 17 | FR017 takedown/appeals; `PLDR_Rights::appeal` | Accountable native transition source present; role/appeal staging needed |
| 18 | FR018 preservation/privacy; `PLDR_Privacy::erase` | Source lifecycle present, physical data retention and key restore unverified |
| 19 | FUT021 File16 corpus; `PLDR_Future_Corpus` | File12 allowlisted source-side adapter present, File16 AI runtime unverified |
| 20 | NFR007 DB/migration and File00/01/16/17/19/20/21/23/24/25/26/CF04 ownership; `PLDR_Schema::maybe_upgrade` | Source upgrade hook exists; actual DB schema/wp_options/deployed checksum, staging rollback, installed companion versions unknown |

## Corrections (after reviewing all twenty source focus areas)
1. **R38-D01:** one new `PLDR_REST::validate_query_shape` for read-only APIs, rejecting invalid query numbers and nested cursor before read/authorization lookups in `reading_items`.
2. **R38-D02:** `ocr_search` uses the same shape guard for `q`/cursor/edition/limit before real OCR domain search. Valid textual query semantics unchanged.
3. **R38-D03:** `citation` no longer changes negative/decimal/nested page/edition requests through `absint`; basic endpoint explicitly accepts `sabri`, `apa`, `mla`, otherwise 400 `pldr_citation_style`. Advanced FUT-006 Chicago/BibTeX/RIS/CSL-JSON belongs to separate governed export, **NOT** claimed delivered by this endpoint.
4. **R38-D04:** `PLDR_Reader::citation` emits `File 12 edition ID` in Sabri/APA/MLA output, retaining the stable public document URL and original page display. Two editions of the same family do not become indistinguishable.
5. **Regression fixture:** `tests/test-r38-rest-read-shapes-citation.php` invokes actual `PLDR_REST::reading_items`, `ocr_search`, `citation`, and real `PLDR_Reader::citation`; checks malformed shapes reject *before* database/auth access, accepted ordinary requests still work, unsupported styles are not falsely labeled, and two editions with one document public ID have distinguishable citations. QA workflow PHP 8.1/8.3/8.4.
6. All prior source security and user-private notes, signed grants, PDF integrity, cover handling, Future24 and source-side provider contracts remain unchanged; no schema changes.

## Operational exclusions, live first
- Written plan WordPress plugin ZIP inner root `12-pdf-library-foundation` vs built source folder `pdf-library-foundation-12`: controlled upgrade compatibility and Founder approval required, no silent rename.
- Need exact active Hostinger File12 plugin PHP/JS/ZIP version/checksum, database physical tables/columns/rows, `wp_options` and migration state, runtime logs/config/auth roles/keyring, malware/OCR/CF04 providers, installed owner versions File00/01/05/06/16/17/19/20/21/23/24/25/26 and their APIs/events.
- Need real WordPress privacy/IDOR/anonymous/guardian/doctor/copyright, browser/RTL/assistive/offline, data export/erase, backup/restore/key recovery, two staged audit/fix cycles, Founder approval, controlled deploy, and live retest. Not performed.
- Source-presence tests do not prove fully functional platform features or successful staging/live integration.

### Five mandatory separate states
**Repository HEAD:** final immutable R38 Draft PR #33 HEAD, only after matching final CI and package digest; GitHub main `2b369ef2d609247896e1ab32118f1dc498407f51`.  
**Deployed Version:** UNVERIFIED.  
**DB Version:** UNVERIFIED (repository source contract 1.1.0 only).  
**Migration State:** UNVERIFIED.  
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
