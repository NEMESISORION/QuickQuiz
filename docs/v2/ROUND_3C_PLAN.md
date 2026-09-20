# Round 3C — Question builder and publishing

## Objective

Complete educator authoring with a safe question builder, deterministic ordering, learner preview, and explicit immediate or scheduled publication. Published structure becomes immutable so Round 4 can create trustworthy attempt snapshots.

## In scope

- Multiple-choice and true-or-false question creation, editing, removal, and ordering.
- Exactly one correct answer, distinct choices, points, and optional explanations.
- Scoped nested routes and ownership checks for every question action.
- A learner-style preview that does not expose the answer key.
- Immediate publishing or future scheduling with optional closing time.
- Structural readiness validation and transactional lifecycle updates.
- Responsive, keyboard-accessible Blade/Tailwind authoring screens with Alpine progressive enhancement.
- Feature coverage for validation, authorization, ordering, preview, publishing, and scheduling.

## Deferred

- Learner catalogue, eligibility, attempt snapshots, timers, autosave, submission, and scoring begin in Round 4.
- Quiz duplication and cancellation of scheduled publication remain backlog items unless required by learner delivery.

## Exit criteria

- [x] Educators can build and reorder valid questions in their own draft quizzes.
- [x] Nested binding and policies prevent cross-quiz and cross-educator mutation.
- [x] Preview renders the learner-facing structure without revealing correct answers.
- [x] Invalid or empty quizzes cannot publish, while valid quizzes publish or schedule transactionally.
- [x] Published quiz structure is immutable through authoring endpoints.
- [x] Formatting, static analysis, tests, assets, audits, and GitHub Actions pass.
