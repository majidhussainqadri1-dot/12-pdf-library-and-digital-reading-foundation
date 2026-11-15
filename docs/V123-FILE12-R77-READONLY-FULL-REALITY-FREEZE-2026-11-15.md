# File12 R77 — comprehensive read-only deployed/schema evidence freeze
**Date:** 2026-11-15 · Asia/Karachi · **status:** SOURCE-ONLY / NO LIVE / DRAFT PR #74

## Governing reason
V12.3 and the Live-First Exact-Deployed-State Rule require evidence in this sequence: Live symptom → exact active plugin path/version/bytes → physical DB schema/version → migration + wp_options → runtime/dependencies → deployment parity → root cause. Previous read-only R56 script captured `pldr_db_version`, generic migration lock and tables, but **missed** the separately versioned Future24 schema, its revision, corrective migration revision, future migration lock, legacy conversion checkpoint, and error markers. This partial inventory must not be interpreted as confirmed deployed-source parity.

## Source-only reproduced audit gap
Initial exact source from PR #73 `69ebd510909d739bd318d353a9ff9395234df2ec`. RED-first `tests/test-r77-readonly-schema-freeze.php` requires the independent `pldr_*` schema and migration markers while rejecting mutation verbs in collector code. GitHub Actions run `43707806850` failed **only** the deliberately failing R77 read-only full deployed schema-state scan (all three PHP-version jobs), proving missing source coverage. It did **not** run against Hostinger.

## Code correction in Draft PR #74 (no application/plugin changes)
- Read-only `wp --skip-plugins --skip-themes db query` enumerates fixed, allowlisted version/revision keys: `pldr_db_version`, `pldr_contract_version`, `pldr_future_db_version`, `pldr_future_schema_revision`, `pldr_schema_corrections_revision`. Only concise string values matching `^[0-9A-Za-z._-]{1,64}$` may print; any other value becomes `[REDACTED_OR_INVALID]`.
- Lock, deferred upgrade, legacy migration, schema error, loader error, and rewrite markers are printed **only as option names and PRESENT**, never option values, serialized bodies, lock tokens, private medical data or secrets.
- Installed entrypoint header `Version` is parsed as **file bytes without executing plugin code**, labeled `PLUGIN_HEADER_VERSION`. This header does NOT prove active PHP runtime version.
- Previously present physical tables/columns/indexes, active plugin paths, per-file SHA-256, directory checksum, CLI PHP/core version, and error-log existence remain read-only.
- Isolated `tests/test-r77-readonly-collector-synthetic.sh` creates a synthetic WP-CLI stub and synthetic WordPress plugin header; it exercises **the actual collector** on generated files without any Hostinger call. It checks expected fields, CLI invocations, plugin header and absence of SQL mutation. Bash syntax plus both static and synthetic tests run in the GitHub CI pipeline.

## Coverage exclusions — do not misinterpret
- Neither WordPress.com/Jetpack nor WPVibe currently supplied usable exact-site File12 runtime and physical DB evidence. Existing Jetpack site-scoped access was marked disconnected; WPVibe connect attempt returned HTTP530. Neither is proof of an origin outage or File12 bug.
- CLI PHP may differ from FPM PHP; active_plugins wp_option may not include must-use plugins, network-activated plugins or custom loader paths. Runtime loaded version, physical installed checksum, actual error logs and activated cross-file modules require authorized host inspection. Only the **standard two File12 directories** are hashed by this script.
- Output may contain site installation metadata (table/column names, plugin names, checksums). The authorized operator must review/redact before sharing; never publish private logs, encryption keys, clinical records or physical server credentials.
- A clean read-only collector output is **not an acceptance pass**; compare its records against exact package checksum, current physical schema migration expected versions, independent staging tests and current Owner contracts before concluding parity.
- Required code release remains NO_GO pending File26 BookPack + tombstone ACK, CF04 10 controls, 39 historic staging, 54 runtime, 77 V12.3, 60 QA cases, 2 independent reviewers and Founder approval.

## Operator handoff (requires explicit Hostinger authorization)
In a trusted terminal with WP-CLI available and a verified absolute WordPress root:
```bash
bash scripts/file12-live-reality-freeze-readonly.sh /absolute/wordpress/root
```
Only execute the reviewed script **on the authenticated host**, never infer this root from repository paths. Preserve the resulting manifest with UTC run time and source revision, review/redact, then compare to the actual installed File12 source and DB. This operation has NOT been run against Production.

## Five final reality fields
Repository main HEAD: `2b369ef2d609247896e1ab32118f1dc498407f51`.
Source candidate parent: Draft PR #73 software 1.1.0-rc.51; read-only R77 PR #74 does not modify plugin version or source DB version.
Deployed Version: UNVERIFIED.
DB Version: UNVERIFIED.
Migration State: UNVERIFIED.
Live Verification Status: NOT PERFORMED.
Release Status: NO_GO.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
