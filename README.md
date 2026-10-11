# File 12 — PDF Library and Digital Reading

This branch contains the **R33 source-only corrective candidate** for File 12, based on the exact-green R30 rc.9 lineage. It retains R28's reconciliation of divergent R27 security fixes (reader/IIIF pregrant checks and current derivative binding revalidation), tightens the direct HTML reader, and retains canonical ownership across the platform. R33 retains R31 malformed metadata guards, and additionally verifies claimed PDF byte size against the physical source, cleans encryption-failure temporary files eagerly (the shutdown safety net remains), and denies explicit bad/partial cover uploads before the canonical PDF transaction. R33 additionally unifies a divergent separately-green R32 rights/consent policy-flag fix and rejects structured rights-policy mutation input before database reads. R34 additionally brings *persisted historic* download/print/offline permission interpretation, public DTO permission projection and explicit anonymous signed-token audience binding into agreement. R35 also unifies an independently divergent R34 private-notes/tag input correction, denies null audience/entitlement rights changes, and rejects corrupted historic private tag DTOs on read. Source/CI/package truth is not staging/live truth.

## R36 private reader and REST parity (11 October 2026)

The two divergent R35 Draft PRs used the same rc.14 candidate name but differed in historical private tags handling. R36 descends from PR #28, preserves the source reader's bounded and flat string-list checks, adds the **same** check to the actual paginated REST collection endpoint, and corrects native private item deletion to report success only for exactly one owner-scoped row deleted. A new actual PHP fixture checks valid and malformed stored rows through both native/REST paths plus DB zero-row/error/success deletion. See `docs/TWENTY-ROUND-REVIEW-2026-10-11-R36.md`. CI proves source behavior, not staging or deployed truth.

## Candidate identity

- Software: `1.1.0-rc.15`
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
