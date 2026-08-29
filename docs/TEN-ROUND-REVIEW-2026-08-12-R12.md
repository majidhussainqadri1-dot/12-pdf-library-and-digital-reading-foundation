# File 12 — Twelfth Fresh Ten-Round Corrective Review (R12)

Date: 2026-08-12 (Founder local date, UTC+05:00)

Frozen starting repository HEAD: `b905310b5911deb7bf3ba6465b1304930da1ce28`

This review was performed as ten sequential review → fix rounds. A later round did not begin until the defect established in the current round had been corrected in the repository candidate.

## Round 1

**Defect found.** Bibliographic authority enrichment accepted an external result with no provider identity and substituted the synthetic provider name `adapter`. That fabricated provenance contradicted File 12's explicit source/provenance boundary.

**Correction:** anonymous authority-provider output is now rejected with an explicit provenance error and audit evidence; canonical metadata remains unchanged.

Commit: `8a2d6c9385792b45eb423f7657c195842a905968`

## Round 2

**Defect found.** Authorized callers could repeatedly bypass the authority cache with forced lookups and call the external authority provider without a server-side per-caller budget.

**Correction:** serialized hourly provider-call accounting was added for authenticated/privacy-hashed callers, including fail-closed rate-policy/state failures and HTTP 429 exhaustion.

Commit: `7b4aee0b727b608172c56ceae42ee17eb7bde83e`

## Round 3

**Defect found.** Future-schema `maybe_upgrade()` trusted DB version/revision options indefinitely. A dropped table/column/index after a successful migration could therefore remain undetected while version markers still claimed the schema was current.

**Correction:** current markers now receive a bounded periodic schema-health verification. Drift is recorded/audited and triggers the normal locked repair/upgrade path instead of being silently trusted.

Commit: `f31fc05e85dc0a19210851a91a44ec77788a9bfc`

## Round 4

**Defect found.** Knowledge Context had two policy-adapter exception gaps: the hourly-limit filter and source-selection validator could throw outside the guarded provider call.

**Correction:** both adapters are now exception-contained. Rate-policy uncertainty prevents the provider call; source-validator uncertainty fails source verification without inventing/copying companion data.

Commit: `3b2e06f390425b6c08c3ef2de7cc2699af0ada34`

## Round 5

**Defect found.** A failing Unified Shell adapter could throw from `pldr_shell_rendered` and break an otherwise locally renderable File 12 route.

**Correction:** shell-adapter exceptions are contained/audited and File 12 falls back to its local governed rendering path.

Commit: `ecb6fa84eaf951c0e28c7080dabbb820ca8f344e`

## Round 6

**Defect found.** Custom Smart Shelf capacity enforcement used an unlocked count-then-insert sequence. Concurrent creates could both see a count below 100 and exceed the configured per-user ceiling.

**Correction:** custom-shelf creation now serializes capacity check + insert under a per-user advisory lock and returns an explicit temporary-capacity error when the lock cannot be acquired.

Commit: `c68ac07c96687dc2789b428ee33a5fe400ccbc2f`

## Round 7

**Defect found.** Corrupt `future_prefs.preference_json` was silently decoded as an empty preference set. A subsequent save could overwrite evidence of corrupt synchronized state as if the user had no settings.

**Correction:** corrupt stored preference JSON is now audited and returned as an explicit integrity error; save/conflict paths propagate that error instead of silently replacing the state.

Commit: `1e001184c044e8eba09de4389f5c3e18d878314e`

## Round 8

**Defect found.** Corrupt cross-device handoff `anchor_json` was silently projected as an empty anchor, hiding stored-state corruption and allowing subsequent state transitions to proceed on incomplete context.

**Correction:** handoff DTO projection now detects/audits invalid persisted anchor JSON and returns an explicit integrity error; conflict/race paths propagate the error.

Commit: `db9e71079484ba40643ffa2af7ada5a8224abc0a`

## Round 9

**Defect found.** Corrupt persisted accessibility `findings_json` was silently replaced by an empty findings array while an old `verified_by` value could still make the public badge appear verified.

**Correction:** corrupt accessibility findings now invalidate trust in the stored audit projection and return an explicit error; the verified/public-badge state is not exposed from corrupt evidence.

Commit: `b98929f4e366d88f25508897a68e51ce3f21a6af`

## Round 10

**Defect found.** Scholarly anchor regions independently bounded x/y/w/h to page percentages but did not require `x + w <= 100` and `y + h <= 100`, allowing a positive region to extend outside the page.

**Correction:** region selectors must now remain wholly within the page boundary before they can be persisted as a precise anchor.

Commit: `53c74d055c64d06ba15d557efe8cd3e074403825`

## R12 result

Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**

Clean rounds: **none**

All defects established during these ten numbered rounds were corrected before the next numbered round began.

Repository evidence does not establish staging/live deployment. Exact deployed code, deployed DB/schema state, migration state and runtime workflow behavior require separate deployment-parity and live/staging evidence.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
