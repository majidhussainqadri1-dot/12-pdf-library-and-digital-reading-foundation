# File 12 R48 — twenty governing source reviews, then historically stored Handoff and File17 scholarly quote integrity
**Date** 12 October 2026, Pakistan. **Frozen inspected R47 exact HEAD:** `95f15514a67b7eea92a63748cda333797e909205`; previous successful GitHub Actions `38225041349` belongs only to that original R47 SHA. Source `main` `2b369ef2d609247896e1ab32118f1dc498407f51` remains README-only bootstrap. New R48 code/CI/package must be verified independently.

## Original written source authority / scope
- Supplied **File12 PDF Library and Digital Reading Complete Master Plan 2026 v1.1 Future-24 amended 2026-08-11**, with FR001–019, NFR001–010, FUT001–024. `F12-FUT-017` explicitly promises user-private cross-device page/layout/zoom/anchor resume with optimistic version and conflict protection. `F12-FUT-005` defines source-bound edition/page/text-quote/region scholarly anchors; `F12-FUT-019` limits File12 reading-room role to document/page/anchor context, while communications/members/calls belong to File17. §11 session_handoffs/room_contexts and private-data protections require canonical identity and fail-closed projections.
- Supplied **Three Central Plans Consolidated Governing Master Plan 2026**: File00 identity/entitlement owner, File05 education, File06 encyclopedia/citation, File17 messaging/room owner, File20 shell, File21 publishing, File23 editor, File24 privacy/security and File25 UI, File26 discovery, CF04 media. User-supplied companion File05, File06, File21, File23 and File25 written plans inform ownership only; physical installed contracts/versions are unverified.

## Twenty frozen checks before correction
Each is a **distinct bounded requirement-to-repository-source inspection** of frozen R47 code, not twenty complete independently rerun E2E reviews. In reviews 1–18, presence of source methods/contracts was checked; for 19–20 native PHP logic was inspected for shape/source integrity. "Source path" is NOT a claim that installed plugins work or that all requirements are complete.

| Check | Governing requirement and owner | Reviewed source code focus | Finding |
|---|---|---|---|
| 01 | F12-FR001; File26 | Reader/Search catalog and access-bounded cursor | source path |
| 02 | F12-FR002; CF04 | PDF ingest/real scanner verification | source path |
| 03 | F12-FR003; File24 | rights cases/owner decisions | source path |
| 04 | F12-FR004 | canonical document edition/version | source path |
| 05 | F12-FR005; FUT024 | fingerprint/distinct-scan candidates | source path |
| 06 | F12-FR006 | protected/encrypted PDF originals | source path |
| 07 | F12-FR007 | permissioned OCR/derivatives | source path |
| 08 | F12-FR008; File00 | rights-calendar/entitlements | source path |
| 09 | F12-FR009 | signed operation/object range delivery | source path |
| 10 | F12-FR010; File20/25 | accessible responsive reader presentation | source path |
| 11 | F12-FR011; File00 | private reading state/progress | source path |
| 12 | F12-FR012 | private notes/bookmarks/tags | source path |
| 13 | F12-FR013; File26 | OCR retrieval/cursor policy | source path |
| 14 | F12-FR014; File06 | per-page scholarly citations | source path |
| 15 | F12-FR015; File05/06/16 | learning/encyclopedia/AI context contracts | source path |
| 16 | F12-FR016; File21/23 | canonical publishing outbox | source path |
| 17 | F12-FR017; File24 | appeals and temporary takedown | source path |
| 18 | F12-FR018/019 | preservation and accessibility owner boundaries | source path |
| 19 | F12-FUT017; File00 | historical persisted cross-device resume DTO | R48-D01: historic anchor/page canonical checks missing |
| 20 | F12-FUT005/019; File17 | TextQuoteSelector source identity at room creation | R48-D02: exact quote missing but accepted |

## Two proven source discrepancies repaired only after review
**R48-D01 — historic reading-session payload may bypass newer strict write validation.** `PLDR_Future_Handoff::save/anchor` validates new typed page and anchor inputs; but `get` previously called `dto`, and `dto` accepted any historical `anchor_json` that `json_decode(...,true)` returned as an array. It could project a nonempty numeric-list, unsupported SVG, missing TextQuote exact, wrong Fragment page, malformed/misbound region or unknown extra private keys, and historical page out of edition, inconsistent with the canonical write boundary. **Fix**: both get/save now pass current `edition['pages']` into `dto`. The DTO differentiates actual JSON object from nonempty positional list, verifies valid edition page, and runs existing `anchor` validator. It rejects records if canonical validation would change/remove fields (no silent normalization/truncation), logs `handoff_anchor_corrupt` and returns `pldr_handoff_corrupt` without partial handoff. Legitimate empty legacy anchors and native valid RTL/selector/region JSON remain readable; no destructive migration or edits to stored data.

**R48-D02 — false TextQuote selector into File17.** `PLDR_Future_Rooms::anchor` rejects SVG/unknown types but allowed `TextQuoteSelector` without a nonblank `exact` excerpt. `anchor_belongs` returned true when no quote texts existed, allowing a source-less scholarly quote identity into the File17 provider context. **Fix**: reject missing/blank `exact` with `pldr_room_exact` before any room row/provider call, matching `PLDR_Future_Anchors` and `PLDR_Future_Handoff`. Page-only rooms, valid TextQuote/Fragment/Css remain supported.

**New native PHP regression:** `tests/test-r48-stored-handoff-and-room-quote.php`, using R46 isolated WordPress/DB fixture, calls the actual private stored `dto` via Reflection and actual `PLDR_Future_Rooms::create/anchor` methods. Tests invalid historical scalars/lists/object, SVG/missing quote/region/fragment page, page outside edition, legitimate empty JSON and valid RTL/quote/region, no write on malformed incoming room. Must run across PHP8.1/8.3/8.4 and retain older suites.

**Source software candidate** `1.1.0-rc.27`. Source DB/schema and integration contract `1.1.0` unchanged. No live plugin, physical database or migration modified.

## Remaining cross-owner and release gates
- Exact Hostinger active plugin files, version/hash/WordPress runtime/errors; installed File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 package and interface parity, `wp_options` and physical tables/version/rows/migrations UNVERIFIED.
- Actual site backups/keyring/OCR/scanner and restore, File17 real provider/compensation, historical invalid handoff records, File00/permissions/IDOR, mobile/RTL/cross-device/weak network, two fresh staging cycles, Founder approval, deployment and live re-test NOT PERFORMED.
- Written plan inner plugin folder `12-pdf-library-foundation` vs source ZIP inner `pdf-library-foundation-12` still Founder-controlled upgrade/rollback gate; no silent rename.

| Required separate live truth | Status |
|---|---|
| Repository HEAD | Frozen R47 `95f15514a67b7eea92a63748cda333797e909205`; new R48 final SHA/CI must be recorded separately |
| Deployed Version | UNVERIFIED |
| Physical DB Version | UNVERIFIED (source-only schema 1.1.0) |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
