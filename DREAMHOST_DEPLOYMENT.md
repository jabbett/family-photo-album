# Dreamhost Shared Hosting Deployment Guide

This guide walks you through deploying the Family Photo Album app on Dreamhost shared hosting.

## Prerequisites

- Dreamhost shared hosting account with PHP 8.2+ support
- Domain configured with Dreamhost
- SSH access enabled (recommended but not required)

## Dreamhost-Specific Considerations

### Directory Structure
Dreamhost shared hosting expects your public files in the domain's root directory (e.g., `yourdomain.com/`). Laravel's structure needs to be adapted:

```
yourdomain.com/                    # Public Laravel files (public/ contents)
├── index.php                      # Modified to point to ../family-photo-album/
├── .htaccess
└── assets/                        # Built CSS/JS

family-photo-album/                # Laravel application (non-public)
├── app/
├── config/
├── routes/
└── ...

# For multiple Laravel apps:
anotherdomain.com/                 # Another domain's public files
├── index.php                      # Points to ../my-other-laravel-app/

my-other-laravel-app/              # Second Laravel app
├── app/
├── config/
└── ...
```

### PHP Version
Ensure your domain is using PHP 8.2 or higher:
1. Go to Dreamhost Panel → Manage Domains
2. Click "Edit" next to your domain
3. Set PHP version to 8.2 or higher

### Database Setup
1. In Dreamhost Panel, create a MySQL database:
   - Go to Advanced → MySQL Databases
   - Create database: `username_photoalbum`
   - Create user with full permissions
   - Note the hostname (usually `mysql.yoursite.com`)

## Deployment Steps

### Step 1: Prepare Complete Local Build

```bash
# In your family-photo-album directory
composer install --no-dev --optimize-autoloader
npm run build
```

### Step 2: Upload Complete Project

```bash
# From within your family-photo-album directory
rsync -avz --exclude-from=.gitignore --exclude='.git/' ./ yourusername@yoursite.com:~/family-photo-album/
```

### Step 3: Set Up Directory Structure

```bash
# SSH into your server
ssh yourusername@yoursite.com

# Move public files to domain root
cp -r family-photo-album/public/* yourdomain.com/
cp family-photo-album/public/.htaccess yourdomain.com/

# Update index.php to point to correct location
```

### Step 4: Modify index.php

Edit `yourdomain.com/index.php` to update the paths for Dreamhost structure:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
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

### Step 5: Environment Configuration

```bash
# Create and configure .env file
cd family-photo-album
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your Dreamhost database settings:

```env
APP_NAME="Family Photo Album"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=mysql.yoursite.com
DB_PORT=3306
DB_DATABASE=username_photoalbum
DB_USERNAME=username_dbuser
DB_PASSWORD=your_db_password

# Mail settings (use Dreamhost SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.dreamhost.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Family Photo Album"

# Security settings for production
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
TRUSTED_PROXIES="*"
```

### Step 6: Database Setup

```bash
# Run migrations
php artisan migrate --force

# Create storage link (adjusted for Dreamhost structure)
php artisan storage:link
```

### Step 7: File Permissions

```bash
# Set correct permissions
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
```

### Step 8: Create Admin User

```bash
# Create first admin user
php artisan tinker
>>> $user = new App\Models\User();
>>> $user->name = 'Admin';
>>> $user->email = 'your-admin@email.com';
>>> $user->password = bcrypt('your-secure-password');
>>> $user->email_verified_at = now();
>>> $user->is_admin = true;
>>> $user->save();
>>> exit
```

## Optimization for Shared Hosting

### Caching Configuration

```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### .htaccess Optimization

Add to `yourdomain.com/.htaccess`:

```apache
# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Browser caching for static assets
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
</IfModule>
```

## Maintenance Commands

Regular maintenance tasks:

```bash
# Clear caches after updates
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Re-cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check disk usage
du -sh storage/app/public/photos/
```

## Troubleshooting

### Common Issues

**Issue**: 500 Internal Server Error
- Check `family-photo-album/storage/logs/laravel.log`
- Verify file permissions (755 for directories, 644 for files)
- Ensure `.env` file exists and has correct database credentials

**Issue**: CSS/JS not loading
- Verify `APP_URL` in `.env` matches your domain
- Check that `yourdomain.com/build/` directory exists with assets
- Run `npm run build` locally and re-upload `build/` directory

**Issue**: Storage link not working
- Manually create symlink: `ln -sf ../family-photo-album/storage/app/public yourdomain.com/storage`
- Verify permissions on storage directories

**Issue**: Database connection errors
- Double-check database credentials in `.env`
- Test connection: `php artisan tinker` then `DB::connection()->getPdo()`
- Contact Dreamhost support if MySQL service is down

### Performance Tips

1. **Enable OPcache**: Contact Dreamhost support to enable PHP OPcache
2. **Use CDN**: Consider Cloudflare for static asset delivery
3. **Image Optimization**: Compress uploaded images before storage
4. **Database Indexing**: Monitor slow queries and add indexes as needed

## Your Deployment Workflow

Since your shared server lacks Composer and modern Node.js, we'll build everything locally and upload the complete project.

### Local Development & Deployment

```bash
# Make your changes locally
# Test with: composer run dev

# Build complete project for production
composer install --no-dev --optimize-autoloader
npm run build

# Upload project excluding development files (from within family-photo-album directory)
rsync -avz --exclude-from=.gitignore --exclude='.git/' ./ yourusername@yoursite.com:~/temp-upload/

# SSH in and replace current version
ssh yourusername@yoursite.com
rm -rf family-photo-album-backup
mv family-photo-album family-photo-album-backup  # Backup current version
mv temp-upload family-photo-album                # Deploy new version

# Copy public files to web root
cp -r family-photo-album/public/* yourdomain.com/
```

### Why This Approach

- ✅ **Simple**: No server dependencies to manage
- ✅ **Complete**: Everything built and tested locally first
- ✅ **Safe**: Keeps backup of previous version
- ✅ **Fast**: No build time on server

## Updates

### Server Setup: Upload Update Script

First, upload the server-side update script:

```bash
# Upload the server update script
scp server-scripts/update-photo-album.sh yourusername@yoursite.com:~/update-photo-album.sh
ssh yourusername@yoursite.com chmod +x ~/update-photo-album.sh
```

### The Update Script

```bash
#!/bin/bash
set -e

echo "🔄 Starting Family Photo Album update..."

# Backup database
echo "📦 Backing up database..."
mkdir -p ~/backups
mysqldump -h mysql.yoursite.com -u $DB_USER -p$DB_PASS $DB_NAME > ~/backups/db-$(date +%Y%m%d-%H%M%S).sql

# Backup current version
echo "💾 Backing up current version..."
rm -rf family-photo-album-backup
mv family-photo-album family-photo-album-backup

# Move new version into place
echo "📁 Installing new version..."
mv temp-upload family-photo-album

# Run migrations
echo "🗃️  Running migrations..."
cd family-photo-album
php artisan migrate --force

# Copy public assets to web root
echo "📁 Copying public assets..."
cp -r public/* ~/yourdomain.com/

echo "✅ Update complete!"
```

Make it executable:
```bash
chmod +x ~/update-photo-album.sh
```

### Your Update Workflow

```bash
# Local: Build and upload new version (from within family-photo-album directory)
./deploy.sh

# Server: Deploy new version
ssh yourusername@yoursite.com
~/update-photo-album.sh
```

The `deploy.sh` script handles building dependencies, assets, and uploading with the correct exclusions for Dreamhost deployment.

### Backup Strategy

1. **Database**: Weekly automated backups via Dreamhost panel
2. **Photos**: Regular downloads of `storage/app/public/photos/`
3. **Configuration**: Keep `.env` file backed up securely

## Security Checklist

- ✅ `.env` file is not in web root and not accessible via web
- ✅ `APP_DEBUG=false` in production
- ✅ Strong database password
- ✅ HTTPS enabled (Dreamhost provides free SSL)
- ✅ Regular updates of dependencies
- ✅ Admin user list reviewed periodically

## Support

- **Dreamhost Knowledge Base**: [https://help.dreamhost.com/](https://help.dreamhost.com/)
- **Laravel Documentation**: [https://laravel.com/docs](https://laravel.com/docs)
- **Project Issues**: [https://github.com/jabbett/family-photo-album/issues](https://github.com/jabbett/family-photo-album/issues)