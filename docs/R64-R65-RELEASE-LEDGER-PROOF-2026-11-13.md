# R64/R65 — No-Go evidence-context and defect-retake control (2026-11-13)

## Source and tests
- Parent R63 candidate `24d90c3b38fa7bb93d78634f9e8b1053acad04b9` software `1.1.0-rc.42`, four GitHub jobs green in `43378582961`.
- **R64 source defect:** staged PASS in the original 39-gate CSV counted based solely on nonempty reviewer and arbitrary evidence id despite untouched `environment=HOSTINGER_STAGING_REQUIRED` and `observed=NOT_OBSERVED`. First failing SHA `e547345e0be37f63006fc3b2eca7665b642b0172`, workflow `43378939348`, pre+post scripts returned `SOURCE_LEDGER_GO` exit 0 on fabricated PASS fixtures. Corrective SHA `e2f87799a0c1b370bafc389ff9024227a8b9c1fa` refuses unobserved/placeholder staging context.
- **R65 source defect:** stage test could show `status=PASS` and `defect_id` while `retest_status=NOT_RUN`, yet count as release approved. First failing SHA `df0dd5908492dc0c081e03a2e23a53ec20e544a2`, workflow `43379286000`, both modes erroneously returned `SOURCE_LEDGER_GO`. Corrective SHA `baf0d5bfef6e1d3f62f368dde629ff52b519e355` requires explicit retest PASS for a recorded defect.
- Corrected source run `43379434645`: PHP8.1/8.3/8.4 + frontend-package 4/4 green; R60 real NO_GO unchanged, R64 anti-fake pre/post and R65 un-retested pre/post all blocked.

## Security scope / epistemic limits
- Both RED-first fixtures operate only on copied test CSV/JSON in temporary directories and do not change the actual evidence records or the live site.
- Valid looking proof IDs, reviewer strings and runtime/environment labels cannot verify the contents of any actual log, signatures, identity or cloud configuration. The release CLI is deliberately not a deployer and remains advisory to separately verified human approval.
- New software label `1.1.0-rc.43`; no physical schema/contract migration (`1.1.0` source declaration). No package deployment.
- Original 39 real Hostinger staging tests, 54 requirement runtime tests, 10 CF04 owner-security tests, two independent reviews, founder authorization, version parity and postdeploy re-test all remain unverified.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
