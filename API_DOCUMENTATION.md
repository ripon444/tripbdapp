# TripBD — Production REST API Specification (Version 4.0 - Phase 4)

All endpoints accept and return standard `application/json`.
Base URL: `/api/v1/`

---

## 1. System Health & Catalogs
- **`GET /api/v1/health`**
  - Verifies database status, cPanel compatibility mode, and service counts.
- **`GET /api/v1/services`**
  - Lists all 8 core service categories (Truck, Ambulance, Private Car, Taxi, CNG, Bike, Return Truck, Car Rental).
- **`GET /api/v1/vehicle-types`**
  - Returns all vehicle types with pricing models, per-KM rates, minimum fares, and load/passenger capacities.
- **`GET /api/v1/locations/districts`**
  - Returns the official registry of all 64 Bangladesh districts with verified GPS centroids and divisional hierarchy.
- **`GET /api/v1/database/tables`**
  - Inspects all 25 production database tables with engine details, indexes, and foreign keys.

---

## 2. Authentication & User Management (Sanctum Tokens)
- **`POST /api/v1/auth/send-otp`**
  - **Body**: `{ "phone": "01712345678", "purpose": "registration" | "login" | "reset_password" }`
- **`POST /api/v1/auth/verify-otp`**
  - **Body**: `{ "phone": "01712345678", "code": "123456", "purpose": "registration" }`
- **`POST /api/v1/auth/register`**
  - **Body**: `{ "name": "Customer Name", "phone": "01712345678", "password": "SecurePassword123!", "role": "customer" }`
- **`POST /api/v1/auth/login`**
  - **Body**: `{ "phone": "01712345678", "password": "SecurePassword123!" }`
  - **Response**: `{ "token": "sanctum_token_...", "user": { "id": 2, "name": "...", "role": "customer" } }`
- **`POST /api/v1/driver/register`**
  - **Body**: `{ "name": "Driver Name", "phone": "01812345678", "password": "...", "license_number": "DL-DH-992384", "nid_number": "1994829384918" }`
- **`GET /api/v1/me`** (Bearer Token required)
- **`POST /api/v1/auth/logout`** (Bearer Token required)

---

## 3. Fare Calculation & Trip Booking Engine
- **`POST /api/v1/trips/estimate`**
  - **Description**: Server-authoritative fare estimation, boundary validation, and real-time nearby driver matching.
  - **Body**:
    ```json
    {
      "service_category_id": 1,
      "vehicle_type_id": 1,
      "pickup_latitude": 23.7937,
      "pickup_longitude": 90.4066,
      "destination_latitude": 22.3274,
      "destination_longitude": 91.8123,
      "passenger_count": 1,
      "load_weight": 0.85,
      "trip_type": "one_way",
      "rental_hours": null,
      "promo_code": "TRIPBD50"
    }
    ```
  - **Response**:
    ```json
    {
      "success": true,
      "data": {
        "base_fare": 800.00,
        "distance_fare": 9828.00,
        "time_fare": 0.00,
        "load_charge": 85.00,
        "return_trip_charge": 0.00,
        "service_charge": 535.65,
        "discount": 300.00,
        "tax": 0.00,
        "total_fare": 10948.65,
        "distance_km": 218.40,
        "duration_minutes": 447,
        "matched_drivers": [ ... ]
      }
    }
    ```

- **`POST /api/v1/bookings`**
  - **Description**: Creates a new booking, generates collision-safe `TRP-YYYYMMDD-XXXXXX` number, and pushes to dispatch queue.
  - **Body**: Same schema as estimate with required `pickup_address` and `destination_address`.
  - **Response**: `{ "success": true, "message": "Trip booking created successfully!", "data": { "id": 2, "booking_number": "TRP-20260820-891024", "status": "searching_driver", ... } }`

- **`GET /api/v1/bookings/:id`**
  - Returns booking details, vehicle details, driver profile, timeline history, and live driver GPS.
  - Authorization: Customer can only view their own booking; Driver can only view assigned booking; Admin can view all.

- **`POST /api/v1/bookings/:id/cancel`**
  - **Body**: `{ "reason": "Customer changed plan" }`
  - Authorization: Customer can only cancel their own uncompleted bookings.

- **`GET /api/v1/customer/bookings`**
  - Returns all historical bookings for authenticated customer.

---

## 4. Driver Dispatch & Lifecycle Management
- **`POST /api/v1/driver/online`**
  - Switches driver status to online (requires approved KYC and active vehicle).
- **`POST /api/v1/driver/offline`**
  - Switches driver status to offline (blocked if driver has active assigned trip).
- **`GET /api/v1/driver/status`**
  - Returns online status, wallet balance, active trip, and vehicle details.
- **`POST /api/v1/driver/location`** (GPS Telemetry Polling Heartbeat)
  - **Body**: `{ "latitude": 23.8103, "longitude": 90.4125, "heading": 45, "speed": 28.5, "accuracy": 5 }`
  - Enforces strict Bangladesh geographic boundary validation.
- **`GET /api/v1/driver/trip-requests`**
  - Real-time nearby available trip requests filtered for driver's vehicle type and proximity.
- **`POST /api/v1/driver/bookings/:id/accept`**
  - Concurrency-locked acceptance. Prevents race conditions / double driver assignment.
- **`POST /api/v1/driver/bookings/:id/arrived`**
  - Marks driver arrived at customer pickup spot.
- **`POST /api/v1/driver/bookings/:id/start`**
  - Commences journey en route to destination.
- **`POST /api/v1/driver/bookings/:id/complete`**
  - Finalizes trip, records collected fare, automatically calculates 85% driver wallet credit and 15% platform commission via centralized `systemSettingsStore`.

---

## 5. Payments, Wallet, Financial Settlements & Admin Hub (Phase 5)

### Payment Processing & Webhooks
- **`POST /api/v1/payments/create`** (Authenticated Customer/Admin)
  - **Body**: `{ "booking_id": 1, "payment_method": "cash" | "bkash" | "nagad" | "rocket" | "card" }`
  - Supports `Idempotency-Key` HTTP header.
  - Returns gateway session URL or immediate cash confirmation.
- **`GET /api/v1/payments/:id`**
  - Returns payment transaction status and metadata.
- **`GET /api/v1/customer/payments`**
  - Customer payment history.
- **`GET /api/v1/driver/payments`**
  - Driver trip payment and earnings history.
- **`POST /api/v1/payments/bkash/callback`**
  - Server-to-server bKash tokenized payment verification webhook.
- **`POST /api/v1/payments/nagad/callback`**
  - Nagad remote payment gateway IPN callback.
- **`POST /api/v1/payments/rocket/callback`**
  - DBBL Rocket merchant webhook callback.
- **`POST /api/v1/payments/card/webhook`**
  - SSLCommerz / PCI hosted card callback.

### Driver Wallet & Payout Withdrawals
- **`GET /api/v1/driver/withdrawals`**
  - Driver payout history with masked account numbers (`01******678`).
- **`POST /api/v1/driver/withdrawals`**
  - **Body**: `{ "amount": 1500.00, "method": "bkash" | "nagad" | "rocket" | "bank_transfer", "account_number": "01712345678" }`
  - Validates available wallet balance and minimum threshold.

### Admin Financial Management & Reports
- **`GET /api/v1/admin/payments`**
  - All platform transactions with filter by status, method, date.
- **`POST /api/v1/admin/payments/:id/refund`**
  - **Body**: `{ "amount": 500.00, "reason": "Driver vehicle mechanical breakdown", "type": "full" | "partial" }`
  - Issues ledger-consistent refund and records AdminLog.
- **`GET /api/v1/admin/withdrawals`**
  - Lists driver payout requests.
- **`POST /api/v1/admin/withdrawals/:id/approve`**
  - Approves payout request.
- **`POST /api/v1/admin/withdrawals/:id/reject`**
  - **Body**: `{ "reason": "Account number mismatch" }`
- **`POST /api/v1/admin/withdrawals/:id/complete`**
  - Finalizes payout and debits driver wallet.
- **`POST /api/v1/admin/drivers/:driver/wallet-adjustment`**
  - **Body**: `{ "amount": 500, "type": "bonus" | "penalty" | "adjustment", "reason": "Exemplary customer service rating" }`
- **`GET /api/v1/admin/reports/financial`**
  - **Query**: `?period=daily|weekly|monthly|all`
  - Returns Gross Booking Value, Platform Commission, Driver Earnings, Cash vs Digital split, and Net Revenue.
- **`GET /api/v1/admin/settings/financial`**
  - Retrieves configurable platform commission %, minimum/maximum withdrawal, etc.
- **`PUT /api/v1/admin/settings/financial`**
  - Updates financial parameters and logs changes.

---

## 6. Verification & Test Suite
- **`POST /api/v1/auth/run-tests`** or **`GET /api/v1/test-suite`**
  - Runs full 68 automated unit & feature tests (44 Existing + 24 Phase 5).
