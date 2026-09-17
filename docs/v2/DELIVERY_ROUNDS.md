# QuickQuiz v2 — Delivery Rounds

## Definition of done for every round

- Acceptance criteria are written before implementation.
- New behavior has proportionate automated tests.
- Formatting, static analysis, tests, and production asset builds pass.
- Relevant responsive, accessibility, security, and error states are reviewed.
- Documentation and changelog are updated.
- The round ends in a focused commit and is pushed only when green.

## Round 0 — Discovery and specification

Deliverables:

- Legacy audit and reuse policy.
- Product specification and explicit business rules.
- Target architecture and migration approach.
- UX principles, information architecture, initial tokens, and component inventory.
- Toolchain and repository baseline.

Exit gate: decisions reviewed, Composer setup agreed, documents committed and pushed.

## Round 1 — Application foundation

- Scaffold Laravel and frontend toolchain.
- Environment validation, PostgreSQL/SQLite configuration, migrations, factories, and seeds.
- Base application shell and initial component library.
- Formatter, static analysis, automated test runner, and GitHub Actions.
- Health check, structured errors, and local setup documentation.

## Round 2 — Identity and authorization

- Registration, authentication, logout, recovery, and profile management.
- Roles, policies, rate limiting, session hardening, and audit events.
- Complete responsive authentication experience and automated security workflows.

## Round 3 — Quiz authoring

- Quiz lifecycle and full CRUD.
- Staged quiz builder, question management, ordering, validation, and preview.
- Publishing, scheduling, attempt and review policies.

## Round 4 — Student assessment experience

- Catalogue, eligibility, instructions, attempts, timer, autosave, and submission.
- Server-authoritative scoring and result review.
- Mobile, keyboard, expiry, reconnect, and idempotency scenarios.

## Round 5 — Live sessions

- Join codes, lobby, host controls, synchronized progression, response distribution, and leaderboard.
- Reconnect handling, authorization, abuse limits, and retained session report.

## Round 6 — Analytics and completion features

- Real dashboards, filters, question analysis, exports, certificates, notifications, and preferences.
- Dark theme and all empty/loading/error states.

## Round 7 — Verification and hardening

- Full unit, feature, integration, browser, accessibility, security, and performance test passes.
- Cross-database verification and production-like deployment rehearsal.
- Defect burn-down with no unresolved critical/high issues.

## Round 8 — Portfolio release

- Production deployment and monitored smoke test.
- Professional README, screenshots, short demo video, diagrams, and demo credentials.
- Clean public history, changelog, tagged release, and GitHub repository presentation.

## Scope control

New ideas enter the backlog and are assigned to a future round. Work already accepted for the active round is not expanded unless it blocks correctness, security, accessibility, or the next round.
