import React, { useState, useEffect } from 'react';
import {
  Truck,
  Car,
  Activity,
  Navigation,
  Zap,
  Bike,
  RotateCcw,
  Calendar,
  MapPin,
  Clock,
  ShieldCheck,
  Tag,
  ArrowRight,
  CheckCircle2,
  AlertCircle,
  Phone,
  User as UserIcon,
  RefreshCw,
  XCircle,
  Layers,
  Sparkles,
  Info
} from 'lucide-react';
import { BANGLADESH_DISTRICTS } from '../data/bangladeshDistricts';
import { ServiceCategory, VehicleType, FareBreakdown, MatchedDriver, Booking, User } from '../types';

interface BookingFlowProps {
  currentUser: User | null;
  authToken?: string | null;
  onBookingCreated?: (booking: Booking) => void;
  onSwitchToDriver?: () => void;
}

export const BookingFlow: React.FC<BookingFlowProps> = ({
  currentUser,
  authToken,
  onBookingCreated,
  onSwitchToDriver
}) => {
  // Catalog State
  const [categories, setCategories] = useState<ServiceCategory[]>([]);
  const [vehicles, setVehicles] = useState<VehicleType[]>([]);
  const [loadingCatalog, setLoadingCatalog] = useState(true);

  // Form State
  const [selectedCategory, setSelectedCategory] = useState<number>(1);
  const [selectedVehicle, setSelectedVehicle] = useState<number>(1);
  const [tripType, setTripType] = useState<'one_way' | 'round_trip' | 'return_trip' | 'hourly'>('one_way');
  
  // Route State (Bangladesh District Presets + Addresses)
  const [pickupDistrict, setPickupDistrict] = useState<string>('Dhaka');
  const [pickupAddress, setPickupAddress] = useState<string>('House 42, Road 11, Banani, Dhaka');
  const [pickupLat, setPickupLat] = useState<number>(23.7937);
  const [pickupLng, setPickupLng] = useState<number>(90.4066);

  const [destDistrict, setDestDistrict] = useState<string>('Chattogram (Chittagong)');
  const [destAddress, setDestAddress] = useState<string>('Agrabad Commercial Area, Chattogram');
  const [destLat, setDestLat] = useState<number>(22.3274);
  const [destLng, setDestLng] = useState<number>(91.8123);

  // Trip Specs
  const [passengerCount, setPassengerCount] = useState<number>(1);
  const [loadWeight, setLoadWeight] = useState<string>('0.5');
  const [loadDescription, setLoadDescription] = useState<string>('Electronics and Home Shifting Boxes');
  const [rentalHours, setRentalHours] = useState<number>(4);
  const [customerNotes, setCustomerNotes] = useState<string>('Please call 10 minutes before arrival.');
  const [promoCode, setPromoCode] = useState<string>('TRIPBD50');

  // Fare Calculation & Matching State
  const [calculating, setCalculating] = useState<boolean>(false);
  const [fareEstimate, setFareEstimate] = useState<FareBreakdown | null>(null);
  const [matchedDrivers, setMatchedDrivers] = useState<MatchedDriver[]>([]);
  const [estimateError, setEstimateError] = useState<string | null>(null);

  // Booking Execution State
  const [creatingBooking, setCreatingBooking] = useState<boolean>(false);
  const [activeBooking, setActiveBooking] = useState<Booking | null>(null);
  const [bookingSuccessMsg, setBookingSuccessMsg] = useState<string | null>(null);

  // Status Polling for active booking
  const [pollingActive, setPollingActive] = useState<boolean>(false);

  // Fetch initial catalog from API
  useEffect(() => {
    const fetchCatalog = async () => {
      try {
        setLoadingCatalog(true);
        const [catRes, vehRes] = await Promise.all([
          fetch('/api/v1/services'),
          fetch('/api/v1/vehicle-types')
        ]);
        const catData = await catRes.json();
        const vehData = await vehRes.json();

        if (catData.success && catData.data) {
          setCategories(catData.data);
        }
        if (vehData.success && vehData.data) {
          setVehicles(vehData.data);
        }
      } catch (err) {
        console.error('Failed to load services catalog', err);
      } finally {
        setLoadingCatalog(false);
      }
    };

    fetchCatalog();
  }, []);

  // Update coordinates when pickup district changes
  const handlePickupDistrictChange = (distName: string) => {
    setPickupDistrict(distName);
    const dist = BANGLADESH_DISTRICTS.find(d => d.name === distName);
    if (dist) {
      setPickupLat(dist.latitude);
      setPickupLng(dist.longitude);
      setPickupAddress(`${dist.name} City Center, ${dist.division} Division`);
    }
  };

  // Update coordinates when destination district changes
  const handleDestDistrictChange = (distName: string) => {
    setDestDistrict(distName);
    const dist = BANGLADESH_DISTRICTS.find(d => d.name === distName);
    if (dist) {
      setDestLat(dist.latitude);
      setDestLng(dist.longitude);
      setDestAddress(`${dist.name} Commercial Hub, ${dist.division} Division`);
    }
  };

  // Filter vehicles for currently selected category
  const filteredVehicles = vehicles.filter(v => v.service_category_id === selectedCategory);

  // Automatically select first vehicle when category changes
  useEffect(() => {
    if (filteredVehicles.length > 0) {
      const exists = filteredVehicles.some(v => v.id === selectedVehicle);
      if (!exists) {
        setSelectedVehicle(filteredVehicles[0].id);
      }
    }
  }, [selectedCategory, filteredVehicles, selectedVehicle]);

  // Adjust trip type defaults based on category
  useEffect(() => {
    if (selectedCategory === 7) {
      setTripType('return_trip');
    } else if (selectedCategory === 8) {
      setTripType('hourly');
    } else if (tripType === 'return_trip' || tripType === 'hourly') {
      setTripType('one_way');
    }
  }, [selectedCategory]);

  // Estimate Fare on Demand or Auto-Refresh
  const handleCalculateFare = async () => {
    try {
      setCalculating(true);
      setEstimateError(null);

      const payload = {
        service_category_id: selectedCategory,
        vehicle_type_id: selectedVehicle,
        pickup_latitude: pickupLat,
        pickup_longitude: pickupLng,
        destination_latitude: destLat,
        destination_longitude: destLng,
        passenger_count: passengerCount,
        load_weight: selectedCategory === 1 || selectedCategory === 7 ? parseFloat(loadWeight || '0') : null,
        trip_type: tripType,
        rental_hours: tripType === 'hourly' ? rentalHours : null,
        promo_code: promoCode ? promoCode.trim() : null
      };

      const res = await fetch('/api/v1/trips/estimate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (data.success && data.data) {
        setFareEstimate(data.data);
        setMatchedDrivers(data.data.matched_drivers || []);
      } else {
        setEstimateError(data.message || 'Failed to estimate fare.');
      }
    } catch (err: any) {
      setEstimateError(err.message || 'Network error during fare estimation.');
    } finally {
      setCalculating(false);
    }
  };

  // Run calculation on initial load once catalog is ready
  useEffect(() => {
    if (categories.length > 0 && vehicles.length > 0) {
      handleCalculateFare();
    }
  }, [selectedCategory, selectedVehicle, tripType, pickupLat, pickupLng, destLat, destLng, loadWeight]);

  // Create Booking
  const handleCreateBooking = async () => {
    try {
      setCreatingBooking(true);
      setEstimateError(null);

      const payload = {
        service_category_id: selectedCategory,
        vehicle_type_id: selectedVehicle,
        pickup_address: pickupAddress,
        pickup_latitude: pickupLat,
        pickup_longitude: pickupLng,
        destination_address: destAddress,
        destination_latitude: destLat,
        destination_longitude: destLng,
        passenger_count: passengerCount,
        load_weight: selectedCategory === 1 || selectedCategory === 7 ? parseFloat(loadWeight || '0') : null,
        load_description: loadDescription,
        trip_type: tripType,
        rental_hours: tripType === 'hourly' ? rentalHours : null,
        promo_code: promoCode ? promoCode.trim() : null,
        customer_notes: customerNotes
      };

      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
      }

      const res = await fetch('/api/v1/bookings', {
        method: 'POST',
        headers,
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (data.success && data.data) {
        setActiveBooking(data.data);
        setBookingSuccessMsg(`Booking ${data.data.booking_number} created! Searching for nearby verified drivers.`);
        if (onBookingCreated) {
          onBookingCreated(data.data);
        }
        // Start polling for updates
        setPollingActive(true);
      } else {
        setEstimateError(data.message || 'Failed to place booking request.');
      }
    } catch (err: any) {
      setEstimateError(err.message || 'Network error during booking submission.');
    } finally {
      setCreatingBooking(false);
    }
  };

  // Poll active booking for status updates
  useEffect(() => {
    if (!activeBooking || !pollingActive) return;

    const interval = setInterval(async () => {
      try {
        const res = await fetch(`/api/v1/bookings/${activeBooking.id}`);
        const data = await res.json();
        if (data.success && data.data) {
          setActiveBooking(data.data);
          if (['trip_completed', 'cancelled_by_customer', 'cancelled_by_driver', 'cancelled_by_admin'].includes(data.data.status)) {
            setPollingActive(false);
          }
        }
      } catch (err) {
        console.error('Polling error', err);
      }
    }, 3000);

    return () => clearInterval(interval);
  }, [activeBooking, pollingActive]);

  // Cancel Booking
  const handleCancelBooking = async (bookingId: number) => {
    try {
      const res = await fetch(`/api/v1/bookings/${bookingId}/cancel`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reason: 'Customer changed travel schedule' })
      });
      const data = await res.json();
      if (data.success && data.data) {
        setActiveBooking(data.data);
        setBookingSuccessMsg('Booking cancelled successfully.');
      }
    } catch (err) {
      console.error('Cancel booking error', err);
    }
  };

  // Category Icon Resolver
  const getCategoryIcon = (iconName: string) => {
    switch (iconName) {
      case 'Truck': return <Truck className="w-5 h-5" />;
      case 'Activity': return <Activity className="w-5 h-5" />;
      case 'Car': return <Car className="w-5 h-5" />;
      case 'Navigation': return <Navigation className="w-5 h-5" />;
      case 'Zap': return <Zap className="w-5 h-5" />;
      case 'Bike': return <Bike className="w-5 h-5" />;
      case 'RotateCcw': return <RotateCcw className="w-5 h-5" />;
      case 'Calendar': return <Calendar className="w-5 h-5" />;
      default: return <Truck className="w-5 h-5" />;
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'searching_driver':
        return <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-500/10 text-amber-500 rounded-full text-xs font-semibold animate-pulse"><RefreshCw className="w-3.5 h-3.5 animate-spin" /> Searching Driver</span>;
      case 'driver_assigned':
        return <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-500/10 text-blue-500 rounded-full text-xs font-semibold"><CheckCircle2 className="w-3.5 h-3.5" /> Driver Assigned</span>;
      case 'arrived':
        return <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-purple-500/10 text-purple-500 rounded-full text-xs font-semibold"><MapPin className="w-3.5 h-3.5" /> Driver Arrived</span>;
      case 'trip_started':
        return <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-500/10 text-emerald-500 rounded-full text-xs font-semibold"><Navigation className="w-3.5 h-3.5 animate-bounce" /> En Route</span>;
      case 'trip_completed':
        return <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-600 text-white rounded-full text-xs font-semibold"><CheckCircle2 className="w-3.5 h-3.5" /> Completed</span>;
      case 'cancelled_by_customer':
      case 'cancelled_by_driver':
      case 'cancelled_by_admin':
        return <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-500/10 text-rose-500 rounded-full text-xs font-semibold"><XCircle className="w-3.5 h-3.5" /> Cancelled</span>;
      default:
        return <span className="px-3 py-1 bg-slate-800 text-slate-300 rounded-full text-xs font-medium">{status}</span>;
    }
  };

  return (
    <div id="booking-flow-container" className="space-y-6">
      {/* Top Banner: Real-time Dispatch Info */}
      <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
            <Navigation className="w-6 h-6" />
          </div>
          <div>
            <h2 className="text-xl font-bold text-white tracking-tight flex items-center gap-2">
              TripBD Booking Engine <span className="text-xs px-2.5 py-0.5 bg-emerald-500/20 text-emerald-400 rounded-full border border-emerald-500/30">Phase 4 Active</span>
            </h2>
            <p className="text-sm text-slate-400">
              Instant Geodesic Haversine Calculation • 64 Bangladesh Districts • Concurrency-Locked Driver Matching
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2">
          {onSwitchToDriver && (
            <button
              id="btn-switch-to-driver-terminal"
              onClick={onSwitchToDriver}
              className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-emerald-400 text-xs font-semibold rounded-lg border border-slate-700 transition flex items-center gap-2"
            >
              <Truck className="w-4 h-4" /> Driver Terminal
            </button>
          )}
          <button
            id="btn-refresh-fare"
            onClick={handleCalculateFare}
            disabled={calculating}
            className="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-semibold rounded-lg transition flex items-center gap-2"
          >
            <RefreshCw className={`w-4 h-4 ${calculating ? 'animate-spin' : ''}`} /> Recalculate
          </button>
        </div>
      </div>

      {/* Active Booking Live Monitor Card (If a booking was made) */}
      {activeBooking && (
        <div id="active-booking-monitor" className="bg-slate-900/90 border-2 border-emerald-500/40 rounded-xl p-6 shadow-2xl relative overflow-hidden backdrop-blur">
          <div className="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 rounded-full blur-3xl pointer-events-none" />

          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-800">
            <div>
              <div className="flex items-center gap-3">
                <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Trip Booking</span>
                {getStatusBadge(activeBooking.status)}
              </div>
              <h3 className="text-2xl font-mono font-bold text-emerald-400 mt-1">{activeBooking.booking_number}</h3>
            </div>

            <div className="flex items-center gap-3">
              <div className="text-right">
                <p className="text-xs text-slate-400">Total Authoritative Fare</p>
                <p className="text-xl font-bold text-white">৳ {Number(activeBooking.final_fare || activeBooking.estimated_fare).toFixed(2)}</p>
              </div>

              {!['trip_completed', 'cancelled_by_customer', 'cancelled_by_driver', 'cancelled_by_admin'].includes(activeBooking.status) && (
                <button
                  id="btn-cancel-active-booking"
                  onClick={() => handleCancelBooking(activeBooking.id)}
                  className="px-3.5 py-2 bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-500/30 rounded-lg text-xs font-medium transition"
                >
                  Cancel Trip
                </button>
              )}
            </div>
          </div>

          {/* Route & Driver Telemetry */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 pt-5">
            {/* Route */}
            <div className="space-y-3">
              <div className="flex items-start gap-3">
                <div className="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <MapPin className="w-3.5 h-3.5" />
                </div>
                <div>
                  <p className="text-xs text-slate-400">Pickup Location</p>
                  <p className="text-sm font-medium text-slate-200">{activeBooking.pickup_address}</p>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <div className="w-6 h-6 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <MapPin className="w-3.5 h-3.5" />
                </div>
                <div>
                  <p className="text-xs text-slate-400">Destination</p>
                  <p className="text-sm font-medium text-slate-200">{activeBooking.destination_address}</p>
                </div>
              </div>

              <div className="flex items-center gap-4 text-xs text-slate-400 pt-1">
                <span>Distance: <strong className="text-slate-200">{activeBooking.distance_km} KM</strong></span>
                <span>•</span>
                <span>Est. Duration: <strong className="text-slate-200">{activeBooking.estimated_duration_minutes} Mins</strong></span>
              </div>
            </div>

            {/* Assigned Driver Card */}
            <div className="bg-slate-950/60 rounded-xl p-4 border border-slate-800">
              <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <UserIcon className="w-3.5 h-3.5 text-emerald-400" /> Assigned Driver
              </p>
              {activeBooking.driver ? (
                <div className="space-y-2">
                  <div className="flex items-center justify-between">
                    <p className="text-sm font-bold text-white">{activeBooking.driver.name}</p>
                    <span className="text-xs px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded">Verified</span>
                  </div>
                  <div className="flex items-center gap-2 text-xs text-slate-300">
                    <Phone className="w-3.5 h-3.5 text-emerald-400" />
                    <span>{activeBooking.driver.phone}</span>
                  </div>
                  {activeBooking.vehicle && (
                    <div className="text-xs text-slate-400 pt-1 border-t border-slate-800/80">
                      Vehicle: <span className="text-slate-200 font-medium">{activeBooking.vehicle.brand} {activeBooking.vehicle.model} ({activeBooking.vehicle.registration_number})</span>
                    </div>
                  )}
                </div>
              ) : (
                <div className="py-3 text-center">
                  <RefreshCw className="w-5 h-5 text-amber-400 animate-spin mx-auto mb-1.5" />
                  <p className="text-xs text-amber-300 font-medium">Broadcasting to nearby drivers...</p>
                  <p className="text-[11px] text-slate-500 mt-0.5">Switch to Driver Terminal to accept</p>
                </div>
              )}
            </div>

            {/* Status Timeline */}
            <div className="bg-slate-950/60 rounded-xl p-4 border border-slate-800">
              <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                <Clock className="w-3.5 h-3.5 text-emerald-400" /> Trip Status Timeline
              </p>
              <div className="space-y-2 max-h-36 overflow-y-auto pr-1 text-xs">
                {activeBooking.status_history && activeBooking.status_history.length > 0 ? (
                  activeBooking.status_history.map((hist, idx) => (
                    <div key={idx} className="flex items-start gap-2 border-l-2 border-emerald-500/40 pl-2 py-0.5">
                      <div>
                        <p className="font-semibold text-emerald-400 capitalize">{hist.status.replace(/_/g, ' ')}</p>
                        <p className="text-[11px] text-slate-400">{hist.notes}</p>
                      </div>
                    </div>
                  ))
                ) : (
                  <p className="text-slate-500 text-xs">Awaiting status transitions...</p>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Main Grid: Booking Configuration & Interactive Price Card */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Left Column: Form Controls (7 Cols) */}
        <div className="lg:col-span-7 space-y-6">
          {/* 1. Service Selection */}
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-lg">
            <h3 className="text-sm font-bold text-slate-200 uppercase tracking-wider mb-3 flex items-center gap-2">
              <Layers className="w-4 h-4 text-emerald-400" /> 1. Select Service Category (৮টি মূল সেবা)
            </h3>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
              {categories.map((cat) => {
                const isSelected = selectedCategory === cat.id;
                return (
                  <button
                    key={cat.id}
                    id={`btn-service-cat-${cat.slug}`}
                    onClick={() => setSelectedCategory(cat.id)}
                    className={`p-3 rounded-xl border text-left transition flex flex-col justify-between ${
                      isSelected
                        ? 'bg-emerald-500/10 border-emerald-500 text-white shadow-md'
                        : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700 hover:text-slate-200'
                    }`}
                  >
                    <div className={`p-2 rounded-lg w-fit mb-2 ${isSelected ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-400'}`}>
                      {getCategoryIcon(cat.icon)}
                    </div>
                    <div>
                      <p className="text-xs font-bold leading-tight">{cat.name.split('(')[0]}</p>
                      <p className="text-[10px] text-slate-500 mt-0.5 line-clamp-1">{cat.description}</p>
                    </div>
                  </button>
                );
              })}
            </div>
          </div>

          {/* 2. Route Configuration with 64 Bangladesh Districts */}
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-lg space-y-4">
            <h3 className="text-sm font-bold text-slate-200 uppercase tracking-wider flex items-center gap-2">
              <MapPin className="w-4 h-4 text-emerald-400" /> 2. Pickup & Destination (বাংলাদেশের ৬৪ জেলা)
            </h3>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {/* Pickup */}
              <div className="space-y-2 bg-slate-950/60 p-3.5 rounded-xl border border-slate-800">
                <label className="text-xs font-semibold text-emerald-400 flex items-center gap-1.5">
                  <MapPin className="w-3.5 h-3.5" /> Pickup District (যাত্রা শুরু)
                </label>
                <select
                  id="select-pickup-district"
                  value={pickupDistrict}
                  onChange={(e) => handlePickupDistrictChange(e.target.value)}
                  className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none"
                >
                  {BANGLADESH_DISTRICTS.map((d) => (
                    <option key={`p-${d.id}`} value={d.name}>
                      {d.name} ({d.bn_name}) — {d.division} Division
                    </option>
                  ))}
                </select>

                <div>
                  <label className="text-[11px] text-slate-400">Detailed Pickup Address</label>
                  <input
                    type="text"
                    id="input-pickup-address"
                    value={pickupAddress}
                    onChange={(e) => setPickupAddress(e.target.value)}
                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white focus:border-emerald-500 focus:outline-none mt-1"
                    placeholder="Street / Area / House No."
                  />
                </div>
                <div className="flex gap-2 text-[10px] font-mono text-slate-500">
                  <span>Lat: {pickupLat.toFixed(4)}</span>
                  <span>•</span>
                  <span>Lng: {pickupLng.toFixed(4)}</span>
                </div>
              </div>

              {/* Destination */}
              <div className="space-y-2 bg-slate-950/60 p-3.5 rounded-xl border border-slate-800">
                <label className="text-xs font-semibold text-rose-400 flex items-center gap-1.5">
                  <MapPin className="w-3.5 h-3.5" /> Destination District (গন্তব্য)
                </label>
                <select
                  id="select-dest-district"
                  value={destDistrict}
                  onChange={(e) => handleDestDistrictChange(e.target.value)}
                  className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:border-rose-500 focus:outline-none"
                >
                  {BANGLADESH_DISTRICTS.map((d) => (
                    <option key={`d-${d.id}`} value={d.name}>
                      {d.name} ({d.bn_name}) — {d.division} Division
                    </option>
                  ))}
                </select>

                <div>
                  <label className="text-[11px] text-slate-400">Detailed Destination Address</label>
                  <input
                    type="text"
                    id="input-dest-address"
                    value={destAddress}
                    onChange={(e) => setDestAddress(e.target.value)}
                    className="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white focus:border-rose-500 focus:outline-none mt-1"
                    placeholder="Street / Market / Destination Hub"
                  />
                </div>
                <div className="flex gap-2 text-[10px] font-mono text-slate-500">
                  <span>Lat: {destLat.toFixed(4)}</span>
                  <span>•</span>
                  <span>Lng: {destLng.toFixed(4)}</span>
                </div>
              </div>
            </div>
          </div>

          {/* 3. Vehicle Type Selection */}
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-lg">
            <h3 className="text-sm font-bold text-slate-200 uppercase tracking-wider mb-3 flex items-center gap-2">
              <Truck className="w-4 h-4 text-emerald-400" /> 3. Vehicle Type & Capacity Matrix
            </h3>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              {filteredVehicles.map((veh) => {
                const isSelected = selectedVehicle === veh.id;
                return (
                  <button
                    key={veh.id}
                    id={`btn-veh-type-${veh.slug}`}
                    onClick={() => setSelectedVehicle(veh.id)}
                    className={`p-3.5 rounded-xl border text-left transition flex items-start justify-between ${
                      isSelected
                        ? 'bg-emerald-500/10 border-emerald-500 text-white shadow-md'
                        : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:border-slate-700 hover:text-slate-200'
                    }`}
                  >
                    <div>
                      <p className="text-xs font-bold text-white">{veh.name}</p>
                      <p className="text-[11px] text-slate-400 mt-0.5 line-clamp-1">{veh.description}</p>
                      <div className="flex items-center gap-2 mt-2 text-[10px]">
                        {veh.load_capacity && Number(veh.load_capacity) > 0 && (
                          <span className="px-2 py-0.5 bg-slate-800 text-emerald-400 rounded">Cap: {veh.load_capacity} Ton</span>
                        )}
                        {veh.passenger_capacity && Number(veh.passenger_capacity) > 0 && (
                          <span className="px-2 py-0.5 bg-slate-800 text-blue-400 rounded">Pass: {veh.passenger_capacity}</span>
                        )}
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-xs font-mono font-bold text-emerald-400">৳ {veh.base_fare}</p>
                      <p className="text-[10px] text-slate-500">+৳{veh.per_km_rate}/km</p>
                    </div>
                  </button>
                );
              })}
            </div>
          </div>

          {/* 4. Trip Type & Extra Options */}
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-lg space-y-4">
            <h3 className="text-sm font-bold text-slate-200 uppercase tracking-wider flex items-center gap-2">
              <Tag className="w-4 h-4 text-emerald-400" /> 4. Trip Type & Cargo Specifications
            </h3>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
              {[
                { id: 'one_way', label: 'One Way (একমুখী)' },
                { id: 'round_trip', label: 'Round Trip (রাউন্ড ট্রিপ)' },
                { id: 'return_trip', label: 'Return Truck (ডিসকাউন্ট)' },
                { id: 'hourly', label: 'Hourly/Daily (ঘন্টায়)' }
              ].map((t) => (
                <button
                  key={t.id}
                  id={`btn-trip-type-${t.id}`}
                  onClick={() => setTripType(t.id as any)}
                  className={`py-2 px-3 rounded-lg text-xs font-semibold border text-center transition ${
                    tripType === t.id
                      ? 'bg-emerald-600 text-white border-emerald-500'
                      : 'bg-slate-950/60 border-slate-800 text-slate-400 hover:text-white'
                  }`}
                >
                  {t.label}
                </button>
              ))}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
              {(selectedCategory === 1 || selectedCategory === 7) && (
                <div>
                  <label className="text-[11px] text-slate-400">Load Weight (Tons)</label>
                  <input
                    type="number"
                    id="input-load-weight"
                    step="0.1"
                    min="0.1"
                    max="20"
                    value={loadWeight}
                    onChange={(e) => setLoadWeight(e.target.value)}
                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white focus:border-emerald-500 focus:outline-none mt-1"
                  />
                </div>
              )}

              <div>
                <label className="text-[11px] text-slate-400">Passengers</label>
                <input
                  type="number"
                  id="input-passenger-count"
                  min="1"
                  max="14"
                  value={passengerCount}
                  onChange={(e) => setPassengerCount(parseInt(e.target.value) || 1)}
                  className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white focus:border-emerald-500 focus:outline-none mt-1"
                />
              </div>

              <div>
                <label className="text-[11px] text-slate-400">Promo Code</label>
                <div className="flex gap-1 mt-1">
                  <input
                    type="text"
                    id="input-promo-code"
                    value={promoCode}
                    onChange={(e) => setPromoCode(e.target.value.toUpperCase())}
                    className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white font-mono uppercase focus:border-emerald-500 focus:outline-none"
                    placeholder="e.g. TRIPBD50"
                  />
                  <button
                    id="btn-apply-promo"
                    onClick={handleCalculateFare}
                    className="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-xs text-emerald-400 rounded-lg border border-slate-700"
                  >
                    Apply
                  </button>
                </div>
              </div>
            </div>

            <div>
              <label className="text-[11px] text-slate-400">Cargo / Special Handling Instructions</label>
              <input
                type="text"
                id="input-cargo-desc"
                value={loadDescription}
                onChange={(e) => setLoadDescription(e.target.value)}
                className="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-1.5 text-xs text-white focus:border-emerald-500 focus:outline-none mt-1"
                placeholder="e.g. 15 carton clothes, fragile crockery, medical patient"
              />
            </div>
          </div>
        </div>

        {/* Right Column: Live Authoritative Fare Card & Driver Matching Radar (5 Cols) */}
        <div className="lg:col-span-5 space-y-6">
          {/* Fare Card */}
          <div className="bg-slate-900 border-2 border-emerald-500/30 rounded-xl p-5 shadow-2xl space-y-4 sticky top-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-800">
              <h3 className="text-base font-bold text-white flex items-center gap-2">
                <Sparkles className="w-4 h-4 text-emerald-400" /> Fare Estimate & Breakdown
              </h3>
              <span className="text-[10px] px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded-full font-mono font-semibold">
                Server Authoritative
              </span>
            </div>

            {estimateError && (
              <div className="p-3 bg-rose-500/10 border border-rose-500/30 rounded-lg text-xs text-rose-400 flex items-start gap-2">
                <AlertCircle className="w-4 h-4 flex-shrink-0 mt-0.5" />
                <span>{estimateError}</span>
              </div>
            )}

            {fareEstimate ? (
              <div className="space-y-3 text-xs">
                {/* Distance & Time */}
                <div className="grid grid-cols-2 gap-2 bg-slate-950/70 p-3 rounded-lg border border-slate-800/80">
                  <div>
                    <span className="text-slate-400">Haversine Distance:</span>
                    <p className="text-sm font-bold text-white font-mono">{fareEstimate.distance_km} KM</p>
                  </div>
                  <div>
                    <span className="text-slate-400">Est. Transit Time:</span>
                    <p className="text-sm font-bold text-white font-mono">{fareEstimate.duration_minutes} Minutes</p>
                  </div>
                </div>

                {/* Line Items */}
                <div className="space-y-1.5 pt-1 text-slate-300">
                  <div className="flex justify-between">
                    <span className="text-slate-400">Base Vehicle Fare:</span>
                    <span className="font-mono">৳ {Number(fareEstimate.base_fare).toFixed(2)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-slate-400">Distance Rate ({fareEstimate.distance_km} km):</span>
                    <span className="font-mono">৳ {Number(fareEstimate.distance_fare).toFixed(2)}</span>
                  </div>
                  {Number(fareEstimate.time_fare) > 0 && (
                    <div className="flex justify-between">
                      <span className="text-slate-400">Hourly Time Rate:</span>
                      <span className="font-mono">৳ {Number(fareEstimate.time_fare).toFixed(2)}</span>
                    </div>
                  )}
                  {Number(fareEstimate.load_charge) > 0 && (
                    <div className="flex justify-between">
                      <span className="text-slate-400">Heavy Load Handling:</span>
                      <span className="font-mono">৳ {Number(fareEstimate.load_charge).toFixed(2)}</span>
                    </div>
                  )}
                  {Number(fareEstimate.return_trip_charge) < 0 && (
                    <div className="flex justify-between text-emerald-400">
                      <span>Return Route Discount:</span>
                      <span className="font-mono">৳ {Number(fareEstimate.return_trip_charge).toFixed(2)}</span>
                    </div>
                  )}
                  <div className="flex justify-between">
                    <span className="text-slate-400">Platform Service Fee (5%):</span>
                    <span className="font-mono">৳ {Number(fareEstimate.service_charge).toFixed(2)}</span>
                  </div>
                  {Number(fareEstimate.discount) > 0 && (
                    <div className="flex justify-between text-emerald-400 font-semibold">
                      <span>Promo Coupon Discount:</span>
                      <span className="font-mono">- ৳ {Number(fareEstimate.discount).toFixed(2)}</span>
                    </div>
                  )}
                </div>

                {/* Grand Total */}
                <div className="pt-3 border-t border-slate-800 flex items-center justify-between bg-slate-950/90 p-3.5 rounded-xl border border-emerald-500/20">
                  <div>
                    <p className="text-xs text-slate-400 uppercase font-semibold">Guaranteed Total Fare</p>
                    <p className="text-[10px] text-slate-500">No hidden fees or surge multipliers</p>
                  </div>
                  <div className="text-right">
                    <p className="text-2xl font-black text-emerald-400 font-mono">৳ {Number(fareEstimate.total_fare).toFixed(2)}</p>
                  </div>
                </div>

                {/* Nearby Driver Matching Radar */}
                <div className="pt-2">
                  <p className="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2 flex items-center justify-between">
                    <span className="flex items-center gap-1.5"><ShieldCheck className="w-3.5 h-3.5 text-emerald-400" /> Available Verified Drivers</span>
                    <span className="text-[11px] font-mono text-emerald-400">{matchedDrivers.length} Online</span>
                  </p>

                  <div className="space-y-2 max-h-36 overflow-y-auto pr-1">
                    {matchedDrivers.length > 0 ? (
                      matchedDrivers.map((d) => (
                        <div key={d.driver_id} className="bg-slate-950 p-2.5 rounded-lg border border-slate-800 flex items-center justify-between">
                          <div className="flex items-center gap-2.5">
                            <div className="w-7 h-7 rounded-full bg-slate-800 text-emerald-400 flex items-center justify-center font-bold text-xs">
                              {d.name.charAt(0)}
                            </div>
                            <div>
                              <p className="text-xs font-bold text-white">{d.name}</p>
                              <p className="text-[10px] text-slate-400">★ {d.rating_avg} • {d.total_trips} trips completed</p>
                            </div>
                          </div>
                          <div className="text-right">
                            <span className="text-xs font-semibold text-emerald-400">{d.distance_km} km</span>
                            <p className="text-[10px] text-slate-500">ETA {d.estimated_arrival_minutes}m</p>
                          </div>
                        </div>
                      ))
                    ) : (
                      <div className="p-3 bg-slate-950 rounded-lg text-center text-slate-500 text-xs">
                        No online drivers currently in radius. Requesting booking will broadcast to all registered drivers.
                      </div>
                    )}
                  </div>
                </div>

                {/* Booking Trigger Button */}
                <button
                  id="btn-create-booking-submit"
                  onClick={handleCreateBooking}
                  disabled={creatingBooking}
                  className="w-full mt-3 py-3 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold rounded-xl shadow-lg shadow-emerald-900/30 transition flex items-center justify-center gap-2 text-sm"
                >
                  {creatingBooking ? (
                    <>
                      <RefreshCw className="w-4 h-4 animate-spin" />
                      Creating Booking & Matching...
                    </>
                  ) : (
                    <>
                      Confirm & Request Trip (বুকিং নিশ্চিত করুন)
                      <ArrowRight className="w-4 h-4" />
                    </>
                  )}
                </button>
              </div>
            ) : (
              <div className="py-8 text-center text-slate-500">
                <RefreshCw className="w-6 h-6 animate-spin mx-auto mb-2 text-emerald-500" />
                <p className="text-xs">Calculating authoritative server fare...</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
