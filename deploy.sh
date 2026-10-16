#!/bin/bash
# ============================================
# WorkBridge - Server Deployment Script
# Run this on a fresh Ubuntu 22.04+ VPS to deploy the entire application.
# Usage: bash deploy.sh
# ============================================

set -e

echo "=========================================="
echo "  WorkBridge - Production Deployment"
echo "=========================================="

# ---- Step 1: Install Docker if not present ----
if ! command -v docker &> /dev/null; then
    echo "[1/5] Installing Docker..."
    curl -fsSL https://get.docker.com | sudo sh
    sudo usermod -aG docker $USER
    echo "Docker installed. You may need to log out and back in for group changes."
else
    echo "[1/5] Docker already installed ✓"
fi

# ---- Step 2: Create .env from template if not exists ----
if [ ! -f .env ]; then
    echo "[2/5] Creating .env from template..."
    cp .env.production .env

    # Generate a random secret key
    SECRET_KEY=$(openssl rand -hex 32)
    sed -i "s/change_me_to_a_64_char_random_string/$SECRET_KEY/" .env

    # Generate random passwords
    MYSQL_ROOT_PW=$(openssl rand -hex 16)
    MYSQL_PW=$(openssl rand -hex 16)
    MONGO_PW=$(openssl rand -hex 16)
    sed -i "s/change_me_root_password/$MYSQL_ROOT_PW/" .env
    sed -i "s/change_me_db_password/$MYSQL_PW/" .env
    sed -i "s/change_me_mongo_password/$MONGO_PW/" .env

    echo "  Generated random passwords and secret key."
    echo "  Review .env file before proceeding."
else
    echo "[2/5] .env file already exists ✓"
fi

# ---- Step 3: Create required directories ----
echo "[3/5] Creating required directories..."
mkdir -p nginx/certbot/conf nginx/certbot/www

# ---- Step 4: Build and start all services ----
echo "[4/5] Building and starting all containers..."
docker compose -f docker-compose.prod.yml up -d --build

# ---- Step 5: Wait and verify ----
echo "[5/5] Waiting for services to start..."
sleep 15

echo ""
echo "=========================================="
echo "  Deployment Complete!"
echo "=========================================="
echo ""
echo "Service Status:"
docker compose -f docker-compose.prod.yml ps
echo ""
echo "Your app is now running at: http://$(hostname -I | awk '{print $1}')"
echo ""
echo "Next steps:"
echo "  1. Point your domain's DNS A record to this server's IP"
echo "  2. Run SSL setup:"
echo "     docker compose -f docker-compose.prod.yml run --rm certbot certonly \\"
echo "       --webroot --webroot-path=/var/www/certbot \\"
echo "       -d YOUR_DOMAIN.com --email YOUR_EMAIL --agree-tos"
echo "  3. Update nginx/default.conf with the SSL block (see comments in the file)"
echo "  4. Restart nginx: docker compose -f docker-compose.prod.yml restart nginx"
echo ""
