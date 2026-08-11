# File 12 — Eighth Fresh Ten-Round Corrective Review (R8)

Date: 2026-08-11

Frozen repository baseline for this independent cycle: `b7c287d40474f5b90e047cef334ef551d134ad07`.

Method: each numbered review was performed on the corrected state produced by the immediately preceding round. When a defect was proven, it was corrected before the next numbered round began. Repository/source evidence is not staging or live evidence.

## Round 1

**Defect:** Precise scholarly anchors accepted `FragmentSelector`, `SvgSelector` and `CssSelector`, but the persisted projection dropped their selector `value`. A non-text selector could therefore collapse to a type-only structure; fragment page identity and zero-area regions were not rejected.

**Correction:** Preserve bounded selector values, require a value or positive region for non-text selectors, verify `FragmentSelector page=` against the requested edition page, retain TextQuote source verification, and bound note/selector payloads.

Correction commit: `17e28aea8072b423041ab2ebfa2ffce7de69a224`.

## Round 2

**Defect:** Private Smart Shelves had no bounded custom-shelf ceiling, shelf listing was unbounded, and per-shelf item counts used an N+1 query pattern.

**Correction:** Add `CUSTOM_SHELF_LIMIT = 100`, bound listing to `LIST_LIMIT = 120`, use one joined aggregate query for item counts, fail visibly on custom-shelf exhaustion, and check transaction start for deletion.

Initial correction commit: `be0cb73d1410fcb542a07e710ce551788d8c698d`.

**Immediate same-round correction before Round 3:** the initial fix wrapped `list()` in a metadata object, which would have changed the established REST response shape by nesting `items`. This compatibility regression was detected in Round 2 itself and corrected before proceeding; the original flat list contract was preserved while retaining the bounds and aggregate count query.

Same-round correction commit: `0ff91043afbe10a68c2860507436210b92b09940`.

## Round 3

**Defect:** Reading Insights silently capped grouped edition aggregates at 1000 and completed-state scanning at 2000, so large accounts could receive undercounted totals with no indication that the result was incomplete.

**Correction:** Query one row past each governed ceiling, slice to the supported bound, and disclose group/completion scan limits plus `group_scan_truncated`, `completion_scan_truncated` and aggregate incompleteness.

Correction commit: `b241e89f76eefe1665be9238afecf93fab4c713f`.

## Round 4

**Defect:** The public accessibility GET surface could turn an uncached read into an external-provider call and a database write. An anonymous cache miss could therefore incur provider cost and persist an audit even though refresh authority is restricted.

**Correction:** Public non-refresh reads now return an existing persisted audit when available; otherwise they compute only a local transient heuristic without external provider invocation or persistence. Only manage/rights-authorized refresh paths may invoke the provider and persist the audit. DTOs disclose whether the result is persisted.

Correction commit: `66ac780e9d2298fadd85f73c0b03a7e5b0a6db98`.

## Round 5

**Defect:** The public derived-text POST path could invoke approved translation/transliteration providers without a server-side provider-call frequency ceiling, exposing cost and resource-amplification risk.

**Correction:** Add a bounded hourly provider-call ceiling, account-or-privacy-hashed-anonymous buckets, serialized rate accounting with a bounded MySQL advisory lock, fail-closed storage/lock behavior, explicit HTTP 429 rate-limit responses, and reliable lock release.

Correction commit: `501e8df008c571fc0ffecc3107603d2798860f86`.

## Round 6

**Defect:** Recomputing OCR/visual scan fingerprints used replacement persistence with `version=1` and fresh creation timestamps, destroying version/provenance continuity despite the schema having an explicit version field.

**Correction:** Existing fingerprints now update through version CAS with incremented versions while preserving creation provenance; new fingerprints start at version 1. Persistence remains transactional and conflicts fail visibly. The response exposes fingerprint-version evidence.

Correction commit: `6aee10d35014e03678f3661562c87043e3c6a6be`.

## Round 7

**Defect:** Portable annotation export generated a new UUID on every export, so the same local annotation had no stable portable identity. Re-importing the same annotation could duplicate it, and selector `refinedBy` information was discarded during import projection.

**Correction:** Export stable privacy-preserving annotation identifiers derived by HMAC from user/edition/item identity; import deduplicates by a hashed stable identity tag, reports skipped duplicates, and preserves bounded FragmentSelector refinement metadata.

Correction commit: `7b166380d1742cc01efad7b6718a7e2011ce5206`.

## Round 8

**Defect:** User-bound delivery/access-token records were absent from the File 12 personal-data export, erasure batches and remaining-work calculation. Privacy erasure could therefore report completion while user-linked delivery-grant metadata still existed.

**Correction:** Export bounded non-secret delivery-grant metadata without token/audience hashes; erase user-bound grant rows in bounded batches; and include those rows in privacy-erasure completion accounting.

Correction commit: `cfd9a648e8eda73ab806a1744f75139ae1019f18`.

## Round 9

**Defect:** Page-specific citation URLs and CSL records carried a locator, but the citation key remained edition-only and page-specific BibTeX/RIS output did not encode the page locator. Structured page citations could therefore collide or lose locator fidelity.

**Correction:** Page-specific citation keys now include the page identity; BibTeX includes a page field, RIS includes `SP`, and citation DTOs disclose locator binding.

Correction commit: `fe8d72ce7a639b3d634450141ab67b0ca3c68420`.

## Round 10

**Defect:** Reading-room anchors accepted Fragment/SVG/CSS selectors while dropping their selector value; non-text selector validity, fragment page identity and positive region dimensions were not enforced.

**Correction:** Preserve bounded selector values, require valid value/region state, reject mismatched fragment page identity, require positive regions, and disclose selector-value preservation while retaining page-scoped text-source validation.

Correction commit: `87edb057d0c9ec8319ee5fc3450067af44419632`.

## R8 result

Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**

Clean rounds: **none**

Every proven defect was corrected before the next numbered review began. Round 2 also contained an immediate same-round API-contract compatibility correction before Round 3 was permitted to start.

## Production-truth boundary

This review establishes repository/source corrections only. It does not establish Hostinger staging or live deployment state, deployed package/checksum parity, deployed DB/schema/migration state, real-role browser workflows, backup/restore/rollback success, Founder acceptance, or live re-test.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
