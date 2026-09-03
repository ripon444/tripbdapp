-- ==========================================================
-- TRIPBD ENTERPRISE PRODUCTION DATABASE DUMP (PHASE 2)
-- Collation: utf8mb4_unicode_ci
-- Engine: InnoDB
-- Architecture: cPanel MySQL 8.0+ / MariaDB 10.4+ Compatible
-- Generated For: TripBD All-in-One Transport & Logistics
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+06:00";

-- --------------------------------------------------------
-- 1. Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(255) NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('customer', 'driver', 'admin') NOT NULL DEFAULT 'customer',
  `status` ENUM('active', 'inactive', 'suspended', 'pending') NOT NULL DEFAULT 'active',
  `phone_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_phone_unique` (`phone`),
  UNIQUE KEY `users_email_unique` (`email`),
  INDEX `users_phone_idx` (`phone`),
  INDEX `users_role_idx` (`role`),
  INDEX `users_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Table: customer_profiles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customer_profiles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `profile_photo` VARCHAR(500) NULL DEFAULT NULL,
  `address` TEXT NULL DEFAULT NULL,
  `city` VARCHAR(100) NULL DEFAULT NULL,
  `district` VARCHAR(100) NULL DEFAULT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'Bangladesh',
  `date_of_birth` DATE NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_profiles_user_id_unique` (`user_id`),
  INDEX `customer_profiles_district_idx` (`district`),
  CONSTRAINT `fk_customer_profiles_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Table: driver_profiles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `driver_profiles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `profile_photo` VARCHAR(500) NULL DEFAULT NULL,
  `address` TEXT NULL DEFAULT NULL,
  `city` VARCHAR(100) NULL DEFAULT NULL,
  `district` VARCHAR(100) NULL DEFAULT NULL,
  `nid_number` VARCHAR(50) NOT NULL,
  `license_number` VARCHAR(50) NOT NULL,
  `license_expiry` DATE NOT NULL,
  `verification_status` ENUM('pending', 'approved', 'rejected', 'suspended') NOT NULL DEFAULT 'pending',
  `online_status` ENUM('online', 'offline', 'busy') NOT NULL DEFAULT 'offline',
  `rating` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
  `total_trips` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `driver_profiles_user_id_unique` (`user_id`),
  INDEX `driver_profiles_verification_idx` (`verification_status`),
  INDEX `driver_profiles_online_idx` (`online_status`),
  INDEX `driver_profiles_district_idx` (`district`),
  INDEX `driver_profiles_rating_idx` (`rating`),
  CONSTRAINT `fk_driver_profiles_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Table: service_categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `service_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `icon` VARCHAR(100) NOT NULL,
  `image` VARCHAR(500) NULL DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_categories_slug_unique` (`slug`),
  INDEX `service_categories_status_idx` (`status`),
  INDEX `service_categories_sort_idx` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Table: vehicle_types
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicle_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `service_category_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `icon` VARCHAR(100) NULL DEFAULT NULL,
  `image` VARCHAR(500) NULL DEFAULT NULL,
  `passenger_capacity` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `load_capacity` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'In Tons or KG',
  `base_fare` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `per_km_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `per_hour_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `minimum_fare` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_types_slug_unique` (`slug`),
  INDEX `vehicle_types_cat_id_idx` (`service_category_id`),
  INDEX `vehicle_types_status_idx` (`status`),
  CONSTRAINT `fk_vehicle_types_cat_id` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Table: vehicles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `driver_id` BIGINT UNSIGNED NOT NULL,
  `vehicle_type_id` BIGINT UNSIGNED NOT NULL,
  `vehicle_number` VARCHAR(100) NOT NULL,
  `brand` VARCHAR(100) NOT NULL,
  `model` VARCHAR(100) NOT NULL,
  `year` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `color` VARCHAR(50) NULL DEFAULT NULL,
  `passenger_capacity` SMALLINT UNSIGNED NOT NULL DEFAULT 4,
  `load_capacity` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `registration_number` VARCHAR(100) NOT NULL,
  `verification_status` ENUM('pending', 'approved', 'rejected', 'suspended') NOT NULL DEFAULT 'pending',
  `status` ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_reg_number_unique` (`registration_number`),
  INDEX `vehicles_driver_id_idx` (`driver_id`),
  INDEX `vehicles_type_id_idx` (`vehicle_type_id`),
  INDEX `vehicles_verification_idx` (`verification_status`),
  INDEX `vehicles_status_idx` (`status`),
  CONSTRAINT `fk_vehicles_driver_id` FOREIGN KEY (`driver_id`) REFERENCES `driver_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vehicles_type_id` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Table: driver_documents
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `driver_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `driver_id` BIGINT UNSIGNED NOT NULL,
  `document_type` ENUM('nid', 'driving_license') NOT NULL,
  `document_number` VARCHAR(100) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `expiry_date` DATE NULL DEFAULT NULL,
  `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `driver_documents_driver_idx` (`driver_id`, `document_type`),
  INDEX `driver_documents_verification_idx` (`verification_status`),
  CONSTRAINT `fk_driver_documents_driver_id` FOREIGN KEY (`driver_id`) REFERENCES `driver_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Table: vehicle_documents
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicle_documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehicle_id` BIGINT UNSIGNED NOT NULL,
  `document_type` ENUM('registration', 'fitness', 'insurance', 'tax_token') NOT NULL,
  `document_number` VARCHAR(100) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `expiry_date` DATE NULL DEFAULT NULL,
  `verification_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `vehicle_documents_vehicle_idx` (`vehicle_id`, `document_type`),
  INDEX `vehicle_documents_verification_idx` (`verification_status`),
  CONSTRAINT `fk_vehicle_documents_vehicle_id` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. Table: locations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `type` ENUM('division', 'district', 'upazila', 'city', 'area') NOT NULL DEFAULT 'district',
  `district` VARCHAR(100) NULL DEFAULT NULL,
  `division` VARCHAR(100) NULL DEFAULT NULL,
  `latitude` DECIMAL(10,8) NULL DEFAULT NULL,
  `longitude` DECIMAL(11,8) NULL DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `locations_district_idx` (`district`),
  INDEX `locations_division_idx` (`division`),
  INDEX `locations_coords_idx` (`latitude`, `longitude`),
  INDEX `locations_type_idx` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. Table: bookings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_number` VARCHAR(50) NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `driver_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `vehicle_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `service_category_id` BIGINT UNSIGNED NOT NULL,
  `vehicle_type_id` BIGINT UNSIGNED NOT NULL,
  `pickup_address` TEXT NOT NULL,
  `pickup_latitude` DECIMAL(10,8) NULL DEFAULT NULL,
  `pickup_longitude` DECIMAL(11,8) NULL DEFAULT NULL,
  `destination_address` TEXT NOT NULL,
  `destination_latitude` DECIMAL(10,8) NULL DEFAULT NULL,
  `destination_longitude` DECIMAL(11,8) NULL DEFAULT NULL,
  `distance_km` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `estimated_duration_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
  `scheduled_at` DATETIME NULL DEFAULT NULL,
  `load_description` TEXT NULL DEFAULT NULL,
  `load_weight` DECIMAL(8,2) NULL DEFAULT NULL,
  `passenger_count` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `luggage_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `trip_type` ENUM('one_way', 'round_trip', 'return_trip', 'hourly') NOT NULL DEFAULT 'one_way',
  `estimated_fare` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `final_fare` DECIMAL(10,2) NULL DEFAULT NULL,
  `status` ENUM(
    'pending',
    'searching_driver',
    'driver_assigned',
    'driver_arriving',
    'arrived',
    'loading',
    'trip_started',
    'trip_completed',
    'cancelled_by_customer',
    'cancelled_by_driver',
    'cancelled_by_admin'
  ) NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `customer_notes` TEXT NULL DEFAULT NULL,
  `driver_notes` TEXT NULL DEFAULT NULL,
  `cancelled_by` ENUM('customer', 'driver', 'admin') NULL DEFAULT NULL,
  `cancellation_reason` TEXT NULL DEFAULT NULL,
  `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bookings_number_unique` (`booking_number`),
  INDEX `bookings_customer_idx` (`customer_id`),
  INDEX `bookings_driver_idx` (`driver_id`),
  INDEX `bookings_status_idx` (`status`),
  INDEX `bookings_payment_status_idx` (`payment_status`),
  INDEX `bookings_trip_type_idx` (`trip_type`),
  INDEX `bookings_scheduled_idx` (`scheduled_at`),
  INDEX `bookings_pickup_coords_idx` (`pickup_latitude`, `pickup_longitude`),
  INDEX `bookings_dest_coords_idx` (`destination_latitude`, `destination_longitude`),
  CONSTRAINT `fk_bookings_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_driver_id` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bookings_vehicle_id` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bookings_service_cat_id` FOREIGN KEY (`service_category_id`) REFERENCES `service_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_vehicle_type_id` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 11. Table: booking_status_history
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `booking_status_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `changed_by_user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `notes` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `bsh_booking_status_idx` (`booking_id`, `status`),
  INDEX `bsh_created_idx` (`created_at`),
  CONSTRAINT `fk_bsh_booking_id` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bsh_changed_by_user_id` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 12. Table: driver_locations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `driver_locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `driver_id` BIGINT UNSIGNED NOT NULL,
  `booking_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `latitude` DECIMAL(10,8) NOT NULL,
  `longitude` DECIMAL(11,8) NOT NULL,
  `heading` DECIMAL(5,2) NULL DEFAULT NULL,
  `speed` DECIMAL(6,2) NULL DEFAULT NULL,
  `accuracy` DECIMAL(6,2) NULL DEFAULT NULL,
  `recorded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `driver_locations_driver_rec_idx` (`driver_id`, `recorded_at`),
  INDEX `driver_locations_booking_idx` (`booking_id`),
  CONSTRAINT `fk_driver_locations_driver_id` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_driver_locations_booking_id` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 13. Table: payments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('cash', 'bkash', 'nagad', 'rocket', 'card', 'other') NOT NULL DEFAULT 'cash',
  `transaction_id` VARCHAR(100) NULL DEFAULT NULL,
  `gateway` VARCHAR(50) NULL DEFAULT NULL,
  `status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `payments_booking_idx` (`booking_id`),
  INDEX `payments_customer_idx` (`customer_id`),
  INDEX `payments_tx_idx` (`transaction_id`),
  INDEX `payments_status_idx` (`status`),
  CONSTRAINT `fk_payments_booking_id` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 14. Table: wallets
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wallets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `driver_id` BIGINT UNSIGNED NOT NULL,
  `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wallets_driver_id_unique` (`driver_id`),
  CONSTRAINT `fk_wallets_driver_id` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 15. Table: wallet_transactions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wallet_id` BIGINT UNSIGNED NOT NULL,
  `type` ENUM('earning', 'commission', 'withdrawal', 'refund', 'adjustment') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `reference_type` VARCHAR(100) NULL DEFAULT NULL,
  `reference_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `balance_after` DECIMAL(12,2) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `wallet_tx_wallet_idx` (`wallet_id`),
  INDEX `wallet_tx_type_idx` (`type`),
  INDEX `wallet_tx_ref_idx` (`reference_type`, `reference_id`),
  CONSTRAINT `fk_wallet_tx_wallet_id` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 16. Table: ratings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ratings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` BIGINT UNSIGNED NOT NULL,
  `customer_id` BIGINT UNSIGNED NOT NULL,
  `driver_id` BIGINT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL COMMENT '1 to 5 stars',
  `review` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `ratings_booking_idx` (`booking_id`),
  INDEX `ratings_driver_idx` (`driver_id`),
  INDEX `ratings_stars_idx` (`rating`),
  CONSTRAINT `fk_ratings_booking_id` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ratings_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ratings_driver_id` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 17. Table: complaints
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `complaints` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
  `admin_response` TEXT NULL DEFAULT NULL,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `complaints_user_idx` (`user_id`),
  INDEX `complaints_booking_idx` (`booking_id`),
  INDEX `complaints_status_idx` (`status`),
  CONSTRAINT `fk_complaints_booking_id` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_complaints_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 18. Table: notifications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` CHAR(36) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `notifiable_type` VARCHAR(255) NOT NULL,
  `notifiable_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NULL DEFAULT NULL,
  `message` TEXT NULL DEFAULT NULL,
  `data` JSON NOT NULL,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `notifications_notifiable_idx` (`notifiable_type`, `notifiable_id`),
  INDEX `notifications_user_idx` (`user_id`),
  INDEX `notifications_read_idx` (`read_at`),
  CONSTRAINT `fk_notifications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 19. Table: promo_codes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `promo_codes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `type` ENUM('fixed', 'percentage') NOT NULL DEFAULT 'percentage',
  `value` DECIMAL(10,2) NOT NULL,
  `minimum_fare` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `maximum_discount` DECIMAL(10,2) NULL DEFAULT NULL,
  `usage_limit` INT UNSIGNED NULL DEFAULT NULL,
  `per_user_limit` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `starts_at` DATETIME NULL DEFAULT NULL,
  `expires_at` DATETIME NULL DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promo_codes_code_unique` (`code`),
  INDEX `promo_codes_code_idx` (`code`),
  INDEX `promo_codes_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 20. Table: promo_code_usages
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `promo_code_usages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `promo_code_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `booking_id` BIGINT UNSIGNED NOT NULL,
  `discount_amount` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `promo_usage_code_user_idx` (`promo_code_id`, `user_id`),
  INDEX `promo_usage_booking_idx` (`booking_id`),
  CONSTRAINT `fk_promo_usage_code_id` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_promo_usage_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_promo_usage_booking_id` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 21. Table: system_settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'string',
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`),
  INDEX `system_settings_key_idx` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 22. Table: admin_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` BIGINT UNSIGNED NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `module` VARCHAR(100) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `admin_logs_admin_mod_idx` (`admin_id`, `module`),
  INDEX `admin_logs_created_idx` (`created_at`),
  CONSTRAINT `fk_admin_logs_admin_id` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 23. Table: personal_access_tokens (Sanctum)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `abilities` TEXT NULL,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  INDEX `personal_access_tokens_tokenable_idx` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 24. Table: failed_jobs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 25. Table: otps (One-Time Passwords)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `otps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone` VARCHAR(20) NOT NULL,
  `otp_hash` VARCHAR(255) NOT NULL,
  `purpose` ENUM('registration', 'login', 'password_reset', 'phone_verification') NOT NULL DEFAULT 'phone_verification',
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `expires_at` TIMESTAMP NOT NULL,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `otps_phone_idx` (`phone`),
  INDEX `otps_expires_at_idx` (`expires_at`),
  INDEX `otps_lookup_idx` (`phone`, `purpose`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SEED DATA INSERTIONS (IDEMPOTENT VIA INSERT IGNORE / REPLACE)
-- ==========================================================

-- System Settings
INSERT INTO `system_settings` (`key`, `value`, `type`, `description`) VALUES
('app_name', 'TripBD', 'string', 'Application Platform Name'),
('app_tagline', 'All-in-One Transport & Logistics Solution in Bangladesh', 'string', 'App Tagline'),
('driver_commission_rate', '10.00', 'decimal', 'Standard platform commission percentage'),
('ambulance_commission_rate', '0.00', 'decimal', 'Zero-commission incentive for ambulance emergencies'),
('truck_commission_rate', '8.00', 'decimal', 'Truck logistics commission percentage'),
('currency_symbol', '৳', 'string', 'Bangladeshi Taka Symbol'),
('currency_code', 'BDT', 'string', 'ISO Currency Code'),
('support_phone', '+8809612000000', 'string', '24/7 Helpline contact number'),
('support_email', 'support@pixelneuron.net', 'string', 'Customer Care Email'),
('mail_host', 'smtp-prod.mailrcld.com', 'string', 'SMTP Server Host'),
('mail_port', '587', 'integer', 'SMTP Server Port (STARTTLS)'),
('mail_encryption', 'tls', 'string', 'SMTP Encryption'),
('mail_username', 'support@pixelneuron.net', 'string', 'SMTP Authenticated Username'),
('mail_from_address', 'support@pixelneuron.net', 'string', 'Transactional Sender Address'),
('mail_from_name', 'TripBD Support', 'string', 'Transactional Sender Name'),
('sms_gateway_provider', 'greenweb', 'string', 'Active SMS Gateway Provider'),
('otp_expiry_minutes', '5', 'integer', 'OTP validity duration in minutes'),
('max_search_radius_km', '15.00', 'decimal', 'Maximum driver search radius in kilometers'),
('driver_location_poll_seconds', '15', 'integer', 'cPanel compatible REST GPS polling interval in seconds'),
('auto_cancel_unaccepted_minutes', '10', 'integer', 'Auto timeout for unassigned requests')
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`), `description`=VALUES(`description`);

-- Service Categories
INSERT INTO `service_categories` (`id`, `name`, `slug`, `description`, `icon`, `image`, `status`, `sort_order`) VALUES
(1, 'Truck Trip', 'truck', 'Reliable logistics and cargo transportation across Bangladesh for commercial, home shifting, and industrial loads.', 'Truck', '/images/services/truck.jpg', 'active', 1),
(2, 'Ambulance Trip', 'ambulance', '24/7 urgent medical transport, ICU, AC, and Non-AC patient transfers with rapid emergency dispatch.', 'Ambulance', '/images/services/ambulance.jpg', 'active', 2),
(3, 'Private Car Trip', 'private-car', 'Comfortable sedans and microbuses for inter-district journeys, family tours, and corporate trips.', 'Car', '/images/services/car.jpg', 'active', 3),
(4, 'Taxi Trip', 'taxi', 'Convenient daily city taxi rides and fast intra-district mobility.', 'Navigation', '/images/services/taxi.jpg', 'active', 4),
(5, 'CNG Auto Rickshaw', 'cng', 'Affordable and swift CNG auto rickshaw rides through urban streets and local zones.', 'Zap', '/images/services/cng.jpg', 'active', 5),
(6, 'Bike Ride', 'bike', 'Fastest solo rider transport beat traffic delays anywhere in town.', 'Bike', '/images/services/bike.jpg', 'active', 6),
(7, 'Return Truck', 'return-truck', 'Up to 50% discount on empty returning trucks across major Bangladesh highway corridors.', 'RefreshCw', '/images/services/return-truck.jpg', 'active', 7),
(8, 'Car Rental', 'rental', 'Chauffeur-driven hourly and day-long vehicle rentals for tours, weddings, and business meetings.', 'Clock', '/images/services/rental.jpg', 'active', 8)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`), `icon`=VALUES(`icon`);

-- Vehicle Types
INSERT INTO `vehicle_types` (`id`, `service_category_id`, `name`, `slug`, `description`, `icon`, `passenger_capacity`, `load_capacity`, `base_fare`, `per_km_rate`, `per_hour_rate`, `minimum_fare`, `status`) VALUES
(1, 1, '7ft Pickup (1 Ton)', 'pickup-1-ton', 'Ideal for small apartment shifting, electronic goods, and retail cargo.', 'Truck', 2, 1.00, 800.00, 45.00, 150.00, 1000.00, 'active'),
(2, 1, '14ft Medium Truck (3.5 Ton)', 'medium-truck-3-5-ton', 'Standard for 2-3 BHK home shifting and light industrial goods.', 'Truck', 2, 3.50, 2500.00, 70.00, 250.00, 3000.00, 'active'),
(3, 1, '18ft Heavy Truck (7.5 Ton)', 'heavy-truck-7-5-ton', 'Heavy industrial raw materials and factory cargo delivery.', 'Truck', 2, 7.50, 5000.00, 95.00, 400.00, 6000.00, 'active'),
(4, 1, '23ft Heavy Trailer (15 Ton)', 'heavy-trailer-15-ton', 'Bulk agro-products, machinery, and inter-city heavy transport.', 'Truck', 2, 15.00, 9000.00, 130.00, 600.00, 12000.00, 'active'),
(5, 2, 'Non-AC Patient Ambulance', 'ambulance-non-ac', 'Basic emergency transfer with primary stretcher and attendant seat.', 'Ambulance', 3, 0.00, 1200.00, 35.00, 200.00, 1500.00, 'active'),
(6, 2, 'AC Emergency Ambulance', 'ambulance-ac', 'Climate-controlled ambulance with continuous oxygen cylinder supply.', 'Ambulance', 3, 0.00, 2000.00, 50.00, 300.00, 2500.00, 'active'),
(7, 2, 'ICU / CCU Life Support Ambulance', 'ambulance-icu', 'Advanced ventilator, cardiac monitor, suction machine, and trained paramedic.', 'Ambulance', 4, 0.00, 6000.00, 85.00, 600.00, 7500.00, 'active'),
(8, 3, 'Sedan Car (4 Seater)', 'sedan-car', 'Axio, Premio, Allion or equivalent AC sedan for premium travel.', 'Car', 4, 0.25, 300.00, 28.00, 180.00, 450.00, 'active'),
(9, 3, 'Microbus (7-11 Seater)', 'microbus-noah-hiace', 'Toyota Noah / HiAce for family group tours and airport pickups.', 'Car', 10, 0.80, 1500.00, 45.00, 350.00, 2000.00, 'active'),
(10, 4, 'Standard City Taxi', 'city-taxi-cab', 'Metropolitan metered taxi service across Dhaka and Chittagong.', 'Navigation', 4, 0.15, 150.00, 22.00, 120.00, 200.00, 'active'),
(11, 5, 'Green CNG Auto Rickshaw', 'cng-auto-rickshaw', 'Standard 3-wheeler auto rickshaw for fast alley and city travel.', 'Zap', 3, 0.05, 60.00, 18.00, 80.00, 90.00, 'active'),
(12, 6, 'Standard Motorbike', 'solo-bike-ride', 'Fastest solo commuter ride with sanitized helmet provided.', 'Bike', 1, 0.02, 35.00, 14.00, 50.00, 50.00, 'active'),
(13, 7, 'Highway Return Truck (Discounted)', 'return-empty-truck', 'Empty truck returning along national corridors at 30-50% discounted fares.', 'RefreshCw', 2, 5.00, 1800.00, 45.00, 150.00, 2500.00, 'active'),
(14, 8, 'Chauffeur Driven Daily Rental', 'car-rental-daily', 'Full-day (10 hours) dedicated vehicle with professional driver.', 'Clock', 7, 0.50, 3500.00, 25.00, 200.00, 3500.00, 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `base_fare`=VALUES(`base_fare`), `per_km_rate`=VALUES(`per_km_rate`), `minimum_fare`=VALUES(`minimum_fare`);

-- Locations (8 Divisions + 64 Districts of Bangladesh)
INSERT INTO `locations` (`id`, `name`, `slug`, `type`, `district`, `division`, `latitude`, `longitude`, `status`) VALUES
-- 8 Divisions
(1, 'Dhaka Division', 'dhaka-division', 'division', 'Dhaka', 'Dhaka', 23.81030000, 90.41250000, 'active'),
(2, 'Chattogram Division', 'chattogram-division', 'division', 'Chattogram', 'Chattogram', 22.35690000, 91.78320000, 'active'),
(3, 'Rajshahi Division', 'rajshahi-division', 'division', 'Rajshahi', 'Rajshahi', 24.37450000, 88.60420000, 'active'),
(4, 'Khulna Division', 'khulna-division', 'division', 'Khulna', 'Khulna', 22.84560000, 89.54030000, 'active'),
(5, 'Barishal Division', 'barishal-division', 'division', 'Barishal', 'Barishal', 22.70100000, 90.35350000, 'active'),
(6, 'Sylhet Division', 'sylhet-division', 'division', 'Sylhet', 'Sylhet', 24.89490000, 91.86870000, 'active'),
(7, 'Rangpur Division', 'rangpur-division', 'division', 'Rangpur', 'Rangpur', 25.74390000, 89.27520000, 'active'),
(8, 'Mymensingh Division', 'mymensingh-division', 'division', 'Mymensingh', 'Mymensingh', 24.74710000, 90.42030000, 'active'),
-- Dhaka Division (13 Districts)
(9, 'Dhaka', 'dhaka', 'district', 'Dhaka', 'Dhaka', 23.81030000, 90.41250000, 'active'),
(10, 'Gazipur', 'gazipur', 'district', 'Gazipur', 'Dhaka', 23.99990000, 90.42030000, 'active'),
(11, 'Narayanganj', 'narayanganj', 'district', 'Narayanganj', 'Dhaka', 23.62380000, 90.50000000, 'active'),
(12, 'Tangail', 'tangail', 'district', 'Tangail', 'Dhaka', 24.25130000, 89.91670000, 'active'),
(13, 'Narsingdi', 'narsingdi', 'district', 'Narsingdi', 'Dhaka', 23.93220000, 90.71540000, 'active'),
(14, 'Faridpur', 'faridpur', 'district', 'Faridpur', 'Dhaka', 23.60700000, 89.84290000, 'active'),
(15, 'Manikganj', 'manikganj', 'district', 'Manikganj', 'Dhaka', 23.86440000, 90.00470000, 'active'),
(16, 'Munshiganj', 'munshiganj', 'district', 'Munshiganj', 'Dhaka', 23.54220000, 90.53050000, 'active'),
(17, 'Rajbari', 'rajbari', 'district', 'Rajbari', 'Dhaka', 23.75740000, 89.64440000, 'active'),
(18, 'Gopalganj', 'gopalganj', 'district', 'Gopalganj', 'Dhaka', 23.00510000, 89.82660000, 'active'),
(19, 'Madaripur', 'madaripur', 'district', 'Madaripur', 'Dhaka', 23.16410000, 90.18970000, 'active'),
(20, 'Shariatpur', 'shariatpur', 'district', 'Shariatpur', 'Dhaka', 23.24230000, 90.43480000, 'active'),
(21, 'Kishoreganj', 'kishoreganj', 'district', 'Kishoreganj', 'Dhaka', 24.44490000, 90.77660000, 'active'),
-- Chattogram Division (11 Districts)
(22, 'Chattogram (Chittagong)', 'chattogram', 'district', 'Chattogram', 'Chattogram', 22.35690000, 91.78320000, 'active'),
(23, 'Cox's Bazar', 'coxs-bazar', 'district', 'Cox's Bazar', 'Chattogram', 21.42720000, 92.00580000, 'active'),
(24, 'Cumilla (Comilla)', 'cumilla', 'district', 'Cumilla', 'Chattogram', 23.46820000, 91.17880000, 'active'),
(25, 'Feni', 'feni', 'district', 'Feni', 'Chattogram', 23.01590000, 91.39760000, 'active'),
(26, 'Brahmanbaria', 'brahmanbaria', 'district', 'Brahmanbaria', 'Chattogram', 23.95710000, 91.11150000, 'active'),
(27, 'Rangamati', 'rangamati', 'district', 'Rangamati', 'Chattogram', 22.65330000, 92.17530000, 'active'),
(28, 'Noakhali', 'noakhali', 'district', 'Noakhali', 'Chattogram', 22.86960000, 91.09940000, 'active'),
(29, 'Chandpur', 'chandpur', 'district', 'Chandpur', 'Chattogram', 23.23330000, 90.66670000, 'active'),
(30, 'Lakshmipur', 'lakshmipur', 'district', 'Lakshmipur', 'Chattogram', 22.94250000, 90.84120000, 'active'),
(31, 'Bandarban', 'bandarban', 'district', 'Bandarban', 'Chattogram', 22.19530000, 92.21840000, 'active'),
(32, 'Khagrachhari', 'khagrachhari', 'district', 'Khagrachhari', 'Chattogram', 23.11930000, 91.98470000, 'active'),
-- Rajshahi Division (8 Districts)
(33, 'Rajshahi', 'rajshahi', 'district', 'Rajshahi', 'Rajshahi', 24.37450000, 88.60420000, 'active'),
(34, 'Bogura (Bogra)', 'bogura', 'district', 'Bogura', 'Rajshahi', 24.84650000, 89.37770000, 'active'),
(35, 'Pabna', 'pabna', 'district', 'Pabna', 'Rajshahi', 24.00640000, 89.23720000, 'active'),
(36, 'Sirajganj', 'sirajganj', 'district', 'Sirajganj', 'Rajshahi', 24.45340000, 89.70060000, 'active'),
(37, 'Naogaon', 'naogaon', 'district', 'Naogaon', 'Rajshahi', 24.81030000, 88.94140000, 'active'),
(38, 'Natore', 'natore', 'district', 'Natore', 'Rajshahi', 24.42060000, 88.93240000, 'active'),
(39, 'Chapainawabganj', 'chapainawabganj', 'district', 'Chapainawabganj', 'Rajshahi', 24.59650000, 88.27750000, 'active'),
(40, 'Joypurhat', 'joypurhat', 'district', 'Joypurhat', 'Rajshahi', 25.10150000, 89.02750000, 'active'),
-- Khulna Division (10 Districts)
(41, 'Khulna', 'khulna', 'district', 'Khulna', 'Khulna', 22.84560000, 89.54030000, 'active'),
(42, 'Jashore (Jessore)', 'jashore', 'district', 'Jashore', 'Khulna', 23.16640000, 89.20810000, 'active'),
(43, 'Kushtia', 'kushtia', 'district', 'Kushtia', 'Khulna', 23.90130000, 89.12050000, 'active'),
(44, 'Satkhira', 'satkhira', 'district', 'Satkhira', 'Khulna', 22.71850000, 89.07050000, 'active'),
(45, 'Bagerhat', 'bagerhat', 'district', 'Bagerhat', 'Khulna', 22.65160000, 89.78590000, 'active'),
(46, 'Jhenaidah', 'jhenaidah', 'district', 'Jhenaidah', 'Khulna', 23.54480000, 89.15390000, 'active'),
(47, 'Chuadanga', 'chuadanga', 'district', 'Chuadanga', 'Khulna', 23.64020000, 88.84180000, 'active'),
(48, 'Magura', 'magura', 'district', 'Magura', 'Khulna', 23.48730000, 89.41980000, 'active'),
(49, 'Meherpur', 'meherpur', 'district', 'Meherpur', 'Khulna', 23.76220000, 88.63180000, 'active'),
(50, 'Narail', 'narail', 'district', 'Narail', 'Khulna', 23.17250000, 89.51270000, 'active'),
-- Barishal Division (6 Districts)
(51, 'Barishal (Barisal)', 'barishal', 'district', 'Barishal', 'Barishal', 22.70100000, 90.35350000, 'active'),
(52, 'Patuakhali', 'patuakhali', 'district', 'Patuakhali', 'Barishal', 22.35960000, 90.32990000, 'active'),
(53, 'Bhola', 'bhola', 'district', 'Bhola', 'Barishal', 22.68590000, 90.64810000, 'active'),
(54, 'Pirojpur', 'pirojpur', 'district', 'Pirojpur', 'Barishal', 22.58410000, 89.97200000, 'active'),
(55, 'Barguna', 'barguna', 'district', 'Barguna', 'Barishal', 22.09530000, 90.07700000, 'active'),
(56, 'Jhalokati', 'jhalokati', 'district', 'Jhalokati', 'Barishal', 22.64060000, 90.19870000, 'active'),
-- Sylhet Division (4 Districts)
(57, 'Sylhet', 'sylhet', 'district', 'Sylhet', 'Sylhet', 24.89490000, 91.86870000, 'active'),
(58, 'Moulvibazar', 'moulvibazar', 'district', 'Moulvibazar', 'Sylhet', 24.48290000, 91.77740000, 'active'),
(59, 'Habiganj', 'habiganj', 'district', 'Habiganj', 'Sylhet', 24.37490000, 91.41550000, 'active'),
(60, 'Sunamganj', 'sunamganj', 'district', 'Sunamganj', 'Sylhet', 25.06580000, 91.39500000, 'active'),
-- Rangpur Division (8 Districts)
(61, 'Rangpur', 'rangpur', 'district', 'Rangpur', 'Rangpur', 25.74390000, 89.27520000, 'active'),
(62, 'Dinajpur', 'dinajpur', 'district', 'Dinajpur', 'Rangpur', 25.62170000, 88.63550000, 'active'),
(63, 'Gaibandha', 'gaibandha', 'district', 'Gaibandha', 'Rangpur', 25.32880000, 89.54060000, 'active'),
(64, 'Kurigram', 'kurigram', 'district', 'Kurigram', 'Rangpur', 25.80540000, 89.63620000, 'active'),
(65, 'Nilphamari', 'nilphamari', 'district', 'Nilphamari', 'Rangpur', 25.93180000, 88.85600000, 'active'),
(66, 'Lalmonirhat', 'lalmonirhat', 'district', 'Lalmonirhat', 'Rangpur', 25.99230000, 89.28470000, 'active'),
(67, 'Thakurgaon', 'thakurgaon', 'district', 'Thakurgaon', 'Rangpur', 26.03370000, 88.46170000, 'active'),
(68, 'Panchagarh', 'panchagarh', 'district', 'Panchagarh', 'Rangpur', 26.34110000, 88.55420000, 'active'),
-- Mymensingh Division (4 Districts)
(69, 'Mymensingh', 'mymensingh', 'district', 'Mymensingh', 'Mymensingh', 24.74710000, 90.42030000, 'active'),
(70, 'Jamalpur', 'jamalpur', 'district', 'Jamalpur', 'Mymensingh', 24.93750000, 89.93780000, 'active'),
(71, 'Netrokona', 'netrokona', 'district', 'Netrokona', 'Mymensingh', 24.87090000, 90.72790000, 'active'),
(72, 'Sherpur', 'sherpur', 'district', 'Sherpur', 'Mymensingh', 25.02050000, 90.01530000, 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `latitude`=VALUES(`latitude`), `longitude`=VALUES(`longitude`), `division`=VALUES(`division`), `district`=VALUES(`district`);

-- Demo Users (Password: password123)
-- Valid PHP/Laravel Bcrypt Hash (Cost: 12, Length: 60, Verified with PHP 8.4 password_verify)
-- Hash: $2y$12$lw2yfRZEd2srIHOWGMdRIeIiKL7hAkpAYFPAooDtMRY3meJ1nt/H6
INSERT INTO `users` (`id`, `name`, `phone`, `email`, `password`, `role`, `status`, `phone_verified_at`) VALUES
(1, 'TripBD System Admin', '01700000000', 'admin@tripbd.com', '$2y$12$lw2yfRZEd2srIHOWGMdRIeIiKL7hAkpAYFPAooDtMRY3meJ1nt/H6', 'admin', 'active', NOW()),
(2, 'Tanvir Hasan', '01711111111', 'tanvir@gmail.com', '$2y$12$lw2yfRZEd2srIHOWGMdRIeIiKL7hAkpAYFPAooDtMRY3meJ1nt/H6', 'customer', 'active', NOW()),
(3, 'Md. Rafiqul Islam', '01822222222', 'rafiq.driver@gmail.com', '$2y$12$lw2yfRZEd2srIHOWGMdRIeIiKL7hAkpAYFPAooDtMRY3meJ1nt/H6', 'driver', 'active', NOW()),
(4, 'Jalal Ahmed (Ambulance)', '01933333333', 'jalal.ambulance@gmail.com', '$2y$12$lw2yfRZEd2srIHOWGMdRIeIiKL7hAkpAYFPAooDtMRY3meJ1nt/H6', 'driver', 'active', NOW())
ON DUPLICATE KEY UPDATE 
  `name` = VALUES(`name`),
  `phone` = VALUES(`phone`),
  `email` = VALUES(`email`),
  `password` = VALUES(`password`),
  `role` = VALUES(`role`),
  `status` = VALUES(`status`);

-- Demo Customer Profile
INSERT INTO `customer_profiles` (`id`, `user_id`, `profile_photo`, `address`, `city`, `district`, `country`, `date_of_birth`) VALUES
(1, 2, NULL, 'House 42, Road 11, Banani', 'Dhaka', 'Dhaka', 'Bangladesh', '1992-05-14')
ON DUPLICATE KEY UPDATE `address`=VALUES(`address`), `district`=VALUES(`district`);

-- Demo Driver Profiles
INSERT INTO `driver_profiles` (`id`, `user_id`, `profile_photo`, `address`, `city`, `district`, `nid_number`, `license_number`, `license_expiry`, `verification_status`, `online_status`, `rating`, `total_trips`) VALUES
(1, 3, NULL, 'Tejgaon Truck Stand, Dhaka', 'Dhaka', 'Dhaka', '19881234567890123', 'DK-88992019-PR', '2028-12-31', 'approved', 'online', 4.92, 148),
(2, 4, NULL, 'Square Hospital Stand, Panthapath', 'Dhaka', 'Dhaka', '19859876543210123', 'DK-77221044-EM', '2029-06-30', 'approved', 'online', 5.00, 92)
ON DUPLICATE KEY UPDATE `verification_status`=VALUES(`verification_status`), `online_status`=VALUES(`online_status`), `rating`=VALUES(`rating`);

-- Demo Driver Wallets
INSERT INTO `wallets` (`id`, `driver_id`, `balance`) VALUES
(1, 3, 4500.00),
(2, 4, 8200.00)
ON DUPLICATE KEY UPDATE `balance`=VALUES(`balance`);

-- Demo Vehicles
INSERT INTO `vehicles` (`id`, `driver_id`, `vehicle_type_id`, `vehicle_number`, `brand`, `model`, `year`, `color`, `passenger_capacity`, `load_capacity`, `registration_number`, `verification_status`, `status`) VALUES
(1, 1, 1, 'DHAKA METRO TA 11-2345', 'Tata', 'Ace EX2', 2022, 'Blue', 2, 1.00, 'DHAKA-METRO-TA-11-2345', 'approved', 'active'),
(2, 2, 7, 'DHAKA METRO CHHA 71-8899', 'Toyota', 'HiAce High Roof ICU', 2023, 'White', 4, 0.00, 'DHAKA-METRO-CHHA-71-8899', 'approved', 'active')
ON DUPLICATE KEY UPDATE `verification_status`=VALUES(`verification_status`), `status`=VALUES(`status`);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
