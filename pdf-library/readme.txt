=== PDF Library and Digital Reading Foundation ===
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later

Corrective foundation release for the Sabri Social Homeopathy Platform PDF Library and digital reading system.

Version 0.2.0 adds chunked AES-256-GCM private storage, explicit backed-up key-ring configuration with per-file key IDs, atomic upload rollback, public reader URLs without expiring public nonces, schema versioning and migrations, accurate title/author/ISBN/keyword search, pagination, working most-read and most-saved sorting, duplicate-safe reading state, moderation audit records, paginated privacy export/erasure, report validation and rate limits, private-page cache/index protection, and system-health gates.

Before accepting uploads, configure a 32-byte master-key ring in wp-config.php and retain an offline backup:

define('SPL_PDF_MASTER_KEYS', array('v1' => 'base64:REPLACE_WITH_32_BYTE_BASE64_KEY'));
define('SPL_PDF_ACTIVE_KEY_ID', 'v1');

Do not remove an old key until every document carrying that key ID has been migrated and verified. The default private storage directory is outside the WordPress root when the hosting layout permits it. SPL_PDF_STORAGE_DIR may be defined to an explicitly protected, writable directory outside the public web root.

Keep companion Files 02, 03, 04, 07, 09, and 10 active. Perform fresh-install, upgrade, key-backup recovery, online-reading-only, downloadable-PDF, privacy, moderation, responsive, backup, and rollback acceptance on staging before production release.

Online-only mode removes the download permission and direct public file path, but no browser-based system can honestly guarantee that readable content can never be copied or captured.
