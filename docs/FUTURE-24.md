# File 12 — Future Digital Reading Intelligence 24

Founder-approved change request for **File 12 — PDF Library and Digital Reading**.

- Change set: `F12-FUT-001` through `F12-FUT-024`
- Candidate: `1.1.0-rc.1`
- DB/contract: `1.1.0`
- Change date: 11 August 2026
- Governing law: latest Founder instruction > latest consolidated central plan > File 12 plan > verified implementation evidence.
- Architectural law: one canonical owner per entity/fact; this change may extend File 12 document/reader state but may not duplicate File 00 identity, File 05 learning truth, File 06 encyclopedia truth, File 16 AI output, communication backends, File 20 shell/navigation, or File 25 global visual-system ownership.

## Approved Future-24 requirements

### F12-FUT-001 — Advanced Reflow Reading Mode
File 12 MUST provide an optional lawful text/reflow representation without modifying the immutable original PDF. It MUST support text size, line height, column width and contrast preferences. If lawful OCR/text is unavailable, the original PDF reader remains the truthful fallback.

### F12-FUT-002 — Read Aloud / Text-to-Speech Reader
The reader MUST expose browser/device text-to-speech for lawful reflow text with start/stop behavior, language-aware utterances and user-rate preference. TTS is a client accessibility feature, not a generated audio publication.

### F12-FUT-003 — Smart Table of Contents & Outline Recovery
File 12 MAY derive an outline from OCR/headings when the source PDF lacks a usable outline. Derived headings MUST be clearly identified as derived and MUST NOT silently rewrite the original PDF.

### F12-FUT-004 — Edition Comparison Laboratory
Authorized readers/reviewers MUST be able to compare two File 12 editions page-by-page using lawful text evidence, similarity and changed-page excerpts. The feature MUST NOT auto-merge distinct editions.

### F12-FUT-005 — Precise Scholarly Anchors
Authenticated users MUST be able to save private anchors containing edition, page, text quote/selector and optional normalized region. Anchors MUST be version/edition aware and private by default.

### F12-FUT-006 — Citation Export Center
File 12 MUST export stable citations in at least Sabri/plain, APA, MLA, Chicago, BibTeX, RIS and CSL-JSON forms while preserving stable File 12 document/edition/page identity.

### F12-FUT-007 — Global Bibliographic Authority Enrichment
Publishing-authorized users MAY query DOI, ORCID and ISBN authority providers through a versioned adapter. External results MUST retain provider/provenance and MUST NOT silently overwrite canonical File 12 metadata.

### F12-FUT-008 — OCR Quality Laboratory
File 12 MUST expose page-level OCR quality, aggregate quality, correction submission, reviewer decision and audit. Corrections create a derived correction layer; the original scan remains immutable.

### F12-FUT-009 — Portable Annotation Standard
Private File 12 bookmarks/notes/highlight anchors MUST have a portable JSON-LD/Web-Annotation-style export/import boundary with strict user ownership and edition access checks.

### F12-FUT-010 — IIIF Digital Library Interoperability
Eligible editions SHOULD expose a rights-aware IIIF Presentation-style manifest with canvases/page identity and short-lived delivery references. Restricted originals MUST NOT become public merely because an IIIF manifest exists.

### F12-FUT-011 — Inside-Book Search Heatmap
Lawful OCR/full-text search MUST be able to return per-page match counts for a visual heatmap and direct page navigation. Access/entitlement checks apply before search results are returned.

### F12-FUT-012 — Encrypted Offline Reading Vault
Where the access policy permits offline use, authenticated users MAY capture encrypted local chunks using a non-extractable WebCrypto key. Local expiry, explicit purge and logout purge MUST be supported. Online-only policy MUST remain online-only.

### F12-FUT-013 — Ultra-Low-Bandwidth Reader
The reader MUST support a data-saver/text-first mode and SHOULD auto-enable it for browser-reported very slow/save-data connections. It MUST preserve reading access without forcing full PDF download before useful text/navigation appears.

### F12-FUT-014 — Multiple Reading Layouts
The advanced reader MUST offer single page, continuous text/reflow, two-page LTR, two-page RTL, horizontal and presentation layouts, with responsive fallback on narrow screens.

### F12-FUT-015 — Personal Smart Shelves
Authenticated users MUST have private shelves such as custom/read-later/current/important collections. Shelf membership is private reading organization, not public popularity or ranking truth.

### F12-FUT-016 — Private Reading Insights — Non-Gamified
Users MAY receive private reading-time, document-use, page-use and completion summaries. Public leaderboards, manipulative streak pressure, shame mechanics and donation/payment-based advantage are prohibited.

### F12-FUT-017 — Cross-Device Reading Session Handoff
Authenticated users MUST be able to resume page/layout/zoom/anchor context across devices using optimistic versioning/conflict protection. Handoff state is private and is not a public analytics projection.

### F12-FUT-018 — Accessibility Quality Inspector
File 12 MUST provide a structured accessibility assessment for an edition, including lawful text/OCR availability and adapter-derived structural findings. A public accessibility badge is permitted only after an authorized verification record, not from an automated score alone.

### F12-FUT-019 — Scholarly Reading Rooms
File 12 MAY create document/page/anchor context records for scholarly rooms. Messages, participants, calls and community discussion remain owned by the canonical communication/community module; File 12 MUST NOT create a duplicate chat backend.

### F12-FUT-020 — Knowledge Context Sidebar
The reader MAY request related canonical knowledge/learning context from companion providers using selected text/page context. File 12 stores no duplicate encyclopedia/course source of truth.

### F12-FUT-021 — AI-Ready Corpus Manifest
File 12 MUST expose a deny-by-default machine-readable corpus manifest for explicitly allowlisted and entitled editions. It includes document/edition/page/chunk identity, citation anchors, rights and hashes; it MUST NOT itself generate AI answers or bypass File 16 governance.

### F12-FUT-022 — Translation & Transliteration Overlay
The reader MAY request translation/transliteration through a derived-text adapter. Output MUST be labeled derived/provider-generated and MUST NOT be represented as the author’s original wording. No permanent canonical replacement occurs without separate editorial review.

### F12-FUT-023 — Digital Preservation Laboratory
File 12 MUST maintain preservation/integrity assessments, checksum generations, derivative status and immutable-original policy. Corruption/integrity failure may quarantine an object; restoration/repair remains governed and auditable.

### F12-FUT-024 — Visual Duplicate & Scan-Fingerprint Detection
File 12 SHOULD compute OCR SimHash and, where lawful preview tooling exists, visual perceptual hashes to identify possible same-scan families despite crop/recompression/watermark changes. Results are review candidates only; automatic merge of editions/scans is forbidden.

## Data additions

The Future-24 schema owns only File 12-native extension data: reader preferences, shelves/shelf items, private reading events, cross-device handoff state, OCR correction records, bibliographic authority cache/provenance, accessibility audit records, room-context references, preservation records and scan fingerprints.

## Provider/degraded-mode law

Optional external authority, translation/transliteration, companion context, reading-room, preservation and accessibility providers MUST be adapters. No configured provider means an explicit degraded/unavailable response, not invented data. Core reading of an otherwise accessible PDF MUST remain available when an optional provider fails.

## Privacy/security law

- Private reading state, shelves, anchors, insights, handoff and offline material are private by default.
- Every object/edition endpoint revalidates current access.
- Offline capture requires offline permission and current user authorization.
- AI corpus access is deny-by-default.
- External enrichment retains provenance and cannot silently become canonical truth.
- No raw protected original or private note is published through public manifests/caches.

## QA and release law

The 24 enhancements are **Specified** when this register is approved, **Coded** when source paths exist and pass review, **Automated-QA Green** only on the exact candidate HEAD, **Staging-Accepted** only after real Hostinger workflows/providers/browser/accessibility/offline/rollback tests, and **Live-Deployed/Operational** only after approved deployment, live re-test and parity/monitoring evidence.
