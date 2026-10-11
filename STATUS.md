# File 12 — R31 source candidate 1.1.0-rc.10 (11 October 2026)

| Independent evidence | Exact fact |
|---|---|
| Governing authority | Attached File12 v1.1 amended Future-24, Three Consolidated Central Plans, companion File05/06/21/23/25, and File12/native owner interface boundaries |
| Main repository HEAD | `2b369ef2d609247896e1ab32118f1dc498407f51`; bootstrap README only |
| Reviewed source baseline | R30 Draft PR #20 exact green SHA `715fa30f1f63bbe34aa05eb148a6434f355e5145` |
| R31 audit | `docs/TWENTY-ROUND-REVIEW-2026-10-11-R31.md`, twenty distinct focused source-plan inspections, not twenty full live/staging/test-suite runs |
| Confirmed R31 source defects | D01 invalid nested scalar document metadata could be cast to `Array`; D02 malformed subjects/collections lists incl nested JSON were not rejected; D03 invalid nested PDF/cover upload shape could reach `is_uploaded_file` |
| R31 code corrections | `PLDR_Ingest::ingest` validates scalar metadata, privacy/rights flags, bounded list/JSON structure and upload fields before any file/DB side effects |
| New actual-code test | `tests/test-r31-ingest-input-shapes.php`: 40+ invalid/valid real PHP ingest-entrypoint cases with DB side effects forbidden |
| Retained tests | 20 × full 53-FR/NFR/Future + 12 cross-owner static source matrix, real R29 catalog fixtures, R30 policy regression, R1–R28 crypto/range/tamper and historical suites |
| Candidate software ZIP | `1.1.0-rc.10` — not deployed |
| DB/source schema + contract | `1.1.0` / `1.1.0`, unchanged |
| Exact final R31 CI | **Must be checked only at final unchanged R31 SHA after this status/review commit** |
| Staging + real consumers | UNVERIFIED; companion installed versions/roles/providers unverified |
| Source-vs-plan package folder | Plan `12-pdf-library-foundation`; historical code `pdf-library-foundation-12`; Founder-approved staging migration decision pending |
| Backup, restoration, offline/RTL/rights/live-role acceptance | NOT VERIFIED |
| Production deploy/live parity | NOT DONE |

## Five-field live-first truth

**Repository HEAD:** R31 exact final SHA in Draft PR after review; `main` initial README `2b369ef...`.  
**Deployed Version:** Unverified.  
**DB Version:** Unverified (source target `1.1.0` only).  
**Migration State:** Unverified.  
**Live Verification Status:** Not performed.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
