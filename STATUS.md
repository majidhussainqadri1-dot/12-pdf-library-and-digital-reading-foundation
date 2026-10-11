# File 12 — R36 strict REST privacy and mutation boundary candidate (11 October 2026)

| Verified item | Exact source/plan evidence |
|---|---|
| Frozen source basis | R35 Draft PR #28 exact HEAD `90dd4768ded28842b93c3b953a48e0a747f32890` (Actions #451 green); GitHub main bootstrap-only `2b369ef2d609247896e1ab32118f1dc498407f51` |
| Governing materials | Actual uploaded File12 amended v1.1 Future-24; three central consolidated master plans; companion File05/06/21/23/25, source-side owner contracts |
| Twenty scoped manual checks | `docs/TWENTY-ROUND-REVIEW-2026-10-11-R36.md`: twenty different source-plan focal checks, *not* twenty exhaustive WordPress or live checks |
| R36-D01 confirmed & fixed | Paginated `PLDR_REST::reading_items` bypassed R35 private stored tag validation. Both direct and REST endpoints now use shared `PLDR_Reading::parse_stored_tags` for historic JSON map/nested/oversized malformed rows |
| R36-D02 confirmed & fixed | REST `save_progress` coerced nested/bool/noninteger page/edition IDs and malformed version marker before current private state check; strict early mutation field type guard |
| R36-D03 confirmed & fixed | REST rights report/decision/appeal silently cast nested evidence/reasons/notes/version; strict scalar/nonnegative decimal/evidence-array preflight before rights DB reads, event/outbox or idempotency |
| Other guarded mutation routes | Private add/delete reading item ID and expected version protected from shape coercion |
| Actual executable new test | `tests/test-r36-rest-privacy-mutation.php`: real paged `PLDR_REST::reading_items` malformed/valid tags, real malformed progress/rights/reading-item REST methods and no DB effects on rejected inputs |
| Retained complete source QA | R35 native Reading 61 actual cases, R34 Access 66, R33 rights 109, R31 Ingest 50, R32 PDF validator 5; R28 20×(53 File12 FR/NFR/FUT IDs +12 source adapters)=1300 static presence asserts and 20 invariants, historic crypto/range/rights/search suites |
| Software candidate | `1.1.0-rc.15`, **DB/schema/interface source contract still `1.1.0`**, no new DB migration |
| Exact final source CI | Only final immutable R36 PR #31 HEAD counts; package inner ZIP hash needs match same run |
| Install root mismatch | Plan `12-pdf-library-foundation`, source ZIP inner `pdf-library-foundation-12`; Founder-controlled compatibility/migration/rollback unresolved |
| Native external owner installs | UNVERIFIED File00/01/05/06/16/17/19/20/21/23/24/25/26 and CF04, including claims/entitlements/cache/projections |
| Staging/Hostinger/browser/provider/backup/rollback | NOT VERIFIED; no Founder authorization to merge/deploy |

## Mandatory live-first five separate truths

**Repository HEAD:** R36 Draft PR #31 exact final SHA; `main` `2b369ef2d609247896e1ab32118f1dc498407f51` bootstrap only.  
**Deployed Version:** UNVERIFIED.  
**DB Version:** UNVERIFIED; repository source target 1.1.0 only.  
**Migration State:** UNVERIFIED.  
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
