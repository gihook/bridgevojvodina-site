# Project Instructions

This project is a Laravel application running inside a Docker environment.

## Command Execution

All PHP and Artisan commands **MUST** be executed within the Docker container using `docker-compose exec`.

### Examples:
- **Artisan:** `docker-compose exec web php artisan ...`
- **Composer:** `docker-compose exec web composer ...`
- **PHP:** `docker-compose exec web php ...`

### Environment
- The Laravel application is located in the `bridgevojvodina-laravel/` directory.
- The `docker-compose.yml` file is in the root directory.
- When running commands, ensure you are in the root directory or adjust paths accordingly.

## Initial Setup

You can initialize or reset the database using the provided setup script in the root directory:
```bash
./setup.sh
```
This script runs fresh migrations and seeds the database with legacy data and demo tournaments.

## Development Workflow
- **Migrations:** Always run migrations via Docker after creating them.
- **Testing:** Run tests using `docker-compose exec bridgevojvodina-laravel php artisan test`.
- **Frontend:** Node/NPM commands can be run locally or via Docker if a service is provided, but typically `npm` is run locally in `bridgevojvodina-laravel/`.
