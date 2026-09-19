# Round 2B — Roles and authorization

## Outcome

Round 2B turns authenticated accounts into verified educator or learner identities. Workspace access is enforced on the server, role selection is intentionally one-time, and users can maintain their profile without controlling protected identity attributes.

## Delivered scope

- Required email verification with signed, expiring links and throttled resend requests.
- A backed `UserRole` enum and database-backed role selection timestamp.
- One-time educator or learner onboarding after email verification.
- A verified-email boundary before onboarding, dashboards, and profile management.
- Role-specific educator and learner workspace routes protected by Laravel gates.
- A neutral dashboard router that directs each role to the correct workspace.
- A user policy that limits profile updates to the account owner.
- Profile editing with normalized unique email validation and verification reset after email changes.
- Mass-assignment protection preventing registration and profile payloads from changing roles.
- Responsive workspace, onboarding, verification, and profile interfaces aligned with the assessment design system.
- Factories and automated tests for every role, verification state, policy branch, invalid role, and cross-role request.

## Acceptance criteria

- [x] New accounts receive an email verification notification.
- [x] Unverified accounts cannot enter onboarding or any workspace.
- [x] Signed verification links verify the correct authenticated account; invalid signatures are rejected.
- [x] A verified account can choose educator or learner exactly once.
- [x] Accounts without roles cannot enter a workspace.
- [x] Educators and learners can enter only their own workspace.
- [x] Profile updates cannot modify roles and email changes require reverification.
- [x] Policy, feature, formatting, static-analysis, asset-build, and security checks pass.

## Deliberate boundaries

Identity audit history, demo-account seeding, stronger production session/cookie headers, and deployment-specific security controls belong to Round 2C. Quiz authoring starts in Round 3 after the identity boundary is closed.
