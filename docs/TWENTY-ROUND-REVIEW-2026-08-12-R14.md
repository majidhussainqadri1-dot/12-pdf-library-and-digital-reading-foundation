# File 12 — Fourteenth Fresh Twenty-Round Corrective Review (R14)

Date: 2026-08-12

## Governing method

This review began from exact repository HEAD `0d4b30b38680ae3fd3c1613b2ce91f4e36d1db8d` after R13. Each numbered round reviewed the corrected repository state produced by the preceding round. A discovered repository defect was fixed and committed before the next numbered round began. Repository evidence is not staging/live evidence.

The review concentrated on the File 12 v1.1 Future-24 requirements for private reading state, portable annotations, source-bound derived data, deny-by-default AI corpus access, scan-fingerprint review evidence, explicit provider degradation, privacy erasure, and DB/provider failure handling.

## Round 1

**Finding:** Private reading-event hourly rate accounting cast a failed `COUNT(*)` DB read to zero, allowing an event mutation when abuse-control state could not be verified.

**Correction:** Added DB-error detection, audit evidence, and `pldr_insight_rate_read` fail-closed behavior before the event insert.

**Commit:** `afe0542b287cf1a155542992078aa64d2cf51ac0`

## Round 2

**Finding:** Reading-insight aggregate DB failure could be projected as a legitimate empty private report.

**Correction:** Aggregate query failure now produces an explicit degraded error and no partial/empty success projection.

**Commit:** `56fc8faa32b794a0225d954f8a8171b5336eb02f`

## Round 3

**Finding:** Completion-state DB failure could silently report zero completed documents.

**Correction:** Completion-state read now fails visibly and prevents a partial report.

**Commit:** `1f329e20a2b7499ed2b1258b5c7e8b3005f67083`

## Round 4

**Finding:** Precise-anchor OCR source DB failure could fall through to an external source-validation provider, confusing unavailable canonical source evidence with ordinary source absence.

**Correction:** OCR DB failure now blocks provider fallback and returns `pldr_anchor_source_read`.

**Commit:** `1986e31535b7d760bf80518ab03235613663e19c`

## Round 5

**Finding:** AI corpus OCR DB failure could produce an empty/partial manifest that looked valid.

**Correction:** Corpus source DB failure now returns explicit degraded failure before chunk projection.

**Commit:** `79fc195ff67dd97b183e8e36e4bb0f6cacc39766`

## Round 6

**Finding:** Scan-fingerprint OCR DB failure could be interpreted as absent OCR and permit incomplete/misleading fingerprint persistence.

**Correction:** Fingerprint computation now aborts before persistence when OCR evidence cannot be read reliably.

**Commit:** `cf2fc185968278d612801cd91a44a87bef2758fd`

## Round 7

**Finding:** Visual fingerprint derivative DB failure could be interpreted as no visual evidence, allowing an OCR-only fingerprint family to be persisted under a DB fault.

**Correction:** Visual derivative read failure now returns `pldr_fingerprint_visual_read`; no fingerprint family is persisted from that failed evidence state.

**Commit:** `7e92bff81f2bc08f504ffc1d5079d562a4b3a464`

## Round 8

**Finding:** Current scan-fingerprint evidence DB failure could be interpreted as an empty/missing family and trigger recomputation; post-compute reread failure could also be masked.

**Correction:** Initial and post-compute current-evidence reads now fail visibly; recomputation/comparison is not performed from an uncertain DB state.

**Commit:** `0f633156a8b8476b94438bfa36baa6a87f309c8d`

## Round 9

**Finding:** Candidate-pool DB failure could return an empty duplicate/scan-family result.

**Correction:** Candidate comparison DB failure now returns `pldr_fingerprint_candidate_read` instead of an empty success.

**Commit:** `b78b956479503524d28ee23c46950757b707eb47`

## Round 10

**Finding:** Knowledge Context marked `truncated=true` whenever exactly the result-limit count was returned, even when the provider supplied exactly that many valid complete results.

**Correction:** Provider-input truncation and valid-result truncation are now measured separately; eligible results are counted through the bounded provider window and `results_truncated` is true only when valid results exceed the output cap.

**Commit:** `ca1b8c760eb6845a478dc8ecca1428ce03cfedc6`

## Round 11

**Finding:** Reflow OCR DB failure could be mistaken for no local OCR and cause external provider fallback.

**Correction:** Reflow now fails closed on OCR DB failure before any provider fallback.

**Commit:** `0a64b8502c04a8823ba625835c60deb0182d2e02`

## Round 12

**Finding:** Reflow OCR total-count DB failure could produce misleading zero/completeness metadata.

**Correction:** OCR count is separately verified and DB failure returns `pldr_reflow_source_count` instead of misleading truncation metadata.

**Commit:** `a66148a287f77689c3e89ce609094d417ac25811`

## Round 13

**Finding:** Outline heuristic OCR DB failure could be projected as a legitimate empty outline.

**Correction:** Outline OCR source read now fails visibly and no empty heuristic success is returned.

**Commit:** `617bfc4369914ec7e1645cb59052108ea053c74f`

## Round 14

**Finding:** Outline OCR total-count DB failure could corrupt completeness/truncation evidence.

**Correction:** Outline OCR total is now separately checked; DB failure prevents misleading completeness metadata.

**Commit:** `524e14ab4129c70d392f0505cd122e583b5e6560`

## Round 15

**Finding:** Edition comparison left-side OCR DB failure could be counted as pages without comparable OCR instead of an infrastructure/evidence failure.

**Correction:** Left-side OCR evidence is read under explicit DB-error checking; failure returns `pldr_compare_left_read` and no partial comparison.

**Commit:** `bb39fb7606799bfbbecdea8300b7e2046e1d3dbe`

## Round 16

**Finding:** Edition comparison right-side OCR DB failure had the same silent-missing behavior.

**Correction:** Right-side OCR evidence now has independent fail-visible DB verification and `pldr_compare_right_read`.

**Commit:** `be31efb6d2ba26bb7ab2ffc27d23fd108f618947`

## Round 17

**Finding:** Private annotation export DB failure could return a valid-looking empty `AnnotationPage`.

**Correction:** Reading-item export DB failure now returns `pldr_annotation_export_read`; no empty export is fabricated.

**Commit:** `9703476cf9122100272f2a6cd4afe68275aa0cac`

## Round 18

**Finding:** Annotation import TextQuote source OCR DB failure could fall through to an external source validator.

**Correction:** Canonical OCR DB failure now blocks provider fallback and returns `pldr_annotation_source_read`.

**Commit:** `6cd5b09ea10cb747faf6cfda741457273276443c`

## Round 19

**Finding:** Annotation duplicate-state DB failure could be treated as “not duplicate” and permit an insert.

**Correction:** Duplicate-state DB failure now blocks the current insert, audits the failure, and reports any earlier batch mutations so reconciliation is explicit before retry.

**Commit:** `d13b3b455c4902a849f30330bfbf52e55e4bb8c6`

## Round 20

**Finding:** Privacy erasure selected at most 50 shelves but performed one unbounded `DELETE` across every item belonging to those shelves, so one eraser invocation could delete an arbitrarily large shelf-item population.

**Correction:** Shelf-item erasure is now ID-batched to `PLDR_Privacy::BATCH`; parent shelves are deleted only after the selected shelves have no remaining shelf items. The erasure remains resumable through subsequent privacy-erasure invocations.

**Commit:** `b912ef97012e98ab80c4ff8a23d3641a1eb9a07a`

## Round distribution

Defect rounds: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20.

Clean rounds: none.

Every defect listed above was corrected before the next numbered review round began.

## Production-truth boundary

R14 establishes repository-level corrective evidence only. It does not establish Hostinger staging/live deployment, DB migration state, runtime configuration, real-provider behavior, browser/accessibility behavior, backup/restore success, or live parity.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
