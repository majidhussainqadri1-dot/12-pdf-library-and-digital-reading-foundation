# File 12 R28: New Twenty-Focus Cross-Plan Review and Integration — 11 October 2026

## Baseline evidence and method

The latest available branch at review start was PR #15 (0c9ba8ba4091234183e057145d0f9a21bcaff7b9), already green on its own exact HEAD. PR #14 (a369a8525d01d7bfdf82c794d1d2cc60510beeea) was a DIVERGENT green rc.6 candidate. GitHub main remained bootstrap-only. Neither one proved deployment.

Materials examined: supplied File 12 v1.1 Future-24 plan (F12-FR-001..019, F12-NFR-001..010, F12-FUT-001..024); supplied consolidated governing plan (CEN-OWN/PRI/MED/REL/STATUS/ACC/SEARCH); File05/06/21/23/25 companion plans; and current repository source. File00/01/16/17/19/20/24/26 native-owner contracts were reviewed through File12 integration boundaries, not through deployment of those companion plugins.

A round here means one focused source/requirements check with relevant code retrieved and evaluated. There were 20 such focused checks before R28 corrections. They are NOT twenty entire-code independent line-by-line reviews, twenty WordPress end-to-end runs or a guarantee of zero defects.

## Twenty-round evidence register

| # | Governing area | File12 actual code inspected | Finding at freeze |
|---:|---|---|---|
| 1 | F12-FR-001/004 taxonomy and canonical ownership | class-pldr-core.php | Types, categories, canonical DTO present; real catalog and other owner deployment unverified |
| 2 | F12-FR-002/003 malware/ingest/source rights | class-pldr-ingest.php | Signature, scanner and rights paths present; external scanner/provider acceptance unverified |
| 3 | F12-FR-017 case, expiry, appeals, legal rights | class-pldr-rights.php | Lifecycle and expiry functions present; live rights transitions pending |
| 4 | F12-FR-006/009/018 encryption, range integrity | class-pldr-crypto.php | Authenticated encryption and range functions present; keys/restore pending |
| 5 | F12-FR-009 current signed grant / object attachment | class-pldr-access.php | **R28-D01 CONFIRMED GAP**: PR15 had no current_delivery_binding() or call inside deliver(); PR14 has verified correction and regression. Integrated after audit |
| 6 | F12-FR-013 catalog + OCR bounded cursors | class-pldr-reader.php | Query and cursor methods present; real load/permission testing pending |
| 7 | F12-FR-011/012 private notes and REST IDOR | class-pldr-rest.php | owner and idempotency gates present; real-role tests pending |
| 8 | F12-NFR-002 data privacy/hold/erasure | class-pldr-privacy.php | export, erase and legal-hold code present; legal-basis/provider testing pending |
| 9 | F12-NFR-007 core migration/legacy upgrade | class-pldr-schema.php | upgrade/migration lock routines present; deployed DB unverified |
| 10 | F12-FUT schema/version readiness | class-pldr-future-schema.php | schema checks and locks present; real DB upgrade/rollback pending |
| 11 | F12-FUT-001..024 feature-owner registry | class-pldr-future.php | all 24 IDs and reader controls present; feature journeys unverified |
| 12 | F12-FUT OCR derived corrections | class-pldr-future-data.php | derived correction overlay code present; scanner/OCR acceptance pending |
| 13 | Files05/06 canonical context integration | class-pldr-future-context.php | owner/provider context hooks present; real linked provider journeys pending |
| 14 | File16 AI corpus and patient privacy | class-pldr-future-corpus.php | allowlist, consumer, patient-case gates present; real File16 acceptance pending |
| 15 | File17 room ownership and compensation | class-pldr-future-rooms.php | provider and compensation paths present; linked messaging runtime pending |
| 16 | F12-FUT-IIIF rights and grant preflight | class-pldr-future-iiif.php | PR15 preflights policy before issuing previews; runtime failure injection pending |
| 17 | F12-NFR-005 accessible independent review | class-pldr-future-a11y.php | inspect/verify separation present; real screen reader/human verification pending |
| 18 | F12-FR-018 integrity/preservation | class-pldr-future-preservation.php | assessment and scheduled scan present; real backup/restore pending |
| 19 | Files19/21 and F12-NFR-003 outbox events | class-pldr-r21-outbox.php | retry/dispatch/dead-letter present; actual consumer subscriptions pending |
| 20 | File01 routes/File20 shell/File25 components | class-pldr-plugin.php and class-pldr-rights.php | Own route registration and shared provider contracts present; plan-vs-package folder name and stale README release identity disclosed for change control |

Additional R27 code comparison: PR15 has reader-manifest and IIIF grant-preflight fixes confirmed in class-pldr-rest.php and class-pldr-future-iiif.php; PR14 differs with a separate current-delivery object check. The R28 release avoids treating the two rc.6 ZIPs as equivalent.

## Corrections after all twenty inspections

**R28-D01 (source defect):** Signed reader/download/preview tokens were checked when issued, but an object can be detached from its edition during derivative regeneration while the ciphertext object remains marked available. PR15 delivery did not recheck current association and an otherwise valid old token could serve detached data. R28 retains PR15 reader/IIIF preflight and integrates PR14's PLDR_Access::current_delivery_binding() with request-time fail-closed rejection of missing, stale or DB-uncertain edition/object linkage.

**R28-D02 (release evidence drift):** Two different rc.6 branches were independently green; inherited README lagged at rc.3. New unified code is assigned 1.1.0-rc.7, with plugin metadata, readme, manifest, current plans, source assertions and ZIP/workflow markers aligned. No DB version bump: source contract 1.1.0 remains.

**R28 regression:** tests/test-twenty-round-review-r28.php uses actual PLDR_Access code with a strict DB fixture to test valid current original/derivative, detached derivative, wrong edition, deleted edition and DB read failure; tests/test-twenty-round-review-r27.php independently preserves Reader/IIIF grant-preflight source invariants. Both must be run on the exact R28 final HEAD alongside PHP 8.1/8.3/8.4, JS, crypto and deterministic packaging. No static review is called a WordPress runtime guarantee.

## Defect and status counts

20 focused source checks performed. Confirmed missing safety code: round 5 (1); release evidence/version discrepancy: round 20 (1). The other 18 scopes had no ADDITIONAL confirmed repository defect from these focused inspections; this is not 18 comprehensive clean staging runs.

### Outstanding decision, not silently patched

The amended File12 written plan specifies ZIP top-level folder 12-pdf-library-foundation. The historic WordPress slug/code uses pdf-library-foundation-12. Renaming without a validated upgrade/deployment migration may orphan installed plugin identity and should require Founder change approval and a staged compatibility plan.

## Nontransferable deployment gates

Real staging File00/01/05/06/16/17/19/20/21/23/24/25/26 and CF-04 versions, physical SQL schema/wp_options/migration, signed object HTTP race/IDOR tests, rights scanner/OCR, keyring, external backup, restore, rollback, Urdu/Arabic RTL/mobile/screen reader/offline, two corrective staging QA passes and Founder sign-off are outstanding.

Repository HEAD: record final R28 SHA on GitHub after all edits and CI.
Deployed Version: unverified.
DB Version: unverified (only source declares 1.1.0).
Migration State: unverified.
Live Verification Status: not performed.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
