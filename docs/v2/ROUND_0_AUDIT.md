# QuickQuiz v2 — Round 0 Audit

Status: In progress

Branch: `v2-round-0-discovery`

## Purpose

QuickQuiz v2 is a deliberate rebuild of the existing quiz application into a secure, testable, accessible, and portfolio-ready assessment platform. The two existing applications remain reference implementations; their procedural PHP structure will not be copied into the new core.

## Sources reviewed

### `QuickQuiz` — primary repository

Strengths:

- Working local PHP 8.2 application.
- SQLite bootstrap makes the demo easy to run.
- Git history and GitHub remote already exist.
- Railway/Nixpacks deployment configuration exists.
- Password hashing and prepared statements are used.
- Core admin, student, quiz-taking, and scoring paths exist.

Gaps and risks:

- Pages combine request handling, database access, business rules, and HTML.
- Dashboard metrics, achievements, quiz metadata, and certificate content include hardcoded placeholders.
- Database fields such as publishing rules, points, negative marking, and certificates are not fully wired into the product.
- No registration, password recovery, user management, or profile workflow.
- No CSRF tokens, login throttling, session regeneration, formal authorization layer, or security headers.
- No automated test suite, static analysis, formatter, or CI pipeline.
- SQLite is convenient locally but the deployed database needs persistent production storage.
- Repeated page chrome and extensive inline styles make visual consistency difficult.
- No product README, screenshots, architecture documentation, or release process.

### `Quick_Quiz` — donor/reference application

Potentially reusable product ideas:

- Registration and password-change flows.
- Admin user management and analytics.
- Result export and certificate pages.
- Dark-mode behavior.
- Richer student dashboard and quiz presentation.
- Existing test scenarios and endpoint inventory as requirements references.

Constraints:

- Requires MySQL while the available local PHP installation currently exposes only PDO SQLite.
- Contains the same page-level coupling and repeated UI patterns as the primary version.
- Test documentation claims broad coverage, but it is not backed by a reproducible dependency lockfile and green CI run.
- CSS and JavaScript may inspire tokens and interactions, but should be reviewed and rebuilt as components.

## Reuse policy

| Asset type | Policy |
| --- | --- |
| Product concepts and workflows | Reuse after validation |
| Copy, labels, and demo content | Adapt and rewrite |
| Color ideas and visual motifs | Use as design input only |
| CSS/JavaScript | Selectively port only after component review |
| Database data model | Use as domain discovery input |
| Procedural PHP page logic | Do not copy into the new core |
| Legacy tests | Convert useful scenarios into new automated tests |

## Baseline toolchain

- PHP 8.2.18: available.
- Node.js 24.19.0: available.
- Composer: not installed yet.
- npm: installed, but PowerShell script execution blocks `npm.ps1`; `npm.cmd` can be used.
- Git remote: `https://github.com/NEMESISORION/QuickQuiz.git`.

## Round 0 exit criteria

- [x] Baseline repository and toolchain recorded.
- [x] Both legacy applications inventoried.
- [x] Reuse and migration policy defined.
- [x] Product scope and non-goals documented.
- [x] Target architecture documented.
- [x] UX principles, information architecture, and design tokens documented.
- [x] Delivery rounds and quality gates documented.
- [ ] Architecture decision records reviewed and accepted.
- [ ] Composer installation approach agreed before Round 1 scaffolding.
- [ ] Round 0 documents committed and pushed.
