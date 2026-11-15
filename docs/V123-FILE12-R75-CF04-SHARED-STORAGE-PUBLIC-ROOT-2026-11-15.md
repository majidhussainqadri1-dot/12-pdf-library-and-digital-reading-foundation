# File12 R75 — CF04 shared private object root confinement (2026-11-15)
**Release status:** DRAFT SOURCE ONLY; NO HOSTINGER DEPLOYMENT.

## Governance and baseline
- V12.3 governing requirements: F12-FR-006 AES-GCM private object encryption, F12-FR-008 protected delivery, F12-NFR-001 object authorization, F12-CEN-01 unified rights and storage; CF04-FR-015 PDF pipeline public/private storage isolation.
- Exact parent source: R74 Draft PR #70 at SHA `649f4cd97cf31623c06c5bb963578416b10b5fe9` (plugin `1.1.0-rc.49`, seven GitHub CI jobs successful).
- R75 isolated Draft PR #71 plugin `1.1.0-rc.50`; declared DB/schema remains `1.1.0`; main README-only `2b369ef2d609247896e1ab32118f1dc498407f51`.
- The candidate source inventory holds **58 plugin file identities**. Immutable rc.43 baseline and rc.44–rc.49 source delta overlays are retained. New rc.50 overlay includes only the changed storage PHP file and plugin version marker.

## RED-first source defect
`PLDR_Storage::root('pldr')` previously protected its private directory and checked whether its realpath was inside the HTTP document root. But the **CF04/SPL shared `spl` scope** skipped both the public-root test and `protect`, while an **unknown scope** defaulted to the File12 path but skipped `pldr`-only safety checks. A misconfigured shared storage directory inside the public webroot could therefore be returned as a permitted object path.

This is a **source configuration vulnerability**, *not* verified exposure of a real PDF or a proven Hostinger exploit. It is specifically relevant to the currently UNVERIFIED CF04 shared-storage acceptance gate.

`tests/test-r75-storage-private-root.php` creates only synthetic temporary folders with `ABSPATH`, `DOCUMENT_ROOT`, an in-webroot shared directory and an outside-webroot private vault; it never uses or reads actual site storage. First RED GitHub Actions run `43705352857`: PHP 8.1/8.3/8.4 jobs failed `shared-owner storage inside public webroot must fail closed`; ordinary File12 outside-webroot private root passed.

## Candidate correction
- Accept only explicit `pldr` and `spl` storage scopes; reject others with `pldr_storage_scope`.
- Resolve each directory's true path (including symlink targets) and deny paths equal to or within **either** HTTP `DOCUMENT_ROOT` **or** WordPress `ABSPATH`. The ABSPATH fallback also covers WP-CLI/cron requests without a server DOCUMENT_ROOT.
- Existing privately managed File12 root still creates and protects its own directory **only after** public root rejection. Shared owner root remains read-only to File12, with no attempt to change CF04-managed permissions.
- `PLDR_Storage::path()` inherits these guards, preventing a route from bypassing root checking.
- Expected positive cases: ordinary File12 private root outside public root remains functional; shared under public root and unknown scope fail closed.
- Source green CI reference `43705676653` applies to exact candidate SHA `98e248fa275cb56d4c65c46d243b3bbf6e089d43`, only when GitHub reports a completed success; after recording source evidence, verify latest branch-head CI separately.

## Remaining releases/proofs
Even after this source correction, a real CF04 private root, malware scanner, keys, key rotation, original PDF/OCR processing, offsite backup/restore, 10 STO controls and truly revoked cached consumer index are *not* proven. Physical Hostinger installed package, DB, migration and runtime are unknown.

**Defect count R64–R75: 12 reproduced source defects; 12 source-candidate corrections in unmerged Draft chains; zero Live-verified corrections. Ten major stop-ship categories remain.** Source-only proof does not reduce 39 historic Stage, 54 runtime, 10 CF04, 77 V12.3 or 60 QA case runtime UNKNOWN status.

**Mandatory reality ledger:** Repository main `2b369ef2d609247896e1ab32118f1dc498407f51`; Deployed Version UNVERIFIED; Physical DB Version UNVERIFIED; Migration State UNVERIFIED; Live Verification NOT PERFORMED; release NO_GO.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
