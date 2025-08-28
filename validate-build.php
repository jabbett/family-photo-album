<?php

/**
 * Build Validation Script
 * 
 * This script validates the Laravel application for common issues
 * before declaring work complete.
 */

echo "🔍 Validating Laravel Application...\n";

$errors = [];
$warnings = [];

// Check for syntax errors in PHP files
echo "📝 Checking PHP syntax...\n";
$phpFiles = glob('app/**/*.php');
foreach ($phpFiles as $file) {
    $output = [];
    $returnCode = 0;
    exec("php -l $file 2>&1", $output, $returnCode);
    
    if ($returnCode !== 0) {
        $errors[] = "Syntax error in $file: " . implode("\n", $output);
    }
}

// Check for missing routes
echo "🛣️ Checking routes...\n";
$routes = [
    'settings/site' => 'Site settings route',
    'settings/family-members' => 'Family members settings route',
    'dashboard' => 'Dashboard route',
    'login' => 'Login route',
    'register' => 'Register route',
];

$routesContent = file_exists('routes/web.php') ? file_get_contents('routes/web.php') : '';
$authContent = file_exists('routes/auth.php') ? file_get_contents('routes/auth.php') : '';

foreach ($routes as $route => $description) {
    $found = false;
    
    // Check web.php
    if (str_contains($routesContent, $route)) {
        $found = true;
    }
    
    // Check auth.php for auth routes
    if (in_array($route, ['login', 'register']) && str_contains($authContent, $route)) {
        $found = true;
    }
    
    // Check for route names instead of paths
    $routeName = str_replace('/', '', $route);
    if (str_contains($routesContent, "route('$routeName'") || str_contains($routesContent, "name('$routeName'")) {
        $found = true;
    }
    
    if (!$found) {
        $warnings[] = "Route $route ($description) not found in routes files";
    }
}

// Check for missing models
echo "📦 Checking models...\n";
$models = [
    'app/Models/User.php' => 'User model',
    'app/Models/Setting.php' => 'Setting model',
    'app/Models/AllowedEmail.php' => 'AllowedEmail model',
];

foreach ($models as $model => $description) {
    if (!file_exists($model)) {
        $errors[] = "Missing $description: $model";
    }
}

// Check for missing migrations
echo "🗄️ Checking migrations...\n";
$migrations = [
    'database/migrations/*_create_users_table.php' => 'Users table migration',
    'database/migrations/*_create_settings_table.php' => 'Settings table migration',
    'database/migrations/*_create_allowed_emails_table.php' => 'Allowed emails table migration',
];

foreach ($migrations as $pattern => $description) {
    $files = glob($pattern);
    if (empty($files)) {
        $warnings[] = "Missing $description";
    }
}

// Check for missing views
echo "👁️ Checking views...\n";
$views = [
    'resources/views/livewire/settings/site.blade.php' => 'Site settings view',
    'resources/views/livewire/settings/family-members.blade.php' => 'Family members settings view',
    'resources/views/dashboard.blade.php' => 'Dashboard view',
    'resources/views/welcome.blade.php' => 'Welcome view',
];

foreach ($views as $view => $description) {
    if (!file_exists($view)) {
        $errors[] = "Missing $description: $view";
    }
}

// Check for common Blade syntax issues
echo "🔧 Checking Blade templates...\n";
$bladeFiles = glob('resources/views/**/*.blade.php');
foreach ($bladeFiles as $file) {
    $content = file_get_contents($file);
    
    // Check for unclosed Blade tags
    if (substr_count($content, '{{') !== substr_count($content, '}}')) {
        $warnings[] = "Possible unclosed Blade tags in $file";
    }
    
    // Check for invalid Flux component usage
    if (str_contains($content, 'flux:button') && str_contains($content, 'variant="secondary"')) {
        $errors[] = "Invalid button variant 'secondary' in $file - use 'outline' instead";
    }
}

// Check for missing middleware
echo "🔒 Checking middleware...\n";
if (!file_exists('app/Http/Middleware/AdminMiddleware.php')) {
    $errors[] = "Missing AdminMiddleware";
}

// Check for missing seeders
echo "🌱 Checking seeders...\n";
if (!file_exists('database/seeders/FamilyUsersSeeder.php')) {
    $warnings[] = "Missing FamilyUsersSeeder";
}

// Report results
echo "\n📊 Validation Results:\n";
echo str_repeat("=", 50) . "\n";

if (empty($errors) && empty($warnings)) {
    echo "✅ All checks passed! Application appears to be ready.\n";
    exit(0);
}

if (!empty($errors)) {
    echo "❌ ERRORS FOUND:\n";
    foreach ($errors as $error) {
        echo "  • $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️ WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  • $warning\n";
    }
    echo "\n";
}

echo "💡 Recommendations:\n";
echo "  1. Fix all errors before testing\n";
echo "  2. Address warnings if they affect functionality\n";
echo "  3. Run 'php artisan test' to verify functionality\n";
echo "  4. Test manually in browser before declaring complete\n";

exit(!empty($errors) ? 1 : 0);
