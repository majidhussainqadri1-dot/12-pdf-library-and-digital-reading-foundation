# File12 R42 — Future REST raw query guard; repository candidate 1.1.0-rc.21 (11 October 2026)

- **Exact parent:** R41 PR #36 head `125be12011d727b85d659ce273d19843533dc0b4` (previous CI does not prove R42).
- **Twenty checks:** 20 distinct requirement/source owner foci, **not** twenty exhaustive E2E reviews. All anchor paths present; only R42 future GET raw query defect groups D01–D03 newly proven. Full ledger: `docs/TWENTY-ROUND-REVIEW-2026-10-11-R42.md`.
- **Corrective batch after all checks:** remove premature Future-24 WordPress numeric/cursor sanitizers and validate ten GET handlers before access/provider/SQL. New actual route/handler fixture `tests/test-r42-future-rest-query-shapes.php`.
- **Candidate:** software `1.1.0-rc.21`, source DB `1.1.0`, source contract `1.1.0`; exact final CI/package digest pending.
- **Deployment parity:** GitHub `main` bootstrap-only `2b369ef2d609247896e1ab32118f1dc498407f51`; active Hostinger plugin, DB, wp_options, migration and companion versions **UNVERIFIED**. Live verification **NOT PERFORMED**. No approved deploy.
- **Known unresolved release blocker:** original File12 inner ZIP folder `12-pdf-library-foundation` versus source `pdf-library-foundation-12`; change-control and staging required.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

---

# File12 — R41 REST request integrity and private tag list truth (11 October 2026)

| Source/release dimension | Verified scope |
|---|---|
| Frozen repository parent | R40 Draft PR #35 exact green HEAD `b1338d4fb7d041cc6c46c3d7bac4814a8f07b584`, Actions #488 complete success |
| Governing attached sources | Original amended File12 v1.1 Future24 **FR001–019, NFR001–010, FUT001–024**; consolidated three central master plans; File05/06/21/23/25; source File00/01/16/17/19/20/24/26/CF04 boundaries |
| R41 distinct twenty focused checks | `docs/TWENTY-ROUND-REVIEW-2026-10-11-R41.md` — 20 requirements-to-source owner foci, **not** 20 exhaustive human reviews or live/staging E2E sessions |
| R41-D01 fixed | `PLDR_REST::register` no longer pre-sanitizes library `page/per_page` with WordPress `absint`, which bypassed R40 strict catalog numeric validation; real registration metadata/handler assertions |
| R41-D02 fixed | `PLDR_REST::reader_manifest` now rejects nested/bool/negative/decimal `edition` before any DB/authorization read |
| R41-D03 fixed | Shared private `PLDR_Reading::parse_stored_tags` distinguishes invalid historic JSON object `{}` from valid indexed empty list `[]`; direct and paginated REST private note outputs fail closed on corrupted historical maps |
| Actual executable test | `tests/test-r41-rest-route-private-tags.php`: actual REST registration, actual malformed catalog/manifest handlers, native and paginated REST private note readers; no DB read on invalid GET and positive valid tags. Runs PHP 8.1/8.3/8.4 |
| Source test baseline retained | R40 126 actual Ingest/Catalog plus 2 explicitly source-only pending branch; R39 70 rights; R38 264 GET/citation; R37 153 reader/deletion +3 UI source; R36 76 private REST; R35 61 native notes; R34 66 grants; R33 109 flags; R31 50 ingest; R32 5 PDF; R28 20×(53 governed IDs +12 source owner adapter anchors)=1,300 static assertions +20 invariants, older crypto/rights/search/range/future tests |
| New software candidate | `1.1.0-rc.20`; **source DB/schema and integration contract `1.1.0` unchanged**, no DB migration |
| Final source proof requirement | Exact final immutable R41 PR #36 HEAD **and matching** SUCCESS Actions on that SHA; installer ZIP SHA256 is distinct from outer artifact digest |
| Unresolved plugin inner root | Plan `12-pdf-library-foundation` versus current build `pdf-library-foundation-12`; Founder-controlled compat upgrade/rollback required |
| Native installed owner code/contract reality | File00/01/05/06/16/17/19/20/21/23/24/25/26 + CF04 actual deployed versions/hash **UNVERIFIED** |
| Hostinger actual File12 PHP/JS version/hash, DB tables/options/migration, scanner/OCR/keyring, browser RTL/a11y/offline, privacy, backup/rollback and staging live retest | **UNVERIFIED**, no approved merge/deploy |

## Five live-first separated realities
**Repository HEAD:** R41 Draft PR #36 final exact SHA once verified by final CI; GitHub `main` is still bootstrap-only `2b369ef2d609247896e1ab32118f1dc498407f51`.  
**Deployed Version:** UNVERIFIED.  
**DB Version:** UNVERIFIED (source contract 1.1.0, not physical DB).  
**Migration State:** UNVERIFIED.  
**Live Verification Status:** NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
