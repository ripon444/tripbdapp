<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'app_name', 'value' => 'TripBD', 'type' => 'string', 'description' => 'Application Platform Name'],
            ['key' => 'app_tagline', 'value' => 'All-in-One Transport & Logistics Solution in Bangladesh', 'type' => 'string', 'description' => 'App tagline'],
            ['key' => 'driver_commission_rate', 'value' => '10.00', 'type' => 'decimal', 'description' => 'Platform commission rate percentage per completed booking'],
            ['key' => 'ambulance_commission_rate', 'value' => '0.00', 'type' => 'decimal', 'description' => 'Zero-commission social incentive for emergency medical ambulance trips'],
            ['key' => 'truck_commission_rate', 'value' => '8.00', 'type' => 'decimal', 'description' => 'Logistics truck commission percentage'],
            ['key' => 'currency_symbol', 'value' => '৳', 'type' => 'string', 'description' => 'Bangladeshi Taka symbol'],
            ['key' => 'currency_code', 'value' => 'BDT', 'type' => 'string', 'description' => 'ISO Currency Code'],
            ['key' => 'support_phone', 'value' => '+8809612000000', 'type' => 'string', 'description' => '24/7 Helpline contact number'],
            ['key' => 'support_email', 'value' => 'support@tripbd.com', 'type' => 'string', 'description' => 'Official customer care email'],
            ['key' => 'sms_gateway_provider', 'value' => 'greenweb', 'type' => 'string', 'description' => 'SMS Gateway provider (e.g. Greenweb, SSL Wireless, MimSMS)'],
            ['key' => 'otp_expiry_minutes', 'value' => '5', 'type' => 'integer', 'description' => 'OTP validity duration in minutes'],
            ['key' => 'max_search_radius_km', 'value' => '15.00', 'type' => 'decimal', 'description' => 'Maximum driver search radius in kilometers'],
            ['key' => 'driver_location_poll_seconds', 'value' => '15', 'type' => 'integer', 'description' => 'REST GPS polling interval in seconds for shared hosting cPanel'],
            ['key' => 'auto_cancel_unaccepted_minutes', 'value' => '10', 'type' => 'integer', 'description' => 'Minutes before an unassigned booking is auto-escalated or timed out'],
            ['key' => 'emergency_hotline_ambulance', 'value' => '999', 'type' => 'string', 'description' => 'National emergency bypass integration hotline'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
