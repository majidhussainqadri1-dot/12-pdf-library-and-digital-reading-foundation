# File 12 — R31 new twenty-area Plan/Code Corrective Audit (2026-10-11)

## 1. Exact reality freeze
- Source baseline: R30 draft PR #20, exact immutable GitHub commit `715fa30f1f63bbe34aa05eb148a6434f355e5145`, software candidate `1.1.0-rc.9`, database source/schema and contract `1.1.0`.
- GitHub `main` HEAD `2b369ef2d609247896e1ab32118f1dc498407f51`: initial README/bootstrap, not proof of deployed code.
- This is a **new repository source inspection**. Current installed Hostinger plugin files, deployed ZIP SHA, WordPress/PHP, physical DB tables/options/rows, migration state, object/key backups, providers and runtime logs have not been inspected. Historical CI, PRs or staging plans cannot substitute.
- The R28 complete static source plan test is preserved, not presented as independent human runtime verification.

## 2. Governing written plans (actual supplied DOCX basis)
1. `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx`: F12-FR-001..019, F12-NFR-001..010, F12-FUT-001..024; mandates genuine uploaded PDF, document metadata, bounded validation, lawful rights, private objects, owner-based consent and access, reader/search, preservation.
2. `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`: central API and event constitution, strict validation, no unbounded/malformed ingestion, privacy/lawful-basis, source/DB/deployed truth separation and staged release.
3. Supplied `05-File-05-Learn...`, `06-File-06-Homeopathy-Encyclopedia...`, `21-Sabri-Complete-Home-and-News-Feed...`, `File-23-Doctor-Founder-Publishing-Dashboard...`, `25-Complete-Public-UI...` plans for companion native ownership. Other File00/01/16/17/19/20/24/26 and CF-04 only checked by File12 source-side interfaces; their deployed contracts cannot be inferred.
4. Source plans call for explicit failure on invalid payload, legitimate doctor/copyright/guardian gates, rollback-safe migrations, *one canonical owner per fact*, File20 shared shell, File25 visual-system components and staged Founder approval.

## 3. What exactly twenty reviews mean
Twenty **separate focused plan-to-repository code-path inspections** were performed on the frozen R30 source. The finding ledger was closed before the product-code corrective batch. This is not an assertion that 20 complete human line-by-line audits of every file, 20 separate real WordPress deployments, or 20 genuine staging/production test cycles occurred. The retained `tests/test-r28-twenty-full-plan-sweeps.php` independently repeats all 53 stable requirement-ID + 12 source companion adapter *presence* checks twenty times (1,300 assertions), with 20 distinct additional source invariants.

| Round | Requirement, original source owner and inspection | Frozen source result |
|---:|---|---|
| 01 | GitHub exact HEAD, PR20/R30, `main` and R29/R28 ancestry | Source lineage identified; deployment unverified |
| 02 | F12-FR-001..019 + F12-NFR-001..010 + F12-FUT-001..024 vs source owners | Static traceability & retained R28 20-repeat matrix present; operational acceptance separate |
| **03** | **F12-FR-001/002 + central validation: `PLDR_Ingest::ingest` required and optional scalar fields** | **R31-D01:** Required `title/author_name/source_name` and optional rights/year fields could be cast from arrays/objects, causing "Array" metadata, warnings and unintended downstream behavior |
| **04** | **F12-FR-001 collections/subjects, nested JSON lists** | **R31-D02:** Nested list item or JSON object was not type-validated before `sanitize_json_list` cast; could turn malformed data into a synthetic "Array" string |
| **05** | **F12-FR-002 genuine PDF + cover upload boundary** | **R31-D03:** `$files['pdf']['tmp_name']` and cover path were passed to `is_uploaded_file` before confirming scalar filename/path/file parameter shapes; nested input could generate PHP TypeError/500 |
| 06 | PDF signature, content MIME, EOF/polyglot, size, password restrictions | Source validation present; real hostile file samples unverified |
| 07 | Malware-scan adapter/provider provenance and quarantine, F12-FR-002/007 | Source fail-closed provider pathways present; real provider not connected/verified here |
| 08 | Rights basis, jurisdiction/expiry, embargo, evidence references, F12-FR-003/008 | R30 rights/date/audience corrections present; legal and date-provider scenarios unverified live |
| 09 | Patient-case consent/anonymization and native privacy owner | Source-side independent clearance adapter present; actual File00/24/clinic systems unverified |
| 10 | Edition family/supersession/hash dedupe/current revision, FR-004/005 | Source state/transaction pathways present; live schema and concurrent ingest unverified |
| 11 | Authenticated encrypted object storage, keyring, range playback, FR-006/009 | Existing crypto/range tests retained; actual key backup/restore unverified |
| 12 | Signed grant, role/audience/rights binding and revocation, FR-008/009 | R26/R27/R28/R30 protections present; real Role/IDOR and suspended user tests pending |
| 13 | Current original/derivative object identity on token use | R28 revalidation source & fixture retained; live stale-token playback unverified |
| 14 | Catalog bibliographic facets, malformed filters and signed cursor, FR-001/013 | R29/R30 real search fixtures green at baseline; File26 actual indexer unverified |
| 15 | Public UI, document landing, Reader keyboard/RTL/zoom, File20/25 | Source markup/navigation present; WCAG/real browser and shell deployment pending |
| 16 | REST Reader/IIIF pregrant rights check, Future-10 and signed manifest | R27 checks and tests present; real WordPress failure injection pending |
| 17 | Private progress/bookmarks/notes/shelves/export/erase, File24 | Source privacy guards present; multi-user and key-based erasure propagation pending |
| 18 | File05/06 native knowledge and course links, File16 AI corpus deny-by-default | Source-side adapters, rights/corpus filters present; actual companion installed builds unverified |
| 19 | File17/19/20/21/23/24/25/26, CF-04 owner adapters, outbox and shell slots | Source-side route/event/visual/provider hooks present; native consumer acceptance remains unverified |
| 20 | Schema correction/migration, staging rollback, release ZIP, `main` and parity | CI/source acceptance independent of Live; plan/package folder-name discrepancy still a Founder-controlled gate |

## 4. Corrective batch after completing source reviews
- **R31-D01 fixed:** `PLDR_Ingest::ingest` now validates the accepted metadata/flag keys as scalar or bool where appropriate *before* any existing-document lookup, PDF/file operation or upload side effect. Structured inputs return stable machine error `pldr_ingest_metadata_shape`, with safe field name only. Legitimate flat data is not blanket denied.
- **R31-D02 fixed:** Subject and collection inputs accept bounded ordinary string/CSV, proper indexed lists and valid JSON list syntax. Nested array/object list items, associative object JSON, malformed list JSON and oversized lists fail closed, rather than becoming printed `Array` labels.
- **R31-D03 fixed:** PDF and cover file upload arrays are checked for string `tmp_name`/`name` and scalar upload error/size *before* `is_uploaded_file`. Invalid structures fail with `pldr_ingest_upload_shape`, rather than passing arrays into PHP upload functions.
- **New actual-method regression:** `tests/test-r31-ingest-input-shapes.php` invokes real `PLDR_Ingest::ingest` with 40+ invalid scalar/list/upload fixtures and one valid-metadata control. The test fails if a DB call is made during an invalid request.
- R28's twenty-pass 53+12 full-source matrix, R29 real catalog and R30 real embargo/audience tests remain in CI. Software-only unique candidate `1.1.0-rc.10`; DB version and integration contract remain `1.1.0`.

## 5. Remaining mandatory gates (not silently reclassified as coding errors)
1. **Package folder policy conflict:** File12 written plan names ZIP root folder `12-pdf-library-foundation`, while longstanding source/package uses `pdf-library-foundation-12`. Any renaming needs approved staging migration, compatibility check, rollback and Founder consent. Never silently rename.
2. **External owner parity:** Actual installed File00/01/05/06/16/17/19/20/21/23/24/25/26 and CF-04 code/versions/roles/contracts remain unverified.
3. **Real Hostinger staging:** physical DB/upgrade/migration/options/logs, PHP extensions, malware/OCR providers, live-role/guardian/suspension/rights tests, real PDF validation + range replay, browser/RTL/offline/a11y, safe-key restore and rollback need real tests.
4. **Release law:** CI exact-HEAD → deterministic package/checksum → staging fresh/upgrade → isolated backup/restore/rollback → two fresh review/fix staging cycles → Founder approval → production deploy → Live re-test and checksum/schema parity.
5. It is prohibited to assert operational `Resolved` or installed code parity until the Live sequence actually passes.

## Five-field production-truth report
**Repository HEAD:** see exact R31 Draft PR SHA on completion; `main` is still `2b369ef2d609247896e1ab32118f1dc498407f51`.  
**Deployed Version:** UNVERIFIED.  
**DB Version:** UNVERIFIED; source target `1.1.0`.  
**Migration State:** UNVERIFIED.  
**Live Verification Status:** NOT TESTED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
