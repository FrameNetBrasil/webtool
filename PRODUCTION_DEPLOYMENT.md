# Webtool 4.2 - Production Deployment Guide

## Overview

This guide covers deploying Webtool 4.2 to production using Docker containers. The production configuration is optimized for performance, security, and reliability.

## Architecture

```
┌─────────────────────────────────────────┐
│     Reverse Proxy (Your Server)        │
│  (Nginx/Traefik/HAProxy with HTTPS)    │
└──────────────┬──────────────────────────┘
               │ HTTP
               ▼
┌─────────────────────────────────────────┐
│        Docker: Caddy Web Server         │
│              (Port 80)                  │
└──────────────┬──────────────────────────┘
               │ PHP-FPM
               ▼
┌─────────────────────────────────────────┐
│     Docker: Laravel Application         │
│          (PHP 8.4-FPM)                  │
├─────────────────────────────────────────┤
│  Connects to:                           │
│  • MariaDB (host.docker.internal)       │
│  • Neo4J (host.docker.internal)         │
│  • Redis (docker network)               │
└─────────────────────────────────────────┘

┌─────────────────┬───────────────────────┐
│ Docker: Reverb  │  Docker: Queue Worker │
│  (Port 8080)    │   (Background Jobs)   │
└─────────────────┴───────────────────────┘

┌─────────────────────────────────────────┐
│          Docker: Redis Cache            │
│            (Port 6379)                  │
└─────────────────────────────────────────┘
```

## Production Features

### Performance Optimizations
- ✅ **OPcache** - PHP bytecode cache with preloading (30-50% performance boost)
- ✅ **JIT Compilation** - PHP 8.4 Just-In-Time compiler
- ✅ **Baked Assets** - Vite-built frontend assets included in image
- ✅ **Cached Configuration** - Laravel config, routes, events, views cached
- ✅ **Gzip Compression** - Caddy serves compressed responses
- ✅ **Static File Optimization** - Long cache headers for assets

### Security
- ✅ **Non-root User** - Containers run as `sail` user
- ✅ **No Development Dependencies** - Production-only packages
- ✅ **Security Headers** - X-Frame-Options, CSP, etc. via Caddy
- ✅ **Error Logging** - Errors logged, not displayed
- ✅ **Disabled Functions** - Dangerous PHP functions disabled
- ✅ **Read-only Mounts** - Configuration files mounted read-only

### Reliability
- ✅ **Health Checks** - All services monitored
- ✅ **Auto-restart** - Containers restart on failure
- ✅ **Log Rotation** - Automatic log file management
- ✅ **Graceful Shutdowns** - Proper signal handling
- ✅ **Immutable Deployments** - Code baked into image

## Prerequisites

### Server Requirements
- Docker Engine 20.10+
- Docker Compose 2.0+
- 2GB+ RAM (4GB+ recommended)
- 10GB+ disk space
- Linux server (Ubuntu 20.04+, Debian 11+, RHEL 8+, etc.)

### External Services (Running on Host or Separate Servers)
- **MariaDB** - Database server
- **Neo4J** (optional) - Graph database
- **Reverse Proxy** - Nginx/Traefik/HAProxy with SSL/HTTPS

### Installed on Server
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install docker.io docker-compose-plugin git

# Start Docker
sudo systemctl enable --now docker

# Add your user to docker group (logout/login after)
sudo usermod -aG docker $USER
```

## Initial Setup

### 1. Clone Repository

```bash
cd /opt
git clone <your-repo-url> webtool
cd webtool
```

### 2. Create Production .env File

```bash
cp .env.example .env
nano .env
```

**Critical production environment variables:**

```bash
# Application
APP_NAME=Webtool
APP_ENV=production
APP_KEY=<generate-with-php-artisan-key:generate>
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_PORT=80

# Laravel Sail/Docker
WWWUSER=1000
WWWGROUP=1000
SAIL_XDEBUG_MODE=off

# Database (External MariaDB)
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=webtool_prod
DB_USERNAME=webtool_user
DB_PASSWORD=<strong-password>

# Redis (Docker service)
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Broadcasting (Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=webtool-prod
REVERB_APP_KEY=<random-string>
REVERB_APP_SECRET=<random-string>
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-server
MAIL_PORT=587
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Neo4J (External, if used)
NEO4J_HOST=host.docker.internal
NEO4J_PORT=7687
NEO4J_USERNAME=neo4j
NEO4J_PASSWORD=<neo4j-password>
NEO4J_ENABLED=true

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=warning

# Version (for image tagging)
APP_VERSION=4.2.0
```

**Generate APP_KEY:**
```bash
# If you have PHP installed locally:
php artisan key:generate

# Or use a temporary Laravel container:
docker run --rm -v $(pwd):/app -w /app php:8.4-cli php artisan key:generate
```

### 3. Set File Permissions

```bash
sudo chown -R 1000:1000 storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## Building the Production Image

### Option A: Using Build Script (Recommended)

```bash
# Build with automatic timestamp version
./build-production.sh

# Build with specific version and tag as latest
./build-production.sh --version 4.2.0 --latest

# Build and push to registry
./build-production.sh --version 4.2.0 --registry registry.example.com --push --latest
```

### Option B: Manual Build

```bash
# Build the production image
docker build \
    -f Dockerfile.production \
    -t webtool:4.2.0 \
    --build-arg WWWUSER=$(id -u) \
    --build-arg WWWGROUP=$(id -g) \
    .

# Tag as latest
docker tag webtool:4.2.0 webtool:latest
```

## Deployment

### First-Time Deployment

```bash
# 1. Update APP_VERSION in .env
echo "APP_VERSION=4.2.0" >> .env

# 2. Start services
docker-compose -f docker-compose.prod.yml up -d

# 3. Wait for services to be healthy
docker-compose -f docker-compose.prod.yml ps

# 4. Run database migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# 5. Optimize Laravel
docker-compose -f docker-compose.prod.yml exec app php artisan optimize

# 6. Verify everything is running
docker-compose -f docker-compose.prod.yml ps
```

### Updating to New Version

```bash
# 1. Pull latest code
git pull origin main

# 2. Build new image with version tag
./build-production.sh --version 4.2.1

# 3. Update .env with new version
sed -i 's/APP_VERSION=.*/APP_VERSION=4.2.1/' .env

# 4. Recreate containers with new image
docker-compose -f docker-compose.prod.yml up -d

# 5. Run migrations (if any)
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# 6. Clear and rebuild caches
docker-compose -f docker-compose.prod.yml exec app php artisan optimize

# 7. Verify deployment
docker-compose -f docker-compose.prod.yml ps
```

### Zero-Downtime Deployment (Rolling Update)

```bash
# 1. Build new image
./build-production.sh --version 4.2.1

# 2. Update .env
sed -i 's/APP_VERSION=.*/APP_VERSION=4.2.1/' .env

# 3. Rolling update (one service at a time)
docker-compose -f docker-compose.prod.yml up -d --no-deps --build app
docker-compose -f docker-compose.prod.yml up -d --no-deps --build reverb
docker-compose -f docker-compose.prod.yml up -d --no-deps --build queue

# 4. Run migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

## Common Operations

### View Logs

```bash
# All services
docker-compose -f docker-compose.prod.yml logs -f

# Specific service
docker-compose -f docker-compose.prod.yml logs -f app
docker-compose -f docker-compose.prod.yml logs -f queue
docker-compose -f docker-compose.prod.yml logs -f reverb

# Laravel application logs
docker-compose -f docker-compose.prod.yml exec app tail -f storage/logs/laravel.log
```

### Access Container Shell

```bash
# Laravel application
docker-compose -f docker-compose.prod.yml exec app bash

# As root
docker-compose -f docker-compose.prod.yml exec --user root app bash
```

### Run Artisan Commands

```bash
# General pattern
docker-compose -f docker-compose.prod.yml exec app php artisan <command>

# Examples
docker-compose -f docker-compose.prod.yml exec app php artisan migrate
docker-compose -f docker-compose.prod.yml exec app php artisan optimize
docker-compose -f docker-compose.prod.yml exec app php artisan queue:restart
docker-compose -f docker-compose.prod.yml exec app php artisan cache:clear
```

### Restart Services

```bash
# Restart all services
docker-compose -f docker-compose.prod.yml restart

# Restart specific service
docker-compose -f docker-compose.prod.yml restart app
docker-compose -f docker-compose.prod.yml restart queue

# Graceful restart (recreate containers)
docker-compose -f docker-compose.prod.yml up -d --force-recreate
```

### Check Service Health

```bash
# Container status
docker-compose -f docker-compose.prod.yml ps

# Detailed health status
docker inspect webtool-app --format='{{json .State.Health}}'
docker inspect webtool-redis --format='{{json .State.Health}}'

# Resource usage
docker stats
```

### Database Operations

```bash
# Create database backup
docker-compose -f docker-compose.prod.yml exec app php artisan db:backup

# Run migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Rollback migration
docker-compose -f docker-compose.prod.yml exec app php artisan migrate:rollback --force

# Seed database
docker-compose -f docker-compose.prod.yml exec app php artisan db:seed --force
```

### Queue Management

```bash
# Check failed jobs
docker-compose -f docker-compose.prod.yml exec app php artisan queue:failed

# Retry failed job
docker-compose -f docker-compose.prod.yml exec app php artisan queue:retry <job-id>

# Restart queue workers (after code update)
docker-compose -f docker-compose.prod.yml exec app php artisan queue:restart

# Monitor queue
docker-compose -f docker-compose.prod.yml logs -f queue
```

## Scaling

### Multiple Queue Workers

Edit `docker-compose.prod.yml` and scale queue service:

```bash
docker-compose -f docker-compose.prod.yml up -d --scale queue=3
```

Or define multiple queue services for different queues:

```yaml
queue-default:
  # ... same as queue service
  command: php artisan queue:work --queue=default

queue-high:
  # ... same as queue service
  command: php artisan queue:work --queue=high --timeout=300
```

### Multiple Application Instances

```bash
# Scale app containers
docker-compose -f docker-compose.prod.yml up -d --scale app=3

# You'll need a load balancer (nginx/traefik) in front
```

## Monitoring & Maintenance

### Disk Space Management

```bash
# View disk usage
df -h

# Docker disk usage
docker system df

# Clean up old images/containers
docker system prune -a

# Clean up volumes (CAREFUL!)
docker volume prune
```

### Log Management

Logs are automatically rotated (max 10MB, 3-5 files per service).

```bash
# View log sizes
docker-compose -f docker-compose.prod.yml exec app du -sh storage/logs/

# Manually clear old logs
docker-compose -f docker-compose.prod.yml exec app sh -c "truncate -s 0 storage/logs/*.log"
```

### Performance Monitoring

```bash
# Container resource usage
docker stats

# PHP-FPM status
docker-compose -f docker-compose.prod.yml exec app curl http://localhost/status

# OPcache status
docker-compose -f docker-compose.prod.yml exec app php -r "print_r(opcache_get_status());"
```

## Troubleshooting

### Container Won't Start

```bash
# Check logs
docker-compose -f docker-compose.prod.yml logs <service-name>

# Check health status
docker inspect <container-name> --format='{{json .State}}'

# Rebuild container
docker-compose -f docker-compose.prod.yml up -d --build --force-recreate <service-name>
```

### Database Connection Issues

```bash
# Test connection from app container
docker-compose -f docker-compose.prod.yml exec app php artisan tinker
>>> DB::connection()->getPdo();

# Check if host.docker.internal resolves
docker-compose -f docker-compose.prod.yml exec app ping -c 3 host.docker.internal

# Verify DB credentials in .env
docker-compose -f docker-compose.prod.yml exec app cat .env | grep DB_
```

### Permission Issues

```bash
# Fix storage permissions
docker-compose -f docker-compose.prod.yml exec --user root app chown -R sail:www storage bootstrap/cache
docker-compose -f docker-compose.prod.yml exec --user root app chmod -R 775 storage bootstrap/cache
```

### High Memory Usage

```bash
# Check container memory
docker stats --no-stream

# Limit container memory (docker-compose.prod.yml)
services:
  app:
    mem_limit: 1g
    mem_reservation: 512m
```

### Queue Not Processing

```bash
# Check if queue worker is running
docker-compose -f docker-compose.prod.yml exec queue ps aux | grep queue

# Check queue status
docker-compose -f docker-compose.prod.yml exec app php artisan queue:monitor

# Restart queue
docker-compose -f docker-compose.prod.yml restart queue
```

## Rollback Procedure

```bash
# 1. Stop current containers
docker-compose -f docker-compose.prod.yml down

# 2. Update .env to previous version
sed -i 's/APP_VERSION=4.2.1/APP_VERSION=4.2.0/' .env

# 3. Start containers with previous image
docker-compose -f docker-compose.prod.yml up -d

# 4. Rollback database if needed
docker-compose -f docker-compose.prod.yml exec app php artisan migrate:rollback --force

# 5. Verify
docker-compose -f docker-compose.prod.yml ps
```

## Backup & Restore

### Backup

```bash
# 1. Database backup (on MariaDB host)
mysqldump -u webtool_user -p webtool_prod > backup-$(date +%Y%m%d).sql

# 2. Storage files
tar -czf storage-backup-$(date +%Y%m%d).tar.gz storage/

# 3. Docker volumes
docker run --rm -v webtool-storage:/data -v $(pwd):/backup alpine tar czf /backup/volume-backup-$(date +%Y%m%d).tar.gz /data
```

### Restore

```bash
# 1. Database restore
mysql -u webtool_user -p webtool_prod < backup-20250110.sql

# 2. Storage files
tar -xzf storage-backup-20250110.tar.gz

# 3. Docker volumes
docker run --rm -v webtool-storage:/data -v $(pwd):/backup alpine tar xzf /backup/volume-backup-20250110.tar.gz -C /
```

## Security Best Practices

1. **Keep Secrets Secret**
   - Never commit `.env` to version control
   - Use environment-specific `.env` files
   - Rotate secrets regularly

2. **Update Regularly**
   - Keep Docker images updated
   - Update base images monthly
   - Monitor security advisories

3. **Firewall Rules**
   ```bash
   # Only expose necessary ports
   ufw allow 80/tcp    # HTTP (from reverse proxy)
   ufw allow 8080/tcp  # Reverb WebSockets
   ufw enable
   ```

4. **Use HTTPS**
   - Always terminate SSL at reverse proxy
   - Use valid SSL certificates (Let's Encrypt)
   - Redirect HTTP to HTTPS

5. **Monitor Logs**
   - Regularly review application logs
   - Set up log aggregation (ELK, Graylog, etc.)
   - Alert on errors

## Performance Tuning

### PHP-FPM Tuning

Edit `docker/php/php.production.ini`:

```ini
[opcache]
opcache.memory_consumption = 512  # Increase if needed
opcache.max_accelerated_files = 30000  # Increase for large apps

[PHP]
memory_limit = 1024M  # Increase for heavy operations
```

### Redis Tuning

Edit `docker-compose.prod.yml`:

```yaml
redis:
  command: redis-server --maxmemory 512mb --maxmemory-policy allkeys-lru
```

### Queue Worker Tuning

```yaml
queue:
  command: php artisan queue:work --sleep=1 --tries=3 --max-jobs=1000 --max-time=3600
```

## Additional Resources

- [Laravel Production Deployment](https://laravel.com/docs/12.x/deployment)
- [Docker Best Practices](https://docs.docker.com/develop/dev-best-practices/)
- [Caddy Documentation](https://caddyserver.com/docs/)
- [PHP-FPM Tuning](https://www.php.net/manual/en/install.fpm.configuration.php)

## Support

For issues or questions:
- Check application logs: `storage/logs/laravel.log`
- Check container logs: `docker-compose -f docker-compose.prod.yml logs`
- Review this documentation
- Contact your development team
