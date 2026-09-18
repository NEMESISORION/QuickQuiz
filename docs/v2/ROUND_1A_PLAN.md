# QuickQuiz v2 — Round 1A Environment and Repository

Status: Complete

## Objective

Make the QuickQuiz repository reproducible from a clean checkout without relying on untracked machine state.

## Scope

- Pin and document the PHP, Composer, Node.js, and framework requirements.
- Install backend and frontend dependencies strictly from their lockfiles.
- Keep secrets, generated assets, local databases, and local tools outside Git.
- Bootstrap the application key, SQLite database, migrations, and production assets with one documented setup command.
- Verify that Laravel configuration and routes can be cached safely.
- Prove the setup on both the local Windows environment and GitHub's Linux runner.

## Acceptance criteria

- [x] Round 1A has a dedicated branch and bounded scope.
- [x] Composer and npm lockfiles are committed.
- [x] Local secrets, generated files, dependencies, and tools are ignored.
- [x] Application code does not read environment variables outside configuration files.
- [x] `composer run setup` succeeds from a clean checkout.
- [x] Database migrations succeed against a newly created SQLite database.
- [x] Configuration and route caches build successfully.
- [x] Backend and frontend verification commands pass locally.
- [x] Production CSS is deterministic and ignores stale compiled views.
- [x] GitHub Actions passes from the final Round 1A commit.
- [x] Round 1A is committed and pushed before Round 1B starts.
