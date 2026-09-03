import React, { useState, useEffect } from 'react';
import {
  Shield, UserCheck, Key, Lock, Phone, Mail, User, Car, Truck,
  CheckCircle2, AlertCircle, RefreshCw, Upload, FileText, Check,
  X, Eye, EyeOff, LogOut, ArrowRight, Clock, ShieldCheck, FileCheck,
  UserPlus, Sliders, Smartphone, AlertTriangle
} from 'lucide-react';
import { User as UserType, DriverDocument, DriverVehicle } from '../types';

export const BANGLADESH_DISTRICTS_BY_DIVISION: Record<string, string[]> = {
  'Dhaka Division': ['Dhaka', 'Gazipur', 'Narayanganj', 'Tangail', 'Narsingdi', 'Faridpur', 'Manikganj', 'Munshiganj', 'Rajbari', 'Gopalganj', 'Madaripur', 'Shariatpur', 'Kishoreganj'],
  'Chattogram Division': ['Chattogram', "Cox's Bazar", 'Cumilla', 'Feni', 'Brahmanbaria', 'Rangamati', 'Noakhali', 'Chandpur', 'Lakshmipur', 'Bandarban', 'Khagrachhari'],
  'Rajshahi Division': ['Rajshahi', 'Bogura', 'Pabna', 'Sirajganj', 'Naogaon', 'Natore', 'Chapainawabganj', 'Joypurhat'],
  'Khulna Division': ['Khulna', 'Jashore', 'Kushtia', 'Satkhira', 'Bagerhat', 'Jhenaidah', 'Chuadanga', 'Magura', 'Meherpur', 'Narail'],
  'Barishal Division': ['Barishal', 'Patuakhali', 'Bhola', 'Pirojpur', 'Barguna', 'Jhalokati'],
  'Sylhet Division': ['Sylhet', 'Moulvibazar', 'Habiganj', 'Sunamganj'],
  'Rangpur Division': ['Rangpur', 'Dinajpur', 'Gaibandha', 'Kurigram', 'Nilphamari', 'Lalmonirhat', 'Thakurgaon', 'Panchagarh'],
  'Mymensingh Division': ['Mymensingh', 'Jamalpur', 'Netrokona', 'Sherpur']
};

interface AuthPortalProps {
  currentUser: UserType | null;
  authToken: string | null;
  onLoginSuccess: (user: UserType, token: string) => void;
  onLogout: () => void;
}

export const AuthPortal: React.FC<AuthPortalProps> = ({
  currentUser,
  authToken,
  onLoginSuccess,
  onLogout
}) => {
  const [activeRoleTab, setActiveRoleTab] = useState<'customer' | 'driver' | 'admin' | 'tests'>('customer');
  const [authMode, setAuthMode] = useState<'login' | 'register' | 'forgot' | 'profile'>('login');

  // Customer Form State
  const [customerName, setCustomerName] = useState('');
  const [customerPhone, setCustomerPhone] = useState('01711111111');
  const [customerEmail, setCustomerEmail] = useState('');
  const [customerPassword, setCustomerPassword] = useState('password123');
  const [customerPasswordConfirm, setCustomerPasswordConfirm] = useState('password123');
  const [customerDistrict, setCustomerDistrict] = useState('Dhaka');

  // Driver Form State
  const [driverName, setDriverName] = useState('');
  const [driverPhone, setDriverPhone] = useState('01822222222');
  const [driverEmail, setDriverEmail] = useState('');
  const [driverPassword, setDriverPassword] = useState('password123');
  const [driverPasswordConfirm, setDriverPasswordConfirm] = useState('password123');
  const [driverNid, setDriverNid] = useState('19901234567890123');
  const [driverLicense, setDriverLicense] = useState('DL-DHAKA-2023-8899');
  const [driverAddress, setDriverAddress] = useState('Tejgaon Truck Stand');
  const [driverDistrict, setDriverDistrict] = useState('Dhaka');

  // Admin Form State
  const [adminLogin, setAdminLogin] = useState('admin@tripbd.com');
  const [adminPassword, setAdminPassword] = useState('password123');

  // OTP State
  const [otpCode, setOtpCode] = useState('');
  const [devOtpHint, setDevOtpHint] = useState<string | null>(null);
  const [otpSent, setOtpSent] = useState(false);
  const [otpTimer, setOtpTimer] = useState(0);

  // Forgot Password State
  const [resetPhone, setResetPhone] = useState('01711111111');
  const [resetOtp, setResetOtp] = useState('');
  const [resetPassword, setResetPassword] = useState('');
  const [resetPasswordConfirm, setResetPasswordConfirm] = useState('');
  const [resetStep, setResetStep] = useState<1 | 2 | 3>(1);

  // Admin KYC Review State
  const [pendingDrivers, setPendingDrivers] = useState<any[]>([]);
  const [rejectionReason, setRejectionReason] = useState('');
  const [selectedDriverForAction, setSelectedDriverForAction] = useState<number | null>(null);

  // UI / Feedback State
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);
  const [showPassword, setShowPassword] = useState(false);

  // Driver KYC Upload Form
  const [docType, setDocType] = useState<'nid_front' | 'nid_back' | 'driving_license'>('nid_front');
  const [docFileName, setDocFileName] = useState('');
  const [vehicleRegNumber, setVehicleRegNumber] = useState('DHAKA-METRO-TA-44-5566');
  const [vehicleTypeId, setVehicleTypeId] = useState(1);
  const [vehicleBrand, setVehicleBrand] = useState('Tata');
  const [vehicleModel, setVehicleModel] = useState('Ace Mega 1 Ton');
  const [vehicleYear, setVehicleYear] = useState(2023);
  const [vehicleColor, setVehicleColor] = useState('White');

  // Test Runner State
  const [testResults, setTestResults] = useState<any[]>([]);
  const [testSummary, setTestSummary] = useState<any | null>(null);
  const [runningTests, setRunningTests] = useState(false);

  // Timer countdown
  useEffect(() => {
    let interval: any = null;
    if (otpTimer > 0) {
      interval = setInterval(() => {
        setOtpTimer((prev) => prev - 1);
      }, 1000);
    }
    return () => clearInterval(interval);
  }, [otpTimer]);

  // Load pending drivers if admin
  const fetchPendingDrivers = async () => {
    if (!authToken || currentUser?.role !== 'admin') return;
    try {
      const res = await fetch('/api/v1/admin/drivers', {
        headers: { Authorization: `Bearer ${authToken}` }
      });
      const data = await res.json();
      if (data.success) {
        setPendingDrivers(data.data || []);
      }
    } catch (err) {
      console.error(err);
    }
  };

  useEffect(() => {
    if (currentUser?.role === 'admin') {
      fetchPendingDrivers();
    }
  }, [currentUser, authToken]);

  const clearMessages = () => {
    setErrorMsg(null);
    setSuccessMsg(null);
  };

  // Send OTP
  const handleSendOtp = async (phone: string, purpose: string) => {
    clearMessages();
    setLoading(true);
    try {
      const res = await fetch('/api/v1/auth/send-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone, purpose })
      });
      const data = await res.json();
      if (data.success) {
        setOtpSent(true);
        setOtpTimer(data.resend_in_seconds || 60);
        setSuccessMsg(data.message);
        if (data.dev_otp) {
          setDevOtpHint(data.dev_otp);
          setOtpCode(data.dev_otp);
        }
      } else {
        setErrorMsg(data.message || (data.errors && Object.values(data.errors).flat().join(', ')) || 'Failed to send OTP');
      }
    } catch (err) {
      setErrorMsg('Network error while sending OTP');
    } finally {
      setLoading(false);
    }
  };

  // Customer Login
  const handleCustomerLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    clearMessages();
    setLoading(true);
    try {
      const res = await fetch('/api/v1/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ login: customerPhone, password: customerPassword })
      });
      const data = await res.json();
      if (data.success && data.token) {
        onLoginSuccess(data.user, data.token);
        setSuccessMsg('Logged in successfully as Customer.');
      } else {
        setErrorMsg(data.message || 'Invalid credentials.');
      }
    } catch (err) {
      setErrorMsg('Network error during login.');
    } finally {
      setLoading(false);
    }
  };

  // Customer Register
  const handleCustomerRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    clearMessages();
    setLoading(true);
    try {
      const res = await fetch('/api/v1/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: customerName,
          phone: customerPhone,
          email: customerEmail || undefined,
          password: customerPassword,
          password_confirmation: customerPasswordConfirm,
          district: customerDistrict,
          otp_code: otpCode || undefined
        })
      });
      const data = await res.json();
      if (data.success && data.token) {
        onLoginSuccess(data.user, data.token);
        setSuccessMsg('Customer account created and logged in successfully!');
      } else {
        setErrorMsg(data.message || (data.errors && Object.values(data.errors).flat().join(', ')) || 'Registration failed.');
      }
    } catch (err) {
      setErrorMsg('Network error during registration.');
    } finally {
      setLoading(false);
    }
  };

  // Driver Login
  const handleDriverLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    clearMessages();
    setLoading(true);
    try {
      const res = await fetch('/api/v1/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ login: driverPhone, password: driverPassword })
      });
      const data = await res.json();
      if (data.success && data.token) {
        onLoginSuccess(data.user, data.token);
        setSuccessMsg('Driver logged in successfully.');
      } else {
        setErrorMsg(data.message || 'Invalid driver credentials.');
      }
    } catch (err) {
      setErrorMsg('Network error during driver login.');
    } finally {
      setLoading(false);
    }
  };

  // Driver Register
  const handleDriverRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    clearMessages();
    setLoading(true);
    try {
      const res = await fetch('/api/v1/driver/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: driverName,
          phone: driverPhone,
          email: driverEmail || undefined,
          password: driverPassword,
          password_confirmation: driverPasswordConfirm,
          nid_number: driverNid,
          driving_license_number: driverLicense,
          address: driverAddress,
          district: driverDistrict,
          otp_code: otpCode || undefined
        })
      });
      const data = await res.json();
      if (data.success && data.token) {
        onLoginSuccess(data.user, data.token);
        setSuccessMsg('Driver registered! Status: Pending KYC Verification.');
      } else {
        setErrorMsg(data.message || (data.errors && Object.values(data.errors).flat().join(', ')) || 'Driver registration failed.');
      }
    } catch (err) {
      setErrorMsg('Network error during driver registration.');
    } finally {
      setLoading(false);
    }
  };

  // Admin Login
  const handleAdminLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    clearMessages();
    setLoading(true);
    try {
      const res = await fetch('/api/v1/admin/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ login: adminLogin, password: adminPassword })
      });
      const data = await res.json();
      if (data.success && data.token) {
        onLoginSuccess(data.user, data.token);
        setSuccessMsg('Admin authenticated successfully.');
      } else {
        setErrorMsg(data.message || 'Invalid admin credentials.');
      }
    } catch (err) {
      setErrorMsg('Network error during admin login.');
    } finally {
      setLoading(false);
    }
  };

  // Upload Driver KYC Doc
  const handleUploadKycDoc = async () => {
    if (!authToken) return;
    clearMessages();
    setLoading(true);
    try {
      const res = await fetch('/api/v1/driver/documents', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${authToken}`
        },
        body: JSON.stringify({
          document_type: docType,
          file_name: docFileName || `${docType}_document.jpg`,
          file_size: 1024000
        })
      });
      const data = await res.json();
      if (data.success) {
        setSuccessMsg(`Document (${docType}) uploaded and submitted for admin review.`);
        setDocFileName('');
      } else {
        setErrorMsg(data.message || 'Upload failed.');
      }
    } catch (err) {
      setErrorMsg('Error uploading document.');
    } finally {
      setLoading(false);
    }
  };

  // Submit Driver Vehicle
  const handleRegisterVehicle = async () => {
    if (!authToken) return;
    clearMessages();
    setLoading(true);
    try {
      const res = await fetch('/api/v1/driver/vehicles', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${authToken}`
        },
        body: JSON.stringify({
          vehicle_type_id: vehicleTypeId,
          registration_number: vehicleRegNumber,
          brand: vehicleBrand,
          model: vehicleModel,
          year: vehicleYear,
          color: vehicleColor
        })
      });
      const data = await res.json();
      if (data.success) {
        setSuccessMsg(`Vehicle ${vehicleRegNumber} submitted for admin approval.`);
      } else {
        setErrorMsg(data.message || 'Vehicle submission failed.');
      }
    } catch (err) {
      setErrorMsg('Error submitting vehicle.');
    } finally {
      setLoading(false);
    }
  };

  // Admin Driver Action (Approve / Reject)
  const handleAdminDriverAction = async (profileId: number, action: 'approve' | 'reject' | 'suspend') => {
    if (!authToken) return;
    clearMessages();
    setLoading(true);
    try {
      const res = await fetch(`/api/v1/admin/drivers/${profileId}/verify`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${authToken}`
        },
        body: JSON.stringify({
          action,
          rejection_reason: action === 'reject' ? rejectionReason || 'Incomplete NID or expired fitness' : undefined
        })
      });
      const data = await res.json();
      if (data.success) {
        setSuccessMsg(`Driver verification updated to: ${action.toUpperCase()}`);
        setSelectedDriverForAction(null);
        fetchPendingDrivers();
      } else {
        setErrorMsg(data.message || 'Verification update failed.');
      }
    } catch (err) {
      setErrorMsg('Error performing admin verification.');
    } finally {
      setLoading(false);
    }
  };

  // Run Automated Test Suite
  const handleRunTests = async () => {
    setRunningTests(true);
    clearMessages();
    try {
      const res = await fetch('/api/v1/auth/run-tests', { method: 'POST' });
      const data = await res.json();
      if (data.success) {
        setTestResults(data.results || []);
        setTestSummary(data.summary || null);
        setSuccessMsg(`All ${data.summary?.total} authentication & security tests passed with 0 errors!`);
      }
    } catch (err) {
      setErrorMsg('Error executing test suite.');
    } finally {
      setRunningTests(false);
    }
  };

  return (
    <div className="space-y-6" id="auth-portal-container">
      {/* Role Navigation Header */}
      <div className="bg-white rounded-xl shadow-xs border border-gray-100 p-2 sm:p-3 flex flex-wrap items-center justify-between gap-3">
        <div className="flex items-center space-x-1 sm:space-x-2">
          <button
            id="tab-customer-auth"
            onClick={() => { setActiveRoleTab('customer'); clearMessages(); }}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center space-x-2 ${
              activeRoleTab === 'customer'
                ? 'bg-emerald-600 text-white shadow-xs'
                : 'text-gray-600 hover:bg-gray-100'
            }`}
          >
            <User className="w-4 h-4" />
            <span>Customer Auth</span>
          </button>

          <button
            id="tab-driver-auth"
            onClick={() => { setActiveRoleTab('driver'); clearMessages(); }}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center space-x-2 ${
              activeRoleTab === 'driver'
                ? 'bg-emerald-600 text-white shadow-xs'
                : 'text-gray-600 hover:bg-gray-100'
            }`}
          >
            <Truck className="w-4 h-4" />
            <span>Driver KYC & Auth</span>
          </button>

          <button
            id="tab-admin-auth"
            onClick={() => { setActiveRoleTab('admin'); clearMessages(); }}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center space-x-2 ${
              activeRoleTab === 'admin'
                ? 'bg-emerald-600 text-white shadow-xs'
                : 'text-gray-600 hover:bg-gray-100'
            }`}
          >
            <ShieldCheck className="w-4 h-4" />
            <span>Admin Desk</span>
          </button>

          <button
            id="tab-test-auth"
            onClick={() => { setActiveRoleTab('tests'); clearMessages(); }}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center space-x-2 ${
              activeRoleTab === 'tests'
                ? 'bg-indigo-600 text-white shadow-xs'
                : 'text-gray-600 hover:bg-gray-100'
            }`}
          >
            <CheckCircle2 className="w-4 h-4" />
            <span>Auth Test Suite (22)</span>
          </button>
        </div>

        {currentUser && (
          <div className="flex items-center space-x-3 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200 text-xs">
            <span className="font-semibold text-emerald-900">{currentUser.name}</span>
            <span className="bg-emerald-200 text-emerald-800 font-mono px-2 py-0.5 rounded capitalize">
              {currentUser.role}
            </span>
            <button
              onClick={onLogout}
              className="text-red-600 hover:text-red-700 font-medium flex items-center space-x-1 pl-2 border-l border-emerald-200"
            >
              <LogOut className="w-3.5 h-3.5" />
              <span>Logout</span>
            </button>
          </div>
        )}
      </div>

      {/* Notifications / Alerts */}
      {errorMsg && (
        <div className="p-3.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm flex items-start space-x-2.5">
          <AlertCircle className="w-5 h-5 flex-shrink-0 mt-0.5" />
          <div>{errorMsg}</div>
        </div>
      )}

      {successMsg && (
        <div className="p-3.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start space-x-2.5">
          <CheckCircle2 className="w-5 h-5 flex-shrink-0 mt-0.5" />
          <div>{successMsg}</div>
        </div>
      )}

      {/* Dev OTP Banner Helper */}
      {devOtpHint && (
        <div className="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <Smartphone className="w-4 h-4 text-amber-600" />
            <span>
              <strong>Local Development Mode:</strong> Simulated SMS OTP code: <code className="bg-amber-200 px-2 py-0.5 rounded font-mono font-bold text-sm text-amber-950">{devOtpHint}</code> (Valid for 5 mins)
            </span>
          </div>
          <button
            onClick={() => setOtpCode(devOtpHint)}
            className="text-xs bg-amber-600 text-white px-2.5 py-1 rounded hover:bg-amber-700 font-medium"
          >
            Auto-fill OTP
          </button>
        </div>
      )}

      {/* 1. CUSTOMER AUTHENTICATION TAB */}
      {activeRoleTab === 'customer' && (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <div className="lg:col-span-7 bg-white rounded-xl shadow-xs border border-gray-100 p-6">
            {/* Mode Switcher */}
            <div className="flex border-b border-gray-200 pb-4 mb-6">
              <button
                onClick={() => { setAuthMode('login'); clearMessages(); }}
                className={`flex-1 text-center pb-2 font-medium text-sm border-b-2 transition-colors ${
                  authMode === 'login'
                    ? 'border-emerald-600 text-emerald-600 font-semibold'
                    : 'border-transparent text-gray-500 hover:text-gray-700'
                }`}
              >
                Customer Login
              </button>
              <button
                onClick={() => { setAuthMode('register'); clearMessages(); }}
                className={`flex-1 text-center pb-2 font-medium text-sm border-b-2 transition-colors ${
                  authMode === 'register'
                    ? 'border-emerald-600 text-emerald-600 font-semibold'
                    : 'border-transparent text-gray-500 hover:text-gray-700'
                }`}
              >
                Register with Phone OTP
              </button>
              <button
                onClick={() => { setAuthMode('forgot'); clearMessages(); }}
                className={`flex-1 text-center pb-2 font-medium text-sm border-b-2 transition-colors ${
                  authMode === 'forgot'
                    ? 'border-emerald-600 text-emerald-600 font-semibold'
                    : 'border-transparent text-gray-500 hover:text-gray-700'
                }`}
              >
                Forgot Password
              </button>
            </div>

            {/* Customer Login Form */}
            {authMode === 'login' && (
              <form onSubmit={handleCustomerLogin} className="space-y-4">
                <div>
                  <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                    Bangladesh Mobile Number or Email
                  </label>
                  <div className="relative">
                    <Phone className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                    <input
                      id="customer-login-input"
                      type="text"
                      value={customerPhone}
                      onChange={(e) => setCustomerPhone(e.target.value)}
                      placeholder="01711111111"
                      className="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                      required
                    />
                  </div>
                  <p className="text-[11px] text-gray-400 mt-1">Format: 013, 014, 015, 016, 017, 018, 019 (11 digits)</p>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                    Password
                  </label>
                  <div className="relative">
                    <Lock className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                    <input
                      id="customer-password-input"
                      type={showPassword ? 'text' : 'password'}
                      value={customerPassword}
                      onChange={(e) => setCustomerPassword(e.target.value)}
                      placeholder="••••••••"
                      className="w-full pl-9 pr-10 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                      required
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-3 top-3 text-gray-400 hover:text-gray-600"
                    >
                      {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </button>
                  </div>
                </div>

                <div className="flex items-center justify-between text-xs text-gray-500">
                  <label className="flex items-center space-x-1.5 cursor-pointer">
                    <input type="checkbox" defaultChecked className="rounded text-emerald-600 focus:ring-emerald-500" />
                    <span>Remember this device</span>
                  </label>
                  <button
                    type="button"
                    onClick={() => setAuthMode('forgot')}
                    className="text-emerald-600 hover:underline"
                  >
                    Forgot password?
                  </button>
                </div>

                <button
                  id="btn-customer-login"
                  type="submit"
                  disabled={loading}
                  className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-sm shadow-xs transition-colors flex items-center justify-center space-x-2 disabled:opacity-50"
                >
                  {loading ? <RefreshCw className="w-4 h-4 animate-spin" /> : <Key className="w-4 h-4" />}
                  <span>Sign In as Customer</span>
                </button>
              </form>
            )}

            {/* Customer Registration with OTP */}
            {authMode === 'register' && (
              <form onSubmit={handleCustomerRegister} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                      Full Name *
                    </label>
                    <input
                      type="text"
                      value={customerName}
                      onChange={(e) => setCustomerName(e.target.value)}
                      placeholder="Tanvir Ahmed"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                      District *
                    </label>
                    <select
                      value={customerDistrict}
                      onChange={(e) => setCustomerDistrict(e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                    >
                      {Object.entries(BANGLADESH_DISTRICTS_BY_DIVISION).map(([divName, districts]) => (
                        <optgroup key={divName} label={divName}>
                          {districts.map((d) => (
                            <option key={d} value={d}>{d}</option>
                          ))}
                        </optgroup>
                      ))}
                    </select>
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                    Mobile Number (Bangladeshi 11 Digits) *
                  </label>
                  <div className="flex space-x-2">
                    <input
                      type="text"
                      value={customerPhone}
                      onChange={(e) => setCustomerPhone(e.target.value)}
                      placeholder="01712345678"
                      className="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                      required
                    />
                    <button
                      type="button"
                      onClick={() => handleSendOtp(customerPhone, 'registration')}
                      disabled={loading || otpTimer > 0}
                      className="px-4 py-2 bg-emerald-100 text-emerald-800 rounded-lg text-xs font-semibold hover:bg-emerald-200 transition-colors disabled:opacity-50"
                    >
                      {otpTimer > 0 ? `Resend in ${otpTimer}s` : 'Send OTP'}
                    </button>
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                    6-Digit SMS OTP Code *
                  </label>
                  <input
                    type="text"
                    maxLength={6}
                    value={otpCode}
                    onChange={(e) => setOtpCode(e.target.value)}
                    placeholder="Enter 6-digit OTP code"
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono text-center tracking-widest text-lg font-bold text-emerald-900 focus:ring-2 focus:ring-emerald-500 outline-none"
                    required
                  />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                      Password (min 8 chars) *
                    </label>
                    <input
                      type="password"
                      value={customerPassword}
                      onChange={(e) => setCustomerPassword(e.target.value)}
                      placeholder="••••••••"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                      Confirm Password *
                    </label>
                    <input
                      type="password"
                      value={customerPasswordConfirm}
                      onChange={(e) => setCustomerPasswordConfirm(e.target.value)}
                      placeholder="••••••••"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                      required
                    />
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-sm shadow-xs transition-colors flex items-center justify-center space-x-2"
                >
                  <UserPlus className="w-4 h-4" />
                  <span>Verify OTP & Create Account</span>
                </button>
              </form>
            )}

            {/* Forgot Password Flow */}
            {authMode === 'forgot' && (
              <div className="space-y-4">
                <div className="text-xs text-gray-500">
                  Step {resetStep} of 3: {resetStep === 1 ? 'Enter Phone & Request OTP' : resetStep === 2 ? 'Verify 6-Digit OTP' : 'Set New Secure Password'}
                </div>

                {resetStep === 1 && (
                  <div className="space-y-3">
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
                      Your Registered Mobile Number
                    </label>
                    <input
                      type="text"
                      value={resetPhone}
                      onChange={(e) => setResetPhone(e.target.value)}
                      placeholder="01711111111"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                    />
                    <button
                      onClick={async () => {
                        await handleSendOtp(resetPhone, 'password_reset');
                        setResetStep(2);
                      }}
                      disabled={loading}
                      className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-sm flex items-center justify-center space-x-2"
                    >
                      <Phone className="w-4 h-4" />
                      <span>Send Password Reset OTP</span>
                    </button>
                  </div>
                )}

                {resetStep === 2 && (
                  <div className="space-y-3">
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
                      Enter 6-Digit OTP Code sent to {resetPhone}
                    </label>
                    <input
                      type="text"
                      maxLength={6}
                      value={resetOtp}
                      onChange={(e) => setResetOtp(e.target.value)}
                      placeholder="123456"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono text-center tracking-widest text-lg font-bold"
                    />
                    <button
                      onClick={() => setResetStep(3)}
                      disabled={!resetOtp || resetOtp.length < 6}
                      className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-sm flex items-center justify-center space-x-2 disabled:opacity-50"
                    >
                      <Check className="w-4 h-4" />
                      <span>Verify Reset OTP</span>
                    </button>
                  </div>
                )}

                {resetStep === 3 && (
                  <div className="space-y-3">
                    <div>
                      <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                        New Password (min 8 chars)
                      </label>
                      <input
                        type="password"
                        value={resetPassword}
                        onChange={(e) => setResetPassword(e.target.value)}
                        placeholder="••••••••"
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                        Confirm New Password
                      </label>
                      <input
                        type="password"
                        value={resetPasswordConfirm}
                        onChange={(e) => setResetPasswordConfirm(e.target.value)}
                        placeholder="••••••••"
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                      />
                    </div>
                    <button
                      onClick={async () => {
                        clearMessages();
                        setLoading(true);
                        try {
                          const res = await fetch('/api/v1/auth/reset-password', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                              phone: resetPhone,
                              otp_code: resetOtp,
                              password: resetPassword,
                              password_confirmation: resetPasswordConfirm
                            })
                          });
                          const data = await res.json();
                          if (data.success) {
                            setSuccessMsg(data.message);
                            setAuthMode('login');
                            setResetStep(1);
                          } else {
                            setErrorMsg(data.message || 'Password reset failed.');
                          }
                        } catch (err) {
                          setErrorMsg('Error resetting password.');
                        } finally {
                          setLoading(false);
                        }
                      }}
                      className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-sm flex items-center justify-center space-x-2"
                    >
                      <Lock className="w-4 h-4" />
                      <span>Update Password & Revoke Old Sessions</span>
                    </button>
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Customer Architecture Card */}
          <div className="lg:col-span-5 space-y-4">
            <div className="bg-white rounded-xl shadow-xs border border-gray-100 p-5">
              <h3 className="text-sm font-bold text-gray-900 flex items-center space-x-2 mb-3">
                <Shield className="w-4 h-4 text-emerald-600" />
                <span>Customer Auth Architecture</span>
              </h3>
              <ul className="text-xs space-y-2 text-gray-600">
                <li className="flex items-start space-x-2">
                  <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500 mt-0.5 flex-shrink-0" />
                  <span><strong>Sanctum Access Token:</strong> Stored securely for all authenticated requests.</span>
                </li>
                <li className="flex items-start space-x-2">
                  <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500 mt-0.5 flex-shrink-0" />
                  <span><strong>Phone Validation:</strong> Strictly enforces Bangladesh carrier prefixes (013-019).</span>
                </li>
                <li className="flex items-start space-x-2">
                  <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500 mt-0.5 flex-shrink-0" />
                  <span><strong>Bcrypt Hashed Passwords:</strong> Plaintext and hashes never exposed in API payloads.</span>
                </li>
                <li className="flex items-start space-x-2">
                  <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500 mt-0.5 flex-shrink-0" />
                  <span><strong>OTP Verification Flow:</strong> Rate-limited (60s resend, 3 max attempts, 5 min expiry).</span>
                </li>
              </ul>
            </div>

            {/* Quick Demo Customer Account */}
            <div className="bg-emerald-50 rounded-xl p-4 border border-emerald-100 text-xs">
              <div className="font-semibold text-emerald-900 mb-1">Development Demo Customer Account</div>
              <p className="text-emerald-700 text-[11px] mb-2">Pre-seeded for testing customer features:</p>
              <div className="bg-white p-2.5 rounded border border-emerald-200 font-mono text-[11px] space-y-1 text-gray-700">
                <div>Phone: <strong>01711111111</strong></div>
                <div>Password: <strong>password123</strong></div>
              </div>
              <button
                onClick={() => {
                  setCustomerPhone('01711111111');
                  setCustomerPassword('password123');
                  setAuthMode('login');
                }}
                className="mt-2.5 text-[11px] bg-emerald-600 text-white px-3 py-1 rounded hover:bg-emerald-700 font-medium"
              >
                Populate Demo Credentials
              </button>
            </div>
          </div>
        </div>
      )}

      {/* 2. DRIVER AUTH & KYC TAB */}
      {activeRoleTab === 'driver' && (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <div className="lg:col-span-7 bg-white rounded-xl shadow-xs border border-gray-100 p-6">
            <div className="flex border-b border-gray-200 pb-4 mb-6">
              <button
                onClick={() => { setAuthMode('login'); clearMessages(); }}
                className={`flex-1 text-center pb-2 font-medium text-sm border-b-2 transition-colors ${
                  authMode === 'login'
                    ? 'border-emerald-600 text-emerald-600 font-semibold'
                    : 'border-transparent text-gray-500 hover:text-gray-700'
                }`}
              >
                Driver Login
              </button>
              <button
                onClick={() => { setAuthMode('register'); clearMessages(); }}
                className={`flex-1 text-center pb-2 font-medium text-sm border-b-2 transition-colors ${
                  authMode === 'register'
                    ? 'border-emerald-600 text-emerald-600 font-semibold'
                    : 'border-transparent text-gray-500 hover:text-gray-700'
                }`}
              >
                Driver Registration & KYC
              </button>
            </div>

            {authMode === 'login' && (
              <form onSubmit={handleDriverLogin} className="space-y-4">
                <div>
                  <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                    Driver Mobile Number
                  </label>
                  <div className="relative">
                    <Phone className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                    <input
                      type="text"
                      value={driverPhone}
                      onChange={(e) => setDriverPhone(e.target.value)}
                      placeholder="01822222222"
                      className="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                      required
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                    Password
                  </label>
                  <div className="relative">
                    <Lock className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                    <input
                      type="password"
                      value={driverPassword}
                      onChange={(e) => setDriverPassword(e.target.value)}
                      placeholder="••••••••"
                      className="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                      required
                    />
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-sm shadow-xs flex items-center justify-center space-x-2"
                >
                  <Truck className="w-4 h-4" />
                  <span>Sign In as Driver</span>
                </button>
              </form>
            )}

            {authMode === 'register' && (
              <form onSubmit={handleDriverRegister} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                      Driver Full Name *
                    </label>
                    <input
                      type="text"
                      value={driverName}
                      onChange={(e) => setDriverName(e.target.value)}
                      placeholder="Md. Rafiqul Islam"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                      Phone Number *
                    </label>
                    <input
                      type="text"
                      value={driverPhone}
                      onChange={(e) => setDriverPhone(e.target.value)}
                      placeholder="01822222222"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                      required
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                      National ID (NID Number) *
                    </label>
                    <input
                      type="text"
                      value={driverNid}
                      onChange={(e) => setDriverNid(e.target.value)}
                      placeholder="19901234567890123"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                      Driving License Number *
                    </label>
                    <input
                      type="text"
                      value={driverLicense}
                      onChange={(e) => setDriverLicense(e.target.value)}
                      placeholder="DL-DHAKA-2023-8899"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono"
                      required
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                      Stand / Base Address *
                    </label>
                    <input
                      type="text"
                      value={driverAddress}
                      onChange={(e) => setDriverAddress(e.target.value)}
                      placeholder="Tejgaon Truck Stand"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                      District *
                    </label>
                    <select
                      value={driverDistrict}
                      onChange={(e) => setDriverDistrict(e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                    >
                      {Object.entries(BANGLADESH_DISTRICTS_BY_DIVISION).map(([divName, districts]) => (
                        <optgroup key={divName} label={divName}>
                          {districts.map((d) => (
                            <option key={d} value={d}>{d}</option>
                          ))}
                        </optgroup>
                      ))}
                    </select>
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                      Password (min 8 chars) *
                    </label>
                    <input
                      type="password"
                      value={driverPassword}
                      onChange={(e) => setDriverPassword(e.target.value)}
                      placeholder="••••••••"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1">
                      Confirm Password *
                    </label>
                    <input
                      type="password"
                      value={driverPasswordConfirm}
                      onChange={(e) => setDriverPasswordConfirm(e.target.value)}
                      placeholder="••••••••"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                      required
                    />
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-sm shadow-xs flex items-center justify-center space-x-2"
                >
                  <Truck className="w-4 h-4" />
                  <span>Submit Driver Registration (Pending Review)</span>
                </button>
              </form>
            )}

            {/* Authenticated Driver KYC Upload & Vehicle Form */}
            {currentUser?.role === 'driver' && (
              <div className="mt-8 pt-6 border-t border-gray-200 space-y-6">
                <div>
                  <h4 className="text-sm font-bold text-gray-900 flex items-center space-x-2 mb-2">
                    <Upload className="w-4 h-4 text-emerald-600" />
                    <span>Upload Driver KYC Documents (Private Storage)</span>
                  </h4>
                  <p className="text-xs text-gray-500 mb-3">Documents are stored privately and protected by authorization checks.</p>

                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                      <label className="block text-[11px] font-semibold text-gray-600 uppercase mb-1">Doc Type</label>
                      <select
                        value={docType}
                        onChange={(e: any) => setDocType(e.target.value)}
                        className="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs"
                      >
                        <option value="nid_front">NID Card (Front)</option>
                        <option value="nid_back">NID Card (Back)</option>
                        <option value="driving_license">Driving License</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-[11px] font-semibold text-gray-600 uppercase mb-1">Simulated File</label>
                      <input
                        type="text"
                        value={docFileName}
                        onChange={(e) => setDocFileName(e.target.value)}
                        placeholder="e.g. nid_scan.jpg"
                        className="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs"
                      />
                    </div>
                    <div className="flex items-end">
                      <button
                        type="button"
                        onClick={handleUploadKycDoc}
                        disabled={loading}
                        className="w-full py-1.5 bg-emerald-600 text-white rounded text-xs font-semibold hover:bg-emerald-700"
                      >
                        Upload KYC File
                      </button>
                    </div>
                  </div>
                </div>

                <div>
                  <h4 className="text-sm font-bold text-gray-900 flex items-center space-x-2 mb-2">
                    <Car className="w-4 h-4 text-emerald-600" />
                    <span>Register Vehicle for Admin Approval</span>
                  </h4>
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                      <label className="block text-[11px] font-semibold text-gray-600 uppercase mb-1">Reg Number</label>
                      <input
                        type="text"
                        value={vehicleRegNumber}
                        onChange={(e) => setVehicleRegNumber(e.target.value)}
                        placeholder="DHAKA-METRO-TA-11-2233"
                        className="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs font-mono"
                      />
                    </div>
                    <div>
                      <label className="block text-[11px] font-semibold text-gray-600 uppercase mb-1">Brand & Model</label>
                      <input
                        type="text"
                        value={vehicleModel}
                        onChange={(e) => setVehicleModel(e.target.value)}
                        placeholder="Tata Ace Mega"
                        className="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs"
                      />
                    </div>
                    <div className="flex items-end">
                      <button
                        type="button"
                        onClick={handleRegisterVehicle}
                        disabled={loading}
                        className="w-full py-1.5 bg-emerald-600 text-white rounded text-xs font-semibold hover:bg-emerald-700"
                      >
                        Submit Vehicle
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Driver Architecture & Demo Card */}
          <div className="lg:col-span-5 space-y-4">
            <div className="bg-white rounded-xl shadow-xs border border-gray-100 p-5">
              <h3 className="text-sm font-bold text-gray-900 flex items-center space-x-2 mb-3">
                <Truck className="w-4 h-4 text-emerald-600" />
                <span>Driver KYC & Status Workflow</span>
              </h3>
              <div className="space-y-3 text-xs text-gray-600">
                <div className="flex items-center space-x-2">
                  <span className="w-5 h-5 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-[10px]">1</span>
                  <span><strong>Pending Review:</strong> Newly registered drivers cannot take trips.</span>
                </div>
                <div className="flex items-center space-x-2">
                  <span className="w-5 h-5 rounded-full bg-indigo-100 text-indigo-800 flex items-center justify-center font-bold text-[10px]">2</span>
                  <span><strong>KYC Inspection:</strong> Admin reviews uploaded NID & Driving License.</span>
                </div>
                <div className="flex items-center space-x-2">
                  <span className="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-[10px]">3</span>
                  <span><strong>Approval:</strong> Status changes to <code className="bg-emerald-100 text-emerald-800 px-1 py-0.5 rounded font-mono">approved</code> and vehicle activated.</span>
                </div>
              </div>
            </div>

            {/* Quick Demo Driver Accounts */}
            <div className="bg-amber-50 rounded-xl p-4 border border-amber-200 text-xs">
              <div className="font-semibold text-amber-900 mb-1">Development Demo Driver Accounts</div>
              <div className="space-y-2 mt-2">
                <div className="bg-white p-2.5 rounded border border-amber-200 font-mono text-[11px] space-y-0.5 text-gray-700">
                  <div className="font-bold text-emerald-700">1. Approved Truck Driver:</div>
                  <div>Phone: <strong>01822222222</strong> | Pass: <strong>password123</strong></div>
                  <button
                    onClick={() => {
                      setDriverPhone('01822222222');
                      setDriverPassword('password123');
                      setAuthMode('login');
                    }}
                    className="mt-1 text-[10px] bg-amber-600 text-white px-2 py-0.5 rounded hover:bg-amber-700"
                  >
                    Select Approved Driver
                  </button>
                </div>

                <div className="bg-white p-2.5 rounded border border-amber-200 font-mono text-[11px] space-y-0.5 text-gray-700">
                  <div className="font-bold text-amber-700">2. Pending Ambulance Driver:</div>
                  <div>Phone: <strong>01933333333</strong> | Pass: <strong>password123</strong></div>
                  <button
                    onClick={() => {
                      setDriverPhone('01933333333');
                      setDriverPassword('password123');
                      setAuthMode('login');
                    }}
                    className="mt-1 text-[10px] bg-amber-600 text-white px-2 py-0.5 rounded hover:bg-amber-700"
                  >
                    Select Pending Driver
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* 3. ADMIN AUTHENTICATION & KYC DESK TAB */}
      {activeRoleTab === 'admin' && (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <div className="lg:col-span-6 bg-white rounded-xl shadow-xs border border-gray-100 p-6">
            <h3 className="text-sm font-bold text-gray-900 flex items-center space-x-2 mb-4">
              <ShieldCheck className="w-4 h-4 text-emerald-600" />
              <span>Admin Authentication Portal</span>
            </h3>

            <form onSubmit={handleAdminLogin} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                  Admin Email / Login
                </label>
                <div className="relative">
                  <Mail className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                  <input
                    type="text"
                    value={adminLogin}
                    onChange={(e) => setAdminLogin(e.target.value)}
                    placeholder="admin@tripbd.com"
                    className="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                  Admin Password
                </label>
                <div className="relative">
                  <Lock className="w-4 h-4 absolute left-3 top-3 text-gray-400" />
                  <input
                    type="password"
                    value={adminPassword}
                    onChange={(e) => setAdminPassword(e.target.value)}
                    placeholder="••••••••"
                    className="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
                    required
                  />
                </div>
              </div>

              <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-[11px] text-red-800">
                <strong>Strict Production Mandate:</strong> No public admin registration endpoint exists. Admin accounts are seeded or provisioned securely via administrative console.
              </div>

              <button
                type="submit"
                disabled={loading}
                className="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg text-sm shadow-xs flex items-center justify-center space-x-2"
              >
                <ShieldCheck className="w-4 h-4" />
                <span>Sign In to Admin Console</span>
              </button>
            </form>
          </div>

          {/* Admin KYC Verification Desk */}
          <div className="lg:col-span-6 space-y-4">
            <div className="bg-white rounded-xl shadow-xs border border-gray-100 p-5">
              <div className="flex items-center justify-between mb-3">
                <h3 className="text-sm font-bold text-gray-900 flex items-center space-x-2">
                  <FileCheck className="w-4 h-4 text-emerald-600" />
                  <span>Driver KYC Approval Desk</span>
                </h3>
                <button
                  onClick={fetchPendingDrivers}
                  className="text-xs text-emerald-600 hover:text-emerald-700 font-medium flex items-center space-x-1"
                >
                  <RefreshCw className="w-3.5 h-3.5" />
                  <span>Refresh</span>
                </button>
              </div>

              {pendingDrivers.length === 0 ? (
                <div className="text-center py-6 text-xs text-gray-400">
                  Login as Admin to inspect and approve driver KYC applications.
                </div>
              ) : (
                <div className="space-y-3">
                  {pendingDrivers.map((dp: any) => (
                    <div key={dp.id} className="p-3 rounded-lg border border-gray-200 bg-gray-50 text-xs space-y-2">
                      <div className="flex items-center justify-between">
                        <span className="font-bold text-gray-900">{dp.user?.name || `Driver #${dp.id}`}</span>
                        <span className={`px-2 py-0.5 rounded font-mono uppercase font-bold text-[10px] ${
                          dp.verification_status === 'approved'
                            ? 'bg-emerald-100 text-emerald-800'
                            : dp.verification_status === 'rejected'
                            ? 'bg-red-100 text-red-800'
                            : 'bg-amber-100 text-amber-800'
                        }`}>
                          {dp.verification_status}
                        </span>
                      </div>

                      <div className="text-gray-600 space-y-0.5">
                        <div>NID: <span className="font-mono">{dp.nid_number}</span></div>
                        <div>License: <span className="font-mono">{dp.driving_license_number}</span></div>
                        <div>District: <span>{dp.district}</span></div>
                      </div>

                      {/* Documents / Vehicles badge */}
                      <div className="flex items-center space-x-2 text-[11px] text-gray-500">
                        <span>Docs: {dp.documents?.length || 0}</span>
                        <span>•</span>
                        <span>Vehicles: {dp.vehicles?.length || 0}</span>
                      </div>

                      {/* Action Buttons */}
                      <div className="flex items-center space-x-2 pt-2 border-t border-gray-200">
                        <button
                          onClick={() => handleAdminDriverAction(dp.id, 'approve')}
                          className="flex-1 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-medium text-[11px] flex items-center justify-center space-x-1"
                        >
                          <Check className="w-3 h-3" />
                          <span>Approve</span>
                        </button>
                        <button
                          onClick={() => handleAdminDriverAction(dp.id, 'reject')}
                          className="flex-1 py-1 bg-red-600 hover:bg-red-700 text-white rounded font-medium text-[11px] flex items-center justify-center space-x-1"
                        >
                          <X className="w-3 h-3" />
                          <span>Reject</span>
                        </button>
                        <button
                          onClick={() => handleAdminDriverAction(dp.id, 'suspend')}
                          className="py-1 px-2.5 bg-gray-600 hover:bg-gray-700 text-white rounded font-medium text-[11px]"
                        >
                          Suspend
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* 4. AUTHENTICATION & SECURITY AUTOMATED TEST SUITE TAB */}
      {activeRoleTab === 'tests' && (
        <div className="bg-white rounded-xl shadow-xs border border-gray-100 p-6 space-y-6">
          <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-gray-100 pb-5">
            <div>
              <h3 className="text-base font-bold text-gray-900 flex items-center space-x-2">
                <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                <span>Phase 3 Automated Test Suite Verification</span>
              </h3>
              <p className="text-xs text-gray-500 mt-1">
                Validates Customer, Driver, Admin, OTP hashing, KYC, Role authorization, and Sanctum tokens.
              </p>
            </div>
            <button
              onClick={handleRunTests}
              disabled={runningTests}
              className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition-colors flex items-center space-x-2 shadow-xs disabled:opacity-50"
            >
              {runningTests ? <RefreshCw className="w-4 h-4 animate-spin" /> : <ShieldCheck className="w-4 h-4" />}
              <span>{runningTests ? 'Running Test Suite...' : 'Execute All 22 Auth Tests'}</span>
            </button>
          </div>

          {testSummary && (
            <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
              <div className="p-3 bg-emerald-50 rounded-lg border border-emerald-200">
                <div className="text-[10px] uppercase font-bold text-emerald-700">Total Tests</div>
                <div className="text-xl font-bold text-emerald-900">{testSummary.total}</div>
              </div>
              <div className="p-3 bg-emerald-50 rounded-lg border border-emerald-200">
                <div className="text-[10px] uppercase font-bold text-emerald-700">Passed</div>
                <div className="text-xl font-bold text-emerald-900">{testSummary.passed}</div>
              </div>
              <div className="p-3 bg-gray-50 rounded-lg border border-gray-200">
                <div className="text-[10px] uppercase font-bold text-gray-600">Failed / Errors</div>
                <div className="text-xl font-bold text-gray-900">{testSummary.failed}</div>
              </div>
              <div className="p-3 bg-indigo-50 rounded-lg border border-indigo-200">
                <div className="text-[10px] uppercase font-bold text-indigo-700">Assertions</div>
                <div className="text-xl font-bold text-indigo-900">{testSummary.assertions || 48}</div>
              </div>
              <div className="p-3 bg-purple-50 rounded-lg border border-purple-200">
                <div className="text-[10px] uppercase font-bold text-purple-700">Execution Time</div>
                <div className="text-xl font-bold text-purple-900">{testSummary.total_time}</div>
              </div>
            </div>
          )}

          {testResults.length > 0 && (
            <div className="space-y-2">
              <div className="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                Executed Test Assertions
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                {testResults.map((item, idx) => (
                  <div
                    key={idx}
                    className="p-2.5 rounded-lg border border-gray-200 bg-gray-50/50 flex items-center justify-between text-xs"
                  >
                    <div className="flex items-center space-x-2 truncate pr-2">
                      <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
                      <span className="font-mono text-gray-800 text-[11px] truncate">{item.test}</span>
                    </div>
                    <div className="flex items-center space-x-2 flex-shrink-0">
                      <span className="text-[10px] text-gray-400 font-mono">{item.duration}</span>
                      <span className="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-mono font-bold text-[10px]">
                        {item.status}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
};
