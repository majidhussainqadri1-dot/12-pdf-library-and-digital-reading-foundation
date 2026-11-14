# File 12 — V12.3 native disposable WordPress/MySQL evidence (2026-11-14)

**Scope:** EXACT WordPress plugin candidate 1.1.0-rc.43; isolated **throwaway GitHub Actions** environments only. **NOT Hostinger Staging or production.** No original books, patient cases, tokens, encryption keys or live WordPress settings were used.

## Frozen source

- PR #62 is a Draft stacked on V12.3 source governance PR #58; package code retains File12 PR #57 rc.43 baseline `d7c0d9458402019a3c46c73b3a7563329e9be8f7`.
- `tests/test-v123-native-wordpress-bootstrap.php`: only executes after checking actual WordPress `ABSPATH`, `CI=true`, `FILE12_CI_EPHEMERAL_DB=yes`, and exact synthetic `http://file12-ci.invalid/` site URL. It refuses a real/unknown site.
- GitHub Actions job `native-wordpress-disposable`: isolated `mysql:8.0` container, PHP 8.3, WP-CLI, WordPress 7.0.1 and 7.1.4 fresh downloads/installs, exact installed candidate copy.
- All credentials in this CI are public synthetic throwaway values, and the database is destroyed when the disposable job ends. No Hostinger secret is required.
- Plugin installation on the two disposable versions and real physical MySQL table creation were observed through GitHub job logs; this is **environment-specific testing, not source-only static assertions**.

## RED → harness repair → GREEN

| Exact commit | GitHub Actions | Result | Proven inference |
|---|---|---|---|
| `d66d3b50f122b23e6da621d3a60f851096f6970f` | `43615638503` | 4 original source jobs GREEN; 2 new native jobs FAIL | WordPress installed, File12 activated, but a `declare(strict_types=1)` in WP-CLI's evaluated test file generated PHP Fatal before assertions. A **test harness failure**, not a demonstrated plugin failure. |
| `8d09884d6f10ab57d836c32c57d7ddf916fc3999` | `43615794132` | **6/6 GREEN** | Removed incompatible `strict_types` declaration from WP-CLI eval-file fixture; no Plugin source change. Native DB/permission assertions passed on WordPress 7.0.1 and 7.1.4. |
| `23f73cc08fae54cb0118b6f3fc73ac4b4895d2ab` | `43615978881` | **6/6 GREEN** | Extended to real second schema migration/idempotency and real native REST route/unauthorized access negative checks, both WordPress versions successful. |

## Positive observations for BOTH WordPress 7.0.1 and 7.1.4

1. WordPress installed successfully on ephemeral MySQL 8.0; File12 `pdf-library-foundation-12` activated successfully.
2. Exact plugin source `PLDR_VERSION=1.1.0-rc.43`, declared `PLDR_DB_VERSION=1.1.0` verified in actual runtime, not live.
3. **13 actual MySQL owner tables** present: `documents`, `editions`, `objects`, `access_policies`, `reading_state`, `reading_items`, `rights_cases`, `derivatives`, `ocr_text`, `access_tokens`, `outbox`, `audit`, `book_packs`.
4. Physical disposable `pldr_db_version` marker agrees with expected source version, and `PLDR_R21_Readiness::core_ready(false)` returns true.
5. A second `PLDR_Schema::upgrade()` succeeds and the physical schema remains ready, supporting idempotence **in this fresh disposable environment only**. No historic 0.2.0/1.0.0 migration or production data rehearsal occurred.
6. No test original books or medical/patient data were present.
7. File26 connector truthfully remains `proposed`, PDF-only; `PLDR_File26_Connector::register()` returns false pending owner cutover.
8. Anonymous `PLDR_Book_Packs::register([],0)` is denied with `pldr_pack_forbidden`, and no book pack row appears.
9. Nonexistent edition cannot be granted anonymous PDF read.
10. Actual native REST route registrations present for `/pldr/v1/library`, `/ingest`, `/book-packs`, `/health`, `/reading/items`.
11. REST requests made as an anonymous WordPress session get HTTP 401 or 403 for POST `/ingest`, POST `/book-packs`, GET `/health` and GET `/reading/items` — native guards deny unauthorized access.

## Remaining acceptance: remain NO_GO

- Hostinger exact deployed artifact, plugin path, wp_options/schema/migrations/locks/logs: **UNVERIFIED**. Jetpack disconnected and WPVibe connection HTTP530 does not establish origin failure.
- Real File00/05/06/16/17/19/20/21/23/24/25/26 and CF04 installed versioned dependencies: **UNVERIFIED**.
- Original licensed books, scanner, encryption keys, private storage, OCR and lawful derivation: **UNVERIFIED**.
- Reader UI in real browsers, actual permissions across members, guardian rules, real cross-device privacy, rights/tombstone index & cache purge, true backup/recovery/rollback: **UNVERIFIED**.
- Existing 39 staging + 54 requirement + 10 CF04, V12.3 77 and 60 QA-15..24 cases: **UNVERIFIED** for authorized Hostinger Staging/Live.
- No independent real Staging reviewers, Founder approval, production rollout or Live retest.
- **Do NOT merge/deploy** because disposable job success only increases source-candidate confidence; it does not verify Hostinger or the unresolved integration contracts.

**Five truth fields:** Repository main SHA `2b369ef2d609247896e1ab32118f1dc498407f51`; tested original source candidate `d7c0d9458402019a3c46c73b3a7563329e9be8f7` with only new QA/test docs; Deployed Version UNVERIFIED; Physical Hostinger DB Version UNVERIFIED; Migration State UNVERIFIED; Live Verification NOT PERFORMED.

**Defect accounting:** In this native sequence, **0 confirmed File12 WordPress production code defects**. 1 harness-only incompatibility identified and fixed in Draft. Prior R64..R68 are 5 corrected source-only release governance defects, 0 verified Live fixes.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
