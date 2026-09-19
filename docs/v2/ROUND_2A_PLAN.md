# Round 2A — Authentication foundation

## Outcome

Round 2A establishes database-backed identity before role-specific product work begins. Guests can register, sign in, recover a password, and reach a protected neutral workspace. Authenticated users can sign out safely.

## Delivered scope

- Laravel-native registration, authentication, logout, and password reset flows.
- Normalized email input, centralized password policy, mass-assignment allow-listing, and server-side validation.
- Session ID rotation after registration and login; invalidation and CSRF-token rotation on logout.
- Per-identity and IP login throttling plus route throttles for registration and recovery submissions.
- Account-neutral password reset responses to reduce email-address enumeration.
- Guest and authenticated route boundaries.
- Responsive, keyboard-friendly auth screens aligned with the assessment design system.
- A role-neutral authenticated workspace ready for Round 2B role onboarding.
- Feature tests covering success, validation, access boundaries, throttling, output escaping, notifications, events, and failed reset attempts.

## Acceptance criteria

- [x] A valid visitor can create an account and is signed in immediately.
- [x] Existing users can sign in and sign out with safe session lifecycle handling.
- [x] Guests cannot access the workspace, and authenticated users cannot reopen guest auth screens.
- [x] Password recovery works without revealing whether an email belongs to an account.
- [x] Authentication abuse is rate limited.
- [x] User-provided identity data is escaped when rendered.
- [x] Auth screens work at mobile and desktop widths and retain visible labels and focus states.
- [x] Backend tests, static analysis, formatting, frontend build, and dependency audits pass.

## Deliberate boundaries

Roles, policies, email verification, educator and learner onboarding, and role-specific navigation belong to Round 2B. Production security headers and deployment hardening remain in the later security round.
