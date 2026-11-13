# File12 R58 — V12.1 54 requirements evidence registry (2026-11-13)

Derived from the FIRST, initial requirement-to-code entries in docs/TRACEABILITY.md for 19 FR + 10 NFR + 24 FUT IDs. Later review tables repeat IDs and were not allowed to override their canonical original names or anchors. The final central F12-CEN-01 is taken separately from the governing V12.1 rights-aware shared-storage rule.

Each row records its original requirement ID, classification, named feature, reported implementation anchor and automated evidence *claim*, then independent source, staging and live statuses. It does not falsely assert that every source anchor is a fully working implementation or that every requirement has passed integrated testing.

All 54 staging and live statuses currently UNVERIFIED. This is not 54 failures. Source status DOCUMENTED_NOT_RUNTIME_ACCEPTED means traceability evidence exists in the repo, not that this registration independently reviewed all core PHP or that original book/page rights have been attested.

To close a requirement: (1) exact source code and native tests, (2) real WordPress + MySQL + owner/File00/05/06/16/17/20/24/25/26 version parity, (3) staged role/rights/OCR/reader execution, (4) independent reviewer and redacted evidence, (5) correct package deploy, (6) live re-test, package/DB checksum parity.

Related, separately versioned 39-test sheet: docs/R57-FILE12-V121-39-STAGING-GATES.csv. Both registers are CI-tested only for identity/count/evidence requirements; a green test here does not set runtime PASS.

Known blocker: File26 owner source contract requires pdf+book_pack, but File12's truthful proposed connector offers pdf only, and rights/tombstone live cutover remains disabled. Original package-folder naming and Hostinger deployed state remain unverified.