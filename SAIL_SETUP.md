# Laravel Sail Setup Guide

## Overview

Webtool 4.2 has been converted to use **Laravel Sail** - Laravel's official Docker development environment. 
This provides a consistent, containerized development setup with PHP 8.4, MariaDB, Redis, and more.

## What Changed

### Previous Setup
- Custom Dockerfile with Caddy web server
- Manual Docker Compose configuration
- Services: Caddy, PHP, Reverb, Queue, Redis

### New Sail Setup
- Standard Laravel Sail with PHP 8.4
- Built-in nginx/Apache via Sail
- Services: Laravel App, Redis, Reverb, Queue Worker
- MariaDB runs externally (not in Docker)
- Neo4J runs externally (not in Docker)

## Services Overview

| Service | Port | Description |
|---------|------|-------------|
| **laravel.test** | 80 | Main Laravel application (nginx/Apache) |
| **redis** | 6379 | Redis cache/session store |
| **reverb** | 8080 | Laravel Reverb WebSocket server |
| **queue** | - | Background queue worker |

**External Services (Not in Docker):**
- **MariaDB** - Your existing database server
- **Neo4J** - Graph database (if enabled)

## Prerequisites

- Docker and Docker Compose installed
- Git
- Node.js/npm/yarn (for Vite, runs on host machine)

## Initial Setup

### 1. Update Your .env File

**IMPORTANT:** Update your `.env` file with these Sail-specific settings:

```bash
# Application
APP_PORT=80

# Laravel Sail
WWWUSER=1000
WWWGROUP=1000
SAIL_XDEBUG_MODE=off

# Database (External MariaDB - not in Docker)
# Use host.docker.internal to access host machine from Docker
DB_HOST=host.docker.internal
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Port Forwarding
FORWARD_REDIS_PORT=6379

# Reverb
REVERB_APP_ID=webtool
REVERB_APP_KEY=app-key
REVERB_APP_SECRET=app-secret
REVERB_HOST=localhost
REVERB_PORT=8080

# Neo4J (External - not in Docker)
NEO4J_HOST=localhost
NEO4J_ENABLED=false
```

### 2. Build Sail Containers

First time only - build the Docker images:

```bash
./vendor/bin/sail build --no-cache
```

### 3. Start Sail

```bash
./vendor/bin/sail up -d
```

The `-d` flag runs containers in the background (detached mode).

### 4. Run Database Migrations

```bash
./vendor/bin/sail artisan migrate
```

## Daily Usage

### Shell Alias (Recommended)

Add this to your `~/.bashrc` or `~/.zshrc`:

```bash
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```

After adding, reload your shell:
```bash
source ~/.bashrc  # or source ~/.zshrc
```

Now you can use `sail` instead of `./vendor/bin/sail`:

```bash
sail up -d
sail artisan migrate
sail composer install
```

### Common Commands

| Task | Command |
|------|---------|
| **Start services** | `sail up -d` |
| **Stop services** | `sail down` |
| **View logs** | `sail logs` or `sail logs -f` (follow) |
| **Access shell** | `sail shell` or `sail root-shell` |
| **Run Artisan** | `sail artisan [command]` |
| **Run Composer** | `sail composer [command]` |
| **Run Tests** | `sail test` or `sail artisan test` |
| **Run Tinker** | `sail tinker` |
| **Database CLI** | `sail mariadb` |
| **Restart services** | `sail restart` |

## Development Workflow

### Frontend Development (Vite)

Vite runs **outside** Docker on your host machine:

```bash
# Terminal 1 - Sail containers
sail up

# Terminal 2 - Vite dev server (host machine)
yarn dev
# or
npm run dev
```

Vite will be accessible at: `http://localhost:5173`

### Accessing Services

- **Application:** http://localhost
- **Reverb WebSockets:** ws://localhost:8080
- **Redis:** localhost:6379
- **Database:** Your external MariaDB server (configured in .env)

### Queue Worker

The queue worker runs automatically as a service. To monitor it:

```bash
sail logs queue -f
```

### Reverb WebSockets

Reverb runs automatically as a service:

```bash
sail logs reverb -f
```

## Troubleshooting

### Port Conflicts

If ports are already in use, update your `.env`:

```bash
APP_PORT=8000
FORWARD_DB_PORT=33060
FORWARD_REDIS_PORT=63790
```

### Permission Issues

If you encounter permission errors:

```bash
# Set correct user/group IDs
echo "WWWUSER=$(id -u)" >> .env
echo "WWWGROUP=$(id -g)" >> .env

# Rebuild containers
sail down
sail build --no-cache
sail up -d
```

### Database Connection Issues

Since you're using an **external database** (not in Docker), ensure your `.env` has:
```bash
DB_HOST=host.docker.internal  # Allows Docker to access host machine
```

This special hostname `host.docker.internal` allows containers to connect to services running on your host machine.

**Note:** Inside Docker containers:
- Use `host.docker.internal` to access services on your host machine (like your MariaDB)
- Use service names (like `redis`) to communicate with other Docker services

### Clear Everything and Start Fresh

```bash
# Stop and remove all containers, networks, and volumes
sail down -v

# Rebuild from scratch
sail build --no-cache

# Start fresh
sail up -d

# Run migrations
sail artisan migrate
```

### View Container Status

```bash
docker ps
# or
sail ps
```

## Neo4J Configuration

Neo4J runs **externally** (not in Docker). Configure in `.env`:

```bash
NEO4J_HOST=localhost  # or your external Neo4J host
NEO4J_PORT=7687
NEO4J_USERNAME=neo4j
NEO4J_PASSWORD=your_password
NEO4J_ENABLED=true  # Set to true when ready to use
```

## Xdebug Setup

To enable Xdebug for debugging:

1. Update `.env`:
```bash
SAIL_XDEBUG_MODE=develop,debug
```

2. Restart Sail:
```bash
sail down
sail up -d
```

3. Configure your IDE to listen on port 9003

## Customizing Services

To add more Docker services (Mailpit, Meilisearch, etc.):

```bash
sail artisan sail:add
```

This will prompt you to select additional services.

**Note:** The current setup intentionally excludes database services since you're using an external MariaDB instance.

## Production Note

**Laravel Sail is for local development only.** Do not use Sail in production. 
For production deployment, use proper Docker setups or hosting platforms like Laravel Forge, Laravel Vapor, or custom server configurations.

## Additional Resources

- [Laravel Sail Documentation](https://laravel.com/docs/12.x/sail)
- [Docker Documentation](https://docs.docker.com/)
- [MariaDB Documentation](https://mariadb.org/documentation/)

## Backup Information

Your old Docker configuration has been backed up to:
- `docker-compose.old.yml`

## Questions?

If you encounter issues not covered here, check:
1. Docker logs: `sail logs`
2. Laravel logs: `storage/logs/laravel.log`
3. Laravel Sail GitHub: https://github.com/laravel/sail
