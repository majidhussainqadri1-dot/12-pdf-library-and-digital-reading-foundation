# Status

## Current state

**Corrective source 0.2.0 prepared — automated quality gate and staging acceptance required before merge or release.**

The original 0.1.0 baseline is preserved in repository history. The current corrective source intentionally resolves the defects recorded in `REMEDIATION.md`.

## Completed local verification

- Original ZIP identity and SHA-256 remain recorded.
- Corrective plugin version is 0.2.0.
- 14 plugin source files are present, including the new chunked-crypto class.
- All plugin and test PHP files pass syntax validation under PHP 8.4.16.
- JavaScript syntax validation passes.
- SPL2 multi-chunk encryption/decryption round trip passes.
- Authenticated tamper rejection passes.
- Key material is no longer derived from WordPress salts.
- PDF encryption/decryption uses bounded chunks rather than full-file memory loading.
- Defect-to-fix traceability is recorded.

## GitHub quality gate required

The branch must pass:

- source checksum verification;
- PHP lint;
- standalone encryption and tamper test;
- legacy unsafe-pattern rejection;
- JavaScript syntax validation;
- reproducible installable ZIP creation.

## Staging gates not yet established

- Fresh WordPress installation and activation without warnings or fatal errors.
- Upgrade from the 0.1.0 schema and duplicate-data migration.
- Verification that the unique state index exists after migration.
- Correct key-ring installation, offline backup, restore, and old-key retention test.
- Private storage path outside Hostinger's public document root.
- Small and large PDF upload, chunked encryption, reader, and optional-download behavior.
- Corrupted/tampered encrypted-file rejection without data leakage.
- Atomic rollback after simulated post, cover, encryption, taxonomy, metadata, and status failures.
- Real search across title, author, ISBN, and keywords; pagination; most-read and most-saved ordering.
- Progress, bookmarks, multiple notes, reactions, comments, reports, moderation, and audit records.
- Privacy export and erasure beyond one batch.
- Founder, administrator, verified doctor, reviewer, member, and public permission enforcement.
- Integration with Files 02, 03, 04, 07, 09, and 10.
- Cache, noindex/noarchive, responsive, accessibility, cross-browser, and browser-PDF behavior.
- Backup restore, deactivation, uninstall retention, upgrade rollback, and recovery acceptance.

## Release judgment

The corrective code may proceed to automated CI and controlled staging. It must not be called production-complete, merged as an accepted release, or deployed live until every blocking gate above is tested and formally accepted.
