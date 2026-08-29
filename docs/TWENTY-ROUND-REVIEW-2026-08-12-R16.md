# File 12 — Sixteenth Fresh Twenty-Round Corrective Review (R16)

Date: 2026-08-12
Baseline exact repository HEAD: `be8b2460d39b177ff5384ef191fc180ac3e81ee1`
Governing source: File 12 v1.1 Future-24 amended master plan plus consolidated central plan.

Discipline: every numbered round was performed on the corrected state produced by the preceding round. A proven repository defect was corrected and syntax-checked before the next numbered round began.

1. Core REST mutation exceptions could escape after an idempotency reservation, leaving a request stuck/pending. Added exception containment, abort and safe machine error.
2. Core mutation wrapper accepted an empty Idempotency-Key; reader progress/bookmark/delete keys also had replay semantics that could suppress legitimate later actions. Idempotency is now mandatory and browser keys are unique per mutation attempt.
3. Future-24 mutation wrapper likewise allowed mutation without an Idempotency-Key. It now fails with 428 when the key is absent.
4. Reliable outbox emit could silently fail JSON encoding or DB insert while returning an event ID. Encoding/storage failures are now explicit WP_Error results and logged.
5. Rights-report/decision/appeal/publication/book-pack/status workflows ignored reliable-event persistence failure. They now return committed-state reconciliation errors where applicable.
6. Access-policy mutation ignored reliable-event persistence failure. It now exposes committed policy state requiring reconciliation.
7. Reading-progress mutation ignored reliable-event persistence failure. It now exposes committed progress state requiring reconciliation.
8. Ingest ignored derivative scheduling failure and post-commit ingest/publication outbox failure. These paths are now fail-visible with committed-state reconciliation metadata.
9. Optional supplied cover could leave encrypted storage/object/derivative state partially persisted. Cover metadata is now transactionally linked and committed storage is removed on metadata failure.
10. Cover validation lacked decoded-pixel/decompression-bomb guard and decoded MIME cross-check. Added governed pixel limit and real decoded image verification.
11. Thumbnail generation lacked renderer resource limits and could orphan encrypted thumbnail files/object metadata after DB failure. Added Imagick resource limits, metadata transaction, cleanup and continuation scheduling evidence.
12. OCR provider results could be unbounded, anonymous, partially stored and still marked available/emit OCRReady. Added provider provenance, bounded page/text/total output, checked DB writes, truthful partial/storage status, and event emission only for complete persisted OCR.
13. PDF EOF checking allowed appended payload after the final EOF marker, and clean scanner results could fabricate `adapter` provenance. Appended non-whitespace payload is rejected and scanner provider identity is mandatory for clean results.
14. Core REST document/edition/object reads could turn DB outages into false 404/unavailable responses. Critical reader/document/citation/download/rights wrappers now distinguish degraded DB reads with 503 errors.
15. Future edition authorization and nested shelves/fingerprint responses could mask DB/errors as forbidden or HTTP-200 data; IIIF pre-reads had the same issue. Added fail-visible DB semantics and direct nested error propagation.
16. Audit JSON/DB persistence failures were silent. Audit now returns success/failure and emits a server-log fallback when audit evidence cannot be encoded/stored.
17. Rescan quarantine, clean-scan persistence and document transition writes were unchecked. They now use checked writes/CAS, revoke grants on quarantine, and expose reconciliation failures.
18. Core/Future migration-lock metadata read/takeover/release DB failures were silent or indistinguishable from ordinary contention. Added explicit audit evidence for lock-store failures.
19. Active scholarly reading-room provider state could be returned successfully even when its reliable integration event failed to persist. It now returns a committed-state reconciliation error.
20. AI corpus manifest could treat a document-classification DB failure as a normal missing document after edition authorization. It now denies corpus access with an explicit degraded read error.

Defect distribution: R16 rounds 1–20 all found a proven repository-level defect. No clean R16 round.

Local corrective verification before repository transfer:
- All 36 plugin PHP files: `php -l` PASS.
- All reader JavaScript files: `node --check` PASS.
- Permanent regression markers: `tests/test-twenty-round-review-r16.php`.

Production-truth boundary: this document is repository evidence only. Staging/live deployment parity, deployed DB/schema/migration state, real-role workflows, rollback/restore and Founder acceptance remain separate release gates.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
