# File12 R39 — 20 focused plan-to-code inspections, strict real calendar/rights authorization repairs
Review date: 11 October 2026 Pakistan. Scope: **GitHub source** only; not live Hostinger runtime.

## Governing materials physically inspected
Uploaded, extracted Word files: `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx`; `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`; companion File05 Learn, File06 Encyclopedia, File21 Home/News Feed, File23 Doctor/Founder Publishing Dashboard and File25 Public Visual Experience plans. This review follows amended File12's founder-approved precedence: latest Founder direction > newer central plans > amended File12 rules > earlier noncontradictory File12 rules; repository/staging evidence only proves implementation status.

**Exact repository baseline**: R38 Draft PR #33 source SHA `186095b1febe3d795798c5bcdb7a2f09f93fa380` and GitHub Actions #474 exact-green. GitHub main is still bootstrap-only `2b369ef2d609247896e1ab32118f1dc498407f51` (not evidence of installed plugin). R39 Draft PR #34 is based linearly on R38, not a replacement for deployed source.

## Twenty separately scoped review foci (finding freeze BEFORE remediation)
Twenty manual *requirement-and-source-path* inspections; **not** 20 complete exhaustive independent human reviews, 20 WordPress browser sessions or 20 production end-to-end tests. Existing R28 automated test repeatedly checks 20×(53 File12 FR/NFR/FUT IDs +12 source-only external owner adapter anchor references)=1,300 static source-presence checks; these do not prove behavior/deployment.

| Pass | Actual File12 governing requirement / inspected source owner | Frozen source finding and operational limit |
|---:|---|---|
| 01 | FR-001 document catalog; `PLDR_Core::DOCUMENT_TYPES` / `PLDR_Search` | Existing source type/facet contract, deployed indexes unverified |
| 02 | FR-002 PDF ingest signature/malware/size; `PLDR_Ingest` | Existing R31/R32 real-file tests retained, scanner provider unverified |
| 03 | FR-003 rights/source license/territory/expiry; `PLDR_Access::can_access_edition` | **R39-D03:** imported `rights_expires_at='2026-02-31'` silently rolled to March 3 using `strtotime`, potentially extending rights for normal reader. Strict calendar validator now checked on reads |
| 04 | FR-004 edition/version/supersession; `PLDR_Core::edition` | Source edition owner/version path present; DB parity unverified |
| 05 | FR-005 duplicate SHA metadata; `PLDR_Ingest` | Source only, real corpus uniqueness unknown |
| 06 | FR-006 AES-GCM private object and signed range; `PLDR_Crypto` | Crypto source QA retained; installed keyring/restore unverified |
| 07 | FR-007 thumbnails/OCR derived object metadata; `PLDR_Ingest` | Source only; OCR/scanner/CF04 providers not runtime-tested |
| **08** | **FR-008 Access Policy embargo mutation, `PLDR_Access::update_policy/parse_embargo`** | **R39-D01:** PHP `strtotime('2026-02-31')` silently returns 2026-03-03, accepting impossible dates; new `strict_rights_timestamp` uses calendar `checkdate`, valid hours/min/sec and ISO offset bounds, reject invalid before DB mutation |
| **09** | **FR-008 historical/persisted embargo read, `PLDR_Access::can_access_edition`** | **R39-D02:** imported invalid embargo such as Feb 31 was silently accepted/rolled forward instead of malformed fail-closed; both mutation and actual read now share strict parser, audit/deny ordinary users; authorized curator override remains as preexisting |
| 10 | FR-009 short-lived signed delivery, `PLDR_Access::issue_token/revoke_document` | Existing R34–R38 owner-bound token QA retained; actual live IDOR/HTTP/revocation unverified |
| 11 | FR-010 accessible in-browser PDF reader, `PLDR_Reader::document_html` | Existing R37 legacy false flag corrections retained; real keyboard/RTL browser unverified |
| 12 | FR-011 private cross-device progress, `PLDR_Reading::save_progress` | Source optimistic concurrency present; real multi-device staging not tested |
| 13 | FR-012 private annotations / tags / delete, `PLDR_Reading` and REST | R35–R37 exact-one-row private delete and strict read/write tags QA retained |
| 14 | FR-013 authorized metadata+OCR search, `PLDR_Search`, `PLDR_REST::ocr_search` | R38 malformed GET protections retained; installed File26 federator unknown |
| 15 | FR-014 citation edition and page provenance, `PLDR_REST::citation` | R38 citation validation/source edition ID retained, external citation resolver/unified UI unverified |
| 16 | FR-015 related content File05/06/16/21, `PLDR_Future_Context` / `PLDR_Future_Corpus` | Source adapter ownership present; no duplicate course/encyclopedia truth, installed companion versions unverified |
| 17 | FR-016 comments/reader interactions, `pldr_interaction_controls_html` | Source UI hook present; File17/21 actual communication owner and event consumers unverified |
| 18 | FR-017 takedown/case/appeal, `PLDR_Rights` | Rights state machine source present; real document status/notifications unverified |
| 19 | FR-018 preservation, FR-019 accessibility metadata, `PLDR_Privacy`/REST | Source preservation/reader a11y present; backup/key escrow/OCR quality/device RTL tests pending |
| **20** | **NFR-001/002/007 and central File00/01/19/20/23/24/25/26, CF04 owner parity; `PLDR_Access::update_policy`** | **R39-D04:** when changing only `audience`, legacy stored `'true'/'on'/'yes'` flags were converted using `(int)` to 0, unintentionally removing user download/print/offline privileges; changed unchanged-flag preservation to `PLDR_Core::policy_flag(...) === 1` with fail-closed malformed legacy flags. Existing rollback/event path kept. Installed external owner parity unverified |

## Corrected source findings
- **R39-D01 — invalid calendar dates**: strict ISO UTC date/time (with optional valid explicit offset) rejects impossible calendar days, leap-year mistakes and impossible clock/offset fields, preserving UTC semantics even if PHP host default timezone differs.
- **R39-D02 — imported embargo safety**: read-time `can_access_edition` uses same calendar validator; invalid historical embargo audits and denies ordinary users rather than normalizing, with pre-existing manager exception.
- **R39-D03 — historic rights expiry safety**: same strict validator applies to imported `edition.rights_expires_at`; invalid expiry does not become later valid rights for regular readers.
- **R39-D04 — unrelated policy edit flag drift**: explicit unchanged legacy true/on/yes preserved as canonical 1 during policy version updates; false/off/no/unknown stays 0, no escalations; mutation version/outbox/token revocation semantics unchanged.

**Regression**: `tests/test-r39-rights-calendar-policy.php` executes the actual `PLDR_Access::update_policy`, `PLDR_Access::can_access_edition` and `PLDR_Core` with a WP database fixture. Includes 11 invalid dates/offsets at policy mutation, 5 valid date/UTC/offset normalizations under a non-UTC process timezone, 11 invalid persisted embargos, 11 invalid persisted rights-expiry strings, a legitimate public read, historical positive flags on unrelated audience update and invalid flags failing closed, with no write on invalid embargo. Wired into PHP 8.1/8.3/8.4 CI, on top of previous R28–R38 actual and static tests.

**Software package**: distinct repository candidate `1.1.0-rc.18`; source database/schema and integration contract `1.1.0` unchanged, no migration.

## External-owner and release gates not assumed
- Plan ZIP inner WordPress plugin folder `12-pdf-library-foundation` still differs from built source `pdf-library-foundation-12` — Founder-controlled staged upgrade/migration, rollback and no duplicate activation required before deployment.
- Exactly installed File12 plugin files/version/hash, DB tables/columns/rows, `wp_options`, migration state, runtime PHP logs/config, keyring, scanner/OCR/CF04, exact installed companion File00/01/05/06/16/17/19/20/21/23/24/25/26 versions are UNKNOWN.
- Actual WordPress/browser user roles/guardian/privacy consent, embargo timezones in live data, staged DB import/migration, report/appeal and other owner event consumers, RTL/a11y/low bandwidth, backups/decryption/restore, two fresh staging audit/fix rounds, Founder approval, controlled rollout and live re-test NOT PERFORMED.
- Green GitHub test is proof about a **specific source candidate**, not proof this code is on live.

## Five-field mandatory live-first release record
- **Repository HEAD:** Final exact R39 Draft PR #34 SHA and matching Actions run when complete. `main` is bootstrap `2b369ef2d609247896e1ab32118f1dc498407f51`.
- **Deployed Version:** UNVERIFIED.
- **DB Version:** UNVERIFIED (source target `1.1.0` only).
- **Migration State:** UNVERIFIED.
- **Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
