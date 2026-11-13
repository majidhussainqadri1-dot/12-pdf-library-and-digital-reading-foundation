# R53 — Scholarly PDF quote provenance correction (2026-11-13)

## Evidence sequence
- R52 parent, SHA `70085803f3f094f045851e800f717b85ba8bb6f2`, software `1.1.0-rc.31`, CI run `43350864121`: 4/4 succeeded on the **old** exact SHA.
- New R53 RED-first test `0d7d8541cf244e5c92213fd52de992e1ff607ffc` in Draft PR #50; GitHub Actions run `43355942223` confirmed FAIL in all three PHP matrices at `Quote with rights evidence different from independently approved mapping denied`.
- Corrective source commit `2079f006778bb4a75031c4ee1638b2b7df27ffdc`: rights-evidence binding on quote approval, quote assertion on each read, File06 canonical owner and exact UUID on approval.
- Candidate unique version `1.1.0-rc.32`; source DB/interface still `1.1.0`.

## Root cause and constrained fix
1. `PLDR_File06_Sources::approve_quote` checked for an approved edition/page mapping but compared neither its `rights_evidence_ref` nor its exact File06 owner/UUID to the requested source proof. A different evidence ref could be approved when OCR happened to match.
2. `quote_assertion` checked the quote approval record and current mapping separately without requiring their legal-rights evidence to be the same. Reapproval of the source-page mapping to a different rights reference could leave an old quote trusted.
3. The original source-approval endpoint correctly checked `file-06` owner/UUID; the quote approval endpoint checked only `published`. Same policy now enforced on both paths.

## Limits of this remedy
- No new schema or previous saved data mutation. Historic quotes with mismatched source rights now fail closed pending individual review.
- File06's actual canonical provider, independent reviewers, legal evidence, original page image, WordPress, MySQL, caches and all rights/takedown paths still require Hostinger staging acceptance.
- OCR matches from `PLDR_Future_Data::ocr_pages` can include reviewed corrections and are not evidence by themselves of original print-image wording. Do not label OCR as a human verified facsimile.
- File26 connector remains proposed and **unregistered**, tombstone/revocation reconciliation blocked; cannot deploy just because CI is green.
- Exact deployed plugin/hash, DB/schema/wp_options, migrations, keyring and live retest **UNVERIFIED**; previous WordPress.com showed disconnected Jetpack and WPVibe HTTP 530 symptom.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
