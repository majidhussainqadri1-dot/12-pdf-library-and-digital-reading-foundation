# File 12 Status — 1.0.0-rc.1

| Gate | Status |
|---|---|
| New central + File 12 plan reconciliation | Complete in candidate scope |
| Coded | Candidate complete; fresh CI required |
| Deterministic package | Built by CI; local build checked |
| Static/unit candidate QA | Local green; GitHub Actions is authoritative after push |
| Review/Fix Round 1 | Completed locally; defects corrected |
| Review/Fix Round 2 | Completed locally; defects corrected |
| Hostinger staging | **Pending** |
| Live deployed | **Not claimed** |
| Operational | **Not claimed** |

## External/runtime acceptance dependencies

The code deliberately fails or degrades safely when required runtime evidence/providers are absent. In particular, public publication requires a **clean malware scan result**; OCR and thumbnails truthfully expose provider-degraded states; encrypted storage/key health is checked; and staging must prove backup/key recovery rather than infer it from configuration.

## Legacy continuity

The candidate can read prior `SPL2` encrypted objects, migrates legacy documents in bounded batches, computes authenticated plaintext checksums where keys are available, migrates progress/bookmarks/private notes and legacy reports, leaves historical source data intact, and emits a canonical interaction-migration request for legacy save/reaction/comment reconciliation rather than silently duplicating interaction ownership.
