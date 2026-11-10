# Trattoria Gusto — Digital Menu System

A complete, production-oriented digital menu system for Trattoria Gusto.
Customers scan a QR code and instantly browse the menu on any device — no app required.

---

## Technology Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2+ |
| Database | MySQL 8.x / MariaDB |
| Web Server | Apache (WampServer) |
| Frontend | HTML5, CSS3, Bootstrap 5.3, Vanilla JS |
| QR Codes | chillerlan/php-qrcode |
| Env Config | vlucas/phpdotenv |
| Dependencies | Composer |

---

## Directory Structure

```
kaldis-menu/
├── app/
│   ├── Controllers/     # Request handlers
│   ├── Models/          # (reserved)
│   ├── Services/        # Business logic (ImportService)
│   ├── Middleware/      # AuthMiddleware
│   ├── Repositories/    # Database query objects
│   └── Helpers/         # Env, Database, Logger, Session, Csrf, Router, View, Http
├── config/
│   ├── app.php          # Application configuration
│   └── database.php     # DB connection configuration
├── database/
│   └── migrations/      # Versioned SQL migration files
├── public/              # ← Apache web root (only this dir is public)
│   ├── index.php        # Single entry point
│   ├── .htaccess        # Apache rewrite + security rules
│   └── assets/          # CSS, JS, images
│       └── uploads/products/  # Product images (uploaded by admin)
├── resources/
│   └── views/           # PHP view templates
│       ├── layouts/     # Base HTML layouts
│       ├── menu/        # Public menu views
│       ├── admin/       # Admin dashboard views
│       └── auth/        # Login views
├── routes/
│   └── web.php          # All route definitions
├── scripts/
│   ├── install_db.php   # Database installer (run once)
│   └── import_products.php  # CLI product import
├── storage/
│   ├── logs/            # Daily application logs (not web-accessible)
│   ├── cache/           # Import lock files etc.
│   └── qrcodes/         # Generated QR code PNG files
├── vendor/              # Composer dependencies
├── .env                 # Local secrets (NOT in Git)
├── .env.example         # Template (committed to Git)
├── .gitignore
├── composer.json
└── README.md
```

---

## WampServer Setup (Windows)

### Prerequisites

1. **WampServer 3.x** installed at `C:\wamp64`
2. PHP 8.2+ active in WampServer
3. MySQL/MariaDB service running
4. Apache service running

### 1. Place the Project

Clone or copy the project to:
```
C:\wamp64\www\kaldis-menu\
```

### 2. Configure Apache Virtual Host (recommended)

Open WampServer tray → Apache → `httpd-vhosts.conf`, add:

```apache
<VirtualHost *:80>
    ServerName kaldis.local
    DocumentRoot "C:/wamp64/www/kaldis-menu/public"
    <Directory "C:/wamp64/www/kaldis-menu/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Add `127.0.0.1 kaldis.local` to `C:\Windows\System32\drivers\etc\hosts`.

Or access directly at: `http://localhost/kaldis-menu/public`

### 3. Enable `mod_rewrite`

WampServer tray → Apache → Apache modules → `rewrite_module` ✓

### 4. Configure Environment

```bash
# Copy example to real .env
copy .env.example .env
```

Edit `.env` and set at minimum:
```
APP_DB_PASSWORD=          # your WampServer MySQL root password (often empty)
MYSQL_PASSWORD=           # the source DB password (provided by IT)
APP_URL=http://localhost/kaldis-menu/public
```

### 5. Install Dependencies

```bash
cd C:\wamp64\www\kaldis-menu
composer install
```

### 6. Install Local Database

```bash
php scripts/install_db.php
```

This creates the `kaldis_menu` database, runs all migrations, and seeds a default admin user.

### 6b. Seed Sample Data (Local Development & Demo)

If the remote source database is currently unreachable or password-protected:
```bash
php scripts/seed_sample_data.php
```

Populates 5 Kaldis branches, 6 parent categories, 10 child categories, and 26 realistic coffee and food items with verified pricing and descriptions.

### 6c. Run Automated Verification Tests

```bash
php tests/SystemTest.php
php tests/RouteTest.php
```

### 7. First Login

Visit: `http://localhost/kaldis-menu/public/admin`

| Field | Value |
|---|---|
| Email | admin@kaldis.coffee |
| Password | Admin@Kaldis2024 |

**Change this password immediately.**

---

## Product Import

### Manual Import (Admin UI)

1. Log in to the admin dashboard
2. Navigate to **Import → Sync Products**
3. Click **Dry Run** first to preview changes
4. Click **Run Sync** to import

### CLI Import

```bash
# Dry run (preview only, no changes)
php scripts/import_products.php --dry-run

# Real import
php scripts/import_products.php
```

### Scheduled Import (Windows Task Scheduler)

1. Open Task Scheduler
2. Create Basic Task → Daily (or desired frequency)
3. Action: Start a program
   - Program: `C:\wamp64\bin\php\php8.2.29\php.exe`
   - Arguments: `C:\wamp64\www\kaldis-menu\scripts\import_products.php`
4. The script logs results to `storage/logs/`

---

## Source Database

The system reads from an existing remote MySQL database (read-only):

| Setting | Value |
|---|---|
| Host | 91.204.209.46 |
| Port | 3306 |
| Database | kaldisbp_cnet_synced_data |
| Table | article_import |

Configure credentials in `.env` (never commit passwords to Git).

The import service:
- Reads in batches of 200 rows
- Uses upsert (INSERT … ON DUPLICATE KEY UPDATE)
- Preserves admin-managed fields (descriptions, images, visibility, prices)
- Prevents duplicate products on repeated runs
- Logs every import with full statistics

---

## QR Codes

1. Admin → QR Codes → Generate
2. Select type: General Menu or Branch-specific
3. Download PNG for printing
4. QR code points to `APP_URL/menu` or `APP_URL/menu?branch=BRANCH_CODE`

Update `QR_PUBLIC_BASE_URL` in `.env` when deploying to production.

---

## Security Notes

- Passwords are hashed with `bcrypt` (cost 12)
- CSRF tokens on all state-changing forms
- PDO prepared statements throughout
- Admin routes protected server-side (middleware)
- Login rate limiting: 5 attempts → 15 minute lockout
- `.env` and private directories blocked by `.htaccess`
- Session cookies: HttpOnly, SameSite=Lax, Secure in production
- File uploads: MIME type validation + size limit

---

## Production Deployment

1. Point Apache DocumentRoot to `public/` only
2. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
3. Configure HTTPS and update `APP_URL` and `QR_PUBLIC_BASE_URL` to HTTPS URLs
4. Set cookie `secure=true` (automatic when `$_SERVER['HTTPS']` is set)
5. Run `composer install --no-dev --optimize-autoloader`
6. Set folder permissions: `storage/` writable by web server, everything else read-only
7. Set up a scheduled task for regular product imports

### Database Backup

```bash
mysqldump -u root -p kaldis_menu > backup_$(date +%Y%m%d).sql
```

Restore:
```bash
mysql -u root -p kaldis_menu < backup_YYYYMMDD.sql
```

---

## Default Admin Credentials

> ⚠️ **Change immediately after first login.**

- Email: `admin@kaldis.coffee`
- Password: `Admin@Kaldis2024`

---

## License

Proprietary — Trattoria Gusto

