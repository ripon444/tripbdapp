import React from 'react';
import { ShieldCheck, Truck, Sparkles, Server, Terminal, Smartphone, UserCheck, Key, Navigation, Layers } from 'lucide-react';

interface HeaderProps {
  activeTab: string;
  setActiveTab: (tab: string) => void;
  currentUser?: any;
}

export const Header: React.FC<HeaderProps> = ({ activeTab, setActiveTab, currentUser }) => {
  const tabs = [
    { id: 'booking', label: 'Book a Trip', icon: Navigation, badge: 'Phase 4' },
    { id: 'driver', label: 'Driver Terminal', icon: Truck, badge: 'Dispatch' },
    { id: 'auth', label: 'Auth & KYC', icon: UserCheck, badge: 'Phase 3' },
    { id: 'overview', label: 'Overview', icon: Sparkles },
    { id: 'database', label: 'Database & Schema', icon: Terminal, badge: 'Phase 2' },
    { id: 'services', label: 'Transport Services', icon: Layers },
    { id: 'cpanel', label: 'cPanel Deployment', icon: Server },
    { id: 'api', label: 'API Sandbox', icon: ShieldCheck },
    { id: 'roadmap', label: '14-Phase Roadmap', icon: Smartphone }
  ];

  return (
    <header className="bg-slate-900 border-b border-slate-800 text-white sticky top-0 z-40">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-3">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-bold shadow-inner">
              <Truck className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-lg font-bold tracking-tight text-white flex items-center gap-2">
                  TripBD
                  <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-medium">
                    Phase 3 Verified • cPanel Ready
                  </span>
                </h1>
              </div>
              <p className="text-[11px] text-slate-300 font-medium">
                “খালি গাড়ি নয়, রিটার্নে যাত্রী নিন” — <span className="text-slate-400">ঢাকা ↔ ৬৪ জেলা</span>
              </p>
            </div>
          </div>

          <div className="flex items-center gap-1.5 bg-slate-800/90 p-1 rounded-xl border border-slate-700/60 overflow-x-auto text-xs">
            {tabs.map((tab) => {
              const Icon = tab.icon;
              const isActive = activeTab === tab.id;
              return (
                <button
                  key={tab.id}
                  id={`nav-tab-${tab.id}`}
                  onClick={() => setActiveTab(tab.id)}
                  className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-medium transition-all whitespace-nowrap cursor-pointer ${
                    isActive
                      ? 'bg-emerald-600 text-white shadow-xs'
                      : 'text-slate-400 hover:text-slate-200 hover:bg-slate-700/50'
                  }`}
                >
                  <Icon className="w-3.5 h-3.5" />
                  <span>{tab.label}</span>
                  {tab.badge && (
                    <span className={`text-[9px] px-1.5 py-0.2 rounded font-mono font-bold ${
                      isActive ? 'bg-emerald-800 text-emerald-100' : 'bg-slate-700 text-slate-300'
                    }`}>
                      {tab.badge}
                    </span>
                  )}
                </button>
              );
            })}
          </div>
        </div>
      </div>
    </header>
  );
};
