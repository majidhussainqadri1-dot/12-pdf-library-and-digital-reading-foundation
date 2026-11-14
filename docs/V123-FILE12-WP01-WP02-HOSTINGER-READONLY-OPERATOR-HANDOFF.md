# File 12 — WP-01/WP-02 authorized Hostinger read-only freeze and parity handoff

**Document date:** 2026-11-14. **Status:** not executed on Live or Staging; external operator action required. **Production mutation:** prohibited.

This handoff addresses the current blocker in the Sabri V12.3 File 12 plan. Live and Staging are distinct environments and must have independent evidence. The authenticated WordPress.com Jetpack connector currently reports `site_disconnected`, and an attempt to connect WPVibe to `https://www.sabrihomeopathy.com` returned HTTP 530. Neither result proves File12 runtime failure, Hostinger outage, active WAF policy, or defective WordPress code.

## A. Authorization and data-minimization requirements

- Only a verified Hostinger account/site administrator or authorized operations person should inspect the **actual** deployment. Do not send SSH credentials, WP application passwords, secrets, raw private PDFs, patient data, complete `wp_options` dumps, database exports, encryption keys or full logs into GitHub or a chat.
- Create separate evidence receipts for **Live** and **Staging**. Include `environment`, canonical host, UTC observed timestamp, responsible operator, exact read-only command and result provenance, WP/PHP/MySQL versions, installed File12/plugin directory, checksum, schema and migration markers. Redact hostname-adjacent secrets, paths that reveal private files, tokens and identity documents.
- Treat connection errors and public web responses as **symptoms** until origin/request correlation and actual runtime evidence establish a cause.
- Before any command, verify site/document-root selection and least-privileged credentials. Never run a staging-only test on live data.

## B. Known source-side evidence (not live truth)

- File12 repository `main`: `2b369ef2d609247896e1ab32118f1dc498407f51` (README only).
- Frozen Draft source baseline PR #57: `d7c0d9458402019a3c46c73b3a7563329e9be8f7`, software `1.1.0-rc.43`; *declared source* DB/contract `1.1.0`. No assertion of installation.
- V12.3 source governance Draft PR #58: `audit/file12-v123-evidence-first-20261114` stacked on PR #57. Not merged or installed.
- Two potentially relevant plugin directory names: `pdf-library-foundation-12` (candidate) and `12-pdf-library-foundation` (historic). **Do not rename/reinstall blindly.**
- Source-provided read-only collector: `scripts/file12-live-reality-freeze-readonly.sh`. This collector was reviewed as source/CI, **not executed on the actual host**.

## C. Permitted preliminary checks (all non-mutating)

1. Verify a trustworthy host-console login and resolve the actual WordPress installation root **outside the chat**; record whether it is Live or Staging.
2. If `wp` (WP-CLI), `php`, `sha256sum`, `find`, `sort`, `xargs` and `date` exist, review the collector content against Hostinger's local security policy first. Then an authorized operator may run the reviewed script on a **read-only** account with **read-only** SQL privileges:
   ```bash
   bash scripts/file12-live-reality-freeze-readonly.sh /absolute/wordpress/root
   ```
   The `/absolute/wordpress/root` placeholder must be replaced locally by the verified installation path; do not paste the private absolute path into a public issue.
3. The script is intended to list current WordPress version, *CLI* PHP version (which may differ from FPM), active plugin path option, **only** `pldr_db_version` value and the presence of selected migration-marker option names (not their secret content), File12 physical table names/approximate row count/columns/indexes, entrypoint SHA and an aggregate deterministic plugin file digest. It checks only whether a default debug.log exists; it intentionally does **not** read log contents.
4. If the script cannot be run safely (unavailable SSH, denied DB `information_schema`, unsupported WP-CLI, private access concerns), do **not** weaken permissions or guess values. Record which individual controls are `UNVERIFIED` and the precise blocker. Safe manual read-only equivalent screenshots/results may be submitted after redaction.
5. Collect only the **names/versions** of required installed companions File00/01/05/06/16/17/19/20/21/24/25/26 and CF04, their active/inactive state, and independently confirmed relevant contract versions. Do not substitute GitHub HEAD for their installed state.
6. Where site connection diagnostics are authorized, obtain origin/provider/Cloudflare relay request IDs and sanitized UTC timestamps for **HTTP 530**; do not infer a security defect from the code alone. Reconnection itself requires owner authorization.

## D. Parity verification on redacted receipts

| Comparison | Minimum independent evidence | Decision |
|---|---|---|
| Live installed folder and exact bytes | plugin slug, file listing and deterministic digest, running site identity | May be compared with source SHA only after captured |
| Approved candidate ZIP | content SHA256, version metadata, root folder, no extraneous private binaries | Not approved merely because CI built ZIP |
| Staging actual installed bytes | independent Staging installed checksum and package evidence | Must not reuse Live or GitHub evidence |
| Live/Stage DB schema | physical table names/columns/indexes and selected version option | Mismatch means migration audit before application debugging |
| Migration state | current lock/checkpoint/partial/deferred upgrade markers; safe error summary | Unverified remains unresolved |
| Companion owner APIs | installed versions + owner contract and callback health | File26 and CF04 stop-ship until owner-consumer proof |
| Runtime observation | redacted error code + relevant endpoint response + UTC, actual role | Not a root-cause proof alone |

If any deployed-version/schema mismatch exists, **STOP ordinary production debugging** and initiate **Deployment-Parity Audit**. Do not create a corrective patch for an unknown live build.

## E. Mandatory sensitive-data omissions

- Never publish `wp-config.php`, `AUTH_KEY`/salts, passwords, private PDF object keys, patient content, entire `active_plugins` raw serialized option if it includes custom sensitive paths, unfiltered log text, private attachment URLs, signed grants, or unredacted database backups.
- Never expose clinical history/appointments/user roles or personal identifiers in sample observations; use designated fictitious staging subjects only.
- Never copy entire `wp_options`, `wp_users`, private reading-state rows, or PDF files to GitHub for diagnostics.

## F. Decision after evidence

- **No access** → WP-01 status `BLOCKED`; WP-02 `BLOCKED`; 39+54+10+77 remain `UNVERIFIED`; Release `NO_GO`.
- **Partial evidence** → preserve each unknown; do not claim Source/Live parity.
- **Complete independently captured evidence** → compare exact installed artifact and DB state to the approved source/tag/ZIP, document differences, then decide root cause and minimum necessary source changes. Verify any change with RED→GREEN tests, independent reviews, staging, deploy and **Live re-test** before marking `Live-Resolved`.

**Required final five truths:** `Repository HEAD` / `Deployed Version` / `DB Version` / `Migration State` / `Live Verification Status`.

**Current status:** Repository `main` README-only; Draft PRs #57/#58 source only; Deployed Version `UNVERIFIED`; DB Version `UNVERIFIED`; Migration State `UNVERIFIED`; Live Verification `NOT PERFORMED`.

> Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
