#!/bin/bash
# Fly.io Deployment Script for Book Elf Laravel API
# This script automates the deployment process

set -e  # Exit on error

echo "🚀 Book Elf API - Fly.io Deployment"
echo "===================================="
echo ""

# Check if flyctl is installed
if ! command -v flyctl &> /dev/null; then
    echo "❌ Error: flyctl is not installed"
    echo "Install it with: brew install flyctl"
    exit 1
fi

# Check if logged in
if ! flyctl auth whoami &> /dev/null; then
    echo "❌ Error: Not logged in to Fly.io"
    echo "Run: flyctl auth login"
    exit 1
fi

# Variables
APP_NAME="${1:-book-elf-api}"
REGION="${2:-ams}"

echo "📝 Configuration:"
echo "   App Name: $APP_NAME"
echo "   Region: $REGION"
echo ""

# Ask for confirmation
read -p "Continue with deployment? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment cancelled."
    exit 0
fi

# Step 1: Update fly.toml
echo "📝 Step 1: Updating fly.toml..."
sed -i.bak "s/your-app-name-here/$APP_NAME/" fly.toml
rm fly.toml.bak
echo "✅ fly.toml updated"
echo ""

# Step 2: Generate APP_KEY if not exists
echo "🔑 Step 2: Checking APP_KEY..."
if ! grep -q "^APP_KEY=" .env 2>/dev/null; then
    echo "Generating new APP_KEY..."
    php artisan key:generate
fi
APP_KEY=$(php artisan key:generate --show)
echo "✅ APP_KEY ready"
echo ""

# Step 3: Launch app (no deploy)
echo "🎯 Step 3: Creating Fly.io app..."
flyctl launch --no-deploy --name "$APP_NAME" --region "$REGION" || echo "App may already exist, continuing..."
echo "✅ App created"
echo ""

# Step 4: Set secrets
echo "🔐 Step 4: Setting secrets..."
flyctl secrets set \
    APP_KEY="$APP_KEY" \
    APP_URL="https://$APP_NAME.fly.dev" \
    APP_DEBUG="false"
echo "✅ Secrets set"
echo ""

# Step 5: Create volume
echo "💾 Step 5: Creating persistent volume..."
flyctl volumes create data --region "$REGION" --size 1 || echo "Volume may already exist, continuing..."
flyctl volumes list
echo "✅ Volume ready"
echo ""

# Step 6: Deploy
echo "🚀 Step 6: Deploying application..."
flyctl deploy
echo "✅ Application deployed"
echo ""

# Step 7: Initialize database
echo "📊 Step 7: Initializing database..."
echo "Waiting for machine to start..."
sleep 10

flyctl ssh console --command "cd /var/www/html && touch database/database.sqlite && chmod 664 database/database.sqlite && chown www-data:www-data database/database.sqlite && php artisan migrate --force"
echo "✅ Database initialized"
echo ""

# Final status
echo "🎉 Deployment complete!"
echo ""
echo "📊 App Status:"
flyctl status
echo ""
echo "🌐 Your API is available at: https://$APP_NAME.fly.dev"
echo "🔍 Test it: curl https://$APP_NAME.fly.dev/up"
echo ""
echo "📝 Useful commands:"
echo "   View logs: flyctl logs"
echo "   SSH access: flyctl ssh console"
echo "   Redeploy: flyctl deploy"
echo "   Scale to 1: flyctl scale count 1"
echo ""
