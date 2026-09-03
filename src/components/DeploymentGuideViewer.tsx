import React, { useState } from 'react';
import { BookOpen, CheckSquare, Terminal, FolderUp, Database, Shield, RefreshCw } from 'lucide-react';

export const DeploymentGuideViewer: React.FC = () => {
  const [activeMethod, setActiveMethod] = useState<'no-ssh' | 'terminal'>('no-ssh');

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-100">
        <div>
          <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
            <BookOpen className="w-5 h-5 text-emerald-600" />
            cPanel Production Deployment Manual (Phase 1 Ready)
          </h2>
          <p className="text-xs text-slate-500 mt-1">
            Choose your preferred deployment method. Both methods guarantee 100% functionality on shared cPanel hosting.
          </p>
        </div>

        <div className="flex items-center gap-1 bg-slate-100 p-1 rounded-lg text-xs font-semibold">
          <button
            onClick={() => setActiveMethod('no-ssh')}
            className={`px-3 py-1.5 rounded-md transition cursor-pointer ${
              activeMethod === 'no-ssh'
                ? 'bg-white text-emerald-700 shadow-xs'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            Method B: File Manager (NO SSH)
          </button>
          <button
            onClick={() => setActiveMethod('terminal')}
            className={`px-3 py-1.5 rounded-md transition cursor-pointer ${
              activeMethod === 'terminal'
                ? 'bg-white text-emerald-700 shadow-xs'
                : 'text-slate-600 hover:text-slate-900'
            }`}
          >
            Method A: Terminal / SSH
          </button>
        </div>
      </div>

      {activeMethod === 'no-ssh' ? (
        <div className="mt-5 space-y-4">
          <div className="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs">
            <h4 className="font-bold flex items-center gap-1.5 mb-1 text-emerald-800">
              <FolderUp className="w-4 h-4" /> Recommended for Standard cPanel Hosting (No Terminal Access)
            </h4>
            This method relies strictly on cPanel web tools (File Manager, phpMyAdmin, MySQL Databases, and Cron Jobs).
          </div>

          <div className="space-y-3">
            {[
              {
                step: '1',
                title: 'Create MySQL Database in cPanel',
                desc: 'Go to MySQL Databases > Create Database (e.g., user_tripbd), create a user with a secure password, and assign ALL PRIVILEGES.'
              },
              {
                step: '2',
                title: 'Import Database Schema via phpMyAdmin',
                desc: 'Open phpMyAdmin, click on your database, navigate to the Import tab, and upload `database/production.sql`.'
              },
              {
                step: '3',
                title: 'Upload Backend Files to /home/USERNAME/tripbd/',
                desc: 'In cPanel File Manager, upload and extract the backend files to `/home/username/tripbd_backend/` (isolated from public web).'
              },
              {
                step: '4',
                title: 'Upload Compiled React Frontend to public_html/',
                desc: 'Upload the compiled static files from `dist/` into `public_html/` alongside `.htaccess`.'
              },
              {
                step: '5',
                title: 'Configure .env Settings',
                desc: 'Edit `.env` in `tripbd_backend/` with your MySQL credentials, APP_URL, and Sanctum domain.'
              },
              {
                step: '6',
                title: 'Setup cPanel 1-Minute Cron Job',
                desc: 'Add Cron Job in cPanel: `* * * * * /usr/local/bin/php /home/USERNAME/tripbd_backend/artisan schedule:run >> /dev/null 2>&1`.'
              }
            ].map((st) => (
              <div key={st.step} className="p-3.5 rounded-lg border border-slate-200 bg-slate-50 flex items-start gap-3">
                <div className="w-6 h-6 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                  {st.step}
                </div>
                <div>
                  <div className="text-xs font-bold text-slate-900">{st.title}</div>
                  <p className="text-xs text-slate-600 mt-0.5">{st.desc}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      ) : (
        <div className="mt-5 space-y-4">
          <div className="p-4 rounded-xl bg-slate-900 text-white text-xs font-mono border border-slate-800">
            <h4 className="font-bold flex items-center gap-1.5 mb-2 text-emerald-400 font-sans">
              <Terminal className="w-4 h-4" /> Terminal / SSH Commands
            </h4>
            <div className="space-y-2 text-[11px] text-slate-300">
              <p><span className="text-emerald-400 font-bold"># 1. Clone & Enter Directory</span><br />cd /home/USERNAME/tripbd_backend</p>
              <p><span className="text-emerald-400 font-bold"># 2. Install Production Composer Dependencies</span><br />composer install --no-dev --optimize-autoloader</p>
              <p><span className="text-emerald-400 font-bold"># 3. Setup Environment & App Key</span><br />cp .env.example .env && php artisan key:generate</p>
              <p><span className="text-emerald-400 font-bold"># 4. Migrate & Seed Database</span><br />php artisan migrate --force --seed</p>
              <p><span className="text-emerald-400 font-bold"># 5. Optimize Caches</span><br />php artisan config:cache && php artisan route:cache</p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
