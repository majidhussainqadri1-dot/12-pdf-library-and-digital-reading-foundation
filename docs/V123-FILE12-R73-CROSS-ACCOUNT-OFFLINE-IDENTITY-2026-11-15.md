# R73 — File 12 offline vault current-user binding (Source, NOT Live)
2026-11-15, Asia/Karachi

## Baseline / plan
The uploaded approved `Sabri-Website-Master-Plan-V12.3.docx` directs File 12 to preserve rights-aware PDF downloads, private bookmarks/notes, versioned edition and rights metadata, and reliable access revocation. This candidate also addresses F12-FUT-012 (encrypted offline vault), F12-NFR-001/002, M06/07 and QA19/20. Original source inspected before any patch: Draft PR #68 SHA `43917c51bf0ba183c31bd9059c6c327236eb6d3c`, version 1.1.0-rc.47. GitHub main `2b369ef2d609247896e1ab32118f1dc498407f51` is README-only.

## Reproduced source defect R73
The original `PLDR_Future::assets()` only passed `loggedIn` as a boolean to the advanced reader. The offline vault stored encrypted bytes and `meta` under **edition ID only**, without an actor identity. When a different user signs into the *same browser origin* and loads the reader offline, the original local copy could open subject to expiry, because the stored edition matched and the browser cannot do online identity reauthorization.

**Red-first proof:** test `tests/test-r73-offline-user-identity.cjs` executes the original actual `assets/future-reader-vault.js` under Node VM with an authentic synthetic AES-GCM encrypted original and fake IndexedDB. RED run `43705657374` failed with `AssertionError: different logged-in account must NOT read cached other-user PDF offline`.

## Source-only fix
- PHP future reader config supplies **current WordPress actor userId** (0 when anonymous).
- Server `offline-grant` and `offline-authorization/<edition>` return `actor_id` from `get_current_user_id()`; this is an ordinary numeric scoped identity, never credentials.
- Offline vault requires a positive numeric actor and rejects any token whose server `actor_id` does not match current page identity, before writing it.
- Persist `meta.actorId` with the offline document. Before **any online or offline open**, require `meta.actorId === current actor` and authenticated status; legacy unbound caches fail closed without silently transferring their contents to another account.
- Existing R71 full-file SHA-256, AES-GCM, 128 MiB cap, R72 online rights reauthorization and denial latch remain in place.
- The R71/R72 fixtures are updated to supply authenticated actor IDs; R73 covers original user, different user, no actor and legacy no actor.
- Candidate `1.1.0-rc.48`, unchanged source schema and contract `1.1.0`. Source ledger remains immutable over rc.43 58 plugin blobs, then rc.44/45/46/47 deltas, with an additional exact **four-file rc.48 delta** (`docs/V123-FILE12-RC48-SOURCE-DELTA-OVERLAY.csv`).

## Critical limits
This is a source-level ordinary-browser *account-context guard*, **not a claim of strong DRM or resistance to a local adversary able to manipulate origin JavaScript/IndexedDB**. A non-extractable WebCrypto key remains usable by scripts on the same origin. Strong cryptographic per-user offline isolation across accounts requires a separately approved key ownership/provider design and actual devices/OS identity boundaries; cannot be proven through these source fixtures. Cross-tab concurrent account switches and local-browser eviction/restore also need real-device tests.

File26 `pdf`/`book_pack` owner contract, tombstone ACK and CF04 encrypted storage/scanner/restore remain blocked. No original licensed PDF was tested; actual Hostinger runtime/deployed plugin/schema/migration still unverified. Do not merge or deploy this Draft as a completed live fix. Independent reviewers and the founder release gate remain pending.

**Reality:** Repository main HEAD `2b369ef2d609247896e1ab32118f1dc498407f51`; Draft PR #72 latest HEAD must be independently checked; Deployed Version UNVERIFIED; Physical DB Version UNVERIFIED; Migration State UNVERIFIED; Live Verification NOT PERFORMED; release NO_GO.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
