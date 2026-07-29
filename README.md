# PDF Library and Digital Reading Foundation

File 12 of the **Sabri Social Homeopathy Platform**.

This repository preserves the original source baseline for **PDF Library and Digital Reading Foundation 0.1.0**. The WordPress plugin provides the first-stage PDF library and digital reading foundation, including encrypted document storage, searchable discovery, controlled inline reading, optional downloads, private reading progress, page bookmarks, notes, reactions, comments, reports, privacy callbacks, structured data, and moderation workflows.

## Repository layout

- `pdf-library/` — original installable WordPress plugin source.
- `SOURCE-PROVENANCE.md` — source origin and integrity record.
- `MANIFEST.md` — package inventory.
- `CHECKSUMS.sha256` — SHA-256 integrity checksums for the preserved source.
- `STATUS.md` — current verification and release status.

## Requirements

- WordPress 6.0 or later.
- PHP 7.4 or later.
- Companion Files 02, 03, 04, 07, 09, and 10 should remain active, as stated by the original package documentation.

## Baseline verification

The imported package has passed:

- ZIP integrity and path-safety inspection before extraction.
- PHP syntax validation for all PHP files under PHP 8.4.16.
- JavaScript syntax validation with Node.js.
- Suspicious executable-pattern scan for common high-risk PHP constructs.
- SHA-256 generation for the original ZIP and every source file.

These checks establish archive and source integrity only. WordPress activation, database migration, permissions, encryption-key deployment, browser behavior, integration, upgrade, rollback, and staging acceptance remain separate release gates.

## Installation

Create an installable ZIP whose top-level directory is `pdf-library/`, upload it through the WordPress plugin installer, activate it on staging, and test both an online-reading-only PDF and a downloadable PDF before public release.

## Original package checksum

`12-pdf-library-and-digital-reading-foundation-0.1.0.zip`

SHA-256: `5f9f9b16e714365fc4eb4a49b40d1eec8a895cf1045747ad4dfae7fc1cb6856d`

## License

GPL-2.0-or-later, as declared by the plugin header and original readme.
