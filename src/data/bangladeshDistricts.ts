import { DistrictLocation } from '../types';

export const BANGLADESH_DISTRICTS: DistrictLocation[] = [
  // Dhaka Division (13 Districts)
  { id: 1, name: 'Dhaka', bn_name: 'ঢাকা', division: 'Dhaka', latitude: 23.8103, longitude: 90.4125 },
  { id: 2, name: 'Gazipur', bn_name: 'গাজীপুর', division: 'Dhaka', latitude: 23.9999, longitude: 90.4203 },
  { id: 3, name: 'Narayanganj', bn_name: 'নারায়ণগঞ্জ', division: 'Dhaka', latitude: 23.6238, longitude: 90.5000 },
  { id: 4, name: 'Tangail', bn_name: 'টাঙ্গাইল', division: 'Dhaka', latitude: 24.2513, longitude: 89.9167 },
  { id: 5, name: 'Narsingdi', bn_name: 'নরসিংদী', division: 'Dhaka', latitude: 23.9322, longitude: 90.7154 },
  { id: 6, name: 'Faridpur', bn_name: 'ফরিদপুর', division: 'Dhaka', latitude: 23.6070, longitude: 89.8429 },
  { id: 7, name: 'Manikganj', bn_name: 'মানিকগঞ্জ', division: 'Dhaka', latitude: 23.8644, longitude: 90.0047 },
  { id: 8, name: 'Munshiganj', bn_name: 'মুন্সীগঞ্জ', division: 'Dhaka', latitude: 23.5422, longitude: 90.5305 },
  { id: 9, name: 'Rajbari', bn_name: 'রাজবাড়ী', division: 'Dhaka', latitude: 23.7574, longitude: 89.6444 },
  { id: 10, name: 'Gopalganj', bn_name: 'গোপালগঞ্জ', division: 'Dhaka', latitude: 23.0051, longitude: 89.8266 },
  { id: 11, name: 'Madaripur', bn_name: 'মাদারীপুর', division: 'Dhaka', latitude: 23.1641, longitude: 90.1897 },
  { id: 12, name: 'Shariatpur', bn_name: 'শরীয়তপুর', division: 'Dhaka', latitude: 23.2423, longitude: 90.4348 },
  { id: 13, name: 'Kishoreganj', bn_name: 'কিশোরগঞ্জ', division: 'Dhaka', latitude: 24.4449, longitude: 90.7766 },

  // Chattogram Division (11 Districts)
  { id: 14, name: 'Chattogram (Chittagong)', bn_name: 'চট্টগ্রাম', division: 'Chattogram', latitude: 22.3569, longitude: 91.7832 },
  { id: 15, name: "Cox's Bazar", bn_name: 'কক্সবাজার', division: 'Chattogram', latitude: 21.4272, longitude: 92.0058 },
  { id: 16, name: 'Cumilla (Comilla)', bn_name: 'কুমিল্লা', division: 'Chattogram', latitude: 23.4682, longitude: 91.1788 },
  { id: 17, name: 'Feni', bn_name: 'ফেনী', division: 'Chattogram', latitude: 23.0159, longitude: 91.3976 },
  { id: 18, name: 'Brahmanbaria', bn_name: 'ব্রাহ্মণবাড়িয়া', division: 'Chattogram', latitude: 23.9571, longitude: 91.1115 },
  { id: 19, name: 'Rangamati', bn_name: 'রাঙ্গামাটি', division: 'Chattogram', latitude: 22.6533, longitude: 92.1753 },
  { id: 20, name: 'Noakhali', bn_name: 'নোয়াখালী', division: 'Chattogram', latitude: 22.8696, longitude: 91.0994 },
  { id: 21, name: 'Chandpur', bn_name: 'চাঁদপুর', division: 'Chattogram', latitude: 23.2333, longitude: 90.6667 },
  { id: 22, name: 'Lakshmipur', bn_name: 'লক্ষ্মীপুর', division: 'Chattogram', latitude: 22.9425, longitude: 90.8412 },
  { id: 23, name: 'Bandarban', bn_name: 'বান্দরবান', division: 'Chattogram', latitude: 22.1953, longitude: 92.2184 },
  { id: 24, name: 'Khagrachhari', bn_name: 'খাগড়াছড়ি', division: 'Chattogram', latitude: 23.1193, longitude: 91.9847 },

  // Rajshahi Division (8 Districts)
  { id: 25, name: 'Rajshahi', bn_name: 'রাজশাহী', division: 'Rajshahi', latitude: 24.3745, longitude: 88.6042 },
  { id: 26, name: 'Bogura (Bogra)', bn_name: 'বগুড়া', division: 'Rajshahi', latitude: 24.8465, longitude: 89.3777 },
  { id: 27, name: 'Pabna', bn_name: 'পাবনা', division: 'Rajshahi', latitude: 24.0064, longitude: 89.2372 },
  { id: 28, name: 'Sirajganj', bn_name: 'সিরাজগঞ্জ', division: 'Rajshahi', latitude: 24.4534, longitude: 89.7008 },
  { id: 29, name: 'Naogaon', bn_name: 'নওগাঁ', division: 'Rajshahi', latitude: 24.8103, longitude: 88.9414 },
  { id: 30, name: 'Natore', bn_name: 'নাটোর', division: 'Rajshahi', latitude: 24.4206, longitude: 88.9322 },
  { id: 31, name: 'Chapai Nawabganj', bn_name: 'চাঁপাইনবাবগঞ্জ', division: 'Rajshahi', latitude: 24.5965, longitude: 88.2775 },
  { id: 32, name: 'Joypurhat', bn_name: 'জয়পুরহাট', division: 'Rajshahi', latitude: 25.1017, longitude: 89.0275 },

  // Khulna Division (10 Districts)
  { id: 33, name: 'Khulna', bn_name: 'খুলনা', division: 'Khulna', latitude: 22.8456, longitude: 89.5403 },
  { id: 34, name: 'Jashore (Jessore)', bn_name: 'যশোর', division: 'Khulna', latitude: 23.1664, longitude: 89.2081 },
  { id: 35, name: 'Kushtia', bn_name: 'কুষ্টিয়া', division: 'Khulna', latitude: 23.9013, longitude: 89.1205 },
  { id: 36, name: 'Satkhira', bn_name: 'সাতক্ষীরা', division: 'Khulna', latitude: 22.7185, longitude: 89.0705 },
  { id: 37, name: 'Bagerhat', bn_name: 'বাগেরহাট', division: 'Khulna', latitude: 22.6516, longitude: 89.7859 },
  { id: 38, name: 'Jhenaidah', bn_name: 'ঝিনাইদহ', division: 'Khulna', latitude: 23.5448, longitude: 89.1539 },
  { id: 39, name: 'Chuadanga', bn_name: 'চুয়াডাঙ্গা', division: 'Khulna', latitude: 23.6402, longitude: 88.8418 },
  { id: 40, name: 'Magura', bn_name: 'মাগুরা', division: 'Khulna', latitude: 23.4873, longitude: 89.4199 },
  { id: 41, name: 'Narail', bn_name: 'নড়াইল', division: 'Khulna', latitude: 23.1725, longitude: 89.5127 },
  { id: 42, name: 'Meherpur', bn_name: 'মেহেরপুর', division: 'Khulna', latitude: 23.7622, longitude: 88.6318 },

  // Barishal Division (6 Districts)
  { id: 43, name: 'Barishal', bn_name: 'বরিশাল', division: 'Barishal', latitude: 22.7010, longitude: 90.3535 },
  { id: 44, name: 'Bhola', bn_name: 'ভোলা', division: 'Barishal', latitude: 22.6859, longitude: 90.6481 },
  { id: 45, name: 'Patuakhali', bn_name: 'পটুয়াখালী', division: 'Barishal', latitude: 22.3596, longitude: 90.3299 },
  { id: 46, name: 'Pirojpur', bn_name: 'পিরোজপুর', division: 'Barishal', latitude: 22.5841, longitude: 89.9720 },
  { id: 47, name: 'Barguna', bn_name: 'বরগুনা', division: 'Barishal', latitude: 22.1570, longitude: 90.1256 },
  { id: 48, name: 'Jhalokathi', bn_name: 'ঝালকাঠি', division: 'Barishal', latitude: 22.6406, longitude: 90.1987 },

  // Sylhet Division (4 Districts)
  { id: 49, name: 'Sylhet', bn_name: 'সিলেট', division: 'Sylhet', latitude: 24.8949, longitude: 91.8687 },
  { id: 50, name: 'Moulvibazar', bn_name: 'মৌলভীবাজার', division: 'Sylhet', latitude: 24.4829, longitude: 91.7774 },
  { id: 51, name: 'Habiganj', bn_name: 'হবিগঞ্জ', division: 'Sylhet', latitude: 24.3749, longitude: 91.4155 },
  { id: 52, name: 'Sunamganj', bn_name: 'সুনামগঞ্জ', division: 'Sylhet', latitude: 25.0658, longitude: 91.3950 },

  // Rangpur Division (8 Districts)
  { id: 53, name: 'Rangpur', bn_name: 'রংপুর', division: 'Rangpur', latitude: 25.7439, longitude: 89.2752 },
  { id: 54, name: 'Dinajpur', bn_name: 'দিনাজপুর', division: 'Rangpur', latitude: 25.6217, longitude: 88.6355 },
  { id: 55, name: 'Gaibandha', bn_name: 'গাইবান্ধা', division: 'Rangpur', latitude: 25.3288, longitude: 89.5404 },
  { id: 56, name: 'Kurigram', bn_name: 'কুড়িগ্রাম', division: 'Rangpur', latitude: 25.8054, longitude: 89.6362 },
  { id: 57, name: 'Nilphamari', bn_name: 'নীলফামারী', division: 'Rangpur', latitude: 25.9318, longitude: 88.8560 },
  { id: 58, name: 'Lalmonirhat', bn_name: 'লালমনিরহাট', division: 'Rangpur', latitude: 25.9165, longitude: 89.4532 },
  { id: 59, name: 'Thakurgaon', bn_name: 'ঠাকুরগাঁও', division: 'Rangpur', latitude: 26.0337, longitude: 88.4617 },
  { id: 60, name: 'Panchagarh', bn_name: 'পঞ্চগড়', division: 'Rangpur', latitude: 26.3411, longitude: 88.5542 },

  // Mymensingh Division (4 Districts)
  { id: 61, name: 'Mymensingh', bn_name: 'ময়মনসিংহ', division: 'Mymensingh', latitude: 24.7471, longitude: 90.4203 },
  { id: 62, name: 'Jamalpur', bn_name: 'জামালপুর', division: 'Mymensingh', latitude: 24.9375, longitude: 89.9378 },
  { id: 63, name: 'Netrokona', bn_name: 'নেত্রকোণা', division: 'Mymensingh', latitude: 24.8709, longitude: 90.7279 },
  { id: 64, name: 'Sherpur', bn_name: 'শেরপুর', division: 'Mymensingh', latitude: 25.0205, longitude: 90.0153 }
];
