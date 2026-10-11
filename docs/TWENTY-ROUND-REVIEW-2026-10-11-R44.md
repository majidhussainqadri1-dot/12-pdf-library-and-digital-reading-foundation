# File 12 R44 — twenty scoped governing-plan reviews, then one offline-rights corrective batch
Date: 11 October 2026, Pakistan time.
Exact base: R43 Draft PR#38 HEAD `1695592dc350ecd77fd4211244ee99b8b70588f9`, GitHub Actions run 38128844576 completed green at that **earlier** SHA. GitHub main `2b369ef2d609247896e1ab32118f1dc498407f51` remains bootstrap README only. R44 CI/package evidence must be collected on its **new final HEAD**.

## Primary written requirements actually compared
- Original supplied **File 12 PDF Library and Digital Reading v1.1 Future-24 Amended (2026-08-11)**: exact governing FR001–019 functional table; NFR001–010 release gates; `F12-FUT-012` expressly permits an encrypted offline vault only for current offline rights; §11.6 prohibits bypassing **expired rights**, requires local expiry and reconnect/recapture server reauthorization; §11.8/11.9 separates source green from staging/live.
- Supplied **Three Central Plans Consolidated Governing Master Plan 2026**, v3.0: one canonical document owner (12), identity/entitlement File00, shell File20, visual system File25, security/privacy File24, discovery File26; derivative UI/index must never override current canonical rights.
- Companion File05 Learn: education course/lesson owner links to File12 PDF object/reader; File06 Encyclopedia: citation/knowledge owner without PDF storage duplication; File21 Home/News + File23 Publishing: discovery/interaction adapters, not rights owner. Other named owner boundaries File16 AI corpus and CF04 secure media remain unverified live.

These are **20 distinct bounded topical requirement-to-source inspections** of the exact R43 candidate. They do **not** constitute 20 full independent line-by-line audits, nor running E2E WordPress sessions. The 19 core area source anchors exist, but merely seeing names is not an acceptance test. The 20th inspection includes manual comparison of the exact rights timestamp and token TTL calculations against File12's offline safety law.

## Frozen findings before any fixes
| Pass | Requirement/owner | Distinct inspection | Source owner paths | Result |
|---|---|---|---|---|
| 01 | FR-001 / File26 | 16-type catalog and access-filtered cursor | `PLDR_Search::search / encode_catalog_cursor` | Source methods present; staging not verified |
| 02 | FR-002 / CF04 | Real PDF validation, scanner and controlled ingest | `PLDR_Ingest::validate_pdf / scan_file` | Source methods present; staging not verified |
| 03 | FR-003 / File24 | Rights/source/evidence and licensing cases | `PLDR_Rights::file_case / decide` | Source methods present; staging not verified |
| 04 | FR-004 | Edition and version canonical family | `PLDR_Core::current_edition / latest_edition` | Source methods present; staging not verified |
| 05 | FR-005 / FUT024 | Exact hash, distinct scans and no auto-merge | `PLDR_Future_Fingerprint::candidates / compute_and_store` | Source methods present; staging not verified |
| 06 | FR-006 | Private cryptography and restore key boundaries | `PLDR_Crypto` | Source methods present; staging not verified |
| 07 | FR-007 | Lawful OCR, cover and derivative generation | `PLDR_Ingest::generate_ocr / generate_derivatives` | Source methods present; staging not verified |
| 08 | FR-008 / File00 | Entitlement/access policy/strict current rights | `PLDR_Access::can_access_edition / strict_rights_timestamp` | Source methods present; staging not verified |
| 09 | FR-009 | Signed/short-lived object+operation/Range | `PLDR_Access::issue_token / deliver / parse_range` | Source methods present; staging not verified |
| 10 | FR-010 / File25 | Inline reader/preview and semantic visual ownership | `PLDR_Reader::reader_html / thumbnail_tokens` | Source methods present; staging not verified |
| 11 | FR-011 / File00 | Account-private reading progress/state | `PLDR_Reading::save_progress / state` | Source methods present; staging not verified |
| 12 | FR-012 | Private notes/tags/export/delete | `PLDR_Reading::add_item / parse_stored_tags / delete_item` | Source methods present; staging not verified |
| 13 | FR-013 / File26 | Lawful access-filtered OCR search/cursors | `PLDR_Search::ocr / encode_ocr_cursor` | Source methods present; staging not verified |
| 14 | FR-014 / File06 | Edition/page stable scholarly citations | `PLDR_Future_Citations::export` | Source methods present; staging not verified |
| 15 | FR-015 / File05/06/16 | Linked lesson/encyclopedia/context no duplicate owner | `PLDR_Future_Context::lookup` | Source methods present; staging not verified |
| 16 | FR-016 / File21/23 | Interaction and publishing owner through outbox | `PLDR_R21_Outbox::dispatch / register_contracts` | Source methods present; staging not verified |
| 17 | FR-017 / File24 | Rights takedown, decisions and appeals | `PLDR_Rights::appeal / temporary_restrict` | Source methods present; staging not verified |
| 18 | FR-018 / File24 | Format/integrity/preservation owner boundary | `PLDR_Future_Preservation::assess / read` | Source methods present; staging not verified |
| 19 | FR-019 / File20/25 | Human-verified accessible metadata | `PLDR_Future_A11y::inspect / verify` | Source methods present; staging not verified |
| 20 | FUT-012, NFR-001/010 / File00/16/24 | Offline rights expiry & signed grant temporal integrity | `PLDR_Future_REST::offline_grant / offline_authorization` | **R44-D01, D02 verified and corrected** |

## Proven defects and batch remediation
- **R44-D01 — historical invalid stored rights calendar silently normalized.** `PLDR_Future_REST::offline_grant` and `offline_authorization` each used `strtotime((string)$edition['rights_expires_at'])`; impossible dates like Feb 31 could roll over, and host-local time interpretation could diverge from File12's canonical strict UTC calendar parser fixed in R39. Both now call shared `offline_rights_expiry` with actual canonical `PLDR_Access::strict_rights_timestamp` and reject malformed/expired rights; invalid historical non-null values such as '0' fail closed.
- **R44-D02 — short remaining rights window could generate overlong 60-second token.** Future offline grant formerly computed `max(60,min(900,$valid-time()))`. The native `PLDR_Access::issue_token` enforces a **minimum 60-second token TTL**. If rights expiry was less than 60 seconds away, grant token lifetime exceeded the rights ceiling. The new `offline_delivery_token_ttl` denies issuance if <=120 seconds remain and otherwise caps grant TTL at `min(900,remaining-60)` so the signed token ends at least 60 seconds before legal policy expiry, allowing transaction skew; no policy widening.
- No attempt was made to change File00 entitlement, current rights owners, original PDF storage, native grants, companion modules, or live configuration.

**Executable safety tests:** `tests/test-r44-offline-rights-window.php` uses real canonical File12 strict calendar implementation in an isolated WP fixture; invokes actual Future offline reauthorization handler and actual new offline grant TTL helper, exercises malformed historical dates, expired/current UTC dates (Karachi host zone), missing rights, 60/90/120-second denial, normal 180+/900-second bounded grants and handler-source wiring. Not equivalent to real Hostinger MySQL, browser, token race or backup tests.

**Software candidate:** `1.1.0-rc.23`. **Source DB/schema and interface contract:** `1.1.0` unchanged. No physical migration/deployment.

## Unresolved change-control and staging gates
Written plugin inner folder `12-pdf-library-foundation` conflicts with built `pdf-library-foundation-12`; Founder-controlled upgrade/rollback plan required. Actual deployed plugin version/file checksums, physical database tables/wp_options/migrations, current installed File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 contracts, real malware/OCR/keys, production backups/restore, privacy/IDOR, mobile/Urdu RTL/accessibility/offline, weak network, real rights calendar cases, two independent staging review/fix rounds, Founder acceptance, live deploy and parity re-test are NOT VERIFIED.

| Five required live-first truths | Status |
|---|---|
| Repository HEAD | GitHub main `2b369ef2d609247896e1ab32118f1dc498407f51`; source parent R43 `1695592dc350ecd77fd4211244ee99b8b70588f9`; R44 exact final SHA/CI still required |
| Deployed Version | UNVERIFIED |
| Physical DB Version | UNVERIFIED (source schema 1.1.0 only) |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
