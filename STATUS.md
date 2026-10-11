# File 12 — R38 source-only read-query/citation candidate, 11 October 2026

| Requirement/source fact | Verified evidence |
|---|---|
| Frozen base | R37 Draft PR #32 `8f7f140c3686f228ef4c6656cc3b4d7e27b2912a`, exact Actions #468 green |
| Governing materials | Uploaded amended File12 v1.1 Future24, three consolidated central governing plans, File05/06/21/23/25 and source-side File00/01/16/17/19/20/24/26/CF04 owner boundaries |
| R38 20 different focused inspections | `docs/TWENTY-ROUND-REVIEW-2026-10-11-R38.md`; manual source/plan foci not 20 full end-to-end deployed trials |
| R38-D01 | GET `PLDR_REST::reading_items` malformed edition_id/limit/offset/cursor previously coerced before private authorization and DB; now strict fail-closed query shape |
| R38-D02 | GET `PLDR_REST::ocr_search` array/bool/cursor/limit/edition values previously coerced; same early query-shape validation |
| R38-D03 | GET `PLDR_REST::citation` negative/float/nested page could become wrong page; unsupported Chicago/RIS/BibTeX/CSL style could return misnamed Sabri citation; strictly deny malformed and unsupported basic styles |
| R38-D04 | `PLDR_Reader::citation` now includes immutable edition ID in actual Sabri/APA/MLA outputs. Canonical document URI is unchanged; edition-specific text no longer ambiguous when labels repeat |
| New actual executable fixture | `tests/test-r38-rest-read-shapes-citation.php`: real Reading/OCR/Citation REST methods; real citation formatter and two same-document editions, negative cases pre-DB/auth, legitimate requests |
| Existing source QA | R37 real reader permissions/private delete 153 +3 JS source checks; R36 76 real REST/rights; R35 61 native notes; R34 66 grants; R33 109 flags; R31 50 ingest; R32 5 actual physical PDF; R28 20×(53 FR/NFR/FUT+12 source-side adapters)=1300 static presence checks+20 invariants, prior crypto/range/search/future suites |
| Software candidate | `1.1.0-rc.17`; source DB/schema/interface contract `1.1.0` unchanged; no new migrations |
| Final evidence gate | Latest last R38 PR #33 immutable HEAD and matching SUCCESS PHP 8.1/8.3/8.4/package with exact inner ZIP checksum; earlier green is not final proof |
| Plugin root release blocker | Written master plan `12-pdf-library-foundation` vs build `pdf-library-foundation-12`, requires Founder-approved compatibility plan/staging/backup/rollback, not silently modified |
| Actual installed counterpart File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 | UNVERIFIED |
| Real Hostinger file checksum/version, physical DB/wp_options/migrations, runtime logs/keys, browser/staging/rollback | UNVERIFIED; no Founder-authorized merge or deploy |

## Five mandatory separate live-first facts

**Repository HEAD:** R38 Draft PR #33 last exact immutable SHA after matching final CI. GitHub `main` remains bootstrap README `2b369ef2d609247896e1ab32118f1dc498407f51`.  
**Deployed Version:** UNVERIFIED.  
**DB Version:** UNVERIFIED, source target 1.1.0 only.  
**Migration State:** UNVERIFIED.  
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
