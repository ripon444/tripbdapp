<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DriverAuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomerBookingController;
use App\Http\Controllers\DriverTripController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DriverWithdrawalController;
use App\Http\Controllers\AdminFinanceController;

/*
|--------------------------------------------------------------------------
| TripBD REST API Routes (Version 5.0 - Phase 5)
|--------------------------------------------------------------------------
| Production-Ready Payment, Wallet, Commission, Refund & Financial Management.
*/

Route::prefix('v1')->group(function () {

    // 1. System Health & Catalog Meta
    Route::get('/health', [HealthController::class, 'check']);
    Route::get('/services', [ServiceCategoryController::class, 'index']);
    Route::get('/services/{id}', [ServiceCategoryController::class, 'show']);
    Route::get('/vehicle-types', [ServiceCategoryController::class, 'vehicleTypes']);

    // Phase 4: Trip Estimation & Fare Calculation (Public / Customer)
    Route::post('/trips/estimate', [TripController::class, 'estimate']);

    // 2. Authentication & OTP (Public API)
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/send-otp', [AuthController::class, 'sendOtp']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

        // Password Reset Workflow
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // 3. Driver Public Registration
    Route::post('/driver/register', [DriverAuthController::class, 'register']);

    // 4. Admin Public Login
    Route::post('/admin/login', [AdminAuthController::class, 'login']);

    // Phase 5: Payment Gateway Webhooks & IPN (Server-to-Server)
    Route::prefix('payments')->group(function () {
        Route::post('/bkash/callback', [PaymentController::class, 'bkashCallback']);
        Route::post('/nagad/callback', [PaymentController::class, 'nagadCallback']);
        Route::post('/rocket/callback', [PaymentController::class, 'rocketCallback']);
        Route::post('/card/webhook', [PaymentController::class, 'cardWebhook']);
    });

    // 5. Authenticated Endpoints (Sanctum Protected)
    Route::middleware(['auth:sanctum', 'account.status'])->group(function () {

        // Generic Current User Profile & Logout
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateMe']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Payments (Authenticated User)
        Route::post('/payments/create', [PaymentController::class, 'store']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);

        // Customer Bookings, Profile & Payments
        Route::middleware('role:customer')->prefix('customer')->group(function () {
            Route::get('/profile', [CustomerController::class, 'profile']);
            Route::put('/profile', [CustomerController::class, 'updateProfile']);
            Route::get('/bookings', [CustomerBookingController::class, 'index']);
            Route::get('/payments', [PaymentController::class, 'customerPayments']);
        });

        // Booking Lifecycle Endpoints
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/bookings/{id}', [BookingController::class, 'show']);
        Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

        // Driver Portal, KYC, Trip Engine & Wallet
        Route::middleware('role:driver')->prefix('driver')->group(function () {
            Route::get('/profile', [DriverAuthController::class, 'profile']);
            Route::put('/profile', [DriverAuthController::class, 'updateProfile']);
            Route::post('/documents', [DriverAuthController::class, 'uploadDocument']);
            Route::post('/vehicles', [DriverAuthController::class, 'registerVehicle']);

            // Driver Availability & Location Heartbeat
            Route::post('/online', [DriverTripController::class, 'goOnline']);
            Route::post('/offline', [DriverTripController::class, 'goOffline']);
            Route::get('/status', [DriverTripController::class, 'getStatus']);
            Route::post('/location', [DriverTripController::class, 'updateLocation']);

            // Driver Trip Operations
            Route::get('/trip-requests', [DriverTripController::class, 'getTripRequests']);
            Route::post('/bookings/{id}/accept', [DriverTripController::class, 'accept']);
            Route::post('/bookings/{id}/reject', [DriverTripController::class, 'reject']);
            Route::post('/bookings/{id}/arrived', [DriverTripController::class, 'arrived']);
            Route::post('/bookings/{id}/start', [DriverTripController::class, 'start']);
            Route::post('/bookings/{id}/complete', [DriverTripController::class, 'complete']);
            Route::post('/bookings/{id}/cancel', [DriverTripController::class, 'cancel']);

            // Phase 5: Driver Payments, Wallet & Withdrawals
            Route::get('/payments', [PaymentController::class, 'driverPayments']);
            Route::post('/withdrawals', [DriverWithdrawalController::class, 'store']);
            Route::get('/withdrawals', [DriverWithdrawalController::class, 'index']);
        });

        // Admin Management Desk & Financial Hub
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            // Dashboard Overview
            Route::get('/overview', [AdminAuthController::class, 'dashboardOverview']);

            // Driver & KYC Management
            Route::get('/drivers', [AdminAuthController::class, 'listDrivers']);
            Route::post('/drivers/{id}/verify', [AdminAuthController::class, 'verifyDriver']);

            // Vehicle Management
            Route::get('/vehicles', [AdminAuthController::class, 'listVehicles']);
            Route::post('/vehicles/{id}/verify', [AdminAuthController::class, 'verifyVehicle']);

            // Booking Management
            Route::get('/bookings', [AdminAuthController::class, 'listBookings']);
            Route::get('/bookings/{id}', [AdminAuthController::class, 'showBooking']);
            Route::post('/bookings/{id}/status', [AdminAuthController::class, 'updateBookingStatus']);

            // Service Catalog Management
            Route::get('/services', [ServiceCategoryController::class, 'adminIndex']);
            Route::post('/services', [ServiceCategoryController::class, 'store']);
            Route::put('/services/{id}', [ServiceCategoryController::class, 'update']);
            Route::post('/vehicle-types', [ServiceCategoryController::class, 'storeVehicleType']);
            Route::put('/vehicle-types/{id}', [ServiceCategoryController::class, 'updateVehicleType']);

            // Phase 5: Financial Hub & Payments
            Route::get('/payments', [PaymentController::class, 'adminPayments']);
            Route::post('/payments/{id}/refund', [PaymentController::class, 'refund']);

            Route::get('/withdrawals', [DriverWithdrawalController::class, 'adminIndex']);
            Route::post('/withdrawals/{id}/approve', [DriverWithdrawalController::class, 'approve']);
            Route::post('/withdrawals/{id}/reject', [DriverWithdrawalController::class, 'reject']);
            Route::post('/withdrawals/{id}/process', [DriverWithdrawalController::class, 'process']);
            Route::post('/withdrawals/{id}/complete', [DriverWithdrawalController::class, 'complete']);

            Route::get('/transactions', [AdminFinanceController::class, 'listTransactions']);
            Route::post('/drivers/{id}/wallet-adjustment', [AdminFinanceController::class, 'walletAdjustment']);
            Route::get('/reports/financial', [AdminFinanceController::class, 'financialReport']);
            Route::get('/settings/financial', [AdminFinanceController::class, 'getSettings']);
            Route::put('/settings/financial', [AdminFinanceController::class, 'updateSettings']);
        });
    });
});
