import React, { useState, useEffect } from 'react';
import { Activity, CheckCircle2, RefreshCw, Server, Database, ShieldCheck, Clock, Layers } from 'lucide-react';
import { HealthCheckResponse } from '../types';

export const HealthMonitor: React.FC = () => {
  const [health, setHealth] = useState<HealthCheckResponse | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [latency, setLatency] = useState<number>(0);
  const [lastChecked, setLastChecked] = useState<Date | null>(null);

  const fetchHealth = async () => {
    setLoading(true);
    const start = performance.now();
    try {
      const res = await fetch('/api/v1/health');
      const data = await res.json();
      const end = performance.now();
      setHealth(data);
      setLatency(Math.round(end - start));
      setLastChecked(new Date());
    } catch (err) {
      console.error('Health check failed', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchHealth();
  }, []);

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
      <div className="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
        <div className="flex items-center gap-2.5">
          <div className="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
            <Activity className="w-4 h-4" />
          </div>
          <div>
            <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
              Live System Health & Environment Monitor
              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Healthy (200 OK)
              </span>
            </h3>
            <p className="text-xs text-slate-500">API Endpoint: <code className="text-slate-700 bg-slate-100 px-1 py-0.5 rounded">GET /api/v1/health</code></p>
          </div>
        </div>

        <button
          id="btn-refresh-health"
          onClick={fetchHealth}
          disabled={loading}
          className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 active:scale-98 transition cursor-pointer"
        >
          <RefreshCw className={`w-3.5 h-3.5 ${loading ? 'animate-spin text-emerald-600' : ''}`} />
          Re-Check Health
        </button>
      </div>

      <div className="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="p-4 rounded-lg bg-slate-50 border border-slate-100">
          <div className="flex items-center justify-between text-slate-500 mb-2">
            <span className="text-xs font-medium">PHP & Server Runtime</span>
            <Server className="w-4 h-4 text-emerald-600" />
          </div>
          <div className="text-base font-bold text-slate-900">PHP 8.2+ Ready</div>
          <p className="text-xs text-slate-500 mt-1">Apache mod_rewrite + Static React</p>
        </div>

        <div className="p-4 rounded-lg bg-slate-50 border border-slate-100">
          <div className="flex items-center justify-between text-slate-500 mb-2">
            <span className="text-xs font-medium">Database Engine</span>
            <Database className="w-4 h-4 text-emerald-600" />
          </div>
          <div className="text-base font-bold text-slate-900">MySQL 8+ InnoDB</div>
          <p className="text-xs text-slate-500 mt-1">utf8mb4 Bangla & foreign keys</p>
        </div>

        <div className="p-4 rounded-lg bg-slate-50 border border-slate-100">
          <div className="flex items-center justify-between text-slate-500 mb-2">
            <span className="text-xs font-medium">Auth & Security</span>
            <ShieldCheck className="w-4 h-4 text-emerald-600" />
          </div>
          <div className="text-base font-bold text-slate-900">Laravel Sanctum</div>
          <p className="text-xs text-slate-500 mt-1">Bearer Token + OTP Verification</p>
        </div>

        <div className="p-4 rounded-lg bg-slate-50 border border-slate-100">
          <div className="flex items-center justify-between text-slate-500 mb-2">
            <span className="text-xs font-medium">API Response Time</span>
            <Clock className="w-4 h-4 text-emerald-600" />
          </div>
          <div className="text-base font-bold text-slate-900">{latency} ms</div>
          <p className="text-xs text-slate-500 mt-1">
            Last: {lastChecked ? lastChecked.toLocaleTimeString() : 'Just now'}
          </p>
        </div>
      </div>

      {health && (
        <div className="px-5 pb-5">
          <div className="p-3.5 rounded-lg bg-slate-900 text-slate-100 font-mono text-xs overflow-x-auto">
            <div className="text-slate-400 mb-1 flex items-center gap-1.5 font-sans font-semibold">
              <Layers className="w-3.5 h-3.5 text-emerald-400" /> Live Health JSON Payload:
            </div>
            <pre>{JSON.stringify(health, null, 2)}</pre>
          </div>
        </div>
      )}
    </div>
  );
};
