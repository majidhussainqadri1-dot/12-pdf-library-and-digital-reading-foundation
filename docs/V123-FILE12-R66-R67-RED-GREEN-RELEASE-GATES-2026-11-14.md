# File12 V12.3 — R66/R67 source-level Red/Green release integrity (2026-11-14)

**Scope:** only the `scripts/file12-release-no-go.php` *non-deploying* release-evidence preflight, V12.3 CSV schema, CI fixtures and documentation. **No WordPress PHP plugin source, installed Hostinger package, production option, database, public right or real user account was changed.**

## Exact source chain

| Stage | Exact SHA | Actions run | Evidence result |
|---|---|---|---|
| Original tested source candidate | `d7c0d9458402019a3c46c73b3a7563329e9be8f7` (PR #57) | `43379718214` | 4/4 GREEN, source only |
| V12.3 audit matrix + baseline test | `f3990fe955547e535a3f3af8ac53ad1e6e1b86ea` (PR #58, intermediate) | `43610885668` | 4/4 GREEN, governance matrix structure only |
| **R66 RED reproduction** | `dde97622138df3ae6f1351e167aa9f13ef69bcb0` | `43611031814` | PHP8.1/8.3/8.4 failed **R66 test**, frontend succeeded |
| **R66 source fix** | `18fdae028586b3d8abf5bae262279eb9528682e1` | `43611173375` | 4/4 GREEN |
| **R67 RED reproduction** | `e7c6f14e2d80f59128ee9bf660da52a04b98e23b` | `43611832303` | PHP8.1/8.3/8.4 failed **R67 test**, frontend succeeded |
| **R67 source fix** | `6d97233ddebef100c87df384f265cd812040cb48` | `43612004615` | 4/4 GREEN |

**R66 root cause (source-only)**: The existing non-deploying approval gate required original V12.1 39/54/10 CSV receipts and approvals, but did not examine the V12.3 77-row crosswalk containing M01–M12, QA15–24, AJ-14. A synthetic fixture set old ledgers and approvals to `PASS`, while V12.3 remained `UNVERIFIED`; the old code could erroneously return `SOURCE_LEDGER_GO`. The RED-first test `tests/test-r66-v123-release-missing-proof.php` demonstrated the omitted gate. Fix: the release script now counts all **39+54+10+77 = 180 evidence rows**, requiring the V12.3 rows rather than treating CI source success as modern Web acceptance.

**R67 root cause (source-only)**: The initial new V12.3 matrix used a single `environment`, `observed`, `evidence_id`, `reviewer` pair for both staging and live. In `postdeploy` mode, setting `live_status=PASS` could reuse a staging-only receipt and its observed environment. The RED-first test `tests/test-r67-live-stage-disjoint-evidence.php` proved staging-only mock values were accepted. Fix: four additional columns `live_environment`, `live_observed`, `live_evidence_id`, and `live_reviewer` per row, with `postdeploy` requiring **both** accepted staging proof and a separately observed production/LIVE environment, distinct nonempty evidence ID, current defect retest PASS, and explicit production version/rollback/approval fields in the original R60 state.

## Synthetic-fix boundaries

- The RED fixtures use **fictional reviewer strings, artificial observed labels and dummy 40/64-character SHAs**, only inside temporary directories. They are deliberately not accepted as real staging, deployment or authorized independent review.
- The amended source release script does not make deployment calls, authenticate arbitrary identity labels, cryptographically verify external receipts, verify real host topology, or check physical database version. Its success message remains `SOURCE_LEDGER_GO` **requiring a human independent evidence review**.
- The exact V12.3 matrix still contains **77/77 staging UNVERIFIED and 77/77 live UNVERIFIED**. Original **39/39 staging**, **54/54 runtime**, **10/10 CF04** remain unverified. These are separate *evidence rows*, not 180 bugs or 180 actual product features.
- Hostinger administrative access remains blocked (Jetpack disconnected; WPVibe HTTP 530/no usable authenticated connection). File26 book_pack/rights tombstone and CF04 owner acceptance remain STOP-SHIP.
- No claim of `Fixed on Live`, `Staging accepted`, `Founder approved`, `production resolved`, or `zero vulnerabilities` follows from 4/4 source CI.

## Defect accounting at this source snapshot

- **New distinct defects found during V12.3 audit:** **2** (R66 and R67).
- **New source corrections:** **2** (each had independent RED reproduction and GREEN retest).
- **Staging-retested/live-resolved:** **0 / 0**.
- **Preexisting draft-only corrected source defects:** **2** (R64 and R65).
- **Known release blockers:** **10 categories**, unchanged.
- **Next mandatory gate:** authorized read-only Hostinger exact-deployed and physical DB/migration freeze; then owner approval of File26 and CF04, complete real stage and independent review.

**Five truth fields:** Repository main HEAD `2b369ef2d609247896e1ab32118f1dc498407f51`; audited rc.43 source baseline `d7c0d9458402019a3c46c73b3a7563329e9be8f7`; Deployed Version **UNVERIFIED**; DB Version **UNVERIFIED**; Migration State **UNVERIFIED**; Live Verification **NOT PERFORMED**.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
