# File 12 — Eleventh Fresh Ten-Round Corrective Review (R11)

Date: 2026-08-11  
Repository: `majidhussainqadri1-dot/12-pdf-library-and-digital-reading-foundation`  
Branch: `feat/file-12-future-24-v1.1.0-rc1`  
Frozen starting HEAD: `3b86bcdaf8303173d7b63f9ad3a360ab0bae3e40`

## Governing discipline

This is a new review cycle. R1–R10 are historical evidence and were not re-counted. Each R11 round began only after the defect discovered in the previous round had been corrected and committed. Repository/source evidence is not staging/live evidence.

The review was driven by the File 12 governing requirements for object/state authorization, privacy, provider-degraded behavior, bounded work, rate/replay/concurrency/abuse controls, migration integrity, reliable failure/reconciliation, Future-24 provider boundaries, and truthful delivery state.

## Round 1 — Core migration-lock ownership and stale takeover

**Defect found.** The core schema migration lock used a stale-lock `update_option()` takeover and owner-insensitive `delete_option()` release. Two stale contenders could race, and a runner could delete a lock that had been replaced by another runner.

**Correction.** The lock is now an owner-bearing JSON payload with a UUID token and acquisition time. Stale takeover uses an exact-value compare-and-set SQL update, release deletes only the exact owned payload, option cache is invalidated, and legacy numeric lock values remain tolerable during transition.

Commit: `06de4c7e1fc9494832af1807475758f727dd5b69`

## Round 2 — Core schema truth before DB-version advancement

**Defect found.** After `dbDelta()`, core upgrade verified only that required tables existed. A partially shaped table missing a required column or critical index could still cause `pldr_db_version` and the contract version to be advanced, creating stale-schema false truth.

**Correction.** Core migration now checks critical columns and indexes across all core File 12 tables with `SHOW COLUMNS` and `SHOW INDEX`. Missing tables, columns, or indexes are recorded separately in `pldr_schema_error`; version advancement occurs only after shape verification succeeds.

Commit: `298b877ffd7b52ab414e2d504e274f00d7f3f10a`

## Round 3 — Precise scholarly-anchor source-validator failure

**Defect found.** If local OCR could not verify a TextQuoteSelector and the optional `pldr_precise_anchor_source_allowed` validator threw, a protected anchor-save request could fatal instead of returning an explicit degraded state.

**Correction.** External source validation is contained with `Throwable`; provider failure is audited without quote content and returned as explicit HTTP 503. The caller distinguishes provider failure from an ordinary source mismatch and does not persist the anchor.

Commit: `3ee7a40e361c2839997f775655f620c9e8e0d9d6`

## Round 4 — Derived-text policy/source/rate adapter failures

**Defect found.** Translation/transliteration provider execution was already contained, but three surrounding policy adapters could still throw: patient-case permission, selection/source validation, and hourly limit policy. Any of these could fatal before or around an external provider boundary.

**Correction.** All three adapters are contained. Patient-case policy uncertainty denies provider processing; source-validator failure prevents the selection from being sent externally; rate-policy failure prevents the provider call. Each returns explicit degraded HTTP 503 and metadata-only audit evidence.

Commit: `de74f14da564e521aa747afe5e6d7e80ba2bc651`

## Round 5 — Scholarly reading-room policy, anchor and compensation failures

**Defect found.** Reading-room patient-case policy and external anchor validation were uncontained. In addition, provider compensation after a local provider-reference persistence failure could itself throw and hide the reconciliation state; provider-failure auditing could also rely on a later mutable insert-id value.

**Correction.** Patient-case and anchor adapters now fail closed with explicit 503 responses. The local room-context ID is captured immediately. Provider compensation is wrapped and a `compensation_failed` state is disclosed/audited rather than masking the original persistence failure.

Commit: `2f50aca4f6bfd338cec1f198c9501f396b3b4b61`

## Round 6 — Private reading-insight rate race

**Defect found.** Reading-event rate enforcement used count-then-insert without serialization. Concurrent requests could observe the same pre-limit count and collectively exceed the hourly ceiling. The hourly-limit policy filter could also throw.

**Correction.** Per-user rate accounting is serialized with a MySQL named lock around count, policy evaluation, limit enforcement, and insert. Lock failure returns 503, exhaustion returns 429, policy-provider failure returns explicit 503, and the lock is always released in `finally`.

Commit: `a2c9833ecb28aa2e77f6f2f72fb7f13d0ac85391`

## Round 7 — OCR correction submission rate race

**Defect found.** OCR correction submissions had the same count-then-insert race, so concurrent correction requests could exceed the configured hourly ceiling. The hourly-limit policy hook was also uncontained.

**Correction.** Source/page validation is performed first, then per-user correction rate accounting is serialized with `GET_LOCK`/`RELEASE_LOCK`. The rate-policy adapter is contained; policy uncertainty returns 503 without storing a correction, limit exhaustion returns 429, and successful responses disclose serialized rate accounting.

Commit: `68ac83ba613990ee8f9d0b1f7b1b0ea5e24c93c4`

## Round 8 — IIIF delivery-limit policy failure before grant issuance

**Defect found.** IIIF canvas and preview-grant limit filters were executed without exception containment. A policy extension failure could fatal the public manifest path instead of producing an explicit degraded response.

**Correction.** Both limit policies are evaluated together inside a `Throwable` guard before any preview delivery grant is issued. Failure is audited and returns explicit HTTP 503 stating that no preview grants were issued.

Commit: `826051d505c5d3eadfde358bc8be1a514e227f3e`

## Round 9 — Offline-vault policy ordering and grant side effect

**Defect found.** The offline endpoint issued a secure offline token before evaluating the configurable vault lifetime and final rights-expiry bound. If the TTL policy failed, or the final validity window was already expired, a grant side effect could already exist despite the request being unusable/denied.

**Correction.** TTL policy and rights expiry are now evaluated first. Policy failure returns explicit 503 and expired rights return 403 before token issuance. Only after a valid offline window is established is the secure grant issued, with its short token TTL bounded by the remaining offline validity. The response records `policy_checked_before_grant=true`.

Commit: `066fd894d6eacc9d18502fa8e61192fd9a7c1b98`

## Round 10 — Search-heatmap abuse budget and limit-policy failure

**Defect found.** The entitlement-filtered OCR heatmap can scan thousands of pages, yet repeated public requests had no per-caller request budget. Its scan/result limit policies were also uncontained, so a policy extension exception could fatal the path.

**Correction.** Scan/result policies are now exception-contained and fail with explicit 503 before scanning. Expensive heatmap requests use privacy-hashed anonymous or authenticated caller identity, serialized hourly transient accounting, 429 exhaustion, 503 on lock/store failure, and a fixed bounded hourly request ceiling. Existing scan/result caps and truthful truncation metadata remain intact.

Commit: `a17d4a9657d78e77b3c7751e909c09c66dc6ca0a`

## R11 result

Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**  
Clean rounds: **none**

Every discovered R11 defect was corrected before the next numbered review round began.

## Exact-head closure evidence

The R11 regression contract is `tests/test-ten-round-review-r11.php` and is wired into the File 12 Future-24 Quality Gate. Exact final HEAD, workflow-run identity and package digest are recorded in the PR/release evidence after the final quality gate; this document intentionally does not embed a self-invalidating current-HEAD value.

A QA-harness literal marker was corrected before closure so the offline-grant source check uses a literal nowdoc rather than PHP variable interpolation. That harness correction did not change production behavior.

## Production-truth boundary

R11 repository source, automated-QA and package evidence do not by themselves prove Hostinger staging or live state. Required separate gates remain exact artifact deployment to staging; deployed plugin/package/checksum parity; deployed DB/schema/migration inspection; real-role and browser/device workflows; provider outage/recovery; privacy/rights/offline/RTL/accessibility/weak-network tests; backup/restore/rollback; Founder acceptance; production deployment; live re-test; and final repository/deployed parity confirmation.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
