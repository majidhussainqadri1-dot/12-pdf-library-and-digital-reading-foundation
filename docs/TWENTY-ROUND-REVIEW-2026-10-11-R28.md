# File 12 — R28 Twenty-Pass Entire Plan-to-Code Regression and Corrective Review
Date: 2026-10-11 (Pakistan Standard Time). Source baseline: R27 PR #15 `0c9ba8ba4091234183e057145d0f9a21bcaff7b9`.

## Documentary authority

The File 12 v1.1 Future Digital Reading Intelligence 24 amended master plan (11 August 2026), the *Three Central Plans Consolidated Governing Master Plan*, the actual File 05 Learn, File 06 Encyclopedia, File 21 Home/News, File 23 Publishing Dashboard, and File 25 UI master plans were inspected as written. The governing source includes F12-FR-001…019, F12-NFR-001…010, F12-FUT-001…024, canonical owner/adapters, privacy, free education, legal/rights constraints and platform release truth.

**Critical distinction:** This is a complete 20-iteration *automated static plan-to-source matrix* plus targeted human code-path reviews. Every loop in `tests/test-r28-twenty-full-plan-sweeps.php` checks **all 53 requirement IDs and 12 source-side companion adapter anchors**, then checks a distinct additional code invariant. This is 1,300 repeated static-anchor assertions plus 20 additional integrity checks. It does not mean twenty independent human line-by-line audits of every file, twenty runtime WordPress tests, or 20 actual Hostinger/Staging deployments. Automated source presence is only source evidence, not functional acceptance.

## Per-round closed audit and post-review correction ledger

| Round | Whole source matrix | Distinct targeted review / after-completion correction | Defect at freeze |
|---:|---|---|---|
| 01 | Cross-plan matrix/source owner paths reviewed | Compared *both distinct R27 branches*, `a369a852...` and `0c9ba8ba...`; only the first had delivery-time original/derivative revalidation. **After completing review:** ported the exact fail-closed binding check and its executable R27-derived test into R28, without losing the other branch's REST/IIIF correction | **R28-D01: FIXED IN SOURCE** |
| 02 | Reader/rights/security matrix reviewed | `PLDR_Reader::reader_html` still issued a read grant before private reading state and access-policy DB errors. **After full review:** moved successful state/DTO/policy preflight before PDF and thumbnail grant issuance; added focused invariant test | **R28-D02: FIXED IN SOURCE** |
| 03 | All release/source package markers reviewed | Two *divergent R27 candidates* had distinct code bytes but both called themselves `1.1.0-rc.6`; cannot treat these artifacts as identical. **After complete review:** advanced source/package candidate to `1.1.0-rc.7`; DB schema and contract stay at `1.1.0` | **R28-D03: FIXED IN SOURCE** |
| 04 | Root repository documentation checked against code | Root `README.md` still represented `1.1.0-rc.3`/R24 despite R28 source. **After review:** corrected software identity, lineage and disclaimers | **R28-D04: FIXED IN SOURCE** |
| 05 | F12-FR-001…005: catalog, 16 types/categories, bibliographic IDs, ingest and dedupe | Matrix and exact owner/source code anchors present; real sample-data test not yet independently reproduced | No additional proved source defect |
| 06 | F12-FR-003/008/017: rights/license/embargo/takedown/appeal | Current source checks present; legal evidence and expiry transition must be verified at staging | No additional proved source defect |
| 07 | F12-FR-006/009/018: private object encryption, keyring, authenticated byte ranges, derivative binding | Source + existing crypto/range and R28 detached-derivative regression present; real key restoration still unverified | No additional proved source defect |
| 08 | F12-FR-010/019: native HTML reader plus API manifest, full pregrant checks | Explicit R27/R28 order assertions; accessible screen reader/UI remains unverified live | No additional proved source defect |
| 09 | F12-FR-011/012: private reading progress, annotations, shelves, session handoff | Source owner checks present; multi-device concurrent authors not tested on staging | No additional proved source defect |
| 10 | F12-FR-013/014: OCR and metadata search, cursor/citation provenance | Source anchors present; File 26 real indexer and rights freshness unverified | No additional proved source defect |
| 11 | F12-FUT-010: IIIF preview and rights check before issuing any delivery token | R27 pregrant ordering preserved; actual IIIF browser/provider acceptance outstanding | No additional proved source defect |
| 12 | F12-NFR-001/002: File 00/24 claims, IDOR, legal hold, private export/erase | Source checks present; suspended/minor/jurisdiction/account integration requires real counterpart | No additional proved source defect |
| 13 | F12-NFR-003: outbox events, retries/dead letter, producer/consumer identity | Source checks present; downstream File 19/21 consumer delivery runtime unverified | No additional proved source defect |
| 14 | F12-NFR-007/008: core/Future schema, migration lock, safe rollback, repair | Repository source markers only; actual deployed DB/version/migration state not frozen | No additional proved source defect |
| 15 | F12-FUT-001…024: complete Future Digital Reading registry + representative implementations | All 24 stable IDs remain in source; provider/browser/human acceptance not inferred | No additional proved source defect |
| 16 | File 05/06/16: lessons, encyclopedia, allowlisted AI corpus, private patient-case boundaries | Source-side filters/adapters present; companion installed version/API trust still unverified | No additional proved source defect |
| 17 | File 01/20/25: route registry, immersive shell, component/visual tokens | Source-side contract hooks present; real shell navigation and 320–1920px RTL still need staging | No additional proved source defect |
| 18 | File 17/21/23, File 26, CF-04: room contexts, document publishing/dashboard links, source-bound search and secure binary | Static owner/event hooks are available; actual companion package, event consumer and CF-04 activation not demonstrated | No additional proved source defect |
| 19 | F12-FUT-012/018/023: encrypted offline, accessibility human verification, preservation fingerprints | Source contracts present; cross-device encrypted vault/revocation/a11y/key restore still unverified | No additional proved source defect |
| 20 | Whole-FR/NFR/Future+adapter repeat, release checksum/QA and zero-claim gate | Matrix source regressions and PHP 8.1/8.3/8.4 workflow scheduled; deployment remains separate | No additional proved source defect |

## Repeatable full-matrix test

**`tests/test-r28-twenty-full-plan-sweeps.php`** checks all 19 functional, 10 non-functional and 24 advanced Future stable IDs plus 12 source adapter contracts on **every one** of 20 passes, with one additional targeted invariant per pass. Separate executable regressions cover detached grants and HTML reader pregrant order; the R27 core/IIIF pregrant test remains retained.

## Unresolved real acceptance and governance issues

1. **Exact deployed code remains unknown.** Repository main has initial README only. R25/R26/R27/R28 development branches are unmerged. Green CI cannot prove a Hostinger install.
2. File 12 written governing package-folder name is `12-pdf-library-foundation`; longstanding plugin candidate ZIP folder is `pdf-library-foundation-12`. This mismatch is **not silently renamed**, because staged deactivation/reinstallation, route/data/key/storage compatibility and Founder change-control approval are required.
3. All external owner versions and contracts — File 00,01,05,06,16,17,19,20,21,23,24,25,26 and CF-04 — need fresh actual staging verification. Source-side hooks are not cross-repo End-to-End parity evidence.
4. External OCR/scan/rights/provider conditions, patient-case safeguards, age/guardian/suspension policies, real roles/IDOR, WordPress UI, browser/RTL/assistive technology, offline vault, physical schema migrations, backups with key decrypt, restoration, rollback and monitoring must be tested before deployment.
5. Production requires two independent fresh **staging** review/fix rounds after last candidate, signed founder acceptance, deployment, live re-test, exact deployed checksum and DB parity.

## Independent five-field reality accounting

- **Repository main HEAD:** `2b369ef2d609247896e1ab32118f1dc498407f51`.
- **R28 HEAD:** fetch exact final changed SHA and matching CI run, do not trust older green.
- **Deployed Version:** UNKNOWN.
- **DB Version:** UNKNOWN (source-defined candidate 1.1.0).
- **Migration State:** UNKNOWN.
- **Live Verification Status:** NOT DONE.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
