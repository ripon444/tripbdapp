import React, { useState, useEffect } from 'react';
import {
  Truck,
  MapPin,
  Clock,
  CheckCircle2,
  XCircle,
  Play,
  Navigation,
  DollarSign,
  User as UserIcon,
  Phone,
  ShieldCheck,
  RefreshCw,
  Power,
  Layers,
  Sparkles,
  AlertTriangle,
  Send
} from 'lucide-react';
import { Booking, User, DriverProfile, DriverVehicle } from '../types';

interface DriverDeskProps {
  currentUser: User | null;
  authToken?: string | null;
  onBookingStatusUpdated?: (booking: Booking) => void;
}

export const DriverDesk: React.FC<DriverDeskProps> = ({
  currentUser,
  authToken,
  onBookingStatusUpdated
}) => {
  // Driver Profile & Status State
  const [driverProfile, setDriverProfile] = useState<any>(null);
  const [onlineStatus, setOnlineStatus] = useState<'online' | 'offline' | 'busy'>('offline');
  const [walletBalance, setWalletBalance] = useState<number>(4250.00);
  const [activeTrip, setActiveTrip] = useState<Booking | null>(null);
  const [loadingStatus, setLoadingStatus] = useState<boolean>(true);

  // Incoming Requests Feed
  const [tripRequests, setTripRequests] = useState<any[]>([]);
  const [loadingRequests, setLoadingRequests] = useState<boolean>(false);
  const [actionInProgress, setActionInProgress] = useState<string | null>(null);
  const [feedbackMessage, setFeedbackMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  // GPS Telemetry State (Simulated Lat/Lng Heartbeat)
  const [currentLat, setCurrentLat] = useState<number>(23.8103);
  const [currentLng, setCurrentLng] = useState<number>(90.4125);
  const [gpsHeading, setGpsHeading] = useState<number>(45.0);
  const [gpsSpeed, setGpsSpeed] = useState<number>(32.0);
  const [gpsHeartbeatActive, setGpsHeartbeatActive] = useState<boolean>(true);

  // Fetch Driver Status & Active Trip
  const fetchDriverStatus = async () => {
    try {
      setLoadingStatus(true);
      const headers: Record<string, string> = {};
      if (authToken) headers['Authorization'] = `Bearer ${authToken}`;

      const res = await fetch('/api/v1/driver/status', { headers });
      const data = await res.json();

      if (data.success && data.driver) {
        setDriverProfile(data.driver);
        setOnlineStatus(data.driver.online_status || 'offline');
        setWalletBalance(data.driver.wallet_balance || 0);
        setActiveTrip(data.active_trip || null);
      }
    } catch (err) {
      console.error('Failed to fetch driver status', err);
    } finally {
      setLoadingStatus(false);
    }
  };

  // Fetch Available Trip Requests Feed
  const fetchTripRequests = async () => {
    try {
      setLoadingRequests(true);
      const headers: Record<string, string> = {};
      if (authToken) headers['Authorization'] = `Bearer ${authToken}`;

      const res = await fetch('/api/v1/driver/trip-requests', { headers });
      const data = await res.json();
      if (data.success && data.data) {
        setTripRequests(data.data);
      }
    } catch (err) {
      console.error('Failed to fetch trip requests', err);
    } finally {
      setLoadingRequests(false);
    }
  };

  // Initial Load & Auto-polling
  useEffect(() => {
    fetchDriverStatus();
    fetchTripRequests();

    const interval = setInterval(() => {
      fetchDriverStatus();
      fetchTripRequests();
    }, 4000);

    return () => clearInterval(interval);
  }, []);

  // GPS Telemetry Heartbeat Sender
  const sendGpsHeartbeat = async () => {
    try {
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (authToken) headers['Authorization'] = `Bearer ${authToken}`;

      await fetch('/api/v1/driver/location', {
        method: 'POST',
        headers,
        body: JSON.stringify({
          latitude: currentLat,
          longitude: currentLng,
          heading: gpsHeading,
          speed: gpsSpeed,
          accuracy: 4.5,
          booking_id: activeTrip ? activeTrip.id : null
        })
      });
    } catch (err) {
      console.error('GPS Heartbeat failure', err);
    }
  };

  useEffect(() => {
    if (!gpsHeartbeatActive) return;
    const interval = setInterval(() => {
      // Simulate minor GPS drift
      setCurrentLat(prev => prev + (Math.random() * 0.0004 - 0.0002));
      setCurrentLng(prev => prev + (Math.random() * 0.0004 - 0.0002));
      sendGpsHeartbeat();
    }, 5000);

    return () => clearInterval(interval);
  }, [gpsHeartbeatActive, activeTrip]);

  // Toggle Online/Offline
  const handleToggleOnline = async () => {
    try {
      setActionInProgress('toggle_online');
      setFeedbackMessage(null);
      const endpoint = onlineStatus === 'online' ? '/api/v1/driver/offline' : '/api/v1/driver/online';

      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (authToken) headers['Authorization'] = `Bearer ${authToken}`;

      const res = await fetch(endpoint, {
        method: 'POST',
        headers
      });

      const data = await res.json();
      if (data.success) {
        setOnlineStatus(data.online_status);
        setFeedbackMessage({ type: 'success', text: data.message });
        fetchDriverStatus();
        fetchTripRequests();
      } else {
        setFeedbackMessage({ type: 'error', text: data.message || 'Status update failed.' });
      }
    } catch (err: any) {
      setFeedbackMessage({ type: 'error', text: err.message || 'Network error.' });
    } finally {
      setActionInProgress(null);
    }
  };

  // Concurrency-Safe Trip Acceptance
  const handleAcceptTrip = async (bookingId: number) => {
    try {
      setActionInProgress(`accept_${bookingId}`);
      setFeedbackMessage(null);

      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (authToken) headers['Authorization'] = `Bearer ${authToken}`;

      const res = await fetch(`/api/v1/driver/bookings/${bookingId}/accept`, {
        method: 'POST',
        headers
      });

      const data = await res.json();
      if (data.success && data.data) {
        setActiveTrip(data.data);
        setOnlineStatus('busy');
        setFeedbackMessage({ type: 'success', text: 'Trip accepted successfully! Proceed to pickup.' });
        if (onBookingStatusUpdated) {
          onBookingStatusUpdated(data.data);
        }
        fetchDriverStatus();
        fetchTripRequests();
      } else {
        setFeedbackMessage({ type: 'error', text: data.message || 'Could not accept trip.' });
      }
    } catch (err: any) {
      setFeedbackMessage({ type: 'error', text: err.message || 'Network error.' });
    } finally {
      setActionInProgress(null);
    }
  };

  // Mark Driver Arrived at Pickup
  const handleMarkArrived = async (bookingId: number) => {
    try {
      setActionInProgress('arrived');
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (authToken) headers['Authorization'] = `Bearer ${authToken}`;

      const res = await fetch(`/api/v1/driver/bookings/${bookingId}/arrived`, {
        method: 'POST',
        headers
      });
      const data = await res.json();
      if (data.success && data.data) {
        setActiveTrip(data.data);
        setFeedbackMessage({ type: 'success', text: 'Arrival at pickup confirmed. Customer notified.' });
        if (onBookingStatusUpdated) onBookingStatusUpdated(data.data);
        fetchDriverStatus();
      }
    } catch (err: any) {
      setFeedbackMessage({ type: 'error', text: err.message });
    } finally {
      setActionInProgress(null);
    }
  };

  // Start Trip Journey
  const handleStartTrip = async (bookingId: number) => {
    try {
      setActionInProgress('start');
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (authToken) headers['Authorization'] = `Bearer ${authToken}`;

      const res = await fetch(`/api/v1/driver/bookings/${bookingId}/start`, {
        method: 'POST',
        headers
      });
      const data = await res.json();
      if (data.success && data.data) {
        setActiveTrip(data.data);
        setFeedbackMessage({ type: 'success', text: 'Trip started! Have a safe journey to destination.' });
        if (onBookingStatusUpdated) onBookingStatusUpdated(data.data);
        fetchDriverStatus();
      }
    } catch (err: any) {
      setFeedbackMessage({ type: 'error', text: err.message });
    } finally {
      setActionInProgress(null);
    }
  };

  // Complete Trip Journey & Collect Fare
  const handleCompleteTrip = async (bookingId: number) => {
    try {
      setActionInProgress('complete');
      const headers: Record<string, string> = { 'Content-Type': 'application/json' };
      if (authToken) headers['Authorization'] = `Bearer ${authToken}`;

      const res = await fetch(`/api/v1/driver/bookings/${bookingId}/complete`, {
        method: 'POST',
        headers
      });
      const data = await res.json();
      if (data.success && data.data) {
        setActiveTrip(null);
        setOnlineStatus('online');
        setWalletBalance(data.data.driver_wallet_balance || walletBalance);
        setFeedbackMessage({
          type: 'success',
          text: `Trip completed! Collected Fare: ৳${Number(data.data.collected_fare).toFixed(2)}. Payout credited to your wallet.`
        });
        if (onBookingStatusUpdated) onBookingStatusUpdated(data.data);
        fetchDriverStatus();
        fetchTripRequests();
      }
    } catch (err: any) {
      setFeedbackMessage({ type: 'error', text: err.message });
    } finally {
      setActionInProgress(null);
    }
  };

  // Dismiss / Reject Request
  const handleRejectTrip = async (bookingId: number) => {
    try {
      setTripRequests(prev => prev.filter(r => r.id !== bookingId));
      await fetch(`/api/v1/driver/bookings/${bookingId}/reject`, { method: 'POST' });
    } catch (err) {
      console.error('Dismiss error', err);
    }
  };

  return (
    <div id="driver-desk-container" className="space-y-6">
      {/* Header Bar: Driver Status, Online Toggle & Wallet */}
      <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
            <Truck className="w-6 h-6" />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <h2 className="text-xl font-bold text-white tracking-tight">Driver Dispatch Desk</h2>
              <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold border ${
                onlineStatus === 'online'
                  ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30 animate-pulse'
                  : onlineStatus === 'busy'
                  ? 'bg-blue-500/20 text-blue-400 border-blue-500/30'
                  : 'bg-slate-800 text-slate-400 border-slate-700'
              }`}>
                ● {onlineStatus.toUpperCase()}
              </span>
            </div>
            <p className="text-sm text-slate-400">
              Verified Driver: <strong className="text-slate-200">{driverProfile?.name || 'Md. Rafiqul Islam'}</strong> • 5.0 ★ Rating
            </p>
          </div>
        </div>

        {/* Right Controls: Wallet & Power Toggle */}
        <div className="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end">
          <div className="bg-slate-950/80 px-4 py-2 rounded-xl border border-slate-800 text-right">
            <p className="text-[10px] text-slate-400 uppercase font-semibold">Driver Wallet</p>
            <p className="text-base font-mono font-bold text-emerald-400">৳ {Number(walletBalance).toFixed(2)}</p>
          </div>

          <button
            id="btn-driver-toggle-online"
            onClick={handleToggleOnline}
            disabled={actionInProgress === 'toggle_online' || onlineStatus === 'busy'}
            className={`px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-2 shadow-lg ${
              onlineStatus === 'online'
                ? 'bg-rose-600/20 text-rose-400 border border-rose-500/40 hover:bg-rose-600/30'
                : 'bg-emerald-600 text-white hover:bg-emerald-500 shadow-emerald-900/30'
            }`}
          >
            <Power className="w-4 h-4" />
            {onlineStatus === 'online' ? 'Go Offline' : 'Go Online (ডিউটি শুরু)'}
          </button>
        </div>
      </div>

      {/* System Feedback Banner */}
      {feedbackMessage && (
        <div className={`p-4 rounded-xl border text-xs flex items-center justify-between ${
          feedbackMessage.type === 'success'
            ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300'
            : 'bg-rose-500/10 border-rose-500/30 text-rose-300'
        }`}>
          <div className="flex items-center gap-2">
            {feedbackMessage.type === 'success' ? <CheckCircle2 className="w-4 h-4" /> : <AlertTriangle className="w-4 h-4" />}
            <span>{feedbackMessage.text}</span>
          </div>
          <button onClick={() => setFeedbackMessage(null)} className="text-slate-400 hover:text-white">✕</button>
        </div>
      )}

      {/* Active Trip Controller (If in trip) */}
      {activeTrip && (
        <div id="driver-active-trip-panel" className="bg-slate-900 border-2 border-emerald-500/40 rounded-xl p-6 shadow-2xl space-y-5">
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-4 border-b border-slate-800">
            <div>
              <span className="text-xs font-bold text-emerald-400 uppercase tracking-wider">Active Trip In Progress</span>
              <h3 className="text-2xl font-mono font-bold text-white mt-0.5">{activeTrip.booking_number}</h3>
            </div>
            <div className="flex items-center gap-3">
              <div className="text-right">
                <p className="text-xs text-slate-400">Total Authoritative Fare</p>
                <p className="text-xl font-bold text-emerald-400 font-mono">৳ {Number(activeTrip.estimated_fare).toFixed(2)}</p>
              </div>
              <span className="px-3 py-1 bg-blue-500/20 text-blue-400 border border-blue-500/30 rounded-full text-xs font-semibold uppercase">
                {activeTrip.status.replace(/_/g, ' ')}
              </span>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Route & Cargo Details */}
            <div className="space-y-3 bg-slate-950/60 p-4 rounded-xl border border-slate-800">
              <div className="flex items-start gap-3">
                <div className="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <MapPin className="w-3.5 h-3.5" />
                </div>
                <div>
                  <p className="text-xs text-slate-400">Pickup Address</p>
                  <p className="text-sm font-medium text-slate-200">{activeTrip.pickup_address}</p>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <div className="w-6 h-6 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <MapPin className="w-3.5 h-3.5" />
                </div>
                <div>
                  <p className="text-xs text-slate-400">Destination Address</p>
                  <p className="text-sm font-medium text-slate-200">{activeTrip.destination_address}</p>
                </div>
              </div>

              <div className="flex items-center gap-4 text-xs text-slate-400 pt-2 border-t border-slate-800">
                <span>Distance: <strong className="text-white">{activeTrip.distance_km} KM</strong></span>
                <span>•</span>
                <span>Est. Time: <strong className="text-white">{activeTrip.estimated_duration_minutes} Mins</strong></span>
              </div>
            </div>

            {/* Customer & Action Lifecycle Controller */}
            <div className="space-y-4 bg-slate-950/60 p-4 rounded-xl border border-slate-800 flex flex-col justify-between">
              <div>
                <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Customer Details</p>
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-bold text-white">{activeTrip.customer?.name || 'Customer'}</p>
                    <p className="text-xs text-slate-400 flex items-center gap-1.5 mt-0.5">
                      <Phone className="w-3.5 h-3.5 text-emerald-400" /> {activeTrip.customer?.phone || '017XXXXXXXX'}
                    </p>
                  </div>
                  <a
                    href={`tel:${activeTrip.customer?.phone || '017XXXXXXXX'}`}
                    className="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-xs text-emerald-400 font-semibold rounded-lg border border-slate-700 transition"
                  >
                    Call Customer
                  </a>
                </div>
              </div>

              {/* Lifecycle Stage Action Buttons */}
              <div className="pt-3 border-t border-slate-800">
                {activeTrip.status === 'driver_assigned' && (
                  <button
                    id="btn-driver-mark-arrived"
                    onClick={() => handleMarkArrived(activeTrip.id)}
                    disabled={actionInProgress === 'arrived'}
                    className="w-full py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-lg shadow-purple-900/30"
                  >
                    <MapPin className="w-4 h-4" /> 1. Mark Arrived at Pickup (পিকআপে পৌঁছেছি)
                  </button>
                )}

                {activeTrip.status === 'arrived' && (
                  <button
                    id="btn-driver-start-trip"
                    onClick={() => handleStartTrip(activeTrip.id)}
                    disabled={actionInProgress === 'start'}
                    className="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/30"
                  >
                    <Play className="w-4 h-4" /> 2. Start Trip Journey (যাত্রা শুরু করুন)
                  </button>
                )}

                {activeTrip.status === 'trip_started' && (
                  <button
                    id="btn-driver-complete-trip"
                    onClick={() => handleCompleteTrip(activeTrip.id)}
                    disabled={actionInProgress === 'complete'}
                    className="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/30"
                  >
                    <CheckCircle2 className="w-4 h-4" /> 3. Complete Trip & Collect Fare (ট্রিপ সম্পন্ন ও ভাড়া গ্রহণ)
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Main Grid: Incoming Trip Requests Radar & GPS Telemetry Simulator */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Left Column: Incoming Trip Requests Feed (8 Cols) */}
        <div className="lg:col-span-8 space-y-4">
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-bold text-slate-200 uppercase tracking-wider flex items-center gap-2">
              <Navigation className="w-4 h-4 text-emerald-400" /> Incoming Nearby Trip Requests (নতুন ট্রিপ রিকোয়েস্ট)
            </h3>
            <button
              id="btn-refresh-driver-requests"
              onClick={fetchTripRequests}
              disabled={loadingRequests}
              className="text-xs text-emerald-400 hover:text-emerald-300 flex items-center gap-1.5"
            >
              <RefreshCw className={`w-3.5 h-3.5 ${loadingRequests ? 'animate-spin' : ''}`} /> Refresh
            </button>
          </div>

          {tripRequests.length > 0 ? (
            <div className="space-y-3">
              {tripRequests.map((req) => (
                <div
                  key={req.id}
                  id={`trip-req-card-${req.id}`}
                  className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-lg hover:border-slate-700 transition space-y-4"
                >
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-800">
                    <div>
                      <span className="text-xs font-mono font-bold text-slate-400">{req.booking_number}</span>
                      <h4 className="text-sm font-bold text-white mt-0.5">
                        {req.service_category?.name} • {req.vehicle_type?.name}
                      </h4>
                    </div>
                    <div className="text-right">
                      <p className="text-xs text-slate-400">Guaranteed Fare</p>
                      <p className="text-xl font-black text-emerald-400 font-mono">৳ {Number(req.estimated_fare).toFixed(2)}</p>
                    </div>
                  </div>

                  {/* Route */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                      <span className="text-slate-400">Pickup:</span>
                      <p className="text-slate-200 font-medium">{req.pickup_address}</p>
                    </div>
                    <div>
                      <span className="text-slate-400">Destination:</span>
                      <p className="text-slate-200 font-medium">{req.destination_address}</p>
                    </div>
                  </div>

                  {/* Distance & Load Specs */}
                  <div className="flex flex-wrap items-center justify-between gap-3 text-xs bg-slate-950/60 p-3 rounded-lg border border-slate-800/80">
                    <div className="flex items-center gap-3">
                      <span>Distance: <strong className="text-white">{req.distance_km} KM</strong></span>
                      <span>•</span>
                      <span>Driver Proximity: <strong className="text-emerald-400">{req.distance_from_driver_km} KM (ETA {req.pickup_eta_minutes}m)</strong></span>
                    </div>

                    {req.load_description && (
                      <span className="text-slate-400 text-[11px]">Cargo: {req.load_description}</span>
                    )}
                  </div>

                  {/* Action Buttons */}
                  <div className="flex items-center justify-end gap-2.5 pt-1">
                    <button
                      id={`btn-reject-trip-${req.id}`}
                      onClick={() => handleRejectTrip(req.id)}
                      className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-lg text-xs font-semibold transition"
                    >
                      Dismiss
                    </button>
                    <button
                      id={`btn-accept-trip-${req.id}`}
                      onClick={() => handleAcceptTrip(req.id)}
                      disabled={actionInProgress === `accept_${req.id}`}
                      className="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-xs transition shadow-lg shadow-emerald-900/30 flex items-center gap-1.5"
                    >
                      <CheckCircle2 className="w-4 h-4" /> Accept Trip (গ্রহণ করুন)
                    </button>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="bg-slate-900/60 border border-dashed border-slate-800 rounded-xl p-8 text-center text-slate-400 space-y-2">
              <Navigation className="w-8 h-8 text-slate-600 mx-auto" />
              <p className="text-sm font-semibold text-slate-300">No Pending Trip Requests in Your Queue</p>
              <p className="text-xs text-slate-500">
                Ensure your status is set to <strong className="text-emerald-400">ONLINE</strong>. You can also open the "Book a Trip" tab to create a customer booking.
              </p>
            </div>
          )}
        </div>

        {/* Right Column: Driver Telemetry Beacon & cPanel Simulation (4 Cols) */}
        <div className="lg:col-span-4 space-y-6">
          {/* GPS Telemetry Simulator Card */}
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-lg space-y-4">
            <div className="flex items-center justify-between pb-3 border-b border-slate-800">
              <h3 className="text-sm font-bold text-white flex items-center gap-2">
                <MapPin className="w-4 h-4 text-emerald-400" /> GPS Telemetry Heartbeat
              </h3>
              <span className="text-[10px] px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded-full font-mono font-semibold">
                REST Polling (cPanel Safe)
              </span>
            </div>

            <div className="space-y-3 text-xs">
              <div className="bg-slate-950 p-3 rounded-lg border border-slate-800 space-y-2">
                <div className="flex justify-between">
                  <span className="text-slate-400">Current Latitude:</span>
                  <span className="font-mono font-bold text-white">{currentLat.toFixed(6)}° N</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-400">Current Longitude:</span>
                  <span className="font-mono font-bold text-white">{currentLng.toFixed(6)}° E</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-400">Heading & Speed:</span>
                  <span className="font-mono text-emerald-400">{gpsHeading}° • {gpsSpeed} km/h</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-400">Heartbeat Interval:</span>
                  <span className="font-mono text-slate-400">5000 ms</span>
                </div>
              </div>

              <div className="flex items-center justify-between pt-1">
                <span className="text-slate-400">Live Beacon Status:</span>
                <button
                  onClick={() => setGpsHeartbeatActive(!gpsHeartbeatActive)}
                  className={`px-2.5 py-1 rounded text-[11px] font-bold ${
                    gpsHeartbeatActive ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-400'
                  }`}
                >
                  {gpsHeartbeatActive ? 'Transmitting' : 'Paused'}
                </button>
              </div>

              <button
                id="btn-trigger-manual-gps-ping"
                onClick={sendGpsHeartbeat}
                className="w-full py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-lg border border-slate-700 transition flex items-center justify-center gap-1.5"
              >
                <Send className="w-3.5 h-3.5 text-emerald-400" /> Send Manual GPS Ping
              </button>
            </div>
          </div>

          {/* Driver Payout & Commission Breakdown */}
          <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-lg space-y-3">
            <h3 className="text-sm font-bold text-white flex items-center gap-2">
              <DollarSign className="w-4 h-4 text-emerald-400" /> Driver Payout Policy
            </h3>
            <div className="space-y-2 text-xs text-slate-400">
              <div className="flex justify-between">
                <span>Driver Earnings Split:</span>
                <strong className="text-emerald-400">85% Net Fare</strong>
              </div>
              <div className="flex justify-between">
                <span>TripBD Platform Fee:</span>
                <strong className="text-slate-300">15%</strong>
              </div>
              <div className="flex justify-between">
                <span>Withdrawal Methods:</span>
                <span className="text-slate-300">bKash, Nagad, Bank EFT</span>
              </div>
              <div className="flex justify-between">
                <span>Daily Payout Window:</span>
                <span className="text-slate-300">24/7 Automated</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
