# QuickQuiz

QuickQuiz v2 is a modern assessment platform for educators and learners. It is being rebuilt around secure role-based workflows, reliable quiz delivery and scoring, meaningful analytics, live sessions, accessibility, automated testing, and an interface designed specifically for assessment.

## Current status

Round 1 is complete. Round 2A provides database-backed authentication and recovery; Round 2B adds verified email, one-time educator or learner onboarding, policy-protected profiles, server-authorized role workspaces, and responsive identity UI. Round 2C will close identity with audit history, demo accounts, and production hardening. The original procedural PHP application remains recoverable from the `legacy-v1.0` Git tag.

## Technology

- PHP 8.5 and Laravel 13
- Blade, Tailwind CSS 4, Alpine.js, and Vite
- PostgreSQL in production and SQLite for local development/tests
- PHPUnit, Larastan/PHPStan, and Laravel Pint
- GitHub Actions for backend and frontend verification

## Local setup

Prerequisites: PHP 8.5 with the standard Laravel extensions, Composer 2, Node.js 24, and npm.

```powershell
composer install --no-interaction --prefer-dist
composer run setup
php artisan serve
```

The setup command copies `.env.example` when needed, generates the application key, creates the local SQLite database, runs migrations, installs the exact frontend lockfile, and builds production assets.

Open `http://127.0.0.1:8000`. The application health check is available at `/up`.

## Verification

```powershell
composer run verify
npm run check
composer audit --locked
```

## v2 documentation

- [Round 0 audit](docs/v2/ROUND_0_AUDIT.md)
- [Product specification](docs/v2/PRODUCT_SPEC.md)
- [Target architecture](docs/v2/ARCHITECTURE.md)
- [UX and design-system brief](docs/v2/UX_SYSTEM.md)
- [Delivery rounds](docs/v2/DELIVERY_ROUNDS.md)
- [Architecture decisions](docs/v2/DECISIONS.md)
- [Round 1 foundation plan](docs/v2/ROUND_1_PLAN.md)
- [Round 1A environment and repository plan](docs/v2/ROUND_1A_PLAN.md)
- [Round 1B architecture and UI foundation](docs/v2/ROUND_1B_PLAN.md)
- [Round 1C quality and delivery](docs/v2/ROUND_1C_PLAN.md)
- [Round 2A authentication foundation](docs/v2/ROUND_2A_PLAN.md)
- [Round 2B roles and authorization](docs/v2/ROUND_2B_PLAN.md)

## Product direction

- Deliberate quiz authoring, scheduling, publishing, and preview.
- A focused timed assessment experience with autosave and accessible navigation.
- Server-authoritative attempts, scoring, review policies, and certificates.
- Real educator and learner analytics without placeholder statistics.
- Live hosted quizzes with join codes and meaningful leaderboards.
- Responsive student and educator workspaces with distinct information needs.
