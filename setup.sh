#!/bin/bash

# Setup script for Bridge Vojvodina application

echo "Starting database setup..."

# Run migrations
docker-compose exec web php artisan migrate --force

# Run seeders
docker-compose exec web php artisan db:seed --force

echo "Database setup completed successfully."
