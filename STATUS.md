# File 12 — R35 reconciled source candidate, 11 October 2026

| Evidence gate | Status |
|---|---|
| Baseline | Draft R34 PR #27 exact HEAD `79ef7659ba816f7959313ddf4ded047236685202` |
| Divergent R34 candidate | PR #26 exact HEAD `335952e2f7b47cd7bf95cd184cfa6de7059917ea` — different source under same rc.13 |
| Governing basis | File12 v1.1 amended Future24 + three consolidated master plans + companion File05/06/21/23/25; other File00/01/16/17/19/20/24/26/CF04 only source-side |
| Audit | 20 separate source/plan focus checks, `docs/TWENTY-ROUND-REVIEW-2026-10-11-R35.md`, plus existing 20×65 **static** requirements presence matrix |
| R35-D01 | Legacy persisted download/print/offline strings could be misread as enabled: read-time canonical `policy_flag` enforcement restored |
| R35-D02 | DTO advertised legacy false/string rights as true: DTO synchronized with actual grant enforcement |
| R35-D03 | Explicit anonymous -1 signed grant persisted with mismatched public/user audience hash: canonical user_id 0 token + -1 authorization kept |
| R35-D04 | Differing R34 rc.13 builds reconciled; distinct release identity `1.1.0-rc.14` |
| Kept | PR27 private notes/tag structures + policy explicit-null input guards and previous R1–R34 fixes |
| Actual regression | `tests/test-r35-access-grants.php` actual Core/Access and signed grants, plus prior `test-r34-reading-item-input-shapes.php` |
| Candidate software | `1.1.0-rc.14` |
| Repository schema / integration contract | `1.1.0` / `1.1.0`, no new SQL migration |
| Final R35 exact-HEAD GitHub CI | PENDING |
| Plan vs plugin ZIP root folder | `12-pdf-library-foundation` vs `pdf-library-foundation-12` controlled migration and Founder decision required |
| Hostinger deployed plugin/version/checksum | UNVERIFIED |
| DB physical schema/options & migration | UNVERIFIED |
| Installed counterpart services, Staging, backup/rollback | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |
| Approved merge/deployment | NONE |

**Repository HEAD:** confirm final R35 SHA separately.  
**Deployed Version:** unverified.  
**DB Version:** unverified.  
**Migration State:** unverified.  
**Live Verification Status:** not performed.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
