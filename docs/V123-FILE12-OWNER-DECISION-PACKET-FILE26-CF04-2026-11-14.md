# File 12 — V12.3 cross-owner contract and security decision packet
**Date:** 2026-11-14 | **Status:** PROPOSAL ONLY / NO MERGE / NO ACTIVATE / NO DEPLOY

## A. Frozen evidence and protected scope

- Governing: `Sabri Website Master Plan V12.3`, File12 `F12-FR-003, 008, 009, 013, 015, 017, 018; F12-CEN-01; M05, M07, M09, M10, M11; QA-18, 20, 22, 24`, and the attached File12 WP-04/WP-05/WP-07/WP-08/WP-12 plan.
- File12 **source**: PR #58 based on unmerged source PR #57, original File12 plugin candidate `1.1.0-rc.43`, source SHA `d7c0d9458402019a3c46c73b3a7563329e9be8f7`. File12 main remains README-only at `2b369ef2d609247896e1ab32118f1dc498407f51`.
- File26 **source owner**: Draft PR #22 at `c0dc5ac09fdf068f5e77dab0e7f0862a21f33cb1`, `includes/class-file26-owner-contracts.php` requirements() lists File12 `entity_types=['pdf','book_pack']`; `docs/CONNECTOR-CONTRACT.md` v1.0 defines manifested owner, `list_batch/can_view/health`, monotonic versions and replay-safe deletion.
- File12 `includes/class-pldr-file26-connector.php` **proposed and intentionally dormant**: `manifest().entity_types=['pdf']`, status `proposed`, no `book_pack` list/fetch implementation, and unapproved rights version/tombstone bridge.
- File12 `includes/class-pldr-r21-outbox.php` declares `PDFDocumentAccessChanged.v1` and document status events for File26; local hook/outbox `sent` does not prove File26 indexed receipt, last-applied sequence, deletion or cache purge.
- File12 `PLDR_Book_Packs::persist_authorized()` persists a versioned authorized manifest in `book_packs` and emits `PDFBookPackRegistered.v1`; this **is not a File26 search projection** and does not prove an indexed public book pack.
- Current owner Issues: File12 #53 (rights withdrawal receipt) and File26 #23 (pdf/book_pack mismatch).
- **Unverified**: actual installed File12/File26/CF04 package SHAs, physical DB/schema/migrations, current runtime callbacks, approved sample licence, actual search/cache removal, WordPress/MySQL staging. No live incident/root cause established.

## B. The versioned owner decision (choose one after authorized owner review)

**Option A — implement and verify book_pack** before activating either entity type. File12 exposes a true, bounded and rights-reviewed `book_pack` list/fetch/can_view using canonical persisted `book_packs` metadata, immutable pack version, independent licence/provenance plus current document/edition rights where linked. File26 consumes a versioned owner manifest, exact source event sequence, delete/restrict tombstones, and query-time revocation. No string-only `entity_types` change; no implied public access for `registered` packs.

**Option B — approve a *new explicit File26 owner contract*** that initially supports PDF-only File12, with `book_pack` explicitly `unsupported/disabled`. File26's required-type readiness gate and installed version must be changed by the **File26 canonical owner**; File12 cannot silently weaken the File26 requirement. This is a new contract version and decision record with migration/backfill/rollback. Search UI must truthfully hide unavailable Book Pack search, never say it is ready.

Both choices require: exact owner signer/decision ID; new File12 and File26 contract versions; schemas and byte-level package hashes; rollout/downgrade plan; separate independent security review; founder acceptance. Until then the File12 connector remains DORMANT.

## C. Proposed canonical handshake (no binding source change without consent)

### Manifest
A correct manifest must include `slug`, `owner_file=File 12`, `contract_version`, actual `entity_types`, privacy classes, public visibility rules, `deletion_semantics`, `event_contract`, `index_schema`, and **actual callable** `list_batch`, `fetch_object`, `can_view`, `health`. File26 must reject promised-but-unimplemented types and invalid callback shapes.

### Object identity and authority
- PDF: File12 canonical UUID, immutable edition/page identity, version and source event sequence; owner-authorized public title, author, language and canonical URL only. No hidden OCR text, PDF object path, signed delivery token or private reading state in File26.
- Book Pack, if approved: immutable `pack_key + pack_version` scoped to approved catalogue, public rights evidence, status and canonical page; do **not** assume `status=registered` is a public publication decision.
- Every result: `can_view` must query current File12 owner rights and return strict boolean true, even on cache/replay or logged-in admin worker; undefined/expired/embargo/restricted is DENY.
- Changes: exact monotonically increasing `owner_version/source_event_sequence`; release cannot assume that `document.version` covers all rights state changes unless tests prove its monotonic coupling.

### Rights withdrawal / versioned tombstone
Proposed event fields: `event_id`, `owner_file`, `entity_type`, `object_id`, `object_version`, `rights_policy_version`, `sequence`, `status` (published/restricted/removed), `reason_code` (safe), `timestamp_utc`, and opaque idempotency correlation. The owner keeps any sensitive rights evidence private; payload is not a downloadable access grant.

A true File26 acknowledgement/observable state must include **consumer identity**, event ID, same object+sequence/policy version, `applied_at`, success/failed state, and current indexed status + cache purge completion. It must be independently queryable; an origin `do_action()` callback or File12 `outbox.sent` alone is insufficient.

Rules: denied on rights withdrawal **before** background purge finishes; eventually tombstone removes old public search and preview; older event replay must never resurrect a withdrawn edition; rejected/timeout consumer goes to bounded retry/dead-letter, with operational alert; reindex/rebuild preserves latest tombstone; no public byte URL or derived text leak.

## D. File26 joint staging acceptance cases

| ID | Real action | Required evidence |
|---|---|---|
| C26-01 | Publish original licensed PDF as Public, File26 index via approved adapter | Exact File12/26 installed SHA, edition ID, signed rights proof, catalog equality |
| C26-02 | Anonymous/member/doctor/denied user query, File12 click-time can_view | No IDOR, no private OCR/annotation/offline grants |
| C26-03 | Change Public→restricted while File26 consumer is healthy | Immediate owner denial, acknowledged tombstone, index and cache purge |
| C26-04 | File26 consumer down, stale cache, retry and dead-letter | No stale public visibility, alert and delayed reconciliation |
| C26-05 | Replay old publish after new rights withdrawal; cross-version reindex | Monotonic final tombstone, never resurrect |
| C26-06 | Pack with approved public rights; pack with incomplete/private rights | Approved indexed only if Option A; other states absent |
| C26-07 | Pack metadata version conflict and tampered provenance | Reject before projection, immutable identity preserved |
| C26-08 | Rollback File12/26 provider versions with existing denied docs | Denials persist, tested restore and re-sync |
| C26-09 | File00 entitlement changes, deleted session, user/device switching | Query and click-time fail closed for protected objects |
| C26-10 | File12/26 real staging trace and two independent reviewers | Same SHA and real receipt IDs, no synthetic PASS |

## E. CF04 protected storage and scanner decisions

The File12 code cannot prove the shared owner CF04 from its local manifest. Owner evidence must cover the **original ten STO-01..10**: (1) request actor/purpose/quota, (2) resumable chunks with hash/incomplete cleanup, (3) MIME/magic/malware/polyglot and scanner-down refusal, (4) non-webroot isolated render/OCR, (5) encrypted objects/key/manifest/checksum, (6) quarantine→ready lifecycle, (7) rights-safe signed delivery/CDN, (8) integrity/orphan/cost monitoring, (9) revocation/CDN and tombstone replay, (10) consistent backup/restore and legal holds. All **10/10 currently UNVERIFIED**, not ten proven failures.

Do not launch File12 upload/read/OCR/preview if scanner, keys, storage privacy or proper object-bound delivery are unknown. Require sample books with approved copyright, language/edition metadata, OCR derivative labels and read-only negative tests; capture only redacted evidence IDs, never keys, patient data, raw PDF bytes or full server logs.

## F. Decision record that remains open

| Decision | Current | Owner action needed |
|---|---|---|
| File12→File26 entity types | PDF-only proposed source; File26 requires PDF + Book Pack | Authorized File26/File12 choice A or B; versioned contract |
| Rights withdrawal search purge | File12 event declarations; File26 consumer ACK unproved | Signed, read-after-write search/tombstone evidence |
| CF04 storage, scanner, keys and restore | No verified live/staging owner proof | CF04 owner tests STO01..10 and secret-free receipt |
| Plugin installed directory | Historic `12-pdf-library-foundation` vs candidate `pdf-library-foundation-12` | Exact deployed path freeze and approved compatible migration |
| Go/No-Go | **NO_GO** | Full real staging, 2 independent reviews, Founder signoff, approved deploy and live retest |

## G. Handoff and evidence receipt fields

For each owner decision and scenario attach `owner_module`, `approver`, `decision_id`, `source_SHA`, `installed_SHA256`, `schema_option`, `migration_checkpoint`, `environment`, `UTC`, `test_id`, `role`, `expected`, `observed`, `sanitized_log_id`, `defect_id`, `root_cause`, `patch_SHA`, `retest_SHA`, `reviewer_1`, `reviewer_2`, `founder_signoff`, `live_retest`. Empty fields remain UNVERIFIED; neither GitHub CI nor this packet supplies runtime proof.

**Final live status:** Repository source as above; Deployed Version UNVERIFIED; DB Version UNVERIFIED; Migration State UNVERIFIED; Live Verification NOT PERFORMED. Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
