# R60 — No-Go release evidence gate (13 November 2026)

Goal: prevent misleading CI-green = production-ready conclusions. This source-only PHP CLI tool is NOT a deployer and NEVER updates a WordPress site.

Usage: php scripts/file12-release-no-go.php predeploy
       php scripts/file12-release-no-go.php postdeploy

Predeploy requires real Staging PASS with reviewer+evidence for all 39 original File12 tests, all 54 requirements, all 10 CF04 shared security controls, plus separate proof fields: freeze current live reality; staged integration parity; backup restore; rollback; Founder signoff; rights tombstone cutover; File26 Book Pack contract; CF04 owner readiness; exact release candidate Git SHA.

Postdeploy additionally requires verifiable LIVE PASS for the 54 requirement and 10 CF04 checks, deployed package SHA256, actual DB Migration state, live retest and independent deployment parity proof. The 39 original staging tests must still remain evidenced. Per-row evidence can require stricter additional checks in real release management; a static evidence string never substitutes human validation or proof of legal rights.

Current JSON evidence states are all UNVERIFIED and empty. Therefore this tool must print NO_GO and return status 2 in predeploy and postdeploy. Source CI asserts this expected refusal. CI test PASS proves the guard refuses the current unauthorised state, not that the site is safe/live ready.

File26 Book Pack mismatch (issue #23), File12 outbox consumer acknowledgement (issue #53), CF04 source runtime disabled, Hostinger Jetpack disconnected and WPVibe HTTP530 continue to prevent a trustworthy release. Deployed Version/DB/Migration/Live Verification UNVERIFIED.

Do not mark a live incident resolved before package deploy, live re-test and parity confirmation.