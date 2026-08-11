# File 12 — Seventh Fresh Ten-Round Corrective Review (R7)

Date: 11 August 2026

Frozen starting HEAD: `aee7d4ae3c6cbf95571f6223596164c55918fca7`

Method: each numbered review was performed on the corrected state produced by the previous round. A discovered defect was corrected before the next review began. Repository evidence remains separate from staging/live evidence.

## Round 1
Defect: Precise `TextQuoteSelector` anchors accepted arbitrary text as long as the caller had edition access; the quoted text was not verified against the requested edition page. This allowed source-unbound scholarly anchors despite the precise-anchor requirement.

Correction: `PLDR_Future_Anchors::save()` now requires a non-empty exact quote for `TextQuoteSelector`, verifies it against page-scoped OCR (or an explicit governed source-validation adapter), and exposes source-verification state.

## Round 2
Defect: The optional preservation assessment provider could throw through the request/cron path, and provider findings were not count-bounded before persistence. A bad provider could therefore disrupt preservation processing or create oversized assessment records.

Correction: provider exceptions are contained and audited; local integrity evidence remains authoritative; provider findings are capped and length-bounded; truncation/failure metadata is persisted and returned.

## Round 3
Defect: Accessibility adapter exceptions were not contained, provider findings were not count-bounded, and an adapter response without provider identity could be accepted with invented `adapter` provenance.

Correction: accessibility-provider failures now degrade safely and are audited, provider findings are capped, missing provider provenance causes external findings to be ignored, and encoding/persistence failures are fail-visible.

## Round 4
Defect: Authenticated users could submit OCR corrections without a server-side frequency ceiling, and an already decided correction could be re-decided repeatedly as long as the caller had review authority and the latest row version.

Correction: OCR correction submissions now have a configurable bounded hourly per-user limit with HTTP 429 semantics. Reviewer decisions are final from `pending` state and use a status+version CAS guard.

## Round 5
Defect: Synchronized reader preferences allowed updates of existing state without an `expected_version`. A stale client could replace the complete preference object after another device had already changed it.

Correction: existing preference updates now require `expected_version`; missing preconditions return 428, stale/missing-version cases return 409, and JSON encoding failures are fail-visible.

## Round 6
Defect: OCR and visual scan fingerprints were persisted independently. If OCR persistence succeeded and visual persistence failed, a partial fingerprint family remained; later candidate lookup could treat the presence of any fingerprint as sufficient and never retry the missing type.

Correction: available fingerprint types are computed first and persisted in one transaction with checked COMMIT. Candidate lookup detects missing expected fingerprint types and retries computation instead of accepting partial state as complete.

## Round 7
Defect: IIIF canvases were generated only from available thumbnail derivatives. Missing thumbnails or failed preview grants silently removed page identities from the manifest, while truncation could still be reported false.

Correction: the manifest now creates bounded canvases from edition page identity, independently of thumbnail availability. Painting annotations are attached only when rights-aware preview grants succeed, and missing/failed previews plus page/canvas truncation are explicitly disclosed.

## Round 8
Defect: Portable annotation import validated the edition-bound source URL but did not verify imported `TextQuoteSelector` text against the target page. Unknown motivations were silently coerced to highlights, allowing source/fidelity bypasses.

Correction: imported text quotes are verified against page-scoped OCR or an explicit governed adapter; unsupported motivations are rejected; empty commenting annotations are rejected rather than silently converted.

## Round 9
Defect: The AI corpus manifest was edition-allowlisted but did not independently enforce the stated `File 16 only through approved contract` consumer boundary. Any currently entitled caller could reach the manifest if the edition allowlist returned true.

Correction: corpus access remains deny-by-default and now also requires an explicit `pldr_ai_corpus_consumer_allowed` approval for the File 16 consumer contract.

## Round 10
Defect: RIS and BibTeX citation exporters interpolated metadata without first eliminating control/newline characters, permitting malformed structured citation records; BibTeX escaping was also incomplete for several structural characters.

Correction: structured citation metadata is normalized to bounded single-line values, URLs are sanitized, and BibTeX structural characters are escaped before export.

## Final R7 accounting

Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**

Clean rounds: **none**

Every discovered R7 defect was corrected before the next numbered review began.

## Production-truth boundary

This record is repository review evidence only. It does not prove Hostinger staging or live deployment, schema state, migration completion, provider configuration, or real browser/workflow behavior.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
