# QuickQuiz

QuickQuiz v2 is a modern assessment platform for educators and learners. It is being rebuilt around secure role-based workflows, reliable quiz delivery and scoring, meaningful analytics, live sessions, accessibility, automated testing, and an interface designed specifically for assessment.

## Current status

Rounds 1 through 7D are merged. The app supports educator authoring, learner attempts and scoring, live rooms, results, analytics, certificates, and notifications. The next milestone is public hosting with persistent PostgreSQL and real email delivery. The original procedural PHP application remains recoverable from the `legacy-v1.0` Git tag.

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

The setup command copies `.env.example` when needed, generates an application key only if one is missing, creates the local SQLite database, runs migrations, installs the exact frontend lockfile, and builds production assets. Never rotate `APP_KEY` on an existing installation: doing so invalidates encrypted sessions and stored encrypted data.

Open `http://127.0.0.1:8000`. The development command starts the web server, queue worker, and Vite. The application health check is available at `/up`.

Local email uses the `log` mailer by default, so verification and password-reset links are written to `storage/logs/laravel.log`. The verified demo accounts below are the simplest way to try both workspaces before SMTP is configured.

For real email delivery, set `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `APP_URL` in your local `.env` or the hosting platform's secret settings. The sender address must belong to a domain verified with your mail provider. Set `MAIL_SCHEME=smtps` for implicit TLS, or use `smtp` for STARTTLS when your provider specifies it. Run a queue worker alongside the web app for queued work, and test registration, resend verification, and password reset with an inbox you control. Never commit provider credentials or use the `log` mailer in production.

### Local demo accounts

Running `php artisan db:seed` in the local environment creates two idempotent, verified demo accounts and a starter quiz for the demo educator. The demo seeders refuse to run in production.

| Workspace | Email | Password |
| --- | --- | --- |
| Educator | `educator@demo.quickquiz.test` | `DemoQuickQuiz1!` |
| Learner | `learner@demo.quickquiz.test` | `DemoQuickQuiz1!` |

For production, set `SESSION_SECURE_COOKIE=true`, keep `SESSION_ENCRYPT=true`, serve only over HTTPS, and never deploy these demo credentials.

## Public deployment preparation

The repository includes a PHP 8.5 Docker image for a Render web service. It serves Laravel from `public/`, builds frontend assets during image creation, runs migrations on startup (Render Free does not include a pre-deploy command), and listens on Render's `PORT`. Use a persistent external PostgreSQL database such as Neon; neither SQLite on Render's filesystem nor Render Free PostgreSQL is suitable for durable portfolio data.

Set these values in the host's secret/environment settings, not in Git:

| Setting | Production value |
| --- | --- |
| `APP_ENV`, `APP_DEBUG`, `APP_URL` | `production`, `false`, and the final HTTPS site URL. On Render, `APP_URL` may be omitted to use Render's `RENDER_EXTERNAL_URL` automatically. |
| `APP_KEY` | One generated Laravel key; retain the same key across redeploys |
| `DB_CONNECTION`, `DB_URL` | `pgsql` and the provider's PostgreSQL connection URL with TLS required |
| `SESSION_DRIVER`, `SESSION_SECURE_COOKIE`, `SESSION_ENCRYPT` | `database`, `true`, `true` |
| `CACHE_STORE`, `QUEUE_CONNECTION` | `database`, `sync` (no separate worker on the free web service) |
| `LOG_CHANNEL`, `LOG_LEVEL` | `stderr`, `warning` |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT` | `smtp`, `smtp-relay.brevo.com`, `2525` |
| `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | Brevo SMTP credentials and a sender verified in Brevo |

Do not deploy until the email sender is ready. The start command rejects missing or unsafe production settings. After deployment, test registration, verification, resend, password reset, educator publishing, and learner attempts on the public URL. A green `/up` health check alone does not prove the database or email service works. Keep Render auto-deploys off while testing to avoid unnecessary builds.

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
