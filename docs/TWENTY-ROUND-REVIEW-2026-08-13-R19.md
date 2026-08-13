# File 12 — Nineteenth Fresh Twenty-Round Corrective Review (R19)

Date: 13 August 2026

## Governing basis and frozen baseline

This review applies the File 12 PDF Library governing plan, Future-24 amendment, canonical ownership rules, and the platform release-truth discipline. Frozen repository baseline before Round 1: `264ca1fb80377f734e12b6afcae5d5b05fb80ce4`.

Mandatory review discipline was followed exactly: **each numbered review round was completed in full before any correction from that round began**. Findings were first accumulated in the round defect ledger; only after review closure were all proven defects corrected as one batch, syntax/regression retesting performed, and the next round started.

Source-review closure after all twenty review/fix/retest rounds: `8eb4ef4d64f59c8280874c486bad18c111bfbb5a`.

## Round 1 — authorization, IDOR and route authority

**Result: clean.** File 00/native authorization, object ownership, route permissions, sensitive-object access and fail-closed boundaries were rechecked; no new repository-level defect was proven.

## Round 2 — optimistic concurrency and stale mutation defense

**Defects:** OCR correction reviewer decisions lacked a client `expected_version`; private reading-item DELETE likewise lacked an exact version precondition.

**Post-review correction:** both mutations now require exact client versions and reject missing/stale state before mutation.

## Round 3 — database failure semantics

**Defects:** delivery-token edition reads, OCR review edition reads and selected administrator list reads could collapse database failure into ordinary forbidden/missing/empty states.

**Post-review correction:** those paths now preserve database failure as explicit degraded/server errors rather than innocent 403/404/empty results.

## Round 4 — idempotency, replay and universal mutation abuse controls

**Result: clean.** Core and Future mutation wrappers retained exact-request idempotency, replay conflict handling and mutation-rate protection; no new defect was proven.

## Round 5 — privacy export, erasure and reconciliation

**Defect:** user-bound idempotency rows keyed by `actor_id` were absent from the privacy export/erasure/reconciliation graph.

**Post-review correction:** idempotency ownership is now included in privacy export, erasure and completion reconciliation.

## Round 6 — access/rights revocation atomicity

**Defect:** several owner-state mutations could commit before delivery-grant revocation, leaving stale grants after policy/rights/quarantine changes.

**Post-review correction:** access-policy, rights, ingest and preservation quarantine paths now couple owner-state transition and grant revocation transactionally where required.

## Round 7 — cryptography and private storage

**Result: clean.** Chunked authenticated encryption, key-ring semantics, nonce/AAD binding, private storage and range delivery were adversarially rechecked; no new defect was proven.

## Round 8 — ingest, duplicate detection and edition lifecycle

**Defects:** eleven related defects were proven: DB-error/missing-state ambiguity; missing document version precondition; ISBN/dedupe DB failure masking; stale database error contamination; loose rights-date parsing; cross-family supersession risk; pending edition mutation of live metadata/policy; missing publication CAS; pending edition becoming active policy; missing old-grant revocation; and duplicate version advancement.

**Post-review correction:** ingest/publication now uses exact preconditions, fail-visible reads, strict rights dates, safe document-family boundaries, pending-state isolation, publication CAS, atomic active-policy transition and grant revocation without duplicate version advancement.

## Round 9 — reader JavaScript/backend contract parity

**Defects:** reader DELETE did not send the newly required `expected_version`; deletion failure and private-overlay GET errors were not surfaced adequately.

**Post-review correction:** browser code now sends the exact item version and reports delete/private-state errors.

## Round 10 — OCR search pagination and failure semantics

**Defects:** OCR authorization DB failure could become a 404-like result, and search exposed only a bounded first page without governed continuation.

**Post-review correction:** authorization failure is explicit and OCR search now uses a signed keyset cursor with bounded continuation.

### First ten rounds checkpoint

Defect rounds: **2, 3, 5, 6, 8, 9, 10**.

Clean rounds: **1, 4, 7**.

## Round 11 — portable annotations and bounded export

**Defect:** W3C annotation export was hard-capped at 1000 rows without continuation.

**Post-review correction:** backend annotation export now has bounded keyset continuation with `next_after_id`.

## Round 12 — corpus, citations, derived text, context and authority

**Result: clean.** Corpus allowlisting, patient-case boundaries, citation/source provenance, provider governance and authority separation were rechecked; no new defect was proven.

## Round 13 — OCR/fingerprint/preservation integrity

**Defects:** initial OCR query DB failure could be masked; a missing fingerprint edition could become an empty result; preservation verification/quarantine could report success after concurrent object/key/hash rotation.

**Post-review correction:** DB/missing-state semantics are explicit and preservation persistence is CAS-bound to the sampled object state.

## Round 14 — external reading-room provider handoff

**Defect:** an external room provider could succeed and then access be revoked before local activation, leaving an orphan external resource.

**Post-review correction:** access is revalidated after the provider call and compensating provider hooks run on revocation/DB/transaction/event failures.

## Round 15 — provider resilience and background jobs

**Defects:** accessibility GET could invoke/persist provider output; IIIF could mask authorization/policy-read failure; Future cleanup lacked continuation; fingerprint background processing lacked bounded retry.

**Post-review correction:** GET remains read-only, provider/security failures are fail-visible, cleanup continues in bounded batches and fingerprint jobs retry with controlled attempts.

## Round 16 — migration and compatibility

**Defects:** legacy document mapping had a dedupe boundary problem, and legacy migration could fabricate a synthetic checksum then publish an object not cryptographically verified from source evidence.

**Post-review correction:** unverified legacy objects are quarantined/rights-review pending and checksum provenance is explicitly distinguished.

## Round 17 — bounded workload and scale

**Defects:** grant revocation could materialize an unbounded edition-ID set; bundled content-pack manifest scanning lacked sufficiently strict file-count/metadata-size bounds.

**Post-review correction:** revocation is join-based/bounded and content-pack metadata/file processing is capped.

## Round 18 — audit, outbox and health observability

**Defects:** audit sanitization was shallow; audit context lacked depth/item/string/byte bounds; reliable outbox payload size was unbounded; health checks did not block on missing scheduled jobs; Future loader/schema revision errors were not surfaced strongly enough.

**Post-review correction:** recursive bounded audit sanitization, 16 KiB context cap, depth/item/string bounds, 64 KiB outbox payload cap, scheduled-job blockers and Future schema/loader evidence were added.

## Round 19 — package/version/contracts/documentation drift

**Defects:** `STATUS.md` was materially stale relative to the R18 candidate/evidence; a dead private rights method still embodied obsolete post-commit revocation/reconciliation semantics and contradicted the strengthened transactional design.

**Post-review correction:** stale status claims were replaced with truthful current evidence and the obsolete dead method was removed.

## Round 20 — holistic final adversarial pass

The full repository was re-reviewed across PHP/JavaScript, mutations, query pagination, DB semantics, migrations, provider boundaries, private state, portability, concurrency and frontend/backend contract parity before any correction began.

**Twelve defect groups were proven:**

1. Rights appeals lacked exact parent version precondition/serialization and allowed duplicate parallel appeals; the original case could still be decided after an appeal existed.
2. Upgrade logic could reset an in-progress legacy migration checkpoint.
3. Legacy document traversal used OFFSET and could skip rows when source data changed.
4. Legacy interaction reconciliation hard-failed above 5000 rows instead of resuming.
5. Legacy interaction dedupe used prefix-prone `LIKE` matching for IDs.
6. Private reading-item public query used OFFSET rather than signed stable cursor pagination.
7. Reader frontend ignored reading-item continuation.
8. File 16 corpus manifest API used OFFSET/`next_offset` instead of a governed cursor.
9. Smart Shelves GET mutated the database by creating default shelves.
10. Smart Shelf browser request omitted backend-required `expected_version`.
11. W3C annotation frontend exported only the first backend page.
12. OCR search frontend exposed only the first cursor page.

**Post-review correction:** rights appeal now uses exact version/row lock/single-child appeal semantics; migration checkpoints are preserved and keyset/resumable; legacy dedupe matches exact encoded IDs; reading-items and corpus use signed keyset cursors; shelf initialization moved to idempotent POST; shelf browser sends version; portable annotation export follows all pages; OCR/private-reading UIs expose safe continuation.

## Final R19 defect distribution

Defect rounds: **2, 3, 5, 6, 8, 9, 10, 11, 13, 14, 15, 16, 17, 18, 19, 20**.

Clean rounds: **1, 4, 7, 12**.

## Evidence boundary

The source-review closure `8eb4ef4d64f59c8280874c486bad18c111bfbb5a` is repository source evidence only. Permanent R19 regression evidence and exact-head GitHub Actions evidence are separate follow-up evidence commits. Hostinger deployed package/version/checksum, database/schema/migration state, real-role/browser/provider/rollback behavior and production live verification remain separate gates.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
