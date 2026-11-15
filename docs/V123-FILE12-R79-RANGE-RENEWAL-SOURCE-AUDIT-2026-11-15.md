# R79 — signed range download refreshed-token continuity (Source-only V12.3)
Date: 2026-11-15. RELEASE NO_GO; **not installed on Hostinger**.

## Governing requirement
The original attached Master Plan V12.3 File 12 section requires edition-exact original, lawful rights-aware download, revocation of old links and dependable reading. The R70/71/72 source candidate already checks exact 206 Content-Range and signed object SHA-256/size identity. This correction is limited to a source-level resumable download continuity defect and does not imply File17's 1GB cross-device transfers are complete.

## Reproduced bug R79
In `assets/reader.js` `runDownload()`, the initial delivery was captured once as `const d=download.session.delivery`. On a 403/410 from `d.url`, it acquired `renewed.delivery` and updated `download.session` but left `d` stale. Every later loop iteration used the *first expired URL*, causing repetitive 403s/new token requests. End-to-end file may eventually finish but degrades availability, violates the one-token continuity expectation, and increases server grant churn.

### Proof
- Parent for PR #77: Draft PR #76 exact source SHA `53d24c9d37371cb2619ce39d8b3ebe6be0435459` (`1.1.0-rc.52`); later PR #76 appended docs without altering the R79 source path.
- RED CI GitHub Actions `43710936251`, frontend step `R79 RED-first signed URL continuation after token refresh` fails: `AssertionError: one token renewal is enough for the entire remaining download`.
- Source fix updates active `d` when `download.session` is renewed. The change retains the R70 check that renewed `size` and `sha256` exactly match the original manifest and continues strict range response checks.
- Actual reader VM test `tests/test-r79-renewal-url-continuation.cjs` requires exactly one refresh and all later ranges from replacement URL, with three exact sequential 4-byte segments. Test uses synthetic 12-byte PDF-like payload, not an original licensed PDF.
- Exact immutable rc.53 delta overlay `docs/V123-FILE12-RC53-SOURCE-DELTA-OVERLAY.csv`, changed source `assets/reader.js` and software version-only entrypoint. Existing historical rc.43-52 blob evidence remains separate.
- Synthetic GitHub CI verdict and final exact PR SHA must be checked independently; never infer Stage/Live acceptance from GitHub alone.

## Remaining work
This source fix does **not** implement persistent across-restart resumable downloads or memory-bounded 1GB whole-file SHA-256; in-memory `download.chunks` and upper 128MiB full-digest cap are documented limitations. Hostinger active File12/DB migration, File26 rights tombstone ACK, CF04 private storage, licensed original books, staging device/RTL/WCAG and independent two-review evidence remain unavailable. Ten broader stop-ship categories unchanged. Do not merge/deploy as an accepted release without Founder GO and actual deployed parity.

**Repo main:** `2b369ef2d609247896e1ab32118f1dc498407f51` README-only. **Draft candidate:** `1.1.0-rc.53` PR #77. **Deployed version:** UNVERIFIED. **DB physical version:** UNVERIFIED. **Migration:** UNVERIFIED. **Live:** NOT PERFORMED. **Release:** NO_GO.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
