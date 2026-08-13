# File 12 — R20 fresh twenty-round corrective review

Date: 2026-08-13

Baseline exact repository HEAD: `fdd41a8120f91c9978226a55654fe53fc80ba980`

Source-correction closure after Round 20 batch: `2796a17a37f46cf1072ce6db0cf72b8da9107cdc`

## Mandatory review discipline

Every numbered round was completed in full before any correction for that round began. During a numbered review, defects were only recorded in that round's defect ledger. After the review was closed, all proven defects from that round were corrected as a post-review batch, the affected scope was retested, and only then did the next numbered review begin.

Repository/package/CI evidence in this record is not staging or live evidence. Exact deployed code remains unverified until deployment parity is separately established.

## Round 1 — exact baseline/package/scope/ownership parity

Result: **Clean.**

R19 artifact checksum/version markers, File 12 canonical ownership, Future-24 scope and PHP syntax were reconciled against the amended File 12 plan and central release law. No new repository defect was proven.

## Round 2 — public REST cache/privacy/projection

Result: **Defect.**

The File 12 REST surface lacked an explicit shared-cache privacy policy for conditional/private endpoints, and the public OCR-quality response could project pending/unreviewed correction material. Post-review correction added `PLDR_Response_Policy`: public anonymous catalog caching is explicit, all other File 12 REST responses are private/no-store/noindex, and ordinary readers receive an approved-corrections-only OCR projection with fail-visible database reads.

## Round 3 — anonymous/idempotency/rate/replay abuse

Result: **Clean.**

Core/Future durable mutations retain request fingerprinting, atomic idempotency reservation/completion, pending/conflict states and serialized mutation-rate accounting. No new defect was proven.

## Round 4 — database write completeness/transaction semantics

Result: **Defect.**

The reliable outbox writer omitted `last_error`, while the base table declared it `NOT NULL` without a default. Strict SQL modes could therefore reject otherwise valid event insertion. Post-review correction introduced a verified forward schema correction making `outbox.last_error` nullable.

## Round 5 — rights/source/license/access invariants

Result: **Defect.**

Publication/restoration paths could proceed without a shared explicit fail-closed rights-expiry eligibility guard. Post-review correction introduced `PLDR_Rights_Policy` to require complete source/rights evidence, an approved rights basis, unexpired/parseable rights and a clean available object before publication/restoration entrypoints proceed.

## Round 6 — ingest/upload/temp cleanup/dedupe

Result: **Defect.**

Generic private temporary files did not have guaranteed end-of-request cleanup if an exceptional branch failed to delete them explicitly. Post-review correction added tracked private temporary paths plus shutdown cleanup.

## Round 7 — cryptography/storage/key rotation/range integrity

Result: **Defect.**

Key rotation could verify/decrypt an object without a full expected-plaintext reconciliation, post-write verification and exact sampled-state CAS before replacing metadata. Post-review correction hardened the rotation path with integrity verification before and after re-encryption, exact-state CAS and old-object deletion only after commit.

## Round 8 — reader/access-token/delivery integrity

Result: **Defect.**

Delivery-integrity quarantine was not bound to every immutable field of the exact sampled object. A concurrent object replacement/key rotation could otherwise make quarantine evidence stale. Post-review correction binds quarantine CAS to storage name/scope, key ID and both stored checksums.

## Round 9 — privacy export/erase/retention coverage

Result: **Defect.**

Durable OCR review, rights/takedown reporter and accessibility-verifier records were not all visible in personal-data export coverage. Post-review correction added an export projection for those records while leaving canonical erasure/anonymization to `PLDR_Privacy`.

## Round 10 — concurrency/CAS/idempotency boundary checkpoint

Result: **Clean.**

Access policy, rights decisions, reading items, OCR review, preferences, shelves, handoff, fingerprints and idempotency state retained optimistic/atomic concurrency controls. No new repository defect was proven.

**First-ten defect rounds: 2, 4, 5, 6, 7, 8, 9.**

**First-ten clean rounds: 1, 3, 10.**

## Round 11 — Future-24 provider provenance/degraded mode

Result: **Defect.**

Knowledge-context provider results trusted a declared File 05/06/16 owner plus `canonical=true` without enforcing a same-origin canonical URL. Post-review correction requires same-origin scheme/host/port and rejects wrong-origin provider links as provenance failures.

## Round 12 — OCR corrections/search quality

Result: **Defect.**

Correction submission/review was bound to immutable base OCR rather than the current approved-correction overlay, and the core OCR-search route searched stale base OCR. Post-review correction revalidates corrections against the current derived layer and adds an approved-correction-aware bounded OCR-search projection with signed continuation cursors.

## Round 13 — annotations/citations/IIIF portability

Result: **Defect.**

The code claimed `SvgSelector` support while the storage sanitation path would strip SVG markup and could not preserve it losslessly. Post-review correction rejects SVG selectors fail-closed until a lossless security-reviewed representation exists; TextQuote/Fragment/CSS support remains explicit and bounded.

## Round 14 — shelves/preferences/handoff/insights private state

Result: **Defect.**

Cross-device handoff accepted precise selector types while silently discarding selector value/region/prefix/suffix fields. Post-review correction preserves and validates supported selector data, page binding and bounded regions, and rejects unsupported SVG selectors.

## Round 15 — AI corpus/derived/context ownership and entitlement

Result: **Clean.**

AI corpus remained deny-by-default and File-16-consumer-bound; derived text remained source-bound, rate-limited and non-authoritative; context remained source-bound and companion-owner-only. No new defect was proven.

## Round 16 — preservation/fingerprint/object integrity

Result: **Defect.**

Canonical objects store both plaintext `sha256` and encrypted-byte `encrypted_sha256`, but preservation/health evidence did not verify the actual encrypted file against `encrypted_sha256`. Post-review correction introduced `PLDR_Object_Integrity` and exact integrity repair/preservation paths that verify ciphertext checksum plus authenticated plaintext checksum before recording verification or rotating keys.

## Round 17 — schema/migration/uninstall/upgrade atomicity

Result: **Defect.**

The code relies on transactions and `FOR UPDATE`, but schema creation did not prove the transactional storage engine. Post-review correction extends the verified schema correction to require/verify InnoDB for every base and Future-24 transactional table, while retaining the outbox nullability correction. The correction revision is written only after all postconditions pass.

## Round 18 — frontend/offline/RTL/accessibility/reliability

Result: **Defect.**

IndexedDB write requests could resolve on request success before the enclosing read-write transaction committed, allowing a later transaction abort to be falsely reported as a successful vault write. Corrupt/incomplete offline copies were also left in place after open failure. Post-review correction waits for transaction completion before resolving writes and purges failed/corrupt local copies.

## Round 19 — operations/cron/observability/release readiness

Result: **Defect.**

The new verified schema-correction revision was release-critical but was not visible as a first-class health/admin readiness gate. Post-review correction exposes required/applied schema-correction revision in health evidence and blocks readiness/raises an admin error notice when the correction is not current.

## Round 20 — final holistic adversarial closure

Result: **Defect.**

The final cross-cutting review found defects introduced at integration seams: the exact-integrity REST repair interceptor could bypass the canonical repair idempotency wrapper; admin publication/rights preflight checks could read eligibility state before coarse authorization/nonce validation; and the new privacy extension had created a second erasure owner even though `PLDR_Privacy` already canonically anonymizes those records. Post-review correction added an earlier idempotency/authorization guard and reduced the extension to export-only coverage, removing duplicate erasure ownership. A cursor-argument suspicion was also rechecked and found already correct (`after_page` is passed as the fifth OCR paging argument), so it was not counted as a defect.

## R20 result

Defect rounds: **2, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14, 16, 17, 18, 19, 20**

Clean rounds: **1, 3, 10, 15**

Total: **16 defect rounds / 4 clean rounds / 20 completed rounds.**

All corrections were made only after the corresponding numbered review had been completed and its defect ledger closed.

## Production-truth boundary

This R20 record proves repository review/correction work only after exact-head automated evidence is separately green. It does not prove Hostinger staging, deployed package parity, deployed database/schema/migration state, backup/restore, real-role browser behavior, Founder acceptance, live deployment or operational verification.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
