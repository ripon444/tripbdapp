import React from 'react';
import { ShieldCheck, Check } from 'lucide-react';

export const PhaseRoadmap: React.FC = () => {
  const phases = [
    { num: 1, name: 'Phase 1: Setup & cPanel Foundation', status: 'completed', desc: 'Laravel, React, Vite, MySQL 8+, Sanctum, .env, Basic API, Health Check, cPanel Compatibility Check' },
    { num: 2, name: 'Phase 2: Database, Migrations, Models & Core Relationships', status: 'completed', desc: '24 Laravel Migrations, 22 Eloquent Models, Relationships, Foreign Keys, Indexes, Seeders, Factories, Tests & production.sql' },
    { num: 3, name: 'Phase 3: Authentication, OTP, Roles & User Management', status: 'completed', desc: 'Customer, Driver, Admin, OTP Verification with Bcrypt hashing, Driver KYC Uploads, Role Middleware, Sanctum Tokens, 22 Automated Tests' },
    { num: 4, name: 'Phase 4: Customer Web Application & Booking Flow', status: 'next', desc: 'Customer Portal, Booking Creation, Inter-District Route Selection, Ride Tracking, History & Support' },
    { num: 5, name: 'Phase 5: Driver Web Application', status: 'pending', desc: 'Driver Portal, Document Upload, Online/Offline Toggle, Booking Requests & Bid Acceptance' },
    { num: 6, name: 'Phase 6: Core Booking Engine & State Machine', status: 'pending', desc: 'Fare Calculation Service, State Management, Validation Engine' },
    { num: 7, name: 'Phase 7: Return Trip Matching Engine', status: 'pending', desc: 'Dhaka ↔ 64 Districts empty return vehicle discount matching algorithm' },
    { num: 8, name: 'Phase 8: Google Maps & Live GPS Polling', status: 'pending', desc: 'Location search, route polyline, distance/ETA, cPanel GPS heartbeat fallback' },
    { num: 9, name: 'Phase 9: Payment & Driver Wallet', status: 'pending', desc: 'Cash, bKash, Nagad, SSLCOMMERZ integration & Driver ledger' },
    { num: 10, name: 'Phase 10: Admin Management Panel', status: 'pending', desc: 'Fleet, Users, Trips, Commissions, Disputes, Fare Settings' },
    { num: 11, name: 'Phase 11: Notification Architecture', status: 'pending', desc: 'SMS OTP gateway, Email receipts & In-app alerts' },
    { num: 12, name: 'Phase 12: Security Hardening & Testing', status: 'pending', desc: 'Rate limiting, CSRF/XSS protection, Input sanitization' },
    { num: 13, name: 'Phase 13: cPanel Production Deployment Package', status: 'pending', desc: 'Complete zipped release package, phpMyAdmin SQL & .htaccess' },
    { num: 14, name: 'Phase 14: React Native Android App Preparation', status: 'pending', desc: 'Mobile API client, background GPS tracking & push tokens' }
  ];

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-100">
        <div>
          <div className="flex items-center gap-2">
            <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
              <ShieldCheck className="w-5 h-5 text-emerald-600" />
              TripBD 14-Phase Production Roadmap
            </h2>
            <span className="px-2.5 py-0.5 text-xs font-bold bg-emerald-100 text-emerald-800 rounded-full">
              Phase 1, 2 & 3 Completed & Verified
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Strict sequential implementation ensuring each layer is verified before advancing to the next phase.
          </p>
        </div>
      </div>

      <div className="space-y-3 mt-5">
        {phases.map((phase) => {
          const isDone = phase.status === 'completed';
          const isNext = phase.status === 'next';

          return (
            <div
              key={phase.num}
              className={`p-4 rounded-xl border transition flex items-start gap-3.5 ${
                isDone
                  ? 'bg-emerald-50/50 border-emerald-300 ring-1 ring-emerald-400/30'
                  : isNext
                  ? 'bg-blue-50/40 border-blue-300 ring-1 ring-blue-400/30'
                  : 'bg-slate-50 border-slate-200 opacity-75'
              }`}
            >
              <div className="mt-0.5">
                {isDone ? (
                  <div className="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                    <Check className="w-3.5 h-3.5" />
                  </div>
                ) : isNext ? (
                  <div className="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs animate-pulse">
                    {phase.num}
                  </div>
                ) : (
                  <div className="w-6 h-6 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs">
                    {phase.num}
                  </div>
                )}
              </div>

              <div className="flex-1">
                <div className="flex items-center justify-between">
                  <h4 className={`text-xs font-bold ${isDone ? 'text-emerald-950' : isNext ? 'text-blue-950' : 'text-slate-700'}`}>
                    {phase.name}
                  </h4>
                  <span
                    className={`text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full ${
                      isDone
                        ? 'bg-emerald-200/70 text-emerald-800'
                        : isNext
                        ? 'bg-blue-200/70 text-blue-800'
                        : 'bg-slate-200 text-slate-600'
                    }`}
                  >
                    {isDone ? `Phase ${phase.num} Complete` : isNext ? `Next Up: Phase ${phase.num}` : 'Pending'}
                  </span>
                </div>
                <p className="text-xs text-slate-600 mt-1">{phase.desc}</p>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};
