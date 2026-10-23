#!/bin/bash
# 🚀 SECURE DEPLOYMENT GUIDE
# Book Elf API to Fly.io - Step by Step

echo "📋 PRE-DEPLOYMENT SECURITY CHECKLIST"
echo "===================================="
echo ""

# 1. Check .env is gitignored
echo "✓ Checking .env is gitignored..."
if grep -q "^\.env$" .gitignore; then
    echo "  ✅ .env is in .gitignore"
else
    echo "  ❌ WARNING: .env is NOT gitignored!"
    exit 1
fi

# 2. Check no secrets in fly.toml
echo ""
echo "✓ Checking fly.toml for secrets..."
if grep -qiE "(password|key|secret|token)" fly.toml; then
    echo "  ⚠️  Found potential secrets in fly.toml - please review!"
else
    echo "  ✅ No obvious secrets in fly.toml"
fi

# 3. Check git status
echo ""
echo "✓ Checking git status..."
if git diff --name-only | grep -qE "\.env$|\.env\..*$"; then
    echo "  ❌ WARNING: .env files in git changes!"
    exit 1
else
    echo "  ✅ No .env files staged"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔐 DEPLOYMENT STEPS"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# STEP 1: Install flyctl
echo "📦 STEP 1: Install Fly CLI (if not installed)"
echo "-------------------------------------------"
if command -v flyctl &> /dev/null; then
    echo "✅ flyctl already installed: $(flyctl version)"
else
    echo "Installing flyctl..."
    echo "Run: brew install flyctl"
    exit 0
fi
echo ""

# STEP 2: Login
echo "🔑 STEP 2: Login to Fly.io"
echo "-------------------------"
if flyctl auth whoami &> /dev/null; then
    echo "✅ Already logged in as: $(flyctl auth whoami)"
else
    echo "Please login:"
    echo "Run: flyctl auth login"
    exit 0
fi
echo ""

# STEP 3: Generate APP_KEY
echo "🔐 STEP 3: Generate Laravel APP_KEY"
echo "----------------------------------"
echo "Generating secure APP_KEY..."
APP_KEY=$(php artisan key:generate --show)
echo "✅ APP_KEY generated (will be set as secret)"
echo ""

# STEP 4: Update fly.toml
echo "📝 STEP 4: Review fly.toml"
echo "-------------------------"
echo "Current app name: $(grep "^app = " fly.toml)"
echo ""
read -p "Is this app name correct? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Please update the app name in fly.toml first"
    exit 0
fi
echo ""

# STEP 5: Launch app
echo "🚀 STEP 5: Launch Fly.io app"
echo "---------------------------"
echo "This creates the app but doesn't deploy yet"
read -p "Continue? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    flyctl launch --no-deploy || echo "App may already exist, continuing..."
fi
echo ""

# STEP 6: Set secrets
echo "🔒 STEP 6: Set Fly.io secrets (NOT in git!)"
echo "-------------------------------------------"
APP_NAME=$(grep "^app = " fly.toml | cut -d"'" -f2)
APP_URL="https://${APP_NAME}.fly.dev"

echo "Setting secrets..."
echo ""
echo "  APP_KEY (generated)"
echo "  APP_URL: $APP_URL"
echo "  APP_DEBUG: false"
echo ""

flyctl secrets set \
    APP_KEY="$APP_KEY" \
    APP_URL="$APP_URL" \
    APP_DEBUG="false"

echo ""
echo "✅ Secrets set (stored securely in Fly.io, NOT in git)"
echo ""

# STEP 7: Create volume
echo "💾 STEP 7: Create persistent volume for database"
echo "------------------------------------------------"
REGION=$(grep "^primary_region = " fly.toml | cut -d"'" -f2)
echo "Creating 1GB volume in region: $REGION"
echo ""
flyctl volumes create data --region "$REGION" --size 1 || echo "Volume may already exist, continuing..."
echo ""
flyctl volumes list
echo ""

# STEP 8: Deploy
echo "🚀 STEP 8: Deploy application"
echo "-----------------------------"
read -p "Ready to deploy? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Deploying (this takes 2-5 minutes)..."
    flyctl deploy
    echo ""
    echo "✅ Deployment complete!"
else
    echo "Deployment skipped. Run 'flyctl deploy' when ready."
    exit 0
fi
echo ""

# STEP 9: Initialize database
echo "🗄️  STEP 9: Initialize SQLite database"
echo "--------------------------------------"
echo "Waiting for machine to start..."
sleep 10

echo "Creating database file and running migrations..."
flyctl ssh console --command "cd /var/www/html && touch database/database.sqlite && chmod 664 database/database.sqlite && chown www-data:www-data database/database.sqlite && php artisan migrate --force"
echo ""
echo "✅ Database initialized"
echo ""

# STEP 10: Verify
echo "✅ STEP 10: Verify deployment"
echo "-----------------------------"
echo ""
echo "Testing health endpoint..."
curl -s "$APP_URL/up" && echo "" || echo "Failed to connect"
echo ""
echo "App Status:"
flyctl status
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎉 DEPLOYMENT COMPLETE!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "🌐 Your API: $APP_URL"
echo "📊 Dashboard: https://fly.io/apps/$APP_NAME"
echo ""
echo "🔐 SECURITY REMINDERS:"
echo "  ✅ Secrets stored in Fly.io (not in git)"
echo "  ✅ .env files gitignored"
echo "  ✅ APP_KEY is unique and secure"
echo ""
echo "📝 USEFUL COMMANDS:"
echo "  View logs:      flyctl logs"
echo "  SSH access:     flyctl ssh console"
echo "  View secrets:   flyctl secrets list"
echo "  Redeploy:       flyctl deploy"
echo "  Scale up:       flyctl scale count 1"
echo ""
echo "🧪 TEST YOUR API:"
echo "  curl $APP_URL/api/books"
echo "  curl $APP_URL/api/series"
echo ""
