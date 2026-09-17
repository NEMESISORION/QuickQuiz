# QuickQuiz v2 — Architecture Decisions

This log records decisions that materially shape the rebuild. A decision is revisited only when new evidence changes its trade-offs.

## ADR-001: Controlled rebuild instead of incremental patching

Status: Accepted

Decision:

Build v2 on a dedicated branch while preserving the existing application as a behavioral and asset reference. Port verified capabilities behind tests rather than reorganizing legacy page files in place.

Why:

- Request handling, data access, business rules, and presentation are tightly coupled.
- Visible placeholder data and incomplete domain wiring make incremental fixes difficult to verify.
- A clean boundary allows migrations, policies, tests, and reusable UI components to be established from the beginning.

Consequence:

The first rounds invest in foundations before feature parity. Legacy production data requires a deliberate importer if preservation is later requested.

## ADR-002: Laravel modular monolith

Status: Accepted

Decision:

Use Laravel conventions with explicit modules for identity, authoring, attempts, results, analytics, and live sessions. Do not split the product into independent services.

Why:

- The domain benefits from framework authentication, authorization, validation, migrations, queues, events, and tests.
- A modular monolith provides clean boundaries without operational complexity that would not add portfolio value at this scale.

Consequence:

Composer becomes required. Framework and dependency versions will be locked in Round 1.

## ADR-003: Server-rendered UI with progressive enhancement

Status: Accepted

Decision:

Use Blade and Tailwind CSS with Alpine.js for local interactions. Introduce richer client-side behavior only where live sessions or complex authoring justify it.

Why:

- Most workflows are form- and content-oriented.
- Server rendering improves the accessibility baseline, initial delivery, and implementation clarity.
- It avoids maintaining a separate API and SPA before the product requires them.

Consequence:

JSON endpoints remain available for autosave and real-time interactions, but are not the default page architecture.

## ADR-004: PostgreSQL production, SQLite-compatible tests

Status: Accepted

Decision:

Use PostgreSQL for persistent hosted data. Use SQLite for fast tests where semantics match, plus a PostgreSQL integration suite for database-specific behavior.

Why:

- The current SQLite deployment risks data loss on ephemeral hosting.
- PostgreSQL supports production concurrency and richer analytics reliably.
- SQLite keeps the fastest part of the developer feedback loop lightweight.

Consequence:

Migrations and queries must avoid accidental database-specific behavior or cover it with PostgreSQL integration tests.

## ADR-005: Product-owned accessible design system

Status: Accepted

Decision:

Build reusable semantic components and tokens around assessment workflows. Legacy styles may inform the visual direction but will not become the new foundation.

Why:

- The student focus experience and educator management experience have different density needs.
- Accessibility, responsive states, dark mode, and error handling must be designed consistently rather than added per page.

Consequence:

Round 1 builds primitives before feature pages, and every later feature must use or extend those primitives.
