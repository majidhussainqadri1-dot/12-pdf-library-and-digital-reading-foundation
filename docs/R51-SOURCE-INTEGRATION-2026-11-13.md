# File 12 R51 — source-only consolidation and rights-gated release record (2026-11-13)

## Frozen exact source parents
- R50 PR #45 `eb3744059f4c587d953f93f527614ade65345cbf` (QA success verified, not deployed).
- File06 PR #46 `c1247100462ea7a0aac5f5a02b721d5f87fa5606` (this R51 parent, QA success verified).
- File26 PR #47 `611226bfac5cf43579f22959e53d4f8a93167545` (independent sibling, QA success; File26 connector source blob `671cd56d50eb5d49d1b2409aef02b0554b07cb20`).

## Confirmed source defects and corrective scope
1. File06 source/quote approvals coerced negative/float/bool/string/array JSON IDs through `absint`, silently allowing a request with a wrong page/edition/concept version to become another numeric identifier before permission/DB checks. Use strict PHP positive integers from JSON before any edition query.
2. Previously stored File06 source link records could also coerce negative/invalid page/ID values on read; reject malformed typed historic identity, including public quote assertion identity, rather than projecting a plausible but wrong page.
3. The PR47 File26 connector identifies itself as `proposed` with a pending versioned rights/tombstone cutover contract. Port its source for integration analysis but DO NOT invoke `PLDR_File26_Connector::register()` from the runtime bootstrap. Merely including its definition is NOT activation. Existing indexed copies may expose stale metadata if rights withdrawal is not reconciled; runtime read revalidation alone does not prove safe search index invalidation.

## Exact source/package identity
- Software candidate `1.1.0-rc.30`; R50 software was `1.1.0-rc.29`. Never reuse a version label for different bytes.
- Source DB version `1.1.0`; integration contract `1.1.0`; no actual migration or package deployment.

## Evidence this branch can prove
- R51 native PHP fixture rejects malformed File06 approval identifiers before source/DB reads; rejects corrupted historical link/quote identifiers.
- File26 native proposed adapter fixture checks a current lawful public catalog candidate, no private text/history/downloading in the DTO, current rights and version rechecks, and dormant bootstrap.
- Exact HEAD PHP 8.1/8.3/8.4 QA and ZIP packaging are required anew; success on PR46/47 is not inherited by R51.

## Mandatory release blockers
- No `main` merge, installation, production deploy or activation of File26 before File26-owned signed/versioned rights/withdrawal/tombstone reconciliation, real WP/MySQL and anonymous/cache/index leakage staging tests, independent review and Founder cutover approval.
- Original plugin root `12-pdf-library-foundation` vs candidate `pdf-library-foundation-12` requires explicit change control and tested rollback.
- Hostinger/WordPress runtime source, active plugin, physical DB/schema/wp_options/migration, keyring, rights evidence, provider configurations, real backup restore, cross-file installed contracts, browser/RTL/IDOR and live parity all unknown. External check currently returned HTTP 530 and Jetpack marked disconnected: symptoms only, not root cause.
- Live-first five truths remain: Repository HEAD (this branch exact SHA after build), Deployed Version UNVERIFIED, DB Version UNVERIFIED, Migration State UNVERIFIED, Live Verification NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**


## Focused R52 review and immutable release candidate
- New source candidate `1.1.0-rc.31` (R51 parent candidate `1.1.0-rc.30`; no identical-version different-code reuse).
- File26 registration and manifest hooks fail closed while `status=proposed`, rights/tombstone event semantics pending. The PHP class is loaded only; **never enabled by this release**.
- Invalid batch `limit` types or unknown partial `scope` are rejected, not silently expanded. `can_view` refuses malformed stored numeric object versions rather than casting to valid.
- New direct-native isolated tests prove registration side effects stay zero, source ACL still current, and bad requests fail before SQL. Not a substitute for real staging.
