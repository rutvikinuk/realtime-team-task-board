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

## 2026-10-31 — Plan day 14 (block 2, day 5): Create / edit / delete from the UI

**Built**
- Frontend: add task (per column), rename task, delete task, add column, rename column, delete column (asks for confirmation when the column still has tasks). A small reusable `TitleForm` handles adding and inline renaming (Enter saves, Esc cancels, blank titles blocked).
- `board.js` gained pure helpers (`addTask`, `updateTask`, `removeTask`, `addColumn`, `updateColumn`, `removeColumn`) with 5 new `node --test` unit tests (10 total). `api.js` gained the matching REST calls.
- Backend: `POST /api/tasks` without a position now appends to the bottom of the column; `DELETE /api/tasks/{id}` renumbers the column so positions stay gap-free (in a transaction). 5 new PHPUnit tests in `TaskPositioningTest`. Roadmap boxes ticked.

**Concept: optimistic updates vs. waiting for the server**
Rename and delete are *optimistic*: the UI changes first, the request goes out, and a failure restores the saved previous board and shows an error. Creating is different: the new row's `id` is chosen by the database, so the UI waits for the response and then adds the server's version. Rule of thumb: if the client can predict the result exactly, be optimistic; if the server decides part of the result (ids, positions), wait for it. Also note the deletes rely on `cascadeOnDelete` in the migrations, so deleting a column removes its tasks in the database itself.

**Self-check:** Why does `createTask` use `setBoard((b) => addTask(b, task))` (functional update) instead of `setBoard(addTask(board, task))` after the `await`?

**Verification honesty:** frontend `npm test` (10 pass), lint (same one non-blocking warning as before) and build pass locally. PHP syntax checked with `php -l`, but the PHP tests were NOT run locally (composer packages unavailable in the sandbox); the first real run is GitHub Actions. The new UI was not exercised in a real browser.

**Next:** Day 15 (block 2, day 6): tests for code added this block (fill gaps in backend + frontend coverage).

## 2026-11-01 — Plan day 15 (block 2, day 6): Tests for this block's code

**Built**
- Backend: `ValidationEdgeCasesTest` (27 tests) covers the error paths of every endpoint: over-long and non-integer input, negative positions, unknown ids (404), failed requests that must not change data, the shape of the move response, moving a task onto its own spot, and a column with gappy positions being healed by a move. `ModelRelationsTest` (5 tests) checks relation ordering (`columns`/`tasks` come back by `position`), the `belongsTo` links, mass-assignment protection and that the factories build a valid Board → Column → Task chain.
- Frontend: `board.edge.test.js` (13 tests: empty column, negative index, same spot, unknown ids, no mutation) and `api.test.js` (7 tests that replace `fetch` with a fake and check URLs, methods, bodies, `data` unwrapping, 204 handling and error messages). `npm test` went from 10 to 30 tests.
- `api.js` now reads `import.meta.env?.VITE_API_URL` so the file can be imported under plain Node (Vite still replaces it at build time).

**Concept: testing the unhappy paths**
Happy-path tests prove the feature works when everything is right; most real bugs live at the edges: empty input, too-long input, ids that do not exist, doing the same thing twice. A good edge test asserts two things: the response is right (422/404, not a 500) *and* nothing was changed in the database. For the frontend, a "fake fetch" test shows how to test code that talks to the network without a server: swap the dependency, record the calls, assert on them.

**Self-check:** Why does `test_move_heals_gaps_in_the_target_column` use `assertEquals` rather than `assertSame` on the id→position map, and which behaviour of `move` is it proving?

**Verification honesty:** frontend `npm test` (30 pass), lint (the same one non-blocking React warning) and build pass locally. The PHP files pass `php -l`, but the PHP test suite was NOT run locally (composer packages cannot be downloaded in the sandbox); the first real run is GitHub Actions. I wrote the expectations by reading the controllers and form requests, so a failing assertion there would point to a wrong test rather than a known bug.

**Next:** Day 16 (block 2, day 7): security pass — validation review, rate limiting, CORS, dependency audits.

## 2026-11-02 — Plan day 16 (block 2, day 7): Security pass

**Built**
- Validation: task descriptions are capped (10 000 chars), `position` is capped at 1 000 000 everywhere, `task_column_id` must be an integer, and the `?task_column_id=` / `?board_id=` list filters are validated as integers (422 instead of passing junk to the database).
- Rate limiting: all `/api` routes now go through `throttle:api` (120 requests/min per IP, `API_RATE_LIMIT`), answered with 429 when exceeded.
- CORS: new `config/cors.php` that only allows the origins in `CORS_ALLOWED_ORIGINS` (the Vite dev server by default), no credentials.
- `SecurityHeaders` middleware adds `nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy` and a strict CSP to every response.
- Dependencies: `npm audit` found 2 high advisories in `vite`; `npm audit fix` bumped it and the audit is now clean. CI has a new `Dependency audit` job (`composer audit --locked` + `npm audit --audit-level=high`).
- 8 new tests in `SecurityHardeningTest`; `docs/SECURITY.md` lists what is covered and the known gaps.

**Concept: defence in depth**
No single control makes an app "secure". You stack small layers so one failure is not a disaster: validation stops bad data at the door, parameter binding stops SQL injection even if validation misses something, rate limiting slows down abuse and brute force, CORS tells *browsers* which websites may call your API (it does not protect against curl or other servers), and response headers tell browsers not to sniff, frame or execute things. Dependency audits cover the code you did not write. The most important gap is still the biggest layer: there is no authentication yet, so every control above is only a fence around an open door.

**Self-check:** Why does CORS not stop an attacker from calling your API with `curl`, and which layer would?

**Verification honesty:** frontend `npm test`, lint and build pass locally after the vite bump. The PHP files pass `php -l`, but composer could not download packages in the sandbox, so the PHP test suite (including the 8 new tests) was NOT run locally; the first real run is GitHub Actions. The CORS and throttling tests in particular are written from my reading of how Laravel's middleware works and may need a fix after the first CI run. `composer audit` has not been run yet either.

**Next:** Day 17 (block 2, day 8): Dockerfile + CI extension (Docker build and smoke test).
