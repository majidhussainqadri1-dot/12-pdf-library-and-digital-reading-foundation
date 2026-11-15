# R71 — File12 V12.3 Encrypted Offline Vault source integrity hardening
Date: 2026-11-15

## Proof boundary
- Base: Draft PR #66 at exact source SHA `7b58c4d8a13d8849277a72df0ae76ec6e863fdc9`, candidate software `1.1.0-rc.45`.
- New stacked Draft PR #67, code `1.1.0-rc.46`, source DB/contract `1.1.0`, no schema change.
- Main remains README-only at `2b369ef2d609247896e1ab32118f1dc498407f51`.
- Hostinger deployed package, physical DB, migration state, true rights grants and browser-installed frontend are **UNVERIFIED**. This does NOT establish a Live incident or an installed production correction.

## Reproduced SOURCE flaw
- `assets/future-reader-vault.js` sent byte-range requests but previously accepted HTTP 200 and any `response.ok` without requiring actual 206 + exact `Content-Range` or exact returned byte length.
- Captured chunks were AES-GCM encrypted locally, but `meta.sha256` merely copied the server-advertised checksum. The client did **not compare the source plaintext SHA-256** at capture or decrypt/open. Individual AES-GCM chunk integrity does not prove source-file digest agreement if incorrect bytes were encrypted from the start.
- The precise risk is source/edition mix-up, proxy truncation, stale range or changed content admitted as a 'stored' offline book. No private-data exfiltration or Hostinger exploit is claimed.

## Reproduction
- RED commit `0415bee09e4ef9410b364f399d5d17e1fd86ccb7`, GitHub Actions run `43685678429`: frontend fixture intentionally failed `AssertionError: http-200 must not persist a false verified offline copy`.
- Test executes the **actual shipped JS** in Node VM, with real WebCrypto AES-GCM and a synthetic in-memory IndexedDB. No original books, keys or user information were used.
- Cases: correct synthetic 206 and SHA; illegal HTTP200; mismatched Content-Range; short payload; wrong SHA; missing manifest digest.

## Source correction
- Require bounded authenticated manifest: positive integer size, SHA-256 hex, valid future offline expiry, valid URL, cap 128 MiB while WebCrypto SHA256 needs bounded full plaintext.
- Fail closed on response !=206, invalid Content-Range or byte count; no manifest is committed without full original-plaintext SHA-256 comparison.
- Reopen locally encrypted contents only after verifying exact decrypted total byte count and full original SHA-256, plus strict index/offset order and per-chunk AES-GCM.
- Purge edition-local partial content on invalid capture or corrupt-open; preserve older R4/R20 exact regression invariants.
- Maintain nonextractable encryption key and original local rights/expiration policy. No new external provider or duplicate backend.
- Immutable source inventory is chained: original rc.43 58 plugin blobs -> rc.44 three-file overlay -> rc.45 two-file overlay -> new rc.46 two-file overlay; source deltas remain unreviewed by two independent human reviewers.

## Scope limits and operational gates
- Honest 128 MiB client limit is a **new operational restriction**; future >128 MiB offline books require a separately reviewed streaming digest/storage design, not skipping digest verification.
- No claims for original licensed books, 1 GB transfer, actual Hostinger staging/mobile/browser offline permissions, safe background tabs, true rights revocation, full WCAG or rollback.
- Release **NO_GO** until exact deployed-state freeze, File26 PDF+BookPack owner agreement and indexed tombstone ACK, CF04 storage/scanner/key/restore, 39+54+10+77+60 real gate evidence, independent reviewers and Founder approval.

**Repository main HEAD:** `2b369ef2d609247896e1ab32118f1dc498407f51`; **Deployed Version:** UNVERIFIED; **Physical DB:** UNVERIFIED; **Migration State:** UNVERIFIED; **Live Verification:** NOT PERFORMED.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
