# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

A Udemy-style online learning marketplace, being built out in phases. **Phase 0** (SPA/API migration + auth hardening) is complete: the project is now a **decoupled Laravel API backend + React SPA frontend**, authenticated via Laravel Sanctum's SPA cookie flow, with Google/GitHub OAuth (Laravel Socialite) and role-based access (Spatie Permission: `student`/`instructor`/`admin`). No course-platform domain tables exist yet (courses, lessons, enrollments, payments, etc. land in later phases) — see the phased roadmap in the project's plan history for what's next.

Stack: PHP 8.2 / Laravel 12 API backend (no Blade views, no Inertia), MySQL, React 19 SPA frontend (JSX) in `frontend/` with React Router, TanStack React Query, React Hook Form, Tailwind CSS v4, and Vite — talking to the API over CORS with credentials.

## Repo layout

Two independently-run halves in one repo, each in its own top-level folder:

- **`backend/`** — the Laravel API (`app/`, `routes/`, `database/`, `config/`, `composer.json`, `artisan`, etc.) — serves only `/api/v1/*` JSON plus a handful of browser-navigated routes that must stay server-rendered (OAuth redirect/callback, signed email-verification links, and eventually the Stripe webhook).
- **`frontend/`** — the React SPA, its own Vite project with its own `package.json`, entirely separate build/dev toolchain from the Laravel app. Laravel does not build, serve, or know about the frontend's assets.

All `php artisan`/`composer` commands below must be run from inside `backend/`; all `npm` commands from inside `frontend/`. `composer dev`/`composer setup` in `backend/composer.json` already invoke the frontend's npm scripts for you via `npm --prefix ../frontend`, so you don't need a second terminal for basic dev work.

## Common commands

**Local dev (single command, run from `backend/`; starts API server + queue worker + log tailer + SPA dev server together):**

```
cd backend
composer dev
```

**Individually:**

```
cd backend && php artisan serve                 # backend API at http://localhost:8000
cd frontend && npm run dev                       # SPA dev server at http://localhost:5173
cd backend && php artisan queue:listen --tries=1 --timeout=0
cd backend && php artisan pail --timeout=0       # live log viewer
```

**Frontend build:**

```
cd frontend && npm run build
```

**Tests (PHPUnit, Feature + Unit suites — run from `backend/`):**

```
composer test                 # clears config cache, then runs php artisan test
php artisan test               # run full suite directly
php artisan test --filter=RegistrationTest        # single test class
php artisan test tests/Feature/Auth/AuthenticationTest.php  # single file
```

Test env config lives inline in `backend/phpunit.xml` (sqlite `:memory:`, array session/cache/mail drivers, sync queue) — no separate `.env.testing` file is needed. Auth Feature tests hit `/api/v1/auth/*` and assert JSON responses, not Inertia/redirect responses — see `backend/tests/TestCase.php` for why a `Referer` header and role seeding are set up globally (Sanctum's stateful-domain detection and Spatie roles both need it).

**Database (run from `backend/`):**

```
php artisan migrate
php artisan migrate:fresh --seed
```

The dev database is MySQL (`DB_CONNECTION=mysql`, configured via `backend/.env`) — despite older docs/scaffolding referencing a `database/database.sqlite` file, the actual dev DB in this environment is MySQL. Tests always run against an isolated in-memory SQLite DB regardless (see `phpunit.xml`).

**First-time setup** (also encoded in `backend/composer.json`'s `setup` script, run from `backend/`): `composer install`, copy `.env.example` to `.env`, `php artisan key:generate`, `php artisan migrate`, `npm --prefix ../frontend install`, copy `../frontend/.env.example` to `../frontend/.env`.

## Architecture

- **JSON API, not Inertia/Blade.** Controllers under `backend/app/Http/Controllers/Api/V1/` return `JsonResponse`s. Routes live in `backend/routes/api.php` under an `/api/v1` prefix. There is no `resources/js/Pages` or server-rendered view layer anymore — the SPA in `frontend/` owns all page rendering and client-side routing (React Router).
- **Auth: Sanctum SPA cookie auth**, not bearer tokens. `EnsureFrontendRequestsAreStateful` is prepended to the `api` middleware group (`backend/bootstrap/app.php`). The SPA must call `GET /sanctum/csrf-cookie` before any state-changing request, then send the `X-XSRF-TOKEN` header (axios does this automatically when `withXSRFToken: true` — see `frontend/src/api/client.js`). CORS is configured in `backend/config/cors.php` against `FRONTEND_URL`/`SANCTUM_STATEFUL_DOMAINS` env vars — update both together when the SPA's dev/prod origin changes.
- **Auth endpoints** (`backend/app/Http/Controllers/Api/V1/AuthController.php`, `ProfileController.php`): register, login, logout, me, forgot/reset password, email verification, password confirm/update, profile update/delete. Email verification (`GET /verify-email/{id}/{hash}`) stays in `backend/routes/web.php` (not `api.php`) because it's a signed link the user clicks from their inbox — a real browser navigation, not a fetch/XHR — and it redirects into the SPA (`config('app.frontend_url')`) afterward rather than returning JSON.
- **Roles & permissions**: `spatie/laravel-permission`. Three base roles (`student`, `instructor`, `admin`) are seeded via `backend/database/seeders/RoleSeeder.php` (called from `DatabaseSeeder`). New users get `student` on registration (both password and OAuth signup). `User` has the `HasRoles` trait; gate routes with the `role:` middleware alias (registered in `backend/bootstrap/app.php`) and Policies for per-resource ownership once domain models exist.
- **Social login (Google/GitHub)**: `App\Http\Controllers\SocialController` (`redirect`/`callback`), routed at `/auth/{provider}/redirect` and `/auth/{provider}/callback` in `backend/routes/web.php` — these must stay browser-navigated (`window.location.href` from the SPA, not axios) since Socialite needs a real redirect chain. The callback matches existing users **by email alone** and links the provider if not already linked (rather than erroring or duplicating), creates+auto-verifies new users otherwise, and finishes with `redirect()->away(frontend_url.'/auth/callback')` so the SPA can hydrate its session via `GET /api/v1/auth/me`. Provider credentials come from `backend/config/services.php` via `GOOGLE_*`/`GITHUB_*` env vars; Socialite runs in `stateless()` mode.
- **Known-fixed gap**: `users.social_provider`/`users.social_id` columns and their unique-together constraint are created by `backend/database/migrations/*_add_social_fields_to_users_table.php`. That migration is written defensively (checks `Schema::hasColumn`/existing index names before altering) because this environment's DB had already drifted from migrations via manual edits before this fix — worth knowing if you see similar drift elsewhere.
- **Migrations**: stock Laravel auth tables, Sanctum's `personal_access_tokens`, Spatie's permission tables, and the social-fields migration. No domain tables (courses, enrollments, etc.) exist yet.
- **Frontend structure** under `frontend/src/`:
    - `api/` — axios client (`client.js`, handles CSRF-cookie + credentials) and per-domain endpoint modules (`auth.js`, more to come per phase).
    - `app/` — router (`router.jsx`), `RequireAuth` route guard (checks the `/auth/me` query, optionally a role), layouts (`layouts/GuestLayout.jsx`, `layouts/AppLayout.jsx`).
    - `features/` — one folder per domain (`auth/` today: `LoginPage`, `RegisterPage`, `OAuthCallbackPage`, `useAuth.js` React Query hooks; `dashboard/` placeholder). Later phases add `catalog/`, `cart-checkout/`, `learning/`, `curriculum-authoring/`, `instructor-dashboard/`, `admin/`.
    - `components/` — shared UI primitives.
    - `lib/apiErrors.js` — flattens Laravel's 422 validation-error shape for form display.
    - Auth state is **not** a separate context/store — it's just the React Query cache for `['auth', 'me']` (`useAuthUser()` in `features/auth/useAuth.js`), read wherever needed.

## Known environment quirk

This machine has had multiple unrelated `php artisan serve` processes left running and bound to `127.0.0.1:8000` at once (observed alongside an unrelated `careerhub-job-platform` project) — Windows will let more than one process claim the same port in this state, and which one actually answers a given request is effectively random. If `php artisan serve` requests behave unexpectedly (wrong app responds, 404s for real routes), run `netstat -ano | grep :8000` and check for stray `php.exe` processes before assuming a code bug; picking an alternate `--port` is the quickest way to confirm.

## Commit conventions

Use conventional commit prefixes (`feat:`, `fix:`, `refactor:`, `chore:`), keep the message short but descriptive, and never include Claude attribution (no name, no `Co-Authored-By` trailer).
