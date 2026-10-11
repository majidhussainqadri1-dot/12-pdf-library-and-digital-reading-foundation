# File 12 — R34 exact-source rights consistency candidate (11 October 2026)

| Field | Verified scope |
|---|---|
| Source basis | R33 Draft PR #24 exact-green `f7b1828ceb932837f9031e65f4d1dc44cc43b9e7` |
| Governing plans | File12 v1.1 Future24 amended; Three Consolidated Central Plans; supplied File05/06/21/23/25 plans |
| New 20 reviews | `docs/TWENTY-ROUND-REVIEW-2026-10-11-R34.md`, twenty distinct scoped source/owner/plan focus checks. NOT twenty full WordPress/staging/live tests |
| R34-D01 | Legacy persisted download/print/offline values `'false'`/`'off'` bypassed new writer validation and were treated as allowed; corrected in actual `PLDR_Access::can_access_edition` |
| R34-D02 | Public DTO permissions used old `!empty()` truthiness and could advertise rights not actually valid; corrected in `PLDR_Core::public_document_dto` |
| R34-D03 | Explicit anonymous `-1` issue-token user did not match `deliver()` public audience hash; corrected issuance to store/hash user 0 after anonymous authorization |
| New real PHP test | `tests/test-r34-access-grants.php`: actual Core/Access methods, persisted false/on/off/invalid, public DTO parity, invalid operation denial and explicit anonymous/user token hash semantics |
| Retained tests | R28 20×(53 File12 governed IDs+12 source-side adapters)=1,300 static anchors, R29 catalog fixture, R30 policy, R31 50 ingest, R32 real PDF, R33 105 policy flag assertions, earlier crypto/range/security/future tests |
| Software candidate | `1.1.0-rc.13` source-only, unique ZIP; no new R34 schema migration |
| Source DB & interface contract | `1.1.0` / `1.1.0` unchanged |
| Exact final R34 HEAD CI | Must verify on last unchanged PR #26 head and matching artifact SHA after all docs commits |
| ZIP internal folder conflict | Written plan `12-pdf-library-foundation` vs source `pdf-library-foundation-12`; Founder-approved compatibility migration and rollback needed |
| Other owner module installed versions/contracts | UNVERIFIED: File00/01/05/06/16/17/19/20/21/23/24/25/26, CF-04 |
| Real WordPress staging/backup/rollback/Hostinger runtime | UNVERIFIED; Founder approval not granted for deployment |

## Separate reality truths

**Repository HEAD:** R34 Draft PR #26 final immutable SHA after checks; `main` remains bootstrap README `2b369ef2d609247896e1ab32118f1dc498407f51`.

**Deployed Version:** UNVERIFIED.  
**DB Version:** UNVERIFIED (source declares 1.1.0).  
**Migration State:** UNVERIFIED.  
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
