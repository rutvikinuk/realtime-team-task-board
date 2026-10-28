# Realtime Team Task Board

A Trello/Jira-style kanban board with real-time updates across users. Drag a
card between columns and every connected teammate sees the change instantly —
no refresh needed.

**Status:** 🚧 In active development

![CI](https://github.com/rutvikinuk/realtime-team-task-board/actions/workflows/ci.yml/badge.svg)

## Overview

Realtime Team Task Board is a small, focused project management tool for
teams who want a lightweight kanban workflow with genuinely live
collaboration. Boards are made up of columns (e.g. "To Do", "In Progress",
"Done"), and each column holds an ordered list of tasks. Any change one user
makes — moving a card, editing a title, adding a task — is broadcast over a
WebSocket connection and reflected on every other connected client in real
time.

## Tech Stack

- **Backend:** Laravel 12 (PHP), REST API
- **Real-time layer:** Laravel Reverb + Laravel Echo (WebSockets)
- **Frontend:** React (Vite)
- **Database:** SQLite (development)

## Planned Features

- Real-time drag-and-drop kanban board (columns + ordered task cards)
- WebSocket-based live sync via Laravel Reverb — every board update pushed
  instantly to all connected clients
- Multi-user boards with presence (see who else is viewing/editing)
- Task assignment to team members
- Task due dates and basic status indicators

## Project Structure

```
.
├── app/                Laravel application code (models, controllers, etc.)
├── database/           Migrations and seeders
├── routes/             API and web routes
├── frontend/            React (Vite) single-page app
├── tests/               PHPUnit feature + unit tests
├── .github/workflows/   CI (backend tests, frontend lint + build)
└── docs/
    ├── ROADMAP.md       Sprint-by-sprint build plan
    ├── AUDIT.md         Day-1 audit: what works / what is missing
    ├── DEVLOG.md        Daily learning log
    └── TRACKER.md       90-day plan tracker
```

## Getting Started

### Backend (Laravel)

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`.

### Frontend (React + Vite)

```bash
cd frontend
npm install
npm run dev
```

The frontend dev server will be available at `http://127.0.0.1:5173` and
talks to the Laravel API over REST (WebSocket sync via Reverb to follow in a
later sprint — see the roadmap below).

## Running the tests

```bash
php artisan test
```

Tests use an in-memory SQLite database, so no `.env` database setup is needed.
The same command runs in GitHub Actions on every push (see
`.github/workflows/ci.yml`), alongside `npm run lint` and `npm run build` for
the frontend.

> Note: the frontend is currently still the default Vite + React starter page.
> The real board UI arrives in the Kanban UI sprint (see the roadmap).

## Roadmap

See [`docs/ROADMAP.md`](docs/ROADMAP.md) for the sprint-by-sprint plan
between now and launch.
