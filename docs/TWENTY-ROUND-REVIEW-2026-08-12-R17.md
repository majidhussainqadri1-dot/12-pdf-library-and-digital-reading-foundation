# File 12 — Seventeenth Fresh Twenty-Round Corrective Review (R17)

Date: 12 August 2026

## Frozen review baseline

R17 began from the exact R16 install artifact produced by GitHub Actions Run #252 for repository HEAD `f76921ddb57e44ef5bf882761410a239f86c5419` (artifact ID `9131522228`). The artifact was extracted and reviewed sequentially. Each proven defect was corrected and syntax-checked before the next numbered review began. The final corrected source state was then transferred back to the PR branch and is subject to the permanent R17 regression contract plus the full exact-HEAD quality gate.

## Twenty sequential corrective rounds

1. **Catalog access filtering — defect.** Edition/access database failures could silently omit catalog records. Added fail-visible edition and authorization read errors.
2. **Reading progress authorization — defect.** Authorization/edition database failures could be misclassified as forbidden/missing. Added explicit degraded errors.
3. **Private reading-item creation — defect.** Authorization/edition database failures could be misclassified. Added fail-closed source-read errors.
4. **Private item listing authorization — defect.** Authorization database failure could yield an empty private list. Added fail-visible authorization failure.
5. **Private item tag integrity — defect.** Corrupt stored `tags_json` could silently become an empty tag list. Corruption is now audited and the list fails without partial data.
6. **Reading workspace authorization — defect.** Authorization database failure could silently hide workspace cards. Replaced filtering with explicit fail-visible authorization checks.
7. **Public document/reader source reads — defect.** Document, edition, object, access and DTO-policy database failures could become ordinary not-found/restricted states. Added explicit error-state handling.
8. **Reader policy consistency — defect.** Reader permissions were derived from repeated policy reads, allowing inconsistent snapshots/failure masking. Reader now uses one verified policy snapshot and one verified interaction DTO.
9. **HTML reader preview grants — defect.** A reader page could issue excessive thumbnail delivery grants. Grant issuance is capped at 50 while later page navigation remains available.
10. **Idempotency request binding — defect.** Idempotency keys were not bound to the exact request payload/target. Added canonical request fingerprints and 409 conflict on key reuse with different requests.
11. **Anonymous idempotency isolation — defect.** Anonymous callers shared actor `0` key namespace. Anonymous keys are now privacy-hashed/scoped by caller context without storing raw address/user-agent data.
12. **Authority lookup POST — defect.** Provider/cache/rate side effects were not under idempotency. Authority POST now uses the governed idempotent mutation wrapper.
13. **Offline grant POST — defect.** Retry could mint duplicate delivery grants. Offline grant issuance is now idempotent.
14. **Derived-text POST — defect.** Retry could repeat provider/rate effects. Derived translation/transliteration POST now uses idempotency.
15. **Preservation GET semantics — defect.** Preservation GET could invoke providers and persist/verify state. GET is now read-only; privileged refresh is a separate idempotent POST.
16. **Accessibility GET semantics — defect.** Privileged refresh via GET could invoke provider/persist audit. GET is read-only; refresh moved to an idempotent POST.
17. **Fingerprint candidate GET semantics — defect.** Candidate GET could compute/persist missing fingerprints. GET is now read-only and requests the existing idempotent fingerprint POST when evidence is missing.
18. **Core REST authorization database failures — defect.** Document/reader/items/citation authorization failures could be misclassified as ordinary 403/404. Added explicit 503 source-read errors.
19. **REST reading-item tag integrity — defect.** Paginated REST still silently normalized corrupt private tags. It now rejects corrupt stored tags and returns no partial page.
20. **Owner-state/outbox atomicity — defect cluster.** Several event-driven mutations committed owner state before the reliable outbox record, contradicting the File 12 transaction/outbox invariant. Rights reports/decisions/appeals/status/publication, access-policy updates, reading progress, book-pack registration, ingest/publish readiness, reading-room activation, OCR-ready state and legacy migration event points now persist the relevant owner-state transition and reliable outbox event in the same database transaction; event persistence failure rolls back the corresponding canonical state transition. External provider compensation/revocation remains post-commit where appropriate and is surfaced separately.

## Distribution

- Defect-bearing rounds: **1–20**
- Clean rounds: **none**
- Every proven defect was corrected before the next numbered round began.

## Evidence boundary

This is repository/artifact review evidence. It does not establish Hostinger staging or live parity. Staging still requires exact artifact deployment, deployed checksum/version/schema/migration inspection, real-role/IDOR/privacy/rights/browser/offline/RTL/accessibility/provider-outage/backup/rollback evidence and Founder acceptance.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
