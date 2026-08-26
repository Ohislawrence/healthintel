import React, { useEffect, useState } from 'react';
import usePartnerAuthStore from '../../stores/partnerAuthStore';

const STATUS_STYLES = {
  pending: 'bg-amber-50 text-amber-700',
  confirmed: 'bg-green-50 text-green-700',
  declined: 'bg-red-50 text-red-700',
  completed: 'bg-gray-50 text-gray-600',
  cancelled: 'bg-gray-50 text-gray-500',
};

export default function PartnerAppointments() {
  const { apiGet, apiPost } = usePartnerAuthStore();
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState('pending');
  const [note, setNote] = useState('');
  const [busyId, setBusyId] = useState(null);

  const load = () => {
    setLoading(true);
    apiGet('/appointments')
      .then((res) => setData(res.data || res))
      .catch((err) => setError(err.message))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, []);

  const decide = (id, decision) => {
    setBusyId(id);
    apiPost(`/appointments/${id}/decision`, { decision, provider_notes: note || undefined })
      .then(() => {
        setNote('');
        load();
      })
      .catch((err) => setError(err.message))
      .finally(() => setBusyId(null));
  };

  const all = data?.appointments || [];
  const items = activeTab === 'all' ? all : all.filter((a) => a.status === activeTab);
  const counts = data?.counts || {};

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-xl font-bold text-gray-900">Appointments</h2>
          <p className="text-sm text-gray-500 mt-1">Review and manage patient booking requests.</p>
        </div>
        <button
          onClick={load}
          className="text-sm text-teal-600 hover:underline"
        >
          Refresh
        </button>
      </div>

      {/* Tabs */}
      <div className="flex gap-2 flex-wrap">
        {[
          ['pending', 'Pending', counts.pending],
          ['confirmed', 'Confirmed', counts.confirmed],
          ['declined', 'Declined', null],
          ['completed', 'Completed', null],
          ['cancelled', 'Cancelled', null],
          ['all', 'All', null],
        ].map(([key, label, count]) => (
          <button
            key={key}
            onClick={() => setActiveTab(key)}
            className={`px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors ${
              activeTab === key ? 'bg-teal-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50'
            }`}
          >
            {label}
            {count != null && <span className="ml-1 opacity-70">({count})</span>}
          </button>
        ))}
      </div>

      {error && <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm">{error}</div>}

      {loading ? (
        <div className="flex justify-center py-16">
          <div className="h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" />
        </div>
      ) : items.length === 0 ? (
        <div className="bg-white rounded-xl border border-gray-200 p-10 text-center">
          <p className="text-gray-400">No {activeTab} appointments.</p>
        </div>
      ) : (
        <div className="space-y-3">
          {items.map((a) => (
            <div key={a.id} className="bg-white rounded-xl border border-gray-200 p-4">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="font-semibold text-gray-900">{a.patient_name || a.user?.name || 'Patient'}</span>
                    <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${STATUS_STYLES[a.status] || STATUS_STYLES.pending}`}>
                      {a.status}
                    </span>
                  </div>
                  <p className="text-xs text-gray-500 mt-1">
                    {a.title} · {a.appointment_date} {a.appointment_time}
                  </p>
                  {(a.patient_phone || a.user?.phone) && (
                    <p className="text-xs text-gray-500 mt-0.5">📞 {a.patient_phone || a.user?.phone}</p>
                  )}
                  {a.notes && <p className="text-sm text-gray-600 mt-2">{a.notes}</p>}
                  {a.provider_notes && <p className="text-xs text-gray-400 mt-1 italic">Your note: {a.provider_notes}</p>}
                </div>
              </div>

              {a.status === 'pending' && (
                <div className="mt-3 pt-3 border-t border-gray-100 space-y-2">
                  <input
                    type="text"
                    value={note}
                    onChange={(e) => setNote(e.target.value)}
                    placeholder="Optional note to the patient…"
                    className="w-full rounded border border-gray-300 px-3 py-2 text-sm"
                  />
                  <div className="flex gap-2">
                    <button
                      onClick={() => decide(a.id, 'confirm')}
                      disabled={busyId === a.id}
                      className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50"
                    >
                      Confirm
                    </button>
                    <button
                      onClick={() => decide(a.id, 'decline')}
                      disabled={busyId === a.id}
                      className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-50"
                    >
                      Decline
                    </button>
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}