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

## 2026-10-30 — Plan day 13 (block 2, day 4): Drag-and-drop board view

**Built**
- Backend: `PATCH /api/tasks/{task}/move` (`TaskController@move`, `MoveTaskRequest`). It puts the task at a zero-based index in the target column and renumbers source and target columns to a gap-free 0..n-1 inside a DB transaction. Out-of-range indexes are clamped.
- 6 feature tests (`TaskMoveApiTest`): cross-column, same-column reorder, clamping, empty column, validation (422), unknown task (404).
- Frontend: the starter page is replaced by a real board view (`App.jsx`): loads the first board, renders columns side by side, native HTML5 drag-and-drop, loading/empty/error states, optimistic update with rollback on API failure. `api.js` wraps fetch; `board.js` holds the pure `moveTask` function with 5 `node --test` unit tests; Vite proxies `/api` to Laravel. CI now runs `npm test`.

**Concept: optimistic UI + server-owned ordering**
Optimistic UI means updating the screen immediately and talking to the server in the background; if the server refuses, you roll back to the saved previous state. It feels instant. For that to be safe, the client's prediction (`moveTask`) and the server's real logic must follow the same rule, so both renumber positions the same way. The server stays the source of truth: a "move" is one atomic request (column + index) instead of many small edits, so two users can never leave a column with duplicate or gapped positions.

**Self-check:** Why does `moveTask` return a new board instead of editing the old one, and how does that make the rollback line `setBoard(previous)` work?

**Verification honesty:** frontend `npm test` (5 pass), lint and build run locally and pass; lint shows one non-blocking React warning (setState inside an effect on first load). PHP syntax checked with `php -l`, but the PHP test suite could NOT be run locally (composer downloads time out in the sandbox); the first real run is GitHub Actions. Drag-and-drop was not exercised in a real browser, and native DnD does not work on touch screens.

**Next:** Day 14 (block 2, day 5): create/edit/delete tasks and columns from the UI.
