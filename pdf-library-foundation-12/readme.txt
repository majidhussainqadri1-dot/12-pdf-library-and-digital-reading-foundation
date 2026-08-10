=== Sabri PDF Library and Digital Reading ===
Contributors: majidhussainqadri1-dot
Tags: pdf, library, digital-reading, rights, accessibility, rtl
Requires at least: 7.0
Tested up to: 7.0.1
Requires PHP: 8.1
Stable tag: 1.0.0-rc.1
License: GPLv2 or later

File 12 canonical implementation for the Sabri Social Homeopathy Platform.

== Description ==
Provides canonical bibliographic documents and editions, encrypted private objects, lawful rights records, signed short-lived audience/object/operation-bound delivery, HTTP byte ranges, an accessible responsive reader, private cross-device progress/bookmarks/notes/highlights, lawful OCR/search adapters, stable citations, takedown/dispute/appeal workflows, Book Content Pack manifests, preservation/integrity jobs, privacy export/erasure, and versioned cross-file events/contracts.

This release is a repository/code candidate. Staging acceptance, live deployment and operational acceptance remain separate evidence gates.

== Required runtime configuration ==
Define a backed-up key ring in wp-config.php. Example with a placeholder only:

PLDR_PDF_MASTER_KEYS => array of key-id => 32-byte base64/hex/raw values
PLDR_PDF_ACTIVE_KEY_ID => active key id

Never store real keys in GitHub. The preferred private object path is outside the public web root; PLDR_PDF_STORAGE_DIR may set an absolute path.

For production ingest, configure a malware-scanner adapter through the pldr_malware_scan filter and set PLDR_REQUIRE_MALWARE_SCANNER=true after staging verifies the provider. OCR is permission/policy aware and uses the pldr_ocr_extract_text provider contract; it fails into a truthful degraded state when no provider exists.

== Upgrade ==
The plugin detects legacy File 12 0.2.0 spl_document records and imports them in bounded background batches. Existing SPL2 chunk-encrypted objects and SPL_PDF_MASTER_KEYS remain readable for controlled migration. Staging must verify the legacy storage path and backed-up key ring before cutover.

== Security ==
Restricted originals are never published through WordPress Media Library. Delivery grants are short-lived, hashed at rest, operation-bound, object-bound, audience-bound, revocable, re-authorized at request time and support byte ranges. Private reading state is noindex/no-cache and excluded from public DTOs.

Localization
American English base with Urdu/Arabic/RTL readiness, semantic LTR exceptions for PDF coordinates/code, and locale-aware public labels.
