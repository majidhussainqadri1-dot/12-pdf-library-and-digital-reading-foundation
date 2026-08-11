# File 12 — Ninth Fresh Ten-Round Corrective Review (R9)

Date: 11 August 2026

Frozen starting source HEAD: `64e1195ad06ec679621edbe6cf821836fdf5c068`.

Method: each numbered round reviewed the corrected state produced by the prior round. When a repository-level defect was proven, the defect was corrected before the next numbered round began. Repository evidence is not staging/live evidence.

## Round 1
Defect found: reflow fallback provider exceptions could escape the optional-provider boundary, anonymous output could be labeled with a fabricated `adapter` provider, and external-provider truncation was not truthfully represented.

Correction: contained provider exceptions, rejected anonymous provider output, bounded provider pages, and disclosed provider input/truncation metadata.

Commit: `a13123ffc4dd7e851e8fbeda5b3da97fff13f1a6`.

## Round 2
Defect found: outline extraction had the same optional-provider provenance/exception weakness and could silently accept anonymous adapter output.

Correction: provider exceptions/invalid output now degrade to the local OCR heading heuristic with explicit provider-failure disclosure; external outline output requires named provenance.

Commit: `9cca73684bd1915aa336dc1411fdccb6812a50cf`.

## Round 3
Defect found: Knowledge Context silently defaulted missing owner provenance to `companion` and marked every accepted provider item `canonical=true` even when the provider had not asserted canonical ownership.

Correction: only explicit canonical items from the governed companion owners File 05, File 06, or File 16 are projected; missing/invalid provenance is rejected and counted.

Commit: `d747b48edf6fad720d45eedf4c9bb089f3aa3959`.

## Round 4
Defect found: an optional preservation provider could return `format_health=quarantined`, causing the stored preservation record to say quarantined even though the authoritative object had not actually been quarantined/revoked by local integrity evidence.

Correction: provider quarantine requests are downgraded to governed `needs-review` unless the local object is actually quarantined; the request is disclosed separately.

Commit: `dea2865ff9f379522c025ffb413a8104f1d90376`.

## Round 5
Defect found: accessibility verification refreshed an audit and then updated by `edition_id` only. A concurrent refresh could replace the assessed row between review and verification, allowing verification of a different assessment.

Correction: verification now performs a compare-and-set update bound to the exact score/status/findings/provider/updated-at snapshot and returns a 409 conflict if the assessment changes.

Commit: `8127e1edde1093fe8842fd8c8150a64b389978ff`.

## Round 6
Defect found: scan fingerprint computation, including Imagick/decryption work and persistence, was available to any user who could merely read the edition; candidate lookup could also trigger missing fingerprint generation.

Correction: expensive fingerprint computation/recomputation now requires document manage/rights/repair authority. Read-authorized users may inspect already-generated candidate evidence but cannot trigger new heavy computation.

Commit: `d96c44327d7960d46f310106f5230336be67bd99`.

## Round 7
Defect found: the public/reader Knowledge Context path could repeatedly invoke an external companion provider without a server-side frequency ceiling.

Correction: added serialized, fail-closed hourly provider accounting for authenticated users and privacy-hashed anonymous callers, including HTTP 429 and retry metadata.

Commit: `87b7e564940a6e6ebbfd239dc88541b15e8f1a65`.

## Round 8
Defect found: one IIIF manifest request could mint a delivery token for every available thumbnail canvas (up to the canvas ceiling), producing large write/token amplification from a read request.

Correction: canvas/page identity remains bounded and complete within the canvas window, but short-lived preview-grant issuance is separately capped (default 50, hard max 100) with issued/deferred/failure disclosure.

Commit: `4a6fd78526e7e6b157fa021317aba29f57d2150d`.

## Round 9
Defect found: portable annotation duplicate suppression used check-then-insert without serialization; concurrent imports of the same annotation could both observe no match and persist duplicates.

Correction: each privacy-preserving annotation identity now uses a short MySQL advisory lock around duplicate detection plus insertion, with lock failures disclosed rather than silently racing.

Commit: `b915f39383ff15d8bd0ac5b3b71ccddae119c5ba`.

## Round 10
No new repository-level defect was proven on the corrected source state. Fresh review covered AI corpus deny-by-default/consumer authorization, derived-text source verification/provider provenance/rate limiting, preservation authoritative quarantine semantics, accessibility verification CAS behavior, IIIF rights/download boundaries, and portable annotation source binding/deduplication.

No patch was created for Round 10.

## Defect distribution

Defects were found in **Rounds 1, 2, 3, 4, 5, 6, 7, 8, and 9**.

**Round 10 was clean.**

## Production-truth boundary

This record proves repository review/corrections only. It does not prove Hostinger staging or live state, deployed package parity, deployed DB/schema/migration state, or real-browser/runtime acceptance.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
