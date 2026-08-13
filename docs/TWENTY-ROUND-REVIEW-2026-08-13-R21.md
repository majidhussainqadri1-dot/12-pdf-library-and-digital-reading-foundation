# File 12 — R21 Fresh Twenty-Round Corrective Review — 2026-08-13

## Governing review discipline

R21 started from exact green R20 repository head `ad153bede56accdbd591b57f959e643e96a02eb8`. Every numbered round follows one mandatory sequence: **finish the complete review first; close that round's complete defect ledger; only then apply all proven corrections as one post-review batch; retest; and only then start the next numbered round.** No defect was fixed while its numbered review was still underway.

Repository review evidence is not staging/live evidence. Exact deployed version, deployed schema, migration state and live behavior remain separately unverified.

## Round 1
**Focus:** activation, core/Future schema, runtime readiness, fail-closed startup.
**Result:** Defects found. Version markers could hide physical schema drift; runtime/Future hooks could proceed after failed or incomplete upgrade/correction state.
**Post-review batch:** added physical table/column/index readiness verification, one canonical repair attempt, schema-correction gate, and fail-closed core/Future runtime.
**Correction commit:** `432d617e0746f42d23ce87b470eb6a1aaa068ac0`.

## Round 2
**Focus:** REST permissions, object/state authorization, IDOR and existence leakage.
**Result:** Clean. No new proven repository defect.

## Round 3
**Focus:** idempotency, replay, mutation rate/concurrency and crash recovery.
**Result:** Defect found. A hard crash after reservation could leave a pending idempotency row blocking same-key retry for an excessive interval.
**Post-review batch:** bounded stale-pending reservation recovery.
**Correction commit:** `f1963dfb30094dc17c60cfe262deb8aa66132901`.

## Round 4
**Focus:** private storage, ingest crypto, active-key selection and key-writing workflows.
**Result:** Defect found. Multiple configured keys without an explicit active key could make encryption choose an arbitrary first key.
**Post-review batch:** fail-closed ingest/key-rotation preflight when active key selection is ambiguous.
**Correction commit:** `5452f80da25966b96498f1b742135febad3ca740`.

## Round 5
**Focus:** access policy, grants, derivative binding, range delivery and live authorization recheck.
**Result:** Clean. No new proven repository defect.

## Round 6
**Focus:** rights reports, decisions, appeals, expiry, publication/restoration and revocation.
**Result:** Clean. No new proven repository defect.

## Round 7
**Focus:** privacy export/erase, legal hold, retention and audit minimization.
**Result:** Defects found. Privacy export included idempotency response bodies that may carry signed/sensitive response material; privacy failure audits used direct subject identity rather than a pseudonymous subject reference.
**Post-review batch:** removed replay response bodies from export and pseudonymized privacy failure audit subject references.
**Correction commit:** `5917d3a0360915e93134cc1d8686293d70af27cb`.

## Round 8
**Focus:** public routes, catalog/search, cache/SEO status and canonical document URLs.
**Result:** Defects found. Virtual document states could be emitted under HTTP 200, slug variants lacked canonical redirect, and filtered catalog routes lacked explicit noindex treatment.
**Post-review batch:** 404/503 route semantics, canonical slug redirect and filtered-catalog noindex policy.
**Correction commit:** `05aa5b36dd657b269b0d368229165a96a422006b`.

## Round 9
**Focus:** reading progress, bookmarks, notes, ownership, page bounds and cursor/delete concurrency.
**Result:** Clean. No new proven repository defect.

## Round 10
**Focus:** OCR correction/review/search overlay, citations, anchors and portable annotations.
**Result:** Clean. No new proven repository defect.

**First-ten defect rounds: **1, 3, 4, 7, 8****

## Round 11
**Focus:** external providers, authority/context adapters, provenance, SSRF/same-origin policy and degraded provider states.
**Result:** Clean. No new proven repository defect.

## Round 12
**Focus:** encrypted offline vault, local authorization expiry and logout purge.
**Result:** Defect found. The new fail-closed runtime gate could prevent logout-purge hooks from registering while a previously stored browser vault still existed.
**Post-review batch:** registered logout purge marker/purge asset independently of File 12 domain schema readiness.
**Correction commit:** `e4d77cfcfe0d0dc0d2c6fccc74e4f58b7d594dc8`.

## Round 13
**Focus:** UI containment, RTL/responsive/accessibility, touch targets and page navigation.
**Result:** Defects found. Reader CSS contained unscoped global form/button selectors, and the private reading workspace lacked a native Back/Home path and explicit empty state.
**Post-review batch:** confined File 12 styles, retained 44px targets/focus/reduced-motion rules, and added native route navigation/empty-state support.
**Correction commit:** `7f7e178df4740b369d878f8c0cc04946326ea4f2`.

## Round 14
**Focus:** legacy migration, schema continuity, deactivation/uninstall and rollback safety.
**Result:** Defects found. Legacy migration lacked an execution lease and legacy progress reconciliation could overwrite newer native reading progress.
**Post-review batch:** serialized legacy migration and snapshot/reconciled native progress with timestamp-aware preservation.
**Correction commit:** `0c22cf73ff00ad2fc6bee573b7e8eca506652bd2`.

## Round 15
**Focus:** integrations, event/outbox contracts, consumers, retries/dead-letter and privacy classification.
**Result:** Defects found. Event contracts lacked explicit privacy/consumer metadata, unknown outbox event names were dispatchable, and arbitrary consumer exception text could be persisted in outbox failure state.
**Post-review batch:** explicit event schema registry, known-event fail-closed dispatch, at-least-once/idempotent-consumer declaration and bounded non-secret failure codes.
**Correction commit:** `f46409229ce9d28a2c7fc21c41c4dcd8469969e4`.

## Round 16
**Focus:** health, exact integrity, safe repair and operational observability.
**Result:** Defects found. One scheduled event could run both exact R20 integrity verification and older legacy verification; admin safe-repair paths for schema/outbox/legacy migration could bypass R21 canonical guards.
**Post-review batch:** retained the exact integrity sampler as the single scheduled integrity owner and guarded schema/outbox/legacy operational repairs.
**Correction commit:** `a4d4d06af983546dae0658906f6404b56aeba576`.

## Round 17
**Focus:** performance, bounded maintenance, cron deduplication and asset loading.
**Result:** Defects found. Stale-idempotency maintenance could run on read-only REST traffic, fingerprint duplicate checking used an argument tuple different from the scheduled event, and reader CSS was enqueued outside File 12 surfaces.
**Post-review batch:** mutation-only/throttled stale cleanup, canonical fingerprint scheduling tuple, and route/shortcode/admin-scoped reader assets.
**Correction commit:** `84f78286b7c2f0c43622e8898332b29a2cf8e445`.

## Round 18
**Focus:** XSS/CSRF, signed delivery, object binding, secrets, mutation abuse and browser security headers.
**Result:** Clean. Existing output escaping, nonce/capability gates, object/derivative binding, token audience checks, delivery `nosniff`/`no-referrer`/sandbox policy and bounded mutation protections did not reveal a new proven repository defect in this round.

## Round 19
**Focus:** repository evidence, review documentation, STATUS/PR truth, CI retention and package-quality-gate coverage.
**Result:** Defects found. R21 product changes had no permanent R21 regression contract/review record in the repository, the workflow had no named R21 retained gate, and STATUS/PR evidence was stale relative to the active R21 branch.
**Post-review batch:** added this R21 review record, a permanent R21 regression contract, a named CI R21 step, and an R21 checkpoint STATUS/PR evidence update before Round 20.

**Through Round 19 defect rounds: **1, 3, 4, 7, 8, 12, 13, 14, 15, 16, 17, 19****
**Through Round 19 clean rounds: **2, 5, 6, 9, 10, 11, 18****

## Round 20
**Status at the Round-19 evidence commit:** Reserved for the final holistic adversarial pass. It is not claimed complete in this checkpoint record. The next review must assess the corrected Round-19 repository state end-to-end before this section and the final summary are closed.

## Production-truth boundary
Repository source/CI/package evidence proves only those repository/package gates. Hostinger staging deployment, deployed artifact checksum, deployed DB/schema/migration state, real-role/browser/accessibility/RTL/offline/provider/backup/restore/rollback journeys, Founder acceptance and live re-test remain separate gates.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
