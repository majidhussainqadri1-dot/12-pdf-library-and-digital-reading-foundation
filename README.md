# PDF Library and Digital Reading Foundation

File 12 of the **Sabri Social Homeopathy Platform**.

This repository contains the preserved original File 12 baseline and the corrective **0.2.0 remediation work** for a secure WordPress PDF library and digital reading foundation.

## Current corrective scope

Version 0.2.0 addresses the defects found during the post-import review:

- replaces WordPress-salt-derived encryption with an explicit, backed-up key ring and per-file key IDs;
- encrypts and decrypts PDFs in authenticated 1 MiB chunks instead of loading entire files into PHP memory;
- writes encrypted files atomically and removes posts, attachments, and files when a submission fails;
- removes expiring public nonces from published reader URLs while retaining authorization and nonces for non-public documents;
- adds schema versioning, idempotent migrations, duplicate-state cleanup, a unique state index, moderation audit records, and counter migration;
- implements real title, author, ISBN, and keyword search, pagination, most-read sorting, and most-saved sorting;
- prevents duplicate progress state while allowing multiple notes and page-specific bookmarks;
- validates report reasons, page ranges, publication metadata, required patient-case consent, upload identity, and genuine PDF structure;
- adds paginated privacy export and erasure, private-page no-cache/noindex controls, upload/report rate limits, and system-health gates;
- records moderation notes, reviewer identity, timestamps, and report-status audit transitions;
- adds a reproducible GitHub quality gate and standalone chunked-encryption tamper test.

## Repository layout

- `pdf-library/` — WordPress plugin source, currently version 0.2.0 on the corrective branch.
- `tests/test-crypto.php` — standalone encryption round-trip and tamper-detection smoke test.
- `SOURCE-PROVENANCE.md` — original 0.1.0 archive identity and baseline boundary.
- `REMEDIATION.md` — defect-to-fix traceability record.
- `STATUS.md` — present QA and release state.
- `MANIFEST.md` — current source inventory.
- `CHECKSUMS.sha256` — SHA-256 checksums for plugin source and the crypto smoke test.

## Runtime requirements

- WordPress 6.0 or later.
- PHP 7.4 or later, with OpenSSL and AES-256-GCM support.
- A writable private directory outside the public document root.
- A securely generated and independently backed-up 32-byte key ring configured in `wp-config.php`.
- Companion Files 02, 03, 04, 07, 09, and 10 for the complete planned integration.

## Mandatory encryption configuration

Use a cryptographically random 32-byte key. Keep an offline backup before uploading any PDF.

```php
define('SPL_PDF_MASTER_KEYS', array(
    'v1' => 'base64:REPLACE_WITH_A_32_BYTE_BASE64_KEY',
));
define('SPL_PDF_ACTIVE_KEY_ID', 'v1');
```

An older key must remain in the key ring while any encrypted file still carries that key ID. Removing or losing a required key makes the corresponding PDF unreadable.

An explicit storage path may be configured:

```php
define('SPL_PDF_STORAGE_DIR', '/absolute/private/path/pdf-library');
```

The path must be writable and outside the public web document root. Uploads remain blocked when encryption or private-storage health checks fail.

## Validation completed locally

- All PHP source and test files pass `php -l` under PHP 8.4.16.
- JavaScript passes `node --check`.
- The SPL2 encryption test passes multi-chunk round-trip verification.
- A modified ciphertext is rejected by AES-GCM authentication.
- The source no longer derives the active encryption key from WordPress salts.
- The upload path no longer reads the entire PDF into memory.

## Remaining acceptance boundary

The corrective code is **not production-complete** until the exact package passes:

- fresh and upgrade installation on Hostinger staging;
- database migration and duplicate-state migration tests;
- key backup, loss-prevention, rotation, and restore exercises;
- online-reading-only and downloadable-PDF workflows with small and large files;
- Founder, administrator, verified-doctor, reviewer, patient, and public-user permission tests;
- integration with companion modules;
- privacy export/erasure, moderation, reporting, caching, responsive, accessibility, browser, backup, rollback, and recovery acceptance.

## Original package identity

- Archive: `12-pdf-library-and-digital-reading-foundation-0.1.0.zip`
- SHA-256: `5f9f9b16e714365fc4eb4a49b40d1eec8a895cf1045747ad4dfae7fc1cb6856d`
- Original extracted files: 13

The exact unmodified import remains represented by the baseline history. The corrective branch intentionally changes source and increments the plugin version to 0.2.0.

## License

GPL-2.0-or-later, as declared by the plugin header and readme.
