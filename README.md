# QuickQuiz

QuickQuiz v2 is a modern assessment platform for educators and learners. It is being rebuilt around secure role-based workflows, reliable quiz delivery and scoring, meaningful analytics, live sessions, accessibility, automated testing, and an interface designed specifically for assessment.

## Current status

Rounds 1 through 7A are merged. The app supports educator authoring, learner attempts and scoring, live rooms, results, analytics, certificates, and notifications. Round 7B is completing quiz lifecycle and live-session workflows. Production email, PostgreSQL verification, browser checks, and deployment remain before release. The original procedural PHP application remains recoverable from the `legacy-v1.0` Git tag.

## Technology

- PHP 8.5 and Laravel 13
- Blade, Tailwind CSS 4, vanilla JavaScript, and Vite
- SQLite for local development/tests; PostgreSQL planned for production
- PHPUnit, Larastan/PHPStan, and Laravel Pint
- GitHub Actions for backend and frontend verification

## Local setup

Prerequisites: PHP 8.5 with the standard Laravel extensions, Composer 2, Node.js 24, and npm.

```powershell
composer install --no-interaction --prefer-dist
composer run setup
composer run dev
```

The setup command copies `.env.example` when needed, generates the application key, creates the local SQLite database, runs migrations, installs the exact frontend lockfile, and builds production assets.

Open `http://127.0.0.1:8000`. The development command starts the web server, queue worker, and Vite. The application health check is available at `/up`.

Local email uses the `log` mailer by default, so verification and password-reset links are written to `storage/logs/laravel.log`. The verified demo accounts below are the simplest way to try both workspaces before SMTP is configured.

### Local demo accounts

Running `php artisan db:seed` in the local environment creates two idempotent, verified demo accounts and a starter quiz for the demo educator. The demo seeders refuse to run in production.

| Workspace | Email | Password |
| --- | --- | --- |
| Educator | `educator@demo.quickquiz.test` | `DemoQuickQuiz1!` |
| Learner | `learner@demo.quickquiz.test` | `DemoQuickQuiz1!` |

For production, set `SESSION_SECURE_COOKIE=true`, keep `SESSION_ENCRYPT=true`, serve only over HTTPS, and never deploy these demo credentials.

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
- [Round 2C identity security and auditability](docs/v2/ROUND_2C_PLAN.md)
- [Round 3A quiz domain foundation](docs/v2/ROUND_3A_PLAN.md)
- [Round 3B educator quiz management](docs/v2/ROUND_3B_PLAN.md)
- [Round 3C question builder and publishing](docs/v2/ROUND_3C_PLAN.md)

## Product direction

- Deliberate quiz authoring, scheduling, publishing, and preview.
- A focused timed assessment experience with autosave and accessible navigation.
- Server-authoritative attempts, scoring, review policies, and certificates.
- Real educator and learner analytics without placeholder statistics.
- Live hosted quizzes with join codes and meaningful leaderboards.
- Responsive student and educator workspaces with distinct information needs.
