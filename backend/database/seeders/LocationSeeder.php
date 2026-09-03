<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    /**
     * Seed all 8 Divisions and 64 Districts of Bangladesh with accurate GPS coordinates.
     */
    public function run(): void
    {
        $locations = [
            // ==========================================
            // 8 ADMINISTRATIVE DIVISIONS OF BANGLADESH
            // ==========================================
            ['name' => 'Dhaka Division', 'slug' => 'dhaka-division', 'type' => 'division', 'district' => 'Dhaka', 'division' => 'Dhaka', 'latitude' => 23.8103, 'longitude' => 90.4125],
            ['name' => 'Chattogram Division', 'slug' => 'chattogram-division', 'type' => 'division', 'district' => 'Chattogram', 'division' => 'Chattogram', 'latitude' => 22.3569, 'longitude' => 91.7832],
            ['name' => 'Rajshahi Division', 'slug' => 'rajshahi-division', 'type' => 'division', 'district' => 'Rajshahi', 'division' => 'Rajshahi', 'latitude' => 24.3745, 'longitude' => 88.6042],
            ['name' => 'Khulna Division', 'slug' => 'khulna-division', 'type' => 'division', 'district' => 'Khulna', 'division' => 'Khulna', 'latitude' => 22.8456, 'longitude' => 89.5403],
            ['name' => 'Barishal Division', 'slug' => 'barishal-division', 'type' => 'division', 'district' => 'Barishal', 'division' => 'Barishal', 'latitude' => 22.7010, 'longitude' => 90.3535],
            ['name' => 'Sylhet Division', 'slug' => 'sylhet-division', 'type' => 'division', 'district' => 'Sylhet', 'division' => 'Sylhet', 'latitude' => 24.8949, 'longitude' => 91.8687],
            ['name' => 'Rangpur Division', 'slug' => 'rangpur-division', 'type' => 'division', 'district' => 'Rangpur', 'division' => 'Rangpur', 'latitude' => 25.7439, 'longitude' => 89.2752],
            ['name' => 'Mymensingh Division', 'slug' => 'mymensingh-division', 'type' => 'division', 'district' => 'Mymensingh', 'division' => 'Mymensingh', 'latitude' => 24.7471, 'longitude' => 90.4203],

            // ==========================================
            // 64 DISTRICTS OF BANGLADESH
            // ==========================================

            // Dhaka Division (13 Districts)
            ['name' => 'Dhaka', 'slug' => 'dhaka', 'type' => 'district', 'district' => 'Dhaka', 'division' => 'Dhaka', 'latitude' => 23.8103, 'longitude' => 90.4125],
            ['name' => 'Gazipur', 'slug' => 'gazipur', 'type' => 'district', 'district' => 'Gazipur', 'division' => 'Dhaka', 'latitude' => 23.9999, 'longitude' => 90.4203],
            ['name' => 'Narayanganj', 'slug' => 'narayanganj', 'type' => 'district', 'district' => 'Narayanganj', 'division' => 'Dhaka', 'latitude' => 23.6238, 'longitude' => 90.5000],
            ['name' => 'Tangail', 'slug' => 'tangail', 'type' => 'district', 'district' => 'Tangail', 'division' => 'Dhaka', 'latitude' => 24.2513, 'longitude' => 89.9167],
            ['name' => 'Narsingdi', 'slug' => 'narsingdi', 'type' => 'district', 'district' => 'Narsingdi', 'division' => 'Dhaka', 'latitude' => 23.9322, 'longitude' => 90.7154],
            ['name' => 'Faridpur', 'slug' => 'faridpur', 'type' => 'district', 'district' => 'Faridpur', 'division' => 'Dhaka', 'latitude' => 23.6070, 'longitude' => 89.8429],
            ['name' => 'Manikganj', 'slug' => 'manikganj', 'type' => 'district', 'district' => 'Manikganj', 'division' => 'Dhaka', 'latitude' => 23.8644, 'longitude' => 90.0047],
            ['name' => 'Munshiganj', 'slug' => 'munshiganj', 'type' => 'district', 'district' => 'Munshiganj', 'division' => 'Dhaka', 'latitude' => 23.5422, 'longitude' => 90.5305],
            ['name' => 'Rajbari', 'slug' => 'rajbari', 'type' => 'district', 'district' => 'Rajbari', 'division' => 'Dhaka', 'latitude' => 23.7574, 'longitude' => 89.6444],
            ['name' => 'Gopalganj', 'slug' => 'gopalganj', 'type' => 'district', 'district' => 'Gopalganj', 'division' => 'Dhaka', 'latitude' => 23.0051, 'longitude' => 89.8266],
            ['name' => 'Madaripur', 'slug' => 'madaripur', 'type' => 'district', 'district' => 'Madaripur', 'division' => 'Dhaka', 'latitude' => 23.1641, 'longitude' => 90.1897],
            ['name' => 'Shariatpur', 'slug' => 'shariatpur', 'type' => 'district', 'district' => 'Shariatpur', 'division' => 'Dhaka', 'latitude' => 23.2423, 'longitude' => 90.4348],
            ['name' => 'Kishoreganj', 'slug' => 'kishoreganj', 'type' => 'district', 'district' => 'Kishoreganj', 'division' => 'Dhaka', 'latitude' => 24.4449, 'longitude' => 90.7766],

            // Chattogram Division (11 Districts)
            ['name' => 'Chattogram (Chittagong)', 'slug' => 'chattogram', 'type' => 'district', 'district' => 'Chattogram', 'division' => 'Chattogram', 'latitude' => 22.3569, 'longitude' => 91.7832],
            ['name' => 'Cox\'s Bazar', 'slug' => 'coxs-bazar', 'type' => 'district', 'district' => 'Cox\'s Bazar', 'division' => 'Chattogram', 'latitude' => 21.4272, 'longitude' => 92.0058],
            ['name' => 'Cumilla (Comilla)', 'slug' => 'cumilla', 'type' => 'district', 'district' => 'Cumilla', 'division' => 'Chattogram', 'latitude' => 23.4682, 'longitude' => 91.1788],
            ['name' => 'Feni', 'slug' => 'feni', 'type' => 'district', 'district' => 'Feni', 'division' => 'Chattogram', 'latitude' => 23.0159, 'longitude' => 91.3976],
            ['name' => 'Brahmanbaria', 'slug' => 'brahmanbaria', 'type' => 'district', 'district' => 'Brahmanbaria', 'division' => 'Chattogram', 'latitude' => 23.9571, 'longitude' => 91.1115],
            ['name' => 'Rangamati', 'slug' => 'rangamati', 'type' => 'district', 'district' => 'Rangamati', 'division' => 'Chattogram', 'latitude' => 22.6533, 'longitude' => 92.1753],
            ['name' => 'Noakhali', 'slug' => 'noakhali', 'type' => 'district', 'district' => 'Noakhali', 'division' => 'Chattogram', 'latitude' => 22.8696, 'longitude' => 91.0994],
            ['name' => 'Chandpur', 'slug' => 'chandpur', 'type' => 'district', 'district' => 'Chandpur', 'division' => 'Chattogram', 'latitude' => 23.2333, 'longitude' => 90.6667],
            ['name' => 'Lakshmipur', 'slug' => 'lakshmipur', 'type' => 'district', 'district' => 'Lakshmipur', 'division' => 'Chattogram', 'latitude' => 22.9425, 'longitude' => 90.8412],
            ['name' => 'Bandarban', 'slug' => 'bandarban', 'type' => 'district', 'district' => 'Bandarban', 'division' => 'Chattogram', 'latitude' => 22.1953, 'longitude' => 92.2184],
            ['name' => 'Khagrachhari', 'slug' => 'khagrachhari', 'type' => 'district', 'district' => 'Khagrachhari', 'division' => 'Chattogram', 'latitude' => 23.1193, 'longitude' => 91.9847],

            // Rajshahi Division (8 Districts)
            ['name' => 'Rajshahi', 'slug' => 'rajshahi', 'type' => 'district', 'district' => 'Rajshahi', 'division' => 'Rajshahi', 'latitude' => 24.3745, 'longitude' => 88.6042],
            ['name' => 'Bogura (Bogra)', 'slug' => 'bogura', 'type' => 'district', 'district' => 'Bogura', 'division' => 'Rajshahi', 'latitude' => 24.8465, 'longitude' => 89.3777],
            ['name' => 'Pabna', 'slug' => 'pabna', 'type' => 'district', 'district' => 'Pabna', 'division' => 'Rajshahi', 'latitude' => 24.0064, 'longitude' => 89.2372],
            ['name' => 'Sirajganj', 'slug' => 'sirajganj', 'type' => 'district', 'district' => 'Sirajganj', 'division' => 'Rajshahi', 'latitude' => 24.4534, 'longitude' => 89.7006],
            ['name' => 'Naogaon', 'slug' => 'naogaon', 'type' => 'district', 'district' => 'Naogaon', 'division' => 'Rajshahi', 'latitude' => 24.8103, 'longitude' => 88.9414],
            ['name' => 'Natore', 'slug' => 'natore', 'type' => 'district', 'district' => 'Natore', 'division' => 'Rajshahi', 'latitude' => 24.4206, 'longitude' => 88.9324],
            ['name' => 'Chapainawabganj', 'slug' => 'chapainawabganj', 'type' => 'district', 'district' => 'Chapainawabganj', 'division' => 'Rajshahi', 'latitude' => 24.5965, 'longitude' => 88.2775],
            ['name' => 'Joypurhat', 'slug' => 'joypurhat', 'type' => 'district', 'district' => 'Joypurhat', 'division' => 'Rajshahi', 'latitude' => 25.1015, 'longitude' => 89.0275],

            // Khulna Division (10 Districts)
            ['name' => 'Khulna', 'slug' => 'khulna', 'type' => 'district', 'district' => 'Khulna', 'division' => 'Khulna', 'latitude' => 22.8456, 'longitude' => 89.5403],
            ['name' => 'Jashore (Jessore)', 'slug' => 'jashore', 'type' => 'district', 'district' => 'Jashore', 'division' => 'Khulna', 'latitude' => 23.1664, 'longitude' => 89.2081],
            ['name' => 'Kushtia', 'slug' => 'kushtia', 'type' => 'district', 'district' => 'Kushtia', 'division' => 'Khulna', 'latitude' => 23.9013, 'longitude' => 89.1205],
            ['name' => 'Satkhira', 'slug' => 'satkhira', 'type' => 'district', 'district' => 'Satkhira', 'division' => 'Khulna', 'latitude' => 22.7185, 'longitude' => 89.0705],
            ['name' => 'Bagerhat', 'slug' => 'bagerhat', 'type' => 'district', 'district' => 'Bagerhat', 'division' => 'Khulna', 'latitude' => 22.6516, 'longitude' => 89.7859],
            ['name' => 'Jhenaidah', 'slug' => 'jhenaidah', 'type' => 'district', 'district' => 'Jhenaidah', 'division' => 'Khulna', 'latitude' => 23.5448, 'longitude' => 89.1539],
            ['name' => 'Chuadanga', 'slug' => 'chuadanga', 'type' => 'district', 'district' => 'Chuadanga', 'division' => 'Khulna', 'latitude' => 23.6402, 'longitude' => 88.8418],
            ['name' => 'Magura', 'slug' => 'magura', 'type' => 'district', 'district' => 'Magura', 'division' => 'Khulna', 'latitude' => 23.4873, 'longitude' => 89.4198],
            ['name' => 'Meherpur', 'slug' => 'meherpur', 'type' => 'district', 'district' => 'Meherpur', 'division' => 'Khulna', 'latitude' => 23.7622, 'longitude' => 88.6318],
            ['name' => 'Narail', 'slug' => 'narail', 'type' => 'district', 'district' => 'Narail', 'division' => 'Khulna', 'latitude' => 23.1725, 'longitude' => 89.5127],

            // Barishal Division (6 Districts)
            ['name' => 'Barishal (Barisal)', 'slug' => 'barishal', 'type' => 'district', 'district' => 'Barishal', 'division' => 'Barishal', 'latitude' => 22.7010, 'longitude' => 90.3535],
            ['name' => 'Patuakhali', 'slug' => 'patuakhali', 'type' => 'district', 'district' => 'Patuakhali', 'division' => 'Barishal', 'latitude' => 22.3596, 'longitude' => 90.3299],
            ['name' => 'Bhola', 'slug' => 'bhola', 'type' => 'district', 'district' => 'Bhola', 'division' => 'Barishal', 'latitude' => 22.6859, 'longitude' => 90.6481],
            ['name' => 'Pirojpur', 'slug' => 'pirojpur', 'type' => 'district', 'district' => 'Pirojpur', 'division' => 'Barishal', 'latitude' => 22.5841, 'longitude' => 89.9720],
            ['name' => 'Barguna', 'slug' => 'barguna', 'type' => 'district', 'district' => 'Barguna', 'division' => 'Barishal', 'latitude' => 22.0953, 'longitude' => 90.0770],
            ['name' => 'Jhalokati', 'slug' => 'jhalokati', 'type' => 'district', 'district' => 'Jhalokati', 'division' => 'Barishal', 'latitude' => 22.6406, 'longitude' => 90.1987],

            // Sylhet Division (4 Districts)
            ['name' => 'Sylhet', 'slug' => 'sylhet', 'type' => 'district', 'district' => 'Sylhet', 'division' => 'Sylhet', 'latitude' => 24.8949, 'longitude' => 91.8687],
            ['name' => 'Moulvibazar', 'slug' => 'moulvibazar', 'type' => 'district', 'district' => 'Moulvibazar', 'division' => 'Sylhet', 'latitude' => 24.4829, 'longitude' => 91.7774],
            ['name' => 'Habiganj', 'slug' => 'habiganj', 'type' => 'district', 'district' => 'Habiganj', 'division' => 'Sylhet', 'latitude' => 24.3749, 'longitude' => 91.4155],
            ['name' => 'Sunamganj', 'slug' => 'sunamganj', 'type' => 'district', 'district' => 'Sunamganj', 'division' => 'Sylhet', 'latitude' => 25.0658, 'longitude' => 91.3950],

            // Rangpur Division (8 Districts)
            ['name' => 'Rangpur', 'slug' => 'rangpur', 'type' => 'district', 'district' => 'Rangpur', 'division' => 'Rangpur', 'latitude' => 25.7439, 'longitude' => 89.2752],
            ['name' => 'Dinajpur', 'slug' => 'dinajpur', 'type' => 'district', 'district' => 'Dinajpur', 'division' => 'Rangpur', 'latitude' => 25.6217, 'longitude' => 88.6355],
            ['name' => 'Gaibandha', 'slug' => 'gaibandha', 'type' => 'district', 'district' => 'Gaibandha', 'division' => 'Rangpur', 'latitude' => 25.3288, 'longitude' => 89.5406],
            ['name' => 'Kurigram', 'slug' => 'kurigram', 'type' => 'district', 'district' => 'Kurigram', 'division' => 'Rangpur', 'latitude' => 25.8054, 'longitude' => 89.6362],
            ['name' => 'Nilphamari', 'slug' => 'nilphamari', 'type' => 'district', 'district' => 'Nilphamari', 'division' => 'Rangpur', 'latitude' => 25.9318, 'longitude' => 88.8560],
            ['name' => 'Lalmonirhat', 'slug' => 'lalmonirhat', 'type' => 'district', 'district' => 'Lalmonirhat', 'division' => 'Rangpur', 'latitude' => 25.9923, 'longitude' => 89.2847],
            ['name' => 'Thakurgaon', 'slug' => 'thakurgaon', 'type' => 'district', 'district' => 'Thakurgaon', 'division' => 'Rangpur', 'latitude' => 26.0337, 'longitude' => 88.4617],
            ['name' => 'Panchagarh', 'slug' => 'panchagarh', 'type' => 'district', 'district' => 'Panchagarh', 'division' => 'Rangpur', 'latitude' => 26.3411, 'longitude' => 88.5542],

            // Mymensingh Division (4 Districts)
            ['name' => 'Mymensingh', 'slug' => 'mymensingh', 'type' => 'district', 'district' => 'Mymensingh', 'division' => 'Mymensingh', 'latitude' => 24.7471, 'longitude' => 90.4203],
            ['name' => 'Jamalpur', 'slug' => 'jamalpur', 'type' => 'district', 'district' => 'Jamalpur', 'division' => 'Mymensingh', 'latitude' => 24.9375, 'longitude' => 89.9378],
            ['name' => 'Netrokona', 'slug' => 'netrokona', 'type' => 'district', 'district' => 'Netrokona', 'division' => 'Mymensingh', 'latitude' => 24.8709, 'longitude' => 90.7279],
            ['name' => 'Sherpur', 'slug' => 'sherpur', 'type' => 'district', 'district' => 'Sherpur', 'division' => 'Mymensingh', 'latitude' => 25.0205, 'longitude' => 90.0153],
        ];

        foreach ($locations as $loc) {
            Location::updateOrCreate(['slug' => $loc['slug']], $loc);
        }
    }
}
