# QuickQuiz

QuickQuiz is being rebuilt as a modern assessment platform for educators and learners. Version 2 focuses on secure role-based workflows, reliable quiz delivery and scoring, meaningful analytics, live sessions, accessibility, automated testing, and a polished product-specific experience.

## Current status

QuickQuiz v2 is in **Round 0: Discovery and Specification** on the `v2-round-0-discovery` branch. The legacy PHP application remains available as a behavioral reference while the new architecture is defined and verified.

## v2 documentation

- [Round 0 audit](docs/v2/ROUND_0_AUDIT.md)
- [Product specification](docs/v2/PRODUCT_SPEC.md)
- [Target architecture](docs/v2/ARCHITECTURE.md)
- [UX and design-system brief](docs/v2/UX_SYSTEM.md)
- [Delivery rounds](docs/v2/DELIVERY_ROUNDS.md)
- [Architecture decisions](docs/v2/DECISIONS.md)

## Product direction

QuickQuiz v2 will provide:

- Quiz authoring, scheduling, publishing, and preview.
- A focused timed assessment experience with autosave and accessible navigation.
- Server-authoritative attempts, scoring, review policies, and certificates.
- Real educator and learner analytics without placeholder statistics.
- Live hosted quizzes with join codes and leaderboards.
- A responsive interface designed specifically for assessment workflows.

## Legacy application

The current application requires PHP 8.2 with PDO SQLite:

```powershell
php -S 127.0.0.1:8000 -t public
```

Then open `http://127.0.0.1:8000`.

Demo accounts in the legacy seed data:

- Administrator: `admin` / `1234`
- Student: `student` / `1234`

These credentials are for local demonstration only and will not be used as production defaults.
