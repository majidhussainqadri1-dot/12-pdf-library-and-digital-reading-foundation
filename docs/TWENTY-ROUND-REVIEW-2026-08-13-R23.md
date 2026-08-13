# File 12 — R23 Fresh Twenty-Round Corrective Review — 2026-08-13

## Governing discipline
R23 started from exact green R22 repository candidate `f65f86144bbdb6a851e33e9087cb17774aaf9f98`.

Every numbered round followed the Founder-mandated sequence: **finish the entire numbered review → close that round's complete defect ledger → only then apply all proven corrections as one post-review batch → retest → only then begin the next numbered round.** No defect was fixed while its numbered review was still underway.

Repository/package evidence is not staging/live evidence. Exact deployed source, deployed schema, migration state and live behavior remain separately unverified.

## Round 1
**Focus:** governing-plan reconciliation, canonical ownership, package/version markers, required File 12 routes and management surface.
**Result:** Defect found. The governing File 12 plan required `/library/manage/`, but the repository had no canonical rewrite/route URL or governed handoff for that route.
**Post-review batch:** added the canonical manage route, authentication/noindex/no-cache boundary, and capability-scoped handoff to File 12 management/rights/repair/publishing surfaces.

## Round 2
**Focus:** fresh install/upgrade, core/Future schema, legacy migration, readiness locks and diagnostic privacy.
**Result:** Defect found. Schema/migration failure state could persist raw database error text, contrary to the safe-diagnostics rule.
**Post-review batch:** added bounded opaque database-error references and transformed DB/SQL/path-like audit context to non-reversible references before persistence.

## Round 3
**Focus:** signed reader/download delivery, token binding, revocation, byte ranges, HTTP methods and IDOR/existence boundaries.
**Result:** Defects found. `Range: bytes=-0` was normalized into a one-byte suffix instead of 416, and the signed delivery route did not explicitly reject non-GET/HEAD methods.
**Post-review batch:** made zero-length suffix ranges unsatisfiable and restricted signed object delivery to GET/HEAD with 405 + `Allow` for other methods.

## Round 4
**Focus:** PDF ingest, genuine-file validation, malware/polyglot controls, rights/source evidence, Patient Case privacy, cover/OCR derivatives.
**Result:** Defect found. A supplied cover could become an available derivative when the malware-scanner result was not `clean`, and its stored `scan_status` used a derivative label instead of the verified scanner state.
**Post-review batch:** cover availability now fails closed until scanner result is `clean`; stored cover object scan state is explicitly `clean` only after that gate.

## Round 5
**Focus:** encryption, private storage, authenticated chunks, key ring/rotation, temporary plaintext/ciphertext cleanup and object integrity.
**Result:** Clean. No new proven repository defect.

## Round 6
**Focus:** rights reports, takedown/dispute, appeals, expiry, restriction/removal/restoration and delivery revocation.
**Result:** Defect found. A rights-review restore decision could mark a document published even when the current edition's rights period had already expired; access enforcement still denied delivery, but canonical rights state could become contradictory.
**Post-review batch:** restoration now refuses expired current-edition rights until lawful rights evidence is renewed.

## Round 7
**Focus:** reading progress, bookmarks, notes/highlights, private ownership, optimistic concurrency, pagination and deletion.
**Result:** Clean. No new proven repository defect.

## Round 8
**Focus:** catalog/search, access-filtered discovery, facets, cursor pagination, OCR search and noindex/cache boundaries.
**Result:** Clean. No new proven repository defect.

## Round 9
**Focus:** citations, scholarly anchors, portable annotations, source-bound imports, edition comparison and annotation deduplication.
**Result:** Clean. No new proven repository defect.

## Round 10
**Focus:** first-ten holistic reconciliation, Future features F12-FUT-001–012, plan traceability and negative-path recheck.
**Result:** Clean. No new proven repository defect.

**First-ten defect rounds: 1, 2, 3, 4, 6**

**First-ten clean rounds: 5, 7, 8, 9, 10**

## Round 11
**Focus:** Future provider boundaries, authority enrichment, Knowledge Context, Reading Rooms, Patient Case privacy and AI deny-by-default rules.
**Result:** Clean. No new proven repository defect.

## Round 12
**Focus:** Future schema/data lifecycle, private preferences/shelves/insights/handoff, retention, export/erase and cleanup continuation.
**Result:** Clean. No new proven repository defect.

## Round 13
**Focus:** core/Future REST mutations, preauthorization, idempotency, replay, denial cleanup, rate protection and retry semantics.
**Result:** Defect found. Callback-produced transient 5xx results could be finalized into the 24-hour idempotency record, poisoning safe same-key retry after a temporary DB/provider outage.
**Post-review batch:** core and Future mutation wrappers now abort the reservation for 5xx outcomes so transient failures remain retry-safe; successful/deterministic results retain idempotency semantics.

## Round 14
**Focus:** reliable event contracts, outbox dispatch/dead-letter, File 00/01/05/06/16/17/19/20/24/25 integration boundaries and event registry completeness.
**Result:** Clean. Every literal emitted File 12 domain event was represented in the governed outbox registry; no new proven defect.

## Round 15
**Focus:** encrypted offline vault, grant entitlement/expiry, logout purge, browser storage and duplicate hook behavior.
**Result:** Defects found. Logout marked the purge cookie, but the purge script was only guaranteed on normal front-end enqueue; a standard logout redirect to the WordPress login surface could leave IndexedDB until a later front-end visit. Purge hooks were also registered twice once Future schema became ready.
**Post-review batch:** registered the purge asset on `login_enqueue_scripts` as well as the front end and kept a single canonical logout/front-end hook registration.

## Round 16
**Focus:** responsive UI, 320–1920 behavior, RTL, keyboard/focus, control target size, reduced motion, screen-reader/error/empty/offline states.
**Result:** Clean. No new proven repository defect.

## Round 17
**Focus:** operations, cron/queue continuation, repair/readiness guards, outbox leases/retries/dead-letter, cleanup and observability.
**Result:** Clean. No new proven repository defect.

## Round 18
**Focus:** broad adversarial security/privacy pass: authorization, CSRF/nonce assumptions, SQL/path/exception leakage, secrets, SSRF/provider boundaries, cache/index leakage and restricted originals.
**Result:** Clean. No new proven repository defect.

## Round 19
**Focus:** performance/scale, access-filtered search cost, large libraries, bounded work, pagination, background processing and degraded dependencies.
**Result:** Defect found. First-page catalog search could scan 2,000 rows by default and up to 20,000 per request while performing per-document access/edition/policy checks, creating an excessive N+1 query budget and avoidable public request amplification.
**Post-review batch:** reduced the default scan budget to a cursor-continuable bounded window and capped a request at 1,000 raw candidate rows; scan truncation already returns continuation state so correctness is preserved without unbounded work.

## Round 20
**Focus:** final holistic adversarial pass over the fully corrected Round-19 state, requirement-to-code traceability, release identity, permanent regression evidence and production-truth boundary.
**Result:** Defects found. Material R23 code changes still carried the R22 `1.1.0-rc.1` software identifier, which would allow different package bytes under the same release-candidate identity. R23 also lacked its own permanent review/regression artifact at the end of the numbered sequence.
**Post-review batch:** bumped the candidate software/package identity to `1.1.0-rc.2` without changing DB/contract schema `1.1.0`; added this permanent R23 review record and regression test; extended the canonical quality gate to execute R23 explicitly.

## Final R23 accounting
**Reviews completed:** 20/20

**First-ten defect rounds:** **1, 2, 3, 4, 6**

**Final defect rounds:** **1, 2, 3, 4, 6, 13, 15, 19, 20**

**Final clean rounds:** **5, 7, 8, 9, 10, 11, 12, 14, 16, 17, 18**

The numbered R23 sequence is complete. Exact-head CI/package evidence must be taken from the final R23 branch head after this closure commit.

## Production-truth boundary
Repository source/CI/package evidence proves only repository/package gates. Hostinger staging deployment, deployed artifact checksum, deployed DB/schema/migration state, real-role/browser/accessibility/RTL/offline/provider/backup/restore/rollback journeys, Founder acceptance and live re-test remain separate gates.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**