# File 12 Corrective Remediation Record

## Review disposition

The original 0.1.0 source passed archive integrity and syntax checks but failed architecture, security, data-integrity, privacy, and production-readiness review. The baseline remains preserved; the corrective source is versioned as 0.2.0.

## Critical defects corrected

| Review finding | Corrective implementation |
|---|---|
| Encryption key derived from WordPress salts | Explicit `SPL_PDF_MASTER_KEYS` key ring, active key ID, 32-byte validation, per-file key ID, and fail-closed upload health gate. |
| Whole PDF loaded into memory for encryption/decryption | `SPL2` chunked AES-256-GCM format using 1 MiB authenticated chunks and bounded memory. |
| Public reader depended on expiring WordPress nonces | Published documents use stable public stream URLs; non-public documents still require capability checks and a nonce. |
| Failed submissions could leave orphan files/posts/attachments | Draft-first transaction, required-cover validation, temporary encrypted file, atomic rename, and complete cleanup on exception. |

## High-priority defects corrected

| Review finding | Corrective implementation |
|---|---|
| Search claimed author/ISBN/keyword support but used only core text search | Scoped SQL search across title, excerpt, content, `_spl_author_name`, `_spl_isbn`, and `_spl_keywords`. |
| Most-read and most-saved sorting used counters that were never updated | Most-read uses `_spl_views`; save actions maintain `_spl_saves`; upgrade migration imports legacy read counters and recalculates saves. |
| Progress and other singleton state could duplicate | Schema version 0.2.0, `item_key`, deduplication migration, and unique user/document/type/item key. |
| No database schema version or upgrade path | `spl_db_version`, idempotent `dbDelta`, data migration, unique-index verification, and visible schema failure notice. |
| Existing unrelated page could be silently mapped as a module page | Managed-page ownership test and collision-safe creation of a unique page slug. |
| Moderation note was discarded | Review note, reviewer ID, timestamp, last review metadata, and immutable audit-table event. |
| Privacy export stopped after 500 records | Paged user-data and report export; batched erasure with completion status. |
| Upload page lacked private cache/index protection | No-cache and `X-Robots-Tag` protection for upload, saved, and reading-workspace pages. |

## Additional defects corrected

- report reason allowlist and independent report rate limit;
- document page-range validation for progress and bookmarks;
- required non-empty notes and report details;
- publication year, page count, ISBN, copyright status, PDF extension, upload origin, header, and WordPress file-type validation;
- patient-case consent rendered in HTML and checked server-side;
- real pagination instead of a fixed first 60 documents;
- storage creation and protection-file verification;
- private absolute storage path and public-document-root detection;
- OpenSSL/AES-256-GCM runtime health check;
- post-type validation on moderation actions;
- database error handling for interactions and report transitions;
- report-resolution audit notes;
- graceful JavaScript error handling and duplicate-submit prevention;
- plugin version, author spelling, readme, QA workflow, and source documentation corrected.

## Deliberate safety decisions

- The plugin does not auto-generate and silently store a master key in the WordPress database.
- Uploads fail closed until an externally backed-up key and private storage are configured.
- Old keys may coexist in the key ring; each encrypted file records its key ID.
- An unsupported legacy encrypted file is rejected with an explicit migration requirement rather than decrypted under an unverified key.
- “Online reading only” does not claim that browser-visible content can never be saved, copied, photographed, or captured.

## Verification performed before repository upload

- PHP syntax validation for all plugin and test PHP files.
- JavaScript syntax validation.
- Multi-chunk encryption and exact SHA-256 round trip.
- Ciphertext-tampering rejection.
- static rejection of the former WordPress-salt-derived key expression.
- static rejection of the former whole-upload `file_get_contents()` pattern.

## Remaining gates

No staging, database, browser, integration, backup, recovery, or production completion claim is made. `STATUS.md` is the authoritative remaining-gate list.
