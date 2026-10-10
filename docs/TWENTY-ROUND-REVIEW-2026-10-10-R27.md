# File 12 — R27 fresh twenty-area code-to-plan review — 10 October 2026

## Evidence freeze and authority
- Exact reviewed baseline: `510754c9cb92b39cb43ff4ff7a0e4430e48a713e` (R26 rc.5 branch; PR #13 Draft).
- Distinct competing R26 candidate: `d8b37264d45583304805bfea97840dc6d510a102` (PR #12 Draft; not silently assumed identical).
- Governing written evidence: supplied File 12 v1.1 Future-24 amended (11 August), *Three Central Plans Consolidated Governing Master Plan*, and supplied File 05, 06, 21, 23, 25 companion plans; File 12 canonical charter and F12-FR-001..019, F12-NFR-001..010, F12-FUT-001..024.
- This exercise is **20 separate focused source-domain/contract review passes**, not twenty end-to-end WordPress runtime/staging tests nor 20 independent full-code manual audits. Presence checks alone are not functional acceptance.
- All twenty inspection findings were frozen before the product changes that follow; no retroactive invention of green staging proof.

## Closed inspection register
| Round | Review focus, source evidence | Finding at freeze |
|---:|---|---|
| 1 | Domain ownership — `PLDR_Core`, schema, rights module | Static owner code present; actual other repositories not runtime-tested |
| 2 | Document taxonomy — `PLDR_Core::DOCUMENT_TYPES/CATEGORIES` | Static 16+16 registers present |
| 3 | Bibliographic DTO and edition schema | Static code present |
| 4 | Genuine PDF ingest/malware/rights gates | Static code present; malware/OCR real provider unverified |
| 5 | Rights, takedown, appeal, revoke | Static code present; live expiry workflow unverified |
| 6 | Authenticated cryptography and range | Static implementation and prior R26 green crypto test evidence |
| 7 | Private object path and destruction | Static code present; live storage path/version unverified |
| 8 | Anonymous signed bearer identity | Explicit negative sentinel and executable R26 regression present |
| 9 | REST object/IDOR/idempotency | Source checks present; real WP roles unverified |
| 10 | Reader manifest preconditions, F12-FR-009/010 | **DEFECT R27-D01**: `reader_manifest` issued PDF read grant and preview grants **before** later policy/reading-state/accessibility DB checks. If such a later read failed, the request could return error after creating bearer grants without delivering them in the response |
| 11 | Metadata catalog + OCR signed continuation | Static owner filtering/cursors present; live pagination unverified |
| 12 | Private progress, bookmarks, retention | Static mechanisms present; real-user erasure/legal-hold unverified |
| 13 | OCR corrections and derived layer | Static corrected-text layer present |
| 14 | Future-24 requirement IDs and REST registrations | Static feature presence and contracts present; 24 browser journeys unverified |
| 15 | Encrypted offline and reconnect entitlement | Code present; browser/local storage reauthorization unverified |
| 16 | File 05/06 knowledge, File 16 corpus + File 17 rooms | Versioned companion/filter boundaries present; counterpart integrations unverified |
| 17 | IIIF manifest and owner-rights bound grants | **DEFECT R27-D02**: `PLDR_Future_IIIF::manifest` issued preview grants before a later access-policy DB read; on policy read failure an error was returned after partial grant creation |
| 18 | Accessibility and independent human verification | Static code present; actual screen-reader/human review unverified |
| 19 | Core/Future DB upgrade + outbox + File 24 privacy | Static code present; deployed schema/migration/rollback unverified |
| 20 | File 01 routes, File 20/25 visual slots, release identity, multi-PR parity | Integration hooks present; **governance discrepancy** File 12 written package folder `12-pdf-library-foundation` versus existing canonical deployed-candidate package directory `pdf-library-foundation-12`; rename requires Founder-approved change-control and staged migration, not automatic unreviewed rename. `main` remains bootstrap-only |

## Post-review remediation batch
- **R27-D01**: Move all database/access-policy/reading-state/accessibility prerequisites and thumbnail metadata reads before issuing the first core reader-manifest bearer token. Failure now occurs before grant creation.
- **R27-D02**: Move IIIF access-policy read and error gate ahead of the preview token loop. Policy DB failure now precedes all manifest preview/download grant issuance.
- Add retained R27 regression `tests/test-twenty-round-review-r27.php`: 20 source-area owner assertions and explicit grant-order invariants for both manifests. CI is static/source evidence, **not** a substitute for full WordPress failure-injection tests.
- Change candidate software/package identity to `1.1.0-rc.6`; keep candidate DB schema and integration contract `1.1.0` unchanged, since these corrections require no new schema migration.
- Preserve R24–R26 historical records; update current status, package and CI markers. No force-merge into `main`.

## Unresolved release/acceptance boundaries
1. **Package-folder naming conflict** under File 12 plan is a controlled-governance decision, not a silent source refactor.
2. External File 00/01/05/06/16/17/19/20/21/23/24/25/26 and CF-04 providers must be tested on actual staging with their exact versions and contracts.
3. Hostinger active plugin/ZIP checksum, WordPress/PHP, deployed DB physical tables, migration options, keys, jobs, security and runtime logs are unverified.
4. Real browser/RTL/assistive-tech/offline, rights expiry/provider failure, private storage, backup/restore, rollback and Founder sign-off remain mandatory.
5. R27 exact-HEAD CI and deterministic package digest must be reported only after the new final SHA passes. R26 green does not prove R27 green.

## Five-field production truth
Repository HEAD: R27 branch exact commit to be resolved after edits; `main` HEAD `2b369ef2d609247896e1ab32118f1dc498407f51`.
Deployed Version: UNVERIFIED.
DB Version: UNVERIFIED (source candidate `1.1.0` only).
Migration State: UNVERIFIED.
Live Verification Status: NOT VERIFIED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
