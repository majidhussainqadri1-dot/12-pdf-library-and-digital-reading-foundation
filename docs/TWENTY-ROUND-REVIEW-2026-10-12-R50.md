# File 12 R50 — twenty separate scoped source inspections and File16 AI corpus citation-integrity repair
**Audit date:** 12 October 2026 PKT. **Immutable source parent inspected:** R49 PR#44 `7f5c33499adbf701f61f25c7300f86b8095a6d3a`, whose earlier CI run `38256997757` passed only on the **R49** source SHA. `main` remains bootstrap-only `2b369ef2d609247896e1ab32118f1dc498407f51`. R50 final HEAD/CI must be separately frozen.

## Supplied source authority actually inspected
- *File 12 PDF Library and Digital Reading Complete Master Plan 2026 v1.1 Future-24 Amended 2026-08-11*, FR001–019, NFR001–010 and especially **F12-FUT-021 — AI-Ready Corpus Manifest**: deny-by-default and allowlisted **and entitled** edition, stable chunk/page/citation/rights/hash identity; File12 document/PDF/OCR provenance source, AI answers and corpus consumption **File16** ownership. Plan §11.6 denies unlawful/offline/AI access bypass and private patient-case content without separate approval.
- *Sabri Social Homeopathy Platform Three Central Plans Consolidated Governing Master Plan 2026*: one canonical owner per document/page/rights fact, File12 original binary/document truth, File16 AI/generation consumer, File00 identity and entitlement, File24 security and privacy, File26 search/classification; no duplicate AI answer backend or medical prescription authority in File12.
- Supplied companion File05 Learn, File06 Encyclopedia, File21 News and Home, File23 Publishing and File25 UI provide source-level reader/citation and UI contracts, not proof of installed dependent code or real Hostinger parity.

These **20 separate bounded topical plan-to-source checks** were completed against frozen R49 before modification. Rounds 1–18 mostly establish named method anchors and are NOT independent full-repository line-by-line reviews or real WordPress E2E tests. Rounds 19–20 include actual File16 corpus/native query/cursor source and SQL-derived OCR page identity reasoning. No method-anchor presence is claimed as functional completion.

## Frozen twenty check results
| Review | Governing area | Concrete source focus | Frozen finding |
|---|---|---|---|
| 01 | F12-FR001 | ACL-filtered catalog / File26 | Catalog/search source anchors found |
| 02 | F12-FR002 | Valid PDF scan and intake / CF04 | Ingest/scanner anchors found |
| 03 | F12-FR003 | Legal source-rights / File24 | Rights/cases anchors found |
| 04 | F12-FR004 | Edition lineage | Core edition source anchors found |
| 05 | F12-FR005 | Duplicate fingerprinting | Fingerprint source anchors found |
| 06 | F12-FR006 | Encrypted PDF object | Crypto source anchors found |
| 07 | F12-FR007 | Authorized OCR/derivatives | Ingest/OCR anchors found |
| 08 | F12-FR008 | Rights/entitlement / File00 | Access/calendar anchors found |
| 09 | F12-FR009 | Signed delivery and byte range | Grant/range anchors found |
| 10 | F12-FR010 | Inline reader / Files20/25 | Reader/thumbnail anchors found |
| 11 | F12-FR011 | Private reading progress | Reader progress anchors found |
| 12 | F12-FR012 | Private bookmarks and notes | Native tag/item anchors found |
| 13 | F12-FR013 | Rights-filtered OCR discovery / File26 | OCR search/cursor anchors found |
| 14 | F12-FR014 | Edition-page citations / File06 | Citation export anchors found |
| 15 | F12-FR015 | Learning/Encyclopedia context / Files05/06 | Context consumer anchors found |
| 16 | F12-FR016 | Publishing outbox / Files21/23 | Outbox/owner registration anchors found |
| 17 | F12-FR017 | Takedown and appeals / File24 | Appeal/restriction anchors found |
| 18 | F12-FR018/019 | Preservation and accessible metadata | Preservation anchors found; prior A11y source boundary retained |
| 19 | F12-FUT-021 | File16 signed AI Corpus page cursor | R50-D01: HMAC valid but page type could be coerced by absint |
| 20 | F12-FUT-021 | File16 OCR chunk-page citation identity | R50-D02: OCR historical page/order outside edition accepted into corpus projection |

## Two proven source defects: one post-review corrective batch
**R50-D01 — signed File16 corpus cursor type coercion.** Previous `PLDR_Future_Corpus::decode_cursor` verified HMAC and entitlement context but then `absint($decoded['p'])` coerced a signed payload containing a negative, fractional, string or boolean page into a different page. A signed malformed cursor could thus point at a different original page. This is source-format/defense-in-depth integrity, NOT evidence of external HMAC bypass: signature checking was already present. **Fix:** require canonical URL-safe base64 payload, JSON object of exactly `p` and `c`, positive PHP integer page, exact current context string and canonical encoded bytes; malformed but properly signed cursor fails with `pldr_corpus_cursor` before OCR reads. Native generated cursors continue to work.

**R50-D02 — historical OCR page data could corrupt File16 chunk/citation identity.** `PLDR_Future_Corpus::manifest` read SQL-derived `ocr_pages` rows and directly cast `page_number` to int, producing a page/citation/hash chunk even when an invalid page, duplicate/out-of-order row, page <= cursor, or page beyond current canonical edition could appear through historical state, erroneous adapter or data corruption. **Fix:** verify **all source rows including the lookahead page** are canonical positive integer page values (MySQL decimal string allowed), strictly increasing after cursor and <= `edition.pages`. Fail closed with auditable `pldr_corpus_source_integrity`, 503 degraded, **no partial manifest or next cursor**. Native data/index owner unchanged. The File16 consumer receives only bound, current-edition page identity and source text remains absent from the metadata-only manifest.

**New native executable regression:** `tests/test-r50-file16-corpus-cursor-source.php` executes actual PHP `PLDR_Future_Corpus::manifest` and private HMAC cursor methods under a controlled WP/DB/corpus fixture. Covers valid canonical pagination; signed malformed negative/decimal/bool/string/list/object cursors; wrong owner, invalid signatures; source OCR out-of-range/duplicate/descending/behind-cursor and lookahead corruption; no partial result or OCR request for wrong cursor. Full PHP 8.1/8.3/8.4 and ZIP CI must pass on R50 **exact final SHA**. Isolated fixture is not real File16 connected staging, security penetration testing or live verification.

**Source software** `1.1.0-rc.29`; source DB/schema and integration contract `1.1.0` unchanged; no physical DB migration or plugin deployment.

## Still-blocked staging and five distinct live truths
Original written plugin root `12-pdf-library-foundation` versus built candidate ZIP root `pdf-library-foundation-12` remains an explicit Founder-controlled install/rollback compatibility gate. Hostinger active File12/runtime checksum, actual physical MySQL tables/wp_options/schema/migration/row versions, installed File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 contracts, File16 actual connected corpus consumer, rights/patient case tests, backup/keyring/scanner, IDOR/RTL/offline/weak-network, two fresh staging review/retest cycles, Founder signoff, merge, production deploy and live parity retest ALL **NOT VERIFIED**.

| Mandatory separate reality | Status |
|---|---|
| Repository HEAD | Frozen R49 `7f5c33499adbf701f61f25c7300f86b8095a6d3a`; R50 exact HEAD/CI pending final check |
| Deployed Version | UNVERIFIED |
| DB Version | UNVERIFIED actual DB (source schema 1.1.0 only) |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
