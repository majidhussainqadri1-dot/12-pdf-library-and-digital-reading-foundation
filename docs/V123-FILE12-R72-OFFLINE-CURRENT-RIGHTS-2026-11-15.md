# File12 V12.3 — R72 current owner-rights online authorization for cached encrypted PDF
Date: 2026-11-15

## Exact source and non-live status
- Governing Word document: `Sabri-Website-Master-Plan-V12.3.docx`, File12 page 41, `F12-FUT-012`, `F12-NFR-001`, `F12-FR-008`, `QA-19`, `AJ-14`.
- Parent is PR #67, exact code SHA `c8cb21794288cef836d711339bb1ae0fc82248b4`, version `1.1.0-rc.46`, seven GitHub jobs GREEN.
- New stacked **Draft PR #68**, isolated software candidate **`1.1.0-rc.47`**, unchanged declared database/contract **`1.1.0`**.
- Repository `main` = `2b369ef2d609247896e1ab32118f1dc498407f51` (README-only); no main merge and no production/staging deployment.

## R72 root cause reproduced in exact original client
`pdf-library-foundation-12/assets/future-reader-vault.js` only called `offline-authorization/<edition>` in the `open()` path when BOTH `navigator.onLine` and persisted `reconnectPending()` were true. The browser may close while offline and reopen online without ever recording an `offline` event, leaving `reconnectPending() === false`. That allowed a formerly authorized encrypted offline copy to be opened **online** without obtaining current owner rights, despite an otherwise intact AES-GCM ciphertext and SHA-256.

**This was a source-level behavior flaw, not a verified Hostinger exploit.** No assertion that prohibited source bytes became accessible in production can be made.

## RED-first reproduction and exact correction
- New standalone Node VM fixture `tests/test-r72-offline-current-rights.cjs`: actual shipped offline-vault asset, real nonextractable WebCrypto AES-GCM local ciphertext, synthetic IndexedDB cached data, valid expiring-in-future manifest, and mock current owner rights.
- Initial harness syntax repaired before evaluating product behavior. **RED** GitHub Actions run `43686398961` explicitly failed `AssertionError: online offline-vault open must consult current rights even without reconnect flag`.
- Minimal fix: `if(navigator.onLine&&!await reauthorizeAfterReconnect())return;`. This enforces online rights validation regardless of whether a prior browser offline event fired, without adding extra network traffic when genuinely offline.
- Negative online-revoked and positive online-approved tests, plus offline-unexpired intact copy test in same fixture.
- New version marker `rc.47`; immutable source version overlays: rc.43 58 exact baseline blob IDs, rc.44 (3 deltas), rc.45 (2), rc.46 (2), rc.47 (2). No historic source evidence overwritten.
- **GREEN** GitHub Actions run `43686569205` on exact candidate SHA `68d6081463b71ee82174dd20c691728ee52c5851`: seven of seven jobs including PHP 8.1/8.3/8.4, native isolated WordPress/MySQL 7.0.1 and 7.1.4, Node frontend and Chromium synthetic browser.

## R72 supplementary negative test: online denial followed by offline reopening

- Extended the **same R72 defect acceptance**, not a newly asserted Live incident: after an online owner response `authorized=false`, a subsequent offline opening in the same device must be prohibited even when the offline PDF local expiry has not elapsed.
- The real-JS synthetic regression `tests/test-r72-offline-current-rights.cjs` now verifies current rights on online opening, persistence of a per-edition denied reauthorization marker `pldrOfflineReauthDenied:<edition>`, and prevention of later offline bypass.
- The corrected source uses a dedicated per-edition, durable denial latch distinct from the usual `offline` network-event marker, so an initial genuinely offline reading period is still permitted within its original grant.
- A later successful **online** current-rights check can clear the denial latch; failure to persist its denied state triggers local-copy purge rather than falsely promising lockout.
- Parent rc.46 inventory, source rc.47 version and declared DB `1.1.0` remain unchanged; the rc.47 two-file delta overlay is updated with final source blob SHA and UTF-8 byte count. The exact GitHub CI record for this supplemental correction is separate from the earlier `43686569205` run.
- This remains a **source-only** safety measure; authorized real host/device/browser tests and genuine rights-policy obligations remain unverified.

## Residual scope and mandatory stop-ship
- A genuinely offline browser cannot query server rights and is allowed only the previously issued, locally unexpired offline grant. Current owner can revoke remote access when online, subject to the documented offline-grant window; strict instant global revocation while disconnected is not cryptographically demonstrable here.
- Real installed keys, true edition rights, licensed PDF source and 128MiB offline cap on actual phones/tablets must be staged and tested.
- 39 previous Staging, 54 requirements runtime, 10 CF04 controls, 77 V12.3 mapping, 60 QA cases and separate live receipt all remain **UNVERIFIED**, not failed.
- File26 `pdf`/`book_pack` contract and revoked rights indexed tombstone ACK, CF04 scanner/key/private-object/restore, precise Hostinger plugin folder/version/schema/migrations, independent review and Founder release approval remain unavailable.
- No developer can call this resolved in Live: corrections in unmerged Drafts R70-R72 bring **new source-only defects = 3; Draft source fixes = 3; Live-verified fixes = 0; stop-ship categories = 10**. Legacy R64-R69 source defects are additionally 6 and remain Draft-only.

**Mandatory 5 reality fields:** Repository main HEAD `2b369ef2d609247896e1ab32118f1dc498407f51`; Deployed Version **UNVERIFIED**; Physical DB Version **UNVERIFIED**; Migration State **UNVERIFIED**; Live Verification **NOT PERFORMED**.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
