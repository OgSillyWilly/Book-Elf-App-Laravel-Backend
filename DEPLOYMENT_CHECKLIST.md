# 🔐 SECURE DEPLOYMENT - Quick Checklist

## ✅ Pre-Deployment Security Check

**BEFORE deploying, verify:**

1. ✅ `.env` files are gitignored (already checked ✓)
2. ✅ No secrets committed to git
3. ✅ `fly.toml` contains NO secrets (only config)
4. ✅ Review app name in `fly.toml` (currently: `book-elf`)

## 🚀 Deployment Options

### Option A: Automated Script (Recommended)

```bash
./deploy.sh
```

This script will:
- Verify security
- Generate APP_KEY securely
- Create Fly.io app
- Set secrets (NOT in git!)
- Create volume
- Deploy
- Initialize database

### Option B: Manual Steps

If you prefer manual control:

```bash
# 1. Install & login to Fly.io
brew install flyctl
flyctl auth login

# 2. Generate APP_KEY (save this!)
php artisan key:generate --show
# Copy the output: base64:xxxxx...

# 3. Launch app (no deploy yet)
flyctl launch --no-deploy

# 4. Set secrets (NEVER commit these!)
flyctl secrets set APP_KEY="base64:YOUR_KEY_FROM_STEP_2"
flyctl secrets set APP_URL="https://book-elf.fly.dev"
flyctl secrets set APP_DEBUG="false"

# 5. Create persistent volume for database
flyctl volumes create data --region ams --size 1

# 6. Deploy
flyctl deploy

# 7. Initialize database
flyctl ssh console
# Inside SSH:
cd /var/www/html
touch database/database.sqlite
chmod 664 database/database.sqlite
chown www-data:www-data database/database.sqlite
php artisan migrate --force
exit

# 8. Test
curl https://book-elf.fly.dev/up
curl https://book-elf.fly.dev/api/books
```

## 🔒 Secrets Management

**Where secrets are stored:**

✅ **Fly.io (secure):**
- `APP_KEY` - Set via `flyctl secrets set`
- `APP_URL` - Set via `flyctl secrets set`
- `APP_DEBUG` - Set via `flyctl secrets set`

✅ **fly.toml (safe to commit):**
- App name, region, memory size
- No passwords or keys!

❌ **NEVER commit to git:**
- `.env` files
- APP_KEY values
- Database credentials
- Any passwords/tokens

## 🔐 View Secrets (Safely)

```bash
# List secret names (values are hidden)
flyctl secrets list

# Update a secret
flyctl secrets set APP_DEBUG="true"

# Remove a secret
flyctl secrets unset SECRET_NAME
```

## 🧪 Test After Deployment

```bash
# Health check
curl https://book-elf.fly.dev/up

# API endpoints
curl https://book-elf.fly.dev/api/books
curl https://book-elf.fly.dev/api/series
curl https://book-elf.fly.dev/api/statistics/overview

# View logs
flyctl logs

# Check status
flyctl status
```

## ⚠️ Important Security Notes

1. **APP_KEY is critical:**
   - Used for encryption/signing
   - Generate once, never change in production
   - Changing it will invalidate sessions/cookies

2. **Never commit secrets:**
   - `.env` files are gitignored ✓
   - Use `flyctl secrets set` only
   - Secrets stored encrypted on Fly.io

3. **If you accidentally commit a secret:**
   ```bash
   # Immediately regenerate it
   php artisan key:generate --show
   flyctl secrets set APP_KEY="new_key_here"
   
   # Remove from git history (if needed)
   git filter-branch --force --index-filter \
     "git rm --cached --ignore-unmatch .env" \
     --prune-empty --tag-name-filter cat -- --all
   ```

4. **Database is on persistent volume:**
   - Survives redeploys ✓
   - Survives scale-to-zero ✓
   - Backup regularly: `flyctl ssh console --command "cat /var/www/html/database/database.sqlite" > backup.sqlite`

## 📊 Monitoring

```bash
# Real-time logs
flyctl logs

# SSH into machine
flyctl ssh console

# View app metrics
flyctl dashboard metrics

# Check scale status
flyctl scale show
```

## 🔄 Updates & Redeployment

When you make code changes:

```bash
# 1. Commit your changes
git add .
git commit -m "Your changes"

# 2. Deploy (migrations run automatically)
flyctl deploy

# 3. Verify
flyctl status
curl https://book-elf.fly.dev/up
```

**Note:** Secrets persist across deploys - no need to set them again!

## 📞 Getting Help

- **View deployment status:** `flyctl status`
- **View logs:** `flyctl logs`
- **SSH access:** `flyctl ssh console`
- **Fly.io docs:** https://fly.io/docs/laravel/
- **Community:** https://community.fly.io/

---

## 🎯 Ready to Deploy?

**Choose your method:**

```bash
# Automated (recommended)
./deploy.sh

# Or manual steps above
```

**Remember:** All secrets are set via `flyctl secrets set` - NEVER in code or git!
