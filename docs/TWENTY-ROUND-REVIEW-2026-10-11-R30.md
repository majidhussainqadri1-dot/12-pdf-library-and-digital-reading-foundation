# File 12 R30 — 20 source-to-plan checks and reconciled corrective candidate
Date: 11 October 2026 (Pakistan Standard Time).

## Governing inputs and exact frozen source
Compared File 12 "PDF Library and Digital Reading" v1.1 (Future Digital Reading Intelligence 24, amended 11 August 2026) with the three central consolidated governing master plans and actual supplied companion File05, File06, File21, File23 and File25 plans. Owner-interface review also covers Files00/01/16/17/19/20/24/26 and CF-04 **only from the File12 source side**, not from running those plugins.
The plan requires catalog/search by title, author, language, type, category, publication/source, edition, license, access mode; 19 FR, 10 NFR, 24 Future stable IDs; rights/privacy/security and versioned owner boundaries; bounded query/cursor/safe filters and staging/live release gates.

Frozen baseline: Draft PR #19 at `8ee7f4246ae90980291c031022d5adfdb40717ae`. Independent Draft PR #18 at `b2a8455685ce0aa47fce941512c36b0e7232f55c` was **divergent**. No branch is proof of installed/live PHP plugin.

A "review round" below is a different targeted code/plan check. Separately, retained `tests/test-r28-twenty-full-plan-sweeps.php` runs the complete **53 stable FR/NFR/FUT IDs + 12 adapter anchors 20 times**. Such source-presence assertions do not constitute 20 line-by-line full audits or 20 WordPress/staging/live end-to-end tests.

## Frozen 20-round ledger (before corrective batch)

| Round | Checked contract and code area | Evidence and finding |
|---:|---|---|
| 01 | Exact GitHub truth and split PRs | PR #18 and #19 were divergent; both used rc.8 for different source bytes. R30 based on #19 and selectively restores #18 code/test |
| 02 | Canonical File12 types, categories, bibliographic source | class-pldr-core.php: canonical types/categories and public-document projection present; physical WordPress acceptance pending |
| 03 | Real PDF upload/scan/hash and rights | class-pldr-ingest.php: validation, scanner and hash paths present; real provider/host proof pending |
| 04 | Rights cases/takedown/expiry | class-pldr-rights.php: case, review and expiry paths present; staging legal-case journeys pending |
| 05 | Catalog filters / malformed input | R30-D01: non-scalar user-supplied facets silently became empty; q/page/per_page casts could warn. Fail-closed validation and 17 negative fixture cases added |
| 06 | Signed token, edition/object binding | class-pldr-access.php: issue_token/current_delivery_binding present; stale-token real HTTP test pending |
| 07 | Encryption, authenticated range and private storage | class-pldr-crypto.php encrypt_file/stream_range/verify_file present; real key restore pending |
| 08 | Persisted embargo and explicit anonymous grant | R30-D02: PR #19 omitted #18 malformed embargo fail-closed behavior. Restored explicit parse/manager guard and audit |
| 09 | Known access audience / File00 entitlement | R30-D03: PR #19 omitted #18 unknown-audience denial. Restored four-value allowlist before external entitlement provider |
| 10 | REST mutation, source reader manifest and signed preflight | class-pldr-rest.php reader_manifest/owner/idempotency present; negative-path WordPress roles pending |
| 11 | Private notes, bookmarks, progress and reading workspace | class-pldr-reader.php source paths present; cross-device actual race/eraser tests pending |
| 12 | Export/erase/retention/legal hold (File24) | class-pldr-privacy.php export/erase/legal_hold present; actual backup/legal-hold proof pending |
| 13 | Citation/page provenance and Future24 coverage | class-pldr-future-citations.php and class-pldr-future.php IDs 001..024 present; functional media journeys pending |
| 14 | File05/06 educational/encyclopedic boundaries | class-pldr-future-context.php File05/File06 adapters present; real companion versions unverified |
| 15 | File16 AI corpus allowlist and patient-case privacy | class-pldr-future-corpus.php provider-consumer policy hooks present; live integration not checked |
| 16 | File17 reading room ownership | class-pldr-future-rooms.php external provider and compensation anchors present; companion messaging not tested |
| 17 | File19/21 publication-event delivery | class-pldr-r21-outbox.php dispatch/retry/dead_letter present; no real consumer delivered event proof |
| 18 | File01/20/25 navigation & shared components | class-pldr-rights.php registers route/slot/component adapters; browser shell/RTL verification pending |
| 19 | Preservation, a11y, File26 search and CF04 media | preservation inspection and File12-local safe search paths present; provider/index/assistive-tech confirmation pending |
| 20 | DB schema, package identity and release readiness | class-pldr-schema.php upgrade/migration lock present; rc.9 unique GitHub source candidate. Plan-vs-package root folder conflict left for Founder change control |

## Root causes and corrective batch (performed only after reviews)

**R30-D01 — malformed catalog filters broaden user queries.** In PR19 `PLDR_Search::search`, an array/object supplied as `author`, `source`, `license`, `edition`, `isbn`, `year` or `access_mode` became an empty string in the facet lambda and therefore silently removed the requested restriction. Structured `q`, type/category, cursor or pagination could produce conversion warnings instead of stable machine errors. Added a first-operation strict scalar/type guard for all 14 known request keys; invalid array/object/bool/null values now return `pldr_catalog_filter_invalid` with empty results rather than widening the query. Augmented `tests/test-r29-catalog-facets.php` with 17 malformed input scenarios while retaining real source-class tests for valid and denied catalog facets, entitlement and signed cursor binding.

**R30-D02 — malformed persisted embargo policy.** PR19 omitted a safety fix already independently confirmed in PR18. A nonempty malformed embargo string evaluated by `strtotime` to `false` could fail open for ordinary/anonymous viewers. Applied PR18's guard to PR19 access code, preserving a named manager exception and audit record `embargo_until_invalid`.

**R30-D03 — undefined access audience.** PR19 omitted a separate PR18 fix. Corrupted audience strings could fall through to a permissive external `pldr_user_entitled` callback. Restored explicit allowlist `public`, `account`, `education-entitled`, `assigned`, audit `policy_audience_invalid` and fail-closed before provider invocation.

**R30 governance/release correction:** PR18 and PR19 both called different code packages `1.1.0-rc.8`. Assigned new corrected candidate `1.1.0-rc.9`; ensured plugin metadata, root README, bundled readme, source tests, release manifest, staging docs and GitHub artifact name share that identity. Candidate DB/contract remains `1.1.0`, **not** live DB proof.

### New and retained tests
- `tests/test-r30-policy-fail-closed.php`: actual `PLDR_Access` class with 23 positive/negative assertions, derived from the previously green PR18 fixture.
- `tests/test-r29-catalog-facets.php`: real `PLDR_Search` fixture now checks scalar and malformed structured/boolean/null facets without silent broadening.
- R26 anonymous-authorization, R27 Reader/IIIF pregrant, R28 HTML reader/detached object and 20× whole-source 53-ID+12-adapter matrix retained.
- CI on **final unchanged HEAD** must demonstrate PHP 8.1/8.3/8.4, frontend JS and deterministic ZIP. Earlier green PR18/19 results are not transferable.

## Unresolved release and Founder decisions
The written governing File12 plan names top-level ZIP/plugin folder `12-pdf-library-foundation`; the longstanding candidate code/ZIP uses `pdf-library-foundation-12`. Do **not** silently rename an installed WordPress plugin; require a Founder-approved, staged compatibility/data/key/rollback procedure.

Required next evidence: real Hostinger active plugin/version/deployed ZIP SHA, WordPress/PHP, DB physical tables/schema/options/migration state; actual File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 contracts; role/guardian/suspension/rights/IDOR and malformed DB policy tests; genuine scanner, OCR and encrypted-key restore; browser/Urdu/Arabic RTL/offline/a11y, 2 staged review/fix cycles, backup and rollback, Founder sign-off, approved deployment, live retest and checksum parity.

**Repository HEAD:** resolve R30 final immutable SHA after latest commit and CI.
**Deployed Version:** unverified.
**DB Version:** unverified (repository target 1.1.0).
**Migration State:** unverified.
**Live Verification Status:** not performed.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
