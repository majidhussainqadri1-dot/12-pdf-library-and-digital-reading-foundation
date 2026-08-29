# File 12 — Fifth Fresh Ten-Round Corrective Review (R5)

Date: 2026-08-11

Baseline exact HEAD: `2c0a794a9203d0b90415a0fb7a35e20777442c4c`

Method: each numbered round reviewed the corrected repository state produced by the immediately preceding round. Where a defect was proven, the owning source was corrected before the next round began. Repository evidence remains distinct from staging/live truth.

## Round 1

**Defect found.** Bulk OCR retrieval and the page-unspecified reflow path could load/return an unbounded OCR corpus. This could create excessive memory/response pressure for very large books.

**Correction:** `PLDR_Future_Data` now imposes a bounded bulk OCR ceiling and a smaller reflow window, while single-page reflow remains page-scoped. Reflow now discloses OCR page totals and truncation instead of implying completeness.

Correction commit: `6dc36b68f79fc62e741c21ea25bbab49821e99b2`.

## Round 2

**Defect found.** Accessibility scoring loaded the full OCR text corpus merely to calculate count and average quality. That made a metadata-quality operation scale with document text size.

**Correction:** accessibility scoring now uses aggregate SQL (`COUNT`/`AVG`) and does not hydrate OCR text content. The result exposes OCR pages assessed and average quality.

Correction commit: `8b84e9cb7c43ba09f459150ffcb12f73c6e8727e`.

## Round 3

**Defect found.** Translation/transliteration source verification scanned the OCR corpus until it reached the requested page, despite the operation being page-bound. Provider output length/provider identity also lacked explicit result bounds.

**Correction:** source verification now requests exactly the selected OCR page; derived provider output and provider identity are explicitly bounded and empty provider output fails visibly.

Correction commit: `4b03fd6d9d3ac48b93e8c6234702ad09dedc727c`.

## Round 4

**Defect found.** Reading-room anchor verification repeated the same corpus-wide OCR scan even though the room context is bound to one page.

**Correction:** reading-room text-anchor verification now retrieves exactly one requested OCR page and preserves the explicit adapter override only when local source verification cannot establish the anchor.

Correction commit: `8a9e1526c1fe151462fa4cba4b2513a9b1b6191f`.

## Round 5

**Defect found.** The OCR Quality Lab returned an unbounded heatmap for every OCR page. Corrections were capped but the response did not disclose total/truncated state.

**Correction:** heatmap and correction payloads now have explicit limits plus returned/total/truncated metadata. Aggregate quality statistics remain complete without hydrating the full OCR text layer.

Correction commit: `6c2c46ccca2bb16ef473737e54b9564b3db29776`.

## Round 6

**Defect found.** Smart Shelf deletion lacked a version-CAS delete and did not verify transaction commit. Removing a non-existent shelf item could also report success, and duplicate add requests reset `added_at` through `REPLACE` semantics.

**Correction:** custom-shelf deletion now uses version-CAS plus fail-visible COMMIT; item removal verifies existence and exactly one deletion; adds use `INSERT IGNORE` and truthfully disclose newly-added versus already-present state.

Correction commit: `80482e0e41d7bf2c40b6750b1a99c2522b84120f`.

## Round 7

**Defect found.** The IIIF manifest always exposed a full-PDF `rendering` link using a `read` grant. That bypassed File 12's explicit `download_allowed` policy boundary at the IIIF contract level.

**Correction:** full-PDF IIIF rendering is now emitted only when the current access policy allows download, and the token is issued for the `download` operation. The manifest explicitly reports whether download rendering is allowed.

Correction commit: `acd7416336b4e997c627dbf309ad54cec6fae340`.

## Round 8

**Defect found.** Cross-device handoff creation treated a simultaneous first-write race as a generic storage failure, and public method responses exposed raw storage-shape fields including encoded anchor JSON.

**Correction:** concurrent initial creation is now detected and returned as a conflict with the current handoff; update storage failure is distinguished from CAS conflict; API-level handoff responses are normalized and decode the anchor into the public DTO.

Correction commit: `89bf06a1c82f0d916120ae6e26fc0c57500a9ea1`.

## Round 9

**Defect found.** Private reading-insight events had no server-side ingestion ceiling. A malfunctioning or hostile authenticated client could therefore create unbounded high-frequency event rows independent of normal UI cadence.

**Correction:** reading-event ingestion now enforces a configurable, bounded hourly per-user ceiling using the indexed `user_created` access path and returns an explicit 429/retry contract when exceeded.

Correction commit: `4bdce2c85152ce000c7cfb45c4ba78bcb9a09a52`.

## Round 10

**No new repository-level defect proven.** Re-reviewed the corrected Future-24 data access boundaries together with AI corpus allowlisting/pagination, bibliographic authority-provider degradation/provenance, preservation quarantine/CAS behavior, and existing Future migration/schema-lock safeguards. No additional source patch was justified by this round.

## R5 result

Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9**

Clean round: **10**

All defects proven in R5 were corrected before the following numbered round began.

## Production-truth boundary

This record proves repository review/fix activity only. It does not prove staging installation, deployed artifact parity, deployed DB/schema/migration state, provider configuration, browser/runtime workflows, backup/restore, production deployment, or live behavior.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
