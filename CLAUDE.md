# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel 10 REST API backend for a padel tennis match management application. Uses PHP 8.1+, Laravel Sanctum for token-based authentication, and L5 Swagger for API documentation.

## Common Commands

```bash
# Install dependencies
composer install
npm install

# Run development server
php artisan serve

# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Unit
php artisan test tests/Feature

# Run a single test file
php artisan test tests/Feature/SomeTest.php

# Run with coverage
php artisan test --coverage

# Database migrations
php artisan migrate

# Regenerate Swagger docs
php artisan l5-swagger:generate

# Code formatting (Laravel Pint)
./vendor/bin/pint

# Build frontend assets
npm run build
npm run dev
```

## Architecture

### API Structure

All API routes are defined in `routes/api.php`. Routes are prefixed with `/api` automatically.

**Public routes:** `/api/login`, `/api/register`
**Protected routes (auth:sanctum middleware):** All others - require Bearer token from login.

### Controllers

Controllers live in `app/Http/Controllers/`. API auth controller is namespaced under `Api/`:

- **AuthController** (`Api/AuthController`) - login, register, logout, me (returns user with player relation)
- **PlayerController** - CRUD for player profiles linked to users
- **GameController** - CRUD for padel matches/games
- **GamePlayerController** - adds players to games (pivot management)
- **ClubController** - CRUD for clubs/venues
- **CourtController** - CRUD for individual courts
- **ClubCourtController** - lists courts belonging to a specific club

### Models & Relationships

Models are in `app/Models/`. Key relationships:

- **User** has one **Player**
- **Player** belongs to many **Games** (through `game_players` pivot)
- **Player** has many owned **Games** (`owner_player_id`)
- **Club** has many **Courts**
- **Game** belongs to a **Club**, a **Court**, and an owner **Player**
- **Player** has one **PlayerStat**

### Database

Migrations are in `database/migrations/`. The app supports MySQL and PostgreSQL (docker-compose uses PostgreSQL). Key tables beyond standard Laravel: `clubs`, `players`, `courts`, `games`, `game_players`, `player_stats`, `chat_messages`, `monthly_stats`, `friends`, `game_invitations`, `time_slots`, `bookings`.

### API Documentation

Controllers use OpenAPI/Swagger annotations (`@OA\Get`, `@OA\Post`, etc.). Documentation is accessible at `/api/documentation` after generating with `php artisan l5-swagger:generate`. Security scheme uses Bearer token (Sanctum).

### Docker

`docker-compose.yml` provides a PostgreSQL container (`laravel_postgres` on port 5432, db: `laravel_db`, user: `laravel`, password: `laravel123`).

## Language

The codebase uses Portuguese for commit messages, Swagger tag descriptions, and some variable/field naming. Follow this convention when contributing.
