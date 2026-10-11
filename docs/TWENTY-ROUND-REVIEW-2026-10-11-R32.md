# File 12 R32 — Twenty Focused Source-to-Governing-Plans Reviews and Corrective Release

**Reviewed 11 October 2026 (PKT). Source baseline:** R31 Draft PR #21 exact HEAD `55005aef64841ab2f23254051f1db44e14594163`; its GitHub Actions #423 was green. This source state is a development branch, **not** the deployed plugin.

## Direct governing source materials
Read the actual uploaded:
- `12-File-12-PDF-Library-and-Digital-Reading-Complete-Master-Plan-2026-v1.1-Future-24-Amended-2026-08-11 (1).docx`, particularly the 19 `F12-FR`, 10 `F12-NFR`, 24 `F12-FUT`, Owner and Interface constitution, implementation trace, backup/rollback and acceptance provisions.
- `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026۔۔۔4۔۔۔۔۔.docx`, including canonical ownership, role claims, cross-module versioned adapters and live/staging/repository separation.
- File 05 Learn, File 06 Encyclopedia, File 21 Home and News Feed, File 23 Publishing Dashboard, and File 25 Unified Visual Experience final uploaded plans.
- File 00/01/16/17/19/20/24/26 and CF-04 owner/interface conditions only from governing cross-links and File 12 repository code; **their exact current source/build/runtime was not fetched or tested here**.

The File12 written `F12-FR-002` specifically requires genuine PDF/direct-private upload, physical file/content signature, size, malware/polyglot/password checks and checksum. `F12-FR-006` requires protected AES-GCM objects/key lifecycle, `F12-FR-007` governs covers/derived OCR, `F12-NFR-003/007` demand reliable recovery, rollback and no silent data loss.

## Twenty independent inspection foci — frozen findings, then corrective batch
Each entry is a separate selected source/plan focus. **Not** a claim of twenty independent comprehensive line-by-line audits of all code, twenty independent staging re-tests or a live acceptance cycle. The retained `tests/test-r28-twenty-full-plan-sweeps.php` independently performs 20 repeated *static* 53-requirement-ID + 12 adapter-presence checks with twenty additional invariants. Static presence cannot prove functional completeness.

| Review | Compared plan/owner and source | Evidence/conclusion at review freeze |
|---:|---|---|
| 01 | Repository exact branch, PR #21/R31, `main` reality | R31 `55005aef...` fixed; `main` remains bootstrap `2b369ef...`; live parity unknown |
| 02 | F12-FR-001, 16 kinds/categories, bibliographic metadata | `PLDR_Core::DOCUMENT_TYPES/CATEGORIES`, `PLDR_Search`, existing source tests; real database/catalog dataset unverified |
| 03 | F12-FR-002, upload metadata shape, duplicate check | R31 `PLDR_Ingest` guards present and its 47 real-entrypoint tests retained |
| **04** | **F12-FR-002, request-declared PDF byte size versus physical source bytes** | **R32-D01:** `validate_pdf()` checked reported `$pdf['size']` and PDF signature/EOF but did not compare with actual disk `filesize($pdf['tmp_name'])`. Mismatch risks inaccurate stored byte_size and content integrity conditions. |
| **05** | **F12-FR-006 / NFR-003/007, temp encrypted object failure recovery** | **R32-D02:** `PLDR_Ingest::ingest()` returned immediately when encryption failed but did not eagerly delete its `PLDR_Storage::temp` path. **Nuance:** `PLDR_Storage` has an existing shutdown cleanup safety-net; source is a missing immediate failure cleanup, **not** proof of permanent disk leakage. |
| **06** | **F12-FR-007, optional cover upload pre-commit validation** | **R32-D03:** explicitly supplied partial, failed or spoofed cover could be silently skipped on the cover branch, or rejected only in post-commit cover reconciliation; non-HTTP or incomplete uploads must fail before the canonical PDF transaction starts |
| 07 | Genuine PDF header/EOF, malicious appended bytes, MIME/password gate | PDF source code validated; new physical-size/changed-file/polyglot fixture added; broader corpus unverified |
| 08 | Malware provider/scan classification and publication gate | `scan_file` and `publish_status` keep non-clean files out of published state; real scanner contract unverified |
| 09 | Lawful rights basis, expiry, embargo, takedown | Source checks from R30/rights manager; real evidence/provider decisions unverified |
| 10 | Edition/version, family concurrency and exact SHA duplicate | `PLDR_Ingest`, `PLDR_Schema`; no new proven source gap, real migration/uniqueness under load unverified |
| 11 | AES-GCM chunked encryption, private storage and key backup | `PLDR_Crypto` and `PLDR_Storage` source; physical key restore/decrypt still unverified |
| 12 | Signed delivery, current token binding, audience revalidation | `PLDR_Access` R26-R30 contracts/tests retained, live HTTP/IDOR tests outstanding |
| 13 | Reader REST/HTML grants, previews and IIIF | R27/R28 source pregrant checks preserved; genuine WordPress injected-DB-failure test still pending |
| 14 | F12-FR-013 catalog facets, OCR and signed cursor | R29/R30 real `PLDR_Search` fixture retained; File26 index freshness/external ranking unverified |
| 15 | Private reading, bookmarks, notes and privacy erase | `PLDR_Reading`/privacy code exists; real multi-actor retention/legal hold not verified |
| 16 | Future-24 IDs and differentiated derived content | `PLDR_Future` has exact `F12-FUT-001..024` registration; production/provider/browser acceptance not implied |
| 17 | Files 05/06/16 original lesson/encyclopedia/AI owners | File12 Future Context/Corpus source-side adapters maintain native-owner link; actual consumer versions and patient privacy claims unverified |
| 18 | Files 17/19/21/23 communication, events, cards and publishing | Source-side rooms/event/outbox references, File21 feed discovery and File23 native content projections; consumer staging not verified |
| 19 | Files 01/20/25 global route, shell, visual/RTL/accessibility | File12 source-side route and slot integration; File20/25 runtime/keyboard/RTL checks outstanding |
| 20 | Source DB revisions, CF-04 activation, package, migration/rollback and live parity | Schema/QA code visible; deployed database/CF04/private object-key, backup/rollback and user approval **not** verified |

### Corrections applied only after source finding freeze
- **R32-D01 fixed:** in the real `PLDR_Ingest::validate_pdf`, verify the physical disk `filesize` equals the claimed upload size before reading header/trailer, hashing, scanning or encrypting. Return machine error `pldr_pdf_size_mismatch` on any missing/changed/mismatched object.
- **R32-D02 fixed:** on an Encryption false result or thrown exception, eagerly `PLDR_Storage::delete($temp)`, in addition to keeping Storage's pre-existing shutdown safety-net. On exceptions, emit only privacy-safe error class as internal audit, not leaking paths.
- **R32-D03 fixed:** explicit cover uploads with bad/partial status, empty/mismatched temporary references or unverified HTTP path are denied **before** canonical document DB lookup and original-PDF commit. The normal absent-cover case remains permitted.
- **New regression:** `tests/test-r32-pdf-physical-integrity.php` invokes actual PDF validator against a local genuine PDF with matching, incorrect declared count and modified-file/polyglot cases; asserts source-flow invariants for eager temporary-file cleanup and cover-before-commit. `tests/test-r31-ingest-input-shapes.php` extended with three additional real `ingest` fixture cases for invalid covers.
- **Version:** source software/ZIP candidate `1.1.0-rc.11`; source DB/schema and interface contracts still `1.1.0` (no schema migration).
- Prior R1–R31 tests, real R29/R30/R31 security fixtures, and repeated static R28 20×65 = 1,300 requirement anchors remain in the CI gate.

## Unresolved release requirements that MUST remain separate from source fixes
1. Original File12 plan specifies install-package root folder `12-pdf-library-foundation`, while existing candidate package uses `pdf-library-foundation-12`. This is a documented **change-control and compatibility migration blocker**, not a license to silently rename a WordPress plugin directory.
2. The *actual* installed source/version of File00/01/05/06/16/17/19/20/21/23/24/25/26 and CF04 and their native owner-contract tests remain unverified.
3. Need Hostinger staging: real HTTP/multipart PDF/cover uploads, database schema + migration state and actual wp_options, rights cases/consent roles, scanner/OCR provider, encrypted delivery/byte-ranges, real physical key backup+restore, privacy/erasure, offline/RTL/a11y and slow-network tests.
4. Need two fresh staging review/fix rounds, backup/rollback practice, Founder approval, controlled deployment, live re-test and exact deployed package checksum/schema parity before calling a production issue resolved.
5. Green GitHub CI never proves live deployment, cross-module compatibility, or human WCAG acceptance.

## Separate reality fields
**Repository main HEAD:** `2b369ef2d609247896e1ab32118f1dc498407f51` (initial README).  
**Repository R32 Candidate HEAD:** use the exact final PR HEAD and matching completed GitHub Actions status; never reuse older green.  
**Deployed Version:** UNVERIFIED.  
**DB Version:** UNVERIFIED (source contract target `1.1.0`).  
**Migration State:** UNVERIFIED.  
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
