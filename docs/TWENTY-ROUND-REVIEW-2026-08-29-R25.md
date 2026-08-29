# File 12 — R25 Fresh Twenty-Round Corrective Review — 29 August 2026

Baseline R24 exact green HEAD: `b28dc3aead75920f15165f7c517f145424ae025f`

R25 branch: `review/file-12-r25-twenty-round-2026-08-29`

## Governing discipline

Every numbered round followed the required sequence: complete the entire numbered review; freeze that round's defect ledger; only then apply the complete correction for every proven defect from that round; syntax/retest; only then begin the next numbered round. Clean rounds changed no product source. Repository/package evidence remains distinct from staging/live evidence.

## Round 1
Focus: canonical ownership, route registry, File 01/20/25 boundaries and duplicate owners.
Result: **CLEAN**.
Evidence: File 12 remains owner of document/edition/object/rights/reader/private reading state; `/library/manage/` is registered to owner 12; shell and visual ownership remain with Files 20/25. No new proven defect.

## Round 2
Focus: core/Future schema readiness, migration locks, physical table/column/index verification, repair and upgrade idempotency.
Result: **CLEAN**.
Evidence: R21 core readiness physically verifies core schema and canonical correction postconditions; Future schema physically verifies required tables/columns/indexes before accepting markers; migration locks are token/CAS released. No new proven defect.

## Round 3
Focus: REST authorization, object/field/IDOR boundaries and protected mutation preauthorization.
Result: **CLEAN**.
Evidence: public reads revalidate entitlement; private state is current-user scoped; document/case/edition targets are resolved and native authorization is rechecked before protected mutations. No new proven defect.

## Round 4
Focus: signed delivery, token audience/object/operation binding, revocation and request-time reauthorization.
Result: **DEFECT**.
Defect: a token issued to the anonymous/public audience stores `user_id=0`, but delivery reauthorization passed that zero into `can_access_edition()`, whose historical zero semantics resolve the current logged-in user. A logged-in browser using a public grant could therefore revalidate the grant under account entitlements rather than under the grant's original public audience.
Post-review correction: introduced an explicit negative sentinel for an intentionally anonymous authorization check and made delivery use it for `user_id=0` grants. User-bound grants still revalidate the exact bound user. PHP lint passed before Round 5.

## Round 5
Focus: ingest validation, current rights/source eligibility, PDF/malware/polyglot/password gates, checksum/dedupe and publication eligibility.
Result: **CLEAN**.
Evidence: current publication policy rejects expired/incomplete rights and canonical writers enforce the policy internally; no new proven defect.

## Round 6
Focus: encrypted private storage, canonical object identity, key rotation, temporary plaintext cleanup and integrity reconciliation.
Result: **DEFECT**.
Defects: `PLDR_Storage::path()` silently reduced a path-like stored object value through `basename()`, so corrupted/poisoned storage metadata could be rebound to another single-component object name rather than rejected. Separately, key rotation committed the new object metadata then silently ignored failure to delete the superseded ciphertext.
Post-review correction: private object names must now already be one canonical path component; slash/backslash/NUL/path-like input is rejected. Storage deletion returns success/failure, and key rotation reports/audits old-ciphertext cleanup with reconciliation required when cleanup fails. PHP lint passed before Round 7.

## Round 7
Focus: rights reports/decisions/appeals, publication/restore, request-time expiry, scheduled expiry and grant revocation.
Result: **DEFECT**.
Defect: the scheduled rights-expiry loop ignored `set_document_status()` errors and only queued prompt continuation for a full 100-row batch. Event/revocation/transition failure could therefore be silently left until the next ordinary recurrence.
Post-review correction: expiry processing returns a bounded summary, counts transition/read failures, audits them and schedules a prompt reconciliation run whenever failures occur or the batch is full. PHP lint passed before Round 8.

## Round 8
Focus: reading progress, bookmarks/highlights/private notes, optimistic concurrency, deletion and edition-aware state.
Result: **CLEAN**.
Evidence: reading progress uses the prior revision token with monotonic same-second protection and private reading items remain user/edition bound. No new proven defect.

## Round 9
Focus: catalog/search/OCR pagination, access filtering, signed cursors and stable ordering.
Result: **DEFECT**.
Defect: the catalog keyset ordered on mutable `documents.updated_at`. Legitimate metadata changes and derived search-index repair could move rows across a signed continuation boundary, causing cross-page duplication/omission even after R24's outstanding-skip fix.
Post-review correction: catalog continuation now orders/keysets by immutable `created_at,id`, and the signed audience/query-bound cursor carries that immutable boundary plus a bounded 30-minute lifetime. PHP lint passed before Round 10.

## Round 10
Focus: citations, scholarly anchors, annotations, comparison and derived reading data.
Result: **CLEAN**.
Evidence: edition/page access is revalidated; annotations remain private and deduplicated; derived comparison/anchors do not mutate original/canonical PDF truth. No new proven defect.

### First-ten checkpoint
First-ten defect rounds: **4, 6, 7, 9**.
First-ten clean rounds: **1, 2, 3, 5, 8, 10**.

## Round 11
Focus: privacy export/erasure, legal hold, private Future state, bounded retention and durable-review anonymization.
Result: **CLEAN**.
Evidence: private states and shelf membership are exported/erased in bounded work, legal-hold failure is fail-closed and audit subject references are minimized. No new proven defect.

## Round 12
Focus: Future-24 accessibility assessment and human verification separation.
Result: **DEFECT**.
Defect: the human `verify()` action called `inspect(..., true)` itself, refreshing provider/automated findings and then immediately verifying the newly generated row. That allowed verification of evidence the reviewer had not necessarily reviewed, contrary to the separate automated-assessment/human-verification boundary.
Post-review correction: verification now requires an existing stored, unverified assessment, validates it, score-checks it and CAS-verifies that exact stored snapshot. Provider refresh remains a separate governed action. PHP lint passed before Round 13.

## Round 13
Focus: mutation idempotency, exact-request fingerprints, replay, rate state and concurrent writers.
Result: **CLEAN**.
Evidence: request body/params/file digest fingerprints, actor/route/key reservation, conflict/in-progress handling, transient-abort semantics and shared mutation rate controls remain intact. No new proven defect.

## Round 14
Focus: reliable outbox, event contracts, lease persistence, retries/dead-letter and repair truthfulness.
Result: **DEFECT**.
Defects: the canonical R21 dispatcher returned `void`, so manual repair unconditionally reported `ok=true`. In retry handling, `$wpdb->update()` returning zero rows after lease loss/no match was recorded as `persisted=true` because the audit used `false !== $retry` instead of requiring one updated lease row.
Post-review correction: the R21 dispatcher now returns a bounded execution summary; dead-letter/retry persistence requires exactly one lease-bound row; claim/store errors are counted; legacy delegation returns the canonical summary; repair exposes the real result instead of fabricating success. PHP lint passed before Round 15.

## Round 15
Focus: encrypted offline vault, local expiry, reconnect reauthorization and logout/explicit purge.
Result: **CLEAN**.
Evidence: R24 reconnect-pending state and authenticated server reauthorization remain in force; stale rights cannot silently reopen the encrypted local copy. No new proven defect.

## Round 16
Focus: UI/UX, File 20/25 token ownership, Sabri Green, responsive/RTL/keyboard/focus/reduced-motion states.
Result: **CLEAN**.
Evidence: File 12 consumes shared visual tokens with Sabri Green fallback and does not create a second shell/visual authority. No new proven repository defect.

## Round 17
Focus: operations, repair, cleanup, derived indexes, cron continuation and truthful operability status.
Result: **DEFECT**.
Defects: search-index repair rewrote canonical `documents.updated_at` even though only derived `search_text` changed; this was semantically false and interacted badly with recency/cursor semantics. Token/idempotency cleanup returned `void`, while repair unconditionally advertised success even when a database delete had failed; continuation scheduling could also duplicate a pending cleanup run.
Post-review correction: derived index rebuild now changes only `search_text`; cleanup returns bounded deletion/error/continuation evidence, guards duplicate continuation scheduling, and repair returns that exact outcome. PHP lint passed before Round 18.

## Round 18
Focus: broad adversarial security/privacy pass: public/provider abuse, CSRF assumptions, SSRF/injection, error/PII leakage and protected-object delivery.
Result: **CLEAN**.
Evidence: provider-derived mutations are authenticated where required, public reader delivery is rights-aware and audience-bound, audit diagnostics are bounded/redacted, and no new proven repository defect was established.

## Round 19
Focus: performance/scale, large histories, bounded workspaces, N+1 ceilings and hidden truncation.
Result: **CLEAN**.
Evidence: catalog/OCR/private Reading Workspace and background jobs remain bounded/continuable after the corrected immutable catalog keyset; no new proven repository defect was established.

## Round 20
Focus: final holistic reconciliation, release identity, permanent R25 evidence and regression gates.
Result: **DEFECT**.
Defect: R25 materially changed candidate source while runtime/package metadata still identified the bytes as the R24 `1.1.0-rc.3` candidate and no permanent R25 record/regression existed. Distinct source bytes must not share the prior release-candidate identity.
Post-review correction: advanced software/package candidate identity only to **`1.1.0-rc.4`** while retaining DB schema and integration contract **`1.1.0`**; aligned repository manifest/status evidence; added this permanent R25 review record and R25 regression; retained R24 behavior evidence without treating the mutable current candidate version as immutable R24 behavior.

## Final R25 accounting
Reviews completed: **20/20**.

First-ten defect rounds: **4, 6, 7, 9**.

Final defect rounds: **4, 6, 7, 9, 12, 14, 17, 20**.

Final clean rounds: **1, 2, 3, 5, 8, 10, 11, 13, 15, 16, 18, 19**.

Source-review closure before Round-20 release/evidence correction: `81215fd851b41a3b01a54bfc363b7b1a1d16a2d3`.

Exact-head CI/package identity is recorded only after the final quality gate runs on the final unchanged R25 HEAD.

## Production-truth boundary
Repository source/CI/package evidence proves only repository/package gates. Hostinger staging deployment, exact deployed package/checksum, DB/schema/migration state, real-role/browser/accessibility/RTL/offline/provider/backup/restore/rollback journeys, Founder acceptance and live re-test remain separate gates.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
