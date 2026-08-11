# File 12 — Independent Review/Fix Log

## Core plan completion — Review/Fix Round 1

Fresh adversarial review found and corrected: scanner-unavailable publication could become public; legacy SPL2 plaintext checksums were not reliably reconstructed; legacy/new key rings were not merged; recurring cron could be scheduled before the custom recurrence existed; bundled Book Packs were re-scanned on every request; appeal cases could become undecidable; restore could publish a quarantined object; unpublished reviewer preview was blocked; multi-range requests fell back incorrectly; all PDF `/Encrypt` detection was bounded to only the first 4 MiB; large page counts could create an unbounded thumbnail DOM; explicit print/highlight controls and native interaction adapter slot were missing; legacy progress/notes/reports were not migrated.

All listed defects were corrected and syntax checks were rerun.

## Core plan completion — Review/Fix Round 2

Fresh review concentrated on access replay, Range semantics, rights-state transitions, migration idempotency, public/private output, weak-provider behavior, pagination bounds, reader keyboard/RTL, destructive uninstall, secrets, and package ownership. Corrections retained fail-closed publication on missing malware evidence, bounded preview rendering, explicit expected-version transitions, non-destructive legacy preservation, token revocation on state change, and a no-secret/non-binary repository boundary.

No known unresolved source-level Critical/High defect remained from these two core review passes. Runtime/staging defects remained discoverable and were explicitly not pre-claimed as absent.

## Future Digital Reading Intelligence 24 — Review/Fix Round 1

The first fresh review of the 24-feature expansion checked package loading, feature registration, REST surface, client assets, ownership boundaries and the exact planned feature list. It found a release-blocking integration defect in the assembled candidate: the initial Future-24 bootstrap referenced four non-existent aggregate class files while the actual implementation had been split into dedicated feature classes. The same review found that the secondary Scholar/Personal/Vault clients and the Future-24 stylesheet were present in the intended implementation set but were not wired through the canonical loader.

Corrections made:

- Replaced the stale aggregate-file loader with the exact dedicated Future-24 class list and a fail-visible missing-class guard.
- Added explicit Future-24 schema/bootstrap readiness reporting instead of silent partial activation.
- Enqueued `future-reader.js`, `future-reader-scholar.js`, `future-reader-personal.js` and `future-reader-vault.js` in dependency order.
- Added the responsive/RTL/reduced-motion Future-24 stylesheet and advanced-reader layout states.
- Added `F12-FUT-001` through `F12-FUT-024` governing documentation, requirements-to-code traceability, staging acceptance gates and a dedicated automated contract/negative-boundary test.
- Expanded CI to lint all Future-24 PHP and JavaScript and build the exact `1.1.0-rc.1` package.

The corrected exact candidate then passed GitHub Actions run #6 at head `d440ac638e65792f60ff4476ff920e96871629fa` before the second review fix.

## Future Digital Reading Intelligence 24 — Review/Fix Round 2

The second fresh review focused on privacy/authorization revalidation, offline rights behavior, local encryption, provider-degraded modes, annotation/corpus boundaries, no-auto-merge protection, accessibility verification, and browser failure paths. It found one concrete reliability defect in the encrypted offline vault: the IndexedDB request helper used an async Promise executor, so a failed `indexedDB.open()` could reject outside the outer Promise and leave the reader operation hanging instead of producing a controlled failure.

Correction made:

- Rewrote IndexedDB request/transaction handling so open/request/abort failures reject deterministically, database handles close in `finally`, and the UI receives a controlled error rather than an unresolved operation.

No second duplicate chat, AI, encyclopedia, course, shell, public-UI or canonical bibliographic backend was introduced by Future-24. External authority/translation/context/room functions remain adapter boundaries; AI corpus manifests remain deny-by-default; scan fingerprints remain review candidates only and cannot automatically merge editions.

A fresh exact-head CI run is required after this Round-2 fix. Hostinger/browser/runtime acceptance remains a separate mandatory gate.
