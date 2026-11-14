# File12 V12.3 — R69 anonymous PDF Reader private-REST suppression

**Date:** 2026-11-14 | **Scope:** source-only defect in unmerged Draft Plugin `1.1.0-rc.43`, corrected in distinct **Draft candidate `1.1.0-rc.44`**. No Hostinger source, physical DB, WordPress production, installed key or live request was modified.

## 1. Frozen source and root cause

Original source baseline: File12 PR #57 `d7c0d9458402019a3c46c73b3a7563329e9be8f7`. At `pdf-library-foundation-12/assets/reader.js`, the initialization always called `loadPrivateItems()` and `updateFrame()`, which always scheduled `saveProgress()`. Both invoked `/wp-json/pldr/v1/reading/items` (GET) or `reading/progress` (POST) even for unauthenticated readers of legitimately public PDF metadata. Server-side WordPress handlers correctly rejected these requests with HTTP 401/403. The client nonetheless made unauthorized/private-state calls and could display an unnecessary private-note error on a guest public reader. The server HTML also showed Bookmark/Private note/Highlight controls irrespective of login.

This is a **reproduced SOURCE UX/privacy request flaw**—not proof of disclosed private data and not a verified production exploit.

## 2. RED-first independent behavior test

- Branch `fix/file12-r69-guest-private-reader-guard-20261114`, Draft PR #65.
- Red commit `e4421e1819cdf731c6ea6ea32449fb875fae24d7`; GitHub Actions `43618836217`.
- `tests/test-r69-reader-guest-private-requests.cjs` executes actual `reader.js` in a bounded Node VM with a fetch spy, simulating guest configurations `canSavePrivate=false` and `undefined`, and registered `true`.
- **Expected RED:** guest must cause *zero* private REST calls, and registered user must still GET notes and POST progress. PHP 8.1/8.3/8.4, native WordPress 7.0.1/7.1.4 were otherwise GREEN; frontend failed at the precise R69 red step. No live incident was used.

## 3. Minimal source repair and traceable new candidate

- `reader.js`: guard both automatic `loadPrivateItems()` and asynchronous `saveProgress()` with strict `cfg.canSavePrivate === true`. Also guard click actions `bookmark/note/highlight`; anonymous public PDF reading, page navigation and citation remain available.
- `class-pldr-reader.php`: PHP issues canonical strict `canSavePrivate => is_user_logged_in()` into `PLDR_READER` config; guest toolbar hides private editing controls and displays a truthful sign-in hint; member toolbar retains those controls. Backend owner authorization remains unchanged.
- `pdf-library-digital-reading.php`: bump exact Plugin header and runtime `PLDR_VERSION` from `1.1.0-rc.43` to `1.1.0-rc.44`, **source DB still `1.1.0`**.
- Frozen prior 58-source-file inventory in `docs/V123-FILE12-RC43-EXACT-SOURCE-INVENTORY.csv` remains untouched. `docs/V123-FILE12-RC44-SOURCE-DELTA-OVERLAY.csv` separately pins the 3 changed blobs, original blob SHA, new blob SHA, byte size and author/source status; `tests/test-v123-source-inventory.php` prohibits any unlisted source change. **All three changed files still await two real independent reviewers**, and this is not an approved release ZIP.

## 4. Tests and exact results

| Test phase | Evidence | Result and limit |
|---|---|---|
| R69 RED | `43618836217` | Expected R69 frontend test FAIL; other 5 original source/native jobs GREEN. The browser job on inherited Ubuntu26 lacked compatible Playwright—independent CI harness problem |
| Chromium harness repair | Parent PR #64 `d08b907178f101345a8f376003277d851d0bca3b`, run `43619198379` | Pin Browser runner to supported Ubuntu24; **7/7 GREEN** on the original rc.43 source-only candidate |
| R69 JS+PHP source patch | `9dc4bc5335b3bee62b348367e6ed10f3b2bce7cb` (JS) and `1c485430ebebd1a29bf30053d8dc3d9f8724503b` (PHP) | Corrected only Guest UI/client request behavior; Source hash checks RED until explicit rc44 delta overlay registered |
| rc44 version/inventory | `948e32a8fb9dd1ae85c2f7c0691f8494c38cda0b` (header), `52402576fe446a1db1bc3e331f7bafd3d577a028` (overlay-aware manifest checker) | Version and 3-file immutable source Delta accounted for without rewriting rc43 frozen baseline |
| Native PHP Renderer | `tests/test-r69-native-public-reader-markup.php`, `43620069173` | WordPress 7.0.1 and 7.1.4 on genuine disposable MySQL: Guest toolbar hides private controls and emits `canSavePrivate:false`; member shows all private controls and emits `true` |
| **Latest complete run** | `43620069173`, exact code head `055868260ad04f838455b877652dedc34ba395c6` | **7/7 GREEN**: PHP8.1, PHP8.3, PHP8.4, Frontend Node R69 behavior, native WordPress/MySQL 7.0.1 and 7.1.4, and Chromium 5 responsive widths + synthetic RTL |

The first attempt of the new WP-CLI renderer fixture failed because WP-CLI did not fire the normal `wp_enqueue_scripts` hook, so the test could not inspect the registered inline script. That was corrected in **the test harness only** by invoking the actual `PLDR_Plugin::register_assets()` method; no extra product defect was inferred.

## 5. Browser screenshots and scope

GitHub Actions `43619198379` contains the artifact `file12-disposable-qa15-chromium-screenshots` with 10 screenshots for 320/390/768/1024/1440px in LTR and synthetic RTL. The actual empty library returned HTTP 200, zero JS page errors and zero unintended horizontal document overflow at all five widths, plus keyboard-focusable Search. **This does not validate a populated library, PDF iframe viewer, original OCR, or WCAG 2.2 AA.**

## 6. Boundaries and outstanding risks

- No proof of a particular File12 version *installed on Hostinger*. WordPress.com Jetpack site connector reports `site_disconnected`, WPVibe is unconnected and observed HTTP530 while connecting. These are integration symptoms, not a diagnosed Hostinger outage.
- Real File26 PDF+BookPack typed acceptance, File26 consumer indexed rights tombstone ACK/replay/cache purge, CF04 scanner/key/private storage/restore, source-licensed actual books, native File00 active membership assertions and full Site Shell remain unverified and deliberately dormant where relevant.
- Original **39 staging + 54 runtime + 10 CF04**, V12.3 **77 mapped** and QA **60 cases** retain runtime `UNVERIFIED`. Successful disposable QA is **not** a substitute for Hostinger staging, WCAG, two independent reviews, Founder GO or production live-retest.
- **Known source-level defect tally after R69: 6 (R64-R69), 6 source candidate corrections, 0 live-verified fixes**. Ten stop-ship blocker categories remain. The same repo main remains README-only; the rc44 candidate PR #65 stays Draft/unmerged.

**Mandatory incident report:** Repository main HEAD `2b369ef2d609247896e1ab32118f1dc498407f51`; Source rc43 frozen SHA `d7c0d9458402019a3c46c73b3a7563329e9be8f7`; rc44 corrected Draft PR #65. **Deployed Version UNVERIFIED | Hostinger DB Version UNVERIFIED | Migration State UNVERIFIED | Live Verification NOT PERFORMED | Release NO_GO.**

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
