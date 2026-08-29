# File 12 — Second Fresh Ten-Round Corrective Review — 2026-08-11

This record is a **new review cycle** performed after the earlier ten-round review at `9a3a53313a3d400ca01541cd775de2baa123f3f9`. Each round was completed in sequence and every concrete source-level finding was corrected before the next round began.

The review is repository/static/automated-QA work only. It does **not** claim Hostinger staging acceptance, deployed parity, migration success on the deployed database, or live operational correctness.

## Round 1 — Object authorization, OCR privacy, external room context
**Findings:** OCR quality reports exposed reviewer/submission identity metadata to any entitled reader; OCR review authority was checked without the target document context. Reading-room text anchors were bounded but not proven to belong to the selected page before being sent to a companion provider.

**Corrections:** split public-reader and reviewer correction projections; revalidated review authority against the correction edition/document; bounded review notes. Reading-room exact/selection anchors are now source/page-bound with an explicit exceptional adapter gate.

## Round 2 — Personal-state concurrency
**Findings:** Smart Shelf rename could lose a concurrent update; first-write preference races were reported as generic storage failures rather than deterministic conflicts.

**Corrections:** shelf rename now uses optimistic `version` compare-and-set; preference first-write races are distinguished from actual DB failure and return controlled conflict state.

## Round 3 — Durable idempotency race
**Findings:** both core and Future-24 REST idempotency used lookup → mutate → store. Two simultaneous requests using the same new key could therefore both execute the mutation; persistence failure of the replay record was not a pre-mutation guard.

**Corrections:** added an atomic reservation state (`status_code=0`) to the existing idempotency ledger, replay/pending/error states, deterministic completion, and fail-before-mutation when reservation cannot be acquired. Core and Future-24 mutation wrappers now use the reservation protocol.

## Round 4 — Accessibility verification scope/freshness
**Findings:** accessibility refresh/verification used global capability checks rather than the target document context, and human verification could be based on a cached assessment.

**Corrections:** edition/document is resolved first; refresh and verification are object-scoped; verification performs a fresh assessment immediately before the human badge decision; note input is bounded and returned verification time is explicit.

## Round 5 — Preservation fail-closed semantics
**Findings:** an external preservation adapter could downgrade an already quarantined object to `healthy`; a requested checksum verification whose storage path could not be opened remained merely `unknown`; quarantine persistence was not checked before claiming quarantine/revocation.

**Corrections:** manual preservation is object-scoped; verify-unavailable is a visible `needs-review` state; persisted quarantine must succeed before quarantine is claimed; external providers cannot downgrade quarantine; failed/unavailable verification no longer falsely advances `last_verified_at`.

## Round 6 — Resource-bounded OCR/search/corpus operations
**Findings:** several advanced endpoints loaded an entire OCR corpus in one request; heatmap accepted arbitrarily long query strings; the AI corpus manifest could emit up to 10,000 chunks in one response; page-specific reflow filtering happened only after building the full reflow response.

**Corrections:** OCR retrieval now supports page/limit/offset; reflow can fetch one requested page directly; outline/comparison have explicit work bounds; heatmap validates query size and scans in bounded batches; AI corpus manifests are paginated with bounded `limit`, `offset`, `has_more`, and `next_offset`.

## Round 7 — Portable annotation integrity
**Findings:** annotation import did not bind a supplied target source to the requested edition and could pass oversized selectors/notes into the reading-item layer.

**Corrections:** imported source, where supplied, must match the canonical edition reader URL; selectors, bodies, export size and import count are bounded; import returns explicit edition-binding evidence.

## Round 8 — IIIF rights semantics
**Findings:** the old rights mapping treated every license string containing `cc-by` as plain CC BY 4.0, potentially misrepresenting CC BY-SA, BY-NC, BY-ND and related licenses. The 500-canvas implementation also silently truncated large manifests without disclosure.

**Corrections:** exact mappings are provided for CC0/Public Domain Mark and common CC 4.0 variants; unknown licenses no longer receive an invented rights URI; manifest canvas limits/truncation are explicit and configurable within a bounded range.

## Round 9 — Reading-insight entitlement and time-window correctness
**Findings:** `most_used` could expose titles from editions whose current access had been revoked; `completed_documents` was all-time even when the report claimed a 30/90/etc-day window; aggregate metrics included inaccessible editions.

**Corrections:** grouped insight rows are revalidated through current edition access; inaccessible editions are excluded; completion is bounded to the selected time window and revalidated; device/layout context is bounded and normalized.

## Round 10 — Cross-layer integration after the preceding fixes
**Findings:** after Round 6 introduced page-specific reflow and paginated corpus manifests, the REST layer still called full reflow before page filtering and did not expose corpus offset/limit, making the new bounds incomplete at the API boundary.

**Corrections:** REST now passes `page` directly into reflow and exposes sanitized `offset`/`limit` for corpus pagination. Object-specific Future routes use a login/public coarse gate while native methods perform target-object authorization, avoiding global-capability prechecks that could incorrectly reject an object-scoped grant.

## Round result
Defects were found in **all rounds 1–10** of this second fresh cycle and were corrected in sequence before proceeding.

## Production-truth boundary
Repository correction and automated tests are not staging/live evidence. Required next gates remain: exact packaged artifact → Hostinger staging install/upgrade → deployed DB/schema inspection → role/IDOR/privacy/rights/browser/offline/RTL/accessibility/weak-network tests → backup/restore/rollback → Founder acceptance → deployment → live re-test → repository/deployed parity confirmation.
