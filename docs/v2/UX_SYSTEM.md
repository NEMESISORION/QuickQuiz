# QuickQuiz v2 — UX and Design System Brief

## Experience principle

QuickQuiz must look and behave like an assessment product—not a generic dashboard theme. The experience balances three modes:

- **Discover:** welcoming, clear, and confidence-building.
- **Manage:** efficient, data-dense, and predictable for educators.
- **Focus:** quiet and distraction-free while a student takes an assessment.

## Product personality

- Clear rather than clever.
- Encouraging rather than childish.
- Confident rather than visually loud.
- Modern without hiding essential actions behind novelty interactions.
- Gamification reflects real behavior; fabricated XP, streaks, or achievements are prohibited.

## Information architecture

### Public

- Landing
- Product/features
- Sign in
- Register
- Password recovery
- Public certificate verification

### Student workspace

- Overview
- Discover quizzes
- Upcoming and active assignments
- Attempt history
- Results and review
- Join live session
- Profile and preferences

### Educator workspace

- Overview
- Quizzes
- Question bank
- Live sessions
- Results
- Analytics
- Users
- Settings

## Key flows

### Quiz authoring

Use a staged builder: details, questions, behavior, availability, and review/publish. Show validation near the affected step and retain unsaved-work status. Preview must use the actual student presentation.

### Quiz taking

Use a dedicated focus layout with only quiz identity, progress, timer, connection/save state, navigator, question content, and submit action. Navigation away or final submission must warn appropriately. Timing and autosave state must be understandable without relying on color.

### Results

Lead with outcome and actionable feedback. Display earned/available points, percentage, pass state, duration, and question review according to policy. Avoid confetti or celebration when a learner fails.

## Initial visual direction

The legacy purple/teal identity is recognizable and can be refined into a calmer indigo/teal system.

### Foundation tokens

| Role | Initial token |
| --- | --- |
| Primary | Indigo `#4F46E5` |
| Primary strong | Indigo `#3730A3` |
| Accent | Teal `#0F766E` |
| Canvas | Slate `#F8FAFC` |
| Surface | White `#FFFFFF` |
| Text | Slate `#0F172A` |
| Muted text | Slate `#64748B` |
| Border | Slate `#E2E8F0` |
| Success | Green `#15803D` |
| Warning | Amber `#B45309` |
| Danger | Red `#B91C1C` |

These are candidate semantic tokens, not permission to scatter literal colors through templates. Dark theme tokens will be defined by semantic role rather than inverted ad hoc.

### Typography and layout

- Use a highly legible variable sans-serif with a system-font fallback.
- Keep assessment question text at a comfortable reading size and line length.
- Use an 8 px spacing rhythm with deliberate compact variants for data tables.
- Maintain visible hierarchy without excessive card nesting.
- Student content uses a constrained reading width; educator tables may use the available workspace.

## Component inventory

- Application shell, public header, educator sidebar, student navigation.
- Button, icon button, link, badge, status indicator.
- Text input, select, checkbox, radio, textarea, date/time and validation message.
- Alert, toast, dialog, dropdown, tabs, tooltip.
- Card, metric, table, pagination, filters, empty state, skeleton.
- Quiz card, question editor, option editor, question navigator.
- Timer, progress indicator, save-status indicator.
- Result summary, answer review, chart, certificate.

Components must include hover, focus, active, disabled, loading, error, and dark-theme states where relevant.

## Responsive rules

- Support 360 px through wide desktop layouts.
- Never require horizontal scrolling to answer a quiz question.
- Tables convert to prioritized cards or controlled scrolling only when necessary.
- Primary assessment actions remain reachable without covering question content.
- Touch targets are at least 44 by 44 CSS pixels.

## Accessibility baseline

- Target WCAG 2.2 AA.
- Full keyboard operation and visible focus.
- Semantic headings, labels, landmarks, tables, and dialogs.
- Color contrast that passes for text and meaningful UI states.
- Status messages announced appropriately.
- Timed assessments provide accessible warnings and policy-controlled accommodations.
- Motion is subtle and respects reduced-motion preferences.
- Charts always have a textual/table equivalent.

## Required states for every feature

- First-use/empty.
- Loading or processing.
- Success confirmation.
- Validation failure.
- Network/server failure with recovery guidance.
- Unauthorized/forbidden.
- Disabled or unavailable with an explanation.

## UX acceptance tests

- A student can start and submit a quiz using only a keyboard.
- A student always knows whether an answer is saved.
- A timed attempt explains what happens at expiry.
- An educator can identify draft, scheduled, live, closed, and archived quizzes without opening them.
- Destructive actions state their exact impact and require appropriate confirmation.
- No interface element links back to the same page while pretending to be an unimplemented feature.
- No visible statistic is hardcoded.
