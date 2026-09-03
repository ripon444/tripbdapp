<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function all_25_required_tables_exist_in_database(): void
    {
        $expectedTables = [
            'users',
            'customer_profiles',
            'driver_profiles',
            'service_categories',
            'vehicle_types',
            'vehicles',
            'driver_documents',
            'vehicle_documents',
            'locations',
            'bookings',
            'booking_status_history',
            'driver_locations',
            'payments',
            'wallets',
            'wallet_transactions',
            'ratings',
            'complaints',
            'notifications',
            'promo_codes',
            'promo_code_usages',
            'system_settings',
            'admin_logs',
            'personal_access_tokens',
            'failed_jobs',
            'otps'
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table [{$table}] does not exist in the database schema."
            );
        }
    }
}
