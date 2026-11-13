# R54 — File26 truthful manifest and disabled state (13 November 2026)

## Exact source history
- Parent R53 candidate: `2f371a1b8b85cf575c5aa9f975cff80d3a80a568`, `1.1.0-rc.32`, CI run `43356493153` successful 4/4.
- RED-first regression `6add1132b32928e9770f44f074cafb5765b07f0c` failed in GitHub run `43357047338` at unsupported File26 `book_pack` claim.
- Corrective source `03bdebba50d914a3c6fdf8b5db1031f618df4532` fixes exact contract claims; R54 unique candidate `1.1.0-rc.33`, source schema + integration version `1.1.0`.

## Reproducible source defects
1. `PLDR_File26_Connector::manifest()` said `entity_types=['pdf','book_pack']` but `fetch_object()` accepts `pdf` only and `list_batch()` yields only PDF. An owner could interpret the manifest as proof of a feature that cannot be fetched.
2. `health()` returned `state=healthy` based solely on File12 schema availability even though `status=proposed`, event/tombstone cutover pending, and runtime registration intentionally disabled. This conflated source readiness with operational readiness.

## Scoped remedy
- Advertise the PDF type alone until book-pack contracts are actually coded and accepted.
- Distinguish `degraded` source/schema from `disabled` unapproved adapter, and `healthy` only after the exact versioned rights/tombstone approval guard.
- Keep `register()/append()/ready()` fail-closed from R52, no indexing event activated.

## Permanent acceptance gates
File26's native indexer, rights policy versioning, deletion/withdrawal tombstones, cache/query invalidation, cross-user search and real Hostinger WordPress remain unverified. Neither source CI nor artifact checksum proves deployed integrity.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
