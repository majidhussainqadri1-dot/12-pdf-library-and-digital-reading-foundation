# File 12 — R29 Rights-Aware Policy Corrective Candidate (2026-10-11)

| Gate | Evidenced status |
|---|---|
| Governing requirements | File 12 v1.1 Future-24 amended; consolidated central governing plan; companion File 05/06/21/23/25; Files 00/01/16/17/19/20/24/26 adapter boundaries |
| Baseline exact HEAD | PR #16 `cac343ecae14b8cff08be56c83cc6d32761426d3`; PR #17 is a divergent independently green branch, not blindly merged |
| Audit and scope | `docs/TWENTY-ROUND-REVIEW-2026-10-11-R29.md`: 20 focused source/contract checks; inherited 20 repetitions of all 53 IDs + 12 adapter static anchors |
| Confirmed source code defects | R29-D01 invalid persisted embargo failed open; R29-D02 malformed audience could be accepted by entitlement callback |
| Corrective code | `PLDR_Access::can_access_edition` fail-closed with audit, preserving valid published/public and named manager grants |
| Executable regression | `tests/test-r29-policy-fail-closed.php` (23 assertions), plus retained R26/R27/R28 suites and 20-pass static matrix |
| Candidate software | `1.1.0-rc.8` distinct from both prior rc.7 candidate branches |
| Source schema / integration contract | `1.1.0` / `1.1.0`: no new SQL migration |
| Exact final HEAD CI | Must be verified after final commit; historical green not transferable |
| Package folder naming | Plan `12-pdf-library-foundation`; repo `pdf-library-foundation-12`. Controlled Founder decision still outstanding |
| Deployed Version | Unverified |
| DB Version | Unverified (repository contract only: `1.1.0`) |
| Migration State | Unverified |
| Live Verification Status | Not performed |
| Staging, rollback, Founder acceptance | Not verified; not authorized to claim resolved or deploy |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
