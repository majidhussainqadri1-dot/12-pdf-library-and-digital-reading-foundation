# File 12 Candidate Manifest

Candidate: `1.1.0-rc.9`

Database schema: `1.1.0`
Integration contract: `1.1.0`

Canonical plugin directory: `pdf-library-foundation-12/`

## Core components

- `class-pldr-core.php` — canonical types/categories, authorization, DTOs, audit, idempotency, events.
- `class-pldr-schema.php` — normalized core schema, migrations, cron, legacy migration.
- `class-pldr-storage.php` — private storage, atomic object paths and cleanup.
- `class-pldr-crypto.php` — PLD3 chunked AES-256-GCM plus SPL2 migration reader and authenticated ranges.
- `class-pldr-ingest.php` — validation, scanner gate, current-rights gate, encryption, dedupe, derivatives/OCR.
- `class-pldr-access.php` — policy evaluation, short-lived bound grants, Range delivery and revocation.
- `class-pldr-reader.php` — catalog, signed continuation, reader, private reading state/workspace, citations and search.
- `class-pldr-rights.php` — rights cases, takedown/appeal, publication eligibility, Book Content Packs, governed outbox/integrations.
- `class-pldr-rest.php` — versioned core command/query API.
- `class-pldr-admin.php` — health, integrity, safe repair and admin workflows.
- `class-pldr-privacy.php` — privacy exporter/eraser and legal-hold boundary.

## Future Digital Reading Intelligence 24

- `class-pldr-future.php` — governed feature registry, loader, schedules, duplicate-safe fingerprint scheduling, advanced-reader UI bridge.
- `class-pldr-future-rest.php` — Future-24 REST contracts including authenticated offline reconnect authorization and provider-derived mutations.
- `class-pldr-future-schema.php` — versioned Future-24 schema.
- `class-pldr-future-data.php` — lawful OCR/reflow, outline recovery and edition comparison.
- `class-pldr-future-derived-text.php` — translation/transliteration provider boundary.
- `class-pldr-future-anchors.php` — precise scholarly anchors.
- `class-pldr-future-citations.php` — citation export formats.
- `class-pldr-future-authority.php` — DOI/ORCID/ISBN enrichment adapter/cache with provenance.
- `class-pldr-future-ocr-lab.php` — OCR quality/correction workflow.
- `class-pldr-future-annotations.php` — portable annotation import/export boundary.
- `class-pldr-future-iiif.php` — rights-aware IIIF Presentation manifest.
- `class-pldr-future-search.php` — inside-book search heatmap.
- `class-pldr-future-preferences.php` — private advanced-reader preferences.
- `class-pldr-future-shelves.php` — private Smart Shelves.
- `class-pldr-future-insights.php` — private non-gamified reading insights.
- `class-pldr-future-handoff.php` — cross-device reading session handoff.
- `class-pldr-future-a11y.php` — accessibility quality inspector/verification record.
- `class-pldr-future-rooms.php` — document/page room-context adapter; no messaging backend duplication.
- `class-pldr-future-context.php` — companion knowledge-context adapter.
- `class-pldr-future-corpus.php` — deny-by-default AI corpus manifest for File 16 consumption.
- `class-pldr-future-preservation.php` — preservation/integrity assessment records.
- `class-pldr-future-fingerprint.php` — visual/OCR scan-family fingerprints, no automatic edition merge.
- `assets/future-reader.js` — reflow/TTS/layout/data-saver/preferences client.
- `assets/future-reader-scholar.js` — outline/compare/anchors/citations/annotations/OCR/IIIF/heatmap client.
- `assets/future-reader-personal.js` — shelves/insights/handoff/rooms/context/derived text/accessibility client.
- `assets/future-reader-vault.js` — encrypted IndexedDB offline vault with reconnect/server reauthorization.
- `assets/reader.css` / `assets/future-reader.css` — responsive/RTL/accessibility styles inheriting shared Sabri visual tokens.

## QA/docs

- `tests/` — cryptographic/tamper/range, plan-contract, Future-24 contract, secret and retained corrective-review regressions through R27.
- `docs/FUTURE-24.md` — approved Future-24 requirement register and ownership boundaries.
- `docs/TRACEABILITY.md` — requirement-to-code traceability.
- `docs/STAGING-ACCEPTANCE.md` — runtime/staging acceptance boundary.
- `docs/TWENTY-ROUND-REVIEW-2026-08-29-R25.md` — historical R25 review/fix/retest evidence; earlier R24 evidence remains retained.
- `docs/TWENTY-ROUND-CROSS-PLAN-AUDIT-2026-10-10-R26.md` — new cross-plan 20-focus audit, explicit audience escalation fix and evidence boundaries.
- `tests/test-twenty-round-review-r26.php` — executable anonymous bearer grant regression under privileged browser context.

Large PDF/book binaries, secrets, private runbooks and external corpus payloads are intentionally **not** embedded in GitHub or the plugin package.

- R27 source review: `docs/TWENTY-ROUND-REVIEW-2026-10-10-R27.md`; permanent regression: `tests/test-twenty-round-review-r27.php`.


## R28 evidence
- `docs/TWENTY-ROUND-REVIEW-2026-10-11-R28.md`: exact-baseline, 20-pass full source-plan matrix ledger, four corrected source/documentation issues, and separate deployment-truth restrictions.
- `tests/test-r28-detached-grant-binding.php`: detached original/derivative signed-delivery authorization regression.
- `tests/test-r28-reader-html-pregrant.php`: direct HTML reader must check private state and policy before grant issuance.
- `tests/test-r28-twenty-full-plan-sweeps.php`: 20 repeated 53-ID + 12 companion anchor source sweeps (1,300 presence checks) and one additional invariant per pass.
- Competing R27 branch security corrections unified into R28; software candidate rc.7, DB/contract still 1.1.0.


## R29 plan-to-code completion evidence
- `docs/TWENTY-ROUND-REVIEW-2026-10-11-R29.md` — 20 scoped comparisons to the actual File 12/central/companion written plans, exact two source defects and remediation boundaries.
- `tests/test-r29-catalog-facets.php` — actual `PLDR_Search` fixtures for author/source/edition/ISBN/license/year/access facets, invalid values, private-result denial, cursor query binding and REST/HTML parity.
- File 12 FR-001 / FR-013: add seven bounded bibliographic facets, plus accessible signed Next/First navigation. Existing owner authorization and signed cursor invariants retained. R28 earlier 20-source-matrix and prior corrective suites preserved.
- Candidate `1.1.0-rc.9`; DB/schema and interface contract stay `1.1.0`. Package folder rename remains a separate unapproved change-control gate.
