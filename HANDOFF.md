# Hand-off: Book Elf Laravel Backend – Authentication, Authorisation, Deployment

_Last updated: 2026-10-23 · Branch: `development` (tracks `origin/development`, nothing committed yet)_

## 1. Where things stand

| Area | Status |
|------|--------|
| Authentication (Sanctum bearer tokens) | ✅ Implemented, hardened, tested locally (Pest suite green) |
| Deployment to Fly.io (`book-elf-api`) | ✅ Live at https://book-elf-api.fly.dev (`/up` → 200). ⚠️ Live version pre-dates the auth fixes below – **needs redeploy** |
| Per-user authorisation / data ownership | ✅ Implemented + tested locally (75 Pest tests pass). **Not deployed, not committed** |
| Commits | ❌ All work is uncommitted (≈27 changed/new files) |

## 2. Authentication – what exists

- Laravel Sanctum (`laravel/sanctum ^4`), `User` uses `HasApiTokens`.
- Endpoints (see [routes/api/auth.php](routes/api/auth.php), controller [AuthController](app/Http/Controllers/Api/AuthController.php)):
  - `POST /api/register` – name, email, password, `password_confirmation` (min 8) → `201 {user, token, token_type: "Bearer"}`
  - `POST /api/login` – email, password → `200 {user, token, token_type}`; wrong creds → `422`
  - `POST /api/logout` – (auth) revokes the current token
  - `GET /api/user` – (auth) current user
- Clients send `Authorization: Bearer <token>`.

### Changes made in this session
1. **Fixed the 500 on unauthenticated `/api/user`** (cause: guest redirect to a non-existent `login` route when no `Accept: application/json` header). [bootstrap/app.php](bootstrap/app.php) now has `redirectGuestsTo(fn () => null)` and `shouldRenderJsonWhen` for `api/*` → returns a proper **401 JSON**.
2. **Login no longer uses the session guard** (`Auth::attempt`); it uses `Hash::check` – correct for a stateless API.
3. **Removed double hashing** in register (the `User` model's `hashed` cast already hashes).
4. **Rate limiting**: `throttle:10,1` on register and login (→ `429`).
5. **Token expiry**: [config/sanctum.php](config/sanctum.php) `expiration` now `env('SANCTUM_TOKEN_EXPIRATION', 43200)` (30 days; was never-expiring).
6. **Tests**: new [tests/Feature/AuthApiTest.php](tests/Feature/AuthApiTest.php) (register/login/validation/duplicate/401/invalid & expired token/logout/rate limit).

### Test infra changes (earlier work, already in the working tree)
- `RefreshDatabase` enabled in [tests/Pest.php](tests/Pest.php); custom `migrate:fresh` removed from [tests/TestCase.php](tests/TestCase.php); `ExampleTest.php` deleted; `BookApiTest` updated for paginated `data` and image-proxy domain validation.

Run tests: `vendor/bin/pest`

## 3. Deployment – what exists

- Fly app `book-elf-api`, region `ams`, 256 MB shared CPU, scale-to-zero, SQLite on volume `data` mounted at `/data` ([fly.toml](fly.toml)).
- `Dockerfile` + [docker-entrypoint.sh](docker-entrypoint.sh) (runs `php artisan migrate --force` on container start, so the `personal_access_tokens` migration applies automatically).
- CI: [.github/workflows/fly-deploy.yml](.github/workflows/fly-deploy.yml) deploys on push to **`main`** (needs repo secret `FLY_API_TOKEN`). GitHub showed **0 workflow runs** – CI has never fired; the live app was presumably deployed manually via `flyctl deploy`.
- Helper docs/scripts: `FLY_DEPLOYMENT.md`, `DEPLOYMENT_CHECKLIST.md`, `QUICKSTART.md`, `deploy.sh`, `deploy-to-fly.sh`, `.env.fly.example`. No secrets found in these files.

### Redeploy steps (not done – `flyctl` was not logged in here)
```bash
flyctl auth login
flyctl deploy -a book-elf-api
flyctl logs -a book-elf-api          # confirm migrations ran
# verify
curl -i https://book-elf-api.fly.dev/api/user                  # expect 401 JSON
curl -i -X POST https://book-elf-api.fly.dev/api/login -H 'Accept: application/json' -d '{}'   # expect 422
```
Optional Fly secret: `flyctl secrets set SANCTUM_TOKEN_EXPIRATION=43200`.

### Known deployment gotchas
- Docs (`FLY_DEPLOYMENT.md`) mention a `release_command` and DB path `/var/www/html/database/database.sqlite`; the real config uses `/data/database.sqlite` and migrates in the entrypoint. `QUICKSTART.md` says WAL mode but config uses `DELETE`. Docs are partly stale.
- `fly.toml` has both `[http_service]` and `[[services]]` blocks for the same port – likely redundant; review.
- Uploaded covers (`storage/app/public/covers`) are **ephemeral** (no storage volume mounted).
- `.env.fly.example` says `SESSION_DRIVER=database`; `fly.toml` uses `cookie`.
- Live `/api/books` currently returns an empty list → the production DB has no data.

## 4. Per-user authorisation – DONE (uncommitted, undeployed)

**Design**
- Migration `2026_10_23_120000_add_user_id_to_books_series_and_reading_challenges`: non-nullable, indexed `user_id` FK → `users`, `cascadeOnDelete`. Assumes an empty DB (true for prod). A local dev DB with rows must be `migrate:fresh`ed.
- Trait [BelongsToUser](app/Models/Concerns/BelongsToUser.php) on `Book`, `Series`, `ReadingChallenge`: global scope `user_id = Auth::id()` (**fails closed**: no authenticated user → no rows) and sets `user_id` on create. `user_id` is not mass-assignable. Because the scope applies to route-model binding, other users' records give **404**. Policies were therefore not needed.
- Console/admin code must use `Model::withoutGlobalScopes()` (already done in `FetchBookCovers` and the `populate_year_read_from_dates` migration).
- All data routes + `image-proxy` + `books/template/download` are inside `auth:sanctum` ([routes/api.php](routes/api.php)). Only register/login are public.
- `series_id` on book create/update is validated against the user's own series (`Rule::exists(...)->where('user_id')`). It was previously **not accepted at all** by `StoreBookRequest`/`UpdateBookRequest`.
- Caches are per user: `books_{uid}_{hash}`, tracker `books_cache_keys:{uid}`, `statistics:{uid}:*`, tracker `statistics_keys:{uid}`. `clearCache` only clears the caller's keys (it used to `Cache::flush()` everything).
- Covers are stored in `covers/{user_id}/…`; delete only removes files inside the caller's own directory (the old code used `public_path()` and never actually deleted anything).
- `BooksImport` takes the user id and assigns imported books.
- Tests: [UserIsolationTest](tests/Feature/UserIsolationTest.php) (two users, 401s, 404s, caches, covers, import, series_id), `BookApiTest` uses `Sanctum::actingAs`. New factories for `Series` and `ReadingChallenge`.

**API contract changes (for the client)**
1. Every endpoint except register/login needs `Authorization: Bearer <token>`; otherwise 401 JSON.
2. Other users' records → 404 (same as non-existent).
3. `image-proxy` now needs the header. `cover_url` values returned by the API point at `/api/image-proxy?...`, so `Image.network`/`CachedNetworkImage` must pass `httpHeaders`.
4. `series_id` is now accepted (and validated) on book POST/PUT.
5. Cover URLs are now `/storage/covers/{user_id}/…`.

**Known gaps / follow-ups**
- Flutter client (`../Book-Elf-App-Flutter-Frontend`, separate repo) has **no auth at all**: no login/register screen, no token storage, ~33 raw `http` calls (`api_data_service.dart`, `image_upload_service.dart`, `books_list_screen.dart`, `book_grid_item.dart`) and 5+ image widgets. It will break against this backend until updated. Deploying before the client is updated locks the current app out.
- Legacy web UI (`routes/web.php`: `/`, `/books` Blade) has no login. It is now empty for guests and creating a book from it would fail (no user). Decide whether to remove or protect it.
- `/storage/{path}` is still public (unguessable-by-convention only: `book_{id}_{timestamp}`), so cover files are readable by anyone with the URL.
- `series_order` is still not accepted by the book request rules (pre-existing).
- Statistics caches are not invalidated when books change (5-minute staleness, pre-existing).

## 5. Housekeeping before/after next session
- Nothing is committed. Suggested commits: (1) Sanctum auth + tests, (2) Fly.io deployment files. Consider whether `deploy.sh` / `deploy-to-fly.sh` duplication should be consolidated.
- `main` is what CI deploys; work currently lives on `development`. Merge → `main` will trigger the Fly deploy (requires `FLY_API_TOKEN` secret).
- Session history lookup found no earlier sessions for this project; this document was reconstructed from the working tree and live checks.
