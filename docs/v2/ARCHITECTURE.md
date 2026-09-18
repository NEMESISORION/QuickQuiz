# QuickQuiz v2 — Target Architecture

## Decision

Build v2 as a Laravel 13 modular monolith running on PHP 8.5, with server-rendered Blade views, Tailwind CSS, and Alpine.js for focused interactions. Use PostgreSQL in hosted environments and SQLite for fast automated tests where behavior is compatible.

This structure provides mature routing, validation, authentication, authorization, migrations, queues, events, and testing without introducing distributed-system complexity.

## Architectural boundaries

```text
Browser
  -> HTTP routes and middleware
    -> Controllers / request validation
      -> Application actions and queries
        -> Domain models, policies, and scoring services
          -> Eloquent repositories / database
    -> Blade components / JSON responses
```

Controllers coordinate requests; they do not contain scoring formulas or large database queries. Domain behavior is expressed through focused actions and services. Authorization policies guard each resource operation.

## Proposed modules

- `Identity`: authentication, profiles, roles, account security.
- `QuizAuthoring`: quizzes, questions, ordering, validation, lifecycle.
- `Attempts`: eligibility, timing, autosave, submission, scoring.
- `Results`: review policy, summaries, certificates, exports.
- `Analytics`: aggregates and educator reporting.
- `LiveSessions`: join codes, presence, progression, leaderboard.
- `Shared`: value objects, shared UI components, common infrastructure.

Laravel conventions remain the default; folders are introduced only when a real boundary needs them.

## Code placement convention

QuickQuiz keeps framework adapters conventional and groups business behavior by domain only after that behavior exists:

- `app/Http/Controllers/{Domain}` and `app/Http/Requests/{Domain}` contain HTTP coordination and validation.
- `app/Actions/{Domain}` contains focused application operations that coordinate a use case.
- `app/Domain/{Domain}` contains domain services, value objects, lifecycle rules, and enums that are independent of HTTP.
- `app/Models` contains Eloquent models so framework conventions and relationships remain easy to discover.
- `app/Policies` contains resource authorization; policy methods delegate reusable domain rules when necessary.
- `resources/views/{domain}` contains feature pages, while `resources/views/components` contains shared interface primitives and layouts.

Empty module directories, generic repositories, and one-method service wrappers are prohibited. A boundary is introduced with the first real behavior that needs it and must have a focused test.

## Core data model

- `users`
- `quizzes`
- `questions`
- `question_options`
- `quiz_attempts`
- `attempt_answers`
- `quiz_assignments` (when access is restricted)
- `certificates`
- `live_sessions`
- `live_participants`
- `activity_events`

Important historical values—including question wording, selected options, points, and applicable quiz rules—must be snapshotted or versioned so later quiz edits do not rewrite past results.

## Infrastructure choices

- Laravel 13 on PHP 8.5, with dependency versions locked by Composer.
- PostgreSQL for persistent production data.
- SQLite for local smoke tests and compatible automated tests.
- Vite for frontend asset compilation.
- Tailwind CSS with application-owned design tokens.
- Alpine.js for dropdowns, dialogs, tabs, timers, and progressive enhancement.
- A supported real-time transport for live sessions, isolated behind application events.
- Queue-backed exports and notifications when operations become expensive.

## Security baseline

- Framework CSRF middleware for state changes.
- Password hashing through the framework hasher.
- Session ID rotation after authentication.
- Rate limits for authentication, join codes, and sensitive endpoints.
- Policies for all quiz, result, user, certificate, and live-session operations.
- Strict validation and output escaping by default.
- Secure cookie configuration and production HTTPS.
- Security headers including CSP, clickjacking protection, and content-type controls.
- Secrets stored only in environment configuration.
- Public errors are generic; diagnostic details go to structured logs.

## Testing strategy

- Unit tests: scoring, policies, lifecycle transitions, value objects.
- Feature tests: HTTP workflows, validation, permissions, database effects.
- Integration tests: exports, queues, real-time events, and production database behavior.
- Browser tests: critical educator and student journeys across responsive viewports.
- Accessibility tests: automated checks plus keyboard and screen-reader-oriented manual review.
- CI gates: formatting, static analysis, tests, asset build, and security/dependency audit.

## Migration strategy

1. Preserve both legacy folders as read-only references during discovery.
2. Capture representative legacy fixtures and business scenarios.
3. Scaffold v2 on the dedicated v2 branch without copying procedural page logic.
4. Port verified behavior module by module behind tests.
5. Provide an explicit data importer only if preserving existing user data becomes a requirement.
6. Remove legacy runtime files only after equivalent v2 flows pass acceptance tests.

## Architecture quality rules

- No hardcoded product metrics or identity data in views.
- No direct request/global access inside domain services.
- No destructive schema reset on application boot.
- No business-critical rule enforced only in JavaScript.
- No page may rely solely on hidden UI controls for authorization.
- Database writes spanning one business operation use transactions.
- New behavior requires tests at the lowest useful level and at least one workflow test for critical paths.
