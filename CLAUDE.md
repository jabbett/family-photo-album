# Family Photo Album - Claude Developer Guide

## Project Overview

Family Photo Album is a **Laravel 12** web application designed for families to share photos in a friction-free, mobile-first experience. It features an Instagram-like public feed accessible without login, while family members authenticate to upload and manage photos.

**Core Philosophy**: Create a simple, self-hosted alternative to social media photo sharing with full content ownership and control, optimized for inexpensive shared hosting.

## Architecture Overview

### Tech Stack
- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Livewire/Volt components with Tailwind CSS 4.x
- **UI Framework**: Flux UI components
- **Database**: SQLite (default) or MySQL
- **Asset Pipeline**: Vite with Tailwind CSS
- **Testing**: Pest PHP (with Browser plugin for E2E) + Vitest (JavaScript)
- **Browser Testing**: Playwright (via Pest 4 browser plugin, replaces Laravel Dusk)
- **Image Processing**: PHP GD/Imagick with CropperJS for client-side cropping

### Key Components

#### Models
- **User**: Authentication with admin flag, family member management
- **Photo**: Core entity with upload stages (is_completed flag), EXIF data extraction
- **AllowedEmail**: Admin-managed whitelist for family registration
- **Setting**: Application configuration storage

#### Controllers
- **PhotoController**: Public photo viewing, editing, deletion with authorization
- **PhotoUploadController**: Multi-stage upload flow (upload → crop → caption)
- **Auth Controllers**: Standard Laravel authentication

#### Livewire Volt Components
- **Settings**: Profile, password, site settings, family member management
- **Auth**: Login, registration, password reset flows

## Development Environment

### Requirements
- PHP 8.2+
- Composer
- Node.js/npm
- SQLite (or MySQL)

### Quick Setup
```bash
# Clone and install
composer install && npm install

# Environment setup
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan storage:link

# Start development (unified command)
composer run dev
```

The `composer run dev` command runs concurrently:
- Laravel development server (`php artisan serve`)
- Queue worker (`php artisan queue:listen`)
- Log viewer (`php artisan pail`)
- Vite development server (`npm run dev`)

### Development Commands

#### PHP/Laravel
```bash
# Testing
composer test                    # Run Unit + Feature tests (179 tests, ~8s)
composer test:browser            # Run Browser/E2E tests only (9 tests, ~4s)
composer test:all                # Run all tests (188 tests total, ~13s)
composer run test:coverage       # Generate PHP coverage reports
composer run test:coverage-text  # Show coverage in terminal
./vendor/bin/pest                # Direct Pest execution

# Code quality
./vendor/bin/pint                # Laravel Pint code formatting

# Laravel commands
php artisan migrate              # Run migrations
php artisan tinker               # Laravel REPL
php artisan storage:link         # Link storage directory
```

#### JavaScript/Frontend  
```bash
npm run dev          # Vite development server
npm run build        # Production build
npm test             # Run Vitest tests
npm run test:watch   # Watch mode testing
npm run test:coverage # Generate JS coverage
```

#### Combined Testing
```bash
composer run coverage-full   # Run both PHP and JS test coverage
```

## Project Structure

### Key Directories
```
app/
├── Http/Controllers/        # Traditional controllers
├── Http/Middleware/        # Security headers, admin auth
├── Livewire/Actions/       # Livewire actions (logout)
├── Models/                 # Eloquent models
└── Providers/              # Service providers

resources/
├── css/app.css            # Tailwind CSS entry point
├── js/                    # JavaScript modules
│   ├── app.js            # Main JS entry
│   ├── photo-album.js    # Infinite scroll functionality
│   ├── photo-crop.js     # CropperJS integration
│   ├── photo-upload.js   # Upload flow logic
│   └── photo-share.js    # Share functionality
└── views/
    ├── livewire/         # Volt component views
    └── components/       # Blade components

routes/
├── web.php              # Main application routes
├── auth.php            # Authentication routes
└── console.php         # Artisan commands

tests/
├── Browser/           # E2E tests with Playwright
├── Feature/           # Integration tests
├── Unit/              # Unit tests
└── js/                # JavaScript tests (Vitest)

database/
├── migrations/         # Database schema
├── factories/         # Test data factories
└── seeders/           # Database seeders
```

### Important Files
- **composer.json**: PHP dependencies, custom scripts (includes test commands)
- **package.json**: Node dependencies, build scripts (includes Playwright)
- **tests/Pest.php**: Pest test configuration (browser testing setup, timeouts, test groups)
- **vite.config.js**: Vite + Tailwind configuration
- **vitest.config.js**: JavaScript test configuration
- **phpunit.xml**: PHP test configuration
- **deploy.sh**: Production deployment script
- **.env.example**: Environment configuration template

## Core Features

### Photo Management Flow
1. **Upload**: Async upload with instant preview (FileReader API for preview, Fetch API for background upload)
2. **Caption**: Add caption while viewing live preview with edit date/time
3. **Crop**: Client-side cropping with CropperJS (for non-square images)
4. **Completion**: Photo marked as completed to show in public feed

**Key UX Features**:
- Instant file preview before upload completes
- Background upload with progress indicator
- HEIC/HEIF support with automatic JPEG conversion
- Non-blocking UI - users can enter captions while upload completes

### Authorization System
- **Public**: View photos and feed (no auth required)
- **Authenticated Users**: Upload photos, edit own photos
- **Admins**: Edit any photo, manage family members, site settings

### Mobile-First Design
- Instagram-like grid layout
- Infinite scroll functionality
- Touch-optimized photo cropping
- Responsive image serving (thumbnails + full size)

## Database Schema

### Key Tables
- **users**: Family members with admin flag
- **photos**: Photo metadata with upload completion tracking
- **allowed_emails**: Admin-managed whitelist for registration
- **settings**: Key-value configuration storage
- **cache**, **jobs**, **sessions**: Laravel system tables

### Photo States
- **Incomplete** (`is_completed = false`): Upload in progress
- **Complete** (`is_completed = true`): Visible in public feed

## Security Features

### Production Security Headers (SecurityHeadersMiddleware)
- Content-Security-Policy with strict external resource policy
- HTTPS enforcement with HSTS
- X-Frame-Options, X-Content-Type-Options
- Referrer-Policy, Permissions-Policy

### Rate Limiting
- **Feed access**: 60 requests/minute (configurable: FEED_PER_MINUTE)
- **Photo viewing**: 120 requests/minute (SHOW_PER_MINUTE)  
- **Downloads**: 30 requests/minute (DOWNLOADS_PER_MINUTE)

### Authentication
- Email-based registration with admin approval
- Laravel's built-in authentication system
- Admin middleware for privileged operations

## Testing Strategy

### PHP Tests (Pest)
- **Feature Tests**: Full request/response testing (integration)
- **Unit Tests**: Individual component testing
- **Browser Tests**: End-to-end testing with real browsers via Playwright
- **Coverage**: HTML reports in `reports/coverage/html/`
- **Current Coverage**: ~77.5% overall, 99%+ for critical upload flow

### Browser Tests (Pest + Playwright)
- **Why Playwright over Dusk**: Laravel 12 recommends Pest 4 browser testing for better performance and usability
- **Real Browser Testing**: Chromium, Firefox, and WebKit support
- **Built-in Assertions**: Accessibility testing, JavaScript error detection
- **Test Database**: Uses `RefreshDatabase` trait for isolated test runs
- **Speed**: ~4-5 seconds for 9 comprehensive E2E tests
- **Key Tests**:
  - Authentication flows (login, registration)
  - Upload flow (file selection, preview, caption)
  - Accessibility compliance (WCAG standards)
  - JavaScript error monitoring

### JavaScript Tests (Vitest)
- **Unit Tests**: Module testing with jsdom
- **Coverage**: V8 coverage reports (99%+ for photo-upload.js)
- **Configuration**: Test environment setup in `tests/js/setup.js`

### Test Organization
```bash
tests/
├── Browser/              # E2E tests with Playwright
│   ├── AuthenticationTest.php
│   └── PhotoUploadTest.php
├── Feature/              # Integration tests
│   ├── HomePageInfiniteScrollTest.php
│   ├── PhotoShowTest.php
│   ├── PhotoUploadAsyncTest.php
│   ├── UploadFlowTest.php
│   └── Settings/ProfileUpdateTest.php
├── Unit/                 # Unit tests
│   ├── AllowedEmailTest.php
│   └── ExampleTest.php
└── js/                   # JavaScript unit tests
    └── photo-upload.test.js
```

### Running Tests
```bash
# Quick feedback during development (Unit + Feature only)
composer test

# Full E2E validation (includes browser tests)
composer test:all

# Browser tests only (requires Playwright binaries)
composer test:browser

# Coverage reports (PHP + JS combined)
composer run coverage-full
```

## Development Workflow and Best Practices

### Using Context7 for Current Documentation

**Critical**: Before implementing any Laravel/Livewire/Pest feature, use Context7 MCP to fetch current documentation. Without this, Claude may suggest outdated patterns from older Laravel versions.

#### Why Context7 Matters
- Prevents using deprecated Laravel syntax
- Ensures use of current Laravel 12 features
- Avoids hallucinating non-existent APIs
- Provides accurate code examples

#### When to Use Context7
Always consult Context7 before:
- Implementing authentication/authorization
- Working with Eloquent relationships
- Using validation rules
- Handling file uploads
- Creating middleware
- Any feature where you're not 100% certain of current syntax

#### Standard Workflow
```bash
1. Resolve library ID: mcp__context7__resolve-library-id with "laravel"
2. Fetch documentation: mcp__context7__get-library-docs with topic
3. Review current patterns
4. Implement using verified Laravel 12 syntax
```

#### Common Topics to Query
- **Laravel**: "authentication", "eloquent relationships", "validation rules", "file uploads", "middleware"
- **Livewire**: "component lifecycle", "form validation", "volt sfc syntax"
- **Pest**: "feature testing", "database testing", "expectations api"

### Visual Verification with Playwright MCP

**Important**: After ANY frontend/UI changes, use Playwright MCP to visually verify the implementation before marking work as complete.

#### When to Use Playwright (Proactively, Without Asking)
1. **After UI/Frontend Changes**:
   - Modified views or Blade components
   - Changed CSS/Tailwind classes
   - Updated layouts or navigation
   - Added or modified forms

2. **Design Verification**:
   - User provides design mockups
   - Need to verify implementation matches designs
   - Checking responsive behavior (mobile/desktop)

3. **Integration Validation**:
   - After installing frontend libraries
   - Verifying JavaScript interactions
   - Testing Livewire component rendering

#### Standard Playwright Workflow
```markdown
1. Ensure dev server is running (composer run dev)
2. Navigate to affected page: mcp__playwright__browser_navigate
3. Take desktop screenshot: mcp__playwright__browser_take_screenshot
4. Test mobile view:
   - Resize: mcp__playwright__browser_resize (width: 375, height: 667)
   - Take mobile screenshot
5. Compare with design mockups if provided
6. Check for console errors: mcp__playwright__browser_console_messages
7. Document findings before marking complete
```

#### Example Workflow
After modifying photo upload page:
```
- Navigate to http://127.0.0.1:8000/photos/upload
- Screenshot desktop view (1920x1080)
- Resize to mobile (375x667)
- Screenshot mobile view
- If design file exists: Read design/upload-page.png and compare
- Report: "✅ Implementation matches design" or "⚠️ Differences found"
```

**Critical Rule**: Only mark UI/frontend work as complete AFTER visual verification passes.

### Simplicity-First Principle

**Core Philosophy**: Always start with the simplest solution that proves the feature works. Don't add complexity until it's actually needed.

#### The Three-Step Approach
1. **Implement the simplest version** that demonstrates the feature working
2. **Get user feedback** on the simple implementation
3. **Add complexity only if actually needed**

#### Example: Photo Upload
❌ **Don't start with:**
```php
// Over-engineered from day one
DB::transaction(function() use ($validated) {
    $photo = Photo::create($validated);
    event(new PhotoCreated($photo));
    Cache::tags('photos')->flush();
    dispatch(new ProcessPhotoJob($photo));
    Notification::send($user, new PhotoUploaded($photo));
});
```

✅ **Start with:**
```php
// Simple, proves it works
$photo = Photo::create($validated);
return redirect()->route('photos.show', $photo);
```

Then add transactions, events, caching, etc. only when actually needed.

#### Simplicity Test Questions
Before adding any dependency or pattern, ask:
- Can this be done with vanilla PHP/JS and Laravel built-ins?
- Is this solving a problem we actually have, or might have?
- Will this make deployment more complex?

### Task Completion Requirements

**Never mark a task as "completed" unless ALL validation steps pass.**

#### Required Validation Before Marking Complete

1. **For Backend Changes:**
   ```bash
   composer test  # All tests must pass
   ```

2. **For Frontend/JavaScript Changes:**
   ```bash
   npm test  # All JS tests must pass
   ```

3. **For UI/Visual Changes:**
   - Run Playwright verification (desktop + mobile)
   - Take screenshots and compare with designs
   - Check browser console for errors
   - Verify responsive behavior

4. **For Database Changes:**
   ```bash
   php artisan migrate:fresh  # Test from scratch
   php artisan migrate:rollback  # Test rollback works
   ```

5. **For New Features:**
   ```bash
   composer test:coverage-text  # Verify adequate coverage
   ```

#### Validation Checklist
Before marking any todo as completed:
- [ ] All automated tests pass (composer test / npm test)
- [ ] Manual testing confirms feature works as expected
- [ ] No console errors (for frontend changes)
- [ ] Visual verification complete (for UI changes)
- [ ] Code follows Laravel conventions
- [ ] Tests added for new functionality
- [ ] No debug code left (dd(), var_dump(), console.log for debugging)

#### When Tests Fail
**Keep task as "in_progress"** and create a new task describing the blocker:
- Tests failing → Fix tests, then mark complete
- Partial implementation → Finish implementation, then mark complete
- Console errors → Fix errors, then mark complete

### When to Plan vs Code Immediately

#### Plan First (Present Plan for Approval) When:
1. **Multi-file features** spanning 3+ files
   - Example: New photo sharing feature touching models, controllers, views, routes

2. **Database schema changes**
   - Migrations affecting existing data
   - Complex model relationships
   - Data transformations

3. **New architectural patterns**
   - Introducing middleware
   - Adding service layers
   - Major refactoring

4. **Exploratory requests**
   - User asks "how would you..."
   - User asks "what's the best way to..."
   - Unclear requirements

**Planning Template:**
```markdown
I'll help implement [feature]. Here's my plan:

## Approach
[High-level strategy]

## Files to Create/Modify
1. file/path.php - [purpose]
2. file/path.php - [purpose]

## Steps
1. [Step 1]
2. [Step 2]

## Testing Plan
- Feature test: [what to test]
- Browser test: [what to verify]

Ready to proceed?
```

#### Code Immediately (No Planning) When:
- Single-file changes
- Simple bug fixes
- View/template updates
- CSS/styling changes
- Adding routes to existing controllers
- Writing tests
- Documentation updates

The key difference: If it's obvious what to do and affects few files, just do it. If it requires architectural decisions or affects many files, plan first.

## Deployment

### Production Deployment
Uses custom `deploy.sh` script with three modes:

```bash
./deploy.sh              # Smart deploy (~500KB, excludes vendor/)
./deploy.sh --full       # Full deploy (~9MB, includes vendor/)  
./deploy.sh --vendor-only # Update dependencies only (~6MB)
```

### Deployment Workflow
1. **First time**: `./deploy.sh --full`
2. **Regular updates**: `./deploy.sh`
3. **After composer changes**: `./deploy.sh --vendor-only && ./deploy.sh`

### Production Environment Variables
```bash
APP_ENV=production
APP_URL=https://yourdomain.example
TRUSTED_PROXIES="*"
SESSION_SECURE_COOKIE=true

# Rate limiting (optional)
FEED_PER_MINUTE=60
SHOW_PER_MINUTE=120
DOWNLOADS_PER_MINUTE=30
```

### Admin Setup
After deployment, promote a user to admin:
```bash
php artisan tinker
>>> User::where('email', 'your@email.com')->first()->update(['is_admin' => true]);
```

## Development Patterns

### Livewire/Volt Usage
- Settings pages use Volt components for reactive interfaces
- Traditional controllers handle file uploads and complex logic
- Blade components for reusable UI elements

### Asset Pipeline
- **Vite**: Modern asset bundling with hot reload
- **Tailwind CSS 4.x**: Utility-first styling with Vite plugin
- **JavaScript**: Vanilla JS modules, no heavy frameworks

### Database Conventions
- Uses Laravel's default conventions
- SQLite for development/shared hosting compatibility
- MySQL support for larger deployments

## Common Development Tasks

### Adding New Features
1. Create migrations: `php artisan make:migration`
2. Update models with fillable fields and relationships
3. Add routes to `routes/web.php`
4. Create controllers or Livewire components as needed
5. Write tests in appropriate Feature/Unit/Browser directories

### Writing Browser Tests
Browser tests use Pest 4's Playwright integration. Key patterns:

```php
it('can perform user action', function () {
    // Create test data
    $user = User::factory()->create(['email' => 'test@example.com']);

    // Navigate and interact
    visit('/login')
        ->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/dashboard');
});
```

**Important API methods**:
- `visit($url)` - Initial page load (once per test)
- `navigate($url)` - Subsequent navigation within test
- `fill($selector, $value)` - Fill form fields
- `click($selector)` - Click elements
- `assertSee($text)` - Assert text is visible
- `assertPathIs($path)` - Assert current URL path
- `assertVisible($selector)` - Assert element is visible
- `assertNoAccessibilityIssues()` - Run WCAG accessibility checks
- `assertNoJavaScriptErrors()` - Assert no console errors

**Best Practices**:
- Always `assertPathIs('/')` after login to wait for redirect completion
- Use `navigate()` for page changes within a test (not `visit()`)
- Browser tests use `RefreshDatabase` trait automatically
- Tests run in isolated browser contexts
- Default timeout: 10 seconds (configured in tests/Pest.php)

### Photo Processing
- Images processed with PHP GD/Imagick
- Thumbnails generated automatically
- EXIF data extraction for taken_at timestamps
- Client-side cropping preserves original files

### Security Considerations
- All uploads go through security middleware
- CSRF protection on all forms
- File type validation for images
- Admin authorization for sensitive operations

## Troubleshooting

### Common Issues
- **Storage link missing**: Run `php artisan storage:link`
- **Permission errors**: Check storage and bootstrap/cache directories
- **Memory limits**: Configure PHP memory_limit for large image processing
- **HTTPS issues**: Verify TRUSTED_PROXIES and proxy headers in production

### Debug Routes
- `/debug-php`: Shows PHP configuration (upload limits, memory, etc.)

This guide should enable Claude Code instances to quickly understand the codebase architecture and development patterns for effective collaboration.