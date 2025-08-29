#!/bin/bash

# Cygnuz ERP Docker Setup Script
# This script helps you quickly set up and run Cygnuz ERP using Docker

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_message() {
    echo -e "${2}${1}${NC}"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# ASCII Art Banner
echo "
╔═══════════════════════════════════════════════════════════╗
║                                                           ║
║        ╔═══╗╦ ╦╔═══╗╔╗╔╦ ╦╔═══╗  ╔═══╗╔═══╗╔═══╗        ║
║        ║    ╚╦╝║ ╔═╝║╚╝║║ ║╠═══║  ║═══╣║ ╔═╝║ ╔═╝        ║
║        ╚═══╝ ╩ ╚═══╝╩   ╚═╝╚═══╝  ╚═══╝╩ ╩  ╩            ║
║                                                           ║
║             Enterprise Resource Planning                  ║
║                Docker Setup Wizard                        ║
║                                                           ║
╚═══════════════════════════════════════════════════════════╝
"

# Check for Docker
if ! command_exists docker; then
    print_message "❌ Docker is not installed. Please install Docker first." "$RED"
    print_message "Visit: https://docs.docker.com/get-docker/" "$YELLOW"
    exit 1
fi

# Check for Docker Compose and set the appropriate command
if docker compose version >/dev/null 2>&1; then
    DOCKER_COMPOSE="docker compose"
elif command_exists docker-compose; then
    DOCKER_COMPOSE="docker-compose"
else
    print_message "❌ Docker Compose is not installed. Please install Docker Compose first." "$RED"
    print_message "Visit: https://docs.docker.com/compose/install/" "$YELLOW"
    exit 1
fi

print_message "✅ Docker and Docker Compose are installed!" "$GREEN"

# Function to generate random password
generate_password() {
    openssl rand -base64 32 | tr -d "=+/" | cut -c1-25
}

# Function to check if port is in use
check_port() {
    local port=$1
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
        return 0  # Port is in use
    else
        return 1  # Port is free
    fi
}

# Function to setup environment
setup_environment() {
    print_message "\n📝 Setting up environment configuration..." "$YELLOW"
    
    # Check for port conflicts
    if check_port 3306; then
        print_message "⚠️  Port 3306 is already in use (probably local MySQL)" "$YELLOW"
        print_message "   You can either:" "$YELLOW"
        echo "   1. Stop your local MySQL: brew services stop mysql"
        echo "   2. Use a different port by setting DB_PORT=3307 in .env"
        read -p "   Use port 3307 instead? (y/n): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            export DB_PORT=3307
            print_message "   Using port 3307 for MySQL" "$GREEN"
        fi
    fi
    
    # Check if .env exists
    if [ ! -f .env ]; then
        if [ -f .env.example ]; then
            cp .env.example .env
            print_message "✅ Created .env from .env.example" "$GREEN"
        else
            print_message "❌ .env.example not found. Creating basic .env file..." "$YELLOW"
            cat > .env << EOF
APP_NAME="Cygnuz ERP"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=cygnuz_erp
DB_USERNAME=cygnuz_user
DB_PASSWORD=$(generate_password)

REDIS_HOST=redis
REDIS_PASSWORD=$(generate_password)
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@cygnuzerp.com"
MAIL_FROM_NAME="\${APP_NAME}"
EOF
            print_message "✅ Created basic .env file" "$GREEN"
        fi
    else
        print_message "ℹ️  .env file already exists" "$YELLOW"
        # Update DB_PORT if needed
        if [ ! -z "$DB_PORT" ]; then
            if [[ "$OSTYPE" == "darwin"* ]]; then
                sed -i '' "s/DB_PORT=.*/DB_PORT=$DB_PORT/" .env
            else
                sed -i "s/DB_PORT=.*/DB_PORT=$DB_PORT/" .env
            fi
            print_message "   Updated DB_PORT to $DB_PORT in .env" "$GREEN"
        fi
    fi
    
    # Generate app key if not set
    if ! grep -q "APP_KEY=base64:" .env; then
        print_message "🔑 Generating application key..." "$YELLOW"
        APP_KEY=$(docker run --rm -v $(pwd):/app -w /app php:8.3-cli php -r "echo 'base64:' . base64_encode(random_bytes(32));")
        # Escape forward slashes in the APP_KEY to prevent sed issues
        APP_KEY_ESCAPED=$(echo "$APP_KEY" | sed 's/\//\\\//g')
        if [[ "$OSTYPE" == "darwin"* ]]; then
            sed -i '' "s/APP_KEY=.*/APP_KEY=$APP_KEY_ESCAPED/" .env
        else
            sed -i "s/APP_KEY=.*/APP_KEY=$APP_KEY_ESCAPED/" .env
        fi
        print_message "✅ Application key generated" "$GREEN"
    fi
}

# Function to start services
start_services() {
    local COMPOSE_FILE=$1
    local PROFILE=$2
    
    print_message "\n🚀 Starting Docker containers..." "$YELLOW"
    
    if [ "$PROFILE" == "dev" ]; then
        $DOCKER_COMPOSE -f $COMPOSE_FILE --profile dev up -d
    else
        $DOCKER_COMPOSE -f $COMPOSE_FILE up -d
    fi
    
    print_message "✅ Docker containers started!" "$GREEN"
}

# Function to wait for services
wait_for_services() {
    print_message "\n⏳ Waiting for services to be ready..." "$YELLOW"
    
    # Wait for MySQL
    echo -n "Waiting for MySQL..."
    until $DOCKER_COMPOSE exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; do
        echo -n "."
        sleep 2
    done
    echo " Ready!"
    
    # Wait for Redis
    echo -n "Waiting for Redis..."
    until $DOCKER_COMPOSE exec -T redis redis-cli ping 2>/dev/null | grep -q PONG; do
        echo -n "."
        sleep 2
    done
    echo " Ready!"
    
    print_message "✅ All services are ready!" "$GREEN"
}

# Function to run initial setup
run_initial_setup() {
    print_message "\n🔧 Running initial setup..." "$YELLOW"
    
    # Run migrations
    print_message "Running database migrations..." "$YELLOW"
    $DOCKER_COMPOSE exec -T app php artisan migrate --force
    
    # Create storage link
    print_message "Creating storage link..." "$YELLOW"
    $DOCKER_COMPOSE exec -T app php artisan storage:link || true
    
    # Clear caches
    print_message "Clearing caches..." "$YELLOW"
    $DOCKER_COMPOSE exec -T app php artisan config:clear
    $DOCKER_COMPOSE exec -T app php artisan cache:clear
    
    print_message "✅ Initial setup completed!" "$GREEN"
}

# Function to seed database
seed_database() {
    print_message "\n🌱 Seeding database with demo data..." "$YELLOW"
    
    read -p "Do you want to seed the database with demo data? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        $DOCKER_COMPOSE exec -T app php artisan db:seed --force || true
        $DOCKER_COMPOSE exec -T app php artisan module:seed HRCore --force || true
        print_message "✅ Database seeded with demo data!" "$GREEN"
        print_message "\n📧 Demo Accounts:" "$YELLOW"
        echo "  Super Admin: superadmin@demo.com / 123456"
        echo "  Admin: admin@demo.com / 123456"
        echo "  HR Manager: hr.manager@demo.com / 123456"
        echo "  Employee: employee@demo.com / 123456"
    fi
}

# Main menu
show_menu() {
    echo ""
    print_message "Please select an option:" "$YELLOW"
    echo "1) Quick Start (Development)"
    echo "2) Production Setup"
    echo "3) Stop All Services"
    echo "4) View Logs"
    echo "5) Run Artisan Command"
    echo "6) Access Container Shell"
    echo "7) Reset Everything"
    echo "8) Exit"
}

# Main execution
main() {
    while true; do
        show_menu
        read -p "Enter your choice [1-8]: " choice
        
        case $choice in
            1)
                print_message "\n🚀 Starting Development Environment..." "$GREEN"
                setup_environment
                start_services "docker-compose.yml" "dev"
                wait_for_services
                run_initial_setup
                seed_database
                print_message "\n✨ Cygnuz ERP is ready!" "$GREEN"
                print_message "🌐 Application: http://localhost:8000" "$YELLOW"
                print_message "📧 MailHog: http://localhost:8025" "$YELLOW"
                print_message "🗄️ phpMyAdmin: http://localhost:8080" "$YELLOW"
                ;;
            2)
                print_message "\n🚀 Starting Production Environment..." "$GREEN"
                setup_environment
                start_services "docker-compose.prod.yml" ""
                wait_for_services
                run_initial_setup
                print_message "\n✨ Cygnuz ERP is ready for production!" "$GREEN"
                print_message "🌐 Application: http://localhost" "$YELLOW"
                ;;
            3)
                print_message "\n🛑 Stopping all services..." "$YELLOW"
                $DOCKER_COMPOSE down
                print_message "✅ All services stopped!" "$GREEN"
                ;;
            4)
                print_message "\n📋 Showing logs (Ctrl+C to exit)..." "$YELLOW"
                $DOCKER_COMPOSE logs -f
                ;;
            5)
                read -p "Enter artisan command (e.g., 'migrate:status'): " cmd
                $DOCKER_COMPOSE exec app php artisan $cmd
                ;;
            6)
                print_message "\n🐚 Accessing container shell..." "$YELLOW"
                $DOCKER_COMPOSE exec app /bin/sh
                ;;
            7)
                print_message "\n⚠️  WARNING: This will delete all data!" "$RED"
                read -p "Are you sure? (yes/no): " confirm
                if [ "$confirm" == "yes" ]; then
                    $DOCKER_COMPOSE down -v
                    rm -f .env
                    print_message "✅ Everything has been reset!" "$GREEN"
                fi
                ;;
            8)
                print_message "\n👋 Goodbye!" "$GREEN"
                exit 0
                ;;
            *)
                print_message "❌ Invalid option. Please try again." "$RED"
                ;;
        esac
    done
}

# Run main function
main