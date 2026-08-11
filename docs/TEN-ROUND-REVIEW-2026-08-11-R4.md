# File 12 — Fourth Fresh Ten-Round Corrective Review (R4)

Date: 2026-08-11

## Governing method

This R4 cycle started from the previous R3 exact green repository candidate `250cc3ec8dfb6a52cc4a6c46fc4403588a36cad8`. Each round reviewed the corrected state produced by the immediately preceding round. A discovered defect was corrected before the next numbered round began. Repository evidence is not staging/live evidence.

## Round 1

**Defect found.** WordPress privacy export/erase covered only legacy reading state/items; the eraser could perform unbounded deletes and Future-24 private stores were not comprehensively covered. The correction added bounded 50-row batches, Future preferences/shelves/reading events/session handoffs/reading-room contexts to export/erase coverage, durable-review anonymization, legal-hold behavior, retryable incomplete erasure, and fail-visible database errors. During this same round, a first implementation draft incorrectly assumed an `id` column for composite-key tables; that same-round defect was corrected before Round 2.

## Round 2

**Defect found.** Private reading-item DELETE treated a zero-row delete as success, the delete mutation did not pass through the idempotency guard, and citation requests could name pages beyond the edition. The correction verifies ownership/existence, requires exactly one deleted row, wraps the mutation in idempotency protection, and rejects out-of-range citation pages.

## Round 3

**Defect found.** Reader manifest/UI paths could query every thumbnail derivative and issue a preview access token for every page although the UI only displays a bounded preview set. The correction caps REST and server-rendered thumbnail grant issuance at 300, reports total/returned/truncated metadata in the REST manifest, and uses one shared server-side preview cap in the rendered reader.

## Round 4

**Defect found.** Invalid embargo text could silently normalize to `null` and clear an embargo, while entitlement-backed audiences could be persisted with an empty entitlement key. The correction rejects malformed/relative embargo input, requires explicit ISO-style dates, requires entitlement keys for `education-entitled` and `assigned`, and makes missing entitlement keys fail closed on access checks.

## Round 5

**Defect found.** Knowledge-context selection verification requested the entire OCR corpus and then searched for one target page. The correction performs a page-scoped, one-row OCR retrieval before provider context is allowed.

## Round 6

**Defect found.** Rights-report and appeal evidence accepted effectively unbounded nested payloads; appeal evidence was stored without the same sanitizer, and reviewer/appeal notes had no explicit bound. The correction adds bounded key/depth/value/total JSON limits, common sanitization, a 32 KiB evidence ceiling, and bounded reviewer/appeal notes.

## Round 7

**Defect found.** Document approval did not fail visibly if superseding older published editions failed, accepted a non-CAS edition publication write, and did not verify COMMIT. The correction checks transaction start, supersede failure, target-edition version CAS with exactly one affected row, and COMMIT success before emitting publication side effects.

## Round 8

**Defect found.** `patient-privacy` / `unauthorized-scan` reports were persisted before a separate best-effort restriction whose failure was ignored, so a sensitive report could succeed while the document remained accessible. The correction makes report persistence and required restriction one transaction, rolls the report back if immediate restriction cannot be committed, and emits revoke/audit/events only after commit.

## Round 9

**Defect found.** Encrypted offline-vault database deletion treated `blocked` and `error` as success, and a failed capture could leave partial encrypted chunks. The correction makes purge success truthful, retries blocked/error deletion, persists a purge-pending marker for later logged-out retries, and clears partial edition data after failed capture.

## Round 10

**No new repository-level defect found.** Fresh review covered Future migration lock/schema verification, AI corpus deny-by-default allowlisting and pagination, IIIF current-access and bounded-canvas behavior, authority-provider degraded behavior/provenance, and ownership/provider boundaries. The reviewed state retained the existing fail-closed and bounded controls; no additional patch was justified in this round.

## R4 result

- Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9**
- Clean round: **10**
- Every R4 defect was corrected before the next numbered round.
- R4 regression guard: `tests/test-ten-round-review-r4.php`.

## Production-truth boundary

This record establishes repository-source review only. It does not prove deployed Hostinger staging/live code, deployed checksums, deployed DB/schema/migrations, browser/offline/RTL/accessibility workflows, backup/restore/rollback, Founder acceptance, or production live re-test.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
