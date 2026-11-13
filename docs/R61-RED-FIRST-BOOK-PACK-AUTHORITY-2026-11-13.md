# R61 File12 Book Content Pack Authoritative Actor Gate (2026-11-13)

Frozen parent R60: source SHA 0b8066b9104d30053e11bb6853b1da4a276a2df3; version 1.1.0-rc.39; CI 43369443677 4/4 GREEN; main still README-only.

RED #1: SHA 9e167ec108daa4a08297a56736cc3c19815f353c, CI run 43370025549, observed PHP failure R61 FAIL: anonymous actor zero must deny before any manifest processing or database work. Original public Book Pack writer `if($actor_id && ...)` omitted authorization when actor was 0.
RED #2: SHA 31e753662a7a1e68526b1b2497622dbb155e58e9, CI run 43370196026, observed PHP failure R61 FAIL: anonymous caller supplying an authorized third-party actor ID must deny. Public Book Pack caller could supply an existing manager ID without proving the current session owns it.

Corrective source: private `persist_authorized` separates controlled bundled files from ordinary API; `register` now denies unless current login session >0, requested actor matches the actual logged-in session, and `PLDR_Core::authorize(manage)` or founder status approves current actor.
Preserves plugin-controlled `scan_bundled_manifests()` call through private persistence, rather than accidentally breaking trusted system import.
New fixture `tests/test-r61-book-pack-actor-authority.php` checks anonymous zero, unauthorised manager, forged explicit actor, authorised third-party ID with anonymous current session, authorized current manager reaching standard required-manifest validation, private trusted loader invariant.

Release boundary: source-only candidate 1.1.0-rc.40, declared DB/contract 1.1.0. No physical WordPress/Hostinger code, actor routing, DB/migration, Book Pack publication or File26 search integration verified. A source-only privilege check is not an assertion that an unauthenticated external WordPress endpoint was exploitable: the REST route has an independent permission_callback.

Open stop-ship: File26 owner contract still requires PDF+book_pack, the File12 proposed File26 connector indexes only PDFs; File26 issue #23. File12 outbox sent still not proven File26 tombstone acknowledgement; File12 issue #53. CF04 runtime not active, Hostinger site disconnected, 39 staging + 54 requirement + 10 shared security gates all unverified. Correct NO_GO.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔