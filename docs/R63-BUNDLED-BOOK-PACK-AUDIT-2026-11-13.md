# File12 R63 — Bundled Book Content Pack source audit + symlink refusal (2026-11-13)

## Exact RED-first and correction evidence
- Parent R62 `05d8aceb3e5eb30c920e9afd7c2105ac1babc5e0`, source candidate rc.41; GitHub Actions run `43377674982` 4/4 success; standalone fixture includes 10 negative malformed identity checks and one valid controlled transactional registration.
- New R63 RED SHA `4907fcf967e400e42beca6217fa88d0e57f01b35`: GitHub Actions `43378011293`, three PHP matrix failures at missing safe audit for `bundled_manifest_invalid_json`.
- Narrow source fix `3863cb7011aec7a2691714478523949b42f03451`; R61 static private-import source assertion updated for changed safe-call formatting in `2dca9c4b614a647a6406ba0bd7d921aaf1e840d9`. Prior source errors and unchanged installed code are not claimed live.

## Root cause / corrected behavior
The plugin activation bundled importer scanned controlled `book-packs/*.json` files, allowed `file_get_contents` to follow symlinks, ignored invalid JSON and discarded the `WP_Error` returned by the private `persist_authorized` writer. This made defective bundle omission invisible to operators and permitted off-directory symlink reading when a writable plugin directory were compromised.

The fixed source uses `is_link` to refuse linked files, `is_file/is_readable/filesize` before reading, bounded source content, explicit invalid JSON audit, and structured rejected manifest audit. Machine error codes and `sha256(basename)`-derived short references are recorded; never raw source text, secrets, or private data. This is not a guarantee against arbitrary filesystem attackers or races: real deployment directory ownership/immutability remains an owner safety requirement.

## Source-only and release limits
Version `1.1.0-rc.42`, schema/contract `1.1.0` (source only). Canonical bundle and rights management still needs actual WP+MySQL install, legitimate old + new pack fixtures, File26 `book_pack` owner consumer, legally reviewed rights, scanner and private storage, recovery and true staging/production parity.

Stop-ship: Issue #23 in File26 for PDF/book_pack contract, File12 Issue #53 for versioned rights/tombstone consumer acknowledgement; Hostinger Jetpack disconnected / WPVibe 530 symptoms, not root cause; all 39 staging tests and live verification remain unverified.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
