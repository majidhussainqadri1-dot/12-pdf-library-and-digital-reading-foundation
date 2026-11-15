# R80 — signed browser PDF original identity: source-only security fix

**Date:** 2026-11-15. **Governing document:** attached `Sabri-Website-Master-Plan-V12.3.docx`, File 12 `F12-FR-002` genuine PDF ingest, `F12-FR-009` signed range delivery, `F12-FR-008` operation-rights policy, `F12-NFR-001` authorization and `F12-CEN-01` coherent policy; also R70/R79 verified range continuity. **Status:** DRAFT, NO_GO, no Hostinger deployment.

## Frozen parent/source evidence

- Repository `main` HEAD `2b369ef2d609247896e1ab32118f1dc498407f51`: README-only; it is not the File12 deployed plugin.
- PR #78 parent: exact unmerged PR #77 HEAD `5da553fa31c7f873becf00330c017b61e259bb5c`; plugin `1.1.0-rc.53`, source DB/contract `1.1.0`.
- R80 unmerged source candidate `1.1.0-rc.54`. Immutable two-file source overlay `docs/V123-FILE12-RC54-SOURCE-DELTA-OVERLAY.csv` covers `assets/reader.js` and plugin version entrypoint. Original 58-file rc.43 baseline and previous rc.44–53 delta ledgers remain unchanged.

## Demonstrated source defect

The primary browser's signed download manager enforced exact HTTP 206 Content-Range and byte count, and for <=128MiB a whole-file digest, but **did not independently validate PDF MIME, safe PDF file extension, or the first original file bytes**. `d.mime_type` supplied the Blob type. A deliberately crafted `text/html` original declared as PDF, even with SHA-256 faithfully calculated for that HTML content and valid 206 ranges, was delivered as if it were PDF. This is a *reproduced source/client vulnerability in a synthetic fixture*, not evidence of a successful upload to the live backend. Server-side ingest already performs further PDF structure checks and should remain authoritative. Large browser downloads >128MiB currently do not independently compute the full digest.

**RED-first source proof:** GitHub Actions run `43715968695`, `frontend-package`, test `tests/test-r80-reader-pdf-original-integrity.cjs` failed: `HTML-disguised original must never be saved, even when SHA-256 matches`.

## Correction and negative tests

- Require `delivery.mime_type` equal to `application/pdf` after trimming/case normalization, and a bounded safe `.pdf` filename. Apply **again on every refreshed grant**, preserving R79 current-URL continuation and R70 exact hash/size identity.
- Validate first five assembled original bytes are precisely `%PDF-` as soon as available across byte ranges; stop early and purge cached untrusted chunks and signed session on wrong header. Never create a browser download for an invalid header, even when declared hash matches the invalid content.
- Construct final browser Blob with constant `application/pdf`; retain strict 206 Content-Range, byte length and <=128MiB whole-file SHA-256.
- Reset prior PDF-header verification whenever renewed object identity or a whole-file digest mismatches. A new download session must not inherit the preceding document's header approval. Extended Node VM test exercises a mismatched first SHA-256, then a newly signed HTML object and confirms fail-closed behavior.
- Synthetic real-reader JavaScript VM fixtures verify: header-bearing PDF accepted; signed HTML bytes with matching digest rejected **before remaining ranges**; bad MIME rejected; `.html` filename rejected; invalid renewed MIME refused. Prior R70 and R79 synthetic original fixtures changed from 12 ASCII bytes to a 12-byte `%PDF-`-prefixed test original so previously valid protocol cases remain valid.
- Full physical PDF structure parsing, malware scanner, encrypted object restore, CF04 sandbox and large original >128MiB streaming digest are **not** proven by this narrow client guard; an adversarial PDF could retain a valid header. Do not call this a comprehensive PDF malware or DRM defense.

## Acceptance and remaining blockers

Source CI proof must use *the final exact PR head SHA*. No assumption that WordPress synthetic CI or Chromium screenshot tests equal Hostinger live tests. Source defects R64–R76, R78–R80 = **16** recorded, 16 Draft-only fixes, 0 Live-proven fixes; R77 is a separate read-only evidence improvement, not a new defect. The **10 stop-ship categories** still require: reviewed release integration, Hostinger exact plugin/runtime checksum, physical DB/migration freeze, install directory migration, File26 PDF+Book Pack owner decision and tombstone ACK/index/cache purge, CF04 actual encrypted/scanned private storage/restore, lawful original PDFs/OCR, real Staging acceptance, two independent reviews, Founder signoff, rollback rehearsal, controlled deploy and live parity re-test. V12.3 Stage and Live 77/77 acceptance items remain unverified separately from other overlapping tests.

**Five mandatory realities:** Repository HEAD `2b369ef2d609247896e1ab32118f1dc498407f51`; Deployed Version **UNVERIFIED**; Physical DB Version **UNVERIFIED**; Migration State **UNVERIFIED**; Live Verification Status **NOT PERFORMED**. Release **NO_GO**.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
