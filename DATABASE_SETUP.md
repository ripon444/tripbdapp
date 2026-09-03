# TripBD — Phase 2 Complete Database Architecture & Setup Guide

TripBD uses MySQL 8+ in strict mode with full UTF8MB4 Unicode support (`utf8mb4_unicode_ci`), essential for accurate Bengali language text addresses, names, vehicle plate characters, and multi-service logistics telemetry.

---

## 1. Database Configuration via `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tripbd_production
DB_USERNAME=tripbd_dbuser
DB_PASSWORD=Secure_Password_2026!
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

---

## 2. Table Directory & Entity-Relationship Architecture (24 Tables)

| # | Table Name | Purpose & Primary Entities | Key Indexes & Foreign Keys | Soft Delete |
|---|------------|----------------------------|----------------------------|:-----------:|
| 1 | `users` | Base account for Customer, Driver, Admin | `phone` (unique), `email` (unique), `role`, `status` | No |
| 2 | `customer_profiles` | Customer details, address, DOB | `user_id` (FK -> users.id CASCADE) | No |
| 3 | `driver_profiles` | Driver NID, License, Verification, Rating | `user_id` (FK -> users.id CASCADE), `online_status`, `verification_status` | No |
| 4 | `service_categories` | 8 Core Services (Truck, Ambulance, Private Car, etc.) | `slug` (unique), `status`, `sort_order` | No |
| 5 | `vehicle_types` | 14 Vehicle categories with rate matrix (Base, KM, Hour) | `service_category_id` (FK CASCADE), `slug` (unique) | No |
| 6 | `vehicles` | Driver vehicle inventory, plate number, specs | `driver_id` (FK CASCADE), `vehicle_type_id` (FK CASCADE), `registration_number` (unique) | **Yes** |
| 7 | `driver_documents` | NID, Driving license file proofs | `driver_id` (FK CASCADE), `document_type`, `verification_status` | No |
| 8 | `vehicle_documents` | BRTA fitness, tax token, insurance, registration | `vehicle_id` (FK CASCADE), `document_type`, `verification_status` | No |
| 9 | `locations` | Divisions, Districts, Upazilas with GPS Coordinates | `district`, `division`, `[latitude, longitude]`, `type` | No |
| 10 | `bookings` | Complete ride & freight trip lifecycle | `booking_number` (unique), `customer_id` (FK), `driver_id` (FK), `service_category_id` (FK), `vehicle_type_id` (FK), `status`, `[pickup_lat, pickup_lng]` | **Yes** |
| 11 | `booking_status_history` | Audit trail for state changes (pending -> completed) | `booking_id` (FK CASCADE), `changed_by_user_id` (FK SET NULL), `created_at` | No |
| 12 | `driver_locations` | Real-time GPS coordinate telemetry (cPanel polling) | `driver_id` (FK CASCADE), `booking_id` (FK SET NULL), `[driver_id, recorded_at]` | No |
| 13 | `payments` | Transactions (Cash, bKash, Nagad, Rocket, Card) | `booking_id` (FK CASCADE), `customer_id` (FK CASCADE), `transaction_id`, `status` | No |
| 14 | `wallets` | Driver wallet balance ledger | `driver_id` (FK -> users.id UNIQUE CASCADE) | No |
| 15 | `wallet_transactions` | Credit/debit records (earnings, commissions, withdrawals) | `wallet_id` (FK CASCADE), `type`, `[reference_type, reference_id]` | No |
| 16 | `ratings` | 1-5 star feedback with written reviews | `booking_id` (FK CASCADE), `customer_id` (FK), `driver_id` (FK), `rating` | No |
| 17 | `complaints` | Customer & Driver dispute tickets | `user_id` (FK CASCADE), `booking_id` (FK SET NULL), `status` | No |
| 18 | `notifications` | Morphic notification queue | `[notifiable_type, notifiable_id]`, `user_id`, `read_at` | No |
| 19 | `promo_codes` | Coupon & discount engine | `code` (unique), `status`, `[starts_at, expires_at]` | No |
| 20 | `promo_code_usages` | Customer promo usage audit | `[promo_code_id, user_id]`, `booking_id` (FK CASCADE) | No |
| 21 | `system_settings` | Dynamic fare multipliers & system configuration | `key` (unique) | No |
| 22 | `admin_logs` | Security audit trail for admin actions | `admin_id` (FK CASCADE), `module`, `created_at` | No |
| 23 | `personal_access_tokens` | Laravel Sanctum token storage | `token` (unique), `[tokenable_type, tokenable_id]` | No |
| 24 | `failed_jobs` | Background queue failure logs | `uuid` (unique) | No |
| 25 | `withdrawals` | Driver payout requests (MFS & Bank) | `withdrawal_number` (unique), `driver_id` (FK), `wallet_id` (FK), `status` | No |
| 26 | `financial_settlements` | Immutable booking revenue splits (Driver / Platform) | `settlement_reference` (unique), `booking_id` (FK), `driver_id` (FK), `status` | No |
| 27 | `refunds` | Full and partial payment refunds ledger | `refund_reference` (unique), `payment_id` (FK), `booking_id` (FK), `status` | No |

---

## 3. Database Execution & Deployment

### Option A: Laravel Artisan CLI (cPanel Terminal or SSH)
```bash
# Run all migrations safely (NEVER run migrate:fresh on production!)
php artisan migrate --force

# Seed initial service categories, vehicle types, Bangladesh locations, and system settings
php artisan db:seed --force

# Run automated database unit & feature tests
php artisan test --filter=DatabaseSchemaTest
php artisan test --filter=ModelRelationshipTest
```

### Option B: 1-Click Import via phpMyAdmin
For standard cPanel hosting without SSH:
1. Log into **cPanel** > **Databases** > **phpMyAdmin**.
2. Select your provisioned database (e.g. `USERNAME_tripbd`).
3. Click the **Import** tab.
4. Choose `database/production.sql` from your local machine.
5. Ensure Character Set is set to `utf-8` and click **Go**.

---

## 4. Eloquent Relationship Summary

- **User**:
  - `hasOne(CustomerProfile::class)`
  - `hasOne(DriverProfile::class)`
  - `hasOne(Wallet::class, 'driver_id')`
  - `hasMany(Booking::class, 'customer_id')` / `hasMany(Booking::class, 'driver_id')`
  - `hasMany(Payment::class, 'customer_id')`
  - `hasMany(Rating::class, 'driver_id')`
- **DriverProfile**:
  - `belongsTo(User::class)`
  - `hasMany(Vehicle::class, 'driver_id')`
  - `hasOne(Vehicle::class, 'driver_id')->where('status', 'active')`
  - `hasMany(DriverDocument::class, 'driver_id')`
- **Booking**:
  - `belongsTo(User::class, 'customer_id')`
  - `belongsTo(User::class, 'driver_id')`
  - `belongsTo(Vehicle::class)`
  - `belongsTo(ServiceCategory::class)`
  - `belongsTo(VehicleType::class)`
  - `hasMany(BookingStatusHistory::class)`
  - `hasMany(Payment::class)`
  - `hasOne(Payment::class)->latestOfMany()`
  - `hasMany(Rating::class)`
  - `hasMany(Complaint::class)`
  - `hasOne(PromoCodeUsage::class)`
  - `hasMany(DriverLocation::class)`
