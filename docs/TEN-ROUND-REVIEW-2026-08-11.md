# File 12 — Fresh Ten-Round Corrective Review — 11 August 2026

Scope: exact Future-24 candidate lineage `1.1.0-rc.1`, reviewed after the earlier two Future-24 review/fix rounds. This is repository-source review evidence only; it is not Hostinger staging or live acceptance.

## Pass 1 — authorization and private-state revalidation
Found and corrected: private handoff and legacy reading-state/item reads could return state after current edition entitlement was lost; the private Reading Workspace could still list revoked-edition history; fingerprint candidate enumeration also needed per-candidate access filtering.

## Pass 2 — OCR correction immutability
Found and corrected: approval of an OCR correction destructively overwrote the base OCR text. Approved corrections now remain a derived overlay; the base OCR/source scan remains immutable, and stale reviews fail safely.

## Pass 3 — retry/idempotency semantics
Found and corrected: Future-24 JavaScript sent idempotency keys, but durable Future REST mutations did not consume them. Durable anchor/OCR/annotation/preferences/shelf/insight/handoff/accessibility/room/fingerprint mutations now use the core idempotency ledger.

## Pass 4 — migration concurrency and schema truth
Found and corrected: stale migration-lock takeover/release was race-prone, and migration success verified only table existence. The migration now uses token-bound atomic lock takeover/release and verifies required tables, columns and indexes before recording schema readiness. An internal schema revision forces this stronger verification without changing the approved DB contract version `1.1.0`.

## Pass 5 — accessibility verification integrity
Found and corrected: the public accessibility GET surface accepted `refresh=1`, allowing an unauthenticated refresh to replace an audit row and erase a prior human verification badge. Refresh now requires review authority; persistence failures are explicit.

## Pass 6 — encrypted offline vault integrity and logout purge
Found and corrected: AES-GCM chunks were authenticated but not cryptographically bound to edition/chunk/index/offset, allowing valid chunks to be swapped without tag failure. AAD now binds that context. Logout purge also retained no retry signal when IndexedDB deletion failed; the purge cookie is now cleared only after successful client deletion.

## Pass 7 — preservation fail-closed behavior and scan rotation
Found and corrected: checksum failure did not quarantine the affected object/revoke grants, and the scheduled five-item preservation scan could repeatedly select the same oldest editions because it ordered by edition update time rather than preservation verification time. Integrity failure now quarantines/revokes, persistence is checked, and scan selection rotates by `last_verified_at`.

## Pass 8 — personal-state validation and database failure paths
Found and corrected: Smart Shelf operations could report success after failed database writes, custom names were not explicitly bounded to schema width, and concurrent default-shelf creation had no deterministic uniqueness guard. Reader preferences accepted arbitrary preference keys and numeric values outside the governed UI ranges. These paths are now bounded and fail-visible.

## Pass 9 — provider input binding and client storage resilience
Found and corrected: translation/transliteration and knowledge-context calls could submit arbitrary text under an accessible edition instead of proving the text belonged to the requested page; provider inputs were insufficiently bounded; reading-room anchors were not tightly bounded; corrupt/blocked `localStorage` could break advanced-reader initialization. Selected text is now page/source-bound (with explicit adapter exception hooks), payloads are bounded, and browser storage is failure-safe.

## Pass 10 — durable persistence truth and external side-effect ordering
Found and corrected: several Future-24 writes (authority provenance cache, accessibility audit, reading events, OCR submissions and scan fingerprints) could silently report success after a database write failure. Reading-room provider creation also happened before the local reconciliation record was durable. All identified writes now fail visibly, and a pending local room-context record is created before invoking the external communication provider.

## Closure gate

A dedicated `tests/test-ten-round-review.php` regression contract now protects the ten corrected defect classes and is included in the multi-PHP-version GitHub quality gate. Final repository status must be taken from the exact post-review HEAD and its fresh GitHub Actions run. Staging/live status remains pending until separately verified on Hostinger.
