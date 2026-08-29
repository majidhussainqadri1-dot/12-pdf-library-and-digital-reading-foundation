# File 12 — Thirteenth Fresh Corrective Review Cycle (20 Rounds)

**Repository:** `majidhussainqadri1-dot/12-pdf-library-and-digital-reading-foundation`  
**Branch:** `feat/file-12-future-24-v1.1.0-rc1`  
**Frozen starting HEAD:** `588dd37b99acabad67a9b39db54cba156693967b`  
**Date:** 12 August 2026  
**Method:** Each numbered review was completed against the corrected state produced by the preceding round. Every defect listed below was corrected before the next numbered review began.

## Governing review boundary

This cycle is a repository/code review only. It applies the File 12 v1.1 Future-24 plan and the consolidated platform release law: repository code, automated QA and a reproducible package are not substitutes for staging, deployed database/schema/migration evidence, Founder acceptance or live re-test.

## Round 1

**Defect:** The scheduled `pldr_future_fingerprint_edition` worker ran without a logged-in actor, but fingerprint computation required interactive manage/rights/repair authority. The scheduled Future-24 scan-fingerprint path was therefore authorization-dead.

**Correction:** Permit the narrowly scoped internal fingerprint action only when WordPress is actually doing cron and the current action is exactly `pldr_future_fingerprint_edition`; interactive calls retain native document authority.

**Commit:** `3aa806d0594d269f697a93272f0096a096506ed6`

## Round 2

**Defect:** Scan-fingerprint candidate evidence was readable by any user who could read the edition, even though scan fingerprints are review evidence rather than reader-facing content.

**Correction:** Candidate inspection now requires manage/rights/repair authority and returns an explicit forbidden machine error when the review boundary is not satisfied.

**Commit:** `37188566660676dbbaaa3c1d24a7706a890b455d`

## Round 3

**Defect:** Preservation assessment treated any WP-Cron execution as trusted system authority, so an unrelated cron action could satisfy the internal bypass.

**Correction:** System authority is now granted only when WordPress is doing cron *and* the exact `pldr_future_preservation_scan` action is executing.

**Commit:** `33d49851c46c5e189bad0a4581b7e7b32ae50fbe`

## Round 4

**Defect:** Reading-room anchor regions bounded x/y/w/h individually but did not require `x+w <= 1` and `y+h <= 1`; a region could extend beyond the page.

**Correction:** Reading-room regions must remain fully inside the page before persistence/provider handoff.

**Commit:** `8896ea53371778f4bd4e064f2a513042e9d64a71`

## Round 5

**Defect:** Annotation-import source-validation provider exceptions were audited but collapsed into an ordinary source mismatch, hiding a provider outage/degraded condition.

**Correction:** Provider failure now returns an explicit machine error, import reports provider-failure counts/degraded state, and source verification is not claimed when provider validation failed.

**Commit:** `635b77159350a3fe1a532ab4c34241d1ae117dcc`

## Round 6

**Defect:** Exported annotation IDs were labelled stable but were HMACed with mutable WordPress auth salt, so salt rotation changed portable annotation identities.

**Correction:** Portable annotation IDs now use a deterministic versioned File-12 identity hash independent of auth-salt rotation.

**Commit:** `afd479f1c0bcdf3082012f5b5b2fcecec4314ce2`

## Round 7

**Defect:** The governing Citation Export Center includes a plain/Sabri text export, but the Future citation endpoint accepted `sabri` and omitted the explicit `plain` format alias.

**Correction:** `plain` is now a supported export format and uses the governed plain/Sabri text renderer while preserving the requested format identity in the response.

**Commit:** `4a76a023813d2ab11cf689145ff78d37a13e7796`

## Round 8

**Defect:** BibTeX escaping used sequential `str_replace`; replacement text inserted for backslashes could itself be altered by later brace escaping.

**Correction:** BibTeX escaping now uses non-recursive `strtr` character mapping.

**Commit:** `5fe72f59498f3037aba1ef40383b4fc119679052`

## Round 9

**Defect:** A database failure while reading bibliographic authority cache was indistinguishable from a cache miss, allowing an external provider call against unverified local cache state. Corrupt-cache deletion failure could also fall through to provider refresh.

**Correction:** Cache read and corrupt-cache repair failures now fail closed and no provider request is made.

**Commit:** `be690d6d6aa7af106dc5fc04705add2db04e4402`

## Round 10

**Defect:** Preservation derivative/evidence database reads could silently become empty state, existing preservation-record read failure could reset checksum-generation semantics, derivative JSON encoding was unchecked, and scheduled-scan selection/assessment failures were not fail-visible.

**Correction:** Database and encoding failures are explicit; no partial preservation record is written; scheduled selection/assessment failures are audited.

**Commit:** `d986a7b6840a164e2171e82ba5bcf44c0fe026d6`

## Round 11

**Defect:** IIIF thumbnail/derivative SQL failure could be projected as ordinary missing previews, producing a misleading partial manifest rather than a degraded database state.

**Correction:** Derivative read failure now aborts manifest grant issuance with explicit degraded evidence. Download-grant failure is also disclosed separately from rights denial.

**Commit:** `cedd3032eaa3984d605d8072695192d221552b86`

## Round 12

**Defect:** Inside-book heatmap could interpret OCR/correction-layer SQL failure as source exhaustion and return a partial/empty success.

**Correction:** Each OCR batch now checks database failure and stops with an explicit degraded machine error; partial results are not represented as complete search evidence.

**Commit:** `caf6574920820ae4e7e0aad548cd2533626c8e2f`

## Round 13

**Defect:** Knowledge-context source OCR failure or source-validation provider exception was collapsed into ordinary selection mismatch, obscuring a degraded condition and risking fallback semantics.

**Correction:** Database source-read failure and source-validation provider failure are explicit machine errors; companion lookup is not attempted.

**Commit:** `f04895b29cc6aa27ad8a02919b73462bd5055ac6`

## Round 14

**Defect:** Accessibility cached audit, OCR aggregate, derivative-count and verification re-read database failures could be treated as absent/zero evidence, allowing misleading scoring or conflict semantics.

**Correction:** These evidence reads now fail closed and no accessibility score/verification is projected from an unverified database read.

**Commit:** `fd4784bfbc3d16221971881af0ea448c265e711a`

## Round 15

**Defect:** Future schema-health verification checked only a subset of indexes declared by the schema, so secondary-index drift could remain hidden while schema health reported green.

**Correction:** Schema verification now requires every declared primary/unique/secondary index for all Future-24 tables.

**Commit:** `8ea54fcae4d0abd8c7bd2a4bd56f3878f7fcbb9e`

## Round 16

**Defect:** Synchronized reading-preference SQL read failure could be interpreted as `version=0` / no preference record, risking incorrect create/conflict behavior.

**Correction:** Preference reads now distinguish database failure from absent state and fail closed with explicit degraded evidence.

**Commit:** `cdf0754e455cb04a9a0a89e4f146a8cd9f3ae0ff`

## Round 17

**Defect:** Cross-device handoff SQL read failure could be interpreted as no existing session and feed incorrect create/conflict logic.

**Correction:** Handoff reads now distinguish database failure from absence and fail closed before any mutation.

**Commit:** `4bea39ec736605e962e01ec28583032db78c68c4`

## Round 18

**Defect:** Smart Shelf default/list/capacity/ownership/membership database read failures could be interpreted as absent rows or zero counts, causing misleading empty success, unsafe creation attempts or incorrect missing-state errors.

**Correction:** Private shelf reads now explicitly distinguish database failure from absence throughout default creation, listing, capacity checks and mutations.

**Commit:** `65394ca3bb55d3a118a8b246b348226340486abe`

## Round 19

**Defect:** OCR Quality Lab report/source/rate/review database read failures could become zero/empty/stale/missing semantics, including a rate-count failure that could effectively look like zero recent submissions.

**Correction:** OCR Lab evidence, source, rate-state and review reads now fail closed with explicit degraded errors before report projection or mutation.

**Commit:** `3554d825daa2b33e45ab6d1d8800bbbe0f6023f8`

## Round 20

**Defect:** Translation/transliteration source binding could treat OCR database failure as no local source and fall through to external source-validation/provider logic.

**Correction:** OCR database failure now blocks fallback validation and all external derived-text processing with an explicit degraded error.

**Commit:** `ab89367a14104fa92a3d385d1ee5ff6f5c036dde`

## Defect distribution

**Defect rounds: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20.**  
**Clean rounds: none.**

All twenty rounds found a distinct repository-level defect or defect cluster, and every correction was committed before the next numbered round began.

## Production-truth boundary

This review cycle does not establish Hostinger staging or live state. Exact deployed plugin/package/checksum, deployed DB/schema/migration state, active configuration/providers and live re-test remain separate evidence gates.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
