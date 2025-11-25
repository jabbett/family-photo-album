# Laravel + Dreamhost + Claude Code Quickstart Guide

**For:** Small software consultancy building high-quality, low-cost PHP applications
**Target Platform:** Dreamhost shared hosting
**Development Tool:** Claude Code
**Version:** 1.0 (Based on family-photo-album project)

---

## Table of Contents

1. [Philosophy and Principles](#philosophy-and-principles)
2. [Prerequisites](#prerequisites)
3. [Initial Project Setup](#initial-project-setup)
4. [Technology Stack Decisions](#technology-stack-decisions)
5. [Git Repository Setup](#git-repository-setup)
6. [Automated Testing Setup](#automated-testing-setup)
7. [Development Environment](#development-environment)
8. [Dreamhost Deployment Preparation](#dreamhost-deployment-preparation)
9. [Claude Code Workflow](#claude-code-workflow)
10. [Context7 MCP Documentation](#context7-mcp-documentation)
11. [Allowed Tools and Permissions](#allowed-tools-and-permissions)
12. [Production Best Practices](#production-best-practices)
13. [Laravel Development Patterns from Production Code](#laravel-development-patterns-from-production-code)
14. [Common Patterns](#common-patterns)
15. [Troubleshooting](#troubleshooting)

---

## Philosophy and Principles

### Core Values
- **Simplicity Over Complexity**: Choose the simplest solution that proves the feature works. Don't add complexity until it's needed.
- **Full Content Ownership**: Self-hosted solutions on inexpensive shared hosting.
- **Mobile-First**: Design for mobile, enhance for desktop.
- **Friction-Free Experience**: Minimize barriers to user adoption.
- **Test-Driven Confidence**: Comprehensive automated testing from day one.

### The Simplicity Test
**Before adding any dependency or pattern, ask:**
- Can this be done with vanilla PHP/JS and Laravel's built-in features?
- Is this solving a problem we actually have, or one we might have?
- Will this make deployment to shared hosting more complex?

**Example from family-photo-album:**
✅ **Good**: Used vanilla JavaScript with CropperJS instead of Vue/React framework
❌ **Avoid**: Adding a full SPA framework for a few interactive components

---

## Prerequisites

### Development Machine (Mac)
```bash
# Recommended: Laravel Herd (Mac-native Laravel environment)
# Download from: https://herd.laravel.com/

# Alternative: Traditional setup
brew install php@8.2
brew install composer
brew install node
```

### Required Tools
- **PHP**: 8.2 or higher
- **Composer**: Latest stable
- **Node.js**: Latest LTS (for Vite and npm)
- **Git**: For version control
- **Claude Code**: Latest version with MCP support
- **Context7 MCP**: Installed and configured (critical!)

### Dreamhost Account Requirements
- Shared hosting account with SSH access
- PHP 8.2+ available (configure in panel)
- MySQL database provisioned
- Domain configured

---

## Initial Project Setup

### Option 1: Using Laravel Herd (Recommended for Mac)

```bash
# If using Herd, it provides a streamlined Laravel environment
# Create new Laravel project
composer create-project laravel/laravel your-project-name
cd your-project-name

# Herd automatically handles:
# - PHP version management
# - Local domain (your-project-name.test)
# - SSL certificates
```

### Option 2: Traditional Laravel Installation

```bash
# Using Composer
composer create-project laravel/laravel your-project-name
cd your-project-name

# Start development server
php artisan serve
```

### Initial Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database for local development
touch database/database.sqlite

# Configure .env for local development
# Edit .env to set:
DB_CONNECTION=sqlite
# Comment out DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Run initial migration
php artisan migrate

# Link storage directory
php artisan storage:link
```

### Verify Installation

```bash
# Test that everything works
php artisan --version  # Should show Laravel 12.x
php artisan route:list  # Should show default routes
```

---

## Technology Stack Decisions

### Backend Framework
**Choice: Laravel 12**
- Latest stable Laravel version
- Excellent documentation
- Built-in authentication and authorization
- ORM (Eloquent) simplifies database interactions
- Queue system (though we avoid using it on shared hosting)

### Database Strategy
**Local Development: SQLite**
```env
DB_CONNECTION=sqlite
```
- Zero configuration
- Fast for development
- File-based (database/database.sqlite)
- Perfect for testing (in-memory mode)

**Production: MySQL**
```env
DB_CONNECTION=mysql
DB_HOST=mysql.example.com
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```
- Provided by Dreamhost
- Better for production traffic
- Familiar to most developers
- Easy backups through Dreamhost panel

### Frontend Stack
**UI Framework: Livewire + Volt**
```bash
composer require livewire/volt
```
- Reactive components without heavy JavaScript
- Laravel-native patterns
- Minimal client-side complexity
- Perfect for interactive forms and CRUD operations

**UI Components: Flux**
```bash
composer require livewire/flux
```
- Pre-built Livewire components
- Consistent design system
- Reduces boilerplate

**CSS: Tailwind CSS 4.x**
```bash
npm install tailwindcss @tailwindcss/vite
```
- Utility-first CSS
- Excellent mobile-first support
- Minimal custom CSS needed
- Vite integration for fast builds

**JavaScript Philosophy: Vanilla JS + Targeted Libraries**
- **Default**: Vanilla JavaScript for most interactions
- **When needed**: Add focused libraries (e.g., CropperJS for image cropping)
- **Avoid**: Heavy frameworks (React, Vue) unless absolutely necessary

### Testing Stack
**PHP Testing: Pest**
```bash
composer require pestphp/pest --dev
composer require pestphp/pest-plugin-laravel --dev
```
- Modern, elegant test syntax
- Better than PHPUnit for readability
- Laravel plugin provides helpful assertions
- Fast test execution

**JavaScript Testing: Vitest**
```bash
npm install vitest jsdom --save-dev
```
- Fast, Vite-native testing
- Compatible with Jest APIs
- Coverage reporting with V8
- Perfect for vanilla JS testing

### Asset Pipeline
**Build Tool: Vite**
- Already included in Laravel 12
- Fast hot module replacement (HMR)
- Optimized production builds
- Tailwind CSS integration

### Development Tools
**Code Quality: Laravel Pint**
```bash
composer require laravel/pint --dev
```
- Automatic code formatting
- Laravel coding standards
- Zero configuration

**Debugging: Laravel Pail**
```bash
composer require laravel/pail --dev
```
- Real-time log viewer
- Color-coded output
- Filter and search logs

**Process Management: Concurrently**
```bash
npm install concurrently --save-dev
```
- Run multiple dev processes at once
- Single command to start everything
- Clean color-coded output

---

## Git Repository Setup

### Initial Repository

```bash
# Initialize git
git init

# Default .gitignore is already good, but verify these are excluded:
cat .gitignore | grep -E "(vendor|node_modules|.env|database.sqlite|public/storage)"

# Add deployment-specific exclusions
cat >> .gitignore << 'EOF'

# Deployment configuration
.deploy-config
.deploy-build/

# Coverage reports
/reports/
/coverage/

# Photo uploads or user content
/storage/app/public/photos/
EOF

# Initial commit
git add .
git commit -m "Initial Laravel 12 project setup"
```

### Recommended Branch Strategy
```bash
# For small projects, simple workflow:
# - main: Production-ready code
# - feature branches: Short-lived, merge via PR

# Create GitHub repository
gh repo create your-project-name --private --source=. --remote=origin

# Push to GitHub
git push -u origin main
```

### Important Files to Track
✅ **Do commit:**
- All application code
- .env.example (template)
- composer.json, composer.lock
- package.json, package-lock.json
- phpunit.xml, vitest.config.js
- deploy.sh (deployment script)
- server-files/ (Dreamhost-specific files)
- tests/ (all test files)

❌ **Don't commit:**
- .env (contains secrets)
- vendor/ (Composer dependencies)
- node_modules/ (npm dependencies)
- database/database.sqlite (local data)
- storage/logs/ (log files)
- public/storage (symlink)
- User-uploaded content

---

## Automated Testing Setup

### Pest PHP Configuration

**File: `phpunit.xml`** (already included in Laravel 12)

Ensure these test environment settings:
```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_STORE" value="array"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
</php>
```

**File: `tests/Pest.php`**
```php
<?php

pest()->extend(Tests\TestCase::class)
    ->in('Feature');

// Add helpful custom expectations
expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

// Global test helper functions
function actingAsAdmin()
{
    return test()->actingAs(
        \App\Models\User::factory()->create(['is_admin' => true])
    );
}
```

### Vitest Configuration

**File: `vitest.config.js`**
```javascript
import { defineConfig } from 'vitest/config';
import { resolve } from 'path';

export default defineConfig({
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./tests/js/setup.js'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      reportsDirectory: './reports/coverage/js',
      include: ['resources/js/**/*.js'],
      exclude: [
        'node_modules/',
        'tests/',
        'vendor/',
      ],
      thresholds: {
        global: {
          branches: 50,
          functions: 50,
          lines: 50,
          statements: 50
        }
      }
    }
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, './resources/js'),
    }
  }
});
```

**File: `tests/js/setup.js`**
```javascript
import { vi } from 'vitest';

// Mock window.Laravel object that Laravel provides
global.Laravel = {
    csrfToken: 'test-csrf-token',
};

// Mock any global browser APIs your code uses
global.fetch = vi.fn();
```

### Composer Test Scripts

**File: `composer.json`** (add to `scripts` section)
```json
{
    "scripts": {
        "test": [
            "@php artisan config:clear --ansi",
            "./vendor/bin/pest"
        ],
        "test:coverage": [
            "@php artisan config:clear --ansi",
            "mkdir -p reports/coverage",
            "./vendor/bin/pest --coverage --coverage-html=reports/coverage/html --coverage-clover=reports/coverage/clover.xml"
        ],
        "test:coverage-text": [
            "@php artisan config:clear --ansi",
            "./vendor/bin/pest --coverage --coverage-text"
        ],
        "coverage-full": [
            "@test:coverage",
            "npm run test:coverage"
        ]
    }
}
```

### NPM Test Scripts

**File: `package.json`** (add to `scripts` section)
```json
{
    "scripts": {
        "test": "vitest run",
        "test:watch": "vitest",
        "test:coverage": "vitest run --coverage",
        "test:ui": "vitest --ui"
    }
}
```

### Writing Your First Tests

**Feature Test Example: `tests/Feature/HomePageTest.php`**
```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('home page loads successfully', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Welcome');
});

test('authenticated users can access dashboard', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
});
```

**JavaScript Test Example: `tests/js/example.test.js`**
```javascript
import { describe, it, expect, beforeEach } from 'vitest';

describe('Example Module', () => {
    it('should perform basic operation', () => {
        expect(1 + 1).toBe(2);
    });
});
```

### Running Tests

```bash
# PHP tests
composer test                    # Run all tests
composer test:coverage          # With HTML coverage report
composer test:coverage-text     # With terminal coverage output

# JavaScript tests
npm test                        # Run all JS tests
npm run test:watch             # Watch mode
npm run test:coverage          # With coverage

# Both PHP and JS tests with coverage
composer run coverage-full
```

### Test Coverage Reports

After running coverage:
- **PHP**: Open `reports/coverage/html/index.html` in browser
- **JavaScript**: Open `reports/coverage/js/index.html` in browser

**Minimum Coverage Goals:**
- Feature tests: Cover all critical user flows
- Unit tests: Cover complex business logic
- Aim for >70% coverage on new projects

---

## Development Environment

### Unified Development Command

**File: `composer.json`** (add to `scripts` section)
```json
{
    "scripts": {
        "dev": [
            "Composer\\Config::disableProcessTimeout",
            "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite --kill-others"
        ]
    }
}
```

This single command runs:
1. **Laravel dev server** (http://localhost:8000)
2. **Queue worker** (if you decide to use queues later)
3. **Log viewer** (Pail - real-time logs)
4. **Vite dev server** (with HMR for CSS/JS)

### Starting Development

```bash
# Single command to start everything
composer run dev

# You'll see color-coded output from all services:
# - Blue: Laravel server
# - Purple: Queue worker
# - Pink: Logs
# - Orange: Vite

# Press Ctrl+C to stop everything
```

### Vite Configuration

**File: `vite.config.js`**
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
    },
});
```

### Tailwind CSS Setup

**File: `resources/css/app.css`**
```css
@import "tailwindcss";
```

That's it! Tailwind 4.x uses a single import with Vite.

---

## Dreamhost Deployment Preparation

### Understanding Dreamhost Directory Structure

**Typical Dreamhost shared hosting structure:**
```
/home/username/
├── your-domain.com/           # Public web root (DocumentRoot)
│   └── index.php              # Custom bootstrap (see below)
├── family-photo-album/        # Laravel application (private)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   └── vendor/
└── server-files/              # Deployment staging area
```

**Key Insight:** The Laravel app lives OUTSIDE the web root for security. Only a custom `index.php` is in the public directory.

### Custom index.php for Dreamhost

**File: `server-files/index.php`**

Create this file in your local project:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Dreamhost shared hosting paths - points to family-photo-album directory
if (file_exists($maintenance = __DIR__.'/../family-photo-album/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../family-photo-album/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../family-photo-album/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

**Adjust the path `family-photo-album` to match your project name!**

### Deployment Script

**File: `deploy.sh`**

Create a smart deployment script that handles Dreamhost's limitations:

```bash
#!/bin/bash
set -e

# Dreamhost deployment script
# Run from within your project directory
#
# USAGE:
#   ./deploy.sh              - Smart deployment (excludes vendor/, ~500KB)
#   ./deploy.sh --full       - Full deployment (includes vendor/, ~9MB)
#   ./deploy.sh --vendor-only - Upload only vendor dependencies (~6MB)
#
# WORKFLOW:
#   First deployment:        ./deploy.sh --full
#   Regular updates:         ./deploy.sh
#   After composer changes:  ./deploy.sh --vendor-only && ./deploy.sh

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

CONFIG_FILE=".deploy-config"
BUILD_DIR=".deploy-build"

# Parse command line arguments
FULL_DEPLOY=false
VENDOR_ONLY=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --full)
            FULL_DEPLOY=true
            shift
            ;;
        --vendor-only)
            VENDOR_ONLY=true
            shift
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            echo "Usage: $0 [--full] [--vendor-only]"
            exit 1
            ;;
    esac
done

if [ "$VENDOR_ONLY" = true ]; then
    echo -e "${YELLOW}📦 Starting vendor-only upload...${NC}"
else
    echo -e "${YELLOW}🚀 Starting Dreamhost deployment...${NC}"
fi

# Check if we're in the right directory
if [ ! -f "composer.json" ] || [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Please run this script from your Laravel project directory${NC}"
    exit 1
fi

# Load or prompt for configuration
if [ -f "$CONFIG_FILE" ]; then
    echo -e "${BLUE}📋 Loading deployment configuration...${NC}"
    source "$CONFIG_FILE"
    echo -e "${GREEN}✅ Using server: $DEPLOY_USERNAME@$DEPLOY_HOST${NC}"
else
    echo -e "${YELLOW}⚙️  First-time setup: Please enter your deployment details${NC}"

    read -p "Dreamhost username: " DEPLOY_USERNAME
    read -p "Dreamhost hostname (e.g., yoursite.com): " DEPLOY_HOST
    read -p "Project directory name (e.g., your-project-name): " PROJECT_DIR

    # Save configuration
    cat > "$CONFIG_FILE" << EOF
# Dreamhost deployment configuration
DEPLOY_USERNAME="$DEPLOY_USERNAME"
DEPLOY_HOST="$DEPLOY_HOST"
PROJECT_DIR="$PROJECT_DIR"
EOF

    echo -e "${GREEN}✅ Configuration saved to $CONFIG_FILE${NC}"
fi

# Skip build steps for vendor-only uploads
if [ "$VENDOR_ONLY" = true ]; then
    echo -e "${YELLOW}🧱 Preparing vendor-only build...${NC}"
    rm -rf "$BUILD_DIR"
    mkdir -p "$BUILD_DIR"

    cp composer.json composer.lock "$BUILD_DIR/"

    echo -e "${YELLOW}📦 Installing Composer production dependencies...${NC}"
    composer install --no-dev --optimize-autoloader --working-dir="$BUILD_DIR"

    echo -e "${YELLOW}📤 Uploading vendor dependencies...${NC}"
    rsync -avz \
        --checksum \
        --delete \
        "$BUILD_DIR/vendor/" "$DEPLOY_USERNAME@$DEPLOY_HOST:~/$PROJECT_DIR/vendor/"

    echo -e "${GREEN}✅ Vendor dependencies updated!${NC}"
    rm -rf "$BUILD_DIR"
    exit 0
fi

# Build assets
echo -e "${YELLOW}🏗️  Building assets...${NC}"
npm run build

echo -e "${YELLOW}🧱 Preparing isolated build directory...${NC}"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

# Copy project into build dir excluding local-only artifacts
rsync -avz \
    --exclude='.git/' \
    --exclude='.env' \
    --exclude='.deploy-config' \
    --exclude='node_modules/' \
    --exclude='.deploy-build/' \
    --exclude='database/database.sqlite' \
    --exclude='storage/app/public/photos/' \
    --exclude='storage/framework/views/*.php' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/logs/*.log' \
    --exclude='public/storage' \
    --exclude='tests/' \
    --exclude='.DS_Store' \
    ./ "$BUILD_DIR/"

if [ "$FULL_DEPLOY" = true ]; then
    echo -e "${YELLOW}📦 Installing Composer production dependencies...${NC}"
    composer install --no-dev --optimize-autoloader --working-dir="$BUILD_DIR"
fi

# Deploy to server
if [ "$FULL_DEPLOY" = true ]; then
    echo -e "${YELLOW}📤 Full upload...${NC}"
    rsync -avz \
        --exclude='.git/' \
        --exclude='.env' \
        "$BUILD_DIR/" "$DEPLOY_USERNAME@$DEPLOY_HOST:~/temp-upload/"
else
    echo -e "${YELLOW}📤 Smart upload (excluding vendor/)...${NC}"
    rsync -avz \
        --checksum \
        --exclude='.git/' \
        --exclude='.env' \
        --exclude='vendor/' \
        "$BUILD_DIR/" "$DEPLOY_USERNAME@$DEPLOY_HOST:~/temp-upload/"
fi

# Upload server-specific files
echo -e "${YELLOW}📤 Uploading server-specific files...${NC}"
rsync -avz server-files/ "$DEPLOY_USERNAME@$DEPLOY_HOST:~/server-files/"

echo -e "${GREEN}✅ Upload complete!${NC}"
rm -rf "$BUILD_DIR"

echo -e "${YELLOW}📋 Next steps:${NC}"
echo -e "   1. SSH into your server: ${BLUE}ssh $DEPLOY_USERNAME@$DEPLOY_HOST${NC}"
echo -e "   2. Run the update script: ${BLUE}~/update-project.sh${NC}"
echo ""
echo -e "${GREEN}🎉 Deployment ready!${NC}"
```

Make it executable:
```bash
chmod +x deploy.sh
```

### Server Update Script

Create a helper script to run on the Dreamhost server.

**On the server: `~/update-project.sh`**

SSH into your Dreamhost server and create this file:

```bash
#!/bin/bash
set -e

PROJECT_DIR="your-project-name"  # Change this!
DOMAIN_DIR="your-domain.com"     # Change this!

echo "🔄 Updating $PROJECT_DIR..."

# Backup current version
if [ -d "$PROJECT_DIR" ]; then
    echo "📦 Creating backup..."
    cp -r "$PROJECT_DIR" "${PROJECT_DIR}.backup.$(date +%Y%m%d_%H%M%S)"
fi

# Copy uploaded files to project directory
echo "📁 Syncing files..."
rsync -avz --delete \
    --exclude='storage/app/public/photos/' \
    --exclude='storage/logs/' \
    --exclude='.env' \
    ~/temp-upload/ ~/$PROJECT_DIR/

# Copy server-specific index.php to web root
echo "🔗 Updating web root..."
cp ~/server-files/index.php ~/$DOMAIN_DIR/

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 755 ~/$PROJECT_DIR/storage
chmod -R 755 ~/$PROJECT_DIR/bootstrap/cache

# Clear caches
echo "🧹 Clearing caches..."
cd ~/$PROJECT_DIR
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations (prompt first)
read -p "Run database migrations? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
fi

# Optimize for production
echo "⚡ Optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Deployment complete!"
echo "🌐 Visit your site to verify"
```

Make it executable:
```bash
chmod +x ~/update-project.sh
```

### Production .env Configuration

SSH into your Dreamhost server and create/edit `~/your-project-name/.env`:

```env
APP_NAME="Your Application"
APP_ENV=production
APP_KEY=base64:GENERATE_WITH_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database - MySQL provided by Dreamhost
DB_CONNECTION=mysql
DB_HOST=mysql.your-domain.com
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Session configuration for shared hosting
SESSION_DRIVER=database
SESSION_LIFETIME=525600
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true

# Cache
CACHE_STORE=database

# Queue - avoid queues on shared hosting
QUEUE_CONNECTION=sync

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error

# Mail configuration (set up through Dreamhost)
MAIL_MAILER=smtp
MAIL_HOST=smtp.dreamhost.com
MAIL_PORT=465
MAIL_USERNAME=noreply@your-domain.com
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Security
TRUSTED_PROXIES="*"

# Rate limiting (adjust as needed)
FEED_PER_MINUTE=60
SHOW_PER_MINUTE=120
DOWNLOADS_PER_MINUTE=30
```

### First Deployment Workflow

```bash
# On your local machine:

# 1. Build and deploy full application
./deploy.sh --full

# 2. SSH into Dreamhost
ssh username@your-domain.com

# 3. Set up .env file (see above)
nano ~/your-project-name/.env

# 4. Generate application key
cd ~/your-project-name
php artisan key:generate

# 5. Create database (through Dreamhost panel first!)
# Then run migrations:
php artisan migrate --force

# 6. Link storage
php artisan storage:link

# 7. Set up the update script
nano ~/update-project.sh
# (paste the content from above, customize PROJECT_DIR and DOMAIN_DIR)
chmod +x ~/update-project.sh

# 8. Run initial update
~/update-project.sh
```

### Subsequent Deployments

```bash
# For code changes (fast, ~500KB):
./deploy.sh
# Then SSH and run: ~/update-project.sh

# For Composer dependency changes:
./deploy.sh --vendor-only
./deploy.sh
# Then SSH and run: ~/update-project.sh

# For major updates:
./deploy.sh --full
# Then SSH and run: ~/update-project.sh
```

---

## Claude Code Workflow

### Critical First Step: Install and Configure Context7 MCP

**Why This Matters:**
In the family-photo-album project, Claude frequently hallucinated Laravel functionality based on outdated documentation. Context7 MCP provides access to current Laravel 12 documentation, preventing incorrect implementations.

**Installation:**

1. Install Context7 MCP server (follow Context7 documentation)
2. Configure in Claude Code settings
3. Verify it's working: Check that Context7 tools are available

**At Project Start, Claude Should:**

```
1. Resolve Laravel framework library ID
2. Fetch Laravel 12 documentation
3. Resolve Livewire library ID (if using)
4. Fetch current Livewire/Volt documentation
5. Resolve Pest library ID
6. Fetch Pest testing documentation
```

**Example Usage:**
```
Before implementing any Laravel feature, use:
mcp__context7__resolve-library-id with "laravel"
mcp__context7__get-library-docs with the resolved ID

This prevents hallucinating old Laravel patterns!
```

### Playwright MCP for Visual Testing

**Why Playwright MCP is Critical:**

Playwright MCP allows Claude to actually see and interact with the Laravel application in a real browser. This enables:
- **Visual verification** of UI changes against design mockups
- **Self-testing** without relying solely on unit/feature tests
- **Screenshot comparison** to catch visual regressions
- **Accessibility validation** through browser snapshots
- **Immediate feedback** on styling, layout, and responsiveness

**Installation:**

Playwright MCP is typically installed as part of Claude Code's MCP ecosystem. Verify it's available by checking for tools starting with `mcp__playwright__*`.

**Available Playwright Tools:**
- `mcp__playwright__browser_navigate` - Navigate to URLs
- `mcp__playwright__browser_snapshot` - Capture accessibility tree (preferred)
- `mcp__playwright__browser_take_screenshot` - Visual screenshots
- `mcp__playwright__browser_resize` - Test responsive layouts
- `mcp__playwright__browser_click` - Interact with elements
- `mcp__playwright__browser_type` - Fill forms
- And many more for comprehensive browser automation

### When Claude Should Use Playwright

**Proactively use Playwright (without asking) for:**

1. **After UI/Frontend Changes**
   - Implemented new views or components
   - Modified CSS/Tailwind classes
   - Changed layouts or navigation structure
   - Added or modified forms

2. **Design Verification**
   - User provides design mockups (PNG, Figma, etc.)
   - Need to verify implementation matches designs
   - Checking responsive behavior (mobile/desktop)

3. **Visual Regression Testing**
   - Modifying existing UI components
   - Refactoring CSS or layout structure
   - Updating styling libraries or frameworks

4. **Integration Validation**
   - After installing frontend libraries (icons, components)
   - Verifying JavaScript/Alpine.js interactions work
   - Testing Livewire component rendering

**Ask before using Playwright for:**
- Interactive testing that requires user credentials
- Testing features that modify data (payments, emails, etc.)
- Load testing or performance testing

### Playwright Workflow for Laravel Apps

**Standard Workflow:**

```markdown
1. Make code changes (views, CSS, controllers)
2. Ensure dev server is running (composer run dev or php artisan serve)
3. Navigate to the application
   - Local dev: http://127.0.0.1:8000 or http://localhost:8000
   - Laravel Herd: http://project-name.test
4. Take screenshots for verification
5. If design mockups exist, read and compare
6. Document findings or iterate on code
```

**Example Pattern:**

```markdown
After implementing navigation layout changes:

1. Navigate to home page
   mcp__playwright__browser_navigate(url: "http://127.0.0.1:8000")

2. Take desktop screenshot
   mcp__playwright__browser_take_screenshot(filename: "nav-desktop.png")

3. Resize to mobile viewport
   mcp__playwright__browser_resize(width: 375, height: 667)

4. Take mobile screenshot
   mcp__playwright__browser_take_screenshot(filename: "nav-mobile.png")

5. Compare with design mockups
   Read(file_path: "design/home-desktop.png")
   Read(file_path: "design/home-mobile.png")

6. Document any discrepancies or confirm match
```

### Common Playwright Patterns

**1. Testing Responsive Layouts**
```markdown
# Desktop view
navigate(http://127.0.0.1:8000)
take_screenshot("desktop.png")

# Tablet view
resize(768, 1024)
take_screenshot("tablet.png")

# Mobile view
resize(375, 667)
take_screenshot("mobile.png")
```

**2. Verifying Against Design Mockups**
```markdown
# Take current screenshot
navigate(http://127.0.0.1:8000/pizzerias)
take_screenshot("current-implementation.png")

# Read design mockup
Read("design/pizzerias-page.png")

# Compare visually and document findings:
- ✅ Logo and branding match
- ✅ Search box in correct location
- ⚠️  Filter bar needs gray background (fix needed)
- ✅ Card layout matches design
```

**3. Testing User Flows**
```markdown
# Navigate to upload form
navigate(http://127.0.0.1:8000/photos/upload)
snapshot()  # Capture form structure

# Fill and submit form
type(element: "photo input", text: "/path/to/test/image.jpg")
type(element: "caption", text: "Test caption")
click(element: "submit button")

# Verify success page
take_screenshot("upload-success.png")
```

**4. Checking Interactive Elements**
```markdown
# Test dropdown menus
click(element: "rating filter dropdown")
take_screenshot("filter-dropdown-open.png")

# Test modal dialogs
click(element: "delete button")
take_screenshot("delete-confirmation.png")

# Test form validation
click(element: "submit")  # Without filling form
take_screenshot("validation-errors.png")
```

### Integration with Validation Workflow

Add Playwright checks to the validation process:

**For UI/Frontend Changes:**
```markdown
1. Run automated tests (npm test, composer test)
2. Start dev server (php artisan serve or composer run dev)
3. Use Playwright to:
   - Navigate to affected pages
   - Take screenshots of changes
   - Test responsive behavior (mobile/desktop)
   - Verify against design mockups if available
4. Document findings
5. Only mark complete if:
   - Automated tests pass
   - Visual verification confirms correct implementation
   - No console errors in browser
   - Matches design specifications
```

### Best Practices

**Do:**
- ✅ Always test responsive layouts (mobile + desktop minimum)
- ✅ Compare screenshots with design mockups when provided
- ✅ Use `browser_snapshot()` for accessibility tree (faster than screenshots)
- ✅ Save screenshots with descriptive names
- ✅ Document what you verified in plain language
- ✅ Test user flows end-to-end when making significant changes

**Don't:**
- ❌ Skip visual verification for "simple" CSS changes
- ❌ Assume implementation matches design without checking
- ❌ Only test desktop view (mobile-first remember!)
- ❌ Test against production sites without permission
- ❌ Leave browser sessions open (close after testing)

### Real-World Example from PizzaJLM Project

**Scenario:** User requested navigation layout refinement to match design mockups.

**What Claude Did:**
1. Modified layout files (public.blade.php, index.blade.php)
2. Rebuilt assets (npm run build)
3. **Used Playwright to verify:**
   ```
   - Navigate to http://127.0.0.1:8000
   - Screenshot desktop layout → saved as navigation-desktop.png
   - Resize to mobile (375x667)
   - Screenshot mobile layout → saved as navigation-mobile.png
   - Read design/home-desktop.png and design/home-mobile.png
   - Compared implementations side-by-side
   - Confirmed perfect match ✅
   ```
4. Reported findings: "Desktop layout matches design perfectly. Mobile layout matches design perfectly."

**Result:** User had confidence the work was correct before even looking, because Claude verified it visually against the source designs.

### When to Document Findings

After Playwright testing, Claude should:

1. **Briefly state what was verified:**
   - "Verified navigation layout in desktop and mobile views"
   - "Tested responsive behavior from 375px to 1920px"
   - "Compared implementation against design mockups"

2. **Report match status:**
   - "✅ Implementation matches design perfectly"
   - "⚠️ Minor differences: filter background needs adjustment"
   - "❌ Significant discrepancies found, iterating on solution"

3. **Save screenshots strategically:**
   - Before/after comparisons
   - Different viewport sizes
   - Different states (hover, active, error)

4. **Only mark work complete when visual verification passes**

### Playwright in Allowed Tools

Since Playwright testing is non-destructive and essential for validation, configure it as auto-allowed:

```json
{
  "permissions": {
    "allow": [
      "mcp__playwright__browser_navigate",
      "mcp__playwright__browser_snapshot",
      "mcp__playwright__browser_take_screenshot",
      "mcp__playwright__browser_resize",
      "mcp__playwright__browser_click"
    ]
  }
}
```

This allows Claude to self-verify work without interrupting flow.

### When to Plan Before Coding

Claude should enter "plan mode" and present a plan for approval when:

1. **Multi-file features** spanning 3+ files
   - Example: Adding a new photo sharing feature that touches models, controllers, views, and routes

2. **Database schema changes**
   - Migrations affecting existing data
   - Complex relationships between models
   - Data migrations or transformations

3. **New architectural patterns**
   - Introducing middleware
   - Adding new service layers
   - Major refactoring

4. **Exploratory questions**
   - User asks "how would you..."
   - User asks "what's the best way to..."
   - Indicates planning is needed before implementation

**Planning Template:**
```markdown
I'll help implement [feature]. Let me plan this out:

## Approach
[High-level strategy]

## Files to Create/Modify
1. app/Models/Example.php - [purpose]
2. app/Http/Controllers/ExampleController.php - [purpose]
3. resources/views/example.blade.php - [purpose]
4. routes/web.php - [add routes]
5. database/migrations/xxx_create_examples_table.php - [schema]

## Steps
1. [First step]
2. [Second step]
3. [Third step]

## Testing Plan
- Feature test: [what to test]
- Unit test: [what to test]

Ready to proceed? [YES/NO]
```

### When to Code Immediately

Claude can proceed directly to implementation when:
- Single-file changes
- Simple bug fixes
- Adding new routes to existing controllers
- View/template updates
- CSS/styling changes
- Simple helper functions

### Validation Before Marking Complete

**Claude must validate work before marking any task as complete:**

1. **For Backend Changes:**
   ```bash
   composer test
   ```
   - All tests must pass
   - Check for new deprecation warnings

2. **For Frontend Changes:**
   ```bash
   npm test
   ```
   - All JS tests must pass
   - Manually test in browser if UI changes

3. **For New Features:**
   ```bash
   composer run test:coverage-text
   ```
   - Ensure adequate test coverage
   - Add tests if coverage drops

4. **For Database Changes:**
   ```bash
   php artisan migrate:fresh
   php artisan migrate
   ```
   - Verify migrations run cleanly
   - Check rollback works

**Validation Checklist:**
```markdown
Before marking complete:
- [ ] All tests pass (composer test)
- [ ] No new errors in browser console (if frontend)
- [ ] Feature works as expected (manual test)
- [ ] Code follows Laravel conventions
- [ ] Tests added for new functionality
- [ ] No var_dump() or dd() left in code
```

### The "Simple First" Principle

**Always start with the simplest solution that could work.**

1. **Prove the feature works simply**
2. **Get user feedback**
3. **Only then add complexity if needed**

**Examples:**

❌ **Don't do this first:**
```php
// Overly complex, premature optimization
public function store(Request $request)
{
    $validated = $request->validate(...);

    DB::transaction(function() use ($validated) {
        $photo = Photo::create($validated);
        event(new PhotoCreated($photo));
        Cache::tags('photos')->flush();
        $this->notificationService->notify($photo);
    });

    return response()->json(['success' => true], 201);
}
```

✅ **Do this first:**
```php
// Simple, proves it works
public function store(Request $request)
{
    $validated = $request->validate([
        'image' => 'required|image|max:10240',
        'caption' => 'nullable|string|max:500',
    ]);

    $photo = Photo::create($validated);

    return redirect()->route('photos.show', $photo);
}
```

**Then**, if needed, add transactions, events, caching, etc.

### Working with Tests

**Test-Driven Development Flow:**

1. **Write the test first** (or alongside the code)
2. **Run the test** - it should fail
3. **Implement the feature**
4. **Run the test again** - it should pass
5. **Refactor if needed**

**Example TDD Flow:**
```bash
# 1. Write test in tests/Feature/PhotoUploadTest.php
# 2. Run test
composer test -- --filter PhotoUpload

# 3. Implement feature in app/Http/Controllers/PhotoController.php
# 4. Run test again
composer test -- --filter PhotoUpload

# 5. All tests pass? Run full suite
composer test
```

### Common Workflow Commands

```bash
# Start development
composer run dev

# Run tests after changes
composer test

# Run specific test
composer test -- --filter TestName

# Check test coverage
composer run test:coverage-text

# Format code
./vendor/bin/pint

# Clear caches during dev
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check routes
php artisan route:list

# Database inspection
php artisan tinker
>>> User::count()
>>> Photo::latest()->first()
```

---

## Context7 MCP Documentation

### Why Context7 is Critical

**Problem:** Claude's training data includes older Laravel versions. Without current documentation, Claude will:
- Suggest deprecated syntax
- Use removed features
- Miss new Laravel 12 capabilities
- Recommend outdated patterns

**Solution:** Context7 MCP provides real-time access to current library documentation.

### Setup Verification

**In Claude Code, verify Context7 is available:**

```
You should see these tools:
- mcp__context7__resolve-library-id
- mcp__context7__get-library-docs
```

If not available, install the Context7 MCP server.

### Project Initialization Pattern

**At the start of every Laravel project, Claude should:**

```markdown
1. Fetch Laravel Documentation
   - resolve-library-id: "laravel"
   - get-library-docs: use returned library ID
   - Topic: "getting started, routing, eloquent, validation"

2. Fetch Livewire Documentation (if using)
   - resolve-library-id: "livewire"
   - get-library-docs: use returned library ID
   - Topic: "components, forms, volt"

3. Fetch Pest Documentation
   - resolve-library-id: "pest php"
   - get-library-docs: use returned library ID
   - Topic: "writing tests, expectations, laravel plugin"

4. Fetch Tailwind CSS Documentation
   - resolve-library-id: "tailwindcss"
   - get-library-docs: use returned library ID
   - Topic: "installation, configuration, utility classes"
```

### Usage Patterns

**Before implementing any feature:**
```
If I'm not sure about current Laravel syntax:
1. Use resolve-library-id to find the library
2. Use get-library-docs with specific topic
3. Implement using current documentation
```

**Common Topics to Query:**

**Laravel:**
- "authentication and authorization"
- "eloquent relationships"
- "validation rules"
- "file uploads and storage"
- "middleware"
- "database migrations"
- "queues and jobs" (even though we avoid them)

**Livewire:**
- "component lifecycle"
- "form validation"
- "file uploads"
- "volt sfc syntax"

**Pest:**
- "feature testing"
- "database testing"
- "http testing"
- "expectations api"

### Example Workflow

```
User: "Add authentication to the app"

Claude's Process:
1. Resolve Laravel library ID
2. Get docs with topic "authentication, sanctum, breeze"
3. Review current Laravel 12 auth patterns
4. Implement using current best practices
5. Run tests to validate
```

### When to Consult Context7

**Always consult before:**
- Using Laravel features you're not 100% certain about
- Implementing authentication/authorization
- Working with Eloquent relationships
- Using validation rules
- File upload handling
- Any "I think this is how it works..." moment

**You don't need to consult for:**
- Basic PHP syntax
- Blade templating basics
- Standard HTML/CSS
- Common JavaScript patterns

---

## Allowed Tools and Permissions

### The Permission Problem

By default, Claude Code asks for permission for many operations. This interrupts flow and slows development. For Laravel projects, we can pre-approve common safe operations.

### Recommended Allowed Tools Configuration

**In Claude Code settings or project configuration, allow these tools/patterns:**

#### PHP/Laravel Commands
```
✅ Allow:
- php artisan * (all artisan commands except destructive ones)
- composer test
- composer test:*
- ./vendor/bin/pest
- ./vendor/bin/pest --filter *
- ./vendor/bin/pint
- composer install
- composer update (with confirmation)
- composer require *
- composer remove *
```

#### Testing Commands
```
✅ Allow:
- composer test
- composer test:coverage
- composer test:coverage-text
- composer run coverage-full
- npm test
- npm run test:*
- ./vendor/bin/pest
- ./vendor/bin/pest --coverage*
```

#### Build Commands
```
✅ Allow:
- npm run build
- npm run build:*
- npm run dev (if needed)
- npm install
- npm install --save*
```

#### Documentation and Research
```
✅ Allow:
- WebSearch (for looking up solutions)
- WebFetch (for documentation)
- mcp__context7__* (all Context7 MCP tools)
```

#### File Operations
```
✅ Allow without asking:
- Read (all files)
- Edit (application files, not system files)
- Write (in project directory)
- Glob (file searching)
- Grep (code searching)
```

### Example .claude/settings.json

Create this file in your project to configure allowed operations:

```json
{
  "allowedTools": {
    "bash": {
      "patterns": [
        "php artisan *",
        "composer test*",
        "composer run test*",
        "composer run coverage*",
        "./vendor/bin/pest*",
        "./vendor/bin/pint",
        "npm run build*",
        "npm test*",
        "npm install*",
        "composer install",
        "composer require *"
      ],
      "requireConfirmation": [
        "composer update",
        "php artisan migrate:fresh",
        "php artisan db:wipe",
        "rm -rf *"
      ]
    },
    "webAccess": {
      "allow": true,
      "domains": [
        "laravel.com",
        "livewire.laravel.com",
        "pestphp.com",
        "tailwindcss.com"
      ]
    },
    "mcp": {
      "context7": {
        "allow": true
      }
    }
  }
}
```

### Safe vs. Destructive Operations

**Safe (Auto-allow):**
- Running tests
- Building assets
- Installing dependencies
- Reading/editing application files
- Running migrations (with --pretend first)
- Clearing caches
- Formatting code

**Destructive (Ask First):**
- `composer update` (changes lock file)
- `php artisan migrate:fresh` (drops all tables)
- `php artisan db:wipe` (drops all tables)
- `rm -rf` commands
- `git push --force`
- `php artisan key:generate` (in production)

### Benefits of Proper Configuration

1. **Faster Development**: Claude doesn't pause for permission
2. **Better Testing**: Tests run automatically after changes
3. **Confidence**: Auto-testing catches regressions immediately
4. **Documentation**: Context7 queries happen seamlessly

---

## Production Best Practices

### Lessons from Family Photo Album

These patterns emerged from running a Laravel app successfully on Dreamhost shared hosting:

#### 1. Avoid Queues on Shared Hosting

**Problem:** Background queue workers are difficult to maintain on shared hosting.

**Solution:** Design features to not need background processing.
- Process uploads synchronously
- Use efficient database queries instead of deferred jobs
- If something takes too long, optimize it rather than queue it

**Example:**
```php
// ❌ Don't do this on shared hosting
dispatch(new ProcessPhoto($photo));

// ✅ Do this instead
$photo->process(); // Synchronous, but optimized
```

#### 2. Use MySQL in Production, SQLite in Development

**Why:**
- SQLite is perfect for development (zero config)
- MySQL handles production traffic better
- Dreamhost provides MySQL included in hosting

**Configuration:**
```env
# .env (local)
DB_CONNECTION=sqlite

# .env (production)
DB_CONNECTION=mysql
DB_HOST=mysql.yourdomain.com
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

**Testing:** Always use in-memory SQLite for tests:
```xml
<!-- phpunit.xml -->
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

#### 3. Database-Backed Sessions and Cache

**For shared hosting without Redis:**
```env
SESSION_DRIVER=database
CACHE_STORE=database
```

**Run migrations for these:**
```bash
php artisan session:table
php artisan cache:table
php artisan queue:table  # Even if not using queues, for future
php artisan migrate
```

#### 4. Optimize Asset Delivery

**Use Vite's build optimization:**
```bash
npm run build
```

This creates:
- Minified CSS/JS
- Versioned filenames (cache busting)
- Optimized images

**In production, ensure .env has:**
```env
APP_ENV=production
APP_DEBUG=false
```

This enables Laravel's caching and optimization.

#### 5. Rate Limiting

**Protect against abuse:**

```php
// routes/web.php
Route::get('/feed', [PhotoController::class, 'feed'])
    ->middleware('throttle:feed');

// app/Providers/AppServiceProvider.php
RateLimiter::for('feed', function (Request $request) {
    return Limit::perMinute(env('FEED_PER_MINUTE', 60));
});
```

**.env:**
```env
FEED_PER_MINUTE=60
SHOW_PER_MINUTE=120
DOWNLOADS_PER_MINUTE=30
```

#### 6. Security Headers Middleware

**Create middleware for production security:**

```php
// app/Http/Middleware/SecurityHeadersMiddleware.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (app()->environment('production')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

            // Strict CSP
            $response->headers->set('Content-Security-Policy',
                "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
                "style-src 'self' 'unsafe-inline'; " .
                "img-src 'self' data:; " .
                "font-src 'self' data:; " .
                "connect-src 'self'"
            );

            // HSTS for HTTPS
            if ($request->secure()) {
                $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
        }

        return $response;
    }
}
```

**Register in `bootstrap/app.php`:**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\SecurityHeadersMiddleware::class,
    ]);
})
```

#### 7. Trust Proxies (Critical for Dreamhost)

**Dreamhost uses proxies. Laravel needs to trust them:**

**.env:**
```env
TRUSTED_PROXIES="*"
```

**This fixes:**
- HTTPS detection
- Correct IP addresses in logs
- Session cookie security

#### 8. Long Session Lifetime for Family Apps

**For apps where users expect to stay logged in:**
```env
SESSION_LIFETIME=525600  # 1 year in minutes
```

**This is appropriate for:**
- Family/private applications
- Apps with low security risk
- Convenience over security

**Don't do this for:**
- Public applications
- Apps handling sensitive data
- E-commerce or financial apps

#### 9. Smart Deployment Strategy

**Three deployment modes save time and bandwidth:**

1. **Smart Deploy** (`./deploy.sh`) - ~500KB
   - For code changes
   - Excludes vendor/
   - Fast uploads

2. **Vendor-Only** (`./deploy.sh --vendor-only`) - ~6MB
   - After composer.json changes
   - Only updates dependencies

3. **Full Deploy** (`./deploy.sh --full`) - ~9MB
   - First deployment
   - Major updates

**Workflow:**
```bash
# Regular development:
./deploy.sh && ssh user@host "~/update-project.sh"

# After adding a package:
./deploy.sh --vendor-only
./deploy.sh
ssh user@host "~/update-project.sh"
```

#### 10. Production Optimization Commands

**On every deployment, run:**
```bash
# Clear old caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Build new caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Include this in your `~/update-project.sh` script!**

#### 11. Backup Strategy

**Simple backup approach:**
```bash
# In update-project.sh, before deployment:
cp -r "$PROJECT_DIR" "${PROJECT_DIR}.backup.$(date +%Y%m%d_%H%M%S)"
```

**Keep last 5 backups:**
```bash
# Clean old backups (keep last 5)
ls -t ~/*.backup.* | tail -n +6 | xargs rm -rf
```

**Database backups:**
```bash
# Via Dreamhost panel or:
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

#### 12. Error Monitoring

**In production, log to files:**
```env
LOG_CHANNEL=stack
LOG_LEVEL=error
```

**Check logs regularly:**
```bash
ssh user@host
tail -f ~/your-project/storage/logs/laravel.log
```

**Or set up email notifications:**
```php
// app/Exceptions/Handler.php
use Illuminate\Support\Facades\Mail;

public function register()
{
    $this->reportable(function (Throwable $e) {
        if (app()->environment('production')) {
            // Send email notification for errors
            Mail::raw($e->getMessage(), function ($message) {
                $message->to('admin@yourdomain.com')
                    ->subject('Application Error');
            });
        }
    });
}
```

---

## Laravel Development Patterns from Production Code

This section extracts concrete patterns from the family-photo-album codebase - actual code that's running successfully in production. These aren't theoretical best practices; they're proven solutions.

### Controller Organization

#### 1. Focused Controller Responsibility

**Pattern:** Separate controllers for different concerns
```php
// Good: PhotoController handles viewing/managing existing photos
class PhotoController extends Controller
{
    public function show(Photo $photo) { }
    public function edit(Photo $photo) { }
    public function update(Request $request, Photo $photo) { }
    public function destroy(Photo $photo) { }
}

// PhotoUploadController handles the multi-stage upload flow
class PhotoUploadController extends Controller
{
    public function showUploadForm() { }
    public function handleUpload(Request $request) { }
    public function showCropForm(Photo $photo) { }
    public function handleCrop(Request $request, Photo $photo) { }
}
```

**Why:** Keeps controllers focused and testable. Upload flow is complex enough to deserve its own controller.

#### 2. Helper Methods for Authorization

**Pattern:** Extract repeated authorization logic into protected methods
```php
protected function authorizeOwner(Photo $photo): void
{
    abort_unless($photo->user_id === Auth::id(), 403);
}

// Usage in multiple methods:
public function showCropForm(Photo $photo): View
{
    $this->authorizeOwner($photo);
    return view('photos.crop', compact('photo'));
}
```

**Why:** DRY principle, consistent authorization, single place to update.

#### 3. Complex Queries with COALESCE

**Pattern:** Use raw SQL when Laravel's query builder becomes awkward
```php
// Fallback ordering: use taken_at if available, otherwise created_at
$photos = Photo::where('is_completed', true)
    ->orderByRaw('COALESCE(taken_at, created_at) DESC')
    ->paginate($perPage);

// Finding previous/next photos
$currentTs = ($photo->taken_at ?? $photo->created_at);
$previous = Photo::where('is_completed', true)
    ->whereRaw('COALESCE(taken_at, created_at) < ?', [$currentTs])
    ->orderByRaw('COALESCE(taken_at, created_at) DESC')
    ->first();
```

**Why:** Sometimes raw SQL is clearer than chained query builder methods.

#### 4. Comprehensive Error Handling for File Uploads

**Pattern:** Check for upload errors BEFORE validation, with helpful messages
```php
public function handleUpload(Request $request): RedirectResponse
{
    // Log attempt with full context
    logger()->info('Upload attempt started', [
        'user_id' => Auth::id(),
        'has_file' => $request->hasFile('photo'),
        'php_limits' => [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ],
    ]);

    // Detect post_max_size overflow (empty $_POST despite content)
    if ($request->isMethod('post')
        && (int)($request->server('CONTENT_LENGTH') ?? 0) > 0
        && empty($_POST)) {

        $limits = sprintf(
            'upload_max_filesize=%s, post_max_size=%s',
            ini_get('upload_max_filesize'),
            ini_get('post_max_size')
        );

        return back()->withErrors([
            'photo' => "Upload exceeded post_max_size. Current limits: {$limits}."
        ])->withInput();
    }

    // Then validate
    $validated = $request->validate([
        'photo' => 'required|file|mimes:jpeg,jpg,png,gif,heic|max:10240',
    ]);

    // Process upload...
}
```

**Why:** PHP's file upload errors are cryptic. Detect them early with helpful messages.

**Key insight:** `post_max_size` overflow signature is non-empty `CONTENT_LENGTH` but empty `$_POST`.

#### 5. API Endpoints with Bounds Checking

**Pattern:** Prevent abuse with configurable limits and bounds
```php
public function feed(Request $request): JsonResponse
{
    $defaultPerPage = 20;
    $maxPerPage = (int) env('FEED_MAX_PER_PAGE', 50);
    $maxPage = (int) env('FEED_MAX_PAGE', 1000);

    // Clamp per_page to safe range
    $perPage = (int) ($request->integer('per_page') ?: $defaultPerPage);
    $perPage = max(1, min($perPage, $maxPerPage));

    // Clamp page to safe range
    $page = (int) ($request->integer('page') ?: 1);
    $page = max(1, min($page, $maxPage));

    $paginator = Photo::where('is_completed', true)
        ->orderByRaw('COALESCE(taken_at, created_at) DESC')
        ->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'data' => $paginator->map(fn($p) => [
            'id' => $p->id,
            'url' => route('photos.show', $p),
            'thumbnail_url' => $p->thumbnail_url,
        ]),
        'nextPage' => $paginator->hasMorePages()
            ? ($paginator->currentPage() + 1)
            : null,
    ]);
}
```

**Why:** Prevent malicious queries like `?page=999999999` or `?per_page=1000000`.

### Model Patterns

#### 1. Accessor Methods for URL Generation

**Pattern:** Encapsulate URL logic in model accessors
```php
class Photo extends Model
{
    public function getOriginalUrlAttribute(): string
    {
        // Relative URLs work regardless of APP_URL or port
        return '/storage/' . ltrim($this->original_path, '/');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path
            ? ('/storage/' . ltrim($this->thumbnail_path, '/'))
            : null;
    }
}

// Usage in views:
<img src="{{ $photo->thumbnail_url }}" alt="{{ $photo->caption }}">
```

**Why:** Views don't need to know about storage paths. Centralizes URL logic.

#### 2. Boolean Helper Methods

**Pattern:** Readable boolean checks with helper methods
```php
class User extends Authenticatable
{
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}

// Usage:
if (auth()->user()->isAdmin()) {
    // Admin-only code
}

// Versus less readable:
if (auth()->user()->is_admin) { }
```

**Why:** More explicit, easier to mock in tests, room for future logic.

#### 3. Computed Properties for Derived Data

**Pattern:** Calculate derived data in model methods
```php
class User extends Authenticatable
{
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}

// Usage in view:
<div class="avatar">{{ auth()->user()->initials() }}</div>
```

**Why:** Keeps views clean, testable logic, consistent formatting.

### Route Organization

#### 1. Named Routes Everywhere

**Pattern:** Every route gets a name
```php
// Public routes
Route::get('/', function () { ... })->name('home');
Route::get('photo/{photo}', [PhotoController::class, 'show'])->name('photos.show');
Route::get('photo/{photo}/download', [PhotoController::class, 'download'])
    ->name('photos.download');

// Auth-protected routes
Route::middleware(['auth'])->group(function () {
    Route::get('photos/upload', [PhotoUploadController::class, 'showUploadForm'])
        ->name('photos.upload.show');
    Route::post('photos/upload', [PhotoUploadController::class, 'handleUpload'])
        ->name('photos.upload.handle');
});
```

**Why:** Change URLs without breaking code. Use `route('photos.show', $photo)` everywhere.

#### 2. Middleware Grouping

**Pattern:** Nest middleware groups for clarity
```php
// All authenticated routes
Route::middleware(['auth'])->group(function () {
    // General authenticated routes
    Route::get('photos/upload', ...);

    // Admin-only routes nested inside auth
    Route::middleware(['admin'])->group(function () {
        Volt::route('settings/site', 'settings.site');
        Volt::route('settings/family-members', 'settings.family-members');
    });
});
```

**Why:** Clear hierarchy, no redundant middleware declarations.

#### 3. Throttle Middleware per Endpoint Type

**Pattern:** Different rate limits for different endpoints
```php
// In routes/web.php:
Route::get('photos/feed', [PhotoController::class, 'feed'])
    ->middleware('throttle:feed')
    ->name('photos.feed');

Route::get('photo/{photo}', [PhotoController::class, 'show'])
    ->middleware('throttle:photo-show')
    ->name('photos.show');

Route::get('photo/{photo}/download', [PhotoController::class, 'download'])
    ->middleware('throttle:download')
    ->name('photos.download');

// In app/Providers/AppServiceProvider.php boot():
RateLimiter::for('feed', function (Request $request) {
    return Limit::perMinute(env('FEED_PER_MINUTE', 60))
        ->by($request->ip());
});

RateLimiter::for('photo-show', function (Request $request) {
    return Limit::perMinute(env('SHOW_PER_MINUTE', 120))
        ->by($request->ip());
});

RateLimiter::for('download', function (Request $request) {
    return Limit::perMinute(env('DOWNLOADS_PER_MINUTE', 30))
        ->by($request->ip());
});
```

**Why:** Downloads are expensive (limit 30/min), viewing is cheap (limit 120/min).

### Authorization Patterns

#### 1. Inline Authorization for Simple Cases

**Pattern:** Use `abort_unless()` for simple ownership checks
```php
public function edit(Photo $photo): View
{
    $user = Auth::user();
    abort_unless($user && ($user->isAdmin() || $user->id === $photo->user_id), 403);

    return view('photos.edit', compact('photo'));
}
```

**Why:** No need for formal policies for simple ownership checks.

**When to use policies instead:**
- Complex authorization logic
- Multiple models with same rules
- Need to call `can()` from multiple places

#### 2. Custom Middleware for Role Checks

**Pattern:** Simple middleware for role-based access
```php
// app/Http/Middleware/AdminMiddleware.php
class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        return $next($request);
    }
}

// Register in bootstrap/app.php:
$middleware->alias([
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
]);

// Use in routes:
Route::middleware(['auth', 'admin'])->group(function () {
    // Admin routes
});
```

**Why:** Reusable across routes, clear error message, stack with other middleware.

### Validation Patterns

#### 1. Inline Validation for Simple Forms

**Pattern:** Validate directly in controller methods
```php
public function handleCaption(Request $request, Photo $photo): RedirectResponse
{
    $this->authorizeOwner($photo);

    $validated = $request->validate([
        'caption' => ['nullable', 'string', 'max:500'],
    ]);

    $photo->caption = $validated['caption'] ?? null;
    $photo->is_completed = true;
    $photo->save();

    return redirect()->route('home')->with('status', 'Photo uploaded');
}
```

**Why:** Simple, readable, no extra files for trivial validation.

**When to use Form Requests instead:**
- Complex validation logic
- Custom authorization checks
- Reusable validation rules
- Need to customize error messages extensively

#### 2. Livewire Validation with Attributes

**Pattern:** Use `#[Rule()]` attributes in Volt components
```php
new class extends Component {
    #[Rule('required|email|max:255')]
    public string $new_email = '';

    #[Rule('nullable|string|max:255')]
    public string $new_email_name = '';

    public function addAllowedEmail(): void
    {
        // Additional validation if needed
        $this->validate([
            'new_email' => 'required|email|max:255|unique:allowed_emails,email',
        ]);

        AllowedEmail::create([
            'email' => $this->new_email,
            'name' => $this->new_email_name ?: null,
        ]);

        $this->reset(['new_email', 'new_email_name']);
    }
};
```

**Why:** Attribute validation runs on property updates, method validation runs on submit.

### Image Processing Patterns

#### 1. HEIC Detection and Conversion

**Pattern:** Handle HEIC files transparently
```php
protected function isHeicFile(string $filePath): bool
{
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $isHeicExtension = in_array($extension, ['heic', 'heif']);

    $mimeType = mime_content_type($filePath);
    $isHeicMime = in_array($mimeType, ['image/heic', 'image/heif']);

    try {
        $imagick = new \Imagick($filePath);
        $format = strtoupper($imagick->getImageFormat());
        $imagick->destroy();
        $isHeicFormat = in_array($format, ['HEIC', 'HEIF']);
    } catch (\Exception $e) {
        $isHeicFormat = false;
    }

    return $isHeicExtension || $isHeicMime || $isHeicFormat;
}

protected function convertAndStoreHeic(string $uploadedTempPath): array
{
    $imagick = new \Imagick($uploadedTempPath);

    $width = $imagick->getImageWidth();
    $height = $imagick->getImageHeight();

    // Convert to JPEG
    $imagick->setImageFormat('JPEG');
    $imagick->setImageCompressionQuality(90);

    $relativePath = 'photos/originals/' . Str::uuid() . '.jpg';
    $dest = Storage::disk('public')->path($relativePath);

    $imagick->writeImage($dest);
    $imagick->destroy();

    return [$relativePath, $width, $height];
}
```

**Why:** iPhones shoot HEIC by default. Convert to JPEG for universal compatibility.

#### 2. EXIF Data Extraction with Fallbacks

**Pattern:** Try multiple methods to extract photo dates
```php
protected function extractTakenAtDate(string $filePath): ?string
{
    $takenAt = null;

    // Method 1: Standard EXIF (works for JPEG)
    if (function_exists('exif_read_data')) {
        try {
            $exif = @exif_read_data($filePath, 'EXIF');
            if ($exif && !empty($exif['DateTimeOriginal'])) {
                $takenAt = date('Y-m-d H:i:s', strtotime($exif['DateTimeOriginal']));
            }
        } catch (\Throwable $e) {
            // Continue to next method
        }
    }

    // Method 2: Imagick properties (works for HEIC and more)
    if (!$takenAt) {
        try {
            $imagick = new \Imagick($filePath);
            $dateProperties = [
                'exif:DateTimeOriginal',
                'exif:DateTime',
                'exif:CreateDate',
                'date:create',
            ];

            foreach ($dateProperties as $property) {
                $dateValue = $imagick->getImageProperty($property);
                if ($dateValue && $dateValue !== '') {
                    $timestamp = strtotime($dateValue);
                    if ($timestamp !== false) {
                        $takenAt = date('Y-m-d H:i:s', $timestamp);
                        break;
                    }
                }
            }
            $imagick->destroy();
        } catch (\Exception $e) {
            // Continue to next method
        }
    }

    // Method 3: File timestamp fallback
    if (!$takenAt) {
        $fileTime = filemtime($filePath);
        if ($fileTime !== false) {
            $takenAt = date('Y-m-d H:i:s', $fileTime);
        }
    }

    return $takenAt;
}
```

**Why:** Different file types store dates differently. Always have a fallback.

#### 3. Auto-Orientation with Compatibility

**Pattern:** Handle EXIF orientation with fallback for older servers
```php
protected function autoOrientImagick(\Imagick $imagick): void
{
    // New method (PHP 8.1+, Imagick 3.7+)
    if (method_exists($imagick, 'autoOrientImage')) {
        $imagick->autoOrientImage();
        return;
    }

    // Fallback: manual orientation handling
    try {
        $orientation = $imagick->getImageProperty('exif:Orientation');

        if (!$orientation || $orientation == 1) {
            return; // Already correct
        }

        switch ($orientation) {
            case 3:
                $imagick->rotateImage('transparent', 180);
                break;
            case 6:
                $imagick->rotateImage('transparent', 90);
                break;
            case 8:
                $imagick->rotateImage('transparent', -90);
                break;
            // ... other cases
        }

        $imagick->setImageProperty('exif:Orientation', '1');
    } catch (\Exception $e) {
        // Ignore orientation errors
    }
}
```

**Why:** Dreamhost might have older PHP/Imagick versions. Always provide fallbacks.

#### 4. Thumbnail Generation with Quality Settings

**Pattern:** Balance quality and file size
```php
protected function createSquareThumbnail(Photo $photo, string $anchor): void
{
    $absolutePath = Storage::disk('public')->path($photo->original_path);
    $imagick = new \Imagick($absolutePath);

    $this->autoOrientImagick($imagick);

    $width = $imagick->getImageWidth();
    $height = $imagick->getImageHeight();
    $squareSize = min($width, $height);

    // Crop to square
    $x = ($width > $height) ? (($width - $squareSize) / 2) : 0;
    $y = ($height > $width) ? (($height - $squareSize) / 2) : 0;
    $imagick->cropImage($squareSize, $squareSize, $x, $y);

    // Resize to 800x800 thumbnail
    $imagick->resizeImage(800, 800, \Imagick::FILTER_LANCZOS, 1);

    // Format and quality
    $imagick->setImageFormat('jpeg');
    $imagick->setImageCompressionQuality(85);  // Good balance
    $imagick->stripImage();  // Remove EXIF for privacy and size

    $filename = 'photos/thumbnails/' . Str::uuid() . '.jpg';
    $thumbPath = Storage::disk('public')->path($filename);

    $imagick->writeImage($thumbPath);
    $imagick->destroy();

    $photo->thumbnail_path = $filename;
    $photo->save();
}
```

**Why:**
- 800x800 is good for display on most screens
- 85% quality: imperceptible quality loss, 50%+ smaller files
- Strip EXIF from thumbnails for privacy
- LANCZOS filter: best quality for downsizing

### Livewire/Volt Patterns

#### 1. Single File Components (SFC)

**Pattern:** Logic and view in one file for simple components
```php
<?php

use Livewire\Volt\Component;
use App\Models\AllowedEmail;

new class extends Component {
    public string $new_email = '';

    public function addAllowedEmail(): void
    {
        $this->validate([
            'new_email' => 'required|email|max:255|unique:allowed_emails,email',
        ]);

        AllowedEmail::create(['email' => $this->new_email]);

        $this->reset('new_email');
        $this->dispatch('allowed-email-added');
    }

    public function getAllowedEmails()
    {
        return AllowedEmail::orderBy('email')->get();
    }
}; ?>

<section>
    <form wire:submit="addAllowedEmail">
        <input wire:model="new_email" type="email">
        <button type="submit">Add</button>
    </form>

    @foreach($this->getAllowedEmails() as $email)
        <div>{{ $email->email }}</div>
    @endforeach
</section>
```

**Why:** Perfect for settings pages and CRUD interfaces. No separate class files.

#### 2. Computed Properties for Dynamic Data

**Pattern:** Methods that return data (like computed properties)
```php
new class extends Component {
    public function getAllowedEmails()
    {
        return AllowedEmail::orderBy('email')->get();
    }
};

// Use in template with $this->:
@foreach($this->getAllowedEmails() as $email)
```

**Why:** Automatically re-queries when component updates. No need to store in properties.

#### 3. Events for Component Communication

**Pattern:** Dispatch events for side effects
```php
public function addAllowedEmail(): void
{
    AllowedEmail::create(['email' => $this->new_email]);

    $this->reset('new_email');
    $this->dispatch('allowed-email-added');  // Other components can listen
}
```

**Why:** Decouple components. Other components can react to changes.

### Middleware Patterns

#### 1. Production-Only Security Middleware

**Pattern:** Only apply security headers in production
```php
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!app()->isProduction()) {
            return $response;  // Skip in development
        }

        $csp = [
            "default-src 'self'",
            "script-src 'self' https://cdn.tailwindcss.com",
            "style-src 'self' 'unsafe-inline'",
            // ...
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        // ... other headers

        return $response;
    }
}
```

**Why:** Development needs relaxed CSP for hot reload. Production needs strict security.

### Migration Patterns

#### 1. Cascade on Delete for Foreign Keys

**Pattern:** Clean up related records automatically
```php
Schema::create('photos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')
        ->constrained()
        ->cascadeOnDelete();  // Delete photos when user is deleted
    $table->string('original_path');
    $table->timestamps();
});
```

**Why:** Prevents orphaned records. Database enforces referential integrity.

#### 2. Index Frequently Queried Columns

**Pattern:** Add indexes to columns used in WHERE/ORDER BY
```php
Schema::create('photos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamp('taken_at')->nullable()->index();  // ← Indexed!
    $table->boolean('is_completed')->default(false)->index();  // ← Indexed!
    $table->timestamps();
});
```

**Why:** Queries like `where('is_completed', true)->orderBy('taken_at')` are fast.

**Rule of thumb:** Index any column used in:
- WHERE clauses
- ORDER BY clauses
- Foreign keys (automatically indexed)

### Debugging Patterns

#### 1. Debug Route for PHP Configuration

**Pattern:** Add a debug route to check PHP limits
```php
Route::get('/debug-php', function() {
    return response()->json([
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'memory_limit' => ini_get('memory_limit'),
        'loaded_ini' => php_ini_loaded_file()
    ]);
});
```

**Why:** Quickly check PHP configuration without SSH access.

**Important:** Remove or protect this route in production!

#### 2. Comprehensive Logging with Context

**Pattern:** Log events with full context for debugging
```php
logger()->info('Upload attempt started', [
    'user_id' => Auth::id(),
    'request_size' => $request->server('CONTENT_LENGTH'),
    'has_file' => $request->hasFile('photo'),
    'php_limits' => [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
    ],
]);
```

**Why:** When things go wrong, you have the context to debug. Structured data is searchable.

### Key Takeaways

1. **Simplicity Wins**: PhotoController and PhotoUploadController are straightforward
2. **Fail Loudly**: Comprehensive error messages for file uploads
3. **Defensive Programming**: Bounds checking, fallbacks, null coalescing
4. **Performance Matters**: Indexes on queried columns, smart pagination limits
5. **User Experience**: HEIC conversion, EXIF extraction, helpful error messages
6. **Security by Default**: Rate limiting, authorization checks, CSP headers
7. **Debuggability**: Extensive logging with context
8. **Compatibility**: Fallbacks for older PHP/Imagick versions (Dreamhost)

---

## Common Patterns

### Authentication Flow

**Use Laravel Breeze (API or Blade):**
```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
# or
php artisan breeze:install api

npm install
npm run build
php artisan migrate
```

**Test authentication:**
```php
// tests/Feature/Auth/AuthenticationTest.php
test('users can login', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});
```

### File Upload Pattern

**Controller:**
```php
public function store(Request $request)
{
    $validated = $request->validate([
        'image' => 'required|image|max:10240', // 10MB
        'caption' => 'nullable|string|max:500',
    ]);

    $path = $request->file('image')->store('photos', 'public');

    $photo = Photo::create([
        'path' => $path,
        'caption' => $validated['caption'] ?? null,
        'user_id' => auth()->id(),
    ]);

    return redirect()->route('photos.show', $photo);
}
```

**Test:**
```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('users can upload photos', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/photos', [
        'image' => UploadedFile::fake()->image('photo.jpg'),
        'caption' => 'Test caption',
    ]);

    $response->assertRedirect();
    Storage::disk('public')->assertExists('photos/photo.jpg');
});
```

### Factory Pattern

**Define factories for testing:**
```php
// database/factories/PhotoFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PhotoFactory extends Factory
{
    public function definition()
    {
        return [
            'path' => 'photos/' . $this->faker->uuid() . '.jpg',
            'caption' => $this->faker->sentence(),
            'user_id' => User::factory(),
            'taken_at' => $this->faker->dateTimeBetween('-1 year'),
            'is_completed' => true,
        ];
    }

    public function incomplete()
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => false,
        ]);
    }
}
```

**Use in tests:**
```php
$photos = Photo::factory()->count(10)->create();
$incomplete = Photo::factory()->incomplete()->create();
```

### API Resource Pattern

**For JSON APIs:**
```php
// app/Http/Resources/PhotoResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PhotoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'caption' => $this->caption,
            'image_url' => Storage::url($this->path),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

**Use in controller:**
```php
public function index()
{
    return PhotoResource::collection(
        Photo::latest()->paginate(20)
    );
}
```

### Livewire Component Pattern

**Volt SFC (Single File Component):**
```php
// resources/views/livewire/photo-uploader.blade.php
<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public $photo;
    public $caption = '';

    public function save()
    {
        $this->validate([
            'photo' => 'required|image|max:10240',
            'caption' => 'nullable|string|max:500',
        ]);

        $path = $this->photo->store('photos', 'public');

        Photo::create([
            'path' => $path,
            'caption' => $this->caption,
            'user_id' => auth()->id(),
        ]);

        session()->flash('message', 'Photo uploaded!');
        $this->redirect(route('photos.index'));
    }
}; ?>

<div>
    <form wire:submit="save">
        <input type="file" wire:model="photo">
        @error('photo') <span>{{ $message }}</span> @enderror

        <input type="text" wire:model="caption" placeholder="Caption">

        <button type="submit">Upload</button>
    </form>
</div>
```

---

## Troubleshooting

This section is organized into two parts:
1. **Real Issues We Hit** - Problems actually encountered during family-photo-album development
2. **Generic Laravel/Dreamhost Issues** - Common problems you might encounter

### Part 1: Real Issues We Hit

These are problems we actually encountered and solved in the family-photo-album project. The solutions are proven in production.

#### 1. File Uploads Silently Failing (post_max_size overflow)

**Problem:** Users upload photos, form submits, but Laravel receives nothing. No error message.

**Symptoms:**
- File uploads work for small images but fail for large ones
- Request appears to succeed but `$request->hasFile()` returns false
- No validation errors shown

**Root Cause:** When uploaded file + POST data exceeds `post_max_size`, PHP **silently discards everything**. Laravel never sees the data.

**How We Detect It:**
```php
// In PhotoUploadController.php
if ($request->isMethod('post')
    && (int)($request->server('CONTENT_LENGTH') ?? 0) > 0
    && empty($_POST)) {

    // This is the signature of post_max_size overflow!
    $limits = sprintf(
        'upload_max_filesize=%s, post_max_size=%s',
        ini_get('upload_max_filesize'),
        ini_get('post_max_size')
    );

    return back()->withErrors([
        'photo' => "Upload exceeded post_max_size. Current limits: {$limits}."
    ])->withInput();
}
```

**Solution:**
1. **Check current limits:** Visit `/debug-php` route (see code below)
2. **Increase limits** on Dreamhost:
   - SSH into server
   - Edit `~/.php/8.2/phprc` (or create it)
   - Add:
     ```ini
     upload_max_filesize = 20M
     post_max_size = 25M        ; Must be larger than upload_max_filesize!
     memory_limit = 128M
     ```
   - Restart PHP: `killall php82.cgi` (Dreamhost restarts automatically)

3. **Verify** by visiting `/debug-php` again

**Debug Route (Add to routes/web.php):**
```php
Route::get('/debug-php', function() {
    return response()->json([
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'loaded_ini' => php_ini_loaded_file()
    ]);
});
```

**Important:** Remove or protect this route in production!

#### 2. HEIC Files Not Recognized (iPhone Photos)

**Problem:** iPhones shoot photos in HEIC format by default. Uploads fail or photos don't display.

**Symptoms:**
- Validation fails with "The file must be an image"
- Users confused why "photos from my phone" don't work
- Works fine with photos from Android or cameras

**Root Cause:**
- PHP's `getimagesize()` doesn't recognize HEIC
- MIME type detection can be inconsistent
- Need Imagick with libheif support

**Our Multi-Method Detection:**
```php
protected function isHeicFile(string $filePath): bool
{
    // Method 1: File extension
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $isHeicExtension = in_array($extension, ['heic', 'heif']);

    // Method 2: MIME type
    $mimeType = mime_content_type($filePath);
    $isHeicMime = in_array($mimeType, ['image/heic', 'image/heif']);

    // Method 3: Imagick format detection
    try {
        $imagick = new \Imagick($filePath);
        $format = strtoupper($imagick->getImageFormat());
        $imagick->destroy();
        $isHeicFormat = in_array($format, ['HEIC', 'HEIF']);
    } catch (\Exception $e) {
        $isHeicFormat = false;
    }

    return $isHeicExtension || $isHeicMime || $isHeicFormat;
}
```

**Solution:**
1. **Accept HEIC in validation:**
   ```php
   $request->validate([
       'photo' => 'required|file|mimes:jpeg,jpg,png,gif,heic,heif|max:10240',
   ]);
   ```

2. **Convert HEIC to JPEG** transparently:
   ```php
   if ($this->isHeicFile($file->getRealPath())) {
       [$path, $width, $height] = $this->convertAndStoreHeic($file->getRealPath());
   }
   ```

3. **Ensure Imagick has HEIC support:**
   - On Dreamhost: Check with `convert -list format | grep HEIC`
   - If missing, contact Dreamhost support or convert client-side

**Fallback:** If server doesn't support HEIC, provide helpful error:
```php
if (str_contains($e->getMessage(), 'NoDecodeDelegateForThisImageFormat')) {
    throw new \RuntimeException(
        'HEIC files are not supported on this server. ' .
        'Please convert to JPEG first or ask your hosting provider to install libheif.'
    );
}
```

#### 3. Images Displaying Rotated Wrong (EXIF Orientation)

**Problem:** Photos appear correctly on phone but display rotated/flipped in browser.

**Symptoms:**
- Portrait photos appear sideways
- Some photos are upside down
- Happens with photos from cameras and phones

**Root Cause:** Many cameras/phones store images in one orientation but set an EXIF "Orientation" flag for how to display them. Browsers ignore this flag.

**Our Solution with Fallback:**
```php
protected function autoOrientImagick(\Imagick $imagick): void
{
    // Try modern method first (Imagick 3.7.0+)
    if (method_exists($imagick, 'autoOrientImage')) {
        $imagick->autoOrientImage();
        return;
    }

    // Fallback for older Imagick (Dreamhost has this)
    try {
        $orientation = $imagick->getImageProperty('exif:Orientation');

        if (!$orientation || $orientation == 1) {
            return; // Already correct
        }

        // Manually rotate based on orientation flag
        switch ($orientation) {
            case 3:
                $imagick->rotateImage('transparent', 180);
                break;
            case 6:
                $imagick->rotateImage('transparent', 90);
                break;
            case 8:
                $imagick->rotateImage('transparent', -90);
                break;
            // ... cases 2, 4, 5, 7 for flips/mirrors
        }

        $imagick->setImageProperty('exif:Orientation', '1');
    } catch (\Exception $e) {
        // Silently continue if orientation fails
    }
}
```

**Apply When Processing:**
```php
$imagick = new \Imagick($absolutePath);
$this->autoOrientImagick($imagick);  // ← Before any resize/crop!
$imagick->cropImage(...);
$imagick->resizeImage(...);
```

**Why This Matters:**
- Older Imagick versions (like on Dreamhost) don't have `autoOrientImage()`
- Fallback ensures photos display correctly everywhere

#### 4. EXIF Date Extraction Failing

**Problem:** Photos don't sort chronologically because `taken_at` is null.

**Symptoms:**
- Recent photos appear before old photos
- Upload date used instead of photo date
- Happens especially with HEIC files or edited photos

**Our Triple-Fallback Solution:**
```php
protected function extractTakenAtDate(string $filePath): ?string
{
    // Method 1: Standard EXIF (fast, works for most JPEGs)
    if (function_exists('exif_read_data')) {
        try {
            $exif = @exif_read_data($filePath, 'EXIF');
            if ($exif && !empty($exif['DateTimeOriginal'])) {
                return date('Y-m-d H:i:s', strtotime($exif['DateTimeOriginal']));
            }
        } catch (\Throwable $e) {
            // Continue to next method
        }
    }

    // Method 2: Imagick (slower, works for HEIC and more)
    if (!$takenAt) {
        try {
            $imagick = new \Imagick($filePath);
            $dateProperties = [
                'exif:DateTimeOriginal',
                'exif:DateTime',
                'exif:CreateDate',
                'date:create',
            ];

            foreach ($dateProperties as $property) {
                $dateValue = $imagick->getImageProperty($property);
                if ($dateValue && strtotime($dateValue) !== false) {
                    return date('Y-m-d H:i:s', strtotime($dateValue));
                }
            }
            $imagick->destroy();
        } catch (\Exception $e) {
            // Continue to fallback
        }
    }

    // Method 3: File timestamp (last resort)
    $fileTime = filemtime($filePath);
    if ($fileTime !== false) {
        return date('Y-m-d H:i:s', $fileTime);
    }

    return null;
}
```

**Why Three Methods:**
- JPEG: EXIF works great
- HEIC: Need Imagick properties
- Edited/Screenshot: No EXIF, use file time
- Always have a fallback!

### Part 2: Generic Laravel/Dreamhost Issues

These are common issues you might encounter. Not all were hit in this project, but good to know.

#### 5. Images Not Showing After Upload

**Problem:** Photos upload successfully but show broken image icons.

**Solution:** Create storage symlink:
```bash
php artisan storage:link
```

**Verify:**
```bash
ls -la public/storage  # Should be symlink to ../../storage/app/public
```

#### 6. "Base table or view not found"

**Problem:** Migrations haven't been run.

**Solution:**
```bash
php artisan migrate
```

**Check status:**
```bash
php artisan migrate:status
```

#### 7. Vite Manifest Not Found (Production)

**Problem:** Assets not built for production.

**Solution:**
```bash
npm run build
```

**Verify:**
```bash
ls -la public/build/manifest.json  # Should exist
```

#### 8. Session Not Persisting

**Problem:** Users get logged out randomly.

**Check:**
```env
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=false  # true only if using HTTPS
```

**Create session table:**
```bash
php artisan session:table
php artisan migrate
```

#### 9. "500 Internal Server Error" (Production)

**Debug:**
```bash
# On server:
tail -50 ~/your-project/storage/logs/laravel.log

# Temporarily enable debug (remove after!)
APP_DEBUG=true  # In .env
```

**Common causes:**
- Missing .env file
- Incorrect database credentials
- Missing vendor/ directory
- Stale cache

**Clear caches:**
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

#### 10. Tests Failing with "Database not found"

**Solution:** Verify `phpunit.xml` has:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Debugging Tips

**1. Add Extensive Logging**
```php
logger()->info('Upload attempt', [
    'user_id' => auth()->id(),
    'file_size' => $file->getSize(),
    'mime_type' => $file->getMimeType(),
    'php_limits' => [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
    ],
]);
```

**2. Check Logs in Real-Time**
```bash
# Development:
php artisan pail

# Production:
tail -f storage/logs/laravel.log
```

**3. Use Laravel Tinker for Data Inspection**
```bash
php artisan tinker
>>> User::count()
>>> Photo::latest()->first()
>>> Photo::where('is_completed', false)->count()
```

---

## Quick Reference Commands

### Development
```bash
# Start everything
composer run dev

# Run tests
composer test
composer test -- --filter TestName
composer run test:coverage

# Code formatting
./vendor/bin/pint

# Database
php artisan migrate
php artisan migrate:fresh
php artisan migrate:rollback
php artisan tinker

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Testing
```bash
# PHP tests
composer test
./vendor/bin/pest
./vendor/bin/pest --filter PhotoUpload
composer run test:coverage-text

# JavaScript tests
npm test
npm run test:watch
npm run test:coverage

# Both
composer run coverage-full
```

### Deployment
```bash
# Local machine
./deploy.sh              # Smart deploy
./deploy.sh --full       # Full deploy
./deploy.sh --vendor-only # Dependencies only

# On server
~/update-project.sh      # Apply deployment
```

### Debugging
```bash
# View logs
php artisan pail

# Or on server:
tail -f storage/logs/laravel.log

# Database inspection
php artisan tinker
>>> User::count()
>>> Photo::latest()->first()

# Route debugging
php artisan route:list
php artisan route:list --name=photos
```

---

## Project Checklist

Use this checklist when starting a new Laravel project:

### Initial Setup
- [ ] Create Laravel project
- [ ] Configure .env for local development (SQLite)
- [ ] Install Livewire/Volt and Flux
- [ ] Install Pest and Vitest
- [ ] Install Laravel Pint
- [ ] Install concurrently
- [ ] Set up Tailwind CSS 4.x
- [ ] Configure Vite
- [ ] Run initial migration

### Testing Setup
- [ ] Configure phpunit.xml
- [ ] Configure vitest.config.js
- [ ] Create test setup files
- [ ] Add composer test scripts
- [ ] Add npm test scripts
- [ ] Write first feature test
- [ ] Verify tests pass

### Git Setup
- [ ] Initialize git repository
- [ ] Update .gitignore
- [ ] Create GitHub repository
- [ ] Initial commit
- [ ] Push to GitHub

### Claude Code Integration
- [ ] Install Context7 MCP
- [ ] Verify Context7 tools available
- [ ] Create .claude/settings.json
- [ ] Configure allowed tools
- [ ] Test Context7 with Laravel docs

### Dreamhost Preparation
- [ ] Create deploy.sh script
- [ ] Create server-files/index.php
- [ ] Provision MySQL database on Dreamhost
- [ ] Configure SSH access
- [ ] Test SSH connection
- [ ] Create .deploy-config (locally)

### First Deployment
- [ ] Run ./deploy.sh --full
- [ ] SSH to server
- [ ] Create production .env
- [ ] Run php artisan key:generate
- [ ] Run migrations
- [ ] Create ~/update-project.sh
- [ ] Test deployment works
- [ ] Verify site loads in browser

### Post-Deployment
- [ ] Set up email monitoring for errors
- [ ] Configure rate limiting
- [ ] Add security headers middleware
- [ ] Test file uploads
- [ ] Set up backup strategy
- [ ] Document any project-specific setup

---

## Additional Resources

### Official Documentation
- **Laravel**: https://laravel.com/docs/12.x
- **Livewire**: https://livewire.laravel.com/docs
- **Pest**: https://pestphp.com/docs
- **Tailwind CSS**: https://tailwindcss.com/docs
- **Vite**: https://vite.dev/

### Tools
- **Laravel Herd**: https://herd.laravel.com/
- **Context7 MCP**: (installation instructions)
- **Dreamhost Docs**: https://help.dreamhost.com/

### Learning Resources
- **Laracasts**: https://laracasts.com/ (excellent video tutorials)
- **Laravel Daily**: https://laraveldaily.com/ (practical tips)
- **Laravel News**: https://laravel-news.com/ (stay updated)

---

## Conclusion

This guide captures lessons learned from building and deploying family-photo-album to Dreamhost shared hosting using Claude Code. Key takeaways:

1. **Simplicity wins** - vanilla JS, no queues, basic Laravel features
2. **Test from day one** - comprehensive Pest and Vitest setup
3. **Use Context7** - avoid hallucinating old Laravel patterns
4. **Smart deployment** - three-mode deploy script saves time
5. **Configure permissions** - let Claude work autonomously
6. **Plan when needed** - but jump in for simple changes

These patterns create high-quality, low-cost applications that deploy easily and run reliably on shared hosting.

**Happy building!**
