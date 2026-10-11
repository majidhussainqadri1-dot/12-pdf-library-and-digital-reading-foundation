# File 12 — R29 Fresh Twenty-Round Plan/Code and Cross-File Audit
**Date:** 11 October 2026 (PKT). **Baseline:** Draft PR #16 HEAD `cac343ecae14b8cff08be56c83cc6d32761426d3`. R28 alternate PR #17 HEAD `f6df2ce08c6d245b6abb0578e234b61eb2034f0c` diverges and is NOT assumed deployed or merged.

## Governing source and claims

Read against the actual File 12 v1.1 Future-24 amended master plan, the three central consolidated governing plans, and companion File 05, 06, 21, 23, 25 plans provided for this platform. Applied the File 12 canonical-owner/entitlements contracts with Files 00, 01, 05, 06, 16, 17, 19, 20, 21, 24, 25, 26 and CF-04. Coverage is 19 core FR, 10 NFR, 24 Future enhancement IDs and 12 source-side adapter anchors.

The inherited `tests/test-r28-twenty-full-plan-sweeps.php` performs 20 **repeated complete static plan-to-source matrix passes**, checking 53 stable IDs and 12 adapter anchors in *every* pass, plus a distinct targeted invariant per pass. R29 retains and reruns that suite **on the corrected exact HEAD** and adds a separate executable, actual-access-class negative-path regression. This is not twenty independently executed WordPress end-to-end tests, twenty human line-by-line audits or proof of live safety.

## New twenty-round review ledger (findings frozen before corrections)

| Round | Plan / native owner boundary checked | Finding |
|---:|---|---|
| 01 | Exact repository HEAD and R28 fork: GitHub PR16 vs PR17, main/source truth | Both Draft/unmerged. PR16 includes additional HTML reader pregrant and 20-pass matrix, chosen R29 base |
| 02 | F12-FR-001/004: canonical document, edition, object and taxonomy | Owner code present; real data/installed DB unverified |
| 03 | F12-FR-002/005: PDF validation, malware/duplicate SHA, upload limits | Source validation hooks present; external malware provider and real scanned PDFs unverified |
| 04 | F12-FR-003/017: license source, expiry, rights disputes/takedown/appeals | State-machine source present; real rights case journeys unverified |
| 05 | F12-FR-006: encrypted original, private path, keyring, corruption quarantine | Crypto and storage source present; real key backup and restore unverified |
| 06 | F12-FR-008/009: signed original/derivative attachment at ISSUE and DELIVERY | R28 current_delivery_binding preserved and its actual-class regression retained |
| 07 | F12-NFR-001 and File00: explicit anonymous sentinel vs current curator session | R26 test and negative-identity code retained; current external File00 integration unverified |
| 08 | F12-FR-008/009: expired and future persisted document embargo dates | **R29-D01 CONFIRMED.** Invalid nonempty stored embargo string had `strtotime(...)=false`, which made `>time()` false and permitted anonymous/ordinary reader. Fixed after audit |
| 09 | F12-FR-008/009: allowed access-policy audience and external entitlements | **R29-D02 CONFIRMED.** Unknown persisted audience could reach arbitrary entitlement adapter; if it returned true, data was readable. Fixed after audit |
| 10 | Signed token issue, audience hash, token expiration, usage rate and revocation | Existing controls inspected; concurrency/HTTP test outstanding |
| 11 | Reader HTML + REST manifest and IIIF preflight BEFORE bearer grants | R28 HTML and R27 REST/IIIF ordering checks retained; actual WP failure injection pending |
| 12 | F12-FR-010/011/012 private reader, notes, progress, cursor and handoff | Source ownership/version controls present; real actors and multi-device races pending |
| 13 | F12-FR-013/014 citation/page provenance, OCR/correction/search | Source mechanisms present; File26 real search index and OCR provider unverified |
| 14 | F12-FUT-001..024: all 24 Future IDs and native feature owners | Static Future registry and source code present; real browser/provider functionality not all evidenced |
| 15 | F12-FUT-010/012/018: IIIF/Offline/WebCrypto/accessibility | Static grants, vault and human-verification flow inspected; device/offline/RTL/screen reader pending |
| 16 | Files05/06/16: course, encyclopedia, AI corpus consumer/rights boundaries | Source-side adapters found; counterpart exact versions and end-to-end checks absent |
| 17 | Files17/19/21/23: room/messaging owner, outbox events, publishing consumers | Source-side events and hooks present; consumers not runtime verified |
| 18 | Files01/20/24/25/26, CF-04: routes, shell UI, privacy assurance, global search, secure media | Source-side adapters present; no runtime plugin/permissions/index parity evidence |
| 19 | F12-NFR-002/007/008: privacy, retention/erasure/legal hold, DB schema lock, backup/rollback | Source pathways present; deployed physical schema, migration/options/restore unverified |
| 20 | Version, package folder, CI, release/change control | R28 source candidate rc.7, new R29 security change receives **distinct rc.8**; folder naming conflict not silently renamed |

## Corrective batch — only after the full review

### R29-D01 — malformed embargo is not permission
In `PLDR_Access::can_access_edition()`, imported or corrupt nonempty `embargo_until` previously passed via a false `strtotime()` value. Its modern write path validates dates, but legacy/migration/manual DB state is a separate reality. Non-parseable embargo now causes a fail-closed denial for anonymous/ordinary readers, with an auditable `embargo_until_invalid` event. The pre-existing authorized manager exception remains, matching the method's curator policy and the rights-expiry exception.

### R29-D02 — unknown audience cannot become entitled
In the same owner policy check, a stored value outside the four authorized policy audiences (`public`, `account`, `education-entitled`, `assigned`) previously fell through to a third-party `pldr_user_entitled` provider. An external `true` could authorize an undefined category. Invalid audiences now audit `policy_audience_invalid` and deny access **before any external provider call**; approved audiences continue to work.

### New regression and retained safeguards
`tests/test-r29-policy-fail-closed.php` executes the *real* PLDR_Access class with **23 explicit assertions**, including valid public and expired embargo, future embargo denial, malformed embargo denial, founder/manager exception, invalid audience denial even when a provider would grant entitlement, and valid account/education/assigned cases. CI also reruns R26 anonymous sentinel, R27 Reader/IIIF ordering, R28 real detached grant binding, R28 HTML reader pregrant and inherited 20 x full 53+12 static matrix. All source/version/test/package markers updated to `1.1.0-rc.8` while repository DB and integration contract remain `1.1.0`.

## Unresolved decisions and independent evidence gates

**Package root mismatch:** governing plan says `12-pdf-library-foundation`, existing WordPress plugin and ZIP root say `pdf-library-foundation-12`. Changing plugin directory can alter installed plugin identity. Do not rename without Founder-authorized change-control and tested deactivation/upgrade/migration/rollback.

**Source vs deployed:** PRs #16, #17 and R29 are Draft/unmerged. Even a green CI is a GitHub source/package statement only, not evidence of live deployed file checksums, database schema, active migration, wp_options, provider credentials, real user/guardian roles or rights.

**Separate required acceptance:** Hostinger staging real File00/01/05/06/16/17/19/20/21/23/24/25/26/CF-04 integrations; signed-token malicious/idempotency/revocation; corrupted policy DB read; real rights/embargo and legal hold; encrypted PDF/key backup/restore; real browsers/Urdu RTL/offline/a11y; 2 fresh staging reviews, rollback, Founder sign-off; approved deploy/live re-test and checksum parity.

## Five-part reality report
- Repository HEAD: resolve final R29 exact SHA after changes/CI; main remains bootstrap-only.
- Deployed Version: **unverified**.
- DB Version: **unverified** (repository schema contract is 1.1.0).
- Migration State: **unverified**.
- Live Verification Status: **not performed**.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
