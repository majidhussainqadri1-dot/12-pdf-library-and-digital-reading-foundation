# File12 R74 — Untrusted provider-output DOM and canonical-link protections
Date: 2026-11-15. **Status: Draft source candidate 1.1.0-rc.49; NOT DEPLOYED.**

## Applicable V12.3 requirements
- File12 F12-FUT-004 (Edition Comparison), FUT-008 (OCR Quality Laboratory), FUT-011 (Inside-Book Heatmap), FUT-020 (canonical Knowledge Context); F12-NFR-001 object/data access and F12-NFR-005 accessibility.
- The Word source describes rights-aware PDF linking and preservation of original metadata. It does not prove external provider input can be trusted as HTML or as a valid URL.

## Source-level reproduction — NOT a production exploit claim
Two isolated VM fixtures execute **actual** plugin JS with artificial provider fields containing `<img src=x onerror=evilR74()>`. They do **not** run a browser exploit and make no real network requests.

1. `tests/test-r74-scholar-dom-source-escaping.cjs`: comparison counts/pages/similarity, OCR quality attributes/page numbers and heatmap aggregate values were interpolated into `innerHTML` sinks. Initial behavior RED GitHub Actions `43704232872`; tool output displays unescaped `<img ... onerror=...>` in assembled markup.
2. `tests/test-r74-personal-context-dom-url.cjs`: companion context UI accepted raw `javascript:` and external `https:` URLs as clickable anchors, although File12 expects canonical same-origin knowledge provider links. The private statistics and a11y score displayed returned values through unescaped HTML. The test initially failed on a fixture-specific relative-url expectation; that harness expectation was corrected, then source behavior RED `43704877211`: unsafe URL `javascript:evilR74()` and external URL recorded.
3. Legitimate relative internal canonical links must remain clickable, and malformed provider data remains visible as **text**, not executable HTML. The source correctness and real provider contract are separate from a Live XSS security finding.

## Candidate correction
- `assets/future-reader-scholar.js`: comparison, OCR quality and search heatmap now construct DOM elements and set API-derived fields with `textContent` or safe attribute APIs; no raw dynamic interpolation into HTML-parser sinks. Invalid numerical quality values receive a bounded CSS display value.
- `assets/future-reader-personal.js`: canonical context URL strictly resolves against the active WordPress origin; only same-origin HTTP(S) without credentials becomes an anchor. An invalid/external scheme remains a visibly unlinked source title with `unverified link unavailable`.
- Private reading insights and a11y score interpolate escaped text, consistent with other already-escaped fields.
- Two isolated RED-first Node VM tests included in frontend CI. Plugin source file inventory stays 58 files, with a *new 3-file immutable rc.49 overlay* rooted in rc.48 hashes; the older inventory and immutable overlays are preserved.

## Evidence boundaries and stop-ship
- GitHub Actions **GREEN** candidate run `43705029615` corresponds to R74 source SHA `a106163a90038e90b38b76666c48e844b5687b98`; final exact latest branch-head CI must still be checked after this documentation and ledger commit. **Only an actual completed success should count as GREEN.**
- No tests have inspected a genuine File06/05 provider result, Hostinger Staging, browser installation, physical DB, File26 live search or private user records. 10 stop-ship categories remain open. Do not merge or deploy from this report alone.
- Source defect IDs R64 through R74: **11** demonstrated source defects, 11 corrections in Draft-only chains, **0** live-verified fixes.
- Header/footer package version `1.1.0-rc.49`; declared DB `1.1.0`; main SHA `2b369ef2d609247896e1ab32118f1dc498407f51` remains README only.

**Repository main HEAD:** `2b369ef2d609247896e1ab32118f1dc498407f51`; **Deployed Version:** UNVERIFIED; **DB Version:** UNVERIFIED; **Migration State:** UNVERIFIED; **Live Verification:** NOT PERFORMED. **Release: NO_GO.**

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
