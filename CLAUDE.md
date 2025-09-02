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
- **Testing**: Pest PHP + Vitest (JavaScript)
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
composer test                 # Run Pest tests
composer run test:coverage    # Generate PHP coverage reports
./vendor/bin/pest            # Direct Pest execution

# Code quality
./vendor/bin/pint            # Laravel Pint code formatting

# Laravel commands
php artisan migrate          # Run migrations
php artisan tinker          # Laravel REPL
php artisan storage:link    # Link storage directory
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
├── Feature/            # Integration tests
├── Unit/              # Unit tests
└── js/                # JavaScript tests (Vitest)

database/
├── migrations/         # Database schema
├── factories/         # Test data factories
└── seeders/           # Database seeders
```

### Important Files
- **composer.json**: PHP dependencies, custom scripts
- **package.json**: Node dependencies, build scripts
- **vite.config.js**: Vite + Tailwind configuration
- **vitest.config.js**: JavaScript test configuration
- **phpunit.xml**: PHP test configuration
- **deploy.sh**: Production deployment script
- **.env.example**: Environment configuration template

## Core Features

### Photo Management Flow
1. **Upload**: Multi-file upload with progress tracking
2. **Crop**: Client-side cropping with CropperJS
3. **Caption**: Add caption and edit date/time
4. **Completion**: Mark as completed to show in public feed

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
- **Feature Tests**: Full request/response testing
- **Unit Tests**: Individual component testing
- **Coverage**: HTML reports in `reports/coverage/html/`

### JavaScript Tests (Vitest)
- **Unit Tests**: Module testing with jsdom
- **Coverage**: V8 coverage reports
- **Configuration**: Test environment setup in `tests/js/setup.js`

### Test Organization
```bash
tests/
├── Feature/
│   ├── HomePageInfiniteScrollTest.php
│   ├── PhotoShowTest.php
│   ├── UploadFlowTest.php
│   └── Settings/ProfileUpdateTest.php
└── Unit/
    ├── AllowedEmailTest.php
    └── ExampleTest.php
```

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
5. Write tests in appropriate Feature/Unit directories

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