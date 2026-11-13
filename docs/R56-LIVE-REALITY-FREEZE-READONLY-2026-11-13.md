# File 12 R56 — Hostinger read-only Live Reality Freeze (13 November 2026)

PURPOSE. Collect the physical evidence required by the V12.1 Live-First rule before diagnosing a WordPress incident or deploying a GitHub source candidate. This procedure is NOT an automatic fix, update, deployment, or proof of current production parity.

RUN ONLY IN AN AUTHORIZED HOSTINGER SSH SESSION WITH A READ-ONLY DB USER IF AVAILABLE:
  bash scripts/file12-live-reality-freeze-readonly.sh /absolute/wordpress/root

CAPTURED: UTC timestamp; installed WP core file version; CLI PHP version (not PHP-FPM); configured active plugin paths; pldr_db_version wp_option; presence only of known migration markers; physical tables, columns and indexes from information_schema; deployed File12 plugin entrypoint and deterministic source-tree hashes; presence of the default debug log (not its contents).

PRIVACY. Never paste secret options, wp-config.php, private patient data, raw logs, identity records, book scans, tokens, keys or unredacted SQL rows into GitHub. Store evidence in an access-controlled location and redact local filesystem path, organization identifiers and other sensitive metadata as appropriate.

LIMITS. Only SELECT SQL is used; WP-CLI is invoked with skip-plugins and skip-themes. This mitigates normal plugin boot/migration, but cannot guarantee a fully side-effect-free Hostinger environment (especially custom wp-cli setup or must-use plugins). Use an authorized read-only database identity. Do not run the script from a public web path or while an incident is active without an approved change freeze.

Active_plugins is a DB configuration flag, NOT proof that that build is executing. CLI PHP may differ from the web handler. SHOW schema metadata is not migration-completion proof. TABLE_ROWS is only an estimate. Plugin tree checksums are not equal to the source ZIP checksum; compare a normalized extracted package tree and explicit installed source files. A custom plugin directory or multisite may need additional manual inquiry.

NEXT. Capture actual PHP/FPM and runtime errors in private redacted review, compare installed plugin build and physical DB migration to exact repository main and the separately immutable draft candidate, resolve any mismatch via Deployment-Parity Audit, then run 39 staged acceptance cases with original books, licensing, privacy, File06/16/26 owner integrations, offline rights, scanner and backup restore.

CURRENT BLOCKER. WordPress.com Jetpack connector reports site_disconnected and WPVibe connect_site returned HTTP 530; these are symptoms, no root cause is established. GitHub source SHA is not installed code truth. File26 OwnerContract requires pdf and book_pack, while File12 adapter only implements pdf (File26 issue #23); tombstone/rights event contract not approved.

NO LIVE CLAIM. Repository candidate SHA must be separately captured. Deployed Version UNVERIFIED; DB Version UNVERIFIED; Migration State UNVERIFIED; Live Verification NOT PERFORMED.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔