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

## 2026-10-29 — Plan day 12 (block 2, day 3): Boards & columns API

**Note:** the plan-day-11 step (boards/columns REST API) was not on the repo yet, so I finished it today before moving on. The next planned feature is still to come.

**Built**
- `BoardController` and `TaskColumnController` (full REST: list, create, show, update, delete), routes `/api/boards` and `/api/columns`.
- Form Requests (`StoreBoardRequest`, `UpdateBoardRequest`, `StoreTaskColumnRequest`, `UpdateTaskColumnRequest`) holding the validation rules.
- API Resources (`BoardResource`, `TaskColumnResource`, `TaskResource`): `GET /api/boards/{id}` returns the whole board — columns in order, each with its tasks in order — in one response.
- New columns without a position go to the far right (max position + 1). A column cannot be moved to another board by update.
- Factories for Board/TaskColumn/Task, and `BoardSeeder` (sample board, safe to run twice) called from `DatabaseSeeder`.
- 15 new feature tests (`BoardApiTest`, `TaskColumnApiTest`); roadmap boxes ticked.

**Concept: Form Requests and API Resources**
A Form Request is a class that owns "is this input acceptable?" so the controller only deals with valid data; Laravel returns a 422 with per-field errors automatically. An API Resource is the opposite side: it owns "what does the outside world see?". It decouples your database columns from your JSON shape, so you can rename a column later without breaking the frontend, and `whenLoaded` includes relations only when you asked for them (avoids accidental N+1 queries and huge payloads). Resources wrap output in a `data` key by default.

**Self-check:** Why does `show` call `$board->load('columns.tasks')` instead of relying on lazy loading inside the resource, and what would happen to the number of SQL queries with 50 columns if you didn't?

**Verification honesty:** PHP syntax checked locally (`php -l`). The test suite was NOT run locally because composer cannot download packages in the sandbox; the first real run is GitHub Actions.

**Next:** Day 13 (block 2, day 4): drag-and-drop board view in React wired to this API, including moving a task (column + position).
