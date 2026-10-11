# File 12 — PDF Library and Digital Reading

This branch contains the **R33 source-only corrective candidate** for File 12, based on the exact-green R30 rc.9 lineage. It retains R28's reconciliation of divergent R27 security fixes (reader/IIIF pregrant checks and current derivative binding revalidation), tightens the direct HTML reader, and retains canonical ownership across the platform. R33 retains R31 malformed metadata guards, and additionally verifies claimed PDF byte size against the physical source, cleans encryption-failure temporary files eagerly (the shutdown safety net remains), and denies explicit bad/partial cover uploads before the canonical PDF transaction. R33 additionally unifies a divergent separately-green R32 rights/consent policy-flag fix and rejects structured rights-policy mutation input before database reads. R34 additionally brings *persisted historic* download/print/offline permission interpretation, public DTO permission projection and explicit anonymous signed-token audience binding into agreement. R35 also unifies an independently divergent R34 private-notes/tag input correction, denies null audience/entitlement rights changes, and rejects corrupted historic private tag DTOs on read. Source/CI/package truth is not staging/live truth.

## Candidate identity

- Software: `1.1.0-rc.19`
- Database schema: `1.1.0`
- Contract: `1.1.0`
- Future enhancement contract: `F12-FUT-001` through `F12-FUT-024`
- Canonical install folder: `pdf-library-foundation-12/`
- WordPress slug/text domain: `pdf-library-digital-reading`
- PHP namespace prefix: `PLDR_`

## R30 integrated source correction (11 October 2026)

This review reconciles divergent R29 Draft PRs #18 and #19 on a distinct rc.9 source candidate. Preserved File12 catalog facets, signed-pagination and public navigation from PR #19, restored the proven embargo/audience fail-closed rules from PR #18, and rejects structured/bool/null filter inputs rather than broadening a search silently. The real policy fixture and catalog facet tests are rerun in exact-HEAD CI alongside the prior R26–R28 suites and 20-repeat static 53-ID + 12-adapter source matrix. See `docs/TWENTY-ROUND-REVIEW-2026-10-11-R30.md`.

No staging or live deployment is implied.

## Core plan scope

The candidate retains File 12 canonical catalog/bibliographic ownership, private encrypted PDF objects, governed ingest, malware/OCR provider gates, editions and dedupe, versioned audience/access policy, short-lived bound delivery grants, authenticated HTTP Range delivery, the responsive accessible reader, private progress/bookmarks/notes/highlights, citations, lawful OCR search, rights/takedown/appeal lifecycle, preservation/integrity, versioned Book Content Packs, governed outbox integration, privacy export/erasure, safe repair, legacy migration, and rights-aware download manager.

## Historical R24 corrective hardening

R24 adds File 01 registration for `/library/manage/`; fail-closed rejection of already-expired ingest rights; canonical publication/restore rights-policy checks; strictly monotonic private progress revisions; access-filtered catalog cursor skip continuity; one governed R21 outbox dispatcher; offline-vault reconnect/server reauthorization; shared Sabri Green token inheritance; duplicate-safe fingerprint scheduling; authenticated derived-text provider mutations; bounded signed-cursor pagination for the private reading workspace; and permanent R24 review/regression evidence.

## Future Digital Reading Intelligence 24

1. Advanced Reflow Reading Mode.
2. Read Aloud / Text-to-Speech Reader.
3. Smart Table of Contents & Outline Recovery.
4. Edition Comparison Laboratory.
5. Precise Scholarly Anchors.
6. Citation Export Center.
7. Global Bibliographic Authority Enrichment.
8. OCR Quality Laboratory.
9. Portable W3C-style Annotation import/export boundary.
10. IIIF Presentation interoperability.
11. Inside-Book Search Heatmap.
12. Encrypted Offline Reading Vault with reconnect/server reauthorization.
13. Ultra-Low-Bandwidth / text-first mode.
14. Multiple Reading Layouts.
15. Personal Smart Shelves.
16. Private Reading Insights — non-gamified.
17. Cross-Device Reading Session Handoff.
18. Accessibility Quality Inspector.
19. Scholarly Reading Rooms through companion communication contracts.
20. Knowledge Context Sidebar through canonical knowledge/learning providers.
21. AI-Ready Corpus Manifest with entitlement and corpus allowlist gates.
22. Translation & Transliteration derived overlay.
23. Digital Preservation Laboratory.
24. Visual Duplicate & Scan-Fingerprint Detection with no automatic edition merge.

## Ownership boundary

File 12 owns document/edition/reader state and advanced reading metadata that belongs to those documents. File 00 remains identity/entitlement authority; File 05 owns courses/lessons; File 06 owns canonical encyclopedia truth; File 16 owns AI output; communication/discussion remains with its native owner; File 20 owns global shell/navigation; File 25 owns global visual-system presentation. Context/room/AI features use adapters and manifests rather than duplicate backends.

## Truthful release boundary

This repository candidate is **not automatically a live-site claim**. Production completion still requires Hostinger staging evidence for fresh install and supported upgrade/migration, real key backup/restore/decrypt, malware/OCR and optional authority/translation providers, private storage, large-file/range/weak-connection/offline-vault behavior, roles/IDOR/privacy/rights workflows, companion integrations, responsive/RTL/accessibility/browser testing, deterministic package verification, backup/rollback rehearsal, Founder acceptance, deployment, and live re-test/parity.

See `docs/TWENTY-ROUND-REVIEW-2026-10-11-R28.md`, `docs/FUTURE-24.md`, `docs/STAGING-ACCEPTANCE.md`, `docs/TRACEABILITY.md`, `docs/TWENTY-ROUND-REVIEW-2026-08-13-R24.md`, and `STATUS.md`.


## R36 — paginated private reader and rights/progress API integrity (2026-10-11)
The R36 **source-only** software candidate `1.1.0-rc.19` addresses: (1) privacy-tag integrity in both direct `PLDR_Reading::items` and paginated `PLDR_REST::reading_items` via `PLDR_Reading::parse_stored_tags`; (2) strict form/JSON page, edition and optimistic marker shapes before reading-progress mutations; (3) early fail-closed scalar/positive-decimal/array evidence inputs for rights reports, decisions and appeals before DB/idempotency; plus guarded add/delete private item IDs. See `docs/TWENTY-ROUND-REVIEW-2026-10-11-R36.md` and executable `tests/test-r36-rest-privacy-mutation.php`. Actual external installed owner contracts, staging and live parity are **unverified**; green source CI does not authorize a deploy.


R36 also applies the same no-coercion input law to signed reader/download grants, rights approval and versioned access-policy updates, and repair command parameters. All four R36 source defect groups are recorded in the dated R36 review document; no live deployed proof exists.


## R37 — Reader rights display and private delete result truth (11 October 2026)
R37 source-only candidate `1.1.0-rc.19` fixes File12 FR-008/010/012 rights/readers: a legacy native `PLDR_Reading::delete_item` call now confirms exactly one owner-scoped deletion rather than treating 0 affected rows as success; direct HTML/JS reader boot and REST `reader_manifest` now both use the same `PLDR_Core::policy_flag` exact-1 rights interpretation as access enforcement/public document DTO. R36's stronger REST privacy/mutation controls are retained. R37 distinct source audit and evidence: `docs/TWENTY-ROUND-REVIEW-2026-10-11-R37.md`, PHP fixture `tests/test-r37-reader-permission-delete.php`. A divergent R36 Draft branch also named `rc.15` is not installed/merged by implication. No staging/live/performance/host-version claims are made.


## R38 — Fail-closed read query and edition-stable citation source candidate (2026-10-11)
Software `1.1.0-rc.19` adds strict GET query shape validation before database/authorization for private reading pagination, lawful OCR and citation; the basic Sabri/APA/MLA endpoint now rejects unsupported style labels rather than silently returning another format. Actual citation text now records immutable File 12 edition ID even where the stable public document URL points to the canonical family. Actual PHP regression: `tests/test-r38-rest-read-shapes-citation.php`. Twenty scoped source/plan areas in `docs/TWENTY-ROUND-REVIEW-2026-10-11-R38.md`. This source QA is not WordPress staging/live acceptance.


## R39 — real calendar/UTC rights controls and historic policy flag truth (11 October 2026)
R39 **source-only** `1.1.0-rc.19` hardens File12 FR-003/FR-008 and central rights/permitted-source boundaries. Invalid calendar days (e.g. 2026-02-31), malformed clock and impossible ISO timezone offsets are denied rather than silently advanced with PHP `strtotime`. Stored historical embargoes and edition rights expiry use the same fail-closed calendar parser on each actual access read, using UTC when no timezone was supplied. An audience-only versioned policy change preserves normalized existing `'true'/'on'/'yes'` permissions; malformed/false-like flags remain disabled. See `tests/test-r39-rights-calendar-policy.php` (actual Access/Core fixture) and `docs/TWENTY-ROUND-REVIEW-2026-10-11-R39.md` (20 distinct scoped governing plan/source checks). Actual Hostinger/staging/DB parity and owner integrations remain unverified.


## R40 — Ingest rights date parity and safe PDF catalog numeric controls (2026-10-11)
`1.1.0-rc.19` is a **repository source candidate only**. The R39 strict real ISO/UTC calendar validator is now shared by fresh PDF Ingest's edition rights-expiry and access embargo, instead of leaving fresh uploads on PHP `strtotime` calendar rollover or local-time behavior. When a new pending edition omits download, print, offline or embargo parameters, the live canonical policy is inherited for the nonmutation comparison, not treated as a user-requested rights change; the actual HTTP upload branch requires WordPress staging acceptance. Catalog `page/per_page` values reject negatives/zero/decimals before DB. Actual PHPUnit-free PHP fixture `tests/test-r40-ingest-date-catalog-pagination.php` invokes real Ingest and Search methods, with pending-branch static assertions explicitly distinguished from real executable tests. Full twenty-source-focus ledger: `docs/TWENTY-ROUND-REVIEW-2026-10-11-R40.md`. No Hostinger deployment/DB/schema verification or Founder approval.
