# File 12 — requirements-to-code traceability

Core governing requirement IDs: `F12-FR-001` through `F12-FR-019` and `F12-NFR-001` through `F12-NFR-010`.

| Requirement | Implementation anchor | Automated evidence |
|---|---|---|
| F12-FR-001 Catalog | `PLDR_Core`, normalized documents/editions, `PLDR_Reader` bounded catalog cursor | `tests/test-plan-contract.php`, R24 regression |
| F12-FR-002 PDF ingest | `PLDR_Ingest::validate_pdf`, scanner adapter, SHA-256, current-rights precondition | contract/static tests, R24 regression |
| F12-FR-003 Rights/source | edition rights fields, `PLDR_Rights_Policy`, restricted rights cases | contract/static tests, R24 regression |
| F12-FR-004 Edition/version | `pldr_editions`, version/supersession | schema/static test |
| F12-FR-005 Duplicate detection | exact SHA + ISBN/title-author candidate gate | source test |
| F12-FR-006 Object encryption | chunked AES-256-GCM, key IDs, SPL2 migration reader | crypto smoke/tamper/range test |
| F12-FR-007 Cover/preview/OCR | encrypted derivatives, fail-closed scanner state, OCR table | source/schema test; runtime provider pending staging |
| F12-FR-008 Access policy | versioned policy table and entitlement adapter | source test |
| F12-FR-009 Signed delivery | hashed short-lived audience/object/operation token + Range | crypto/range + static test |
| F12-FR-010 Inline reader | responsive toolbar, page/zoom/fit/thumbnails/keyboard/fullscreen/recovery | JS syntax + static UI assertions; browser acceptance pending |
| F12-FR-011 Progress | private cross-device state + strictly monotonic optimistic revision + clear/export | schema/source + R24 regression |
| F12-FR-012 Bookmarks/notes | private page anchors/tags/export/delete | schema/source test |
| F12-FR-013 Search | metadata normalization + entitlement-filtered OCR search + signed continuation state | source + R24 regression |
| F12-FR-014 Citations | stable document/edition/page styles | source test |
| F12-FR-015 Related content | File 05/06/16/21/26 adapter/filter/event boundaries | integration contract test |
| F12-FR-016 Interactions | canonical interaction HTML/event adapter; no duplicate comments/reactions backend | source negative assertion |
| F12-FR-017 Takedown/dispute | report/restrict/decide/appeal/restore/remove/revoke; published transition rechecks rights policy | source + R24 regression |
| F12-FR-018 Preservation | checksums, integrity sampling/quarantine, backup evidence contract, key recovery gates | crypto test + health source test |
| F12-FR-019 Accessibility metadata | language/title/author/pages/OCR quality/thumbnail count/fallback | source/UI assertions |
| F12-NFR-001 Authorization | native object/policy revalidation and adapter | source + R24 test |
| F12-NFR-002 Privacy | WP exporter/eraser, legal hold, no public reading state | source test |
| F12-NFR-003 Reliability | idempotency, single governed R21 outbox retry/dead-letter, duplicate-safe fingerprint jobs | source/schema + R24 test |
| F12-NFR-004 Performance | bounded catalog/private-workspace keyset pagination, HTTP range, background heavy work | static + R24 test; measured p75/p95 pending staging |
| F12-NFR-005 Accessibility | semantic controls, focus, 44px targets, RTL, reduced motion, shared Sabri visual tokens | CSS/JS assertions; assistive-tech evidence pending staging |
| F12-NFR-006 Observability | privacy-safe audit, health report, outbox/dead-letter | source test |
| F12-NFR-007 Migration/rollback | versioned schema, lock, bounded legacy import, non-destructive uninstall | source/schema test; rollback rehearsal pending staging |
| F12-NFR-008 Operability | health + safe repairs + integrity sample | source test |
| F12-NFR-009 Compatibility | WP 7.0 / PHP >=8.1 plugin header, CI PHP 8.1/8.3/8.4 | CI matrix |
| F12-NFR-010 Localization | gettext strings, Unicode/Urdu/Arabic accepted, RTL CSS | static test; locale UI acceptance pending staging |

## Central-plan additions

- Global rights-aware PDF Reader: implemented in File 12 reader UI and delivery contract.
- File 01 route registry: File 12 registers `/library/`, detail/read, governed `/library/manage/`, and private reading workspace routes with owner/visibility metadata.
- Universal Download Manager for File 12 PDFs: chunked byte-range queue with pause/resume, access revalidation, expected checksum and bounded client verification.
- Homeopathic Book Content Packs: versioned manifest registration with rights, provenance, checksum and update-manifest validation; no parallel library backend or embedded large binary.
- File 20/25 visual boundary: module styles inherit the shared Sabri primary token, with `#087A4E` as the local fallback rather than a competing module brand system.

## Future Digital Reading Intelligence 24 traceability

| ID | Feature | Primary code anchors |
|---|---|---|
| F12-FUT-001 | Advanced Reflow Reading Mode | `PLDR_Future_Data::reflow`, `assets/future-reader.js`, `assets/future-reader.css` |
| F12-FUT-002 | Read Aloud / TTS | `assets/future-reader.js` Web Speech client over lawful reflow text |
| F12-FUT-003 | Smart TOC / Outline Recovery | `PLDR_Future_Data::outline`, `future-reader-scholar.js` |
| F12-FUT-004 | Edition Comparison Laboratory | `PLDR_Future_Data::compare`, `future-reader-scholar.js` |
| F12-FUT-005 | Precise Scholarly Anchors | `PLDR_Future_Anchors`, Future REST `/anchor` |
| F12-FUT-006 | Citation Export Center | `PLDR_Future_Citations`, `/citation-export` |
| F12-FUT-007 | Bibliographic Authority Enrichment | `PLDR_Future_Authority`, `pldr_authority_lookup`, provenance cache |
| F12-FUT-008 | OCR Quality Laboratory | `PLDR_Future_OCR_Lab`, `ocr_corrections`, review API |
| F12-FUT-009 | Portable Annotation Standard | `PLDR_Future_Annotations`, JSON-LD/Web-Annotation-style import/export |
| F12-FUT-010 | IIIF Interoperability | `PLDR_Future_IIIF`, rights-aware manifest endpoint |
| F12-FUT-011 | Inside-Book Search Heatmap | `PLDR_Future_Search`, `future-reader-scholar.js` |
| F12-FUT-012 | Encrypted Offline Reading Vault | `future-reader-vault.js`, offline delivery grant, authenticated `/future/offline-authorization/{edition}` reconnect check |
| F12-FUT-013 | Ultra-Low-Bandwidth Reader | `future-reader.js` connection/data-saver text-first behavior |
| F12-FUT-014 | Multiple Reading Layouts | `future-reader.js`, `future-reader.css` |
| F12-FUT-015 | Personal Smart Shelves | `PLDR_Future_Shelves`, `shelves`, `shelf_items` |
| F12-FUT-016 | Private Non-Gamified Reading Insights | `PLDR_Future_Insights`, `reading_events` |
| F12-FUT-017 | Cross-Device Session Handoff | `PLDR_Future_Handoff`, `session_handoffs` optimistic versioning |
| F12-FUT-018 | Accessibility Quality Inspector | `PLDR_Future_A11y`, `a11y_audits` |
| F12-FUT-019 | Scholarly Reading Rooms | `PLDR_Future_Rooms`, `room_contexts`; companion communication adapter only |
| F12-FUT-020 | Knowledge Context Sidebar | `PLDR_Future_Context`; companion provider adapter only |
| F12-FUT-021 | AI-Ready Corpus Manifest | `PLDR_Future_Corpus`; deny-by-default allowlist/entitlement gate |
| F12-FUT-022 | Translation & Transliteration Overlay | authenticated Future POST + `PLDR_Future_Derived_Text`; labeled derived-provider output |
| F12-FUT-023 | Digital Preservation Laboratory | `PLDR_Future_Preservation`, `preservation_records` |
| F12-FUT-024 | Visual Duplicate / Scan Fingerprints | `PLDR_Future_Fingerprint`, duplicate-safe cron scheduling, `scan_fingerprints`; no auto merge |

Automated presence/negative-boundary checks are implemented in `tests/test-future-24-contract.php`; R24-specific invariants are permanently covered by `tests/test-twenty-round-review-r24.php`. PHP syntax, JavaScript syntax, secret scan, retained corrective-review regressions and deterministic package integrity remain in the GitHub Actions quality gate.

## Truth boundary

This traceability establishes implementation presence and automated repository evidence. It does **not** claim Hostinger staging acceptance, external provider configuration, real backup/restore proof, browser/device/accessibility/offline-vault acceptance, production deployment or live operational correctness.


## R27 cross-plan corrective trace — 10 October 2026

| Requirement / shared contract | Finding / actual source change | Retained check | Acceptance still outstanding |
|---|---|---|---|
| F12-FR-009 signed delivery, F12-FR-010 reader, F12-NFR-003 reliability | R27-D01: Move all reader-manifest policy, thumbnail, private state and accessibility read/error guards **ahead of** original bearer grant issuance in `PLDR_REST::reader_manifest` | R27 grant-order regression | Failure-injection under real WP/MySQL; bearer-token row count remains unchanged on 503 |
| F12-FUT-010 IIIF, F12-FR-008 access policy | R27-D02: Move rights/access-policy DB check **ahead of** `PLDR_Future_IIIF` preview token loop | R27 IIIF pregrant regression | Real staging rights provider/DB failure tests |
| F12-FR-001..019, F12-NFR-001..010, F12-FUT-001..024 / File 05/06/16/17/19/20/21/23/24/25/26 | Twenty separate plan-to-source focus passes of the R26 baseline; feature/source anchors only; no unsupported claim of companion operational acceptance | `tests/test-twenty-round-review-r27.php` | Real counterpart version/endpoint/role contract journeys |
| File 12 package-folder constitution | Written `12-pdf-library-foundation` vs source candidate `pdf-library-foundation-12` persists historically; **no silent rename**. | R27 review register | Founder-approved dated change request, staging compatibility/migration plan |
| Versioned release | Candidate software `1.1.0-rc.6` / DB `1.1.0` / contract `1.1.0`, package checksum controlled separately | CI exact-head build | Deployed parity + staging/live acceptance |

R27 green CI (if obtained) proves only the exact GitHub candidate. It cannot prove deployed source checksum, actual schema/migration, live UI, backup/restore, rollback, or installed companion owner contracts.


## R28 requirement-to-code and adjacent-plan audit (2026-10-11)
- **Entire governing matrix:** F12-FR-001–019 (19), F12-NFR-001–010 (10), F12-FUT-001–024 (24) = 53 unique IDs; 12 source-side external adapter anchors. All appear in `tests/test-r28-twenty-full-plan-sweeps.php` and are rechecked for 20 complete source regression passes, with 20 distinct extra invariants.
- **F12-FR-009 / File 24:** merged R27-B `PLDR_Access::current_delivery_binding()` and delivery-time fail-closed association recheck; tests `tests/test-r28-detached-grant-binding.php`.
- **F12-FR-010:** direct HTML-reader `PLDR_Reading::state`, `PLDR_Core::public_document_dto` and `PLDR_Core::policy` checked before any initial PDF/thumbnail bearer grant. Test `tests/test-r28-reader-html-pregrant.php`.
- **F12-FR-009/F12-FUT-010:** inherited and retained R27-A core REST reader / IIIF policy-before-grant sequencing; earlier tests retained.
- **File 05/06/16/17/19/20/21/23/24/25/26 and CF-04:** only File 12 source adapters inspected. External source/deployed contracts, native roles, version parity, File 23 native edit-route status and CF-04 secure-media activation must be independently tested. No duplicate domain ownership authorized.
- **Release identity:** distinct source candidate `1.1.0-rc.7`, schema & contract still `1.1.0`; root/plugin README and package markers aligned. Written planned ZIP folder vs longstanding candidate folder discrepancy is intentionally a dated change-control blocker, not silently rewritten.
- **Evidence boundary:** 20 automated repeated static source checks + specific executable PHP regressions are repository claims, not complete running-product/staging/live validation. Full details in `docs/TWENTY-ROUND-REVIEW-2026-10-11-R28.md`.


## R29 (2026-10-11): actual File 12 written-plan catalog/search and shared UI correction

| Plan provision | Previously verified gap | R29 exact code remedy | New test | Remaining live gate |
|---|---|---|---|---|
| F12-FR-001, clause 2.1: title, author, source, edition, license and access-mode catalog search | `PLDR_Search` handled only q/type/category/language | Canonical-current-edition author/source/edition/ISBN/license/year and authorized DTO access_mode facets; bounded/invalid input fail-closed | `tests/test-r29-catalog-facets.php` | Real rights/large-catalog/role journeys in staging |
| F12-FR-013, File26 search contract | Missing filters and cursor query binding | All selected filters + actor ID hashed into signed catalog cursor context; prevent incompatible filter replay | same fixture test, cursor query mismatch denial | File26 real consumer and index freshness |
| F12-NFR-004 / File20/25 accessible public navigation | `next_cursor` existed in source but `/library` page gave no Next navigation | UI filters and accessible `First results`/`Next eligible documents` links keeping only safe scalar facets | same test HTML/REST parity | RTL, keyboard, 320–1920px, very long multilingual strings |
| File 05, File 06, File 21, File 23, File 25 owner boundaries | Source adapter only; counterpart versions and real deployment not established | No duplicate course/knowledge/feed/dashboard/visual owner created; File 12 remains source document owner | Retained static R28 owner contract suite | Versioned connected provider/consumer journey |

**Unverified dependencies:** Auth File00 claims, real WordPress staging, File26 indexing, privacy/guardian/rights providers and all cross-repository deployments. Package directory name conflict remains change-control, not fixed by unrelated source patches. No live-resolution claim.

## R30 Catalog-safe input and policy parity (2026-10-11)

| Source requirement | Code/test evidence | Remaining acceptance |
|---|---|---|
| F12-FR-001, F12-FR-013, F12-NFR-004 | `PLDR_Search::search` rejects non-scalar/bool/null typed catalog parameters before query casts or filters; augmented `tests/test-r29-catalog-facets.php` with 17 structured/invalid scenarios | Actual WordPress REST and browser query error semantics, File26 index parity and high-volume entitlement pagination |
| F12-FR-008/009, File00 security, CEN-PRI-001 | `PLDR_Access::can_access_edition` re-integrates tested malformed embargo and unknown-audience denial; `tests/test-r30-policy-fail-closed.php` real-class 23 assertions | Actual physical DB corrupted rows, current File00 entitlement provider and logged-in/anonymous/suspended journeys |
| F12-NFR-009, central release status | New unique `1.1.0-rc.9` source/package; R26/27/28 and repeated 20× whole-plan static suite retained | New exact-HEAD CI and separately deployed runtime/DB/package hash verification |

## R31 (11 October 2026): File 12 / central governed ingest validation

| Plan requirement | Proven previous source gap | Changed source and regression | Pending acceptance |
|---|---|---|---|
| F12-FR-001/002, central API structural-validation law | Required and optional scalar metadata could coerce an array/object into the literal string `Array`, or throw before returning a stable error | `PLDR_Ingest::ingest` now validates known metadata/flags before DB or uploaded-file side effects; `tests/test-r31-ingest-input-shapes.php` actual method fixtures | Real WP REST/nonce/role/HTTP upload on staging |
| F12-FR-001 subjects & collections | Nested array/object JSON lists were not rejected before string conversion | Bounded indexed list and JSON list checks, invalid nested input fails closed | Actual large multilingual metadata/provider tests |
| F12-FR-002, AT-01/AT-06 | PDF/Cover nested `tmp_name` and filename could reach `is_uploaded_file`, causing TypeError | Verify file parameter array, path/name strings and scalar size/status before file operations | Real multipart hostile shape/scan on staging |
| F12-NFR-001/007 and central owner law | Not an excuse to alter schema or other owners | Source-only `1.1.0-rc.10`; same `1.1.0` DB/contract; all canonical owners preserved | Hostinger exact artifact/DB/migration, external File00/05/06/16/20/24/25 and CF04 integration, backup/rollback and Founder acceptance |

R31 is a source QA candidate, not proof of operational resolution. See `docs/TWENTY-ROUND-REVIEW-2026-10-11-R31.md`.

## R32 (2026-10-11): physical PDF integrity and precommit failure recovery

| Governing master plan | Proven prior code defect | R32 source correction | Regression / acceptance boundary |
|---|---|---|---|
| F12-FR-002 genuine PDF size + signature/EOF | Declared `pdf.size` was used to validate/read/store but not checked against actual `filesize(tmp_name)` | `PLDR_Ingest::validate_pdf` fail-closed `pldr_pdf_size_mismatch`, prior to examining the PDF or processing source | `tests/test-r32-pdf-physical-integrity.php` actual PDF validator; real multipart upload staging outstanding |
| F12-FR-006 AES-GCM and F12-NFR-003/007 safe failure cleanup | Ingest false/exception encryption failed without **immediate** private-temp deletion, although Storage has a shutdown cleanup fallback | `PLDR_Storage::delete($temp)` on both failure paths; privacy-safe exception audit | R32 source-flow checks; live file permissions + disk-full / key failure injections outstanding |
| F12-FR-007 cover/derived media and F12-NFR-007 no silent data loss | Explicit bad/partial/spoofed cover could be skipped or only reconciled after PDF commit | Pre-commit cover upload status+HTTP path gate, preserve valid absent-cover behavior | Actual `PLDR_Ingest::ingest` fixture covers three bad covers; browser/multipart staging still outstanding |
| Central owner and File05/06/21/23/25 contracts | No new external source defect proven in the File12 static owner adapters | No duplicate foreign domain owner and no DB-schema changes created | Real companion source, versions and operational API/event delivery remain unverified |

See `docs/TWENTY-ROUND-REVIEW-2026-10-11-R32.md`; do not mistake exact-head green CI for live deployment/DB parity.

## R33 11 October 2026 — rights/consent truth and divergent PR source reconciliation

| Governing requirement | Verified prior source gap | R33 exact owner fix + real test | Production boundary |
|---|---|---|---|
| F12-FR-008, F12-NFR-001; platform rights and user consent | PHP `empty('false')` / `!empty('false')` could promote a disabled flag to true | `PLDR_Core::policy_flag`; `PLDR_Ingest` normalizes patient-case consent, distinct scan and download/print/offline; `tests/test-r33-policy-flags.php` | Real WordPress/permissions and provider checks mandatory |
| F12-FR-008 licensed read/download/print/offline policy | `PLDR_Access::update_policy` could write 1 for text `false/off/no` | Canonical flag validation and normalized 0/1 persistence in real update policy method, executable regression checks persisted values | Installed DB/role, revocation and provider parity pending |
| F12-NFR-001 and central strict request contract | Nested `audience`, `entitlement_key`, `embargo_until` might be coerced during rights mutation | Explicit scalar guard before DB reads; negative cases assert no DB writes/reads | Staging JSON/REST request and malformed roles pending |
| Central release and source-reality rules | Two diverged R32 code candidates both labelled `1.1.0-rc.11` | R33 independently versioned `1.1.0-rc.12` based on proven R32 PR #22 and selective PR #23 fixes; separate exact HEAD CI | No automatic `main` merge or Hostinger deployment without Founder approval |
| File05/06/16/17/19/20/21/23/24/25/26 & CF04 | Source-side adapters do not establish matching deployed code | No replacement of foreign canonical owners; retained static adapter checks | Fetch and test real external install versions, provider contracts and coupled journeys |

**20-area audit:** `docs/TWENTY-ROUND-REVIEW-2026-10-11-R33.md`. Do not classify static presence/green CI as production acceptance.

## R34 — File12 FR-008/009 and NFR-001 rights consistency (11 Oct 2026)

| Governing requirement | Confirmed gap at exact R33 SHA | Source correction | Test/remaining gate |
|---|---|---|---|
| FR-008, NFR-001: denied historical download/print/offline permissions | Persisted noncanonical DB strings 'false'/'off' evaluated as allowed under `empty` even though R33 validated new writes | `PLDR_Access::can_access_edition` uses canonical policy_flag on each applicable persisted grant, explicit 1 only; unknown operations rejected | `tests/test-r34-access-grants.php` actual Access behavior; WordPress DB migration/legacy corruption test still pending |
| FR-008, public truth/permissions in File20/25 views | `PLDR_Core::public_document_dto` used `!empty`, and could display a prohibited operation as true | DTO uses identical exact binary parser; invalid legacy data is never advertised as permitted | Real Core DTO fixture; File20/25 live visual UX pending |
| FR-009: signed audience/operation/bearer/revocation | Explicit anonymous `-1` grant was written/hash-bound as user:-1 but delivery revalidated public for nonpositive stored user | Anonymous auth sentinel kept at access check; persisted grant user_id=0 and audience hash public; named users remain bound | Real issue_token/Core test fixture; real HTTP bearer+revocation and IDOR staging pending |
| Central plan + File05/06/16/17/19/20/21/23/24/25/26/CF04 owner contracts | Source-side references don't prove deployed other owners | No foreign data ownership moved to File12; source/schema contract 1.1.0 unchanged | Installed cross-file contracts, events/roles and staging E2E unverified |

The R34 source audit and twenty scoped checks are recorded in `docs/TWENTY-ROUND-REVIEW-2026-10-11-R34.md`. The retained 20-repeat static plan matrix is not a staging or Live test.

## R35 (2026-10-11): File12 FR-012 / NFR-001/002 private reader state and central owner integrity

| Governing plan | Confirmed R34 code gap | R35 correction | Real source QA and acceptance gate |
|---|---|---|---|
| FR-012, NFR-001/002 private Page/Note/Anchor | `PLDR_Reading::add_item` array/object/bool casts and noninteger page could store synthesized/corrupted owner state | Strict scalar/type, integer page, note length checks before insert; legitimate actions still accepted | `tests/test-r35-reading-item-input-shapes.php`, real add_item; actual WP REST/browser input and export/erasure unverified |
| FR-012 private tags | Associative/nested/malformed JSON and oversized tags could be silently coerced or dropped on write | Valid bounded indexed arrays, legitimate JSON and CSV; reject nested/map before insert | Actual PHP negative/positive test; WordPress DB and cross-device storage pending |
| FR-012 / NFR-002 legacy data integrity | `PLDR_Reading::items` accepted any decoded JSON array including maps/nested and oversized user-private tag structures | Enforce indexed list max 30 string values, audit failure, return non-partial error | Actual historical-tag DB fixture tests corrupt and valid stored data; real migration reconciliation pending |
| FR-008 rights/embargo, NFR-001 request contracts | Explicit null audience/entitlement could inherit stored value without explicit normalized validation | Deny before DB read, preserve approved `embargo_until:null` clear | Extended actual R33 Access policy fixture; staging malformed REST cases pending |
| Central release exact artifact identity | R34 PR #26 and #27 both `1.1.0-rc.13` despite different bytes | New linear R35 rc.14 candidate includes private note fixes plus prior rights/anonymous grants | Only exact final R35 SHA/Actions/ZIP hash can prove source; live deployed checksum unknown |
| File05/06/16/17/19/20/21/23/24/25/26/CF04 owners | Source adapter checks are not native consumer deployment truth | No cross-file ownership change, source DB/contract stays 1.1.0 | Real installed version and staging provider acceptance mandatory |

See `docs/TWENTY-ROUND-REVIEW-2026-10-11-R35.md`. Source-only; no merge/live claims.


## R36 — File12 and consolidated central source-to-code correction (11 October 2026)

| Plan/owner condition | Confirmed R35 code gap | R36 correction | Test and operational limit |
|---|---|---|---|
| `F12-FR-012`, `F12-NFR-001/002`; user-private tags must be a bounded list, not a public or malformed DTO | `PLDR_Reading::items` had strict checks, but separate REST `/pldr/v1/reading/items` only checked `is_array(json_decode)`, accepting historic map/nested/mixed/oversized tags | Add one `PLDR_Reading::parse_stored_tags` and invoke on direct and paginated REST path; fail closed and audit entire response | Actual `PLDR_REST::reading_items` PHP fixtures; real WP/host DB legacy-data migration still pending |
| `F12-FR-011`, `F12-NFR-001`: correct optimistic private reading progress | `PLDR_REST::save_progress` converted Array/Boolean/decimal page and edition_id to int before authorization and private transaction | `PLDR_REST::validate_mutation_shape` before any DB/precondition; legitimate simple/empty expected revision retained | Actual malformed REST method cases, no DB reads/writes; real simultaneous cross-device updates need staging |
| `F12-FR-017`, `F12-NFR-001/002`: safe rights reports, review notes/evidence and appeals | `PLDR_REST` cast nested reason/note/evidence to strings/arrays, nondecimal version to `absint` before rights/idempotent mutation | Same strict preflight for rights_case/decision/appeal and private add/delete ID/version | Executable malformed POST REST cases, pre-read/write denial; native File00 role and File19/21 event consumer staging pending |
| Central File05/06/16/17/19/20/21/23/24/25/26/CF04 domain ownership and separation | Static File12 adapters do not prove matching external deployments | No canonical owner moved to File12, no new schema or cross-domain DB joins | Deployed owner version and coupled workflow parity remain unverified |

See `docs/TWENTY-ROUND-REVIEW-2026-10-11-R36.md`. No GitHub CI result is a substitute for live deployed-code/DB/migration freeze.


| `F12-FR-008/009` signed grants, rights updates and `F12-NFR-001` no implicit object-field conversions | Reader-access, download-session, approve-document, policy-update and repair REST routes accepted nested IDs/TTL/version/operation values via PHP casts before the native owner gates | R36-D04: shared strict REST mutation input gate before DB/lookups/idempotency plus explicitly checking JSON `expected_version` shape | Actual REST method malformed inputs in R36 PHP fixture; full Hostinger HTTP/permissions/IDOR acceptance still outstanding |


## R37 (11 October 2026) — File12 canonical private deletion and consumer rights display truth

| Plan/owner rule | Confirmed R36 source mismatch | Exact R37 source fix | Test and remaining gate |
|---|---|---|---|
| File12 FR-012 private bookmarks/notes delete + NFR-002 owner privacy | `PLDR_Reading::delete_item` returned true when WordPress DB deleted 0 rows and accepted invalid IDs | Reject nonpositive ID/user; only exactly-one affected owner-scoped row can report success | Actual native reader method with 0, 1, false DB result and invalid IDs in `tests/test-r37-reader-permission-delete.php`; installed DB and restore unverified |
| File12 FR-008/010 + File25 UI access presentation | HTML/JS reader config used `!empty()` on persisted permission flags; legacy 'false'/'off' shown true although `PLDR_Access` denied | `PLDR_Reader::document_html` `canDownload/canPrint/canOffline` based on exact `PLDR_Core::policy_flag(...)===1` | Source boot-config invariant only; real JS/RTL/browser acceptance not yet performed |
| File12 FR-008/010 REST reader and central consumer DTO truth | `PLDR_REST::reader_manifest` exposed incorrect truthy string download/print/offline | REST manifest uses same canonical binary rights parser as actual Access and `PLDR_Core::public_document_dto` | Actual REST method/Core parser assertions with prior false/off/no/invalid/null stored values; staging access/IDOR pending |
| Central release source vs live separation | R36 PR30 and PR31 both rc.15 but different code | Unique R37 rc.16 based on PR31 with explicit PR30 native deletion reconciliation; no unverified main merge | Exact SHA QA on R37 only; installed File12 and File05/06/16/17/19/20/21/23/24/25/26 and CF04 contract parity unverified |

R37 twenty distinct scoped audit ledger: `docs/TWENTY-ROUND-REVIEW-2026-10-11-R37.md`. Static requirement presence/green CI does not establish production acceptance.


## R38 (11 October 2026) — File12 read input shape and scholarly citation provenance

| Source requirement | Exact R37 defect | R38 source fix and actual test | Staging/owner acceptance gap |
|---|---|---|---|
| `F12-FR-012`, `NFR-001/002/004`: private reading pages/filters | `PLDR_REST::reading_items` converted nested/boolean/negative/decimal edition_id, limit/offset and malformed cursor with PHP casts before DB/authorization | New `PLDR_REST::validate_query_shape` fails closed with 400 before reading; `tests/test-r38-rest-read-shapes-citation.php` executes actual method, invalid and valid pages, no DB/auth on invalid | Real WP REST route, enrolled/guardian user + DB/page concurrency staging unverified |
| `F12-FR-013`: lawful OCR text search, safe query access | OCR endpoint cast invalid q/cursor to "Array" and invalid limit/edition to synthetic number | Strict GET query shape before `PLDR_Search::ocr`; actual method negative and legitimate OCR controls | OCR provider/index, Arabic/Urdu search and File26 installed federator unverified |
| `F12-FR-014`: stable document/edition/page citation | Citation endpoint accepted negative/decimal/nested page as another page; invalid style name was echoed even when formatter returned Sabri content | Strict page/edition/style query; reject unsupported basic styles instead of mislabel; actual `PLDR_REST::citation` cases | Future24 advanced Chicago/BibTeX/RIS/CSL export is a separate governed output, not claimed here |
| `F12-FR-004/014`: specific immutable edition provenance | Citation renderer returned canonical document link + bibliographic labels, with no unique edition ID when same work has multiple editions | `PLDR_Reader::citation` includes immutable File12 edition ID across Sabri/APA/MLA; real tests cite editions 42 and 43 with same family ID | End-user browser/copy-paste, File25 presentation, external archival resolver not checked |
| Central ownership/trace: File05/06/16/17/19/20/21/23/24/25/26 and CF04 | Static File12 adapter does not prove native plugin installed or contract-compatible | No foreign owner or source DB contract moved; rc.17 is separate signed source candidate | Live deployed File12 checksum, actual DB/options/schema/migration, other installed owner versions and all staged workflow tests unverified |

All twenty new focused source/plan reviews: `docs/TWENTY-ROUND-REVIEW-2026-10-11-R38.md`. A 20x static matrix is a source-presence check, not an end-to-end or Live certification.


## R39 (2026-10-11) — Real calendar-date rights authority and legacy policy preservation

| Governing source | Proven R38 source gap | Exact R39 fix and actual test | Production acceptance not yet performed |
|---|---|---|---|
| `F12-FR-008` embargo and `F12-NFR-001/002` strict rights | `PLDR_Access::parse_embargo` accepted impossible calendar date via `strtotime('2026-02-31')` → March 3 | Strict ISO calendar, leap-year, clock/timezone offset and UTC validator before update; executable Access mutation regression | Hostinger rights calendar/approved timezone convention, WP policy migration and rollback unverified |
| `F12-FR-003/008` source rights expiry, historic imported data | `PLDR_Access::can_access_edition` parsed malformed stored `rights_expires_at` and `embargo_until` permissively, extending rights if day rolled | Same strict timestamp parser applied on actual access read; malformed date audits/denies ordinary reader, existing rights/manager override remains | Live imported rows, role/guardian parity, new token issuance/HTTP revocation and real staging denial unverified |
| Central canonical rights truth, `F12-FR-008` versioned access policy | `update_policy` used `(int)$current[flag]`; legacy true/on/yes values became 0 during unrelated policy updates | Parse untouched flags via canonical `PLDR_Core::policy_flag`, preserve true=1/deny malformed; real versioned access policy write fixture | Real installed historic policy data, key/provider/companion consumers, transactional rollback unverified |
| File05/06/16/17/19/20/21/23/24/25/26/CF04 split canonical owners | File12 source adapters are not evidence all consumer modules run with same version | No other canonical owner moved or source DB contract changed, rc.18 is separate QA target | Deployed plugin ZIP/DB/other File runtime parity unknown, Founder approval required |

20 separately scoped plan/code foci and limits: `docs/TWENTY-ROUND-REVIEW-2026-10-11-R39.md`.


## R40 — File12 v1.1 Future24 + central owner source requirements (11 October 2026)

| Original governing rule | R39 source gap observed | R40 source correction and evidence | Release gap |
|---|---|---|---|
| `F12-FR-003` author/source/rights expiry, `F12-FR-008` lawful access, `F12-NFR-010` time-zone correctness | PDF Ingest `date_or_null` still used `strtotime`, different from R39 strict permission/embargo parser; impossible dates and local timezone rollover could be accepted at ingestion | Reuse exact `PLDR_Access::strict_rights_timestamp`, reject invalid dates before upload/DB, normalize UTC. Actual `PLDR_Ingest::ingest` fixture validates malformed/valid inputs | True PHP/WordPress media uploads, deployed keyring, schema/calendar parity remain unverified |
| `F12-FR-004/008` edition version and no nonpublished policy mutation | Pending edition comparison mapped omitted download/print/offline to false and omitted embargo to empty, generating false positive mismatch against existing valid rights | Inherit current canonical grant for absent fields; compare embargo only when supplied. Source behavior invariants explicitly tested. | Actual post-upload pending HTTP path untested; needs real WordPress staging |
| `F12-FR-001` governed catalog, `F12-NFR-004` bounded pagination/performance | `PLDR_Search::search` `absint` silently transformed `page=-2` to `2`, decimals, zero, and oversized numeric strings | Explicit whole-positive numeric guard before DB; actual `PLDR_Search::search` malformed and positive fixtures. Existing booleans rejected through earlier general filter error. | Measured p75/p95 and real catalog loaded-query performance remain unverified |
| Central File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 ownership and privacy contracts | File12 source adapters alone are not installed module parity evidence | No cross-module ownership moved, no DB schema or interface migration, source candidate rc.19 | Installed native owner version/contract, Feed/Search/Preview/Reader and rollback integration in staging outstanding |

Detailed twenty distinct scoped source-to-plan inspections: `docs/TWENTY-ROUND-REVIEW-2026-10-11-R40.md`. Source presence/green CI never certifies live production behavior.


## R41 — File12 amended, consolidated central and companion-owner traceability (2026-10-11)

| Governing requirement | Proven R40 source gap | Exact R41 correction and test scope | Release gate |
|---|---|---|---|
| File12 `F12-FR-001` / `F12-NFR-004`, central bounded lawful discovery | WordPress REST route `sanitize_callback=absint` converted `page=-2` to 2 **before** strict `PLDR_Search::search` validation | Remove early route coercion. Actual `PLDR_REST::register` callback metadata and `PLDR_REST::library` wrong-pagination requests tested, no DB side effects | Genuine HTTP WordPress REST testing, File20 shell, File26 federated catalog, PHP deployment parity not verified |
| File12 `F12-FR-010` reader, `NFR-001` field/object auth | `PLDR_REST::reader_manifest` still accepted bad nested/negative/decimal edition shape through `absint`, unlike R38 GET endpoints | Invoke same canonical query-shape validator before DB and native access checks; actual handler malformed requests tested | Browser navigation, signed delivery/guardian/anonymous/RTL/a11y staging unverified |
| File12 `F12-FR-012`, `NFR-002` private tag correctness | PHP associative `json_decode(...,true)` collapses forbidden empty JSON map `{}` and valid list `[]` to same PHP [] | Decode to preserve JSON type, reject map and allow indexed bounded string-list; actual `PLDR_Reading::items` + `PLDR_REST::reading_items` regression | Existing physical historical DB rows, File00 identity, privacy export/erase and cache/index leakage acceptance unverified |
| Central and File05/06/16/17/19/20/21/23/24/25/26/CF04 canonical ownership | File12 source-side consumer adapters cannot certify actual companion plugin deployment and contract versions | No canonical foreign source moved into File12 and no schema upgrade; R41 unique rc.20 package candidate only | Founder-approved installed parity audit, backups, two staging cycles, rollback and live retest pending |

Twenty distinct scoped source-to-plan review paths and limits recorded in `docs/TWENTY-ROUND-REVIEW-2026-10-11-R41.md`. Neither passing CI nor a source anchor establishes Production readiness.
