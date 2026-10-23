# Fly.io Deployment Guide for Book Elf Laravel API

## Prerequisites
- Fly.io account created
- Fly CLI installed (`brew install flyctl` on macOS)
- Logged in to Fly.io: `flyctl auth login`

## Step-by-Step Deployment

### 1. Update fly.toml Configuration

Edit `fly.toml` and change:
```toml
app = 'your-app-name-here'  # Choose a unique name like 'book-elf-api'
primary_region = 'ams'       # Choose your region (ams, fra, lhr, etc.)
```

### 2. Generate Laravel APP_KEY

If you don't have one yet:
```bash
php artisan key:generate --show
```

Copy the output (starts with `base64:`).

### 3. Set Fly.io Secrets

Set all required secrets (replace with your actual values):

```bash
# Required: Laravel APP_KEY
flyctl secrets set APP_KEY="base64:YOUR_GENERATED_KEY_HERE"

# Required: Application URL (will be assigned after launch)
# Format: https://your-app-name.fly.dev
flyctl secrets set APP_URL="https://your-app-name.fly.dev"

# Optional: If you need debugging initially (set to false in production)
flyctl secrets set APP_DEBUG="false"

# Optional: Database path (default is fine, but you can override)
flyctl secrets set DB_DATABASE="/var/www/html/database/database.sqlite"
```

### 4. Launch the App (First Time Only)

```bash
# This creates the app on Fly.io but doesn't deploy yet
flyctl launch --no-deploy --name your-app-name --region ams

# Or if you've already updated fly.toml, just use:
flyctl launch --no-deploy
```

### 5. Create Persistent Volume for SQLite Database

```bash
# Create a 1GB volume named 'data' in your app's region
flyctl volumes create data --region ams --size 1

# Verify volume was created
flyctl volumes list
```

### 6. Initialize the Database

Before first deploy, we need to create the SQLite file. Deploy once, then:

```bash
# Deploy the application
flyctl deploy

# SSH into the machine and create the database
flyctl ssh console

# Inside the SSH session:
cd /var/www/html
touch database/database.sqlite
chmod 664 database/database.sqlite
chown www-data:www-data database/database.sqlite

# Run migrations
php artisan migrate --force

# Optional: Seed data if you have seeders
# php artisan db:seed --force

# Exit SSH
exit
```

### 7. Verify Deployment

```bash
# Check app status
flyctl status

# View logs
flyctl logs

# Test the API
curl https://your-app-name.fly.dev/up

# Test a real endpoint
curl https://your-app-name.fly.dev/api/books
```

### 8. Subsequent Deploys

After the initial setup, just run:

```bash
flyctl deploy
```

Migrations will run automatically via the `release_command` in fly.toml.

## Important Notes

### Scale-to-Zero Behavior
- Machines will stop after ~5 minutes of inactivity
- First request after stopping will take ~10-20 seconds (cold start)
- Database persists on the volume
- **⚠️ Uploaded covers in `storage/app/public/covers` will be LOST** unless you add a second volume mount

### Persistent Storage for Uploaded Covers

If you need uploaded images to persist, uncomment the storage mount in `fly.toml`:

```toml
[[mounts]]
  source = 'storage'
  destination = '/var/www/html/storage/app/public'
```

Then create the volume:
```bash
flyctl volumes create storage --region ams --size 1
```

### Scaling

To prevent scale-to-zero (keep at least 1 machine running):
```bash
flyctl scale count 1 --region ams
```

Or edit fly.toml:
```toml
min_machines_running = 1
```

### Monitoring

```bash
# View real-time logs
flyctl logs

# SSH into running machine
flyctl ssh console

# Check resource usage
flyctl status
flyctl scale show
```

### Troubleshooting

**Database locked errors:**
- SQLite doesn't handle high concurrency well
- Consider upgrading to PostgreSQL if you see these errors
- Or set `busy_timeout` in `config/database.php`:
  ```php
  'sqlite' => [
      'database' => env('DB_DATABASE', database_path('database.sqlite')),
      'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
      'busy_timeout' => 5000, // 5 seconds
  ],
  ```

**Cold starts too slow:**
- Increase VM size: `flyctl scale vm shared-cpu-1x --memory 512`
- Keep 1 machine always running: `min_machines_running = 1`

**Out of disk space:**
- Increase volume size: `flyctl volumes extend <volume-id> --size 2`

## Cost Optimization

With this configuration:
- **Free tier eligible** (up to 3 shared-cpu-1x VMs with 256MB RAM)
- Scale-to-zero = $0 when idle
- ~$2-5/month for 1GB persistent volumes
- Free 160GB outbound data transfer

## Useful Commands

```bash
# View app info
flyctl info

# View secrets (values are hidden)
flyctl secrets list

# Update a secret
flyctl secrets set APP_DEBUG="false"

# View releases
flyctl releases

# Rollback to previous version
flyctl releases rollback

# View resource usage
flyctl dashboard metrics

# Destroy app (careful!)
flyctl apps destroy your-app-name
```

## Files Created

1. ✅ `Dockerfile` - Optimized for Laravel with SQLite
2. ✅ `.dockerignore` - Excludes unnecessary files from image
3. ✅ `fly.toml` - Fly.io configuration with scale-to-zero
4. ✅ `FLY_DEPLOYMENT.md` - This deployment guide

## Next Steps

1. Update `app` name in `fly.toml`
2. Generate APP_KEY: `php artisan key:generate --show`
3. Follow deployment steps above
4. Test your API endpoints
5. Set up custom domain (optional): `flyctl certs add yourdomain.com`

---

Need help? Check:
- Fly.io Laravel docs: https://fly.io/docs/laravel/
- Fly.io community: https://community.fly.io/
