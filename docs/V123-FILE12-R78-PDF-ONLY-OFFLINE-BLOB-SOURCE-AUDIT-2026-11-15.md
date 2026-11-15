# File 12 — R78 signed offline PDF Blob MIME and byte-header integrity (2026-11-15)

**Evidence and scope:** Sabri Social Homeopathy Platform governing attached Master Plan V12.3, File12 PDF catalog/rights, specifically `F12-FR-004/009`, `F12-FUT-012`, `F12-NFR-001/002` and `F12-CEN-01`. **DRAFT SOURCE ONLY / NO_GO.**

## Exact separate realities

- GitHub `main`: `2b369ef2d609247896e1ab32118f1dc498407f51` — README-only (NOT the installed plugin).
- Source parent **R77** Draft PR #74: `0a7d14393e5f04e0bf766a0564ef619036468f55`, based on R76 PR #73 `69ebd510909d739bd318d353a9ff9395234df2ec`. Source version `1.1.0-rc.51`, declared schema/contract `1.1.0`.
- Corrected **R78** Draft PR #76: unmerged source `1.1.0-rc.52`; declared schema/contract unchanged. Exact SHA/CI must be refreshed after final evidence commits.
- Deployed Hostinger plugin, installed folder and checksum **UNVERIFIED**. Physical DB version/columns/options/migrations **UNVERIFIED**. Real staging and Live **NOT PERFORMED**.
- Earlier exploratory PR #75 was based directly on R76, predating R77 freeze checks. Its CI failed due lost R77 collector test; PR #76 is reconstructed from exact R77 parent, preserves R77 Bash/PHP read-only audit, and is the only intended candidate from this line. Never merge PR #75.

## R78 demonstrated Source defect and RED evidence

Actual `assets/future-reader-vault.js` authenticated the WordPress actor, enforced server rights, exact 206 range bytes, encrypted content and verified whole-file SHA256, **but** constructed browser `Blob` on store/open using the supplied `grant.mime_type` / persisted `meta.mime` without verifying PDF format. A matching SHA identifies bytes but does not prove media type. When malformed metadata advertises `text/html`, these bytes could be supplied to a same-origin browser Blob URL, although the UI describes a verified PDF.

RED-first CI `43710048142` failed at `tests/test-r77-offline-pdf-safe-open.cjs` before any implementation change, with: `AssertionError: an HTML MIME type from local metadata must not open an origin-bearing Blob URL`. The regression has been assigned unique **R78** test IDs to avoid collision with independent R77 read-only schema-freeze work.

## Source changes in rc.52

1. Require explicit `application/pdf` MIME for the signed `offline-grant` **before** capturing/retaining local bytes; an authorized `text/html`/JS response is denied.
2. Require a `%PDF-` PDF header within the first 1024 bytes of the first strict HTTP206 range **before** storing ciphertext. Matching SHA on HTML is insufficient.
3. On local `open`, refuse persisted non-PDF MIME before decrypting or presenting. Verify the first decrypted chunk independently carries `%PDF-`. Invalid/legacy local data purges via the existing error path; it must be re-captured after current authorization.
4. Construct all accepted PDF `Blob` objects with a fixed `application/pdf` type (never delegate executable MIME to a stored or remote value).
5. Keep current R71 128MiB full SHA256 integrity, R72 current rights and deny latch, R73 rights-expiry narrowing, R74 safe provider DOM, R75 private-root guard, R76 WordPress actor isolation, and R77 full read-only deployment/migration freeze.
6. Bounded real-source Node VM/AES-GCM tests: `tests/test-r78-offline-pdf-safe-open.cjs`, `tests/test-r78-offline-pdf-safe-store.cjs`. Existing R71/R72/R73/R76 synthetic byte fixtures now begin with an illustrative PDF header; the R71 mismatch fixture mutates the trailing byte to preserve the independent SHA mismatch test.
7. Immutable package source SHA inventory: 58 original rc43 plugin blobs, existing immutable rc44–51 historical overlays, plus `docs/V123-FILE12-RC52-SOURCE-DELTA-OVERLAY.csv` verifying the **two** changed plugin files and their exact source-parent SHA.

**Limitation:** a PDF signature and MIME label **do not** prove full PDF structural validity, malware freedom, embedded active-content safety, or original publication rights. The independent CF04 secure PDF scanner, sandbox, licensing and isolated staging tests remain mandatory. This source patch also cannot prevent arbitrary same-origin malicious JavaScript, and a maliciously crafted PDF still requires proper backend validation and a hardened PDF viewer. The new tests are synthetic and cannot establish an actual browser exploit.

## Remaining release blockers and acceptance

All 10 previously documented stop-ship blocker categories remain: immutable reviewed release scope, exact deployed/DB reality, plugin path compatibility, File26 PDF+Book Pack owner contract, File26 tombstone consumer ACK and index purge, CF04 scanner/storage/keys/restore, lawful original book rights, real Hostinger Staging/UX/WCAG/RTL/performance, two independent human reviews and Founder approved GO/rollback/Live retest. Existing 39 historical Stage, 54 runtime requirement, 10 CF04, 77 V12.3 Stage/Live and 60 QA journeys are **UNVERIFIED**, not 240+ confirmed defects. Actual device/browser preview and licensed original PDF remain untested.

**Final rule:** exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔ No deployment, live database changes, merge to `main`, or `Resolved` classification is authorized by this source-only PR.
