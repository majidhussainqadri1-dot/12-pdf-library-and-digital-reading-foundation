# File 12 Candidate Manifest

Candidate: `1.1.0-rc.1`

Canonical plugin directory: `pdf-library-foundation-12/`

## Core components

- `class-pldr-core.php` — canonical types/categories, authorization, DTOs, audit, idempotency, events.
- `class-pldr-schema.php` — normalized core schema, migrations, cron, legacy migration.
- `class-pldr-storage.php` — private storage, atomic object paths and cleanup.
- `class-pldr-crypto.php` — PLD3 chunked AES-256-GCM plus SPL2 migration reader and authenticated ranges.
- `class-pldr-ingest.php` — validation, scanner gate, encryption, dedupe, derivatives/OCR.
- `class-pldr-access.php` — policy evaluation, short-lived bound grants, Range delivery and revocation.
- `class-pldr-reader.php` — catalog, reader, private reading state, citations and search.
- `class-pldr-rights.php` — rights cases, takedown/appeal, Book Content Packs, outbox integrations.
- `class-pldr-rest.php` — versioned core command/query API.
- `class-pldr-admin.php` — health, integrity, safe repair and admin workflows.
- `class-pldr-privacy.php` — privacy exporter/eraser and legal-hold boundary.

## Future Digital Reading Intelligence 24

- `class-pldr-future.php` — governed feature registry, loader, schedules, advanced-reader UI bridge.
- `class-pldr-future-rest.php` — Future-24 REST contracts.
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
- `assets/future-reader-vault.js` — encrypted IndexedDB offline vault.
- `assets/future-reader.css` — responsive/RTL/accessibility styling for Future-24 tools.

## QA/docs

- `tests/` — cryptographic/tamper/range, plan-contract, Future-24 contract, secret and regression checks.
- `docs/FUTURE-24.md` — approved Future-24 requirement register and ownership boundaries.
- `docs/TRACEABILITY.md` — requirement-to-code traceability.
- `docs/STAGING-ACCEPTANCE.md` — runtime/staging acceptance boundary.
- `docs/REVIEW-LOG.md` — fresh review/fix evidence.

Large PDF/book binaries, secrets, private runbooks and external corpus payloads are intentionally **not** embedded in GitHub or the plugin package.
