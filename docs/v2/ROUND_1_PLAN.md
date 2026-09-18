# QuickQuiz v2 — Round 1 Foundation Plan

Status: Verification in progress

## Objective

Replace the legacy runtime with a clean, reproducible Laravel 13 foundation that enforces the architectural, UX, security, and quality decisions accepted in Round 0.

## Toolchain

- Official portable PHP 8.5 x64 NTS build stored under ignored `.tools/`.
- Official Composer installer verified against its published SHA-384 signature.
- Laravel 13 with locked PHP and JavaScript dependencies.
- Node.js and `npm.cmd` for Vite assets on Windows.

The repository-local toolchain avoids mutating the machine-wide PHP setup and makes the required runtime explicit.

## Work packages

### 1. Preserve the baseline

- Tag the final legacy application commit.
- Close and publish Round 0 documentation.
- Create the dedicated Round 1 branch.

### 2. Scaffold the application

- Replace the legacy runtime with a standard Laravel 13 structure.
- Preserve v2 documentation and Git history.
- Configure safe example environment defaults and local SQLite development.
- Update deployment configuration for the new public entry point and migration flow.

### 3. Establish architectural seams

- Add module namespaces for Identity, Quiz Authoring, Attempts, Results, Analytics, Live Sessions, and Shared concerns.
- Add an application health endpoint.
- Keep controllers and routes thin from the first commit.

### 4. Establish the UI foundation

- Encode semantic design tokens from the UX brief.
- Create accessible public and application layout primitives.
- Replace the framework welcome page with a QuickQuiz-specific landing shell.
- Verify responsive behavior at the smallest supported viewport.

### 5. Establish quality gates

- Configure formatter, static analysis, unit/feature tests, and frontend production build.
- Add GitHub Actions for PHP and frontend verification.
- Document repeatable setup and verification commands.

## Acceptance criteria

- [ ] A clean checkout can install locked dependencies with documented commands.
- [x] The application boots on PHP 8.5 and exposes a passing health check.
- [x] Database migrations run against local SQLite.
- [x] Backend automated tests pass.
- [x] Static analysis and formatting checks pass.
- [x] Frontend dependencies install and the production bundle builds.
- [x] The initial QuickQuiz UI shell is responsive and accessible by construction.
- [x] CI expresses every required verification command.
- [x] Legacy runtime remains recoverable from Git history and its release tag.
- [ ] Round 1 is committed and pushed only after all available gates are green.
