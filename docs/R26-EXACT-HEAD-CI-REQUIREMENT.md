# R26 exact-HEAD CI trigger and release gate — 2026-10-10

The prior R25 green GitHub Actions run applies only to the R25 SHA. R26 modified real authorization behavior, added a standalone PHP negative-path test, and updated traceability documents. The R26 candidate is **not Automated-QA Green** unless the immutable final SHA completes `.github/workflows/file-12-quality.yml` with the PHP 8.1/8.3/8.4, full R1–R26 regression sweep, JavaScript checks and deterministic package jobs succeeding.

A GitHub Actions workflow is configured for `pull_request` and `workflow_dispatch`. R26 is kept a Draft PR against the R25 branch. This document is a source-trigger commit to request/surface a fresh `pull_request synchronize` event without falsely claiming a CI result.

If no workflow attaches to the R26 SHA: **BLOCKED / CI NOT STARTED**. Investigate GitHub Actions event permissions, base-branch workflow eligibility and CI configuration. Do not merge, package for production, deploy, or claim resolved based on an older R25 workflow.

Deployed version, database state, migration and live re-test remain unverified.
