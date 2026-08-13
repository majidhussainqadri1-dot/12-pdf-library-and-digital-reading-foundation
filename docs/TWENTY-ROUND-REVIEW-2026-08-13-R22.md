# File 12 — R22 Fresh Twenty-Round Corrective Review — 2026-08-13

## Governing review discipline

R22 started from exact green R21 repository head `89cb5cdd6fbcb21b9e4f7e6947ce032c646212b3`.

Every numbered round followed the Founder-mandated sequence: **finish the complete review first → close that round's complete defect ledger → only then apply all proven corrections as one post-review batch → retest → only then begin the next numbered round.** No defect was fixed while its numbered review was still underway.

Repository review evidence is not staging/live evidence. Exact deployed version, deployed schema, migration state and live behavior remain separately unverified.

## Round 1
**Focus:** governing-plan reconciliation, repository/package namespace, version markers, traceability and File-12 ownership boundary.
**Result:** Clean. No new proven repository defect.

## Round 2
**Focus:** core REST authorization ordering, delivery IDOR/existence leakage, reader/download token issuance and denial side effects.
**Result:** Defects found. Reader-access and download-session paths could reach edition/object state and replay reservation before the strongest delivery eligibility check, allowing unnecessary authorization-state differences.
**Post-review batch:** added generic delivery preauthorization before idempotency/token issuance so unavailable/unauthorized objects fail before mutation state is reserved.

## Round 3
**Focus:** PDF ingest, rights/source metadata, patient-case privacy, cover ingestion and upload safety.
**Result:** Defects found. Patient Cases trusted a submitted consent flag without independent publication/anonymization clearance; uploaded cover images were decoded/type-checked but lacked the governed malware-scanner path.
**Post-review batch:** added fail-closed independent patient-case publication clearance and malware scanning for supplied cover images before encrypted storage.

## Round 4
**Focus:** encrypted object writes, key rotation, temporary plaintext/ciphertext, permissions and failure cleanup.
**Result:** Defects found. Temporary key-rotation plaintext/encrypted files were not deterministically removed on every early-return path, and newly opened crypto outputs were not explicitly hardened to owner-only file permissions.
**Post-review batch:** added `0600` output hardening and `try/finally` cleanup for key-rotation temporary files.

## Round 5
**Focus:** signed delivery, audience/object/operation binding, byte-range behavior, revocation, HEAD and tamper detection.
**Result:** Clean. No new proven repository defect.

## Round 6
**Focus:** rights reports, decisions, appeals, expiry, publication/restore and revocation semantics.
**Result:** Defect found. The affected document publisher/uploader could not appeal a rights case unless separately granted rights/manage authority.
**Post-review batch:** allowed the canonical affected publisher to appeal while retaining reporter/reviewer/manager checks and optimistic case-version protection.

## Round 7
**Focus:** reading progress, bookmarks, notes, private ownership and cross-device concurrency.
**Result:** Defect found. Reading progress used last-write replacement without an explicit client revision, so a stale tab/device could overwrite newer progress.
**Post-review batch:** added `expected_updated_at` optimistic concurrency, transactional row locking/CAS update, returned revision and reader-JS revision propagation.

## Round 8
**Focus:** catalog/search, filtering, access-filtered discovery, pagination and OCR search cursors.
**Result:** Clean. No new proven repository defect.

## Round 9
**Focus:** OCR correction model, citations, anchors and portable private annotations.
**Result:** Clean. No new proven repository defect.

## Round 10
**Focus:** privacy export/erase, legal hold, retention, minimization and private reading data.
**Result:** Clean. No new proven repository defect.

**First-ten defect rounds: 2, 3, 4, 6, 7**

**First-ten clean rounds: 1, 5, 8, 9, 10**

## Round 11
**Focus:** fresh install/upgrade, core/Future schema continuity and legacy File-12 migration.
**Result:** Defect found. A legacy WordPress `publish` state plus checksum could migrate an old PDF toward published status without current malware-rescan evidence.
**Post-review batch:** legacy objects now enter `legacy-imported-pending-rescan`; migrated documents remain scan/rights-review state and cannot auto-publish from legacy post status alone.

## Round 12
**Focus:** reliable events, explicit outbox contracts, retries/dead-letter and consumer governance.
**Result:** Defect found. Current code emitted `PDFDocumentIngested.v1` and `PDFDocumentOCRReady.v1`, but the governed R21 outbox registry did not list them, so valid events could be dead-lettered as unknown.
**Post-review batch:** added explicit privacy/consumer/retention contracts for both events.

## Round 13
**Focus:** Future-24 common data authorization, public/readable endpoints and existence leakage.
**Result:** Defect found. Common Future edition lookup fetched edition state before access eligibility, creating missing-vs-inaccessible response differences across multiple Future endpoints.
**Post-review batch:** Future edition access now checks entitlement first and returns a generic unavailable state before fetching protected edition data.

## Round 14
**Focus:** IIIF, OCR/citation/annotation portability and public derivative boundaries.
**Result:** Defect found. IIIF manifest paths still used distinguishable missing/unavailable document states.
**Post-review batch:** aligned IIIF missing/inaccessible states to the same generic unavailable response while preserving DB/provider failure diagnostics.

## Round 15
**Focus:** encrypted offline vault, offline grants, entitlement expiry and logout purge.
**Result:** Defect found. Offline-grant idempotency could be reserved before edition entitlement was checked.
**Post-review batch:** moved edition/offline authorization before idempotency reservation; local expiry and logout purge protections remain intact.

## Round 16
**Focus:** personal Smart Shelves, preferences, reading insights and cross-device private state.
**Result:** Defects found. Shelves exposed counts/add/remove but had no governed API to enumerate actual shelf contents, and shelf membership had no lifetime row bound.
**Post-review batch:** added owner-only cursor-paginated shelf-item listing with per-item entitlement rechecks plus a bounded 5000-item lifetime cap per shelf.

## Round 17
**Focus:** external providers, scholarly rooms, knowledge context, provenance and patient-case privacy.
**Result:** Defects found. Knowledge Context could forward selected Patient Case text without the separate privacy approval already required by other provider features; Reading Room provider failure audit could persist raw provider exception text.
**Post-review batch:** added fail-closed Patient Case provider approval for Knowledge Context and replaced raw provider exception text with bounded failure class metadata.

## Round 18
**Focus:** responsive/RTL/accessibility/reduced-motion/navigation/error states.
**Result:** Defects found. Future heatmap controls were below the 44px target objective, reader download-manager forced smooth scrolling despite reduced-motion preference, and reader fallback/error state lacked complete Home/retry/support-reference recovery affordances.
**Post-review batch:** restored 44×44 control targets, respected `prefers-reduced-motion`, and added canonical Home/Library/retry/support-reference recovery UI.

## Round 19
**Focus:** operations, repair paths, cron/queues, cleanup, diagnostics and bounded maintenance.
**Result:** Defects found. REST/internal repair paths could bypass R21 canonical schema/outbox/legacy guards; expired completed idempotency rows had no global bounded cleanup; saturated token cleanup did not guarantee prompt continuation; some active provider diagnostics still recorded raw exception text.
**Post-review batch:** routed schema/outbox/legacy repairs through canonical R21 guards, added bounded expired-idempotency cleanup with continuation scheduling, and minimized active provider failure diagnostics to safe classes/codes.

## Round 20
**Focus:** final holistic adversarial pass over the fully corrected Round-19 state: authorization/side-effect ordering, idempotency, private-object IDOR, provider/exception leakage, migration, event/operations, UI and release evidence.
**Result:** Defects found. The complete review was finished before coding and identified three remaining cross-cutting groups:

1. **Protected mutation replay side effects:** several core/Future object mutations could reserve and retain idempotency state before object-specific authorization/ownership was resolved; private shelf, OCR review, reading-progress/item, rights-appeal, accessibility/preservation/fingerprint and other Future mutations needed explicit preauthorization or denial cleanup.
2. **Transient callback exception poisoning:** Future idempotency converted unexpected callback exceptions into a cached 500 replay result, potentially blocking safe same-key retry for the retention window.
3. **Raw exception leakage:** ingest/cover error responses and legacy/thumbnail diagnostic paths could expose or persist raw exception message text rather than bounded safe error classes.

**Post-review batch:** only after the complete Round-20 ledger was closed, core and Future mutation preauthorization was expanded; private shelf/OCR/review targets received owner/authority preflight; 401/403/404 replay reservations are aborted; unexpected Future callback exceptions abort reservation and return explicit retry-safe failure; and ingest/cover/migration/thumbnail diagnostics were changed to bounded safe messages/error classes.

## Final R22 accounting

**Reviews completed:** 20/20

**First-ten defect rounds:** **2, 3, 4, 6, 7**

**Final defect rounds:** **2, 3, 4, 6, 7, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20**

**Final clean rounds:** **1, 5, 8, 9, 10**

The numbered R22 sequence is complete. Permanent R22 regression coverage, STATUS/PR evidence and the exact-head CI/package gate must be taken from the final R22 branch head. A green repository CI/package does not establish staging/live truth.

## Production-truth boundary

Repository source/CI/package evidence proves only those repository/package gates. Hostinger staging deployment, deployed artifact checksum, deployed DB/schema/migration state, real-role/browser/accessibility/RTL/offline/provider/backup/restore/rollback journeys, Founder acceptance and live re-test remain separate gates.

Final release/incident reporting must state separately: **Repository HEAD / Deployed Version / DB Version / Migration State / Live Verification Status**.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
