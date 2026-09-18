# QuickQuiz

QuickQuiz v2 is a modern assessment platform for educators and learners. It is being rebuilt around secure role-based workflows, reliable quiz delivery and scoring, meaningful analytics, live sessions, accessibility, automated testing, and an interface designed specifically for assessment.

## Current status

Round 1 is establishing the Laravel application foundation, product-owned design system, and automated quality gates. Authentication begins in Round 2. The original procedural PHP application remains recoverable from the `legacy-v1.0` Git tag.

## Technology

- PHP 8.5 and Laravel 13
- Blade, Tailwind CSS 4, Alpine.js, and Vite
- PostgreSQL in production and SQLite for local development/tests
- PHPUnit, Larastan/PHPStan, and Laravel Pint
- GitHub Actions for backend and frontend verification

## Local setup

Prerequisites: PHP 8.5 with the standard Laravel extensions, Composer 2, Node.js 24, and npm.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
npm ci
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`. The application health check is available at `/up`.

## Verification

```powershell
php artisan test --compact
php vendor/bin/phpstan analyse --memory-limit=1G
php vendor/bin/pint --format agent
npm run build
```

## v2 documentation

- [Round 0 audit](docs/v2/ROUND_0_AUDIT.md)
- [Product specification](docs/v2/PRODUCT_SPEC.md)
- [Target architecture](docs/v2/ARCHITECTURE.md)
- [UX and design-system brief](docs/v2/UX_SYSTEM.md)
- [Delivery rounds](docs/v2/DELIVERY_ROUNDS.md)
- [Architecture decisions](docs/v2/DECISIONS.md)
- [Round 1 foundation plan](docs/v2/ROUND_1_PLAN.md)

## Product direction

- Deliberate quiz authoring, scheduling, publishing, and preview.
- A focused timed assessment experience with autosave and accessible navigation.
- Server-authoritative attempts, scoring, review policies, and certificates.
- Real educator and learner analytics without placeholder statistics.
- Live hosted quizzes with join codes and meaningful leaderboards.
- Responsive student and educator workspaces with distinct information needs.
