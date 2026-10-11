# File 12 R35: Twenty plan-to-source review foci, private reading-state repair and divergent-release reconciliation
Date: 11 October 2026 (PKT).

## Evidence / governing specifications
- Physically read uploaded `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx`, including File12 `F12-FR-001..019`, `F12-NFR-001..010`, `F12-FUT-001..024`, its owner/data privacy, migration and release laws.
- Read uploaded `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`, especially canonical owner, strict request shape, private notes, File12 PDF vs File25 visual vs File24 assurance vs CF04 delivery ownership.
- Read companion File05 Learn, File06 Encyclopedia, File21 Home+News Feed, File23 Founder/Doctor Publishing Dashboard, File25 Public Visual Experience plans.
- Baseline GitHub **R34 PR #26 exact source HEAD** `335952e2f7b47cd7bf95cd184cfa6de7059917ea`, run #444 green. GitHub `main` remains `2b369ef2d609247896e1ab32118f1dc498407f51` bootstrap-only. Source/plan vs installed runtime must remain distinct.
- Additional divergent **R34 PR #27** `79ef7659ba816f7959313ddf4ded047236685202`: same `1.1.0-rc.13` software name but independent code/private reading protections. Neither draft PR auto-includes the other's unique code. R35 ports only relevant additions on top of R34 PR #26, retains its previously-green access and anonymous delivery fixes, and adopts unique `1.1.0-rc.14` identity.

## Source review method and twenty distinct checks
All **twenty scoped, distinct manual source/plan checks** below completed before the correction batch. They are **not** twenty separate line-by-line exhaustive audits nor twenty full WordPress/staging/browser sessions. Also retained original automated R28 source test that repeats *each* 53 File12 FR/NFR/FUT IDs and 12 source-side external owner adapter references 20 times (1,300 static anchor matches plus 20 invariants). A static contract anchor cannot prove a feature's behavior or live deployment.

| Pass | Plan requirement, native owner, inspected code | Frozen finding / verification limits |
|---:|---|---|
| **01** | Central release parity: compare R34 PR #26 and #27 commits/unique software version | **R35-D01 release identity conflict:** divergent code from shared R33 parent both identified as `rc.13`; R35 unifies distinct approved-branch source corrections in *new* `rc.14`; main/installed remains untouched |
| 02 | FR-001 File12 catalog 16 governed types/categories; `PLDR_Core::DOCUMENT_TYPES/CATEGORIES` | Source types and DTO owner code present; real populated catalog, indexing and page/UI acceptance unverified |
| 03 | FR-002 genuine PDF upload and scanner; `PLDR_Ingest::validate_pdf/scan_file` | R31 shape and R32 actual byte-size/signature checks retained; scanner provider/runtime not tested |
| 04 | FR-003/004 rights/supersession/edition; `PLDR_Ingest` and `PLDR_Rights` | Canonical edition and rights gate source present; rights evidence and live migration not inspected |
| 05 | FR-005 hash metadata dedupe; `PLDR_Ingest::similar_candidate` | Similar candidate and guarded distinct-scan flag retained; real corpus duplicates and historical data not proven |
| 06 | FR-006 AES-GCM private encryption/restore; `PLDR_Crypto`, `PLDR_Storage` | Eager failed-encrypt cleanup and authenticated chunked source present; actual backup keys/restoration unverified |
| 07 | FR-007 cover, derived OCR/thumbnails; `PLDR_Ingest::store_cover` | Explicit cover preflight retained; actual malware/OCR/thumbnail workers and CF04 not verified |
| 08 | FR-008 public/account/entitled rights; `PLDR_Access::can_access_edition` | R33 canonical normalized flags and R34 **historic persisted false** read-fail-close retained; File00 installed guardian/suspension claims unverified |
| 09 | FR-009 signed delivery, real token operation/revocation; `PLDR_Access::issue_token/deliver` | R34 anonymous public/token-hash normalization retained; HTTP range/bearer/revocation staging unverified |
| 10 | FR-010/019 reader accessibility, page navigation; `PLDR_Reader` | Reader UI/REST/ARIA source present; 320px→1920px browser, keyboard, reduced motion/RTL still require acceptance |
| 11 | FR-011 private progress and handoff; `PLDR_Reading::save_progress` | Owner-only progress with versioning source exists; real cross-device and File00 revoke behavior unverified |
| **12** | **FR-012, NFR-001/002: private note/bookmark/highlight fields; `PLDR_Reading::add_item`** | **R35-D02:** original branch cast malformed nested `type/page/note/anchor` to PHP string/absint; could persist synthetic `Array` or incorrect page. Strict scalar field, positive-integer page, bounded note validation before insert |
| **13** | **FR-012: private tags creation; `PLDR_Reading::add_item`** | **R35-D03:** nested/associative/malformed JSON tags could be silently dropped/string-cast in sanitizer. Enforce bounded indexed simple lists or valid bounded JSON/CSV; fail-closed before private DB insert |
| **14** | **FR-012/NFR-002: private tag re-read, old/corrupted DB rows; `PLDR_Reading::items`** | **R35-D05:** prior read accepted any decoded PHP array; associative map, nested arrays, oversized lists, mixed types could return incompatible/corrupt private DTO. Enforce indexed list with at most 30 string elements, no partial leaked item list |
| 15 | FR-013 authorized catalog/full-text/OCR signed cursor; `PLDR_Search` | R29/30 actual search/input/cursor fixtures retained; real File26 provider, index freshness/spelling/Arabic-Urdu semantics unverified |
| 16 | FR-014 citations and FR-015 related knowledge; `PLDR_REST`, `PLDR_Future_Context` | Own-edition citations, File05/06 canonical links and File16 optional AI corpus references present; companion installed code unverified |
| 17 | FR-016 reader interactions and FR-017 takedown/dispute; `PLDR_Rights::file_case/decide/appeal` | Rights-native owner command and source-side interaction adapters exist; downstream File17/19/21 notifications/appeals not staged |
| 18 | FR-018/NFR-003/007/008 preservation/health/migrations; `PLDR_Schema`, `PLDR_Future_Preservation`, `PLDR_Crypto` | Source APIs/worker paths exist; real backup/restore, old schema rows, live wp_options/migration unknown |
| **19** | **FR-008/NFR-001 policy mutation API, `PLDR_Access::update_policy`** | **R35-D04:** `null` audience/entitlement previously accepted despite “explicit” update then silently inherited existing policy; reject null except `embargo_until` permitted null-to-clear. Verify no DB reads on bad input |
| 20 | FUT-001..024, NFR-004/005/009/010, File20 shell/File21 feed/File23 publisher/File25 visual/File24 assurance, CF04 / File01 canonical contracts | File12 own Future24/route/event/consumer adapters and static trace anchors present; actual installed companion versions, CSS/device accessibility, privacy-key lifecycle, File26 search and cross-owner event acknowledgement **not verified** |

## Frozen defect correction batch and actual regression
1. **R35-D01** Release candidate provenance: branch R34 PR26 `rc.13` ≠ branch R34 PR27 `rc.13`, unify complementary source under distinct **`1.1.0-rc.14`**. No silent merges or GitHub main change.
2. **R35-D02** Native private `PLDR_Reading::add_item` rejects arrays/objects/booleans in type/page/note/anchor; positive integer page and bounded note before side effects; ordinary valid note/anchor unchanged.
3. **R35-D03** Tag JSON input rejects nested objects/arrays/associative input, malformed JSON and size/list excess, while retaining legitimate CSV, JSON string lists and native lists. Early fail-closed before note DB insert.
4. **R35-D04** Explicit null `audience` and `entitlement_key` rejected on `PLDR_Access::update_policy` prior to DB queries; existing `embargo_until:null` capability to remove embargo remains.
5. **R35-D05** Historical tag records read through `PLDR_Reading::items` are checked as indexed simple string lists <=30, otherwise an audit and non-partial machine-error response.
- `tests/test-r35-reading-item-input-shapes.php` executes **actual PLDR_Reading::add_item/items**, including invalid shape and JSON inputs with proof no DB insert, valid CSV/JSON/native tags, authorized user checks, and invalid historical stored tag DTOs.
- `tests/test-r33-policy-flags.php` extended with actual null rights policy mutation negative/no-DB-side-effect cases.
- Quality workflow executes all newly added tests across PHP 8.1, 8.3, 8.4. Retains R34 actual Core/Access token, R33 105 rights tests, R32 5 real PDF validator cases, R31 50 malformed-ingest cases, R29 catalog, R30 embargo, earlier crypto/range/provider tests, R28 static repeated matrix.

## Honest release blockers / no silent repair
- Plan install ZIP inner plugin folder `12-pdf-library-foundation` conflicts with existing built root `pdf-library-foundation-12`. Do not rename/replace automatically; need Founder-approved migration and plugin upgrade dual-activation avoidance, staging backup/rollback and exact installed plugin parity.
- Need live-reality freeze Hostinger installed PHP/plugin files/version/hash; exact WP database schema/rows/options, migrations, runtime errors, keys/salts, OCR/malware/index and CF04 media provider; installed File00/01/05/06/16/17/19/20/21/23/24/25/26 companion versions. Release gates include two fresh staged review/fix cycles and restore proof, rights/age/guardian/suspension rechecks, RTL/keyboard/low bandwidth, Founder signoff, controlled deploy, live re-test.
- All source CI tests passing is not proof of live or cross-file integration completeness. None of the above live/staging steps executed.

## Five-field production reality ledger
- **Repository HEAD:** verify actual latest R35 Draft PR #28 final exact SHA and completed matching Actions run; repository `main` is still `2b369ef2d609247896e1ab32118f1dc498407f51` (README bootstrap).
- **Deployed Version:** UNVERIFIED.
- **DB Version:** UNVERIFIED (candidate source contract `1.1.0` only).
- **Migration State:** UNVERIFIED.
- **Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
