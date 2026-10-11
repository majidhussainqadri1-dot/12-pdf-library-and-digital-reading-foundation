# File 12 — R29 Source-vs-Governing-Plans Twenty-Focus Audit
Date: 11 October 2026 (Pakistan). Exact baseline: R28 PR #16 `cac343ecae14b8cff08be56c83cc6d32761426d3`.

## Governing written source evidence
The actual uploaded **File 12 PDF Library and Digital Reading Complete Master Plan 2026 v1.1 Future-24 amended 11 August**, **Three Central Plans Consolidated Governing Master Plan**, and actual companion File 05, File 06, File 21, File 23 and File 25 Word plans were read. Source-derived requirements here include:
- File 12 clause 2.1: *Catalog/search by title, author, language, type, category, publication/source, edition, license and access mode*.
- File 12 `F12-FR-001`: 16 governed types/categories, metadata and collections.
- File 12 `F12-FR-013`: metadata + lawful OCR search, facets, spelling/transliteration and access filtering.
- File 12 `F12-NFR-001` per-action owner authorization, `F12-NFR-004` bounded query/pagination.
- File 05 is course/lesson/progress truth owner; File 06 is encyclopedia truth owner; File 21 discovers PDFs as cards; File 23 projects doctor/founder linked PDF collections without taking native PDF ownership; File 25 owns shared visual components, while File 20 owns global shell.
- File 12 Future-24 IDs remain F12-FUT-001…024 with their original individual requirements. No new File 12 facts were invented.

## Audit method and honest limits
Twenty different **source-domain code reviews/check groups** were performed against the R28 exact HEAD and governing text, including an additional Future-24/source-adapter sweep. This is not twenty independent exhaustive full-code manual reads and does not verify installed WordPress, real counterpart repositories or live runtime. The pre-existing R28 automated 20-repeat plan/source matrix still runs in CI. **The defect ledger was closed before the product correction batch below**.

## Per-round frozen findings

| Round | Actual source/requirement focus | Finding |
|---|---|---|
| 01 | Owner, types, 16 categories, central architecture | Source anchors present; external module truth not independently proven |
| 02 | **F12-FR-001/013 catalog faceted search** against written plan | **R29-D01:** Search/REST lacked `author`, `source`, `edition`, `license`, `isbn`, `year`, and `access_mode`; only `q/type/category/language` were handled. Source deficiency proved by reviewing actual `PLDR_Search::search` and `PLDR_REST::register` |
| 03 | PDF validator/upload and rights evidence | Scanner/rights anchors present; real malformed PDF and provider not tested |
| 04 | Canonical edition/dedupe/hash | Code owner anchors present; real legacy/migration dataset unverified |
| 05 | Encrypted object and signed derivative binding | R28 reauthorization code present; live key/object parity unverified |
| 06 | Public/account/education/assigned policy, embargo and revocation | Existing code anchors present; purchased entitlement policy requires real File 00 contract/Founder governance before change |
| 07 | **F12-NFR-004 catalog HTML pagination** | **R29-D02:** `PLDR_Search` returned a bound `next_cursor`, but `PLDR_Reader::library_html` rendered no Next results link; users could not traverse the available pages from public browser UI |
| 08 | Private notes/progress/export/erase | Code owner anchors present; real multi-user concurrency and File 24 assurance unverified |
| 09 | OCR search and signed continuation | Code/variant hooks present; actual OCR/search provider integration pending |
| 10 | Citation and edition identity | Citation code present; actual canonical citations/real page mapping not yet verified |
| 11 | Rights takedown/appeal/revocation | Source state machine present; real rights evidence and scheduled expiry pending |
| 12 | Admin/repair/privacy isolation | Methods present; real least-privileged roles/guardians pending |
| 13 | Outbox and File 19/21 publishing/discovery link | Native event code/source-side integration hooks present, real consumers unverified |
| 14 | Separate File 05/06/16 owner domains | File 12 source adapters and AI corpus allowlist; counterpart contract acceptance unverified |
| 15 | File 01/20/25 route/shell/component contracts | Source hooks present, real combined browser navigation unverified |
| 16 | Future 24 registry, lawful OCR, reflow and overlays | All 24 stable feature anchors source-visible; no provider/browser equivalence claim |
| 17 | IIIF, offline vault and private shelves | Source anchors present; cross-device/rights/offline revocation unverified |
| 18 | Accessibility, language and RTL | Code owner hooks present; actual WCAG 2.2 AA human acceptance unverified |
| 19 | Schema, migration and preservation | Source/lock code present; deployed physical tables/options unknown |
| 20 | Deployment/release identity and package manifest | R28 exact HEAD known; software `rc.8` source update requires new exact-head CI; ZIP top-level folder conflict with plan remains formally unresolved |

## Corrections after review freeze

**R29-D01**: Updated `PLDR_Search::search` to support bounded author, publication source, edition label, ISBN, license, year, and rights access-mode search facets, using the *current* document edition and entitlement-filtered public projection. Invalid values/unknown types reject safely. All filters and user audience are included in the signed catalog cursor context; a token from another facet/audience is not replayable.

**R29-D02**: Updated the canonical `/library/` HTML form with the seven bibliographic filters and added accessible `First results` / `Next eligible documents` navigation using the returned signed cursor. Only allowlisted scalar query facets persist across Next links.

**Regression:** `tests/test-r29-catalog-facets.php` runs actual `PLDR_Search` logic against an isolated WordPress/DB fixture, including private-document exclusion, author/source/edition/ISBN/year/license/access filters, bounded/invalid input denial, and signed-cursor audience/facet binding. Registered in PHP quality gate. R28 previous 20-pass whole source matrix + all prior correctives retained.

**Versioning:** Software/package `1.1.0-rc.8`; DB schema `1.1.0` and integration contract `1.1.0` unchanged.

## Remaining source-to-acceptance gaps
- The **full live/staging File 00, 01, 05, 06, 16, 17, 19, 20, 21, 23, 24, 25, 26 and CF-04 contract matrix** cannot be verified by File 12 GitHub sources. Compatible installed versions, runtime routes, cross-owner events and real role/entitlement checks must be tested against their actual deployed artifacts.
- Folder discrepancy: plan specifies top-level ZIP directory `12-pdf-library-foundation`; historical source candidate uses `pdf-library-foundation-12`. **Do not silently rename without dated change request, migration/rollback and Founder approval.**
- Staging/real-actor and role testing, signed-range integrity, genuine PDF and malware/OCR providers, key backup/restore/decrypt, privacy deletion/consent, browser keyboard/RTL/a11y/offline, source/DB migration, backup/rollback, two fresh staging review cycles and Founder signoff remain mandatory.
- Source/CI success alone is **not** production resolved.

## Final five-field production truth
- Repository main HEAD: `2b369ef2d609247896e1ab32118f1dc498407f51` (initial README).
- Repository candidate R29 HEAD: exact final SHA from Draft PR; use only its exact-head successful GitHub CI if observed.
- Deployed Version: UNVERIFIED.
- DB Version: UNVERIFIED (candidate source `1.1.0` only).
- Migration State: UNVERIFIED.
- Live Verification Status: NOT VERIFIED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
