import React, { useState, useEffect } from 'react';
import { Truck, Siren, Car, Compass, Repeat, ChevronRight, Info, ShieldCheck, Zap } from 'lucide-react';
import { ServiceCategory, VehicleType } from '../types';

export const ServiceCatalogPreview: React.FC = () => {
  const [categories, setCategories] = useState<ServiceCategory[]>([]);
  const [vehicleTypes, setVehicleTypes] = useState<VehicleType[]>([]);
  const [selectedSlug, setSelectedSlug] = useState<string>('return-trip');
  const [loading, setLoading] = useState<boolean>(true);

  useEffect(() => {
    const loadData = async () => {
      try {
        const [catRes, vehRes] = await Promise.all([
          fetch('/api/v1/services'),
          fetch('/api/v1/vehicle-types')
        ]);
        const catData = await catRes.json();
        const vehData = await vehRes.json();
        if (catData.success) setCategories(catData.data);
        if (vehData.success) setVehicleTypes(vehData.data);
      } catch (e) {
        console.error(e);
      } finally {
        setLoading(false);
      }
    };
    loadData();
  }, []);

  const getIcon = (slug: string) => {
    switch (slug) {
      case 'truck': return <Truck className="w-5 h-5" />;
      case 'ambulance': return <Siren className="w-5 h-5" />;
      case 'private-car': return <Car className="w-5 h-5" />;
      case 'taxi': return <Compass className="w-5 h-5" />;
      case 'return-trip': return <Repeat className="w-5 h-5" />;
      default: return <Car className="w-5 h-5" />;
    }
  };

  const filteredVehicles = vehicleTypes.filter(v => selectedSlug === 'all' || v.category_slug === selectedSlug);

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-xs">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-100">
        <div>
          <div className="flex items-center gap-2">
            <h2 className="text-base font-bold text-slate-900">TripBD Transport Categories (Phase 1 Baseline)</h2>
            <span className="px-2 py-0.5 text-[11px] font-semibold bg-emerald-100 text-emerald-800 rounded-full">
              5 Core Categories Seeded
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Dynamic service categories & vehicle types configured in database migrations and REST API endpoints.
          </p>
        </div>

        <div className="text-xs text-slate-500 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
          Main Tagline: <strong className="text-slate-800 font-semibold">“খালি গাড়ি নয়, রিটার্নে যাত্রী নিন”</strong>
        </div>
      </div>

      {/* Category Pills */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 my-5">
        {categories.map((cat) => {
          const isSelected = selectedSlug === cat.slug;
          return (
            <button
              key={cat.slug}
              id={`cat-btn-${cat.slug}`}
              onClick={() => setSelectedSlug(cat.slug)}
              className={`p-3.5 rounded-xl border text-left transition-all cursor-pointer ${
                isSelected
                  ? 'border-emerald-600 bg-emerald-50/50 shadow-xs ring-1 ring-emerald-500'
                  : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/50'
              }`}
            >
              <div className={`w-8 h-8 rounded-lg flex items-center justify-center mb-2.5 ${
                isSelected ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600'
              }`}>
                {getIcon(cat.slug)}
              </div>
              <div className="text-xs font-bold text-slate-900 leading-tight">{cat.name_en}</div>
              <div className="text-[11px] text-emerald-700 font-medium mt-0.5">{cat.name_bn}</div>
              <div className="text-[10px] text-slate-500 mt-1 line-clamp-1">{cat.tagline}</div>
            </button>
          );
        })}
      </div>

      {/* Active Category Details */}
      {selectedSlug === 'return-trip' && (
        <div className="mb-5 p-4 rounded-xl bg-gradient-to-r from-emerald-900 to-slate-900 text-white border border-emerald-800">
          <div className="flex items-start justify-between">
            <div>
              <div className="flex items-center gap-2">
                <span className="px-2 py-0.5 rounded-md bg-emerald-500 text-slate-950 text-[10px] font-extrabold uppercase">
                  Flagship Feature
                </span>
                <h3 className="text-sm font-bold text-white">Return Trip Matching Engine</h3>
              </div>
              <p className="text-xs text-emerald-200 mt-1 max-w-2xl">
                Solves empty return vehicles across Bangladesh (Dhaka ↔ Rajshahi, Dhaka ↔ Chittagong, Sylhet, Khulna, etc.). Matches driver return routes with customer bookings for up to 40% cheaper fares.
              </p>
            </div>
            <div className="hidden sm:block text-right">
              <span className="text-xl font-bold text-emerald-400">Up to 40% Off</span>
              <p className="text-[10px] text-slate-300">Driver earns on return leg</p>
            </div>
          </div>
        </div>
      )}

      {/* Vehicle Type Table */}
      <div className="border border-slate-200 rounded-xl overflow-hidden">
        <div className="bg-slate-50 px-4 py-2.5 border-b border-slate-200 flex items-center justify-between text-xs font-semibold text-slate-700">
          <span>Configured Vehicle Types for <strong className="text-emerald-700">{selectedSlug.toUpperCase()}</strong></span>
          <span>REST API: <code className="bg-white px-1.5 py-0.5 rounded border border-slate-200 text-slate-800">GET /api/v1/vehicle-types?category={selectedSlug}</code></span>
        </div>
        <div className="divide-y divide-slate-100 text-xs">
          {filteredVehicles.length > 0 ? (
            filteredVehicles.map((veh) => (
              <div key={veh.id} className="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-slate-50/60 transition">
                <div>
                  <div className="font-bold text-slate-900 flex items-center gap-2">
                    {veh.name_en}
                    <span className="font-normal text-slate-500 text-[11px]">({veh.name_bn})</span>
                  </div>
                  <div className="text-[11px] text-slate-500 mt-0.5 flex items-center gap-3">
                    <span>Base Fare: ৳{veh.base_fare}</span>
                    <span>•</span>
                    <span>Per KM: ৳{veh.per_km}</span>
                    <span>•</span>
                    <span>Min Fare: ৳{veh.min_fare}</span>
                    {veh.discount_percent && (
                      <span className="text-emerald-600 font-semibold">• Return Discount: {veh.discount_percent}%</span>
                    )}
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <span className="text-[11px] px-2 py-0.5 bg-slate-100 text-slate-700 rounded font-mono font-medium">
                    InnoDB ID #{veh.id}
                  </span>
                </div>
              </div>
            ))
          ) : (
            <div className="p-6 text-center text-xs text-slate-500">
              Loading vehicle catalog for {selectedSlug}...
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
