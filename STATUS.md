# File 12 — R37 source-level private deletion and reader permission truth (11 October 2026)

| Fact | Exact and limited evidence |
|---|---|
| Frozen parent | R36 Draft PR #31, `eeafd8b29189a13dd961a96a3bfa3cd80625c9da`, GitHub Actions #462 green |
| Divergent candidate | Independent R36 Draft PR #30 `8dd37ee8d08d9fcdbc3d5187da76b8f73c94cdfa`, also labelled rc.15; different source contained native private deletion correction. It is NOT auto-merged or deployed |
| Requirements / original docs | File12 v1.1 Future-24 amended; three central consolidated governing plans; File05/06/21/23/25 companion plans |
| R37 20 focused source reviews | `docs/TWENTY-ROUND-REVIEW-2026-10-11-R37.md`: twenty separate scoped requirement/source checks (not twenty human-complete code audits, staging, or Live E2E tests) |
| R37-D01 native private delete | `PLDR_Reading::delete_item` no longer misreports deleting 0 rows or deleting with invalid item/user ID; only `1 === $wpdb->delete` is success |
| R37-D02 native reader user-facing permission truth | `PLDR_Reader::document_html` JS config for download, print and offline checks stored rights through canonical `PLDR_Core::policy_flag`, denies legacy `false/off` |
| R37-D03 REST manifest permission truth | `PLDR_REST::reader_manifest` uses identical exact-1 parser for rights projections, not PHP `!empty()` |
| R37-D04 release ambiguity | Two different rc.15 Drafts are not a deployable common truth; R37's reconciled source is a distinct unique software `1.1.0-rc.16` candidate |
| Real source regression | `tests/test-r37-reader-permission-delete.php`: actual REST manifest + real Core rights parser for supported/legacy/invalid flags, native private deletion row-count + owner guard; JS reader boot config source invariant (not real browser rendering) |
| Other source QA | Retains R36 76 real REST/privacy, R35 61 native private reading, R34 66 access/token, R33 109 rights flags, R31 50 ingest, R32 5 actual PDF, R28 20×(53 FR/NFR/FUT +12 owner-adapter checks)=1300 static matches plus 20 invariants, earlier crypto/range/rights/search/future tests |
| Source software version | `1.1.0-rc.16`; DB/schema version `1.1.0` and external interface contract `1.1.0` unchanged, no new migration |
| Final required QA evidence | Exact LAST PR #32 commit must have matching SUCCESS Actions; matching inner install ZIP SHA and outer artifact digest separate |
| Plan vs source ZIP inner root | Written plan `12-pdf-library-foundation` vs source/build `pdf-library-foundation-12`, Founder change-control/staged compatibility upgrade/rollback required |
| Exact installed File12 and companion owner versions | UNVERIFIED: File00/01/05/06/16/17/19/20/21/23/24/25/26 and CF04 |
| Real live/staging/backup/keys/DB/config/logs | UNVERIFIED; no approval/merge/deploy/live re-test |

## Live-first 5-field evidence

**Repository HEAD:** latest R37 Draft PR #32 exact final SHA from matched CI; GitHub `main` unchanged bootstrap `2b369ef2d609247896e1ab32118f1dc498407f51`.  
**Deployed Version:** UNVERIFIED.  
**DB Version:** UNVERIFIED (source declares 1.1.0 only).  
**Migration State:** UNVERIFIED.  
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
