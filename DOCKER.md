# 🐳 Docker Setup Guide for Cygnuz ERP

This guide will help you run Cygnuz ERP using Docker, making deployment quick and consistent across all environments.

## 📋 Prerequisites

- Docker Engine 20.10+ ([Install Docker](https://docs.docker.com/get-docker/))
- Docker Compose 2.0+ ([Install Docker Compose](https://docs.docker.com/compose/install/))
- 4GB RAM minimum (8GB recommended)
- 10GB free disk space

## 🚀 Quick Start

### Option 1: Using the Setup Script (Recommended)

```bash
# Make the script executable
chmod +x docker-setup.sh

# Run the interactive setup
./docker-setup.sh
```

The script provides an interactive menu with options for:
- Quick development setup with demo data
- Production deployment
- Container management
- Database operations

### Option 2: Manual Setup

#### Development Environment

```bash
# Copy environment file
cp .env.example .env

# Start all services with development tools
docker-compose --profile dev up -d

# Wait for services to be ready (check logs)
docker-compose logs -f

# Run migrations
docker-compose exec app php artisan migrate

# Seed demo data (optional)
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan module:seed HRCore
```

#### Production Environment

```bash
# Copy and configure environment
cp .env.example .env
# Edit .env with production values

# Build and start production services
docker-compose -f docker-compose.prod.yml up -d

# Run migrations
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

## 📦 Available Services

### Core Services

| Service | Port | Description |
|---------|------|-------------|
| **app** | 8000 | Main Laravel application (Nginx + PHP-FPM) |
| **mysql** | 3306 | MySQL 8.0 database |
| **redis** | 6379 | Redis cache and queue backend |
| **queue** | - | Laravel queue worker (2 workers) |
| **scheduler** | - | Laravel task scheduler (cron) |

### Development Services (--profile dev)

| Service | Port | Description |
|---------|------|-------------|
| **mailhog** | 8025 | Email testing interface |
| **phpmyadmin** | 8080 | Database management UI |

## 🔧 Configuration

### Environment Variables

Key environment variables in `.env`:

```env
# Application
APP_NAME="Cygnuz ERP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_PORT=8000

# Database
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=cygnuz_erp
DB_USERNAME=cygnuz_user
DB_PASSWORD=your_password

# Redis
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password

# Mail (Development)
MAIL_HOST=mailhog
MAIL_PORT=1025
```

### Volumes

Persistent data is stored in Docker volumes:

- `mysql_data` - MySQL database files
- `redis_data` - Redis persistence
- `app_storage` - Laravel storage directory
- `app_logs` - Application logs

## 📝 Common Commands

### Container Management

```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# Stop and remove volumes (⚠️ deletes data)
docker-compose down -v

# View logs
docker-compose logs -f [service_name]

# Restart a service
docker-compose restart [service_name]
```

### Laravel Artisan

```bash
# Run any artisan command
docker-compose exec app php artisan [command]

# Common commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan queue:restart
docker-compose exec app php artisan storage:link
```

### Module Management

```bash
# List modules
docker-compose exec app php artisan module:list

# Enable a module
docker-compose exec app php artisan module:enable [ModuleName]

# Run module migrations
docker-compose exec app php artisan module:migrate [ModuleName]

# Seed module data
docker-compose exec app php artisan module:seed [ModuleName]
```

### Database Access

```bash
# Access MySQL CLI
docker-compose exec mysql mysql -u root -p

# Import database dump
docker-compose exec -T mysql mysql -u root -p cygnuz_erp < backup.sql

# Export database
docker-compose exec mysql mysqldump -u root -p cygnuz_erp > backup.sql
```

### Debugging

```bash
# Access application shell
docker-compose exec app sh

# Access PHP REPL (Tinker)
docker-compose exec app php artisan tinker

# View PHP logs
docker-compose exec app tail -f /var/log/php-fpm/www-error.log

# View Nginx logs
docker-compose exec app tail -f /var/log/nginx/error.log
```

## 🏗️ Building Custom Images

### Build for Development

```bash
# Build image with current code
docker build -t cygnuzerp/app:dev .

# Use custom image
docker-compose up -d
```

### Build for Production

```bash
# Build optimized production image
docker build -t cygnuzerp/app:latest .

# Push to registry
docker push cygnuzerp/app:latest

# Deploy using production compose
docker-compose -f docker-compose.prod.yml up -d
```

## 🔒 Security Considerations

### Production Deployment

1. **Change default passwords** in `.env`
2. **Use SSL/TLS** - Put a reverse proxy (Nginx/Traefik) in front
3. **Limit exposed ports** - Only expose necessary ports
4. **Use secrets management** for sensitive data
5. **Regular updates** - Keep images and dependencies updated
6. **Backup regularly** - Implement automated backups

### SSL Setup with Traefik (Example)

```yaml
# docker-compose.override.yml
services:
  traefik:
    image: traefik:v2.10
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./traefik:/etc/traefik
      - /var/run/docker.sock:/var/run/docker.sock
    labels:
      - "traefik.enable=true"
      
  app:
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.app.rule=Host(`erp.yourdomain.com`)"
      - "traefik.http.routers.app.tls=true"
      - "traefik.http.routers.app.tls.certresolver=letsencrypt"
```

## 🚨 Troubleshooting

### Common Issues

#### 1. Permission Errors

```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

#### 2. Database Connection Refused

```bash
# Check if MySQL is running
docker-compose ps mysql

# Check MySQL logs
docker-compose logs mysql

# Test connection
docker-compose exec app php artisan db:monitor
```

#### 3. Redis Connection Error

```bash
# Check Redis status
docker-compose exec redis redis-cli ping

# Clear Redis cache
docker-compose exec redis redis-cli FLUSHALL
```

#### 4. Container Won't Start

```bash
# Check logs for specific service
docker-compose logs [service_name]

# Rebuild containers
docker-compose build --no-cache
docker-compose up -d
```

#### 5. Out of Disk Space

```bash
# Clean up unused Docker resources
docker system prune -a --volumes

# Check volume sizes
docker system df
```

## 🔄 Updating Cygnuz ERP

```bash
# Pull latest changes
git pull origin main

# Rebuild containers
docker-compose build --no-cache

# Restart services
docker-compose down
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Clear caches
docker-compose exec app php artisan optimize:clear
```

## 📊 Performance Tuning

### Docker Resources

Configure Docker Desktop resources:
- CPUs: 4+ cores
- Memory: 6-8 GB
- Swap: 2 GB
- Disk: 20+ GB

### PHP-FPM Optimization

Edit `docker/php/www.conf`:
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
```

### MySQL Optimization

Create `docker/mysql/custom.cnf`:
```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 128M
```

## 🤝 Contributing

When contributing Docker-related changes:

1. Test in both development and production modes
2. Update this documentation
3. Ensure backward compatibility
4. Add new services to both compose files if needed
5. Document any new environment variables

## 📚 Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)
- [Laravel Docker Best Practices](https://laravel.com/docs/deployment)
- [Cygnuz ERP Documentation](./README.md)

## 📞 Support

If you encounter issues with Docker setup:

1. Check the [Troubleshooting](#-troubleshooting) section
2. Review [GitHub Issues](https://github.com/CZ-App-Studio/cygnuz-erp/issues)
3. Join our [Discord Community](https://discord.gg/cygnuz)
4. Contact support at docker-support@cygnuzerp.com

---

**Note**: This Docker setup is optimized for both development and production use. For large-scale deployments, consider using Kubernetes or Docker Swarm for orchestration.