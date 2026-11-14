# File12 V12.3 — R68 immutable acceptance ledger integrity (2026-11-14)

**Status: DRAFT SOURCE-ONLY CORRECTION. NO PRODUCTION DEPLOY.**

## Frozen source and reproducible cause

R68 is scoped to the *non-deploying* `scripts/file12-release-no-go.php` evidence-preflight from the V12.3 review branch exact SHA `59558199d643d52485e237c243ee2117e2c38266` (PR #58). It makes no change to the WordPress PDF plugin, site database, physical schema, book contents, rights, keys, or search connector.

**Source defect:** The R67 gate counted only rows actually found in each CSV, then interpreted `0 of 0 evidence gates not proved` as a nonblocking condition. When all surviving rows had synthetic `PASS` receipts, removing required rows, leaving an empty register, or duplicating an ID could yield `SOURCE_LEDGER_GO` in either predeploy or postdeploy mode. The separate matrix-structure CI test did not make this direct release CLI check safe against altered files.

The failing test `tests/test-r68-release-ledger-identity.php` deliberately makes only **synthetic temporary copies** of the four ledgers and approval-state JSON. It exercises five tampering scenarios in both release modes (10 negative cases): remove R57 T-01, duplicate a R57 T-ID, remove V12.3 one criterion, duplicate a V12.3 criterion ID, and strip all ten CF04 rows. It never submits real reviewer credentials, production log evidence, patient records or copyright content.

## Exact RED → fix → GREEN

| Phase | SHA | CI | Verified result |
|---|---|---|---|
| RED first | `60c98293c24674cc8fcae8b896c8f56503622d4a` | GitHub Actions `43612637361` | PHP8.1/8.3/8.4 fail **R68 tampering test**, frontend passes |
| Minimal fix | `a0ba94b60e084b483c8d2993f010c8359f0f49f7` | GitHub Actions `43612771846` | 4/4 GREEN including all retained R60–R67 source controls, PHP8.1/8.3/8.4 and deterministic plugin ZIP |

Fix enforces immutable, distinct, governed identity sets **before** release ledger row acceptance:
- R57 T-01..T-39 exactly **39** staging test IDs;
- R58 F12-FR-001..019, NFR-001..010, FUT-001..024 and F12-CEN-01 exactly **54** requirement IDs;
- R59 STO-01..STO-10 exactly **10** CF04 security-control IDs;
- V12.3 54 original IDs + M01..M12 + QA-15..QA-24 + AJ-14 exactly **77** distinct mapped IDs.

An empty, truncated, extra, malformed, substituted or duplicate ID now emits a specific blocking `invalid immutable identity/cardinality` reason. The approved universe totals **180 evidence rows**, which are overlapping verification gates—not 180 separate product features or proof that runtime tests passed.

## Scope and limits

- The current original register statuses remain **39/39**, **54/54**, **10/10**, **77/77** stage and V12.3 77/77 live **UNVERIFIED**.
- The CI tests verify only the source gate logic and synthetically constructed status inputs. Real source receipt IDs and reviewer identity are still subject to authorized independent human review.
- Earlier R64/R65 source flaws were corrected in PR #57; R66/R67 source flaws were corrected in PR #58. R68 makes **one newly proven** release-governance source defect, now fixed in Draft PR #59.
- Staging-retested **0**; production re-tested **0**; installed version/DB/migration state **UNVERIFIED**; production result **NO_GO**.
- File26 PDF/Book Pack owner contract, indexed revocation/tombstone acknowledgment, CF04 scanner/storage/restore, licensed real source books, two independent real-host reviews and Founder approval remain release blockers.

## Mandatory next gate

Freeze active Hostinger File12 folder/build and physical `wp_options` + schema/migration state using authorized redacted read-only collection. If installed code and candidate diverge, perform Deployment-Parity Audit before attempting any live incident patch. Only after real source/rights/consumer evidence may stage and production be evaluated. A true `SOURCE_LEDGER_GO` is advisory, **not** a deployer or an actual release authorization.

**Five truths:** Repository main `2b369ef2d609247896e1ab32118f1dc498407f51`; PR59 source code `a0ba94b60e084b483c8d2993f010c8359f0f49f7`; Deployed Version **UNVERIFIED**; DB Version **UNVERIFIED**; Migration State **UNVERIFIED**; Live Verification **NOT PERFORMED**.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
