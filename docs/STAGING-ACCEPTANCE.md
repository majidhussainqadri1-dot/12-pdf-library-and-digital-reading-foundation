# File 12 staging acceptance checklist — 1.1.0-rc.1 Future-24

A release may not be called staging-accepted until all items below have fresh evidence on the actual Hostinger staging environment.

1. Freeze exact deployed package/version/checksum, WordPress/PHP/MySQL/LiteSpeed configuration and File 00/01/05/06/16/19/20/24/25/26 dependency versions.
2. Prove fresh install and legacy 0.2.0/1.0.0-rc.1 upgrade, core + Future-24 schema idempotency, migration locks, bounded resume, source/object counts, SPL2 key/storage compatibility and no silent data loss.
3. Configure backed-up PLDR key ring; prove encrypt/decrypt, rotation coexistence, missing-key fail-closed behavior and isolated restore/decrypt from backup.
4. Configure private storage outside public document root; prove direct URL/path access fails.
5. Configure malware scanner; test clean, infected, MIME mismatch, truncated/polyglot, password/encrypted source and large lawful PDF paths.
6. Verify Founder/admin/verified doctor/ordinary member/guest/suspended/entitled/non-entitled authorization and IDOR negative cases.
7. Verify public/account/entitled/assigned, embargo, online-only, download/print/offline rights and token revocation after rights changes.
8. Verify signed delivery expiry/replay/audience/object/operation binding, HTTP Range/suffix range/HEAD, weak-connection resume and cache behavior.
9. Verify core reader page navigation, jump, zoom, fit, fullscreen, thumbnails, keyboard, screen reader fallback, focus order, 200%/400% zoom, 44x44 targets, 320px through desktop, Urdu/Arabic/RTL and no horizontal overflow.
10. Configure lawful OCR provider; verify OCR quality metadata, full-text search, access filtering, spelling/transliteration adapter behavior and provider-degraded state.
11. Verify private progress/bookmarks/notes/highlights across devices plus export, erase, legal hold and no public cache/index leakage.
12. Verify takedown temporary restriction, evidence, decision, appeal, restore/remove, token revocation and downstream event reconciliation.
13. Register representative Founder-owned and licensed/public-domain Book Content Packs; validate checksum/provenance/update manifest and File 05/06/16/21/26 consumption adapters.
14. Verify outbox idempotency, retry, dead-letter, File 19/21 events, File 20 shell and File 25 visual component placement without duplicate ownership.
15. Run integrity sampling, object quarantine, cache/search rebuild, token cleanup and safe repairs.
16. Prove database + object storage + key/config backup as one consistent set; isolated restore with counts/checksums/decrypt/cache/index rebuild; target RPO <=24h and RTO <=8h or stricter approved values.
17. Measure p75/p95 budgets under representative catalog/large PDF loads; verify bounded queries and no fatal memory behavior.
18. Execute rollback rehearsal that preserves post-cutover data, restores previous owner/read/write behavior and reconciles downstream events.

## Future Digital Reading Intelligence 24 staging gates

19. **Reflow/TTS/low-bandwidth/layouts:** verify lawful OCR-only reflow, unavailable fallback, text size/line height/column width/contrast, Web Speech start/stop/language, data-saver auto/manual mode, single/continuous/two-page LTR/two-page RTL/horizontal/presentation layouts and responsive fallback.
20. **Smart outline + edition comparison:** verify derived outline is visibly labeled derived; compare same/different editions, missing OCR and access-denied cases; prove no automatic merge or source mutation.
21. **Precise anchors + annotations:** verify private text-quote/page/region anchors, JSON-LD annotation export/import, cross-user denial, edition access change, deletion/export and no public cache leakage.
22. **Citation center + authority enrichment:** validate Sabri/APA/MLA/Chicago/BibTeX/RIS/CSL-JSON output and stable IDs; configure DOI/ORCID/ISBN provider adapter, provider outage, cache expiry/provenance and no silent canonical overwrite.
23. **OCR Quality Laboratory:** verify page heatmap/aggregate scores, user correction submission, reviewer approve/reject, immutable source, audit and unauthorized review denial.
24. **IIIF + search heatmap:** validate eligible public and restricted manifests, short-lived delivery references, no rights widening, OCR match counts and direct-page navigation; verify a restricted edition never leaks OCR text or object URLs.
25. **Encrypted Offline Vault:** verify offline-allowed success, online-only denial, expired rights, local-expiry purge, logout purge, incomplete/tampered local chunks, browser without WebCrypto/IndexedDB, slow network resume and server reauthorization on recapture.
26. **Smart Shelves + private insights + handoff:** verify shelf CRUD, duplicate protection, private-only visibility, non-gamified metrics, retention, cross-device page/zoom/layout restore, concurrent-version conflict handling and privacy erasure.
27. **Accessibility/rooms/context/derived text/AI corpus:** verify accessibility automated assessment versus human verification badge; scholarly-room context does not create a duplicate messaging backend; knowledge context consumes File 05/06 providers; translation/transliteration is labeled derived; corpus manifest is deny-by-default, patient-case guarded, entitlement checked and does not generate AI answers.
28. **Preservation + scan fingerprints:** verify checksum-generation records, corrupted-object quarantine, immutable original, preservation adapter degraded mode, OCR SimHash and visual hash behavior where Imagick is available, false-positive review cases and proof that scan-family candidates never auto-merge editions.

29. Complete two independent fresh review/fix rounds after the final Future-24 candidate package; no known Critical/High blockers may remain.
30. Founder functional/visual/copy/business/safety acceptance; then production deploy, smoke test, monitoring window and parity confirmation.
