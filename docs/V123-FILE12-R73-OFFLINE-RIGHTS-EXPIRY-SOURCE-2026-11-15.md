# File12 R73 — source owner-rights expiry cannot outlive current lawful window (2026-11-15)

**Governing plan:** `Sabri-Website-Master-Plan-V12.3.docx`, File12 section: F12-FR-003 (rights expiry/source), F12-FR-008 (access policy), F12-NFR-001 (object authorization), F12-CEN-01 (canonical rights), F12-FUT-012 (encrypted offline vault). **No claims that Hostinger has this source installed.**

## Exact immutable source lineage
- Parent: Draft PR #68 `43917c51bf0ba183c31bd9059c6c327236eb6d3c`, `1.1.0-rc.47`, GitHub Actions `43687245998` 7/7 successful.
- Current change: stacked Draft PR #69, `1.1.0-rc.48`, declared schema/contract still `1.1.0`.
- `main` remains `2b369ef2d609247896e1ab32118f1dc498407f51` (README only).
- Chain of approved-to-review source inventories unchanged: frozen original 58 plugin files rc.43; immutable rc.44, rc.45, rc.46, rc.47 overlays plus new two-blob rc.48 overlay. SHA exactness is source-only, not deployed parity.

## R73 RED-first proof
- Native `PLDR_Future_REST::offline_authorization()` returns `authorized`, `edition_id`, and **`rights_valid_until`** after checking the current edition and rights owner.
- Existing `future-reader-vault.js` only checked `authorized`. Its local `meta.offlineValidUntil` could remain seven days even when current rights expiry was shortened to tomorrow. It could open a previously lawful encrypted PDF offline after the shortened expiry, without a new online check.
- First synthetic fixture implementation error (missing memory-DB handle) was corrected before the behavior proof. Exact behavior **RED** GitHub Actions run `43703471388`: `AssertionError: Cached PDF must not open offline after revised legal expiry`.
- **GREEN** `43703847186` at source SHA `3dee9a0d0b1d9e265c043120cca2fd4852954d26`: 7/7 jobs (PHP 8.1/8.3/8.4; frontend; Chromium; disposable WP+MySQL 7.0.1/7.1.4).

## Candidate repair
- Binds response `edition_id` to the active PDF edition and requires `authorized=true`.
- A non-null owner rights expiry must parse a strict timezone-aware ISO timestamp; malformed/currently elapsed expiry fails closed. If no owner expiry, original offline bound stays in force.
- **Monotonic restriction only:** new local expiry = MIN(original unexpired `offlineValidUntil`, current server `rights_valid_until`). It is saved in IndexedDB before permitting the cached local copy to open.
- Re-reads committed metadata after the online rights validation, before decrypting. Failure writes the durable denied-recheck latch used by R72; if durable denial cannot be stored, local cache is purged.
- Genuine disconnected use remains within the last verified offline expiry (nothing can retroactively learn revocations while offline).

## Test scope and limitations
The isolated Node VM runs the **actual JavaScript** with a synthetic nonextractable AES-GCM key, IndexedDB, mock response shape and controlled wall clock. It verifies shorter expiry, no new expiry, malformed expiry, wrong edition and initially disconnected use. It does not exercise real Hostinger rights, patient data, original books, legal licensing, mobile platforms or live redeploy.

**Ten existing release blocker categories unchanged.** 39 Staging, 54 runtime, 10 CF04, 77 V12.3 plus 60 QA cases still lack real staging/live receipts. File26 PDF+book_pack connector and right-tombstone ACK, private storage scanner, restore, two independent reviews, release approval and parity remain separate.

**Source defects R64–R73:** 10 found, 10 corrected in unmerged Draft candidates, 0 proven Live corrections. **Release status: NO_GO**.

**Repository main HEAD:** `2b369ef2d609247896e1ab32118f1dc498407f51`; **Deployed Version:** UNVERIFIED; **DB Version:** UNVERIFIED; **Migration State:** UNVERIFIED; **Live Verification:** NOT PERFORMED.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
