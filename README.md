# File 12 — PDF Library and Digital Reading

This branch contains the **new-plan implementation candidate** for Sabri Social Homeopathy Platform File 12. It supersedes the earlier 0.2.0 corrective candidate as the forward source candidate while preserving the earlier Git history and provenance.

## Candidate identity

- Software: `1.0.0-rc.1`
- Database schema: `1.0.0`
- Contract: `1.0.0`
- Canonical install folder: `pdf-library-foundation-12/`
- WordPress slug/text domain: `pdf-library-digital-reading`
- PHP namespace prefix: `PLDR_`

## Implemented plan scope

The candidate implements the File 12 catalog and bibliographic domain, private encrypted objects, governed ingest, malware/OCR adapter gates, edition/hash dedupe, audience/access policy, short-lived bound delivery grants, authenticated HTTP Range delivery, a responsive accessible reader, progress/bookmarks/private notes/highlight notes, citations, lawful OCR search, rights/takedown/appeal lifecycle, preservation/integrity checks, versioned Book Content Packs, reliable outbox integration, privacy export/erasure, safe repair, legacy File 12 migration, and the rights-aware download manager.

The implementation consumes File 00 identity/entitlement authority and integrates through versioned hooks with Files 01, 05, 06, 16, 19, 20, 21 and 25 without creating parallel ownership.

## Truthful release boundary

This repository candidate is **not automatically a live-site claim**. Production completion requires Hostinger staging evidence for fresh install and supported upgrade/migration, real key backup/restore/decrypt, an actual malware scanner, storage placement, large-file/range/weak-connection behavior, roles/IDOR/privacy/rights workflows, companion integrations, responsive/RTL/accessibility/browser checks, deterministic package verification, backup/rollback rehearsal, Founder acceptance, deployment, and live re-test.

See `docs/STAGING-ACCEPTANCE.md` and `STATUS.md`.
