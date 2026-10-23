# 🚀 Fly.io Deployment - Quick Start

## ✅ Files Created

1. **Dockerfile** - Production-ready Laravel container with PHP 8.4
2. **.dockerignore** - Optimized to reduce image size
3. **fly.toml** - Fly.io config with scale-to-zero enabled
4. **deploy-to-fly.sh** - Automated deployment script
5. **FLY_DEPLOYMENT.md** - Complete deployment guide
6. **config/database.php** - Updated with production SQLite settings (WAL mode, 5s timeout)

## 📋 Required Fly.io Secrets

```bash
# These will be set automatically by deploy-to-fly.sh
APP_KEY="base64:..." 
APP_URL="https://your-app-name.fly.dev"
APP_DEBUG="false"
```

## ⚡ Quick Deploy (Automated)

```bash
# Option 1: Use default name and region
./deploy-to-fly.sh

# Option 2: Custom name and region
./deploy-to-fly.sh my-book-api fra
```

## 🔧 Manual Deploy (Step by Step)

```bash
# 1. Update fly.toml with your app name
vim fly.toml  # Change 'your-app-name-here'

# 2. Generate and copy APP_KEY
php artisan key:generate --show

# 3. Create app on Fly.io (no deploy yet)
flyctl launch --no-deploy

# 4. Set secrets
flyctl secrets set APP_KEY="base64:YOUR_KEY_HERE"
flyctl secrets set APP_URL="https://your-app.fly.dev"
flyctl secrets set APP_DEBUG="false"

# 5. Create volume for database
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
```

## 🧪 Testing

```bash
# Check status
flyctl status

# View logs
flyctl logs

# Test health endpoint
curl https://your-app.fly.dev/up

# Test API
curl https://your-app.fly.dev/api/books
```

## 📦 What Gets Persisted

✅ **Persistent (on volume):**
- SQLite database (`/var/www/html/database/database.sqlite`)

❌ **Ephemeral (lost on scale-to-zero):**
- Uploaded book covers (`storage/app/public/covers/*`)
- Logs
- Cache files

## ⚠️ Important: Uploaded Covers

Your app allows users to upload book covers. With scale-to-zero, these will be **LOST** when machines stop.

**Solutions:**

### Option 1: Add Storage Volume (Recommended)
Uncomment in `fly.toml`:
```toml
[[mounts]]
  source = 'storage'
  destination = '/var/www/html/storage/app/public'
```

Then create volume:
```bash
flyctl volumes create storage --region ams --size 1
```

### Option 2: Use External Storage (Best)
Consider using AWS S3, Cloudinary, or similar:
```bash
composer require league/flysystem-aws-s3-v3
# Update config/filesystems.php to use S3
# Set AWS credentials as Fly secrets
```

### Option 3: Disable Uploads
Only use external cover URLs (Google Books, Open Library) - already working!

## 💰 Cost Estimate

**Free Tier Configuration:**
- VM: `shared-cpu-1x` (256MB RAM) - **FREE**
- Volume: 1GB database - **~$0.15/month**
- Volume: 1GB storage (optional) - **~$0.15/month**
- Bandwidth: 160GB/month included - **FREE**

**Total: ~$0.30/month** (or less with scale-to-zero)

## 🔄 Scaling

```bash
# Scale to zero when idle (default in fly.toml)
flyctl scale count 0 --region ams

# Keep 1 machine always running (no cold starts)
flyctl scale count 1 --region ams

# Increase memory if needed
flyctl scale vm shared-cpu-1x --memory 512

# Multiple regions for redundancy
flyctl scale count 2  # Creates 2 machines
```

## 🛠️ Common Commands

```bash
# Redeploy after code changes
flyctl deploy

# View real-time logs
flyctl logs

# SSH into running machine
flyctl ssh console

# Run artisan commands
flyctl ssh console --command "php artisan cache:clear"

# Database backup
flyctl ssh console --command "cat /var/www/html/database/database.sqlite" > backup.sqlite

# List volumes
flyctl volumes list

# Extend volume size
flyctl volumes extend <volume-id> --size 2

# View secrets
flyctl secrets list

# Update secret
flyctl secrets set APP_DEBUG="true"

# View all releases
flyctl releases

# Rollback
flyctl releases rollback
```

## 🐛 Troubleshooting

### Cold Start Too Slow
- Increase memory: `flyctl scale vm shared-cpu-1x --memory 512`
- Keep 1 machine running: Edit `fly.toml` → `min_machines_running = 1`

### Database Locked Errors
- Already handled! Config updated with:
  - `busy_timeout = 5000ms`
  - `journal_mode = WAL` (Write-Ahead Logging)
  - If still happening, consider PostgreSQL

### Deployment Fails
```bash
# View build logs
flyctl logs

# Try building locally first
docker build -t test .

# Check secrets are set
flyctl secrets list
```

### Can't Access API
```bash
# Check if machines are running
flyctl status

# Check health checks
flyctl checks list

# Scale up manually
flyctl scale count 1
```

## 🌐 Custom Domain

```bash
# Add your domain
flyctl certs add yourdomain.com

# Follow DNS instructions shown
# Add CNAME: yourdomain.com → your-app.fly.dev

# Verify
flyctl certs show yourdomain.com
```

## 📚 Resources

- **Full Guide**: See `FLY_DEPLOYMENT.md`
- **Fly.io Docs**: https://fly.io/docs/laravel/
- **Dashboard**: https://fly.io/dashboard
- **Community**: https://community.fly.io/

## 🎯 Next Steps

1. ✅ Review `fly.toml` configuration
2. ✅ Run `./deploy-to-fly.sh` or follow manual steps
3. ✅ Test your API endpoints
4. ✅ Decide on storage strategy for uploaded covers
5. ✅ Set up monitoring/alerts (optional)
6. ✅ Configure custom domain (optional)

---

**Need help?** Check `FLY_DEPLOYMENT.md` for detailed instructions!
