# File 12 — R32 policy/consent flags corrective source candidate (2026-10-11)

| Evidence gate | Verifiable status |
|---|---|
| Governing plans | File 12 v1.1 Future-24 amended, 3 central consolidated master plans, File 05/06/21/23/25 and native File 00/01/16/17/19/20/24/26 interfaces |
| Exact source baseline | R31 Draft PR #21 HEAD `55005aef64841ab2f23254051f1db44e14594163`, previously green only for its own SHA |
| Twenty-round audit | `docs/TWENTY-ROUND-REVIEW-2026-10-11-R32.md`; twenty separate focused source/contract checks, not 20 live/staging tests |
| Code defect | R32-01 PHP `empty()` interpreted explicit 'false' flags as enabled in PDF ingest and policy updates, also affected patient-case and duplicate-scan confirmations |
| Corrective source | `PLDR_Core::policy_flag` used by `PLDR_Ingest::ingest` and `PLDR_Access::update_policy`; malformed values reject before mutation |
| New PHP regression | `tests/test-r32-policy-flags.php`, actual Core/Ingest/Access class with private DB fixture and positive/negative rights persistence assertions |
| Preserved prior source | R26 explicit anonymous rights, R27/R28 pregrants/object binding, R29 catalog, R30 malformed embargo/audience, R31 ingest shapes |
| Repeated static requirement matrix | R28: 20 × (53 governing IDs + 12 source companion adapters) plus supplemental checks |
| Candidate plugin/package | `1.1.0-rc.11`, new source identity, not deployed |
| Repository DB / integration contract | `1.1.0` / `1.1.0`, no new schema migration |
| R32 exact final HEAD CI | Await **new workflow result on final changed HEAD**; prior CI cannot substitute |
| WordPress plugin folder conflict | Plan ZIP root `12-pdf-library-foundation` vs candidate `pdf-library-foundation-12`: founder-controlled staged change/rollback required |
| Hostinger deployed version/checksum | UNVERIFIED |
| Physical DB, options and migration state | UNVERIFIED |
| Real companion providers, keys, browser/RTL/a11y/offline, rollback | UNVERIFIED |
| Founder release approval, production deploy, live re-test | NOT PERFORMED |

**Repository HEAD:** confirm final R32 SHA; GitHub main remains separately scoped.  
**Deployed Version:** unverified.  
**DB Version:** unverified.  
**Migration State:** unverified.  
**Live Verification Status:** not performed.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
