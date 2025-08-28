#!/bin/bash

echo "🧪 Testing Laravel Application..."

# Clear all caches
echo "📦 Clearing caches..."
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan cache:clear

# Check for syntax errors
echo "🔍 Checking PHP syntax..."
find app -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"

# Check for Blade syntax errors
echo "🔍 Checking Blade templates..."
find resources/views -name "*.blade.php" -exec php -l {} \; 2>/dev/null | grep -v "No syntax errors"

# Test database connection
echo "🗄️ Testing database connection..."
php artisan tinker --execute="echo 'Database connection: ' . (DB::connection()->getPdo() ? 'OK' : 'FAILED') . PHP_EOL;"

# Test key routes (if server is running)
if curl -s http://localhost:8000 > /dev/null 2>&1; then
    echo "🌐 Testing key routes..."
    
    # Test welcome page
    WELCOME_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000)
    echo "  Welcome page: $WELCOME_STATUS"
    
    # Test login page
    LOGIN_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/login)
    echo "  Login page: $LOGIN_STATUS"
    
    # Test dashboard (should redirect to login)
    DASHBOARD_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/dashboard)
    echo "  Dashboard (unauthenticated): $DASHBOARD_STATUS"
    
    # Test admin settings (should redirect to login)
    ADMIN_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/settings/admin)
    echo "  Admin settings (unauthenticated): $ADMIN_STATUS"
else
    echo "⚠️ Server not running on localhost:8000"
fi

# Run Laravel tests
echo "🧪 Running Laravel tests..."
php artisan test --stop-on-failure

echo "✅ Testing complete!"
