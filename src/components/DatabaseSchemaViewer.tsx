import React, { useState, useEffect } from 'react';
import { Database, Table, Key, Check, Copy, Play, CheckCircle2, ShieldCheck, GitFork, BookOpen, Layers, Terminal } from 'lucide-react';
import { DatabaseTableDefinition, TestSuiteResponse } from '../types';

export const DatabaseSchemaViewer: React.FC = () => {
  const [copied, setCopied] = useState(false);
  const [activeTab, setActiveTab] = useState<'tables' | 'models' | 'relationships' | 'seeders' | 'tests'>('tables');
  const [activeCategory, setActiveCategory] = useState<string>('All');
  const [tables, setTables] = useState<DatabaseTableDefinition[]>([]);
  const [loading, setLoading] = useState(true);
  const [testSuite, setTestSuite] = useState<TestSuiteResponse | null>(null);
  const [runningTests, setRunningTests] = useState(false);

  useEffect(() => {
    fetch('/api/v1/database/tables')
      .then(res => res.json())
      .then(data => {
        if (data.tables) {
          setTables(data.tables);
        }
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, []);

  const runTests = async () => {
    setRunningTests(true);
    try {
      const res = await fetch('/api/v1/database/run-tests', { method: 'POST' });
      const data = await res.json();
      setTestSuite(data);
    } catch (e) {
      console.error(e);
    } finally {
      setRunningTests(false);
    }
  };

  const categories = ['All', 'Authentication & Core', 'User Profiles', 'Catalog & Rates', 'Fleet & Assets', 'KYC & Verification', 'Geography & Dispatch', 'Trips & Bookings', 'Real-time Telemetry', 'Finance & Wallets', 'Feedback & Trust', 'Marketing & Promos', 'System Administration', 'Sanctum Auth'];

  const filteredTables = activeCategory === 'All'
    ? tables
    : tables.filter(t => t.category === activeCategory);

  const copySqlPath = () => {
    navigator.clipboard.writeText('database/production.sql');
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-100">
        <div>
          <div className="flex items-center gap-2">
            <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
              <Database className="w-5 h-5 text-emerald-600" />
              Phase 2 — MySQL 8+ Database Architecture (24 Tables & 22 Models)
            </h2>
            <span className="px-2.5 py-0.5 text-xs font-semibold bg-emerald-100 text-emerald-800 rounded-full">
              InnoDB • utf8mb4_unicode_ci
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Standard Laravel 11 Migrations, Eloquent Models, Relationships, and Seeders with safe production support for cPanel.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={copySqlPath}
            className="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium rounded-lg flex items-center gap-1.5 transition cursor-pointer"
          >
            {copied ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
            {copied ? 'Path Copied' : 'database/production.sql'}
          </button>
        </div>
      </div>

      {/* Navigation Subtabs */}
      <div className="flex items-center gap-2 my-4 border-b border-slate-200 pb-2 text-xs">
        <button
          onClick={() => setActiveTab('tables')}
          className={`px-3 py-1.5 rounded-lg font-medium transition cursor-pointer flex items-center gap-1.5 ${
            activeTab === 'tables' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          }`}
        >
          <Table className="w-3.5 h-3.5" /> 24 Database Tables
        </button>
        <button
          onClick={() => setActiveTab('models')}
          className={`px-3 py-1.5 rounded-lg font-medium transition cursor-pointer flex items-center gap-1.5 ${
            activeTab === 'models' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          }`}
        >
          <Layers className="w-3.5 h-3.5" /> 22 Eloquent Models
        </button>
        <button
          onClick={() => setActiveTab('relationships')}
          className={`px-3 py-1.5 rounded-lg font-medium transition cursor-pointer flex items-center gap-1.5 ${
            activeTab === 'relationships' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          }`}
        >
          <GitFork className="w-3.5 h-3.5" /> Core Relationships
        </button>
        <button
          onClick={() => setActiveTab('seeders')}
          className={`px-3 py-1.5 rounded-lg font-medium transition cursor-pointer flex items-center gap-1.5 ${
            activeTab === 'seeders' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          }`}
        >
          <BookOpen className="w-3.5 h-3.5" /> Seeders & Settings
        </button>
        <button
          onClick={() => setActiveTab('tests')}
          className={`px-3 py-1.5 rounded-lg font-medium transition cursor-pointer flex items-center gap-1.5 ${
            activeTab === 'tests' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          }`}
        >
          <ShieldCheck className="w-3.5 h-3.5" /> PHPUnit Test Suite
        </button>
      </div>

      {/* TAB 1: 24 TABLES */}
      {activeTab === 'tables' && (
        <div>
          {/* Category Filter */}
          <div className="flex items-center gap-2 mb-4 overflow-x-auto pb-1 text-xs">
            {categories.map((cat) => (
              <button
                key={cat}
                onClick={() => setActiveCategory(cat)}
                className={`px-2.5 py-1 rounded-md font-medium transition whitespace-nowrap cursor-pointer ${
                  activeCategory === cat
                    ? 'bg-slate-900 text-white'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                }`}
              >
                {cat} {cat === 'All' ? `(${tables.length})` : `(${tables.filter(t => t.category === cat).length})`}
              </button>
            ))}
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {filteredTables.map((tbl) => (
              <div key={tbl.name} className="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white hover:border-slate-300 transition">
                <div className="flex items-center justify-between pb-2 border-b border-slate-200/80">
                  <div className="flex items-center gap-2 font-bold text-xs text-slate-900">
                    <Table className="w-3.5 h-3.5 text-emerald-600" />
                    <span className="font-mono">{tbl.name}</span>
                    {tbl.soft_delete && (
                      <span className="text-[9px] px-1.5 py-0.5 bg-amber-100 text-amber-800 rounded font-medium">
                        Soft Deletes
                      </span>
                    )}
                  </div>
                  <span className="text-[10px] font-semibold px-2 py-0.5 bg-slate-200 text-slate-700 rounded-full">
                    {tbl.category}
                  </span>
                </div>
                <p className="text-xs text-slate-600 mt-2">{tbl.description}</p>
                <div className="mt-3 flex flex-wrap gap-1">
                  {tbl.indexes.map((idx, i) => (
                    <span key={i} className="text-[10px] px-1.5 py-0.5 bg-white border border-slate-200 text-slate-700 rounded font-mono">
                      {idx}
                    </span>
                  ))}
                  {tbl.foreign_keys.map((fk, i) => (
                    <span key={i} className="text-[10px] px-1.5 py-0.5 bg-blue-50 border border-blue-200 text-blue-700 rounded font-mono">
                      {fk}
                    </span>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* TAB 2: 22 ELOQUENT MODELS */}
      {activeTab === 'models' && (
        <div className="space-y-4">
          <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700">
            <h3 className="font-bold text-slate-900 mb-2 flex items-center gap-1.5">
              <Terminal className="w-4 h-4 text-emerald-600" />
              Eloquent Model Standards & Casts
            </h3>
            <p className="mb-2">
              All 22 Eloquent models are housed in <code className="font-mono bg-white px-1 py-0.5 rounded border">backend/app/Models/</code> with strict type casting (decimals for currency/GPS, integers for trips/capacities, datetimes for timestamps).
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
            {[
              { model: 'User', table: 'users', role: 'Authentication & Sanctum Tokens' },
              { model: 'CustomerProfile', table: 'customer_profiles', role: 'Customer address & history' },
              { model: 'DriverProfile', table: 'driver_profiles', role: 'Driver NID, license & status' },
              { model: 'ServiceCategory', table: 'service_categories', role: '8 Transport service lines' },
              { model: 'VehicleType', table: 'vehicle_types', role: '14 Fare & capacity matrix models' },
              { model: 'Vehicle', table: 'vehicles', role: 'Fleet assets (SoftDeletes)' },
              { model: 'DriverDocument', table: 'driver_documents', role: 'Driver NID/License KYC' },
              { model: 'VehicleDocument', table: 'vehicle_documents', role: 'BRTA Fitness/Tax token KYC' },
              { model: 'Location', table: 'locations', role: 'Districts & Divisions with Lat/Lng' },
              { model: 'Booking', table: 'bookings', role: 'Core ride & freight lifecycle (SoftDeletes)' },
              { model: 'BookingStatusHistory', table: 'booking_status_history', role: 'Immutable state transition audit' },
              { model: 'DriverLocation', table: 'driver_locations', role: 'GPS coordinate telemetry log' },
              { model: 'Payment', table: 'payments', role: 'Cash, bKash, Nagad, Card' },
              { model: 'Wallet', table: 'wallets', role: 'Driver ledger balance' },
              { model: 'WalletTransaction', table: 'wallet_transactions', role: 'Earnings, deductions & withdrawals' },
              { model: 'Rating', table: 'ratings', role: 'Mutual 1-5 star reviews' },
              { model: 'Complaint', table: 'complaints', role: 'Dispute support tickets' },
              { model: 'Notification', table: 'notifications', role: 'Morphic push notification engine' },
              { model: 'PromoCode', table: 'promo_codes', role: 'Discount coupon generator' },
              { model: 'PromoCodeUsage', table: 'promo_code_usages', role: 'Per-user promo usage audit' },
              { model: 'SystemSetting', table: 'system_settings', role: 'Dynamic key-value settings engine' },
              { model: 'AdminLog', table: 'admin_logs', role: 'Audit trail for admin actions' },
            ].map((m) => (
              <div key={m.model} className="p-3 bg-white border border-slate-200 rounded-lg">
                <div className="font-bold text-slate-900 flex items-center justify-between">
                  <span className="font-mono text-emerald-700">{m.model}</span>
                  <span className="text-[10px] text-slate-400 font-mono">→ {m.table}</span>
                </div>
                <p className="text-slate-500 text-[11px] mt-1">{m.role}</p>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* TAB 3: RELATIONSHIPS */}
      {activeTab === 'relationships' && (
        <div className="space-y-4">
          <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-900">
            <h3 className="font-bold mb-1">Normalized Relational Integrity</h3>
            <p>Every relationship uses explicit foreign key constraints with ON DELETE CASCADE or ON DELETE SET NULL to protect data integrity.</p>
          </div>

          <div className="space-y-3">
            {[
              {
                entity: 'User (Auth Root)',
                relations: [
                  'hasOne(CustomerProfile::class)',
                  'hasOne(DriverProfile::class)',
                  'hasOne(Wallet::class, "driver_id")',
                  'hasMany(Booking::class, "customer_id")',
                  'hasMany(Booking::class, "driver_id")',
                  'hasMany(Payment::class, "customer_id")',
                  'hasMany(Rating::class, "driver_id")'
                ]
              },
              {
                entity: 'DriverProfile',
                relations: [
                  'belongsTo(User::class)',
                  'hasMany(Vehicle::class, "driver_id")',
                  'hasOne(Vehicle::class, "driver_id")->where("status", "active")',
                  'hasMany(DriverDocument::class, "driver_id")'
                ]
              },
              {
                entity: 'Booking (Lifecycle Core)',
                relations: [
                  'belongsTo(User::class, "customer_id")',
                  'belongsTo(User::class, "driver_id")',
                  'belongsTo(Vehicle::class)',
                  'belongsTo(ServiceCategory::class)',
                  'belongsTo(VehicleType::class)',
                  'hasMany(BookingStatusHistory::class)',
                  'hasMany(Payment::class)',
                  'hasOne(Payment::class)->latestOfMany()',
                  'hasMany(Rating::class)',
                  'hasMany(Complaint::class)',
                  'hasOne(PromoCodeUsage::class)'
                ]
              },
              {
                entity: 'Wallet & Ledger',
                relations: [
                  'belongsTo(User::class, "driver_id")',
                  'hasMany(WalletTransaction::class, "wallet_id")'
                ]
              }
            ].map((rel, idx) => (
              <div key={idx} className="p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                <h4 className="font-bold text-slate-900 mb-2 flex items-center gap-1.5">
                  <GitFork className="w-3.5 h-3.5 text-emerald-600" />
                  {rel.entity}
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-1.5 font-mono text-[11px]">
                  {rel.relations.map((r, i) => (
                    <div key={i} className="p-1.5 bg-white border border-slate-200 rounded text-slate-700">
                      {r}
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* TAB 4: SEEDERS & SYSTEM SETTINGS */}
      {activeTab === 'seeders' && (
        <div className="space-y-4 text-xs">
          <div className="p-4 bg-slate-900 text-slate-200 rounded-xl font-mono">
            <div className="flex items-center justify-between pb-2 border-b border-slate-800">
              <span className="text-emerald-400 font-bold"># Non-destructive Artisan Seeding Command</span>
            </div>
            <p className="mt-2 text-slate-300">php artisan db:seed --force</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl">
              <h4 className="font-bold text-slate-900 mb-2">Seeded System Settings (No Hardcoding)</h4>
              <ul className="space-y-1.5 text-slate-600">
                <li>• <code className="font-mono text-slate-800">driver_commission_rate</code>: 10.00%</li>
                <li>• <code className="font-mono text-slate-800">ambulance_commission_rate</code>: 0.00% (Social Relief)</li>
                <li>• <code className="font-mono text-slate-800">truck_commission_rate</code>: 8.00%</li>
                <li>• <code className="font-mono text-slate-800">driver_location_poll_seconds</code>: 15 seconds (cPanel Safe)</li>
                <li>• <code className="font-mono text-slate-800">otp_expiry_minutes</code>: 5 minutes</li>
              </ul>
            </div>

            <div className="p-4 bg-slate-50 border border-slate-200 rounded-xl">
              <h4 className="font-bold text-slate-900 mb-2">Demo Users Provisioned</h4>
              <ul className="space-y-1.5 text-slate-600">
                <li>• <strong>Admin</strong>: 01700000000 (admin@tripbd.com) — Password: <code className="font-mono text-emerald-700 bg-emerald-50 px-1 py-0.5 rounded font-semibold">password123</code> (Bcrypt 60-char)</li>
                <li>• <strong>Customer</strong>: 01711111111 (Tanvir Hasan - Banani, Dhaka)</li>
                <li>• <strong>Truck Driver</strong>: 01822222222 (Md. Rafiqul Islam - Tata Ace)</li>
                <li>• <strong>Ambulance Driver</strong>: 01933333333 (Jalal Ahmed - ICU HiAce)</li>
              </ul>
            </div>
          </div>

          {/* Admin Bcrypt SQL Hotfix & Verification Box */}
          <div className="p-5 bg-white border border-emerald-200 rounded-xl shadow-xs space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
              <div>
                <h4 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                  <ShieldCheck className="w-4 h-4 text-emerald-600" />
                  phpMyAdmin Admin Bcrypt Password Patch & Verification
                </h4>
                <p className="text-xs text-slate-500 mt-0.5">
                  Fixes "This password does not use the Bcrypt algorithm" in Laravel by setting a verified 60-character Bcrypt hash for <code>admin@tripbd.com</code>.
                </p>
              </div>
              <span className="text-[11px] font-mono font-semibold px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-md shrink-0 self-start sm:self-auto">
                Algorithm: Bcrypt ($2y$12$)
              </span>
            </div>

            <div className="space-y-3">
              <div>
                <div className="flex items-center justify-between mb-1">
                  <span className="text-xs font-bold text-slate-800">1. phpMyAdmin UPDATE / INSERT Query</span>
                  <button
                    onClick={() => {
                      const sql = `-- Ensure password column is VARCHAR(255)\nALTER TABLE \`users\` MODIFY COLUMN \`password\` VARCHAR(255) NOT NULL;\n\n-- Safely update or insert admin with verified Bcrypt hash\nINSERT INTO \`users\` (\`name\`, \`phone\`, \`email\`, \`password\`, \`role\`, \`status\`, \`phone_verified_at\`, \`created_at\`, \`updated_at\`)\nVALUES ('TripBD System Admin', '01700000000', 'admin@tripbd.com', '$2y$12$GZKWOlA.0hefSC6.fUyc4eiJoe/g/dAnGk935FRNsBv3bgiwH0fGm', 'admin', 'active', NOW(), NOW(), NOW())\nON DUPLICATE KEY UPDATE \`password\` = VALUES(\`password\`), \`role\` = 'admin', \`status\` = 'active', \`name\` = VALUES(\`name\`), \`phone\` = VALUES(\`phone\`), \`updated_at\` = NOW();`;
                      navigator.clipboard.writeText(sql);
                      setCopied(true);
                      setTimeout(() => setCopied(false), 2000);
                    }}
                    className="text-xs text-emerald-700 hover:text-emerald-800 font-semibold flex items-center gap-1 cursor-pointer"
                  >
                    {copied ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                    Copy SQL Statement
                  </button>
                </div>
                <pre className="p-3 bg-slate-900 text-emerald-300 rounded-lg font-mono text-[11px] overflow-x-auto whitespace-pre-wrap leading-relaxed">
{`-- Step 1: Ensure password column is VARCHAR(255)
ALTER TABLE \`users\` MODIFY COLUMN \`password\` VARCHAR(255) NOT NULL;

-- Step 2: Update admin row with verified Bcrypt hash for "password123"
UPDATE \`users\`
SET 
    \`password\` = '$2y$12$GZKWOlA.0hefSC6.fUyc4eiJoe/g/dAnGk935FRNsBv3bgiwH0fGm',
    \`role\` = 'admin',
    \`status\` = 'active',
    \`updated_at\` = NOW()
WHERE \`email\` = 'admin@tripbd.com';`}
                </pre>
              </div>

              <div>
                <div className="flex items-center justify-between mb-1">
                  <span className="text-xs font-bold text-slate-800">2. Verification SQL Query</span>
                  <button
                    onClick={() => {
                      const verifySql = `SELECT \`id\`, \`name\`, \`email\`, \`phone\`, \`role\`, \`status\`, CHAR_LENGTH(\`password\`) AS \`password_length\`, LEFT(\`password\`, 4) AS \`password_prefix\` FROM \`users\` WHERE \`email\` = 'admin@tripbd.com';`;
                      navigator.clipboard.writeText(verifySql);
                      setCopied(true);
                      setTimeout(() => setCopied(false), 2000);
                    }}
                    className="text-xs text-emerald-700 hover:text-emerald-800 font-semibold flex items-center gap-1 cursor-pointer"
                  >
                    <Copy className="w-3.5 h-3.5" />
                    Copy Verification Query
                  </button>
                </div>
                <pre className="p-3 bg-slate-900 text-slate-200 rounded-lg font-mono text-[11px] overflow-x-auto whitespace-pre-wrap leading-relaxed">
{`SELECT 
    \`id\`,
    \`name\`,
    \`email\`,
    \`phone\`,
    \`role\`,
    \`status\`,
    CHAR_LENGTH(\`password\`) AS \`password_length\`,
    LEFT(\`password\`, 4) AS \`password_prefix\`,
    SUBSTRING(\`password\`, 1, 7) AS \`cost_prefix\`
FROM \`users\`
WHERE \`email\` = 'admin@tripbd.com';`}
                </pre>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* TAB 5: AUTOMATED PHPUNIT TEST SUITE */}
      {activeTab === 'tests' && (
        <div className="space-y-4">
          <div className="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-xl">
            <div>
              <h3 className="text-xs font-bold text-slate-900">Automated Database & Model Test Runner</h3>
              <p className="text-xs text-slate-500 mt-0.5">Executes DatabaseSchemaTest and ModelRelationshipTest suites.</p>
            </div>
            <button
              onClick={runTests}
              disabled={runningTests}
              className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg flex items-center gap-2 transition cursor-pointer disabled:opacity-50"
            >
              <Play className="w-3.5 h-3.5 fill-white" />
              {runningTests ? 'Running Suite...' : 'Run PHPUnit Tests'}
            </button>
          </div>

          {testSuite && (
            <div className="space-y-2">
              <div className="flex items-center justify-between p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs">
                <span className="font-bold text-emerald-800 flex items-center gap-1.5">
                  <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                  {testSuite.summary.passed}/{testSuite.summary.total} Tests Passed ({testSuite.summary.total_time})
                </span>
                <span className="font-mono text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded font-bold">
                  {testSuite.summary.status}
                </span>
              </div>

              <div className="space-y-1.5 text-xs font-mono">
                {testSuite.results.map((r, i) => (
                  <div key={i} className="p-2.5 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between">
                    <span className="text-slate-800 flex items-center gap-1.5">
                      <Check className="w-3.5 h-3.5 text-emerald-600" />
                      {r.test}
                    </span>
                    <span className="text-slate-400">{r.duration}</span>
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
