# File 12 — Independent Review/Fix Log

## Review/Fix Round 1

Fresh adversarial review found and corrected: scanner-unavailable publication could become public; legacy SPL2 plaintext checksums were not reliably reconstructed; legacy/new key rings were not merged; recurring cron could be scheduled before the custom recurrence existed; bundled Book Packs were re-scanned on every request; appeal cases could become undecidable; restore could publish a quarantined object; unpublished reviewer preview was blocked; multi-range requests fell back incorrectly; all PDF `/Encrypt` detection was bounded to only the first 4 MiB; large page counts could create an unbounded thumbnail DOM; explicit print/highlight controls and native interaction adapter slot were missing; legacy progress/notes/reports were not migrated.

All listed defects were corrected and syntax checks were rerun.

## Review/Fix Round 2

Fresh review concentrated on access replay, Range semantics, rights-state transitions, migration idempotency, public/private output, weak-provider behavior, pagination bounds, reader keyboard/RTL, destructive uninstall, secrets, and package ownership. Corrections retained fail-closed publication on missing malware evidence, bounded preview rendering, explicit expected-version transitions, non-destructive legacy preservation, token revocation on state change, and a no-secret/non-binary repository boundary.

No known unresolved source-level Critical/High defect remains from these two review passes. Runtime/staging defects remain discoverable and are explicitly not pre-claimed as absent.
