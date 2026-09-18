# QuickQuiz v2 — Round 1C Quality and Delivery

Status: Verification in progress

## Objective

Prove the complete application foundation with project-owned verification commands, meaningful HTTP contracts, reproducible CI, and a reviewable GitHub branch.

## Scope

- Review the value, isolation, naming, and determinism of the foundation test suite.
- Cover the configured JSON error boundary for API routes.
- Use the same Composer and npm verification entry points locally and in CI.
- Bound CI execution time and retain actionable PHPUnit failure annotations.
- Run formatting, static analysis, tests, migrations, cached configuration, production builds, and dependency audits.
- Prepare the sequential Round 1 work for review without merging to `main` automatically.

## Acceptance criteria

- [x] Round 1C has a dedicated branch and bounded scope.
- [x] Existing foundation tests each protect a distinct observable contract.
- [x] API routes return structured JSON errors even without an `Accept` header.
- [x] `composer run verify` covers Composer validation, formatting, static analysis, and backend tests.
- [x] `npm run check` covers the production build and dependency audit.
- [x] CI invokes the project-owned verification commands and has explicit timeouts.
- [x] Fresh migrations and configuration/route caching pass locally and are required by CI.
- [x] All local verification and dependency security gates pass.
- [ ] GitHub Actions passes from the final Round 1C commit.
- [ ] Round 1C is committed and pushed, ready for review into `main`.
