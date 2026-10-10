# File 12 — R27 source candidate status (10 October 2026)

| Independent gate | Status and exact evidence |
|---|---|
| Governing specs | Consolidated central plan + File 12 v1.1 Future-24 amended + companion File 05/06/21/23/25 plans |
| `main` truth | `2b369ef2d609247896e1ab32118f1dc498407f51`: bootstrap README only |
| Exact inspected source baseline | R26 PR #13 `510754c9cb92b39cb43ff4ff7a0e4430e48a713e` |
| Competing R26 branch | PR #12 `d8b37264d45583304805bfea97840dc6d510a102`, still unmerged; not presumed identical |
| R27 review record | `docs/TWENTY-ROUND-REVIEW-2026-10-10-R27.md`: 20 distinct focused source-plan passes (not twenty live runs) |
| Confirmed code defects | R27-D01 reader manifest creates signed grants before later DB/preflight failures; R27-D02 IIIF creates preview grants before policy DB check |
| Corrections | Move entire reader-manifest entitlement/policy/reading/a11y/thumbnail preflight before first grant; move IIIF policy preflight before first preview grant |
| Retained regression | `tests/test-twenty-round-review-r27.php`: 20 owner/feature source-area checks and signed-grant sequencing tests |
| Candidate software/plugin | `1.1.0-rc.6` |
| Candidate DB schema / integration contract | `1.1.0` / `1.1.0` (no migration version change in R27) |
| Current exact R27 CI result | **Unverified until GitHub Actions completes on final unchanged R27 HEAD** |
| Packaging conflict | Plan names ZIP internal top-level folder `12-pdf-library-foundation`; longstanding candidate actually uses `pdf-library-foundation-12`. Controlled Founder change request/compatibility migration needed before changing |
| Staging deployed code/DB/migration | Unverified; repository source/CI is NOT deployed truth |
| Backup/restore/browser/accessibility/offline/provider/Founder acceptance | Not independently evidenced |
| Live verification | Not performed |

## Source and live are distinct

Do not merge, relabel as production ready, deploy or claim defect resolved on live until: final exact-head QA and package checksum → Hostinger staging artifact/DB/roles/rights/provider/offline/RTL tests → backup/restore/rollback → Founder approval → production deploy → live test and parity proof.

**Repository HEAD:** See exact R27 branch commit. **Deployed Version:** unverified. **DB Version:** unverified. **Migration State:** unverified. **Live Verification Status:** not verified.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
