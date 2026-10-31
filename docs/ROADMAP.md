# Roadmap

Target: fully working real-time kanban board by **2026-10-10**.

Three one-and-a-half-week sprints, tracked loosely — this is a solo project
so sprint boundaries are more "checkpoint" than ceremony.

## Sprint 1 — Foundation (Sep 9 – Sep 19)

Backend data model, REST API, and frontend scaffold.

- [x] Laravel app scaffolded, SQLite configured for local dev
- [x] `Board`, `TaskColumn`, `Task` models + migrations
- [x] `TaskController` REST endpoints (`index`, `store`, `update`, `destroy`)
- [x] React (Vite) frontend scaffolded in `frontend/`
- [x] `BoardController` and `TaskColumnController` REST endpoints
- [x] API resource classes (`BoardResource`, `TaskColumnResource`, `TaskResource`)
- [x] Seed data / factory for local development (sample board with columns + tasks)
- [x] Basic form request validation + error responses

## Sprint 2 — Kanban UI (Sep 20 – Sep 30)

Wire the frontend to the API and get drag-and-drop working against real data.

- [x] Board view: columns rendered side by side, tasks listed per column
- [x] Drag-and-drop integrated (native HTML5 drag-and-drop, no library; may swap to `@dnd-kit` for touch support)
- [x] Moving a card updates `task_column_id` + `position` via the API
- [x] Create/edit/delete tasks from the UI
- [x] Create/edit/delete columns from the UI
- [x] Basic loading/error states, optimistic UI updates on drag
- [ ] Simple styling pass (layout, card design, column headers)

## Sprint 3 — Real-Time Sync & Polish (Oct 1 – Oct 10)

Bring in Laravel Reverb so every connected client sees updates live, then
harden and ship.

- [ ] Laravel Reverb installed and configured
- [ ] Broadcast events on task create/update/move and column create/update
- [ ] Laravel Echo wired up in the React app, subscribed to board channels
- [ ] Remote changes reflected in the UI without a page refresh
- [ ] Basic presence indicator (who's currently viewing the board)
- [ ] Cross-browser/manual multi-tab testing of real-time sync
- [ ] Environment config for production (Reverb, CORS, API base URL)
- [ ] Deployment (backend + frontend) and final README pass
