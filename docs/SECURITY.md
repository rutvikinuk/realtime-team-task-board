# Security notes

Result of the day-16 security pass (2 Nov 2026).

## What is in place

| Area | Control |
| --- | --- |
| Input validation | Every endpoint validates input. Titles max 255, descriptions max 10 000 (tasks) / 5 000 (boards), positions 0..1 000 000, ids must be integers and exist. Query filters (`task_column_id`, `board_id`) must be integers. |
| SQL injection | Only Eloquent / bound parameters are used; no raw SQL. Covered by a test that stores `'); DROP TABLE` literally. |
| Mass assignment | Models list `$fillable` explicitly. |
| Rate limiting | `throttle:api` on all `/api` routes, 120 requests/min per IP (`API_RATE_LIMIT`). Returns 429. |
| CORS | `config/cors.php`: only origins in `CORS_ALLOWED_ORIGINS`, only `api/*`, no credentials. |
| Response headers | `SecurityHeaders` middleware: `nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`, `Permissions-Policy`, strict CSP. |
| XSS | React escapes text by default; the frontend has no `dangerouslySetInnerHTML`/`innerHTML`. |
| Secrets | `.env` is git-ignored; `.env.example` holds placeholders only. |
| Dependencies | `npm audit` clean (vite bumped via `npm audit fix`). CI job `Dependency audit` runs `composer audit --locked` and `npm audit --audit-level=high` on every push. |

## Known gaps (not fixed yet)

- **No authentication or authorization.** Anyone who can reach the API can read and change every board. Sanctum is installed but not wired. This must be solved before any public deployment (planned together with multi-user boards / presence).
- Rate limiting is per IP only; behind a proxy configure trusted proxies so the real client IP is used.
- List endpoints (`/api/tasks`, `/api/columns`) are not paginated.
- `APP_DEBUG=true` in `.env.example` is for local development; production must set it to `false`.
- Reverb/WebSocket channels (sprint 3) will need channel authorization.
