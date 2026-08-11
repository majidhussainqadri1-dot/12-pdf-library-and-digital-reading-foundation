# File 12 — Tenth Fresh Ten-Round Corrective Review (R10)

Date: 2026-08-11

Frozen starting source HEAD: `e2f1d14ef1869cd2c544747650d1d3335029ab4b`

Method: each numbered review round was performed against the corrected state produced by the preceding round. A confirmed defect was corrected before the next numbered round began. Repository evidence remains separate from staging/live truth.

## Round 1

**Defect found.** External authorization and verified-doctor providers could throw through `PLDR_Core::authorize()`, turning a protected action into a fatal path rather than a fail-closed denial.

**Correction:** contain `Throwable`, audit provider failure, and return false for authorization/doctor-verification provider failures.

Commit: `64894ad361658c97f65c59f1c20bd6544f0f1a4e`

## Round 2

**Defect found.** Entitlement provider/companion failures in `PLDR_Access::can_access_edition()` could escape instead of failing closed.

**Correction:** contain entitlement-provider exceptions, audit the failure and deny access safely.

Commit: `535c1e30535674bb35dc9e146349931d9fd2ed89`

## Round 3

**Defect found.** Access-policy mutation accepted an omitted optimistic precondition (`expected_version=0`), allowing stale clients to mutate policy without proving the version they reviewed.

**Correction:** require an exact expected policy version; missing precondition is HTTP 428 and stale version is HTTP 409.

Commit: `ba9f68108d04cfbde61dd417aa31dd2612272d25`

## Round 4

**Defect found.** Core reader-manifest GET could mint a preview delivery grant for every loaded thumbnail (up to 300), creating request-to-token-storage amplification even though IIIF had already received its own cap.

**Correction:** retain bounded thumbnail discovery while separately limiting preview grants to 50 and disclosing issued/failed/deferred counts.

Commit: `ff31e474d12d3b3280bd57b475cbceb5aaaf4ebc`

## Round 5

**Defect found.** Private `/reading/items` listing delegated to an unbounded reading-items query and could hydrate an arbitrarily large private note/bookmark set.

**Correction:** owner/edition scoped bounded pagination (default 100, maximum 200, `LIMIT + 1`) with `has_more` and `next_offset` metadata.

Commit: `af32f602253fc3d7a733630de2acd04e18ec120a`

## Round 6

**Defect found.** General secure access-token issuance had no per-caller rate ceiling, so repeated public/authorized calls could amplify the access-token table beyond endpoint-specific caps.

**Correction:** serialized hourly issuance accounting for authenticated users and privacy-hashed anonymous callers, governable bounded ceiling, HTTP 429 on exhaustion and fail-closed 503 when rate state cannot be secured/persisted.

Commit: `126091b4c915dffb9869a87dcea9080e4abcb81d`

## Round 7

**Defect found.** Delivery integrity/decryption failure was only logged; the affected object remained `available`, so later requests could repeatedly attempt to serve a corrupt/unreadable object.

**Correction:** compare-and-set available objects to `quarantined`, revoke document grants, audit quarantine and fail-visible reconciliation failure.

Commit: `f1bcddc66d1a2169e8c446d94b3f1b3659c7d2f9`

## Round 8

**Defect found.** A throwing privacy legal-hold provider could crash erasure before the hold decision was known.

**Correction:** legal-hold provider failure now fails closed by retaining data, returns `done=false`, audits the provider fault and requires retry/reconciliation rather than deleting under uncertainty.

Commit: `f0bd44cc70d639144a77219d551c7ec07c9da9b6`

## Round 9

**Defect found.** AI corpus patient-case, allowlist and File 16 consumer-authorization hooks were uncontained. A provider fault could fatal this sensitive policy boundary.

**Correction:** contain all corpus policy-provider faults, audit them, and return explicit degraded HTTP 503 with corpus access denied. Deny-by-default behavior remains intact.

Commit: `68e47f56f54ef91b8619b29f96c0a6bca66df747`

## Round 10

**Defect found.** Portable annotation import's external source-validation fallback could throw after earlier annotations in the same bounded import had already been processed, producing a fatal/partially reported import request.

**Correction:** contain source-validator exceptions, audit only edition/page provider-failure metadata, treat the source as unverified, and continue with the annotation rejected rather than crashing the request.

Commit: `62e2c9190146cc5701db9754254ee876ffef1856`

## Defect distribution

Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**.

Clean rounds: **none**.

Every discovered R10 defect was corrected before the next numbered review round began.

## Production-truth boundary

This record proves repository-source review/correction only. It does not prove Hostinger staging or live deployment, deployed schema/migrations, provider configuration, browser behavior, rollback, backup restore or operational monitoring.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
