# File 12 Status — 1.0.0-rc.1

| Gate | Status |
|---|---|
| New central + File 12 plan reconciliation | Complete in candidate scope |
| Coded | **Candidate complete** |
| Deterministic package | **GitHub Actions build + ZIP integrity gate green** |
| Static / automated QA | **GitHub Actions green on the exact candidate HEAD after each source/status change** |
| PHP compatibility QA | **PHP 8.1 / 8.3 / 8.4 green** |
| Cryptographic range/tamper QA | **Green** |
| New-plan contract + secret scan | **Green** |
| Independent Review/Fix Round 1 | Completed; defects corrected |
| Independent Review/Fix Round 2 | Completed; defects corrected |
| Hostinger staging | **Pending** |
| Live deployed | **Not claimed** |
| Operational | **Not claimed** |

## External/runtime acceptance dependencies

The code deliberately fails or degrades safely when required runtime evidence/providers are absent. In particular, public publication requires a **clean malware scan result**; OCR and thumbnails truthfully expose provider-degraded states; encrypted storage/key health is checked; and staging must prove backup/key recovery rather than infer it from configuration.

The CI artifact is named `file-12-pdf-library-1.0.0-rc.1`; its artifact identifier/digest are runtime evidence produced by the corresponding GitHub Actions run and therefore are not frozen into this source-status file.

## Legacy continuity

The candidate can read prior `SPL2` encrypted objects, migrates legacy documents in bounded batches, computes authenticated plaintext checksums where keys are available, migrates progress/bookmarks/private notes and legacy reports, leaves historical source data intact, and emits a canonical interaction-migration request for legacy save/reaction/comment reconciliation rather than silently duplicating interaction ownership.

## Production-truth boundary

This status does **not** convert repository evidence into Hostinger truth. Fresh install/upgrade/migration, private storage and key recovery, real provider configuration, real-role/browser/accessibility/RTL workflows, backup/restore/rollback, Founder acceptance, production deployment and live re-test/parity remain separate gates.
