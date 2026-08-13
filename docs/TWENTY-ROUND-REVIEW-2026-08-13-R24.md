# File 12 — R24 Fresh Twenty-Round Corrective Review — 2026-08-13
Baseline R23 exact green HEAD: 3d9adb18732cfa4c25119f18c0a47f8f4c0512b4
R24 branch: review/file-12-r24-twenty-round-2026-08-13

## Governing discipline
Every numbered round followed the required sequence: finish the entire numbered review first; close the complete defect ledger; only then apply that round's complete corrective batch; retest; only then begin the next numbered round. No defect was fixed while its numbered review was still underway.


## Round 1
Focus: governing plan/canonical ownership/routes/File01 registry.
Result: DEFECT.
Defect: /library/manage/ existed in runtime rewrite and route_url but was omitted from PLDR_Integrations::register_contracts() File01 route registry.
Post-review fix: added /library/manage/ owner 12 authenticated-noindex-no-cache route registration. php -l PASS.

## Round 2
Focus: fresh install/upgrade, core/Future schema, migration locks, physical readiness and forward schema corrections.
Result: CLEAN.
Evidence: core upgrader verifies required tables/columns/indexes before writing DB/contract markers; R21 readiness physically rechecks and repairs drift; Future upgrader uses try/finally lock release and physical verification; schema errors are stored with bounded/opaque diagnostic references. No new proven defect.

## Round 3
Focus: core/Future REST authorization, object/field/IDOR boundaries, protected mutation preauthorization.
Result: CLEAN.
Evidence: public reads re-check edition entitlement and return non-enumerating unavailable states; private items/shelves bind to current user; review mutations resolve target then perform document-scoped authorization; reader derivative object IDs are revalidated against the requested edition before grants. No new proven authorization/IDOR defect.

## Round 4
Focus: signed reader/download delivery, token audience/object/operation binding, revocation, GET/HEAD methods, HTTP byte ranges and authenticated decryption.
Result: CLEAN.
Evidence: token issuance revalidates entitlement and object-to-edition ownership; delivery re-checks current authorization and object state; audience hash is bound to edition+operation; GET/HEAD only; single-range parser rejects malformed/multiple/zero suffix/unsatisfiable ranges; usage increment is atomic; AES-GCM chunk streaming authenticates each chunk. No new proven defect.

## Round 5
Focus: PDF ingest, genuine-file validation, password/polyglot checks, malware/cover gates, rights/source/expiry inputs, checksum and duplicate controls.
Result: DEFECT.
Defect: ingest accepted an explicitly supplied rights_expires_at already in the past; a clean scan from a Founder/manager could therefore create a `published` edition and emit PDFDocumentPublished.v1 even though ordinary access would immediately reject the expired rights, leaving canonical publication state contradictory.
Post-review fix: after the complete Round-5 review, added a fail-closed current-rights precondition that rejects an already-expired rights_expires_at before storage/DB mutation. PHP lint PASS.

## Round 6
Focus: encrypted private storage, AES-GCM chunk format, key ring/rotation, temporary plaintext cleanup, exact ciphertext+plaintext integrity and quarantine.
Result: CLEAN.
Evidence: temporary paths are private and shutdown-tracked; encryption/decryption deletes failed outputs; key rotation verifies old object, decrypts/re-encrypts, verifies new ciphertext+plaintext, CAS-updates metadata and cleans plaintext/encrypted temps; integrity failures quarantine exact object and revoke document grants. Backup/key-restore drill remains a staging/operations gate, not a repository-code claim. No new proven defect.

## Round 7
Focus: rights reports, temporary restriction, decisions, appeals, publication approval, restoration, expiry and grant revocation.
Result: DEFECT.
Defect: publication/restoration eligibility (complete rights/source basis, non-expired rights and clean object) was enforced by REST/wp-admin pre-dispatch guards, but the canonical writer `PLDR_Rights::approve_document()` itself did not call that policy; internal/plugin calls could bypass the outer entrypoint guard. The restore writer also duplicated only a subset of the eligibility rules.
Post-review fix: after the complete Round-7 review, made the canonical publication writer and every `published` rights-state transition call `PLDR_Rights_Policy::check()` internally before mutation. Existing outer guards remain defense-in-depth. PHP lint PASS.

## Round 8
Focus: reading progress, bookmarks/notes/highlights, private ownership, edition supersession, optimistic concurrency and deletion/pagination.
Result: DEFECT.
Defect: reading-progress optimistic concurrency used `updated_at` as the revision token, but the column/value has one-second resolution. Two saves within the same second could persist the same revision value, allowing a later request carrying the stale prior revision to pass the equality/CAS check and overwrite the first update.
Post-review fix: after the complete Round-8 review, made reading-progress `updated_at` a strictly monotonic revision token: if the current server timestamp is not greater than the stored revision, the next stored revision advances by one second. This preserves the existing schema while making the FOR UPDATE + expected revision CAS effective for same-second writes. PHP lint PASS.

## Round 9
Focus: catalog/search/OCR discovery, access-filtered pagination, signed cursors, facets and cache/index leakage.
Result: DEFECT.
Defect: when a legacy page request had a logical eligible-item offset but the bounded raw scan truncated before enough authorized items were found, the returned cursor started after the scanned raw row yet reset the logical skip to zero. On sparse entitlement-filtered catalogs this could return items earlier than the requested logical page after continuation.
Post-review fix: after the complete Round-9 review, extended the signed catalog cursor with a bounded `skip` remainder. Cursor continuation now carries forward only the eligible-item skip still outstanding when a bounded scan truncates, preserving access-filtered page semantics across continuation without unbounded scanning. PHP lint PASS.


## Round 10
Focus: citations, precise scholarly anchors, portable W3C-style annotations, edition comparison and derived reading data.
Result: CLEAN.
Evidence: citation exports bind stable document/edition/page identity and revalidate edition access; precise text-quote anchors require source-page verification or fail-closed approved validation; private annotation export/import is current-user + edition bound with bounded pagination, canonical source checks and serialized identity dedupe; edition comparison revalidates both File 12 editions, is OCR-derived/bounded and never performs automatic merge. No new proven defect.

### First-ten checkpoint
Defect rounds after the first 10 complete reviews: 1, 5, 7, 8, 9.
Clean rounds: 2, 3, 4, 6, 10.

## Round 11
Focus: privacy export/erasure, legal hold, retention, private Future state, shelf membership, reading telemetry and durable-review anonymization.
Result: CLEAN.
Evidence: private reading/Future tables are exported with bounded paging; erasure is fail-closed when legal-hold verification fails, removes current-user reading state/items/grants/preferences/events/handoffs/room contexts/idempotency and shelf membership in bounded batches, anonymizes durable OCR/a11y/closed-rights actors, then re-counts before declaring done; private reading events have bounded scheduled retention. Companion-owned provider data remains under its native owner rather than being silently deleted by File 12. No new proven repository defect.

## Round 12
Focus: Future-24 provider boundaries, authority enrichment, Knowledge Context, reading rooms, AI corpus allowlist, derived translation/transliteration, IIIF and preservation/fingerprint boundaries.
Result: CLEAN.
Evidence: Future operations revalidate current edition access; authority enrichment is provenance-bound and cannot overwrite canonical metadata; selected text/page is source-verified before companion/translation providers; Patient Case provider use is deny-by-default; reading rooms store only File 12 context and use File 17 provider/compensation boundaries; AI corpus requires entitlement + document allowlist + explicit File 16 consumer authorization; IIIF issues only short-lived rights-aware grants; derived text is labeled non-authorial; fingerprint candidates never auto-merge. No new proven defect.

## Round 13
Focus: core/Future REST mutation idempotency, request fingerprinting, replay, abuse-rate limits, preauthorization, concurrency and transient failure retry semantics.
Result: CLEAN.
Evidence: mutating routes require bounded Idempotency-Key plus full request fingerprint (including uploaded-file digest where readable); reservations are actor/route/key scoped, conflicting payloads are rejected, in-flight requests return pending, authorization-denial and 5xx paths abort retry records, deterministic non-5xx results are retained, mutation rate state is serialized, and private/Future writers use expected-version or transactional/CAS controls where mutable truth requires them. No new proven defect.

## Round 14
Focus: reliable outbox/event contracts, dead-letter/retry semantics, companion ownership boundaries, File 01/05/06/16/17/19/20/21/24/25/26 integration and direct-write duplication.
Result: DEFECT.
Defect: the governed `PLDR_R21_Outbox::dispatch()` was the hooked canonical dispatcher, but a second public legacy implementation `PLDR_Integrations::dispatch_outbox()` remained in the repository. Although unhooked, direct invocation of that legacy method could dispatch arbitrary/unknown outbox event names without the R21 contract-registry check, creating a divergent duplicate event-delivery path contrary to single-owner/event-governance rules.
Post-review fix: after the complete Round-14 review, replaced the legacy dispatcher body with a backward-compatible delegation to `PLDR_R21_Outbox::dispatch()`. The R21 dispatcher is now the single event-delivery implementation and unknown event contracts remain fail-closed/dead-lettered. PHP lint PASS.

## Round 15
Focus: encrypted offline vault, offline grants, local expiry, reconnect reauthorization, logout/explicit purge and browser IndexedDB state.
Result: DEFECT.
Defect: the vault correctly required a server-authorized grant for initial capture and enforced local expiry/logout purge, but after a browser went offline and then reconnected the already-captured local copy could still be opened without the server reauthorization explicitly required by F12-FUT-012. The browser had no reconnect-pending state or rights revalidation endpoint.
Post-review fix: after the complete Round-15 review, added an authenticated `/future/offline-authorization/{edition}` rights check that revalidates current offline entitlement/rights without issuing a new object grant. The vault now records a reconnect-revalidation requirement when the browser goes offline, reauthorizes on reconnect/before open when required, retains but refuses to open the encrypted copy if reauthorization cannot be completed, and clears the pending marker only after successful server authorization. PHP lint and JavaScript syntax PASS.

## Round 16
Focus: UI/UX, File 20/25 visual-token boundary, Sabri Green, 320–1920 responsive behavior, RTL, keyboard/focus, zoom, reduced motion and explicit states.
Result: DEFECT.
Defect: the File 12 reader still hard-coded legacy/different greens (`#166534` / `#15803d`) as its primary visual system even though the amended File 12/central visual law makes Sabri Green `#087A4E` the primary and requires File 20/25 token inheritance rather than a parallel module brand system.
Post-review fix: after the complete Round-16 review, changed File 12's local primary token to inherit the shared Sabri token when present with `#087A4E` only as the fallback, added a shared hover-token fallback, and changed Future-reader green uses to consume the local/shared token rather than hard-coded competing greens. Existing semantic danger/information/focus colors remain distinct. CSS/static token check PASS.

## Round 17
Focus: operations, cron/queue continuation, fingerprint background jobs, repair/readiness guards, retries/dead-letter, cleanup and observability.
Result: DEFECT.
Defect: `after_derivatives()` checked `wp_next_scheduled('pldr_future_fingerprint_edition', array($edition_id))`, but actual fingerprint events are scheduled with two arguments `(edition_id, attempt)`. WordPress cron hashes exact argument arrays, so the one-argument probe could not see the existing job and repeated derivative completion could enqueue duplicate fingerprint work for the same edition. Retry scheduling also did not guard an already-scheduled same retry attempt.
Post-review fix: after the complete Round-17 review, added an edition-scoped scheduler check across attempts 0–3, used it before the initial job, and guarded each retry with the exact `(edition_id,next_attempt)` argument tuple. PHP lint PASS.

## Round 18
Focus: broad adversarial security/privacy pass — authentication/CSRF assumptions, public POST/provider abuse, upload/injection/SSRF boundaries, error/PII leakage, cache/index exposure and protected-object delivery.
Result: DEFECT.
Defect: `/wp-json/pldr/v1/future/derive-text` was a POST route using the Future mutation/idempotency/rate-state machinery and could invoke external translation/transliteration providers, but its REST permission callback was `__return_true`. That contradicted File 12's mutating-API constitution requiring an authenticated actor and exposed provider capacity/idempotency writes to anonymous requests.
Post-review fix: after the complete Round-18 review, made the derived-text POST route use the canonical `logged_in` permission callback. Current edition/page/source-selection authorization and Patient Case privacy/provider gates remain inside the callback as defense in depth. PHP lint PASS.

## Round 19
Focus: performance/scale, bounded private workspaces, large reading histories, keyset traversal, N+1 ceilings and hidden truncation.
Result: DEFECT.
Defect: `/account/reading/` fetched a hard-coded latest 100 reading-state rows and exposed no continuation. If those 100 included inaccessible/restricted editions, older still-authorized progress could become permanently unreachable; the fixed ceiling was therefore both a scale/correctness and access-filtered-pagination defect.
Post-review fix: after the complete Round-19 review, replaced the silent 100-row ceiling with bounded 50-row keyset pages ordered by `(updated_at,edition_id)`, an account-bound HMAC-signed continuation cursor, per-page authorization rechecks, and an explicit older-progress continuation link. A page with only filtered rows reports the state honestly while still allowing continuation. PHP lint PASS.

## Round 20
Focus: final holistic adversarial reconciliation of the fully corrected Round-19 state against central/File 12/Future-24 requirements, release identity, permanent review/regression evidence and production-truth boundary.
Result: DEFECT.
Defect: the materially changed R24 source still identified itself as the already-published R23 repository candidate `1.1.0-rc.2`, while repository manifest/Future-24 release metadata still carried older candidate identities. That would permit different source/package bytes or conflicting reviewer metadata under the same/older release-candidate identity. R24 also did not yet have its own permanent review record/regression gate at the end of the numbered sequence.
Post-review fix: after the complete Round-20 review, advanced only the software/package candidate identity to `1.1.0-rc.3` while retaining DB/contract schema `1.1.0`; aligned manifest/Future-24/repository status metadata; created the permanent R24 twenty-round record and regression contract; future-proofed the retained R23 regression so it tests R23 behavior rather than freezing the mutable current candidate version; and prepared the canonical quality gate/package metadata to execute/build the R24 candidate explicitly. Full exact-head CI/package evidence is a separate post-review QA closure and must be taken from the final unchanged GitHub branch HEAD.

## Final R24 accounting
Reviews completed: 20/20.
First-ten defect rounds: 1, 5, 7, 8, 9.
Final defect rounds: 1, 5, 7, 8, 9, 14, 15, 16, 17, 18, 19, 20.
Final clean rounds: 2, 3, 4, 6, 10, 11, 12, 13.

## Production-truth boundary
Repository source/CI/package evidence proves only repository/package gates. Hostinger staging deployment, deployed artifact checksum, deployed DB/schema/migration state, real-role/browser/accessibility/RTL/offline/provider/backup/restore/rollback journeys, Founder acceptance and live re-test remain separate gates.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
