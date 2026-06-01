#!/bin/bash

# Setup script for Bridge Vojvodina application

echo "Installing Composer dependencies..."
docker run --rm -v $(pwd)/bridgevojvodina-laravel:/app composer install

echo "Installing NPM dependencies and building assets..."
docker run --rm -v $(pwd)/bridgevojvodina-laravel:/app -w /app node:20 npm install
docker run --rm -v $(pwd)/bridgevojvodina-laravel:/app -w /app node:20 npm run build

echo "Generating application key..."
docker-compose exec web php artisan key:generate

echo "Fixing permissions for storage and bootstrap/cache..."
docker-compose exec web chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "Starting database setup..."

# Run migrations
docker-compose exec web php artisan migrate --force

# Run seeders
docker-compose exec web php artisan db:seed --force

echo "Database setup completed successfully."
