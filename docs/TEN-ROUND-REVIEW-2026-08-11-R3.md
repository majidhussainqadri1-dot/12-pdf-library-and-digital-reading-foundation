# File 12 — Third Fresh Ten-Round Corrective Review (R3)

Date: 2026-08-11

This is a new ten-round review cycle. Its frozen starting baseline was the second-cycle exact green HEAD `556850667feaa9f5a5d363e631a84cc2a15e3f12`. Each round reviewed the corrected state produced by the preceding round. When a defect was identified, the correction was committed before the next round began.

Repository evidence is source-repository truth only. It does not establish Hostinger staging or live deployment parity, schema state, migration state, or live workflow correctness.

## Round 1

**Finding:** access-token delivery usage was checked and incremented in separate operations. Concurrent requests could pass the same pre-check and stream beyond the token's governed `max_uses`. A zero-byte object could also reach invalid range calculations.

**Correction:** non-HEAD delivery now atomically consumes one permitted use with expiry/revocation/quota predicates and requires exactly one affected row before streaming. HEAD requests do not consume quota. Invalid zero-byte objects fail before range handling.

Commit: `84dba165824db2bdf508be6b16de31379a766c83`.

## Round 2

**Finding:** rights-case decisions and appeals used global rights authority (`object_id = 0`) rather than the affected document, defeating document-scoped authorization adapters.

**Correction:** the case is resolved first, its `document_id` becomes the authorization object, and both rights decisions and privileged appeals require rights/manage authority for that document.

Commit: `a02df017d35724fc219483c378d083b9605ab289`.

## Round 3

**Finding:** a rights case could be marked decided/closed before its requested document status transition succeeded. The old status helper was silent, so a failed restrict/remove/restore could leave case state and document state inconsistent.

**Correction:** case compare-and-set and document status compare-and-set now commit in one transaction. Restore validates a clean available current object, document version conflicts fail closed, and token revocation/domain events occur only after commit.

Commit: `0ba6a88bcbeab771990e769bc681747d9a551451`.

## Round 4

**Finding:** access-policy version insertion and the document's `access_mode`/version update were separate unchecked writes. A failed or concurrent document update could leave the latest policy row inconsistent with document state.

**Correction:** policy insertion and document update now share one transaction; the document update is version-CAS protected; write/commit failures are fail-visible; token revocation, audit and domain event happen only after commit.

Commit: `81197b1084578a46d8bb8b2f159b675a48df5818`.

## Round 5

**Finding:** the outbox dispatcher selected pending/retry events and dispatched them without atomically claiming them. Concurrent cron workers could therefore deliver the same event concurrently.

**Correction:** dispatch now leases each row by atomically moving it to `processing` with a ten-minute lease. Only the successful claimant dispatches. Success/retry/dead-letter persistence is constrained to the claimed processing state, and expired processing leases can be recovered after worker failure.

Commit: `9585814db993fbaf9e14f9c9f4cf1ed5d7e447a8`.

## Round 6

**Finding:** rights expiry scanned every historical edition. An expired superseded/historical edition could therefore restrict a document whose current published edition still had valid rights.

**Correction:** expiry now evaluates only the latest edition that is currently `published` for each document before restricting the document.

Commit: `21c8a9e3d996096a3e2bae88c7da8948d951afdc`.

## Round 7

**Finding:** scan-family fingerprinting loaded every OCR page even though only the first twelve pages were used, bypassing the bounded OCR retrieval introduced in the preceding review cycle.

**Correction:** fingerprint computation now requests exactly the bounded twelve-page OCR sample and reports the sampled-page count.

Commit: `4ba41f6707436911186aa84f8041074ea8056251`.

## Round 8

**Finding:** portable annotation import advertised edition binding but accepted annotations with no `target.source`; page numbers alone could therefore be rebound to the requested edition. Malformed non-array bodies were also not explicitly handled.

**Correction:** import now requires a canonical source URL that matches the target edition reader URL, rejects missing/mismatched sources, and safely handles malformed body nodes.

Commit: `f3b992b96a5f2385bd490e9c75b7d11e835d50c9`.

## Round 9

**Finding:** library pagination applied SQL offset before entitlement filtering. When inaccessible rows were skipped, page 1 could scan past its nominal raw offset while page 2 began inside those already-scanned rows, producing duplicate/unstable access-filtered pages.

**Correction:** pagination now scans the ordered raw result set in bounded batches from the beginning, applies entitlement filtering first, then applies the logical page offset to eligible rows. A governed scan cap and explicit `scan_truncated` disclosure prevent unbounded work or false completeness claims.

Commit: `6d822e865265a22ee99aac07301658826977d552`.

## Round 10

**Finding:** rights-appeal insertion and Book Content Pack persistence were not checked before emitting events/audits. A failed write could produce a false successful response or an event with an invalid/zero aggregate id.

**Correction:** appeal insert success and nonzero id are mandatory before event emission; Book Content Pack replace and subsequent persisted-id confirmation are mandatory before audit/event emission. Persistence failures now return explicit machine errors.

Commit: `31e5d6989bf8d10ba2bc8fe3a18711d98913b09e`.

## R3 result

Defects were found in **Rounds 1, 2, 3, 4, 5, 6, 7, 8, 9 and 10**. Every identified defect was corrected before the next review round began.

This record does not claim staging/live acceptance. Exact deployed plugin version/package/checksum, deployed DB version, migration state, runtime configuration, real roles/entitlements, browser/network behavior and live re-test remain separate evidence gates.
