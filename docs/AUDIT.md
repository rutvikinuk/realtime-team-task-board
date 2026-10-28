# Day-1 Audit (2026-10-28)

## What works
- Laravel app boots; routes load (`/up` health, `/api/tasks` apiResource).
- Models `Board`, `TaskColumn`, `Task` with relations and fillable fields; migrations for all three tables with cascade deletes.
- `TaskController` index/store/show/update/destroy with validation.
- Frontend: `npm ci`, `npm run lint` (oxlint) and `npm run build` pass locally.

## What is missing or broken
- No automated tests beyond Laravel's example tests (the old `ExampleTest` only checked `/`). Added `TaskApiTest` (7 tests) covering create, validation, filtering/ordering, move, delete, cascade.
- No CI at all. Added `.github/workflows/ci.yml` (backend tests, frontend lint + build).
- `BoardController`, `TaskColumnController`, API resources and seed data do not exist yet (Sprint 1 leftovers; planned for the next block days).
- Seeder only creates a test user, no sample board.
- Frontend is the unmodified Vite starter page; it does not call the API.
- No auth on `/api/tasks` (Sanctum is installed but not applied) and no rate limits: scheduled for the security day.
- No Reverb / broadcasting config yet.
- `composer.json` still has Laravel's skeleton name/description.

## Not verified locally
`composer install` could not complete in the sandbox (Packagist/GitHub download timeouts), so the PHP test suite had its first real run in GitHub Actions.
