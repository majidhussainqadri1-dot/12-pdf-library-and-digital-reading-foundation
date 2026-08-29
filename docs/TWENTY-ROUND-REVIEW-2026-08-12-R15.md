# File 12 — Fifteenth Fresh Twenty-Round Corrective Review (R15)

Date: 12 August 2026

## Governing method

This was a fresh sequential review of File 12 — PDF Library and Digital Reading. The frozen R14 repository baseline was `069893142ecf20ef3a708844df20989291900944`. Each numbered round inspected the corrected state produced by the preceding round. A proven repository-level defect was corrected before the next numbered round began.

Repository/CI/package evidence is not staging or production acceptance. Exact Hostinger deployed source, deployed database/schema/migration state and live workflow evidence remain separate gates.

## R15 rounds

1. **Core reader/search/REST fail-visible bounds.** Catalog and OCR database failures, search-provider exceptions, unbounded OCR variants/query size, reading-state silent fallbacks, reading-item payload/list bounds, reader provider failure and REST read failures were corrected.
2. **Access and secure delivery correctness.** Access-policy/object/derivative/token database failures, transaction-start checks, revocation reconciliation, provider failure, delivery reauthorization/read/update errors and the invalid `objects.updated_at` write were corrected.
3. **Ingest/provider/transaction fail-closed behavior.** Duplicate/similarity reads, transaction start/commit, supersession/publication writes, rights-evidence persistence, PDF-size policy, malware-scanner and OCR-provider failure paths were corrected.
4. **Rights lifecycle and outbox concurrency.** Rights-case reads, transaction starts, appeals, post-publication revocation reconciliation, document-status transitions, expiry worker reads and outbox lease compare-and-set ownership were corrected.
5. **Privacy-erasure boundedness.** Shelf-child deletion and durable OCR/accessibility/rights-record anonymization were converted to bounded batches with reliable remaining-work accounting.
6. **Health/repair accuracy.** Schema/table/count failures, backup-provider failures, search repair, rescan queue, key rotation, integrity reads and commit failures became explicit rather than optimistic health/repair success.
7. **Derived-text privacy classification.** Document-classification database failure or absence can no longer bypass patient-case policy or send unverified source text to a provider.
8. **Reading-room source/privacy enforcement.** Document classification and anchor-source OCR failures now block provider fallback and room persistence failures are explicit.
9. **Smart Shelf default-race serialization.** Default shelf creation is serialized per user so concurrent requests cannot create duplicate semantic default shelves where no unique `(user_id,shelf_type)` key exists.
10. **Future cleanup retention/provider/bounded jobs.** Retention-policy provider exceptions are contained and high-volume cleanup deletes are bounded and fail-visible.
11. **Preservation quarantine/schema correctness.** Edition/object reads, invalid object timestamp-column usage and quarantine revocation reconciliation were corrected.
12. **Future REST idempotency/offline failure containment.** Mutation callback exceptions are handled and finalized under idempotency; offline edition/read-rights state fails closed, including malformed rights expiry.
13. **Related-content provider containment.** Optional related-content rendering failures are contained, audited and degraded without breaking the reader page.
14. **Future schema verification read accuracy.** `SHOW TABLES`, `SHOW COLUMNS` and index-read failures are distinguished from genuinely missing schema through explicit `read_errors`.
15. **Core schema verification read accuracy.** The same fail-visible database-read discipline was applied to the core schema validator.
16. **Fingerprint provenance/source reliability.** Edition/current fingerprint/candidate/object database failures now block recomputation, persistence or comparison rather than becoming absence/empty success.
17. **Privacy export completeness.** Export table-existence/read failures now return `done=false` with reconciliation evidence rather than silently omitting data; erase helpers also distinguish missing tables from failed metadata reads.
18. **Core lookup/idempotency/current-edition fail-closed semantics.** Core lookup helpers preserve database error state, `current_edition()` no longer falls through to a latest non-published edition after a failed published-edition query, and idempotency cleanup/insert/read failures are explicit.
19. **Book-pack immutable version registration.** Manifest encoding/size is checked, an existing same version/hash is idempotent, a same version with different hash is rejected, destructive `REPLACE` was removed, and concurrent registration is reconciled.
20. **Legacy migration integrity and final adversarial sweep.** Legacy-source DB failure can no longer mark migration complete; transaction/insert/commit failures are checked; offsets advance only after a successful item; post-commit-style data skips were eliminated; legacy reading/report imports are bounded and idempotent; full plugin PHP and JavaScript syntax sweeps were rerun.

## Defect distribution

**Defects were found in R15 rounds 1–20. There were no clean R15 rounds.** Every proven defect was corrected before the next numbered round began.

## Corrected production files

R15 touched the following owning source files:

- `includes/class-pldr-access.php`
- `includes/class-pldr-admin.php`
- `includes/class-pldr-core.php`
- `includes/class-pldr-future-derived-text.php`
- `includes/class-pldr-future-fingerprint.php`
- `includes/class-pldr-future-preservation.php`
- `includes/class-pldr-future-rest.php`
- `includes/class-pldr-future-rooms.php`
- `includes/class-pldr-future-schema.php`
- `includes/class-pldr-future-shelves.php`
- `includes/class-pldr-future.php`
- `includes/class-pldr-ingest.php`
- `includes/class-pldr-privacy.php`
- `includes/class-pldr-reader.php`
- `includes/class-pldr-rest.php`
- `includes/class-pldr-rights.php`
- `includes/class-pldr-schema.php`

## QA-harness disclosure

A temporary write-enabled R15 patch-applier workflow was used only to transfer the already reviewed sequential corrections to the PR branch. Two early temporary harness runs failed because exact textual patch markers did not match the evolving source; these were tooling-harness failures, not PHP runtime failures. The correction was retried against the exact evolving source, the source-application run passed PHP/JavaScript syntax checks, and the temporary patch workflow/scripts/chunks were then self-removed from the candidate before final exact-head quality-gate verification.

## Production-truth boundary

The PR remains a repository candidate until the normal release gates are separately proven: deterministic package, exact-head automated QA, exact artifact deployment to staging, deployed checksum parity, real database/schema/migration inspection, real role/IDOR/privacy/rights/browser/offline/RTL/accessibility/provider-outage workflows, backup/restore/rollback, Founder acceptance, production deployment and live re-test.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
