# File 12 — R32 PDF Integrity, Ingest Recovery and Twenty-Focus Review (11 October 2026)

| Gate | Verified source or operational status |
|---|---|
| Governing documents | Uploaded amended File12 v1.1 Future-24, consolidated Three Central Plans; companion File05/06/21/23/25 master plans; native other owners in shared central rule |
| Exact inspected parent | R31 Draft PR #21 `55005aef64841ab2f23254051f1db44e14594163`; prior CI #423 green |
| New review record | `docs/TWENTY-ROUND-REVIEW-2026-10-11-R32.md`: 20 distinct scoped plan-to-source code review foci; not 20 real Hostinger tests |
| Proven new source defects | R32-D01 PDF request byte size did not match actual physical file; R32-D02 no **immediate** encrypt-failure temporary file cleanup (pre-existing shutdown safety net acknowledged); R32-D03 explicitly failed/partial/non-HTTP cover uploads could be silently omitted or caught only after primary commit |
| Corrective source | `PLDR_Ingest::validate_pdf` physical byte count, `PLDR_Ingest::ingest` encrypt false/exception eager cleanup and explicit supplied cover precommit gate |
| New tests | `tests/test-r32-pdf-physical-integrity.php`: actual valid PDF, claimed short/large size, changed PDF/EOF content; source invariant for precommit and eager cleanup; `tests/test-r31-ingest-input-shapes.php`: 3 new real ingestion cover rejection cases |
| Retained regression evidence | R28 20×(53 requirement IDs+12 source-side adapters) = 1,300 static source anchors, R29/R30/R31 real PHP fixtures, prior crypto/range/tamper/security tests |
| Software/package candidate | `1.1.0-rc.11` — R32 Draft source only |
| Source DB schema & interface contract | `1.1.0` / `1.1.0` — no new migration |
| Exact final CI | Verify only the last unchanged R32 PR SHA; earlier green is not transferable |
| Main branch | `2b369ef2d609247896e1ab32118f1dc498407f51` — README bootstrap only, **not** production truth |
| Plan/plugin internal ZIP folder mismatch | Plan `12-pdf-library-foundation` vs source `pdf-library-foundation-12`; Founder change-control/upgrade/rollback still required |
| Companion installed versions/contracts | UNVERIFIED for File00/01/05/06/16/17/19/20/21/23/24/25/26 and CF04 |
| Real staging and live upload rights/roles, physical DB, keys and backups | UNVERIFIED |
| Founder acceptance, deployment and live parity | NOT DONE |

### Five separate production truths

- **Repository HEAD:** R32 latest exact SHA after final docs commit (main `2b369ef...`).
- **Deployed Version:** UNVERIFIED.
- **DB Version:** UNVERIFIED; source candidate 1.1.0 only.
- **Migration State:** UNVERIFIED.
- **Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
