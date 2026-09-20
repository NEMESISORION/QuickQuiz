# Round 3A — Quiz domain foundation

## Objective

Establish the persistent authoring model and authorization boundary that the quiz builder, learner attempts, and analytics will use. Round 3A deliberately stops before authoring screens and attempt execution so later rounds build on one tested domain contract.

## In scope

- Quiz ownership by verified educator identities.
- Draft, scheduled, published, closed, and archived lifecycle states with explicit transitions.
- Multiple-choice and true-or-false questions with stable per-quiz ordering.
- Ordered answer options, correctness flags, points, and explanations.
- Duration, pass threshold, attempt limit, randomization, availability, and answer-review policy fields.
- Soft deletion for quizzes and cascading cleanup for unpublished question structures.
- Eloquent relationships, typed casts, factories, local demo content, policies, and automated tests.

## Deferred

- Authoring forms, controllers, validation, preview, duplication, and publish orchestration belong to Rounds 3B and 3C.
- Attempt records, answer snapshots, scoring, autosave, and submission belong to Round 4.
- Learner catalogue visibility and eligibility are not granted by the authoring policies in this round.

## Exit criteria

- [x] Fresh migrations run on the SQLite test database and remain PostgreSQL-compatible.
- [x] Quiz, question, and answer-option relationships preserve deterministic ordering.
- [x] Lifecycle transitions and educator ownership rules have automated coverage.
- [x] Demo quiz seeding is local-only, repeatable, and attached to the demo educator.
- [x] Formatting, static analysis, backend tests, frontend build, and dependency audits pass.
- [x] The round is committed and pushed to its dedicated branch with green GitHub Actions.
