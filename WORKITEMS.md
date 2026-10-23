# Work items

## BE-1 Deploy the per-user isolation changes to Fly.io (guided, together)

Goal: get the current `development` work live on `book-elf-api` and smoke-test it with two accounts. Nobody uses the Flutter app yet, so breaking the old public API is acceptable. Do not start without the owner's go-ahead.

### Pre-flight
- [ ] `vendor/bin/pest` is green (75 tests).
- [ ] Decide what to commit. `HANDOFF.md` suggests (1) Sanctum auth and tests, (2) per-user isolation, (3) Fly.io deployment files. Commits need the owner's approval, and the `Co-authored-by: Copilot` trailer.
- [ ] Decide the legacy Blade UI (`/`, `/books` in `routes/web.php`). It has no login and is empty for guests. Remove or protect it.
- [ ] The production DB is empty, so the non-nullable `user_id` migration is safe. Confirm with `flyctl ssh console -a book-elf-api -C "php artisan tinker --execute='echo App\Models\Book::withoutGlobalScopes()->count();'"` (expect 0). If it is not 0, the migration will fail and needs a backfill plan.
- [ ] `flyctl auth login` (flyctl was not logged in earlier) and `flyctl status -a book-elf-api`.

### Deploy
- [ ] `flyctl deploy -a book-elf-api`. The entrypoint runs `php artisan migrate --force` on start, which applies the personal_access_tokens and user_id migrations.
- [ ] `flyctl logs -a book-elf-api` and confirm both migrations ran without errors.
- [ ] Optional: `flyctl secrets set SANCTUM_TOKEN_EXPIRATION=43200 -a book-elf-api`.
- Note: CI (`.github/workflows/fly-deploy.yml`) deploys on push to `main` and has never run. Deploy manually for now.

### Smoke test (replace `$A` and `$B` with tokens from register)
```bash
API=https://book-elf-api.fly.dev/api
H='-H Accept:application/json -H Content-Type:application/json'

curl -i $H $API/books                                  # 401
curl -i $H $API/image-proxy?url=x                      # 401

A=$(curl -s $H -X POST $API/register -d '{"name":"A","email":"a@test.dev","password":"password123","password_confirmation":"password123"}' | jq -r .token)
B=$(curl -s $H -X POST $API/register -d '{"name":"B","email":"b@test.dev","password":"password123","password_confirmation":"password123"}' | jq -r .token)

BOOK=$(curl -s $H -H "Authorization: Bearer $A" -X POST $API/books -d '{"title":"A book","author":"A"}' | jq -r .id)
curl -s $H -H "Authorization: Bearer $A" $API/books | jq '.data|length'            # 1
curl -s $H -H "Authorization: Bearer $B" $API/books | jq '.data|length'            # 0
curl -i $H -H "Authorization: Bearer $B" $API/books/$BOOK                          # 404
curl -i $H -H "Authorization: Bearer $B" -X DELETE $API/books/$BOOK                # 404
curl -s $H -H "Authorization: Bearer $B" $API/statistics/overview | jq .total_books  # 0
curl -s $H -H "Authorization: Bearer $A" $API/statistics/overview | jq .total_books  # 1
```
- [ ] Also check on the live app: series, reading challenge (create, then B gets 404), cover upload (the file lands in `covers/{user_id}/`), and CSV import.
- [ ] Clean up: delete the test users and their data (cascade delete removes their rows). E.g. `flyctl ssh console -a book-elf-api -C "php artisan tinker --execute='App\Models\User::whereIn(\"email\",[\"a@test.dev\",\"b@test.dev\"])->delete();'"`.

### Known risks to review during the deploy
- Uploaded covers are ephemeral (no storage volume mounted), so they disappear on redeploy. Consider a volume for `storage/app/public`.
- `/storage/{path}` is public, so cover files are readable by anyone with the URL.
- `fly.toml` has both `[http_service]` and `[[services]]` for the same port. Review.
- The docs `FLY_DEPLOYMENT.md` and `QUICKSTART.md` are partly stale (`release_command`, DB path, WAL mode).
- Rollback: `flyctl releases -a book-elf-api` then `flyctl deploy -a book-elf-api --image <previous image>`. Note that the `user_id` migration has a `down()`, but rolling back the code alone leaves the column, which is harmless.

### Done when
Two accounts on the live API see only their own data, and the test data is removed.
