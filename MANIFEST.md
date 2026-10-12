# File 12 Candidate Manifest

Candidate: `1.1.0-rc.29`

Database schema: `1.1.0`
Integration contract: `1.1.0`

Canonical plugin directory: `pdf-library-foundation-12/`

## Core components

- `class-pldr-core.php` — canonical types/categories, authorization, DTOs, audit, idempotency, events.
- `class-pldr-schema.php` — normalized core schema, migrations, cron, legacy migration.
- `class-pldr-storage.php` — private storage, atomic object paths and cleanup.
- `class-pldr-crypto.php` — PLD3 chunked AES-256-GCM plus SPL2 migration reader and authenticated ranges.
- `class-pldr-ingest.php` — validation, scanner gate, current-rights gate, encryption, dedupe, derivatives/OCR.
- `class-pldr-access.php` — policy evaluation, short-lived bound grants, Range delivery and revocation.
- `class-pldr-reader.php` — catalog, signed continuation, reader, private reading state/workspace, citations and search.
- `class-pldr-rights.php` — rights cases, takedown/appeal, publication eligibility, Book Content Packs, governed outbox/integrations.
- `class-pldr-rest.php` — versioned core command/query API.
- `class-pldr-admin.php` — health, integrity, safe repair and admin workflows.
- `class-pldr-privacy.php` — privacy exporter/eraser and legal-hold boundary.

## Future Digital Reading Intelligence 24

- `class-pldr-future.php` — governed feature registry, loader, schedules, duplicate-safe fingerprint scheduling, advanced-reader UI bridge.
- `class-pldr-future-rest.php` — Future-24 REST contracts including authenticated offline reconnect authorization and provider-derived mutations.
- `class-pldr-future-schema.php` — versioned Future-24 schema.
- `class-pldr-future-data.php` — lawful OCR/reflow, outline recovery and edition comparison.
- `class-pldr-future-derived-text.php` — translation/transliteration provider boundary.
- `class-pldr-future-anchors.php` — precise scholarly anchors.
- `class-pldr-future-citations.php` — citation export formats.
- `class-pldr-future-authority.php` — DOI/ORCID/ISBN enrichment adapter/cache with provenance.
- `class-pldr-future-ocr-lab.php` — OCR quality/correction workflow.
- `class-pldr-future-annotations.php` — portable annotation import/export boundary.
- `class-pldr-future-iiif.php` — rights-aware IIIF Presentation manifest.
- `class-pldr-future-search.php` — inside-book search heatmap.
- `class-pldr-future-preferences.php` — private advanced-reader preferences.
- `class-pldr-future-shelves.php` — private Smart Shelves.
- `class-pldr-future-insights.php` — private non-gamified reading insights.
- `class-pldr-future-handoff.php` — cross-device reading session handoff.
- `class-pldr-future-a11y.php` — accessibility quality inspector/verification record.
- `class-pldr-future-rooms.php` — document/page room-context adapter; no messaging backend duplication.
- `class-pldr-future-context.php` — companion knowledge-context adapter.
- `class-pldr-future-corpus.php` — deny-by-default AI corpus manifest for File 16 consumption.
- `class-pldr-future-preservation.php` — preservation/integrity assessment records.
- `class-pldr-future-fingerprint.php` — visual/OCR scan-family fingerprints, no automatic edition merge.
- `assets/future-reader.js` — reflow/TTS/layout/data-saver/preferences client.
- `assets/future-reader-scholar.js` — outline/compare/anchors/citations/annotations/OCR/IIIF/heatmap client.
- `assets/future-reader-personal.js` — shelves/insights/handoff/rooms/context/derived text/accessibility client.
- `assets/future-reader-vault.js` — encrypted IndexedDB offline vault with reconnect/server reauthorization.
- `assets/reader.css` / `assets/future-reader.css` — responsive/RTL/accessibility styles inheriting shared Sabri visual tokens.

## QA/docs

- `tests/` — cryptographic/tamper/range, plan-contract, Future-24 contract, secret and retained corrective-review regressions through R27.
- `docs/FUTURE-24.md` — approved Future-24 requirement register and ownership boundaries.
- `docs/TRACEABILITY.md` — requirement-to-code traceability.
- `docs/STAGING-ACCEPTANCE.md` — runtime/staging acceptance boundary.
- `docs/TWENTY-ROUND-REVIEW-2026-08-29-R25.md` — historical R25 review/fix/retest evidence; earlier R24 evidence remains retained.
- `docs/TWENTY-ROUND-CROSS-PLAN-AUDIT-2026-10-10-R26.md` — new cross-plan 20-focus audit, explicit audience escalation fix and evidence boundaries.
- `tests/test-twenty-round-review-r26.php` — executable anonymous bearer grant regression under privileged browser context.

Large PDF/book binaries, secrets, private runbooks and external corpus payloads are intentionally **not** embedded in GitHub or the plugin package.

- R27 source review: `docs/TWENTY-ROUND-REVIEW-2026-10-10-R27.md`; permanent regression: `tests/test-twenty-round-review-r27.php`.


## R28 evidence
- `docs/TWENTY-ROUND-REVIEW-2026-10-11-R28.md`: exact-baseline, 20-pass full source-plan matrix ledger, four corrected source/documentation issues, and separate deployment-truth restrictions.
- `tests/test-r28-detached-grant-binding.php`: detached original/derivative signed-delivery authorization regression.
- `tests/test-r28-reader-html-pregrant.php`: direct HTML reader must check private state and policy before grant issuance.
- `tests/test-r28-twenty-full-plan-sweeps.php`: 20 repeated 53-ID + 12 companion anchor source sweeps (1,300 presence checks) and one additional invariant per pass.
- Competing R27 branch security corrections unified into R28; software candidate rc.7, DB/contract still 1.1.0.


## R29 plan-to-code completion evidence
- `docs/TWENTY-ROUND-REVIEW-2026-10-11-R29.md` — 20 scoped comparisons to the actual File 12/central/companion written plans, exact two source defects and remediation boundaries.
- `tests/test-r29-catalog-facets.php` — actual `PLDR_Search` fixtures for author/source/edition/ISBN/license/year/access facets, invalid values, private-result denial, cursor query binding and REST/HTML parity.
- File 12 FR-001 / FR-013: add seven bounded bibliographic facets, plus accessible signed Next/First navigation. Existing owner authorization and signed cursor invariants retained. R28 earlier 20-source-matrix and prior corrective suites preserved.
- Candidate `1.1.0-rc.20`; DB/schema and interface contract stay `1.1.0`. Package folder rename remains a separate unapproved change-control gate.

## R31 corrective audit and test inventory
- `docs/TWENTY-ROUND-REVIEW-2026-10-11-R31.md`: new 20 distinct focused source/plan inspections, frozen D01-D03 finding ledger and staged/live evidence limits.
- `tests/test-r31-ingest-input-shapes.php`: actual PHP ingest entrypoint under malformed scalar/JSON-list/PDF-cover upload fixtures; invalid data cannot touch DB or file operations.
- `class-pldr-ingest.php`: metadata schema and upload shape strict gates before canonical lookup or upload handling. Candidate `1.1.0-rc.20`, DB/contract `1.1.0`, no migration.

## R32 new source audit, integrity tests and release truth
- `docs/TWENTY-ROUND-REVIEW-2026-10-11-R32.md`: 20 scoped plan/owner/source inspections, frozen source defects R32-D01/D02/D03 and unverified staging/live prerequisites.
- `tests/test-r32-pdf-physical-integrity.php`: exercises real `PLDR_Ingest::validate_pdf` on correctly-sized and mismatched PDF file bytes, modified source, appended polyglot; source invariants for Crypto failure immediate private-temp deletion and supplied cover precommit acceptance.
- `tests/test-r31-ingest-input-shapes.php`: three actual ingest-entrypoint bad cover/HTTP path tests added to existing 47 R31 fixtures.
- Version 1.1.0-rc.20 is *software/package only*; source DB/schema/interface contract still 1.1.0, no R32 migration. Package internal-folder naming conflict remains a separate unapproved change-control matter.

## R33 unified candidate and new tests
- `docs/TWENTY-ROUND-REVIEW-2026-10-11-R33.md`: 20 focused source/plan checks and exact divergence reconciliation between separate R32 PR #22 and #23 green candidates. Both mistakenly carried rc.11 but had different source bytes.
- `PLDR_Core::policy_flag`: canonical optional 1/0/null parser for access, consent and duplicate-scan permissions; callers reject invalid flags before DB effects.
- `PLDR_Ingest::ingest`: stores canonical binary policy flags and rejects false-like patient case consent and distinct-scan confirmations.
- `PLDR_Access::update_policy`: rejects nested audience/entitlement/embargo metadata and interprets download/print/offline disabled values correctly, before DB read.
- `tests/test-r33-policy-flags.php`: live PHP class/method isolated fixture; retained R32 physical PDF validator suite, R31 malformed-ingest fixtures and earlier tests.
- Software/package rc.12 is distinct; source schema and interface contract remain 1.1.0, no migration. Live deployed artifact and folder naming mismatch remain outside this release candidate.

## R34: persisted rights/public delivery parity (software 1.1.0-rc.20)
- `docs/TWENTY-ROUND-REVIEW-2026-10-11-R34.md`: twenty new scoped source-plan reviews and three proven source defects (not 20 complete staging/live tests).
- `PLDR_Access::can_access_edition`: strict five-operation allowlist, read/download/print/offline/preview; canonical `PLDR_Core::policy_flag` used on persisted rights for fail-closed historical corrupt values.
- `PLDR_Core::public_document_dto`: displays permissions only if same canonical 1/0 parser explicitly grants; false/off/unknown values no longer advertise download/print/offline.
- `PLDR_Access::issue_token`: explicit anonymous -1 authorized without inheriting active browser identity, and newly issued public token stored as user_id 0 with public audience hash matching `deliver`.
- `tests/test-r34-access-grants.php`: actual Core and Access PHP fixture covering legacy policies, DTO and anonymous/user signed grants, in PHP 8.1/8.3/8.4 quality gate.
- Source-only candidate: DB/schema and integration contract remain `1.1.0`; live installed versions and the plan/source folder name conflict are unverified and unchanged.

## R35 private notes/bookmark + exact source unity (1.1.0-rc.20)
- Governing plan: File12 `F12-FR-012` user-private bookmarks/notes/tags; NFR-001/002 object/field security and privacy, central canonical ownership/strict input/traceability.
- `docs/TWENTY-ROUND-REVIEW-2026-10-11-R35.md`: twenty distinct source/plan review foci; 5 source/release findings, implementation truth limits.
- R34 PR #26 and #27 were **divergent code builds both named rc.13**. R35 uses #26 as parent (preserves its persisted-permission + anonymous signed token repairs), selectively incorporates #27 private read/write guards; new exact build identity rc.14.
- `PLDR_Reading::add_item`: validates note/tag/type/page/anchor fields before insert, rejects nested/associative/malformed JSON and silently coerced `Array` content; accepts legitimate bounded plain or JSON tags.
- `PLDR_Reading::items`: fail closed previously stored corrupt/map/nested/oversized tags before exposing user-private DTOs.
- `PLDR_Access::update_policy`: explicitly nullable embargo clear permitted, null audience/entitlement now denied.
- `tests/test-r35-reading-item-input-shapes.php`: actual add_item and items PHP integration fixture; `tests/test-r33-policy-flags.php` extended null audience/entitlement test; PHP 8.1/8.3/8.4 QA jobs.
- No source DB/schema contract migration (still 1.1.0). Plan inner plugin root discrepancy and installed companion owner/version/real Hostinger state require staged founder-controlled parity and rollback.


## R36 package v1.1.0-rc.20 — private REST/rights mutation guard
- Actual File12 `F12-FR-011/012/017`, `F12-NFR-001/002/007`; central Master Plan private C2–C5 owner data and versioned File00/17/19/20/24/25 boundaries.
- `PLDR_Reading::parse_stored_tags`: canonical strict JSON string-list parser, shared by native `items` and private REST paginated `reading_items`; legacy corrupted maps/nested/tag-limit data fail closed, no partial private DTO.
- `PLDR_REST::validate_mutation_shape`: strict REST scalar/page/edition/version/rights-evidence preflight before any private reads/idempotency/mutations for `save_progress`, `add_reading_item`, `delete_reading_item`, `rights_case`, `rights_decision`, and `rights_appeal`.
- `tests/test-r36-rest-privacy-mutation.php`: actual REST PHP fixture with legacy private map/nested/bad JSON, valid items, malformed progress and rights changes, fake DB no-side-effect assertions; on PHP8.1/8.3/8.4 quality gate.
- `docs/TWENTY-ROUND-REVIEW-2026-10-11-R36.md`: 20 scoped Source/Plan checks, three classes of proven source issues, strict source/staging/live demarcation.
- Software `1.1.0-rc.20` differs from previous rc.14; source DB/schema and interface contract `1.1.0` unchanged; original plan/plugin ZIP root naming incompatibility remains unapproved change control.


- R36-D04 source extension: strict pre-DB `reader_access` / `download_session` signed grant inputs, `approve_document` and `access_policy` IDs/version, and `repair` operation. Exercises actual methods with malformed inputs in the R36 REST fixture; core File00 authorization and staging/lifecycle acceptance remain separate release gates.


## R37 rc.16 — native private deletion + reader permissions agree with Access
- Governing written File12 amended v1.1 FR-008/FR-010/FR-012, NFR-001/002 and consolidated central plan: private owner state must be truthful, rights displays may not contradict delivery authorization, and different source candidates need distinct release evidence.
- R37-D01 `PLDR_Reading::delete_item`: invalid user/item is rejected; success only if WordPress DB delete affected exactly 1 owner-scoped row; SQL failure and 0 affected rows are false.
- R37-D02 `PLDR_Reader::document_html` permission flags `canDownload/canPrint/canOffline`: canonical `PLDR_Core::policy_flag` exact true only; historical text `false/off/no` and malformed imported data do not advertise granted permissions.
- R37-D03 `PLDR_REST::reader_manifest` `permissions` similarly fail closed on legacy values and remain consistent with current Access enforcement.
- R37-D04 distinct R36 Drafts PR30/31 both identify rc.15 but differ by source; R37 uses PR31 proven exact-green base and selectively preserves PR30 native deletion fix as unique software rc.16.
- New `tests/test-r37-reader-permission-delete.php` exercises actual REST manifest/Core parser and direct owned private deletion with real methods, plus a source-only UI config invariant; PHP8.1/8.3/8.4. Previous R28–R36 QA retained.
- Source DB/schema and integration contract remain 1.1.0. Production installed exact build, schema/migration, plugins, keys, browser and backup/rollback remain UNVERIFIED.


## R38 source/package identity rc.17
- File12 FR-012 private paginated note reads; FR-013 lawful OCR search; FR-014 stable document/edition/page scholarly citation; NFR-001 per read authorization; NFR-004 bounded inputs.
- R38-D01/02: `PLDR_REST::validate_query_shape` denies invalid nested, bool, negative, decimal numbers and cursor/query values before DB/ownership on private reading and OCR GET endpoints.
- R38-D03: strict citation page/edition/style GET inputs; basic endpoint allows Sabri/APA/MLA only, returns explicit 400 for unsupported export names rather than mislabeling Sabri output. FUT-006 advanced export remains distinct.
- R38-D04: `PLDR_Reader::citation` embeds stable edition ID across Sabri/APA/MLA while retaining the existing document family URL and page.
- `tests/test-r38-rest-read-shapes-citation.php` invokes actual REST and citation renderer methods with bad and legitimate controls; included PHP 8.1/8.3/8.4 gate, all R1–R37 tests retained.
- No new DB/schema migration; source contract stays 1.1.0. Source-only package `1.1.0-rc.20`, no Hostinger deployed/source checksum/DB/schema parity proof.


## R39 rc.18 — File12 embargo and rights expiry as real calendars; legacy flag preservation
- Governing File12 FR-003 rights expiry and FR-008 embargo, NFR-001/002 authorization/privacy, NFR-007 staged migration and consolidated central owner boundaries.
- R39-D01/02/03: strict `PLDR_Access::strict_rights_timestamp` with checkdate, ISO clock/offset validity and deterministic UTC; reused for embargo *updates* and for historical persisted embargo and edition rights expiration *reads*. Invalid imported rights cannot silently extend ordinary-reader access.
- R39-D04: untouched legacy `true/on/yes` stored grant rights are normalized to 1 on versioned audience/rights edits; bad/false flags to 0. Existing transaction, outbox and issued-token revocation pathways unchanged.
- Actual `tests/test-r39-rights-calendar-policy.php`: execute Access/Core with fake WP DB; bad/valid dates, UTC/offset, legacy record fail-close and positive flag preservation. Included in PHP8.1/8.3/8.4 QA, prior R28–R38 source tests retained.
- Software rc.18; source DB/schema/interface contract 1.1.0. No new DB migration. No changes to plan/source WordPress inner folder mismatch without Founder staging approval.


## R40 rc.19 — rights calendar on ingest, pending version-policy inheritance, bounded catalog pagination
- F12-FR-001: catalog `page/per_page` strict positive whole-numeric values before SQL authorization/filter scans; no negative/decimal/zero coercion.
- F12-FR-003/008: fresh PDF `rights_expires_at` / `embargo_until` validated by same ISO checkdate/UTC/offset `PLDR_Access::strict_rights_timestamp` as read-time authorization/policy mutation. Malformed dates rejected before PDF/DB operations.
- F12-FR-004/008: pending-edition nonmutation of current access policy compares explicitly supplied fields, inherited omitted grant flags and embargo, preserving R39 canonical true/on/yes flags and conservative malformed values. Actual pending-version post-upload branch still requires genuine HTTP WordPress staging testing (two source invariants only).
- Real `tests/test-r40-ingest-date-catalog-pagination.php` (PHP8.1/8.3/8.4): exercises actual ingest and catalog with invalid/valid dates and page numbers plus canonical timestamp helper. Retains all prior R28–R39 source QA.
- Software package `1.1.0-rc.20`; source DB schema/integration interface `1.1.0`; no new migration. Existing ZIP inner folder mismatch and deployed/plugin/DB/live verification unmodified and remain release gates.


## R41 rc.20 — catalog REST pre-sanitization, reader query and private historic tags
- File12 `F12-FR-001` catalog / `F12-NFR-004` bounded numeric pagination: removed `sanitize_callback=absint` from actual WordPress REST `page/per_page` registration; source `PLDR_Search::search` now sees original invalid numeric values and fails closed before catalog queries.
- File12 `F12-FR-010`, `NFR-001` reader manifest: call canonical `PLDR_REST::validate_query_shape` on edition before `PLDR_Core::edition` and object/authorization reads, no silent malformed/negative/decimal conversion.
- File12 `F12-FR-012`, `NFR-002` private notes: canonical native+REST stored-tags decoder preserves JSON array/object type. Invalid `{}` rejects rather than being presented as valid `[]`; normal bounded string tag arrays retained.
- Actual test `tests/test-r41-rest-route-private-tags.php` invokes REST registration, `PLDR_REST::library`, `reader_manifest`, `reading_items`, `PLDR_Reading::items`/parser on real methods with isolated WP DB mocks. PHP8.1/8.3/8.4 CI and retained R28–R40 suites.
- Software version `1.1.0-rc.20`, schema/interface source contract `1.1.0` unchanged; plugin inner directory plan/source mismatch, staging/restore and active deployment unknown.


## R42 — Future REST strict raw query (software 1.1.0-rc.21)
- Frozen twenty owner/plan scope inspections: `docs/TWENTY-ROUND-REVIEW-2026-10-11-R42.md`.
- `PLDR_Future_REST`: remove pre-handler numeric/cursor sanitization, early typed checks for 10 Future GET endpoints; prevents negative/decimal/structured query coercion and protects private shelves, File16 corpus and File05/06 context adapters.
- `tests/test-r42-future-rest-query-shapes.php`: real route registration and method input-shape negative tests (isolated WP fixture, not E2E).
- Source DB and versioned integration contract remain 1.1.0. No staging or deployed code/DB parity evidence; folder migration decision outstanding.


## R43 source and test evidence
- 20 scoped checks ledger: `docs/TWENTY-ROUND-REVIEW-2026-10-11-R43.md`.
- `class-pldr-future-rest.php`: typed mutation preflight for 18 REST entrypoints, avoiding `absint`/string/array coercion of malformed request bodies before owner, File00 entitlement and File05/06/16/21/23 adapter boundaries.
- `tests/test-r43-future-mutation-shapes.php`: actual PHP method negative-input tests under isolated WP/DB stubs. Runs along with all previous regression suites.
- Software 1.1.0-rc.22; source DB/schema and integration contract unchanged 1.1.0, no DB migration. Source-only; no staging/live claim.


## R44 strict historical offline rights and token window (software rc.23)
- R44 20 scoped reviews: `docs/TWENTY-ROUND-REVIEW-2026-10-11-R44.md`.
- `PLDR_Future_REST::offline_rights_expiry` delegates to canonical `PLDR_Access::strict_rights_timestamp`, used by both grant and reconnect.
- `PLDR_Future_REST::offline_delivery_token_ttl` accounts for immutable native 60-second grant minimum and keeps a 60-second buffer before rights expiry; too-short windows are denied.
- `tests/test-r44-offline-rights-window.php` exercises real parser + Future reauthorization/TTL, isolated WordPress fixtures only.
- Source schema/integration version still 1.1.0, runtime Hostinger state/backup/staging/live unverified.


## R45 — private annotation portable export cross-API parity (software 1.1.0-rc.24)
- Twenty source/plan foci: `docs/TWENTY-ROUND-REVIEW-2026-10-11-R45.md`.
- `class-pldr-future-annotations.php` export of private W3C JSON-LD now refuses invalid historical `tags_json` via the canonical `PLDR_Reading::parse_stored_tags` parser; no partial output on corrupt row.
- `tests/test-r45-annotation-export-integrity.php` real export and parser fixture added to PHP CI quality gate; source DB/schema/interface 1.1.0 unchanged, no migration.
- Live server/backend/installed owners and plugin directory-naming conflict remain unverified for production approval.


## R46 cross-owner handoff / reading-room source evidence
- Plan/source ledger: `docs/TWENTY-ROUND-REVIEW-2026-10-12-R46.md` (20 bounded topics, not full E2E).
- `PLDR_Future_Handoff::save/anchor` checks positive typed nested page, explicit valid edition range, typed layout/anchor/region, finite normalized 0..1 region values, no silent coercion or clamp of user location.
- `PLDR_Future_Rooms::anchor` rejects previously admitted and unsupported `SvgSelector` and malformed/unknown types; retains approved TextQuote/Fragment/Css selectors without changing File17 canonical communication owner.
- New `tests/test-r46-handoff-room-selector-safety.php` actual native methods and no-write test, PHP CI; source schema/interface `1.1.0` unchanged, source version `1.1.0-rc.25`.
- Deployed version, plugin folder/rename, MySQL/migration, backups, integration/staging/live verification are not established.


## R47 — File17 page-region owner integrity source evidence
- Review ledger: `docs/TWENTY-ROUND-REVIEW-2026-10-12-R47.md`, twenty **scoped** inspections, not 20 exhaustive complete audits.
- `PLDR_Future_Rooms::create/anchor` now rejects out-of-range page numbers and incorrectly shaped/typed/out-of-range region coordinates rather than silently rebinding them; no changes to File17 communication backend.
- Native executable regression `tests/test-r47-reading-room-page-region-integrity.php` on PHP matrix. Software rc.26; schema/integration contract 1.1.0 unchanged; live acceptance not performed.


## R48 source candidate — Future Handoff GET read/Room quote integrity
- Twenty governing scoped inspections: `docs/TWENTY-ROUND-REVIEW-2026-10-12-R48.md`.
- `PLDR_Future_Handoff::dto` checks historical persisted JSON object/empty list, typed canonical selection/source/region and current edition page bounds instead of exposing malformed old state.
- `PLDR_Future_Rooms::anchor` requires nonblank TextQuoteSelector `exact`; retains empty page-only and approved selectors.
- New native PHP regression `tests/test-r48-stored-handoff-and-room-quote.php`; source candidate rc.27; DB/schema/contract 1.1.0 unchanged; no live verification.


## R49 user-private Reader Preferences, source rc.28
- Twenty bounded governing checks: `docs/TWENTY-ROUND-REVIEW-2026-10-12-R49.md`.
- `PLDR_Future_Preferences::validate_value_shape`: shared strict approved typed nested fields, no new-write DB access for malformed settings.
- `PLDR_Future_Preferences::get`: validates historical JSON object vs list, canonical values, denies partial corrupt projections, preserves valid legacy empty state.
- `tests/test-r49-reader-preferences-integrity.php` native PHP fixtures included in PHP CI. Source schema and integration contract 1.1.0 unchanged, no physical migration; deployed/staging unknown.


## R50 source candidate 1.1.0-rc.29 — File16 corpus provenance
- Review ledger: `docs/TWENTY-ROUND-REVIEW-2026-10-12-R50.md` (20 bounded checks, not full E2E).
- `PLDR_Future_Corpus::decode_cursor`: type-exact signed HMAC cursor bound to current File16 edition/consumer context, no page coercion.
- `PLDR_Future_Corpus::manifest`: validates all OCR row pages are strictly increasing canonical edition pages including lookahead; fails without partial AI chunks on corruption.
- `tests/test-r50-file16-corpus-cursor-source.php` real PHP native-method fixture, CI gate. Source DB/schema/interface 1.1.0 unchanged; installed state unverified.
