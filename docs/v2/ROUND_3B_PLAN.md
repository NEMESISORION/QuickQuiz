# Round 3B — Educator quiz management

## Objective

Turn the Round 3A domain into a coherent educator workflow for creating and maintaining quiz drafts. The interface must communicate assessment rules clearly, preserve authorization boundaries, and work without client-side JavaScript.

## In scope

- Educator quiz library with useful empty, populated, and status states.
- Draft creation, overview, settings editing, and soft deletion.
- Quiz-level settings for duration, passing score, attempt limits, randomization, and answer review.
- Server-side validation, ownership policies, role middleware, CSRF protection, and escaped output.
- Responsive and keyboard-accessible Blade/Tailwind screens using the shared design system.
- Feature coverage for authentication, role access, cross-educator access, validation, persistence, and deletion.

## Deferred

- Question and answer-option editing, reordering, preview, duplication, scheduling, and publishing belong to Round 3C.
- Learner catalogue and attempt execution remain Round 4 work.

## Exit criteria

- [x] Educators can create, view, edit, and archive their own draft quizzes.
- [x] Learners and other educators cannot mutate quiz resources.
- [x] Invalid assessment settings return accessible validation feedback without persistence.
- [x] Quiz screens are responsive, escaped, and navigable without JavaScript.
- [x] Formatting, static analysis, tests, production assets, and dependency audits pass.
- [x] The branch is committed, pushed, and green in GitHub Actions.
