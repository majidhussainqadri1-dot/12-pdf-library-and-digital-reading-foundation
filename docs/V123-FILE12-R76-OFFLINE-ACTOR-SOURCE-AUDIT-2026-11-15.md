# File 12 R76 — Offline Vault authenticated-actor boundary and exact-source evidence
**Date:** 2026-11-15 | **Timezone:** Asia/Karachi | **Status:** SOURCE TESTED / UNMERGED DRAFT / LIVE NOT VERIFIED

## Plan and canonical dependency context
V12.3 Master Plan File12, PDF reader, private progress/bookmarks and rights-aware original source; related FUT-012 offline, NFR-001/002, M06/M07, QA-19/20/24, AJ-14. No fabricated Hostinger or legal-rights acceptance.

## Source reality and branch reconciliation
- Repository `main` SHA `2b369ef2d609247896e1ab32118f1dc498407f51`: only README; not proof of a live plugin.
- Actual prior latest Draft PR #71 (R75), SHA `092f3adcebba9a597d0f8322a08dde861507053c`, software `1.1.0-rc.50`.
- This PR #73 is **R76 / 1.1.0-rc.51**, branched DIRECTLY from that exact PR #71 SHA. Retains previous rc.48 R73 current-rights expiry clamp, rc.49 R74 provider-text/URL hardening, and rc.50 R75 private storage path protection.
- Earlier conflicting parallel Draft PR #72 reused R73/rc.48 and did not include newest upstream PRs. It has been **closed without merge**; its code was rebuilt on this branch. Do not merge, cherry-pick or deploy PR #72.
- `PLDR_DB_VERSION` / source contract remain `1.1.0`; **physical** WordPress DB and migration state unverified.

## Demonstrated source defect
Actual `future-reader-vault.js` IndexedDB metadata and encrypted book chunks were keyed by edition, without an authenticated WordPress actor ID. Its offline `open()` allowed a logged-in browser session from a different WordPress user to open a previous user's same-edition cached copy while no Internet access existed and without an actor-specific check. A browser-only encryption key per origin does not by itself create per-WordPress-user isolation.

**Red-first evidence:** exact inherited source `092f3ad...` with added actual-JS synthetic Node/WebCrypto/IndexedDB fixture `tests/test-r76-offline-user-identity.cjs`. GitHub CI run `43706807139` failed the frontend package at `R76 offline PDF actor-isolation red-first regression` because a different logged-in actor could open the cached book.

## Corrective source implementation (unmerged)
1. WordPress `PLDR_Future::assets()` explicitly provides `userId = get_current_user_id()` alongside `loggedIn`.
2. Secure offline REST `offline_grant` and `offline_authorization` return `actor_id` from the actual authenticated WordPress session.
3. Browser `store()` requires a positive valid current actor and an exact server grant actor match before encryption/write. Its stored metadata includes `meta.actorId`.
4. `open()` requires current account ID = exact cached actor even **before** online/offline authorization and checks the persisted metadata again after an online rights-expiry reauthorization; another user or legacy/unbound metadata fails closed. Existing R71 SHA-256/AEAD integrity and R72 online revocation latch remain.
5. Original R71/R72/R73 synthetic fixtures now exercise valid account-bound server grants and cached editions. R71 additionally checks that a grant for a different actor is rejected before requesting any PDF bytes.
6. `tests/test-r76-native-wordpress-actor.php` executes in real **disposable** WordPress 7.0.1 and 7.1.4, verifying authenticated runtime JS config actor vs anonymous 0, avoiding assertion from a static substring alone. Both executions refuse any real Hostinger environment.
7. Original 58-file rc.43 exact source manifest and rc.44–50 overlays remain immutable. `docs/V123-FILE12-RC51-SOURCE-DELTA-OVERLAY.csv` records exactly four plugin source changes, with Git blob hashes and source audit states `SOURCE_DELTA_AWAITING_INDEPENDENT_REVIEW`; no fake installed parity.

**First verified green:** GitHub Actions run `43707352538` on SHA `784262ebfcf9298d597b58e54dcd8b7b8e3853f8`, **7/7** (PHP 8.1/8.3/8.4, frontend Node, Chromium, native disposable WordPress/MySQL 7.0.1 and 7.1.4). Later doc/ledger-only commits must separately pass exact-head CI before freezing the candidate.

## Security scope / limitations (important)
- The actor check is a defense against **ordinary same-browser account-context mistakes**. It is **NOT a DRM guarantee** against a local adversary capable of running arbitrary same-origin JavaScript or directly manipulating indexed local storage; browser origin-scoped CryptoKeys remain accessible to same-origin scripts. Strong per-user cryptographic isolation across users of one OS/browser profile needs separately controlled keys and real device/account boundary, approval and testing.
- This does not prove instantaneous offline revocation without a connection. Current lawful expiry is still bounded and local; reconnection requires current server authorization.
- Real certified books, CF04 object/private storage, File26 PDF/BookPack connector owner decision and rights-event tombstone ACK, 77 mapped V12.3 acceptance IDs, 39 historic staging gates, 54 runtime requirements, 10 CF04 controls, 60 QA15-24 scenarios, WCAG/RTL, migration and restores remain UNVERIFIED, not confirmed defects.
- Two independent reviewers, Founder GO/NO_GO, exact installed Hostinger artifact and physical DB schema, test credentials/devices and production re-test are unavailable.

## Final reality ledger
`Repository HEAD/main`: `2b369ef2d609247896e1ab32118f1dc498407f51`.
`Source candidate`: PR #73, exact current HEAD to be rechecked, `1.1.0-rc.51`.
`Deployed Version`: **UNVERIFIED**.
`Physical DB Version`: **UNVERIFIED**.
`Migration State`: **UNVERIFIED**.
`Live Verification Status`: **NOT PERFORMED**.
`Release`: **NO_GO**.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
