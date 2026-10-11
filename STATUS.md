# File12 — R39 strict rights dates / imported-policy flags (11 October 2026)

| Evidence category | Fact |
|---|---|
| Frozen source baseline | R38 Draft PR #33 exact HEAD `186095b1febe3d795798c5bcdb7a2f09f93fa380`, Actions #474 SUCCESS |
| Governing master plans | Actual uploaded File12 amended v1.1 Future24, three consolidated central plans, File05/06/21/23/25 companions |
| R39 plan/source reviews | `docs/TWENTY-ROUND-REVIEW-2026-10-11-R39.md`, 20 separately scoped source comparisons, not 20 exhaustive WordPress/staging/live E2E runs |
| R39-D01 (fixed) | Rights embargo mutation: strict ISO calendar/clock/timezone rejects impossible February 31 and clock/offset overflow before policy write |
| R39-D02 (fixed) | Persisted imported malformed embargo: real reader authorization audits/fails closed rather than `strtotime` silently rolling date |
| R39-D03 (fixed) | Imported edition `rights_expires_at`: real access-time parser denies invalid calendar expiry to noncurators |
| R39-D04 (fixed) | Versioning an unrelated access-policy field no longer converts historic saved `'true'/'on'/'yes'` download/print/offline to 0; malformed legacy rights are not enabled |
| R39 actual fixture | `tests/test-r39-rights-calendar-policy.php` uses actual `PLDR_Access::update_policy/can_access_edition`, `PLDR_Core`, isolated fake WP/DB, invalid real calendar dates, explicit timezone, legacy data and flag preservation; PHP 8.1/8.3/8.4 matrix |
| Prior actual QA | R38 264 GET/citations, R37 153 reader rights+delete +3 source UI invariants, R36 76 REST mutations, R35 61 private notes, R34 66 access/grants, R33 109 policy, R31 50 ingest, R32 5 true PDF, R28 20×(53 FR/NFR/FUT +12 source adapters)=1300 static anchors +20 invariants and older crypto/search/range tests |
| Software candidate | `1.1.0-rc.18`, source database/schema/interface contract still `1.1.0` (no R39 schema migration) |
| Exact QA | Only last immutable R39 PR #34 SHA with matching completed Actions and inner ZIP digest is final evidence |
| Deploy naming conflict | Plan plugin root `12-pdf-library-foundation`; source/build root `pdf-library-foundation-12`. No silent folder change without Founder-approved backup, migration and rollback |
| Companion native deployments | File00/01/05/06/16/17/19/20/21/23/24/25/26 and CF04: UNVERIFIED |
| Hostinger physical PHP/JS versions/checksums + DB/options/rows/schema/migration/logs/keys/consumers, WordPress staging/browser/a11y/backups/rollback | UNVERIFIED, NOT TESTED OR DEPLOYED |

## Five live-first reality fields

**Repository HEAD:** R39 Draft PR #34 last exact SHA; GitHub `main` still bootstrap `2b369ef2d609247896e1ab32118f1dc498407f51`.  
**Deployed Version:** UNVERIFIED.  
**DB Version:** UNVERIFIED (source target 1.1.0 only).  
**Migration State:** UNVERIFIED.  
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
