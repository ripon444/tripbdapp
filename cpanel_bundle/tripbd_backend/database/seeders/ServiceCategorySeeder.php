<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;

class ServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'id' => 1,
                'name' => 'Truck Trip',
                'slug' => 'truck',
                'description' => 'Reliable logistics and cargo transportation across Bangladesh for commercial, home shifting, and industrial loads.',
                'icon' => 'Truck',
                'image' => '/images/services/truck.jpg',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Ambulance Trip',
                'slug' => 'ambulance',
                'description' => '24/7 urgent medical transport, ICU, AC, and Non-AC patient transfers with rapid emergency dispatch.',
                'icon' => 'Ambulance',
                'image' => '/images/services/ambulance.jpg',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'id' => 3,
                'name' => 'Private Car Trip',
                'slug' => 'private-car',
                'description' => 'Comfortable sedans and microbuses for inter-district journeys, family tours, and corporate trips.',
                'icon' => 'Car',
                'image' => '/images/services/car.jpg',
                'status' => 'active',
                'sort_order' => 3,
            ],
            [
                'id' => 4,
                'name' => 'Taxi Trip',
                'slug' => 'taxi',
                'description' => 'Convenient daily city taxi rides and fast intra-district mobility.',
                'icon' => 'Navigation',
                'image' => '/images/services/taxi.jpg',
                'status' => 'active',
                'sort_order' => 4,
            ],
            [
                'id' => 5,
                'name' => 'CNG Auto Rickshaw',
                'slug' => 'cng',
                'description' => 'Affordable and swift CNG auto rickshaw rides through urban streets and local zones.',
                'icon' => 'Zap',
                'image' => '/images/services/cng.jpg',
                'status' => 'active',
                'sort_order' => 5,
            ],
            [
                'id' => 6,
                'name' => 'Bike Ride',
                'slug' => 'bike',
                'description' => 'Fastest solo rider transport beat traffic delays anywhere in town.',
                'icon' => 'Bike',
                'image' => '/images/services/bike.jpg',
                'status' => 'active',
                'sort_order' => 6,
            ],
            [
                'id' => 7,
                'name' => 'Return Truck',
                'slug' => 'return-truck',
                'description' => 'Up to 50% discount on empty returning trucks across major Bangladesh highway corridors.',
                'icon' => 'RefreshCw',
                'image' => '/images/services/return-truck.jpg',
                'status' => 'active',
                'sort_order' => 7,
            ],
            [
                'id' => 8,
                'name' => 'Car Rental',
                'slug' => 'rental',
                'description' => 'Chauffeur-driven hourly and day-long vehicle rentals for tours, weddings, and business meetings.',
                'icon' => 'Clock',
                'image' => '/images/services/rental.jpg',
                'status' => 'active',
                'sort_order' => 8,
            ],
        ];

        foreach ($categories as $category) {
            ServiceCategory::updateOrCreate(['id' => $category['id']], $category);
        }
    }
}
