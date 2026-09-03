<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VehicleType;

class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // Truck Trip (category 1)
            [
                'service_category_id' => 1,
                'name' => '7ft Pickup (1 Ton)',
                'slug' => 'pickup-1-ton',
                'description' => 'Ideal for small apartment shifting, electronic goods, and retail cargo.',
                'icon' => 'Truck',
                'passenger_capacity' => 2,
                'load_capacity' => 1.00,
                'base_fare' => 800.00,
                'per_km_rate' => 45.00,
                'per_hour_rate' => 150.00,
                'minimum_fare' => 1000.00,
                'status' => 'active',
            ],
            [
                'service_category_id' => 1,
                'name' => '14ft Medium Truck (3.5 Ton)',
                'slug' => 'medium-truck-3-5-ton',
                'description' => 'Standard for 2-3 BHK home shifting and light industrial goods.',
                'icon' => 'Truck',
                'passenger_capacity' => 2,
                'load_capacity' => 3.50,
                'base_fare' => 2500.00,
                'per_km_rate' => 70.00,
                'per_hour_rate' => 250.00,
                'minimum_fare' => 3000.00,
                'status' => 'active',
            ],
            [
                'service_category_id' => 1,
                'name' => '18ft Heavy Truck (7.5 Ton)',
                'slug' => 'heavy-truck-7-5-ton',
                'description' => 'Heavy industrial raw materials and factory cargo delivery.',
                'icon' => 'Truck',
                'passenger_capacity' => 2,
                'load_capacity' => 7.50,
                'base_fare' => 5000.00,
                'per_km_rate' => 95.00,
                'per_hour_rate' => 400.00,
                'minimum_fare' => 6000.00,
                'status' => 'active',
            ],
            [
                'service_category_id' => 1,
                'name' => '23ft Heavy Trailer (15 Ton)',
                'slug' => 'heavy-trailer-15-ton',
                'description' => 'Bulk agro-products, machinery, and inter-city heavy transport.',
                'icon' => 'Truck',
                'passenger_capacity' => 2,
                'load_capacity' => 15.00,
                'base_fare' => 9000.00,
                'per_km_rate' => 130.00,
                'per_hour_rate' => 600.00,
                'minimum_fare' => 12000.00,
                'status' => 'active',
            ],

            // Ambulance Trip (category 2)
            [
                'service_category_id' => 2,
                'name' => 'Non-AC Patient Ambulance',
                'slug' => 'ambulance-non-ac',
                'description' => 'Basic emergency transfer with primary stretcher and attendant seat.',
                'icon' => 'Ambulance',
                'passenger_capacity' => 3,
                'load_capacity' => 0.00,
                'base_fare' => 1200.00,
                'per_km_rate' => 35.00,
                'per_hour_rate' => 200.00,
                'minimum_fare' => 1500.00,
                'status' => 'active',
            ],
            [
                'service_category_id' => 2,
                'name' => 'AC Emergency Ambulance',
                'slug' => 'ambulance-ac',
                'description' => 'Climate-controlled ambulance with continuous oxygen cylinder supply.',
                'icon' => 'Ambulance',
                'passenger_capacity' => 3,
                'load_capacity' => 0.00,
                'base_fare' => 2000.00,
                'per_km_rate' => 50.00,
                'per_hour_rate' => 300.00,
                'minimum_fare' => 2500.00,
                'status' => 'active',
            ],
            [
                'service_category_id' => 2,
                'name' => 'ICU / CCU Life Support Ambulance',
                'slug' => 'ambulance-icu',
                'description' => 'Advanced ventilator, cardiac monitor, suction machine, and trained paramedic.',
                'icon' => 'Ambulance',
                'passenger_capacity' => 4,
                'load_capacity' => 0.00,
                'base_fare' => 6000.00,
                'per_km_rate' => 85.00,
                'per_hour_rate' => 600.00,
                'minimum_fare' => 7500.00,
                'status' => 'active',
            ],

            // Private Car (category 3)
            [
                'service_category_id' => 3,
                'name' => 'Sedan Car (4 Seater)',
                'slug' => 'sedan-car',
                'description' => 'Axio, Premio, Allion or equivalent AC sedan for premium travel.',
                'icon' => 'Car',
                'passenger_capacity' => 4,
                'load_capacity' => 0.25,
                'base_fare' => 300.00,
                'per_km_rate' => 28.00,
                'per_hour_rate' => 180.00,
                'minimum_fare' => 450.00,
                'status' => 'active',
            ],
            [
                'service_category_id' => 3,
                'name' => 'Microbus (7-11 Seater)',
                'slug' => 'microbus-noah-hiace',
                'description' => 'Toyota Noah / HiAce for family group tours and airport pickups.',
                'icon' => 'Car',
                'passenger_capacity' => 10,
                'load_capacity' => 0.80,
                'base_fare' => 1500.00,
                'per_km_rate' => 45.00,
                'per_hour_rate' => 350.00,
                'minimum_fare' => 2000.00,
                'status' => 'active',
            ],

            // Taxi Trip (category 4)
            [
                'service_category_id' => 4,
                'name' => 'Standard City Taxi',
                'slug' => 'city-taxi-cab',
                'description' => 'Metropolitan metered taxi service across Dhaka and Chittagong.',
                'icon' => 'Navigation',
                'passenger_capacity' => 4,
                'load_capacity' => 0.15,
                'base_fare' => 150.00,
                'per_km_rate' => 22.00,
                'per_hour_rate' => 120.00,
                'minimum_fare' => 200.00,
                'status' => 'active',
            ],

            // CNG Auto Rickshaw (category 5)
            [
                'service_category_id' => 5,
                'name' => 'Green CNG Auto Rickshaw',
                'slug' => 'cng-auto-rickshaw',
                'description' => 'Standard 3-wheeler auto rickshaw for fast alley and city travel.',
                'icon' => 'Zap',
                'passenger_capacity' => 3,
                'load_capacity' => 0.05,
                'base_fare' => 60.00,
                'per_km_rate' => 18.00,
                'per_hour_rate' => 80.00,
                'minimum_fare' => 90.00,
                'status' => 'active',
            ],

            // Bike Ride (category 6)
            [
                'service_category_id' => 6,
                'name' => 'Standard Motorbike',
                'slug' => 'solo-bike-ride',
                'description' => 'Fastest solo commuter ride with sanitized helmet provided.',
                'icon' => 'Bike',
                'passenger_capacity' => 1,
                'load_capacity' => 0.02,
                'base_fare' => 35.00,
                'per_km_rate' => 14.00,
                'per_hour_rate' => 50.00,
                'minimum_fare' => 50.00,
                'status' => 'active',
            ],

            // Return Truck (category 7)
            [
                'service_category_id' => 7,
                'name' => 'Highway Return Truck (Discounted)',
                'slug' => 'return-empty-truck',
                'description' => 'Empty truck returning along national corridors at 30-50% discounted fares.',
                'icon' => 'RefreshCw',
                'passenger_capacity' => 2,
                'load_capacity' => 5.00,
                'base_fare' => 1800.00,
                'per_km_rate' => 45.00,
                'per_hour_rate' => 150.00,
                'minimum_fare' => 2500.00,
                'status' => 'active',
            ],

            // Car Rental (category 8)
            [
                'service_category_id' => 8,
                'name' => 'Chauffeur Driven Daily Rental',
                'slug' => 'car-rental-daily',
                'description' => 'Full-day (10 hours) dedicated vehicle with professional driver.',
                'icon' => 'Clock',
                'passenger_capacity' => 7,
                'load_capacity' => 0.50,
                'base_fare' => 3500.00,
                'per_km_rate' => 25.00,
                'per_hour_rate' => 200.00,
                'minimum_fare' => 3500.00,
                'status' => 'active',
            ],
        ];

        foreach ($types as $type) {
            VehicleType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
