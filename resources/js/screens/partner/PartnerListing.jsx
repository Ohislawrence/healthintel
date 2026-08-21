import React, { useEffect, useState } from 'react';
import usePartnerAuthStore from '../../stores/partnerAuthStore';

export default function PartnerListing() {
  const { apiGet, apiPut, provider } = usePartnerAuthStore();
  const [form, setForm] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  useEffect(() => {
    loadListing();
  }, []);

  const loadListing = async () => {
    try {
      setLoading(true);
      const data = await apiGet('/listing');
      const p = data.data?.provider || data.provider || {};
      setForm({
        name: p.name || '',
        phone: p.phone || '',
        email: p.email || '',
        address: p.address || '',
        city: p.city || '',
        state: p.state || '',
        website: p.website || '',
        bio: p.bio || '',
        specialty: p.specialty || '',
        insurance_plans: p.insurance_plans || [],
        logo_url: p.logo_url || '',
        banner_url: p.banner_url || '',
        latitude: p.latitude ?? '',
        longitude: p.longitude ?? '',
        locations: Array.isArray(p.locations)
          ? p.locations.map((l) => ({
              name: l.name || '',
              address: l.address || '',
              city: l.city || '',
              state: l.state || '',
              country: l.country || 'Nigeria',
              phone: l.phone || '',
              latitude: l.latitude ?? '',
              longitude: l.longitude ?? '',
              is_primary: !!l.is_primary,
            }))
          : [],
      });
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const setField = (key, value) => setForm((f) => ({ ...f, [key]: value }));

  const setLocation = (index, key, value) =>
    setForm((f) => ({
      ...f,
      locations: f.locations.map((loc, i) => (i === index ? { ...loc, [key]: value } : loc)),
    }));

  const addLocation = () =>
    setForm((f) => ({
      ...f,
      locations: [
        ...f.locations,
        { name: '', address: '', city: '', state: '', country: 'Nigeria', phone: '', latitude: '', longitude: '', is_primary: false },
      ],
    }));

  const removeLocation = (index) =>
    setForm((f) => ({ ...f, locations: f.locations.filter((_, i) => i !== index) }));

  const setPrimaryLocation = (index) =>
    setForm((f) => ({
      ...f,
      locations: f.locations.map((loc, i) => ({ ...loc, is_primary: i === index })),
    }));

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError(null);
    setSuccess(null);
    try {
      await apiPut('/listing', form);
      setSuccess('Your listing has been updated.');
      setTimeout(() => setSuccess(null), 4000);
    } catch (err) {
      setError(err.message);
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-20">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900">Manage Listing</h2>
        <p className="text-sm text-gray-500 mt-1">
          Update how {provider?.name} appears in the HealthIntel provider directory.
        </p>
      </div>

      {success && (
        <div className="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm">{success}</div>
      )}
      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">{error}</div>
      )}

      <form onSubmit={handleSave} className="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
        <h3 className="text-sm font-semibold text-gray-700">Basic Information</h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <Field label="Facility Name" value={form.name} onChange={(v) => setField('name', v)} />
          <Field label="Specialty" value={form.specialty} onChange={(v) => setField('specialty', v)} />
          <Field label="Phone" value={form.phone} onChange={(v) => setField('phone', v)} />
          <Field label="Email" value={form.email} onChange={(v) => setField('email', v)} type="email" />
          <Field label="Website" value={form.website} onChange={(v) => setField('website', v)} />
          <Field label="State" value={form.state} onChange={(v) => setField('state', v)} />
          <Field label="City" value={form.city} onChange={(v) => setField('city', v)} />
          <div className="sm:col-span-2">
            <Field label="Address" value={form.address} onChange={(v) => setField('address', v)} />
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Bio / Description</label>
          <textarea
            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none"
            rows={3}
            value={form.bio}
            onChange={(e) => setField('bio', e.target.value)}
            placeholder="Short description about your facility…"
          />
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Logo URL</label>
            <div className="flex items-center gap-3">
              {form.logo_url && (
                <img src={form.logo_url} alt="Logo" className="h-10 w-10 rounded object-contain border border-gray-200 bg-white p-0.5" onError={(e) => { e.currentTarget.style.display = 'none'; }} />
              )}
              <input
                className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none"
                value={form.logo_url}
                onChange={(e) => setField('logo_url', e.target.value)}
                placeholder="https://…/logo.png"
              />
            </div>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Banner URL</label>
            <div className="flex items-center gap-3">
              {form.banner_url && (
                <img src={form.banner_url} alt="Banner" className="h-8 w-auto rounded object-cover border border-gray-200 bg-white p-0.5" onError={(e) => { e.currentTarget.style.display = 'none'; }} />
              )}
              <input
                className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 outline-none"
                value={form.banner_url}
                onChange={(e) => setField('banner_url', e.target.value)}
                placeholder="https://…/banner.png"
              />
            </div>
          </div>
        </div>

        {/* Locations */}
        <div className="border-t border-gray-200 pt-5">
          <div className="flex items-center justify-between mb-3">
            <h3 className="text-sm font-semibold text-gray-700">Branches / Locations ({form.locations.length})</h3>
            <button
              type="button"
              onClick={addLocation}
              className="text-sm font-semibold text-teal-700 bg-teal-50 border border-teal-200 rounded-lg px-3 py-1.5 hover:bg-teal-100"
            >
              + Add location
            </button>
          </div>
          {form.locations.length === 0 ? (
            <p className="text-sm text-gray-400">No locations yet. Add branches for multi-location businesses.</p>
          ) : (
            <div className="space-y-4">
              {form.locations.map((loc, i) => (
                <div key={i} className="rounded-lg border border-gray-200 bg-gray-50 p-4">
                  <div className="flex items-center justify-between mb-3">
                    <span className="text-xs font-bold text-gray-400 uppercase">Branch {i + 1}</span>
                    <div className="flex items-center gap-3">
                      <label className="flex items-center gap-1 text-xs font-semibold text-gray-500">
                        <input
                          type="radio"
                          name="primary-location"
                          checked={!!loc.is_primary}
                          onChange={() => setPrimaryLocation(i)}
                        />
                        Primary
                      </label>
                      <button type="button" onClick={() => removeLocation(i)} className="text-red-500 text-xs hover:underline">
                        Remove
                      </button>
                    </div>
                  </div>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <LocationField label="Branch name" value={loc.name} onChange={(v) => setLocation(i, 'name', v)} />
                    <LocationField label="Phone" value={loc.phone} onChange={(v) => setLocation(i, 'phone', v)} />
                    <LocationField label="Address" value={loc.address} onChange={(v) => setLocation(i, 'address', v)} />
                    <LocationField label="City" value={loc.city} onChange={(v) => setLocation(i, 'city', v)} />
                    <LocationField label="State" value={loc.state} onChange={(v) => setLocation(i, 'state', v)} />
                    <LocationField label="Latitude" value={loc.latitude} onChange={(v) => setLocation(i, 'latitude', v)} />
                    <LocationField label="Longitude" value={loc.longitude} onChange={(v) => setLocation(i, 'longitude', v)} />
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="flex items-center gap-3 pt-2">
          <button
            type="submit"
            disabled={saving}
            className="rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
          >
            {saving ? 'Saving…' : 'Save Changes'}
          </button>
        </div>
      </form>
    </div>
  );
}

function Field({ label, value, onChange, type = 'text' }) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
      <input
        type={type}
        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none"
        value={value}
        onChange={(e) => onChange(e.target.value)}
      />
    </div>
  );
}

function LocationField({ label, value, onChange }) {
  return (
    <div>
      <label className="block text-xs font-medium text-gray-500 mb-1">{label}</label>
      <input
        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-teal-500 outline-none"
        value={value}
        onChange={(e) => onChange(e.target.value)}
      />
    </div>
  );
}