# Dev Log

## 2026-10-28 — Plan day 10 (block 2, day 1): Audit + CI

**Note:** the 27 Oct audit step was not on the repo, so it was done today. The plan day-11 feature work follows next.

**Built**
- Audited the repo (see `docs/AUDIT.md`).
- Added GitHub Actions CI: a backend job (PHP 8.3, `composer install`, `php artisan test`) and a frontend job (`npm ci`, lint, build).
- Added `tests/Feature/TaskApiTest.php` with 7 tests for the tasks API.
- README: CI badge, structure, how to run tests, honest note that the UI is still the starter page.

**Concept: why CI from day one**
Continuous integration means a machine rebuilds the project from a clean checkout and runs the tests on every push. "Works on my machine" hides missing files, forgotten dependencies and stale caches; a clean runner exposes them immediately. Starting on day one means every later feature is protected by a safety net. Feature tests here use `RefreshDatabase`: each test starts with a fresh in-memory SQLite database, so tests cannot affect one another.

**Self-check:** Why does `postJson` matter for API tests, and what status code should invalid input return (and why not 500)?

**Verification honesty:** frontend lint/build run locally and pass. PHP tests were NOT run locally (composer could not download packages); first run is on GitHub Actions.

**Next:** Boards/columns/tasks REST API with ordering (BoardController, TaskColumnController, API resources, seeder).
