# QuickQuiz v2 — Product Specification

## Product statement

QuickQuiz is a modern assessment platform that lets educators create structured quizzes, deliver self-paced or live sessions, and understand learner performance. Students receive a focused, accessible test-taking experience with useful feedback rather than decorative statistics.

## Portfolio story

The project must demonstrate more than CRUD. Its engineering story is secure role-based workflows, reliable scoring, thoughtful assessment UX, analytics, real-time live sessions, automated testing, and production delivery.

## Users

### Visitor

- Understand the product from a public landing page.
- View public product information.
- Register, sign in, or recover access.

### Student

- Discover available and scheduled quizzes.
- Read rules before starting an attempt.
- Complete timed quizzes with autosaved progress.
- Review results when the quiz policy permits it.
- Track genuine progress and attempt history.
- Join a live session with a short code.

### Administrator/educator

- Manage quizzes and question banks.
- Preview assessments as a student.
- Publish immediately or schedule availability.
- Manage users and roles within authorized scope.
- Host live sessions.
- Review, filter, and export performance analytics.

## v2 release scope

### Identity and access

- Registration, login, logout, password reset, and profile management.
- Student and administrator roles with policy-based authorization.
- Secure sessions, CSRF protection, login throttling, and audit-worthy events.
- Seeded demo accounts that are clearly isolated from production setup.

### Quiz authoring

- Create, edit, duplicate, archive, and delete quizzes.
- Draft, scheduled, published, and closed lifecycle states.
- Multiple choice and true/false questions in the first stable release.
- Question ordering, point values, explanations, and per-quiz policies.
- Duration, pass threshold, randomization, answer-review policy, and attempt limits.
- Student preview before publishing.

### Attempts and scoring

- Server-authoritative attempt start and expiry times.
- Autosave with clear connection/saved state.
- Resume rules defined by quiz policy.
- Transactional submission and idempotency protection.
- Points-based scoring with recorded answer snapshots.
- Result summary, question review, and printable/verifiable certificate where enabled.

### Analytics

- Real counts and trends; no placeholder metrics.
- Student history, average and best performance, completion, and pass rate.
- Per-question correctness and distractor analysis.
- Filtered CSV export.

### Live quiz differentiator

- Host-controlled session with join code.
- Waiting room and participant presence.
- Synchronized question progression.
- Live response distribution and leaderboard.
- Final report retained for later analysis.

## Business rules that must be explicit

- Only eligible students may start an available published quiz.
- The server, not browser JavaScript, determines whether an attempt is late.
- Submission is processed at most once for an attempt.
- Scoring uses the question and policy snapshot captured for that attempt.
- Results and correct answers respect the quiz review policy.
- Certificates are issued only when enabled and the passing rule is met.
- Archived quizzes preserve historic attempts and analytics.
- Authorization is checked on every protected action, not only in navigation.

## Non-goals for the initial v2 release

- Full learning-management-system course authoring.
- Payments or subscriptions.
- Native mobile applications.
- AI-generated questions until the core assessment workflow is stable.
- Supporting every possible question format in the first release.

## Success criteria

- A new visitor understands the product and reaches a demo in under one minute.
- An educator can publish a valid five-question quiz without documentation.
- A student can complete a quiz on a 360 px-wide screen using keyboard or touch.
- All displayed metrics are derived from stored data.
- Critical authentication, authorization, scoring, and submission flows are automated.
- A clean checkout can be installed, tested, and run from documented commands.
