# File 12 — Sixth Fresh Ten-Round Corrective Review (R6)

Date: 11 August 2026
Baseline: `66fbeb374dc6a3e7576bf5ed1b2da20a4c5efebd`
Method: each numbered round reviewed the corrected state produced by the prior round. A confirmed defect was fixed before the next round began.

## Round 1
**Defect found.** Future citation export accepted an out-of-range page and did not preserve stable edition identity in its citation key/URL projection. The citation key was document-only.

**Correction.** Added page upper-bound validation; citation keys now include the edition ID; citation URLs carry edition/page identity; every export format returns explicit document/edition/page identity.

## Round 2
**Defect found.** Portable annotation export hydrated all private reading items before slicing; export/import truncation was not fully disclosed; document-level source URLs did not distinguish editions; import collapsed bookmark/comment/highlight motivation into a highlight.

**Correction.** Export now queries at most 1001 records, reports truncation, uses an edition-bound canonical source URL, preserves supported motivation semantics, and import reports total/truncation with a 500-item ceiling.

## Round 3
**Defect found.** Search heatmap could stop at the result-page cap before advancing scan accounting, causing `pages_scanned` to under-report work. Result-cap versus scan-cap truncation was conflated.

**Correction.** Scan accounting is advanced for every retrieved batch, result-page and scan limits are separately governed, and `scan_truncated` / `results_truncated` / aggregate `truncated` are truthful.

## Round 4
**Defect found.** External outline adapters could return arbitrary/unbounded item structures; the public DTO forwarded up to 300 raw provider items without explicit field projection, page validation or payload shaping. OCR fallback truncation was also not disclosed precisely.

**Correction.** Provider output is projected to page/title/level only, page/title/level are validated and bounded, provider identity is bounded, input/returned/truncated metadata is exposed, and OCR fallback reports pages scanned/total/truncation.

## Round 5
**Defect found.** Edition comparison always reported the precomputed maximum as `pages_compared` even when the changed-page result cap terminated the loop early. This overstated completed comparison work.

**Correction.** Actual processed pages are counted; changed-page cap, page-scan cap and aggregate truncation are separately exposed; declared page span and candidate page span are included.

## Round 6
**Defect found.** Cross-device handoff optimistic concurrency was optional. If an existing handoff was updated without `expected_version`, the server silently based the write on the latest row and could overwrite a stale client state, contrary to F12-FUT-017.

**Correction.** Existing handoff updates now require `expected_version` (HTTP 428 when absent), stale versions return 409, and an expected version against a missing row also conflicts.

## Round 7
**Defect found.** Reading-room provider exceptions could escape as runtime failures. A provider could create an external room but a local provider-reference persistence failure had no compensation hook, leaving an orphaned external side effect.

**Correction.** Provider calls are exception-safe and degrade explicitly; absent provider returns a 503 pending state; provider-reference persistence is CAS-scoped; on persistence failure a compensation action is emitted before reconciliation is required. Source URL now includes edition identity.

## Round 8
**Defect found.** Bibliographic authority provider exceptions were not contained, and a corrupt cached JSON record could be returned as an apparently valid empty cached result.

**Correction.** Provider exceptions now return an explicit degraded 503 without canonical mutation; invalid cache rows are discarded/audited; provenance encoding and persistence remain fail-visible.

## Round 9
**Defect found.** Translation/transliteration provider exceptions could escape, and provider output without a provider identity was accepted with the invented fallback label `adapter`, weakening provenance truth.

**Correction.** Provider exceptions now return explicit degraded errors; anonymous provider output is rejected; bounded provider identity, source language, target language and provider-generated labels are returned.

## Round 10
**Defect found.** Knowledge-context provider exceptions could escape and provider arrays were iterated without a pre-projection input ceiling, allowing an adapter to create excessive work before the final result slice.

**Correction.** Provider exceptions/invalid responses degrade explicitly; input is capped at 100 items before projection, only 20 sanitized canonical-context DTOs are returned, and total/limit/truncation metadata is exposed.

## R6 result

Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**

Clean rounds: **none**

Every confirmed R6 defect was corrected before the next numbered round began. Repository/CI evidence remains source-repository evidence only; staging/live acceptance is separate.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
