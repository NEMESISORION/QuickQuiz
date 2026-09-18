# QuickQuiz v2 — Round 1B Architecture and UI Foundation

Status: Verification in progress

## Objective

Establish concrete code-placement rules and a reusable assessment-oriented interface foundation without inventing domain abstractions before real behavior exists.

## Scope

- Define where HTTP, application, domain, model, policy, and view code belongs.
- Extract reusable Blade primitives from the public experience.
- Keep visual decisions expressed through semantic Tailwind tokens.
- Remove controls that imply unavailable behavior.
- Verify the landing experience from 360 px mobile through wide desktop.
- Preserve semantic landmarks, keyboard focus, reduced motion, and 44 px touch targets.

## Acceptance criteria

- [x] Round 1B has a dedicated branch and bounded scope.
- [x] Architectural placement rules are explicit and reject speculative abstractions.
- [x] Shared button, badge, panel, brand, and marketing-layout components have explicit interfaces.
- [x] The public landing page uses shared primitives rather than duplicating their styles.
- [x] Preview controls clearly communicate that they are unavailable and cannot be activated.
- [x] Product copy describes assessment workflows rather than internal development rounds.
- [x] The landing feature test covers the updated public contract.
- [x] The interface is reviewed at 360 px and desktop widths without horizontal overflow.
- [x] Formatting, static analysis, tests, and production assets pass locally.
- [ ] GitHub Actions passes from the final Round 1B commit.
- [ ] Round 1B is committed and pushed before Round 1C starts.
