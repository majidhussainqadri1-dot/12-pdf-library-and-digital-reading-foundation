# File 12 — Eighteenth Fresh Twenty-Round Corrective Review (R18)

Date: 12 August 2026

## Governing basis

This review was performed against the File 12 PDF Library governing plan and its Future-24 amendment. The controlling review rules used here are: every state-changing transition must use current state + expected version + authorization/policy checks; mutating APIs require idempotency/replay protection/rate limiting/authorization/validation/audit; query APIs require bounded/cursor pagination and safe audience filtering; cron expiry must be reconciled at request time; owner data and reliable outbox facts must not silently diverge; private reading state must remain privacy-protected/exportable; and heavy/background work must be bounded/resumable.

Frozen repository source baseline before Round 1: `e69ec6bfda55602a284c70c47de81a801d3808c7`.

The temporary transfer/review tooling was not treated as product source. Rounds were committed sequentially on the corrected state; each proven defect was corrected and syntax-checked before the next numbered round started.

## Round 1

**Defect:** rights-case decisions allowed `expected_version=0`, so a stale client could decide a case without the governing optimistic precondition.

**Correction:** `expected_version` is mandatory; omission returns 428 and stale version returns 409 before mutation.

## Round 2

**Defect:** document publication approval also allowed a missing expected version, and its source document/edition/object reads could collapse database failure into ordinary missing/ineligible state.

**Correction:** publication approval now requires the exact document version and fails visibly when source reads cannot be trusted.

## Round 3

**Defect:** edition `rights_expires_at` was enforced by periodic expiry processing/offline-specific logic but not by the general request-time access decision, so delayed cron could leave an expired edition readable.

**Correction:** every access decision now evaluates rights expiry at request time; invalid expiry metadata fails closed for ordinary readers while remaining reviewable by governed curators.

## Round 4

**Defect:** `/reader-access` creates durable delivery grants but was outside the idempotency wrapper.

**Correction:** delivery-grant issuance now requires the standard exact-request idempotency contract.

## Round 5

**Defect:** `/download-session` also creates a durable download grant/job response but was non-idempotent.

**Correction:** the download-session mutation now runs through the same idempotent mutation wrapper.

## Round 6

**Defect:** core File 12 mutations had idempotency but no universal server-side mutation abuse ceiling, despite the governing API constitution requiring rate limiting for mutating APIs.

**Correction:** a privacy-scoped, serialized hourly mutation limiter was added to the core mutation wrapper, with fail-closed lock/policy/store errors and 429 responses.

## Round 7

**Defect:** Future-24 mutations likewise depended on scattered endpoint-specific throttles and lacked the universal mutation rate gate.

**Correction:** the Future-24 idempotent wrapper now uses the shared mutation limiter before executing the reserved mutation.

## Round 8

**Defect:** private reading-state reads could turn an authorization database failure into the innocent default `{page:1, percent:0}`.

**Correction:** authorization DB failure is now an explicit degraded error; only genuine no-access/no-state returns the ordinary default.

## Round 9

**Defect:** reading-insight aggregate filtering could silently hide an edition when its authorization recheck hit a DB failure, returning a partial aggregate as though complete.

**Correction:** entitlement-read failure aborts the report with an explicit degraded error; no partial aggregate is returned.

## Round 10

**Defect:** completion-count filtering had the same silent partial-success failure mode.

**Correction:** completion authorization read failure now aborts the private report rather than undercounting silently.

## Round 11

**Defect:** Smart Shelf rename used an internal CAS but accepted no client `expected_version`, so stale clients could overwrite a newer rename.

**Correction:** rename requires the exact shelf version and returns 428/409 before mutation when missing/stale.

## Round 12

**Defect:** Smart Shelf deletion likewise accepted no client version precondition.

**Correction:** deletion now requires and CAS-binds the exact client shelf version.

## Round 13

**Defect:** adding/removing shelf membership did not advance shelf version at all, so membership changes were invisible to optimistic synchronization.

**Correction:** add/remove membership requires `expected_version`; real membership changes and the shelf version update commit atomically, while duplicate add remains non-mutating.

## Round 14

**Defect:** catalog query traversal exposed only legacy page semantics, lacked the governed signed cursor contract, allowed very deep page windows, and accepted unbounded search text length.

**Correction:** the catalog now supports signed audience/query-bound cursors and internally advances by stable `(updated_at,id)` keyset ordering; deep legacy traversal is forced onto the cursor path, and normalized query text is bounded. Catalog edition/access/policy projection read failures remain fail-visible.

## Round 15

**Defect:** when local OCR reflow was unavailable, repeated public reflow requests could invoke the external reflow provider without a provider-call ceiling.

**Correction:** external reflow invocation is guarded by a privacy-scoped serialized provider rate limiter; over-limit/provider-rate-state failures do not fabricate output.

## Round 16

**Defect:** outline GET could invoke the external outline provider on every request without a provider-call ceiling.

**Correction:** outline provider calls use the same provider limiter; when unavailable/rate-limited the response degrades to the lawful local heuristic instead of claiming external success.

## Round 17

**Defect:** scan-fingerprint review read at most 1000 evidence rows and returned at most 50 candidates without reliably distinguishing a complete result from a truncated one.

**Correction:** source evidence is probed to 1001 rows and candidate matches beyond 50 are detected; when either bound is exceeded the endpoint returns an explicit truncation error rather than a misleading partial candidate list.

## Round 18

**Defects:** several operations violated bounded/resumable-job expectations: token/idempotency cleanup used unbounded deletes; rights-expiry processing selected an unbounded expired set; search-index repair loaded all documents in one request; and corrupt outbox JSON was converted into an empty payload and could be dispatched as a fabricated event body.

**Corrections:** cleanup is bounded to 500 rows with continuation scheduling; rights expiry processes 100 documents per batch and continues when full; search-index repair is a persisted 100-row cursor batch; corrupt outbox payload JSON is dead-lettered/audited and never dispatched.

## Round 19

**Defect:** integrity sampling could report verification/quarantine without confirming that the object row was still the exact sampled storage/key/checksum state; DB update failure/concurrent rotation could therefore make the report untruthful.

**Correction:** verification/quarantine persistence is compare-and-set bound to the sampled object identity/storage/key/encrypted checksum. A failed/stale persistence is explicitly reconciled and never falsely reported as quarantined/verified.

## Round 20

**Defects:** privacy export included shelf definitions but omitted the user’s private shelf-membership rows; the erasure completion audit also wrote the erased subject’s raw numeric user ID into new audit context.

**Corrections:** privacy export now includes bounded Smart Shelf membership with document/edition/shelf context; the post-erasure audit records a privacy-hashed `subject_ref` rather than the raw erased user ID.

## R18 defect distribution

Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20**.

Clean rounds: **none**.

Source-review closure after Round 20: `4b403768940cceaed99ab1fb98248bb5c9d457f8`.

## Evidence boundary

The R18 source-review closure has passed per-round PHP syntax checks and a final full PHP/JavaScript syntax pass in the sequential corrective-review workflow. A permanent R18 static regression contract and exact-head File 12 quality gate are required after this record is committed; those evidence-only commits do not alter the Round-20 product-source closure above.

Repository evidence is not staging/live evidence. Hostinger deployed package/version/checksum, DB/schema/migration state, real-role/browser/provider/rollback behavior and live verification remain separate gates.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
