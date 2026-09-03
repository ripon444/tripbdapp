import React, { useState } from 'react';
import { Play, CheckCircle2, XCircle, Clock, Send, ShieldCheck, Code } from 'lucide-react';

export const ApiTester: React.FC = () => {
  const [selectedEndpoint, setSelectedEndpoint] = useState<string>('GET /api/v1/health');
  const [phone, setPhone] = useState<string>('01711000000');
  const [otp, setOtp] = useState<string>('1234');
  const [email, setEmail] = useState<string>('capitalaurex444@gmail.com');
  const [response, setResponse] = useState<any>(null);
  const [status, setStatus] = useState<number | null>(null);
  const [loading, setLoading] = useState<boolean>(false);
  const [time, setTime] = useState<number | null>(null);

  const endpoints = [
    { method: 'GET', path: '/api/v1/health', name: 'Health Check & Environment' },
    { method: 'GET', path: '/api/v1/services', name: 'Get Transport Categories' },
    { method: 'GET', path: '/api/v1/vehicle-types', name: 'Get Vehicle Types' },
    { method: 'GET', path: '/api/v1/cpanel-check', name: 'cPanel Hosting Audit' },
    { method: 'GET', path: '/api/v1/admin/mail/settings', name: 'SMTP Mail Configuration' },
    { method: 'POST', path: '/api/v1/admin/mail/test', name: 'Send SMTP Test Email' },
    { method: 'POST', path: '/api/v1/auth/send-otp', name: 'Send OTP (Sanctum/Auth)' },
    { method: 'POST', path: '/api/v1/auth/verify-otp', name: 'Verify OTP & Issue Token' }
  ];

  const handleTest = async () => {
    setLoading(true);
    setResponse(null);
    setStatus(null);
    const start = performance.now();

    try {
      const [method, path] = selectedEndpoint.split(' ');
      let res;
      if (method === 'GET') {
        res = await fetch(path);
      } else if (path === '/api/v1/auth/send-otp') {
        res = await fetch(path, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ phone })
        });
      } else if (path === '/api/v1/auth/verify-otp') {
        res = await fetch(path, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ phone, otp_code: otp })
        });
      } else if (path === '/api/v1/admin/mail/test') {
        res = await fetch(path, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email })
        });
      }

      if (res) {
        setStatus(res.status);
        const data = await res.json();
        setResponse(data);
      }
    } catch (err: any) {
      setStatus(500);
      setResponse({ error: err.message || 'Request failed' });
    } finally {
      const end = performance.now();
      setTime(Math.round(end - start));
      setLoading(false);
    }
  };

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-100">
        <div>
          <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
            <Code className="w-5 h-5 text-emerald-600" />
            Phase 1 REST API Live Sandbox & Verification
          </h2>
          <p className="text-xs text-slate-500 mt-1">
            Execute real HTTP requests against the Phase 1 REST endpoints to test JSON formats, status codes, and Sanctum tokens.
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-5">
        {/* Left Column: Select Endpoint */}
        <div className="space-y-2">
          <label className="text-xs font-bold text-slate-700 uppercase tracking-wider block">
            Select API Endpoint
          </label>
          <div className="space-y-1.5">
            {endpoints.map((ep) => {
              const fullKey = `${ep.method} ${ep.path}`;
              const isSelected = selectedEndpoint === fullKey;
              return (
                <button
                  key={fullKey}
                  onClick={() => setSelectedEndpoint(fullKey)}
                  className={`w-full text-left p-2.5 rounded-lg text-xs font-mono transition-all flex items-center justify-between cursor-pointer ${
                    isSelected
                      ? 'bg-slate-900 text-white shadow-xs'
                      : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200'
                  }`}
                >
                  <div className="flex items-center gap-2">
                    <span className={`px-1.5 py-0.5 rounded text-[10px] font-bold ${
                      ep.method === 'GET' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-blue-500/20 text-blue-400'
                    }`}>
                      {ep.method}
                    </span>
                    <span className="truncate">{ep.path}</span>
                  </div>
                </button>
              );
            })}
          </div>

          {/* Body Params for POST */}
          {selectedEndpoint.startsWith('POST') && (
            <div className="mt-4 p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-3">
              <span className="text-[11px] font-bold text-slate-700 block">POST Request Body</span>
              
              {selectedEndpoint.includes('mail/test') ? (
                <div>
                  <label className="text-[11px] text-slate-600 block mb-1">Recipient Email Address:</label>
                  <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    className="w-full text-xs font-mono p-2 border border-slate-300 rounded bg-white"
                    placeholder="capitalaurex444@gmail.com"
                  />
                  <span className="text-[10px] text-slate-400 mt-1 block">
                    Sent from <strong>support@pixelneuron.net</strong> via <strong>smtp-prod.mailrcld.com:587</strong> (STARTTLS)
                  </span>
                </div>
              ) : (
                <>
                  <div>
                    <label className="text-[11px] text-slate-600 block mb-1">Bangladeshi Phone (013-019):</label>
                    <input
                      type="text"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      className="w-full text-xs font-mono p-2 border border-slate-300 rounded bg-white"
                      placeholder="01711000000"
                    />
                  </div>
                  {selectedEndpoint.includes('verify-otp') && (
                    <div>
                      <label className="text-[11px] text-slate-600 block mb-1">OTP Code (Test: 1234):</label>
                      <input
                        type="text"
                        value={otp}
                        onChange={(e) => setOtp(e.target.value)}
                        className="w-full text-xs font-mono p-2 border border-slate-300 rounded bg-white"
                        placeholder="1234"
                      />
                    </div>
                  )}
                </>
              )}
            </div>
          )}

          <button
            id="btn-execute-api"
            onClick={handleTest}
            disabled={loading}
            className="w-full mt-3 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg flex items-center justify-center gap-2 shadow-xs transition active:scale-98 cursor-pointer"
          >
            <Play className={`w-3.5 h-3.5 ${loading ? 'animate-spin' : ''}`} />
            {loading ? 'Executing...' : 'Execute Request'}
          </button>
        </div>

        {/* Right 2 Columns: Live Output */}
        <div className="lg:col-span-2">
          <div className="bg-slate-950 rounded-xl border border-slate-800 text-slate-100 p-4 h-full flex flex-col font-mono text-xs">
            <div className="flex items-center justify-between pb-3 border-b border-slate-800 text-slate-400 font-sans">
              <span className="flex items-center gap-2">
                <span className="font-semibold text-slate-200">{selectedEndpoint}</span>
              </span>
              <div className="flex items-center gap-3 text-xs">
                {status && (
                  <span className={`px-2 py-0.5 rounded text-[11px] font-bold ${
                    status === 200 ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-red-500/20 text-red-400'
                  }`}>
                    HTTP {status}
                  </span>
                )}
                {time !== null && (
                  <span className="text-slate-400 flex items-center gap-1">
                    <Clock className="w-3 h-3 text-slate-500" /> {time}ms
                  </span>
                )}
              </div>
            </div>

            <div className="flex-1 overflow-auto py-3">
              {loading ? (
                <div className="h-40 flex items-center justify-center text-slate-500">
                  <div className="flex items-center gap-2">
                    <div className="w-2 h-2 rounded-full bg-emerald-400 animate-bounce"></div>
                    <div className="w-2 h-2 rounded-full bg-emerald-400 animate-bounce [animation-delay:0.2s]"></div>
                    <div className="w-2 h-2 rounded-full bg-emerald-400 animate-bounce [animation-delay:0.4s]"></div>
                    <span>Processing request...</span>
                  </div>
                </div>
              ) : response ? (
                <pre className="text-emerald-300 font-mono text-[11px] leading-relaxed">
                  {JSON.stringify(response, null, 2)}
                </pre>
              ) : (
                <div className="h-40 flex flex-col items-center justify-center text-slate-500 text-center font-sans">
                  <Send className="w-6 h-6 text-slate-600 mb-2 opacity-60" />
                  <p className="text-xs">Select an endpoint and click "Execute Request" to inspect the JSON response.</p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
