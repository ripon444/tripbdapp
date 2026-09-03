-- ==========================================================
-- TRIPBD ENTERPRISE POSTGRESQL PRODUCTION DUMP
-- Compatible with PostgreSQL 14+, 15+, 16+, 17+, 18+ (Neon DB / Supabase / RDS)
-- Target Host: ep-withered-cell-azwm4gqa-pooler.c-3.ap-southeast-1.aws.neon.tech
-- Database: neondb
-- Generated for TripBD Bangladesh Transport & Logistics Platform
-- ==========================================================

BEGIN;

-- --------------------------------------------------------
-- 1. Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(30) NOT NULL UNIQUE,
  email VARCHAR(255) UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'customer' CHECK (role IN ('customer', 'driver', 'admin')),
  status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended', 'pending')),
  phone_verified_at TIMESTAMPTZ,
  remember_token VARCHAR(100),
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS users_phone_idx ON users(phone);
CREATE INDEX IF NOT EXISTS users_role_idx ON users(role);
CREATE INDEX IF NOT EXISTS users_status_idx ON users(status);

-- --------------------------------------------------------
-- 2. Table: customer_profiles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS customer_profiles (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
  profile_photo VARCHAR(500),
  address TEXT,
  city VARCHAR(100),
  district VARCHAR(100),
  country VARCHAR(100) NOT NULL DEFAULT 'Bangladesh',
  date_of_birth DATE,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS customer_profiles_district_idx ON customer_profiles(district);

-- --------------------------------------------------------
-- 3. Table: driver_profiles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS driver_profiles (
  id BIGSERIAL PRIMARY KEY,
  user_id BIGINT NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
  profile_photo VARCHAR(500),
  address TEXT,
  city VARCHAR(100),
  district VARCHAR(100),
  nid_number VARCHAR(50) NOT NULL,
  license_number VARCHAR(50) NOT NULL,
  license_expiry DATE NOT NULL,
  verification_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (verification_status IN ('pending', 'approved', 'rejected', 'suspended')),
  online_status VARCHAR(20) NOT NULL DEFAULT 'offline' CHECK (online_status IN ('online', 'offline', 'busy')),
  rating NUMERIC(3,2) NOT NULL DEFAULT 5.00,
  total_trips INT NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS driver_profiles_verification_idx ON driver_profiles(verification_status);
CREATE INDEX IF NOT EXISTS driver_profiles_online_idx ON driver_profiles(online_status);
CREATE INDEX IF NOT EXISTS driver_profiles_district_idx ON driver_profiles(district);
CREATE INDEX IF NOT EXISTS driver_profiles_rating_idx ON driver_profiles(rating);

-- --------------------------------------------------------
-- 4. Table: service_categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_categories (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  icon VARCHAR(100) NOT NULL,
  image VARCHAR(500),
  status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS service_categories_status_idx ON service_categories(status);
CREATE INDEX IF NOT EXISTS service_categories_sort_idx ON service_categories(sort_order);

-- --------------------------------------------------------
-- 5. Table: vehicle_types
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS vehicle_types (
  id BIGSERIAL PRIMARY KEY,
  service_category_id BIGINT NOT NULL REFERENCES service_categories(id) ON DELETE CASCADE,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  icon VARCHAR(100),
  image VARCHAR(500),
  passenger_capacity SMALLINT NOT NULL DEFAULT 1,
  load_capacity NUMERIC(8,2) NOT NULL DEFAULT 0.00,
  base_fare NUMERIC(10,2) NOT NULL DEFAULT 0.00,
  per_km_rate NUMERIC(10,2) NOT NULL DEFAULT 0.00,
  per_hour_rate NUMERIC(10,2) NOT NULL DEFAULT 0.00,
  minimum_fare NUMERIC(10,2) NOT NULL DEFAULT 0.00,
  status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS vehicle_types_cat_id_idx ON vehicle_types(service_category_id);
CREATE INDEX IF NOT EXISTS vehicle_types_status_idx ON vehicle_types(status);

-- --------------------------------------------------------
-- 6. Table: vehicles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS vehicles (
  id BIGSERIAL PRIMARY KEY,
  driver_id BIGINT NOT NULL REFERENCES driver_profiles(id) ON DELETE CASCADE,
  vehicle_type_id BIGINT NOT NULL REFERENCES vehicle_types(id) ON DELETE CASCADE,
  vehicle_number VARCHAR(100) NOT NULL,
  brand VARCHAR(100) NOT NULL,
  model VARCHAR(100) NOT NULL,
  year SMALLINT,
  color VARCHAR(50),
  passenger_capacity SMALLINT NOT NULL DEFAULT 4,
  load_capacity NUMERIC(8,2) NOT NULL DEFAULT 0.00,
  registration_number VARCHAR(100) NOT NULL UNIQUE,
  verification_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (verification_status IN ('pending', 'approved', 'rejected', 'suspended')),
  status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'maintenance')),
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS vehicles_driver_id_idx ON vehicles(driver_id);
CREATE INDEX IF NOT EXISTS vehicles_type_id_idx ON vehicles(vehicle_type_id);
CREATE INDEX IF NOT EXISTS vehicles_verification_idx ON vehicles(verification_status);
CREATE INDEX IF NOT EXISTS vehicles_status_idx ON vehicles(status);

-- --------------------------------------------------------
-- 7. Table: driver_documents
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS driver_documents (
  id BIGSERIAL PRIMARY KEY,
  driver_id BIGINT NOT NULL REFERENCES driver_profiles(id) ON DELETE CASCADE,
  document_type VARCHAR(50) NOT NULL CHECK (document_type IN ('nid', 'driving_license')),
  document_number VARCHAR(100) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  expiry_date DATE,
  verification_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (verification_status IN ('pending', 'approved', 'rejected')),
  rejection_reason TEXT,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS driver_documents_driver_idx ON driver_documents(driver_id, document_type);
CREATE INDEX IF NOT EXISTS driver_documents_verification_idx ON driver_documents(verification_status);

-- --------------------------------------------------------
-- 8. Table: vehicle_documents
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS vehicle_documents (
  id BIGSERIAL PRIMARY KEY,
  vehicle_id BIGINT NOT NULL REFERENCES vehicles(id) ON DELETE CASCADE,
  document_type VARCHAR(50) NOT NULL CHECK (document_type IN ('registration', 'fitness', 'insurance', 'tax_token')),
  document_number VARCHAR(100) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  expiry_date DATE,
  verification_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (verification_status IN ('pending', 'approved', 'rejected')),
  rejection_reason TEXT,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS vehicle_documents_vehicle_idx ON vehicle_documents(vehicle_id, document_type);
CREATE INDEX IF NOT EXISTS vehicle_documents_verification_idx ON vehicle_documents(verification_status);

-- --------------------------------------------------------
-- 9. Table: locations (8 Divisions & 64 Districts)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS locations (
  id BIGSERIAL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'district' CHECK (type IN ('division', 'district', 'upazila', 'city', 'area')),
  district VARCHAR(100),
  division VARCHAR(100),
  latitude NUMERIC(10,8),
  longitude NUMERIC(11,8),
  status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS locations_district_idx ON locations(district);
CREATE INDEX IF NOT EXISTS locations_division_idx ON locations(division);
CREATE INDEX IF NOT EXISTS locations_coords_idx ON locations(latitude, longitude);
CREATE INDEX IF NOT EXISTS locations_type_idx ON locations(type);

-- --------------------------------------------------------
-- 10. Table: bookings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
  id BIGSERIAL PRIMARY KEY,
  booking_number VARCHAR(50) NOT NULL UNIQUE,
  customer_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  driver_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
  vehicle_id BIGINT REFERENCES vehicles(id) ON DELETE SET NULL,
  service_category_id BIGINT NOT NULL REFERENCES service_categories(id) ON DELETE CASCADE,
  vehicle_type_id BIGINT NOT NULL REFERENCES vehicle_types(id) ON DELETE CASCADE,
  pickup_address TEXT NOT NULL,
  pickup_latitude NUMERIC(10,8),
  pickup_longitude NUMERIC(11,8),
  destination_address TEXT NOT NULL,
  destination_latitude NUMERIC(10,8),
  destination_longitude NUMERIC(11,8),
  distance_km NUMERIC(8,2) NOT NULL DEFAULT 0.00,
  estimated_duration_minutes INT NOT NULL DEFAULT 0,
  scheduled_at TIMESTAMPTZ,
  load_description TEXT,
  load_weight NUMERIC(8,2),
  passenger_count SMALLINT NOT NULL DEFAULT 1,
  luggage_count SMALLINT NOT NULL DEFAULT 0,
  trip_type VARCHAR(20) NOT NULL DEFAULT 'one_way' CHECK (trip_type IN ('one_way', 'round_trip', 'return_trip', 'hourly')),
  estimated_fare NUMERIC(10,2) NOT NULL DEFAULT 0.00,
  final_fare NUMERIC(10,2),
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  payment_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (payment_status IN ('pending', 'paid', 'failed', 'refunded')),
  payment_method VARCHAR(30) DEFAULT 'cash',
  customer_notes TEXT,
  driver_notes TEXT,
  cancelled_by VARCHAR(20),
  cancellation_reason TEXT,
  cancelled_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  deleted_at TIMESTAMPTZ
);
CREATE INDEX IF NOT EXISTS bookings_customer_idx ON bookings(customer_id);
CREATE INDEX IF NOT EXISTS bookings_driver_idx ON bookings(driver_id);
CREATE INDEX IF NOT EXISTS bookings_status_idx ON bookings(status);
CREATE INDEX IF NOT EXISTS bookings_payment_status_idx ON bookings(payment_status);
CREATE INDEX IF NOT EXISTS bookings_trip_type_idx ON bookings(trip_type);
CREATE INDEX IF NOT EXISTS bookings_scheduled_idx ON bookings(scheduled_at);

-- --------------------------------------------------------
-- 11. Table: booking_status_history
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS booking_status_history (
  id BIGSERIAL PRIMARY KEY,
  booking_id BIGINT NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
  status VARCHAR(50) NOT NULL,
  changed_by_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
  notes TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS bsh_booking_status_idx ON booking_status_history(booking_id, status);
CREATE INDEX IF NOT EXISTS bsh_created_idx ON booking_status_history(created_at);

-- --------------------------------------------------------
-- 12. Table: driver_locations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS driver_locations (
  id BIGSERIAL PRIMARY KEY,
  driver_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  booking_id BIGINT REFERENCES bookings(id) ON DELETE SET NULL,
  latitude NUMERIC(10,8) NOT NULL,
  longitude NUMERIC(11,8) NOT NULL,
  heading NUMERIC(5,2),
  speed NUMERIC(6,2),
  accuracy NUMERIC(6,2),
  recorded_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS driver_locations_driver_rec_idx ON driver_locations(driver_id, recorded_at);
CREATE INDEX IF NOT EXISTS driver_locations_booking_idx ON driver_locations(booking_id);

-- --------------------------------------------------------
-- 13. Table: payments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
  id BIGSERIAL PRIMARY KEY,
  booking_id BIGINT NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
  customer_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  amount NUMERIC(10,2) NOT NULL,
  payment_method VARCHAR(20) NOT NULL DEFAULT 'cash' CHECK (payment_method IN ('cash', 'bkash', 'nagad', 'rocket', 'card', 'other')),
  transaction_id VARCHAR(100),
  gateway VARCHAR(50),
  status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'failed', 'refunded')),
  paid_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS payments_booking_idx ON payments(booking_id);
CREATE INDEX IF NOT EXISTS payments_customer_idx ON payments(customer_id);
CREATE INDEX IF NOT EXISTS payments_tx_idx ON payments(transaction_id);
CREATE INDEX IF NOT EXISTS payments_status_idx ON payments(status);

-- --------------------------------------------------------
-- 14. Table: wallets
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS wallets (
  id BIGSERIAL PRIMARY KEY,
  driver_id BIGINT NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
  balance NUMERIC(12,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- 15. Table: wallet_transactions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS wallet_transactions (
  id BIGSERIAL PRIMARY KEY,
  wallet_id BIGINT NOT NULL REFERENCES wallets(id) ON DELETE CASCADE,
  type VARCHAR(20) NOT NULL CHECK (type IN ('earning', 'commission', 'withdrawal', 'refund', 'adjustment')),
  amount NUMERIC(10,2) NOT NULL,
  reference_type VARCHAR(100),
  reference_id BIGINT,
  description TEXT,
  balance_after NUMERIC(12,2) NOT NULL,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS wallet_tx_wallet_idx ON wallet_transactions(wallet_id);
CREATE INDEX IF NOT EXISTS wallet_tx_type_idx ON wallet_transactions(type);
CREATE INDEX IF NOT EXISTS wallet_tx_ref_idx ON wallet_transactions(reference_type, reference_id);

-- --------------------------------------------------------
-- 16. Table: ratings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS ratings (
  id BIGSERIAL PRIMARY KEY,
  booking_id BIGINT NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
  customer_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  driver_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  rating SMALLINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
  review TEXT,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS ratings_booking_idx ON ratings(booking_id);
CREATE INDEX IF NOT EXISTS ratings_driver_idx ON ratings(driver_id);
CREATE INDEX IF NOT EXISTS ratings_stars_idx ON ratings(rating);

-- --------------------------------------------------------
-- 17. Table: complaints
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaints (
  id BIGSERIAL PRIMARY KEY,
  booking_id BIGINT REFERENCES bookings(id) ON DELETE SET NULL,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  subject VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'in_progress', 'resolved', 'closed')),
  admin_response TEXT,
  resolved_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS complaints_user_idx ON complaints(user_id);
CREATE INDEX IF NOT EXISTS complaints_booking_idx ON complaints(booking_id);
CREATE INDEX IF NOT EXISTS complaints_status_idx ON complaints(status);

-- --------------------------------------------------------
-- 18. Table: notifications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
  id CHAR(36) PRIMARY KEY,
  type VARCHAR(255) NOT NULL,
  user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
  notifiable_type VARCHAR(255) NOT NULL,
  notifiable_id BIGINT NOT NULL,
  title VARCHAR(255),
  message TEXT,
  data JSONB NOT NULL,
  read_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS notifications_notifiable_idx ON notifications(notifiable_type, notifiable_id);
CREATE INDEX IF NOT EXISTS notifications_user_idx ON notifications(user_id);
CREATE INDEX IF NOT EXISTS notifications_read_idx ON notifications(read_at);

-- --------------------------------------------------------
-- 19. Table: promo_codes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS promo_codes (
  id BIGSERIAL PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  type VARCHAR(20) NOT NULL DEFAULT 'percentage' CHECK (type IN ('fixed', 'percentage')),
  value NUMERIC(10,2) NOT NULL,
  minimum_fare NUMERIC(10,2) NOT NULL DEFAULT 0.00,
  maximum_discount NUMERIC(10,2),
  usage_limit INT,
  per_user_limit SMALLINT NOT NULL DEFAULT 1,
  starts_at TIMESTAMPTZ,
  expires_at TIMESTAMPTZ,
  status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS promo_codes_status_idx ON promo_codes(status);

-- --------------------------------------------------------
-- 20. Table: promo_code_usages
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS promo_code_usages (
  id BIGSERIAL PRIMARY KEY,
  promo_code_id BIGINT NOT NULL REFERENCES promo_codes(id) ON DELETE CASCADE,
  user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  booking_id BIGINT NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
  discount_amount NUMERIC(10,2) NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS promo_usage_code_user_idx ON promo_code_usages(promo_code_id, user_id);
CREATE INDEX IF NOT EXISTS promo_usage_booking_idx ON promo_code_usages(booking_id);

-- --------------------------------------------------------
-- 21. Table: system_settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS system_settings (
  id BIGSERIAL PRIMARY KEY,
  key VARCHAR(100) NOT NULL UNIQUE,
  value TEXT NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'string',
  description VARCHAR(255),
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- 22. Table: admin_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_logs (
  id BIGSERIAL PRIMARY KEY,
  admin_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  action VARCHAR(255) NOT NULL,
  module VARCHAR(100) NOT NULL,
  target_id BIGINT,
  description TEXT,
  details JSONB,
  ip_address VARCHAR(45),
  user_agent TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS admin_logs_admin_mod_idx ON admin_logs(admin_id, module);
CREATE INDEX IF NOT EXISTS admin_logs_created_idx ON admin_logs(created_at);

-- --------------------------------------------------------
-- 23. Table: personal_access_tokens
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS personal_access_tokens (
  id BIGSERIAL PRIMARY KEY,
  tokenable_type VARCHAR(255) NOT NULL,
  tokenable_id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  abilities TEXT,
  last_used_at TIMESTAMPTZ,
  expires_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS pat_tokenable_idx ON personal_access_tokens(tokenable_type, tokenable_id);

-- --------------------------------------------------------
-- 24. Table: failed_jobs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS failed_jobs (
  id BIGSERIAL PRIMARY KEY,
  uuid VARCHAR(255) NOT NULL UNIQUE,
  connection TEXT NOT NULL,
  queue TEXT NOT NULL,
  payload TEXT NOT NULL,
  exception TEXT NOT NULL,
  failed_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- 25. Table: otps
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS otps (
  id BIGSERIAL PRIMARY KEY,
  phone VARCHAR(20) NOT NULL,
  otp_hash VARCHAR(255) NOT NULL,
  purpose VARCHAR(30) NOT NULL DEFAULT 'phone_verification',
  attempts SMALLINT NOT NULL DEFAULT 0,
  max_attempts SMALLINT NOT NULL DEFAULT 3,
  expires_at TIMESTAMPTZ NOT NULL,
  verified_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS otps_phone_idx ON otps(phone);
CREATE INDEX IF NOT EXISTS otps_expires_at_idx ON otps(expires_at);

-- --------------------------------------------------------
-- 26. Table: withdrawals
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS withdrawals (
  id BIGSERIAL PRIMARY KEY,
  driver_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  wallet_id BIGINT NOT NULL REFERENCES wallets(id) ON DELETE CASCADE,
  withdrawal_number VARCHAR(50) NOT NULL UNIQUE,
  amount NUMERIC(12,2) NOT NULL,
  method VARCHAR(30) NOT NULL DEFAULT 'bkash' CHECK (method IN ('bkash', 'nagad', 'rocket', 'bank_transfer')),
  account_number VARCHAR(100) NOT NULL,
  bank_name VARCHAR(100),
  branch_name VARCHAR(100),
  routing_number VARCHAR(50),
  status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'processing', 'completed', 'rejected', 'cancelled')),
  admin_note TEXT,
  processed_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
  processed_at TIMESTAMPTZ,
  completed_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS withdrawals_driver_idx ON withdrawals(driver_id);
CREATE INDEX IF NOT EXISTS withdrawals_wallet_idx ON withdrawals(wallet_id);
CREATE INDEX IF NOT EXISTS withdrawals_status_idx ON withdrawals(status);

-- --------------------------------------------------------
-- 27. Table: financial_settlements
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS financial_settlements (
  id BIGSERIAL PRIMARY KEY,
  booking_id BIGINT NOT NULL UNIQUE REFERENCES bookings(id) ON DELETE CASCADE,
  driver_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  settlement_reference VARCHAR(50) NOT NULL UNIQUE,
  gross_fare NUMERIC(12,2) NOT NULL,
  platform_commission_percent NUMERIC(5,2) NOT NULL,
  platform_commission_amount NUMERIC(12,2) NOT NULL,
  driver_earning_amount NUMERIC(12,2) NOT NULL,
  service_charge_amount NUMERIC(10,2) DEFAULT 0.00,
  tax_amount NUMERIC(10,2) DEFAULT 0.00,
  discount_amount NUMERIC(10,2) DEFAULT 0.00,
  status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'settled', 'reversed', 'failed')),
  settled_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS fin_settle_driver_idx ON financial_settlements(driver_id);
CREATE INDEX IF NOT EXISTS fin_settle_status_idx ON financial_settlements(status);

-- --------------------------------------------------------
-- 28. Table: refunds
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS refunds (
  id BIGSERIAL PRIMARY KEY,
  payment_id BIGINT NOT NULL REFERENCES payments(id) ON DELETE CASCADE,
  booking_id BIGINT NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
  customer_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  refund_reference VARCHAR(50) NOT NULL UNIQUE,
  amount NUMERIC(12,2) NOT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'full' CHECK (type IN ('full', 'partial')),
  reason TEXT NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'processed', 'rejected', 'failed')),
  approved_by BIGINT REFERENCES users(id) ON DELETE SET NULL,
  approved_at TIMESTAMPTZ,
  processed_at TIMESTAMPTZ,
  gateway_refund_id VARCHAR(100),
  created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS refunds_payment_idx ON refunds(payment_id);
CREATE INDEX IF NOT EXISTS refunds_booking_idx ON refunds(booking_id);
CREATE INDEX IF NOT EXISTS refunds_customer_idx ON refunds(customer_id);
CREATE INDEX IF NOT EXISTS refunds_status_idx ON refunds(status);

-- --------------------------------------------------------
-- DATA SEEDING: Initial Configuration, Roles, & Catalogs
-- --------------------------------------------------------

-- 1. System Settings
INSERT INTO system_settings (key, value, type, description) VALUES
('app_name', 'TripBD', 'string', 'Application Platform Name'),
('app_tagline', 'All-in-One Transport & Logistics Solution in Bangladesh', 'string', 'App Tagline'),
('driver_commission_rate', '15.00', 'decimal', 'Standard platform commission percentage (15%)'),
('ambulance_commission_rate', '0.00', 'decimal', 'Zero-commission incentive for ambulance emergencies'),
('truck_commission_rate', '10.00', 'decimal', 'Truck logistics commission percentage'),
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
('driver_location_poll_seconds', '15', 'integer', 'REST GPS polling interval in seconds'),
('auto_cancel_unaccepted_minutes', '10', 'integer', 'Auto timeout for unassigned requests')
ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value, description = EXCLUDED.description;

-- 2. Service Categories (8 Core Bangladesh Transport Categories)
INSERT INTO service_categories (id, name, slug, description, icon, image, status, sort_order) VALUES
(1, 'Truck Trip', 'truck', 'Reliable logistics and cargo transportation across Bangladesh for commercial, home shifting, and industrial loads.', 'Truck', '/images/services/truck.jpg', 'active', 1),
(2, 'Ambulance Trip', 'ambulance', '24/7 urgent medical transport, ICU, AC, and Non-AC patient transfers with rapid emergency dispatch.', 'Ambulance', '/images/services/ambulance.jpg', 'active', 2),
(3, 'Private Car Trip', 'private-car', 'Comfortable sedans and microbuses for inter-district journeys, family tours, and corporate trips.', 'Car', '/images/services/car.jpg', 'active', 3),
(4, 'Taxi Trip', 'taxi', 'Convenient daily city taxi rides and fast intra-district mobility.', 'Navigation', '/images/services/taxi.jpg', 'active', 4),
(5, 'CNG Auto Rickshaw', 'cng', 'Affordable and swift CNG auto rickshaw rides through urban streets and local zones.', 'Zap', '/images/services/cng.jpg', 'active', 5),
(6, 'Bike Ride', 'bike', 'Fastest solo rider transport beat traffic delays anywhere in town.', 'Bike', '/images/services/bike.jpg', 'active', 6),
(7, 'Return Truck', 'return-truck', 'Up to 50% discount on empty returning trucks across major Bangladesh highway corridors.', 'RefreshCw', '/images/services/return-truck.jpg', 'active', 7),
(8, 'Car Rental', 'rental', 'Chauffeur-driven hourly and day-long vehicle rentals for tours, weddings, and business meetings.', 'Clock', '/images/services/rental.jpg', 'active', 8)
ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, description = EXCLUDED.description, icon = EXCLUDED.icon;

SELECT setval('service_categories_id_seq', (SELECT COALESCE(MAX(id), 1) FROM service_categories));

-- 3. Vehicle Types (14 Bangladesh Transport Tiers)
INSERT INTO vehicle_types (id, service_category_id, name, slug, description, icon, passenger_capacity, load_capacity, base_fare, per_km_rate, per_hour_rate, minimum_fare, status) VALUES
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
ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, base_fare = EXCLUDED.base_fare, per_km_rate = EXCLUDED.per_km_rate, minimum_fare = EXCLUDED.minimum_fare;

SELECT setval('vehicle_types_id_seq', (SELECT COALESCE(MAX(id), 1) FROM vehicle_types));

-- 4. Locations (8 Divisions + 64 Districts of Bangladesh)
INSERT INTO locations (id, name, slug, type, district, division, latitude, longitude, status) VALUES
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
(23, 'Cox''s Bazar', 'coxs-bazar', 'district', 'Cox''s Bazar', 'Chattogram', 21.42720000, 92.00580000, 'active'),
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
ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, latitude = EXCLUDED.latitude, longitude = EXCLUDED.longitude;

SELECT setval('locations_id_seq', (SELECT COALESCE(MAX(id), 1) FROM locations));

-- 5. Seed Users (Demo Admin, Customer, & Drivers)
-- Password for all demo accounts: 'password123' (PHP 8.4 Bcrypt Cost 12 Hash)
-- Hash: $2y$12$lw2yfRZEd2srIHOWGMdRIeIiKL7hAkpAYFPAooDtMRY3meJ1nt/H6
INSERT INTO users (id, name, phone, email, password, role, status, phone_verified_at) VALUES
(1, 'TripBD System Admin', '01700000000', 'admin@tripbd.com', '$2y$12$lw2yfRZEd2srIHOWGMdRIeIiKL7hAkpAYFPAooDtMRY3meJ1nt/H6', 'admin', 'active', NOW()),
(2, 'Tanvir Hasan', '01711111111', 'tanvir@gmail.com', '$2y$12$lw2yfRZEd2srIHOWGMdRIeIiKL7hAkpAYFPAooDtMRY3meJ1nt/H6', 'customer', 'active', NOW()),
(3, 'Md. Rafiqul Islam', '01822222222', 'rafiq.driver@gmail.com', '$2y$12$lw2yfRZEd2srIHOWGMdRIeIiKL7hAkpAYFPAooDtMRY3meJ1nt/H6', 'driver', 'active', NOW()),
(4, 'Jalal Ahmed (Ambulance)', '01933333333', 'jalal.ambulance@gmail.com', '$2y$12$lw2yfRZEd2srIHOWGMdRIeIiKL7hAkpAYFPAooDtMRY3meJ1nt/H6', 'driver', 'active', NOW())
ON CONFLICT (id) DO UPDATE SET name = EXCLUDED.name, phone = EXCLUDED.phone, email = EXCLUDED.email, password = EXCLUDED.password, role = EXCLUDED.role, status = EXCLUDED.status;

SELECT setval('users_id_seq', (SELECT COALESCE(MAX(id), 1) FROM users));

-- 6. Demo Customer Profile
INSERT INTO customer_profiles (id, user_id, address, city, district, country, date_of_birth) VALUES
(1, 2, 'House 42, Road 11, Banani', 'Dhaka', 'Dhaka', 'Bangladesh', '1992-05-14')
ON CONFLICT (id) DO UPDATE SET address = EXCLUDED.address, district = EXCLUDED.district;

SELECT setval('customer_profiles_id_seq', (SELECT COALESCE(MAX(id), 1) FROM customer_profiles));

-- 7. Demo Driver Profiles
INSERT INTO driver_profiles (id, user_id, address, city, district, nid_number, license_number, license_expiry, verification_status, online_status, rating, total_trips) VALUES
(1, 3, 'Tejgaon Truck Stand, Dhaka', 'Dhaka', 'Dhaka', '19881234567890123', 'DK-88992019-PR', '2028-12-31', 'approved', 'online', 4.92, 148),
(2, 4, 'Square Hospital Stand, Panthapath', 'Dhaka', 'Dhaka', '19859876543210123', 'DK-77221044-EM', '2029-06-30', 'approved', 'online', 5.00, 92)
ON CONFLICT (id) DO UPDATE SET verification_status = EXCLUDED.verification_status, online_status = EXCLUDED.online_status, rating = EXCLUDED.rating;

SELECT setval('driver_profiles_id_seq', (SELECT COALESCE(MAX(id), 1) FROM driver_profiles));

-- 8. Demo Driver Wallets
INSERT INTO wallets (id, driver_id, balance) VALUES
(1, 3, 4500.00),
(2, 4, 8200.00)
ON CONFLICT (id) DO UPDATE SET balance = EXCLUDED.balance;

SELECT setval('wallets_id_seq', (SELECT COALESCE(MAX(id), 1) FROM wallets));

-- 9. Demo Vehicles
INSERT INTO vehicles (id, driver_id, vehicle_type_id, vehicle_number, brand, model, year, color, passenger_capacity, load_capacity, registration_number, verification_status, status) VALUES
(1, 1, 1, 'DHAKA METRO TA 11-2345', 'Tata', 'Ace EX2', 2022, 'Blue', 2, 1.00, 'DHAKA-METRO-TA-11-2345', 'approved', 'active'),
(2, 2, 7, 'DHAKA METRO CHHA 71-8899', 'Toyota', 'HiAce High Roof ICU', 2023, 'White', 4, 0.00, 'DHAKA-METRO-CHHA-71-8899', 'approved', 'active')
ON CONFLICT (id) DO UPDATE SET verification_status = EXCLUDED.verification_status, status = EXCLUDED.status;

SELECT setval('vehicles_id_seq', (SELECT COALESCE(MAX(id), 1) FROM vehicles));

COMMIT;
