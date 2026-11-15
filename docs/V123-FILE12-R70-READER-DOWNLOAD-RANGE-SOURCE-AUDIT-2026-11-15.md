# File 12 — R70 source-level HTTP Range and object-identity hardening
Date: 2026-11-15

## Scope and provenance

- Exact R69 parent: `8ed340c0ffe61985bdbe8e49b369ef7c468200da`, **unmerged Draft PR #65**, source version `1.1.0-rc.44`.
- New **Draft PR #66**, **not live**, source `1.1.0-rc.45`; declared source DB/contract `1.1.0`.
- Current `main`: `2b369ef2d609247896e1ab32118f1dc498407f51`, README only.
- This is an isolated source flaw; exact Hostinger deployed plugin, physical schema/migration and runtime remain **UNVERIFIED**.

## Reproduced source defect R70

In `assets/reader.js`, `runDownload()` previously accepted any `response.ok` or HTTP206 for a requested byte range. It appended `response.blob()` without comparing `Content-Range` to the requested start/end/size or the returned byte count to the expected range. If the signed token returned 403/410, it requested a fresh `downloads/session` but did **not** require the renewed object `size + sha256` to match the original partial download. The full browser SHA-256 was skipped for files greater than 128 MiB; therefore full-body cryptographic integrity could not always detect mixed/unexpected ranges at the client.

**Impact class:** download corruption/trustworthiness and potentially mixing byte ranges across versions. **No Live data compromise or exploit is asserted.**

## RED-first evidence

- First two commits: `af03e1e7ed8d1983e105b5ee3d986987bcd88def` and `1b38d16dc910047aba2c50f672158ff3d620b3cc`.
- GitHub Actions `43684888800`, `frontend-package` failed on R70 as expected:
  `AssertionError: http-200 must stop at the first untrusted range`.
- Isolated Node VM runs **actual reader JS** with synthetic public object bytes and signed-range responses, no production account, PDF, keys or data.
- Five scenarios: valid HTTP206; illegal HTTP200; incorrect Content-Range; truncated range; 403 plus renewed grant for changed SHA-256.

## Corrected source candidate

- `verifyDownloadRange()` requires exact 206 + `Content-Range` start/end/full-size + exact body byte count **before appending**.
- After refresh, require unchanged object size + SHA-256; on mismatch discard buffered partial bytes, clear session and insist on restart.
- Validate signed download manifest digest and bounded range size before processing.
- Small/medium (at most 128 MiB) complete downloaded blob requires browser SHA-256, else fails closed. Large downloads truthfully disclose that the **full client** checksum was **not** verified; authenticated byte-range and count checks are not mislabeled as end-to-end SHA-256.
- New version label `rc.45`, unchanged schema; rc.43 original 58-blob source inventory and rc.44 overlay are immutable. New two-file rc.45 overlay anchored to rc.44 hashes, with exact candidate hashes/byte sizes and source audit status `SOURCE_DELTA_AWAITING_INDEPENDENT_REVIEW`.

## Non-goals and remaining blockers

Not a full multi-gigabyte streaming implementation, no external library, no live/deploy claims, no changes to File26 or CF04 owner contracts. A complete Hostinger Staging QA of actual large downloads (including interruption, rights revocation, retry, browser compatibility and multi-GB memory/cost) remains required before approval. The candidate `1.1.0-rc.45` is **Draft / NO_GO** until all gate evidence is available.

**Truth fields:** Repository HEAD main `2b369ef2d609247896e1ab32118f1dc498407f51`; candidate PR #66; Deployed Version **UNVERIFIED**; DB Version **UNVERIFIED**; Migration State **UNVERIFIED**; Live Verification **NOT PERFORMED**.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
