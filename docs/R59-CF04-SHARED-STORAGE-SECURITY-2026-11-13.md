# R59 — V12.1 STO-01..10 shared security and CF04 ownership (2026-11-13)

Original governing V12.1 source: File12 completion plan section 10, page 10, reflects 10 new common storage/security controls. CF04 is the canonical media processing, private encrypted quarantine, scanner/sandbox, derivative and delivery owner; File12 retains library item, edition/page, rights decision and reader domain truth.

Source comparison: CF04 repository majidhussainqadri1-dot/cf-04-central-media-processing-secure-delivery has main HEAD 0294442f0fddd1ca5440d9d5ac992ba80aced972 and independent Draft PR #5 exact HEAD af8098374de9aa8f7b0910f5c9cf24121a1b491f, runtime candidate 1.3.0-rc.1, declared schema/contract 1.5.0. CF04 README explicitly states runtime disabled/fail-closed pending real provider/staging/migration/restore/approval. These source facts do not establish current Hostinger installed CF04.

10 controls STO-01..10 are listed individually in docs/R59-V121-CF04-STO10-SHARED-SECURITY-GATES.csv. Each has actual owner, acceptance scenario, dependency and empty evidence. All 10 Hostinger Staging and Live states are UNVERIFIED. This is NOT 10 observed failures. The new PHP CI only tests identity/count/anti-false-pass schema.

Critical shared integration acceptance: verify File12/CF04 installed versions, actual private object and DB records, real scanner fail-closed, no patient-record uploads into public library, rights revocation including search and CDN purge, immutable originals and lawful derivatives, and a real isolated DB+objects+keys restore. CF04 itself warns that local do_action dispatch does not prove external delivery; do not treat File12 outbox sent as File26 rights tombstone consumer acknowledgement (File12 issue #53).

Do not merge/deploy File12 or activate File26 while CF04 runtime, owner contracts, book_pack search type, rights-versioned tombstones, and the original 39 Staging gates remain unverified.