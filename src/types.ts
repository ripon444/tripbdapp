export interface ServiceCategory {
  id: number;
  slug: string;
  name: string;
  description: string;
  icon: string;
  status: string;
  sort_order: number;
  vehicle_types_count?: number;
}

export interface VehicleType {
  id: number;
  service_category_id: number;
  category_slug?: string;
  name: string;
  slug: string;
  description?: string;
  passenger_capacity?: number;
  load_capacity?: string | number;
  base_fare: number;
  per_km_rate: number;
  minimum_fare: number;
  status?: string;
}

export interface User {
  id: number;
  name: string;
  phone: string;
  email: string | null;
  role: 'customer' | 'driver' | 'admin';
  status: 'active' | 'inactive' | 'suspended' | 'pending';
  avatar?: string | null;
  phone_verified?: boolean;
  customer_profile?: CustomerProfile | null;
  driver_profile?: DriverProfile | null;
  wallet?: Wallet | null;
}

export interface CustomerProfile {
  id: number;
  user_id: number;
  address?: string | null;
  city?: string | null;
  district: string;
  emergency_contact?: string | null;
  date_of_birth?: string | null;
}

export interface DriverProfile {
  id: number;
  user_id: number;
  nid_number: string;
  driving_license_number: string;
  address: string;
  district: string;
  verification_status: 'pending' | 'approved' | 'rejected' | 'suspended';
  online_status: 'online' | 'offline' | 'busy';
  rating_avg: number;
  total_trips: number;
  rejection_reason?: string | null;
  documents?: DriverDocument[];
  vehicles?: DriverVehicle[];
}

export interface DriverDocument {
  id: number;
  driver_id: number;
  document_type: 'nid_front' | 'nid_back' | 'driving_license' | 'profile_photo';
  file_name: string;
  file_size: number;
  mime_type: string;
  verification_status: 'pending' | 'approved' | 'rejected';
}

export interface DriverVehicle {
  id: number;
  driver_id: number;
  vehicle_type_id: number;
  registration_number: string;
  brand: string;
  model: string;
  year: number;
  color: string;
  verification_status: 'pending' | 'approved' | 'rejected';
  status: 'active' | 'inactive';
}

export interface Wallet {
  id: number;
  driver_id: number;
  balance: number;
  currency: string;
}

export interface HealthCheckResponse {
  status: string;
  service?: string;
  platform?: string;
  phase?: string;
  tagline?: string;
  version?: string;
  timestamp: string;
  database_summary?: {
    tables_count: number;
    models_count: number;
    migrations_count: number;
    seeders_count: number;
    factories_count: number;
    engine: string;
    collation: string;
    cpanel_safe_migrations: boolean;
  };
  auth_features?: {
    sanctum_tokens: string;
    otp_hashing: string;
    rate_limiting: string;
    roles: string[];
    kyc_driver_flow: string;
  };
}

export interface CPanelCheckItem {
  name: string;
  status: 'passed' | 'warning' | 'failed';
  detail: string;
}

export interface DatabaseTableDefinition {
  name: string;
  category: string;
  engine: string;
  columns_count: number;
  indexes: string[];
  foreign_keys: string[];
  soft_delete: boolean;
  description: string;
}

export interface TestResultItem {
  test: string;
  status: 'PASSED' | 'FAILED';
  duration: string;
}

export interface TestSuiteResponse {
  success: boolean;
  summary: {
    total: number;
    passed: number;
    failed: number;
    errors?: number;
    assertions?: number;
    total_time: string;
    status: string;
  };
  results: TestResultItem[];
}

export interface DistrictLocation {
  id: number;
  name: string;
  bn_name?: string;
  division: string;
  latitude: number;
  longitude: number;
}

export interface FareBreakdown {
  base_fare: number;
  distance_fare: number;
  time_fare: number;
  load_charge: number;
  return_trip_charge: number;
  service_charge: number;
  discount: number;
  tax: number;
  total_fare: number;
  distance_km: number;
  duration_minutes: number;
  trip_type: 'one_way' | 'round_trip' | 'return_trip' | 'hourly';
  service_category?: {
    id: number;
    name: string;
    slug: string;
  };
  vehicle_type?: {
    id: number;
    name: string;
    slug: string;
    passenger_capacity?: number;
    load_capacity?: string | number;
  };
  pickup_address?: string;
  destination_address?: string;
  scheduled_at?: string | null;
}

export interface MatchedDriver {
  driver_id: number;
  name: string;
  phone: string;
  rating_avg: number;
  total_trips: number;
  distance_km: number;
  estimated_arrival_minutes: number;
  latitude: number;
  longitude: number;
  vehicle?: {
    id: number;
    registration_number: string;
    brand: string;
    model: string;
    color: string;
  } | null;
}

export interface BookingStatusHistory {
  id: number;
  booking_id: number;
  status: string;
  changed_by_user_id?: number | null;
  notes?: string | null;
  created_at: string;
}

export interface Booking {
  id: number;
  booking_number: string;
  customer_id: number;
  driver_id?: number | null;
  vehicle_id?: number | null;
  service_category_id: number;
  vehicle_type_id: number;
  pickup_address: string;
  pickup_latitude: number;
  pickup_longitude: number;
  destination_address: string;
  destination_latitude: number;
  destination_longitude: number;
  distance_km: number;
  estimated_duration_minutes: number;
  scheduled_at?: string | null;
  load_description?: string | null;
  load_weight?: number | null;
  passenger_count: number;
  luggage_count: number;
  trip_type: 'one_way' | 'round_trip' | 'return_trip' | 'hourly';
  estimated_fare: number;
  final_fare?: number | null;
  status:
    | 'pending'
    | 'searching_driver'
    | 'driver_assigned'
    | 'driver_arriving'
    | 'arrived'
    | 'loading'
    | 'trip_started'
    | 'trip_completed'
    | 'cancelled_by_customer'
    | 'cancelled_by_driver'
    | 'cancelled_by_admin';
  payment_status: 'pending' | 'paid' | 'failed' | 'refunded';
  customer_notes?: string | null;
  driver_notes?: string | null;
  cancelled_by?: 'customer' | 'driver' | 'admin' | null;
  cancellation_reason?: string | null;
  cancelled_at?: string | null;
  created_at: string;
  updated_at: string;
  customer?: {
    id: number;
    name: string;
    phone: string;
    avatar?: string | null;
  };
  driver?: {
    id: number;
    name: string;
    phone: string;
    avatar?: string | null;
  } | null;
  vehicle?: DriverVehicle | null;
  service_category?: ServiceCategory;
  vehicle_type?: VehicleType;
  status_history?: BookingStatusHistory[];
  latest_location?: {
    latitude: number;
    longitude: number;
    heading: number;
    speed: number;
    recorded_at: string;
  } | null;
}

