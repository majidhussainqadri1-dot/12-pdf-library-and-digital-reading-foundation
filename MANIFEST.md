# File 12 Candidate Manifest

Candidate: `1.0.0-rc.1`

Canonical plugin directory: `pdf-library-foundation-12/`

Major components:

- `class-pldr-core.php` — canonical types/categories, authorization, DTOs, audit, idempotency, events.
- `class-pldr-schema.php` — normalized schema, migrations, cron, legacy migration.
- `class-pldr-storage.php` — private storage, atomic object paths and cleanup.
- `class-pldr-crypto.php` — PLD3 chunked AES-256-GCM plus SPL2 migration reader and authenticated ranges.
- `class-pldr-ingest.php` — validation, scanner gate, encryption, dedupe, derivatives/OCR.
- `class-pldr-access.php` — policy evaluation, short-lived bound grants, Range delivery and revocation.
- `class-pldr-reader.php` — catalog, reader, private reading state, citations and search.
- `class-pldr-rights.php` — rights cases, takedown/appeal, Book Content Packs, outbox integrations.
- `class-pldr-rest.php` — versioned command/query API.
- `class-pldr-admin.php` — health, integrity, safe repair and admin workflows.
- `class-pldr-privacy.php` — privacy exporter/eraser and legal-hold boundary.
- `assets/reader.js` — reader behavior and resumable Range download manager.
- `assets/reader.css` — responsive, accessible, RTL-aware presentation.
- `tests/` — cryptographic/tamper/range and plan-contract checks.
- `docs/` — traceability and staging acceptance boundary.

Large PDF/book binaries are intentionally **not** embedded in GitHub or the plugin package.
