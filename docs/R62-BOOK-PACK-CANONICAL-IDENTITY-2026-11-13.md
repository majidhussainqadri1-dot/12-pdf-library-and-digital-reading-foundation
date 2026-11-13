# R62 — Book Content Pack immutable identity and schema-safe input audit (2026-11-13)

## Evidence chain
- Parent PR #54 rc.40 Git HEAD `f35bc055489e0764925a8a7b6009e2fe06b41405`, GitHub Actions 43370443588 (4/4 successful). No production assertion.
- RED-first isolated regression SHA `f410c0789accf008f14a70f994ef5c98b248e561`, GitHub Actions 43376844903: PHP matrix fails because a raw nonempty `pack_key='!!!'` normalizes to empty and reaches a DB `prepare()` call instead of returning a controlled validation error.
- Corrective source SHA `854e69e5e533ac6e25df7d2c7dd5fe962643bb6d`, GitHub Actions 43377002228: all 4 jobs GREEN on corrected source, before rc.41 release identity.
- Candidate software `1.1.0-rc.41`; declared schema/contract `1.1.0`; new exact candidate tests needed.

## Root cause
`PLDR_Book_Packs::persist_authorized()` checked each required field with `empty` before canonicalization. But WordPress `sanitize_key` could remove all characters from a nonempty `pack_key` and `sanitize_text_field` could reduce a whitespace-only title/author/language/version to an empty value. It also did not enforce the physical schema bounds `pack_key VARCHAR(120)`, `pack_version VARCHAR(40)` before DB operations. Nonstring JSON-shaped rights/checksum fields were not rejected before sanitizer/regex calls.

## Scoped correction
- Required key/version/title/author/language/rights/checksum must be real strings; canonical pack key regex and 120-byte upper bound, version regex and 40-byte upper bound.
- Normalize and require nonempty author, title and language before any SQL.
- Preserve existing `register()` current-session authentication, private controlled bundled import, immutable pack SHA comparison and transaction/outbox atomicity; no schema or external owner change.
- New standalone R62 source regression checks 10 malformed input classes, with a database trap asserting every invalid pack is rejected *before DB operations*.

## Unfinished acceptance
- Real Hostinger DB Unicode/collation and existing pack/import compatibility, legitimate import success, role/nonce/source-version parity, safe licence evidence, genuine File26 Book Pack public object/list/rights contract, versioned tombstone/consumer ack, cache invalidation, backup/restore, and original 39 Staging tests remain pending.
- No `book_pack` File26 implementation is advertised; the connector stays proposed/unregistered rather than exposing protected metadata.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
