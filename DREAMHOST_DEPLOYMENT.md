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
yourdomain.com/          # Public Laravel files (public/ contents)
├── index.php            # Modified to point to ../laravel/
├── .htaccess
└── assets/              # Built CSS/JS

laravel/                 # Laravel application (non-public)
├── app/
├── config/
├── routes/
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

### Step 1: Prepare Local Build

```bash
# In your local development directory
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### Step 2: Upload Files

**Option A: Via SSH (Recommended)**
```bash
# Upload entire project to a temporary directory
scp -r family-photo-album yourusername@yoursite.com:~/
```

**Option B: Via FTP/SFTP**
- Upload all files except `.env`, `node_modules/`, `storage/app/public/photos/`
- Use FileZilla or similar FTP client

### Step 3: Set Up Directory Structure

```bash
# SSH into your Dreamhost account
ssh yourusername@yoursite.com

# Create Laravel directory outside web root
mv family-photo-album laravel

# Move public files to domain root
cp -r laravel/public/* yourdomain.com/
cp laravel/public/.htaccess yourdomain.com/

# Update index.php to point to correct location
```

### Step 4: Modify index.php

Edit `yourdomain.com/index.php`:

```php
<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Update these paths for Dreamhost structure
require __DIR__.'/../laravel/vendor/autoload.php';

$app = require_once __DIR__.'/../laravel/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
```

### Step 5: Environment Configuration

```bash
# Create and configure .env file
cd laravel
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
>>> $user->admin = true;
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
- Check `laravel/storage/logs/laravel.log`
- Verify file permissions (755 for directories, 644 for files)
- Ensure `.env` file exists and has correct database credentials

**Issue**: CSS/JS not loading
- Verify `APP_URL` in `.env` matches your domain
- Check that `yourdomain.com/build/` directory exists with assets
- Run `npm run build` locally and re-upload `build/` directory

**Issue**: Storage link not working
- Manually create symlink: `ln -sf ../laravel/storage/app/public yourdomain.com/storage`
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

## Updates and Backups

### Updating the Application

```bash
# Backup database first
mysqldump -h mysql.yoursite.com -u username -p database_name > backup.sql

# Upload new code
# Update dependencies if needed
composer install --no-dev --optimize-autoloader

# Clear and rebuild caches
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache

# Run any new migrations
php artisan migrate --force
```

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