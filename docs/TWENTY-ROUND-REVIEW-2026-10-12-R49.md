# File12 R49 — twenty bounded governing-source inspections and one batch of private Reader Preferences integrity fixes
Date: 12 October 2026 (Pakistan). **Exact repository parent inspected:** R48 DRAFT PR #43 SHA `37dc2b6ef18914714c804274b61f260f924ddf8c`. R48 Actions `38229366637` passed only for that older parent commit, not automatically for R49. GitHub `main` stays README-only `2b369ef2d609247896e1ab32118f1dc498407f51`.

## Actually consulted written specifications / canonical module boundaries
- Supplied File12 *PDF Library and Digital Reading Complete Master Plan 2026 v1.1 Future-24 Amended (11 August 2026)*, FR001–019/NFR001–010/FUT001–024. The applicable exact passages: FUT001 Advanced Reflow text-size/line-height/column-width/contrast; FUT002 TTS reader rate/language/voice; FUT013 low bandwidth Data Saver; FUT014 responsive single/continuous/two-page RTL and LTR/presentation layouts; section 11.4 `Reader preferences: user-scoped versioned preferences; global profile truth نہیں` and §11.6 private reading state protection.
- Supplied *Three Central Plans Consolidated Governing Master Plan*: File00 identity and account private state/roles; File05 education, File06 scholarly knowledge/citation, File20 shell, File21 publishing, File23 author dashboard, File24 privacy/security, File25 visual UI, File26 discovery and CF04 media ownership. File12 may store domain-local reader preferences, not global identity or medical records.
- Companion files File05, File06, File21, File23 and File25 were supplied in the current SH Website project. Their owner boundaries inform source contract comparison; their **installed code and version parity are NOT verified**.

## Twenty frozen scoped code reviews (all before R49 code modification)
These are **20 separate bounded topic-to-source inspections**. Reviews 1–18 primarily check the existence of the relevant source methods/guards and do **NOT** constitute independently rerun complete line-by-line or WordPress E2E checks. Reviews 19–20 inspect actual code branches of `PLDR_Future_Preferences`.

| Review | Governing specification / owner | Focus | Frozen result |
|---|---|---|---|
| 01 | F12-FR-001; File26 canonical discovery | catalog/filtering search methods | Source path exists |
| 02 | F12-FR-002; CF04 secure intake | PDF validation and scanner gates | Source path exists |
| 03 | F12-FR-003; File24 rights/privacy | copyright cases, decisions | Source path exists |
| 04 | F12-FR-004 | document edition lineage | Source path exists |
| 05 | F12-FR-005/FUT-024 | duplicate candidate fingerprints | Source path exists |
| 06 | F12-FR-006 | encrypted PDF original | Source path exists |
| 07 | F12-FR-007 | permissioned OCR derivatives | Source path exists |
| 08 | F12-FR-008; File00 | current entitlements/calendar rights | Source path exists |
| 09 | F12-FR-009 | bound signed delivery + byte range | Source path exists |
| 10 | F12-FR-010; Files20/25 | responsive digital reader | Source path exists |
| 11 | F12-FR-011 | account-private progress | Source path exists |
| 12 | F12-FR-012 | private note/tag integrity | Source path exists |
| 13 | F12-FR-013; File26 | lawful searchable OCR and cursors | Source path exists |
| 14 | F12-FR-014; File06 | stable edition/page scholarly citations | Source path exists |
| 15 | F12-FR-015; Files05/06 | lesson/encyclopedia context links | Source path exists |
| 16 | F12-FR-016; Files21/23 | canonical publishing event bridge | Source path exists |
| 17 | F12-FR-017; File24 | takedown, appeals and restrictions | Source path exists |
| 18 | F12-FUT-012; File00/24 | offline rights reauthorization | Source path exists |
| 19 | F12-FUT-001/002/013/014; central account preferences | new reader preference writes | R49-D01 untyped nested fields coerced |
| 20 | F12-FUT-001/013/014; central privacy controls | historic stored reader preferences | R49-D02 JSON list/invalid values projected |

## Two proven defects collected, then corrected together
**R49-D01 — malformed new private Reader Preferences silently coerced**. Native `PLDR_Future_Preferences::sanitize` formerly accepted `data_saver:'false'` as true, `text_size:'120'` as 120, `tts_rate:'1.25'` as numeric, invalid array/boolean layout/voice via scalar conversion. `PLDR_Future_REST::preferences_save` validates outer `value` array, not inner typed fields. Fix: private canonical `validate_value_shape` validates approved layout/contrast enumerations, finite numeric/whole sizing, typed JSON boolean Data Saver, typed text/voice/language and rejects unknown/positional fields. Native `save` invokes this **before reading existing private DB state or performing writes**. Legal values still pass through existing bounded sanitizer; optimistic version updates remain intact.

**R49-D02 — historical stored user preferences incompletely validated**. `get` previously checked only `is_array(json_decode(...,true))`, accepting nonempty JSON lists and malformed nested values, projecting unreviewed/private keys or a historically incorrect Data Saver. Fix: `get` now differentiates JSON map from positional list (only legitimate empty legacy `[]` or `{}` may mean no preferences), uses exact same typed validator and canonical sanitizer equivalence. Corrupt historic rows return `pldr_future_pref_corrupt` (audited, original version available for reconciliation) without partial private setting projection or changing physical DB rows. Valid RTL/data-saver/TTS preferences are retained.

**Actual executable regression**: `tests/test-r49-reader-preferences-integrity.php` invokes real PHP native GET and SAVE with isolated WordPress + simulated DB, invalid new types with zero reads/writes, historical malformed JSON and maps, valid typed settings, valid legacy empty data, and optimistic version updates. This is source fixture evidence **not** real site validation.

**Source software candidate `1.1.0-rc.28`**. Source schema and interface contract version `1.1.0` unchanged, no migration. CI on final exact new HEAD + ZIP checksum pending until successful run.

## Deployment parity, release and governing non-claims
The written original inner plugin folder `12-pdf-library-foundation` and built source installer inner folder `pdf-library-foundation-12` differ and require Founder-controlled compatibility/rollback approval. Actual Hostinger deployed plugin code/version/checksum; physical DB tables/columns/rows/schema/`wp_options`/migration state; installed File00/01/05/06/16/17/19/20/21/23/24/25/26/CF04; real account permissions/IDOR/private preference behavior, RTL/offline/screen readers; backup/restore/security/OCR; two independent fresh staging cycles; Founder signoff; production deployment and live retest ALL UNVERIFIED.

| Five live-first realities | Status |
|---|---|
| Repository HEAD | Frozen R48 parent `37dc2b6ef18914714c804274b61f260f924ddf8c`; R49 exact final SHA/CI to be captured |
| Deployed Version | UNVERIFIED |
| Physical DB Version | UNVERIFIED (source schema 1.1.0 only) |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
