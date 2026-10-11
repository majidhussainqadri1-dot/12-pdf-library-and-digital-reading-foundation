# File12 — R40, Source-Only Ingest Rights & Catalog Numeric Integrity (11 Oct 2026)

| Source verification dimension | Grounded evidence |
|---|---|
| Frozen source baseline | R39 Draft PR#34 exact green `c572bef9de1d6aa71c370a9a73e35235b9878c75`; Actions #480 SUCCESS |
| Governing plans | Original uploaded amended File12 v1.1 Future24 **19 FR/10 NFR/24 FUT source requirements**, consolidated 3 central plans, uploaded File05/06/21/23/25 and File00/01/16/17/19/20/24/26/CF04 source-side adapter boundaries |
| Twenty scoped new checks | `docs/TWENTY-ROUND-REVIEW-2026-10-11-R40.md`, 20 distinct requirement/path presence plus risk inspections, NOT 20 exhaustive complete code/Hostinger E2E reviews |
| R40-D01 fixed | New PDF-ingest `rights_expires_at` and `embargo_until` no longer accept malformed Feb31 or host-timezone-dependent dates; reuse canonical R39 rights validator already used on access read and rights policy mutation |
| R40-D02 fixed | Pending edition submitted with omitted grant/embargo fields no longer falsely signals a change of live policy. Stored current policy is canonical source; invalid legacy grants fail closed. **Source invariant only** until real WP HTTP-upload staging acceptance |
| R40-D03 fixed | Catalog page/per_page invalid numeric inputs rejected pre-DB instead of `absint` converting negative, decimal, zero to another page/size |
| Executable R40 test | `tests/test-r40-ingest-date-catalog-pagination.php`: actual `PLDR_Ingest::ingest`, `PLDR_Search::search`, `PLDR_Access::strict_rights_timestamp` under isolated mock WP DB; real positive and negative inputs, no-DB-on-invalid, plus two specifically marked source-only pending branch guards |
| Previous executable/source quality | R39 real rights calendar 70; R38 GET/citation 264; R37 real reader/deletion 153 + 3 JS source invariants; R36 76 real REST/rights; R35 61 native private notes; R34 66 token/access; R33 109 flags; R31 50 ingest; R32 5 PDF physical. R28 repeated 20×(53 governed IDs+12 source adapter anchors)=1300 static checks +20 invariants. Does not certify live/staging completeness |
| New source software candidate | `1.1.0-rc.19`, DB schema and integration contract remain `1.1.0`. No migrations or live changes |
| Plugin install root | Plan `12-pdf-library-foundation` vs built source `pdf-library-foundation-12`: compatibility and rollback controlled by Founder; unverified installed root |
| Actual File12 Hostinger installed code/version/hash, schema/options/data/migrations/logs/keys, actual installed owner module versions | UNVERIFIED |
| Production release acceptance | Browser+RTL/accessibility, real scanner/OCR, roles/access/timezones, File26 search, backup/rollback/recovery, two real staging reviews, Founder sign-off, merge/deploy and live re-test NOT PERFORMED |

## Live-First FIVE independent fields
**Repository HEAD:** R40 Draft PR#35 final exact SHA and same-sha GitHub Actions success must be checked. `main` still bootstrap `2b369ef2d609247896e1ab32118f1dc498407f51`.
**Deployed Version:** UNVERIFIED.
**DB Version:** UNVERIFIED (source declares contract/schema `1.1.0` only).
**Migration State:** UNVERIFIED.
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
