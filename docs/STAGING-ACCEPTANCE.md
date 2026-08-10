# File 12 staging acceptance checklist

A release may not be called staging-accepted until all items below have fresh evidence on the actual Hostinger staging environment.

1. Freeze exact deployed package/version/checksum, WordPress/PHP/MySQL/LiteSpeed configuration and File 00/01/19/20/24/25 dependency versions.
2. Prove fresh install and legacy 0.2.0 upgrade, schema idempotency, migration lock, bounded resume, source/object counts, SPL2 key/storage compatibility and no silent data loss.
3. Configure backed-up PLDR key ring; prove encrypt/decrypt, rotation coexistence, missing-key fail-closed behavior and isolated restore/decrypt from backup.
4. Configure private storage outside public document root; prove direct URL/path access fails.
5. Configure malware scanner; test clean, infected, MIME mismatch, truncated/polyglot, password/encrypted source and large lawful PDF paths.
6. Verify Founder/admin/verified doctor/ordinary member/guest/suspended/entitled/non-entitled authorization and IDOR negative cases.
7. Verify public/account/entitled/assigned, embargo, online-only, download/print/offline rights and token revocation after rights changes.
8. Verify signed delivery expiry/replay/audience/object/operation binding, HTTP Range/suffix range/HEAD, weak-connection resume and cache behavior.
9. Verify reader page navigation, jump, zoom, fit, fullscreen, thumbnails, keyboard, screen reader fallback, focus order, 200%/400% zoom, 44x44 targets, 320px through desktop, Urdu/Arabic/RTL and no horizontal overflow.
10. Configure lawful OCR provider; verify OCR quality metadata, full-text search, access filtering, spelling/transliteration adapter behavior and provider-degraded state.
11. Verify private progress/bookmarks/notes/highlights across devices plus export, erase, legal hold and no public cache/index leakage.
12. Verify takedown temporary restriction, evidence, decision, appeal, restore/remove, token revocation and downstream event reconciliation.
13. Register representative Founder-owned and licensed/public-domain Book Content Packs; validate checksum/provenance/update manifest and File 05/06/16/21/26 consumption adapters.
14. Verify outbox idempotency, retry, dead-letter, File 19/21 events, File 20 shell and File 25 visual component placement without duplicate ownership.
15. Run integrity sampling, object quarantine, cache/search rebuild, token cleanup and safe repairs.
16. Prove database + object storage + key/config backup as one consistent set; isolated restore with counts/checksums/decrypt/cache/index rebuild; target RPO <=24h and RTO <=8h or stricter approved values.
17. Measure p75/p95 budgets under representative catalog/large PDF loads; verify bounded queries and no fatal memory behavior.
18. Execute rollback rehearsal that preserves post-cutover data, restores previous owner/read/write behavior and reconciles downstream events.
19. Complete two independent fresh review/fix rounds after the final candidate package; no known Critical/High blockers.
20. Founder functional/visual/copy/business/safety acceptance; then production deploy, smoke test, monitoring window and parity confirmation.
