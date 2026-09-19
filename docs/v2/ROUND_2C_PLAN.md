# Round 2C — Identity security and auditability

## Outcome

Round 2C closes the identity foundation with durable, privacy-conscious security activity; hardened browser and session behavior; safe local demo identities; and adversarial tests for the complete authentication boundary.

## Delivered scope

- Persistent identity audit events for registration, successful and failed sign-in, sign-out, password reset, email verification, role selection, and profile updates.
- Privacy-conscious failed-login fingerprints using a keyed hash instead of storing raw attempted email addresses.
- No passwords, reset tokens, or identity values in audit metadata.
- A policy-protected security activity page showing only the signed-in user's latest 20 events.
- Authenticated-session password-hash checks so password changes invalidate sessions holding an older hash.
- Browser hardening headers on all responses and a production Content Security Policy and HSTS policy.
- Private, no-store caching rules for authentication and authenticated responses.
- Encrypted session payloads by default, HTTP-only cookies, SameSite=Lax, and an explicit secure-cookie production setting.
- Idempotent educator and learner demo accounts that are available locally and refuse to seed in production.
- Clean-database migration, factory, seeder, policy, audit, session, output-escaping, and response-header tests.

## Security controls

| Boundary | Control |
| --- | --- |
| Credentials | Framework hashing, strong validation, neutral recovery responses, no audit storage |
| Sessions | ID rotation, logout invalidation, encrypted payloads, authenticated-session hash checks |
| Abuse | Per-identity login throttling and throttled registration/recovery/verification |
| Authorization | Verified email, one-time role assignment, gates, policy-protected profile and activity |
| Browser | CSP, HSTS, clickjacking, MIME sniffing, referrer, permissions, and cross-origin headers |
| Audit | Append-oriented account events, request context, privacy-preserving metadata |
| Demo data | Known local credentials, idempotent creation, production refusal |

## Acceptance criteria

- [x] Every critical identity transition creates the expected audit event.
- [x] Failed-login records contain no raw credentials or email address.
- [x] Users can view only their own recent security activity.
- [x] Stored request context remains escaped when rendered.
- [x] A changed password invalidates sessions authenticated with the previous hash.
- [x] Sensitive responses cannot be cached by shared or browser caches.
- [x] Production responses include CSP and HSTS; all responses include the baseline browser headers.
- [x] Demo accounts are repeatable locally and impossible to seed in production.
- [x] Formatting, static analysis, tests, frontend build, migrations, and dependency audits pass.

## Round boundary

Identity and access are now ready to support real domain resources. Round 3 begins quiz authoring with quiz ownership, lifecycle rules, question structures, and educator policies built on these controls.
