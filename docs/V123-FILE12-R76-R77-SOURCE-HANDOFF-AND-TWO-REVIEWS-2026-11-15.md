# File12 — V12.3 source completion handoff and two internal technical reviews
**Freeze:** 2026-11-15 (Asia/Karachi) · **Status:** CODED + AUTOMATED-QA GREEN; **NOT** STAGING-ACCEPTED, LIVE-DEPLOYED OR OPERATIONAL.

## Source and package invariants
- Repo: `majidhussainqadri1-dot/12-pdf-library-and-digital-reading-foundation`.
- Immutable `main` HEAD `2b369ef2d609247896e1ab32118f1dc498407f51` — README only; does not contain the candidate plugin and does not identify Hostinger deployment.
- Parent verified PR #71 R75 plugin candidate `1.1.0-rc.50` SHA `092f3adcebba9a597d0f8322a08dde861507053c`.
- R76 **Draft PR #73** `1.1.0-rc.51`, SHA `69ebd510909d739bd318d353a9ff9395234df2ec`: 7/7 exact commit CI `43707656991`. Source actor-bound offline grant/config/metadata/recheck; inherits R73/R74/R75.
- R77 **Draft PR #74** based directly on PR #73 SHA `69ebd...`, plugin still `1.1.0-rc.51`. Exact first code+QA commit `6aad0a30edcca6f73f0d28d7e87d622a8b1e27e7` verified 7/7 in CI `43708060470`; documentation head `eedbf57d860e7d7e5f4781a47c66f18c8d3fc937` verified 7/7 in CI `43708184767`. R77 only adds a read-only script and synthetic tests/documentation, no plugin change.
- Deterministic plugin ZIP: `12-pdf-library-and-digital-reading-1.1.0-rc.51.zip`; SHA-256 **`3405592c3cd89bcf1519e36c00f46bbd83ab62af95a1697c19809b33caec16b2`**. Independently computed in both 7/7 green builds `43707656991` and `43708184767` on different repository exact heads. This proves deterministic candidate plugin package parity between these two CI builds **only**, not that Hostinger installed it.
- GitHub Actions artifact for `43708184767`: package artifact `14159655928`. It is NOT an authorized release until real staging, reviewed migration, backup+rollback and Founder GO. Additional outer GitHub artifact compression checksum differs from inner package SHA.
- Old conflicting PR #72 has been **closed, unmerged**, because its R73/rc.48 identity conflicted with parallel PRs #69–71 and omitted later R73/R74/R75 fixes.

## Exact demonstrated issues and coding outcomes
| ID | Source issue | Candidate code | Automated QA / limitations |
|---|---|---|---|
| R76 | PDF offline cache tied to edition but not authenticated WordPress actor | Server `actor_id`, inline `userId`, cache `actorId`, fail-closed online/offline account mismatch and unknown/legacy metadata | RED `43706807139`; 7/7 GREEN `43707656991`; supplemental native WP actor/login/logout test, synthetic AES-GCM; **no live proof** |
| R77 audit gap | Original production-freeze collector lacked Future-schema, corrections, legacy/migration/error observation | Whitelisted redacted version fields, option-name-only markers, read-only plugin header parse | RED `43707806850`; 7/7 GREEN `43708184767`, including synthetic WP-CLI execution; **never ran on Hostinger** |

Previously logged R64–R75 comprise **12 distinct demonstrated source defects, source-fixed in Draft**; R76 raises this to **13 Draft-corrected source defects**. R77 is a separately corrected **audit-evidence coverage gap**, not an additional application bug. **Live-proven corrections: 0.** Independent reviewers and exact deployed source remain unverified.

## Technical review A — exact source / regression / build parity
- Both chain histories and source overlays preserved; no historical rc.43–50 reference files overwritten.
- No plugin change in R77: double-sampled deterministic ZIP SHA-256 identical.
- New R76 test checks original user's genuine offline reading vs changed user/unknown/legacy account; server mismatched grant rejected without range fetch; online current rights and expiry clamp preserved.
- New R76 native WordPress proof uses a disposable WordPress+MySQL installation; user role is changed via real WP API; former actor must not appear in fresh anonymous JS config.
- PHP 8.1/8.3/8.4 source tests, WordPress 7.0.1 & 7.1.4 disposable suites, Node and Chromium are automated green. These are not legally licensed books, actual production users or V12.3 owner acceptance.
- Packaging is reproducible and has `dist/SOURCE-SHA256SUMS`; checksum alone does not mean deploy parity.

## Technical review B — adversarial privacy / security / operations
- R76 is ordinary UI account-context protection, **NOT cryptographic per-WordPress-user isolation from a local adversary controlling the browser origin**. A cached same-origin non-extractable CryptoKey can still be used by authorized same-origin JS; same device/OS profile is not a true security boundary.
- Stale open tabs and account changes in another tab, browser evictions, inaccessible device storage, offline licence expiry, rights revocation and real mobile device recovery require staging QA.
- A disconnected device cannot receive instant remote revocation; local expiry is the policy boundary. Existing R73 rights clamp is only observed while online and requires persistence.
- R77 output is intentionally limited: network/must-use plugin paths, runtime PHP-FPM version, secrets, object-store checksums, decrypted data, true DB rows, raw error logs and unexpected custom plugin paths need separate authorized evidence. The script cannot certify runtime version or active Hostinger source.
- Both internal technical reviews above are performed as **source analysis**, not the mandatory **two independent human reviews**. Therefore release still NO_GO.

## Remaining 10 release blocker categories (same as original ledger)
1. Main/source Draft reconciliation, accepted source SHA and authorized merge decision.
2. Authenticated Hostinger exact installed plugin path/hash/runtime version/active state.
3. Real physical DB versions, `wp_options`, schema/indexes and migration/legacy/Future/correction marker parity.
4. Old vs canonical File12 plugin directory route/activation/migration compatibility.
5. File26 typed BookPack + PDF owner contract / public search approval, source of truth.
6. File26 rights-withdrawal event delivery, tombstone ACK, index/cache purge and replay safety.
7. CF04 private object storage, scanner/OCR sandbox, key policy, restore and ten controls.
8. Genuine licensed/public-domain book, author/edition/pages/OCR/citation provenance and right-to-publish receipts.
9. Hostinger staging test on exact package: 39 historic + 54 requirements + 10 CF04 + 77 V12.3 IDs + 60 QA15-24; duplicate controls **cannot be added as unique bugs**.
10. Two independent reviewers, Founder GO/NO_GO, backup/rehearsed rollback, controlled deploy, independent Live re-test and parity proof.

## Authorized next execution sequence
1. Hostinger operator (no person assigned): run **reviewed** `scripts/file12-live-reality-freeze-readonly.sh` with verified absolute WordPress root, only if WP-CLI permissions permit; redact reviewed output; do **not** run migration or reveal secret options.
2. File12 + File26 + CF04 owner decisions: freeze exact current deployed/versioned contracts, reconcile last applied rights-event acknowledgement and book packs, verify storage and schema. On parity difference stop application debugging for deployment-parity audit.
3. Only after authorized Staging deployment, sample licensed original, rights/session/revocation/mobile/RTL/WCAG/performance/restore tests, two independent reviewers and Founder approval may the candidate progress to a deploy decision.
4. On approved production release: package checksum and DB version freeze → deploy → live re-test → verify installed per-file/ZIP parity; otherwise restore and issue a factual NO_GO incident report.

## Mandatory final release realities
**Repository HEAD/main:** `2b369ef2d609247896e1ab32118f1dc498407f51`.
**Deployed Version:** UNVERIFIED.
**DB Version:** UNVERIFIED.
**Migration State:** UNVERIFIED.
**Live Verification Status:** NOT PERFORMED.
**Release:** **NO_GO**.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
