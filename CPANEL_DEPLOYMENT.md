# TripBD — cPanel Production Deployment Manual & Guide

This document provides complete, production-verified step-by-step instructions for deploying **TripBD (Bangladesh On-Demand Transport & Logistics Platform)** to standard cPanel shared hosting (Apache, PHP 8.2+, MySQL 8+).

---

## 1. Architecture & Folder Hierarchy on cPanel

To ensure absolute security, the backend Laravel application is placed **outside** the public web root.

```text
/home/CPANEL_USER/
│
├── tripbd_backend/                         <-- OUTSIDE public_html (Secure Core)
│   ├── app/                                <-- Models, Controllers, Services, Gateways
│   ├── bootstrap/                          <-- app.php, providers.php, cache/
│   ├── config/                             <-- app.php, auth.php, database.php, cors.php, sanctum.php
│   ├── database/                           <-- migrations/, seeders/, factories/
│   ├── routes/                             <-- api.php, web.php, console.php
│   ├── storage/                            <-- app/, framework/ (cache, sessions, views), logs/
│   ├── vendor/                             <-- Composer packages (after composer install)
│   ├── .env                                <-- Environment config (Hidden & strictly non-public)
│   ├── .env.example                        <-- Safe template
│   ├── composer.json                       <-- PHP dependency manifest
│   └── artisan                             <-- CLI Artisan console
│
└── public_html/                            <-- Public Web Document Root
    ├── assets/                             <-- React SPA production bundles (JS, CSS, static media)
    ├── index.html                          <-- React SPA entry page
    ├── index.php                           <-- Laravel Front-Controller (bridges to ../tripbd_backend/)
    └── .htaccess                           <-- Apache URL Rewrite & Security directives
```

---

## 2. Server Requirements & PHP Extensions

TripBD is engineered with **zero external daemon dependencies**. It does **NOT** require:
- ❌ Docker
- ❌ PM2
- ❌ Supervisor
- ❌ Redis (uses standard MySQL/file storage)
- ❌ WebSockets (uses standard polling / HTTP state engine)
- ❌ Node.js server daemons

### Required PHP Settings
1. In cPanel, navigate to **Select PHP Version** or **MultiPHP Manager**.
2. Select **PHP 8.2** or **PHP 8.3**.
3. In **PHP Extensions / Options**, ensure the following extensions are enabled:
   - `pdo_mysql` (MySQL 8 database connection)
   - `bcmath` (High-precision BDT financial fare & commission calculations)
   - `mbstring` (UTF-8 string and Bengali typography handling)
   - `openssl` (Sanctum encryption and token hashing)
   - `tokenizer` & `xml` (Core Laravel framework)
   - `ctype` & `json` (REST API serialization)
   - `fileinfo` (Strict MIME-type validation for driver NID & license uploads)
   - `curl` (MFS Payment Gateways: bKash, Nagad, Rocket, SSLCommerz)
4. Recommended PHP settings in **MultiPHP INI Editor**:
   - `memory_limit = 256M`
   - `upload_max_filesize = 10M`
   - `post_max_size = 12M`
   - `max_execution_time = 60`

---

## 3. Database Setup (MySQL 8+ / MariaDB 10.4+)

1. In cPanel, go to **MySQL® Databases**.
2. Under **Create New Database**, enter: `tripbd` (Full DB name: `CPANEL_USER_tripbd`). Click **Create Database**.
3. Under **Add New User**, create a database user: `CPANEL_USER_tripbd_user` with a strong password. Click **Create User**.
4. Under **Add User To Database**:
   - Select User: `CPANEL_USER_tripbd_user`
   - Select Database: `CPANEL_USER_tripbd`
   - Click **Add**.
   - Check **ALL PRIVILEGES** and click **Make Changes**.

---

## 4. Upload & File Extraction

### Option A: Using `TripBD-cpanel-production.zip` via File Manager
1. Open **cPanel File Manager**.
2. In `/home/CPANEL_USER/`, click **Upload** and upload `TripBD-cpanel-production.zip`.
3. Select the uploaded ZIP file and click **Extract** into `/home/CPANEL_USER/`.
4. This will extract two directories:
   - `/home/CPANEL_USER/tripbd_backend/`
   - `/home/CPANEL_USER/public_html/` (or merge into your existing `public_html/`).
5. Delete the `.zip` archive after extraction.

---

## 5. Storage Permissions

In cPanel File Manager (or Terminal), set appropriate write permissions for Laravel storage:

```bash
chmod -R 775 /home/CPANEL_USER/tripbd_backend/storage
chmod -R 775 /home/CPANEL_USER/tripbd_backend/bootstrap/cache
```

*(Note: In cPanel File Manager, right-click the `storage` and `bootstrap/cache` directories, select **Change Permissions**, and ensure User & Group have Read, Write, and Execute — 0775).*

---

## 6. Environment Configuration (`.env`)

1. In `tripbd_backend/`, rename `.env.example` to `.env` (or create a new `.env`).
2. Open `.env` in cPanel Code Editor and configure your production values:

```env
APP_NAME=TripBD
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATE_VIA_ARTISAN_KEY_GEN_OR_CPANEL_SETUP
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# MySQL 8 Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=CPANEL_USER_tripbd
DB_USERNAME=CPANEL_USER_tripbd_user
DB_PASSWORD=YOUR_STRONG_DATABASE_PASSWORD

# Native cPanel Drivers (Zero Redis required)
BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Google Maps API
GOOGLE_MAPS_API_KEY=YOUR_GOOGLE_MAPS_KEY

# SMS / OTP Provider (Bangladeshi SMS Gateway)
SMS_GATEWAY_PROVIDER=greenweb
SMS_API_KEY=YOUR_SMS_API_KEY
SMS_SENDER_ID=TripBD

# cPanel Webmail SMTP Configuration
MAIL_MAILER=smtp
MAIL_HOST=mail.your-domain.com
MAIL_PORT=465
MAIL_USERNAME=noreply@your-domain.com
MAIL_PASSWORD=YOUR_EMAIL_PASSWORD
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Payment Gateway Configuration (Sandbox Mode by default)
PAYMENT_ENV=sandbox

# bKash Tokenized Checkout
BKASH_APP_KEY=
BKASH_APP_SECRET=
BKASH_USERNAME=
BKASH_PASSWORD=
BKASH_BASE_URL=https://tokenized.sandbox.bka.sh/v1.2.0-beta
BKASH_CALLBACK_URL=https://your-domain.com/api/v1/payments/bkash/callback

# Nagad DFS Merchant Gateway
NAGAD_MERCHANT_ID=
NAGAD_PUBLIC_KEY=
NAGAD_PRIVATE_KEY=
NAGAD_BASE_URL=http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs
NAGAD_CALLBACK_URL=https://your-domain.com/api/v1/payments/nagad/callback

# Rocket Merchant Gateway
ROCKET_MERCHANT_ID=
ROCKET_BASE_URL=https://sandbox.dutchbanglabank.com/rocket

# SSLCOMMERZ Hosted Gateway
CARD_GATEWAY=sslcommerz
SSLCOMMERZ_STORE_ID=
SSLCOMMERZ_STORE_PASS=

# Platform Financial Defaults
PLATFORM_COMMISSION_PERCENT=15.00
MINIMUM_WITHDRAWAL=500.00
MAXIMUM_WITHDRAWAL=50000.00
CURRENCY=BDT
```

---

## 7. Public Index & Apache Rewrite (`.htaccess`)

Verify that `/home/CPANEL_USER/public_html/index.php` contains the external loader:

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Autoloader from tripbd_backend
if (file_exists(__DIR__.'/../tripbd_backend/vendor/autoload.php')) {
    require __DIR__.'/../tripbd_backend/vendor/autoload.php';
}

// Bootstrap from tripbd_backend
if (file_exists(__DIR__.'/../tripbd_backend/bootstrap/app.php')) {
    $app = require_once __DIR__.'/../tripbd_backend/bootstrap/app.php';
}

$app->handleRequest(Request::capture());
```

Verify that `/home/CPANEL_USER/public_html/.htaccess` contains:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Pass Authorization Header for Sanctum
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Route API and Sanctum requests to Laravel index.php
    RewriteCond %{REQUEST_URI} ^/api/ [OR]
    RewriteCond %{REQUEST_URI} ^/sanctum/
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]

    # Route all other frontend SPA requests to index.html
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.html [L]
</IfModule>
```

---

## 8. Database Migrations, Seeding & Optimization

If you have cPanel **Terminal** or SSH access:

```bash
cd /home/CPANEL_USER/tripbd_backend

# 1. Install Composer dependencies (if not pre-packaged)
composer install --no-dev --optimize-autoloader

# 2. Generate Application Key (if not already set)
php artisan key:generate

# 3. Run Migrations (NEVER run migrate:fresh in production!)
php artisan migrate --force

# 4. Seed Essential Base Data (Vehicle Types, Service Categories, Locations, System Settings)
php artisan db:seed --force

# 5. Create Storage Symlink
php artisan storage:link

# 6. Cache Configurations and Routes for High Performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

*(If Terminal is disabled on your hosting plan, import the initial `database/production.sql` directly into phpMyAdmin, and set the `APP_KEY` in `.env`).*

---

## 9. cPanel Cron Job Configuration

To automate background tasks (e.g. driver matching timeouts, return trip alert notifications, OTP expiration cleanup):

1. In cPanel, navigate to **Cron Jobs**.
2. Under **Add New Cron Job**:
   - Common Settings: **Once Per Minute** (`* * * * *`)
   - Command:
     ```bash
     /usr/local/bin/php /home/CPANEL_USER/tripbd_backend/artisan schedule:run >> /dev/null 2>&1
     ```
3. Click **Add New Cron Job**.

---

## 10. Post-Deployment Endpoint Verification

Verify that the platform is operating normally by testing these critical endpoints:

| Endpoint | Method | Expected Output / Status |
|---|:---:|---|
| `/` | `GET` | 200 OK — React SPA Landing / Dashboard loads |
| `/api/v1/auth/register` | `POST` | 201 Created — Customer / Driver registration |
| `/api/v1/auth/login` | `POST` | 200 OK — Returns Sanctum Auth Token & User object |
| `/api/v1/me` | `GET` | 200 OK — Returns authenticated user profile |
| `/api/v1/bookings` | `POST` | 201 Created — Dispatches booking & fare calculation |
| `/api/v1/payments/create` | `POST` | 200 OK / 201 Created — Creates transaction record |

---

## 11. Deployment Checklist

- [x] Backend extracted to `/home/CPANEL_USER/tripbd_backend/` (outside `public_html`).
- [x] Frontend static build & `index.html` placed in `/home/CPANEL_USER/public_html/`.
- [x] `public_html/index.php` bridges to `../tripbd_backend/`.
- [x] Storage permissions set to `0775` on `storage` and `bootstrap/cache`.
- [x] `.env` created with `APP_DEBUG=false` and `PAYMENT_ENV=sandbox`.
- [x] MySQL database created with ALL PRIVILEGES granted to user.
- [x] Migrations executed safely via `php artisan migrate --force` (NO `migrate:fresh`).
- [x] Apache `.htaccess` configured with Authorization header pass-through and SPA rewrite.
- [x] Zero external daemons (No Docker, No PM2, No Redis, No WebSockets).
- [x] Cron job configured for `artisan schedule:run`.

