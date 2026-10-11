# File 12 R36 — source fixes, required release gates

| Gate | Status |
|---|---|
| Latest reviewed baseline | Draft PR #28 exact `90dd4768ded28842b93c3b953a48e0a747f32890` |
| Divergent sibling | Draft PR #29 exact `834d635aa70d7c2de78fe0e6b54d67d3cca3d11e` (different source under same rc.14) |
| Governing source | File12 amended v1.1 (19 FR, 10 NFR, 24 FUT), consolidated three central master plans, File05/06/21/25 companions and File12-side partner contracts |
| 20-pass audit | 20 different focused code/plan inspections in `docs/TWENTY-ROUND-REVIEW-2026-10-11-R36.md` (not 20 staging/full-depth E2E runs) |
| R36-D01 | REST paginated private tags insufficient integrity checking; native and REST now call shared valid_stored_tags flat bounded string-only guard |
| R36-D02 | Native owner-scoped `delete_item` falsely returned true when DB deleted 0 private rows; strict 1-row success |
| R36-D03 | Reconciled divergent rc.14 source identifiers by unique rc.15 candidate |
| New actual-class regression | `tests/test-r36-private-reader-rest-parity.php` PHP fixture native/REST private JSON tags, deletion and ownership |
| Earlier coverage | R28 20×65 static anchors, R29 catalog, R30 embargo, R31 ingest, R32 PDF, R33 rights, R34 grants, R35 private reading fixture retained |
| Candidate software | `1.1.0-rc.15` |
| Source DB/schema/contract | `1.1.0` unchanged, no R36 migration |
| Exact final SHA CI | PENDING fresh matching run |
| Plan vs plugin ZIP root | `12-pdf-library-foundation` vs `pdf-library-foundation-12` requires founder-approved staged change-control |
| Staging/installed File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04 | UNVERIFIED |
| Hostinger deployed code/ZIP SHA, WP runtime | UNVERIFIED |
| Physical DB/options/migrations | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |
| Founder approval, main merge/live deploy | NOT DONE |

**Repository HEAD:** verify latest R36 source SHA after final commit.
**Deployed Version:** unverified.
**DB Version:** unverified.
**Migration State:** unverified.
**Live Verification Status:** not performed.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
