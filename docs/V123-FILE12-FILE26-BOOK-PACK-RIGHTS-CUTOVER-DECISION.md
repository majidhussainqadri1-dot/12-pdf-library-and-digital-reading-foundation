# File12 ↔ File26 V12.3 owner contract decision and stop-ship cutover protocol

**Status:** PROPOSAL / OWNER DECISION REQUIRED / NO DEPLOY. **Date:** 2026-11-14.

## 1. Frozen source truth — never mistake for deployed reality

- File12 frozen Draft PR #57: `d7c0d9458402019a3c46c73b3a7563329e9be8f7`; candidate plugin `1.1.0-rc.43`.
- File12 actual `includes/class-pldr-file26-connector.php`: `manifest().entity_types=['pdf']`, `status='proposed'` and a disabled `cutover_approved()` guard requiring a verified versioned rights tombstone bridge. Its `project()`, `list_batch()` and `fetch_object()` do not serve `book_pack`.
- File12 `PLDR_Book_Packs::register()` and private `persist_authorized()` (in `includes/class-pldr-rights.php`) register **metadata-only immutable Book Pack versions** with `status='registered'`. This does **not** approve public search, provide public source ACL, or authorize file download.
- File26 owner source Issue #23 records that File26 Draft PR #22 `Owner_Contracts::requirements()` expects **both `pdf` and `book_pack`**. The mismatch is known source truth, not a verified Live defect.
- File12 `PLDR_R21_Outbox` declares `PDFDocumentAccessChanged.v1` and status-change events with a File26 consumer. An outbox `sent` marker after WordPress hooks does not prove receiver ACK, applied sequence, index deletion, cache purge or visible search deindexing.

No source-only evidence shows installed Site File12/File26/CF04 versions or a physical DB migration. Do not activate the dormant connector or falsely widen `entity_types`.

## 2. Two legitimate, mutually exclusive approval paths

**Option A — full Book Pack publication/search owner:** The owner approves a new rights-reviewed Pack publication lifecycle distinct from metadata registration, with immutable public version IDs, independent rights/territory/expiry and withdrawal, bounded list/fetch, accurate current-view enforcement, tombstone/replay, File26 index and cache invalidation and audited publisher approval. Pack text/OCR, private notes, user progress, personal shelves and signed download grants remain excluded from public projection.

**Option B — explicitly limited PDF-only phase:** File26 owner and Founder approve a **new versioned requirement contract** allowing only `pdf` for File12, with `book_pack` explicitly deferred/disabled and separately tracked; verify source registry, shadow reindex, revoked PDF search and stage rollback. Do not silently weaken the existing File26 two-type requirement or mislabel the implementation as full Book Pack indexing.

Until an authorized decision, **neither Option A nor Option B is selected**. Merely adding `book_pack` to an array without working ACL/list/fetch would be an incorrect source-level fix.

## 3. Minimum versioned data contract for either path

| Domain field | Required invariant |
|---|---|
| `source_owner` | Exact File12 canonical owner; no consumer-generated source state |
| `object_type` | Only an approved actual type (`pdf`; `book_pack` only after Option A) |
| `object_id` | Stable canonical opaque ID; no private DB row, patient IDs, user notes or object path |
| `object_version` | Monotonically advancing owner revision with strict integer validation |
| `rights_policy_version` | Current owner rights revision; never infer access from last published metadata |
| `event_id/event_sequence` | Idempotent durable event identity, ordered per source object; nondecreasing apply |
| `visibility/status` | Source-authorized public-only projection; no intermediate `registered` as `published` |
| `tombstone` | Rights withdrawal, embargo, deletion, appeal restriction, licence expiry must deindex |
| `last_applied_sequence` | Independently queryable File26 consumer receipt/ACK of each event ID/version |
| `permission check` | Fresh File12 `can_view` at query time; denies on owner down, revoked/unknown/malformed |
| `preview/canonical_url` | Approved nonsecret metadata only; no private OCR, derivative, signed token or raw PDF path |

Required security property: **the newest restrictive source right wins** over stale/out-of-order event replay, stale caches, old manifests and delayed jobs. The connector must not become search-ready merely because a local outbox row was marked `sent`.

## 4. Deterministic acceptance scenarios (authorized real Staging only)

1. Start from exact installed File12, File26, CF04 source/package checksums and physical DB/migrations; validate signed owner contract versions.
2. Add a genuine licensed/founder-owned PDF and optionally an independently approved publicly searchable Book Pack (only Option A). Verify original bibliographic metadata, distinct edition/version, rights and source integrity.
3. Full owner crawl: bounded pagination, advancing cursor, exact permitted entity type, no duplicate identity, missing/invalid rights denied. File26 must not silently accept malformed `done` or partial owner completion.
4. Withdraw rights while search index is populated. Immediately confirm File12 server-side denial, signed grant revocation and File26 query-time deny. Verify event ID, last applied sequence, tombstone ACK, index row removal and preview/cache purge.
5. Consumer-down/timeout/retry/dead-letter: local `sent` without confirmed owner ACK is not a success. On recovery, eventual apply must be monotonic and visible.
6. Replay old publish event after newer withdrawal, duplicate tombstone, out-of-order policy revisions and concurrent document updates. None may resurrect public results.
7. Browser/search authorization by guest, normal user, verified doctor, suspended account, owner and privileged session; current File00 identity/guardian restrictions and no IDOR.
8. Verify private notes, progress, Book Pack draft/registered state, OCR text and signed delivery grants are *not* leaked through File26 result/HTML/card/search snippets.
9. Verify lawful deletion/export/legal hold and a consistent DB+Object+Index backup/restore. Restore and reindex must honor current withdrawn rights.
10. Independent Review A and Review B verify T-12, T-13, T-14, T-15, T-30 and V12.3 QA-18/19/20/22/24 with exact final SHA, reviewer evidence, and Founder decision.

## 5. Release switch (do not confuse source presence with activation)

- **Default:** File26 adapter dormant; `manifest.status='proposed'`; `book_pack` unavailable to public Search.
- **Preactivation proofs:** Versioned approved owner type contract; File12/26/CF04 installed SHA; true current ACL and lifecycle; consumer ACK/tombstone; source rights; real Staging; independent review; rollback.
- **Activation:** Only an approved released package after File01B/Founder signoff, deployment parity and backend staging/live migration validation. Never toggle a flag to disguise a missing provider.
- **Cutover rollback:** Revert search feature projection safely; preserve new source/rights events and later rescans; never restore an older public index without reapplying restrictive tombstones.

## 6. Present verdict

**10 broader release blocker categories remain open**, including File26, CF04 and exact deployed/DB state. The V12.3 audit PR #58 adds source evidence, not a File26 adapter activation. The user or relevant File26/Founder owner must approve Option A or Option B based on complete dependency/rights evidence before implementation can be treated as authorized. Until then: **NO_GO**.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
