import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';

const STATUS_OPTIONS = ['upcoming', 'completed', 'cancelled'];
const STATUS_COLORS = {
  upcoming: { bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500' },
  completed: { bg: 'bg-green-50', text: 'text-green-700', dot: 'bg-green-500' },
  cancelled: { bg: 'bg-neutral-100', text: 'text-neutral-500', dot: 'bg-neutral-400' },
};

export default function AppointmentTracker() {
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState('upcoming');
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ title: '', description: '', appointment_date: '', appointment_time: '', notes: '', reminder_enabled: true, reminder_minutes_before: '30' });
  const [error, setError] = useState('');

  const { data, isLoading } = useQuery({
    queryKey: ['appointments'],
    queryFn: () => api.get('/appointments'),
  });

  const appointmentsData = data?.data || {};
  const upcoming = appointmentsData.upcoming || [];
  const past = appointmentsData.past || [];
  const items = activeTab === 'upcoming' ? upcoming : past;

  const createMut = useMutation({
    mutationFn: (payload) => api.post('/appointments', payload),
    onSuccess: () => { queryClient.invalidateQueries(['appointments']); setShowForm(false); resetForm(); },
    onError: (err) => setError(err?.message || 'Failed to create appointment'),
  });

  const updateMut = useMutation({
    mutationFn: ({ id, payload }) => api.put(`/appointments/${id}`, payload),
    onSuccess: () => queryClient.invalidateQueries(['appointments']),
  });

  const deleteMut = useMutation({
    mutationFn: (id) => api.delete(`/appointments/${id}`),
    onSuccess: () => queryClient.invalidateQueries(['appointments']),
  });

  const resetForm = () => setForm({ title: '', description: '', appointment_date: '', appointment_time: '', notes: '', reminder_enabled: true, reminder_minutes_before: '30' });

  const handleSubmit = () => {
    if (!form.title.trim() || !form.appointment_date) { setError('Title and date are required'); return; }
    setError('');
    createMut.mutate({
      title: form.title.trim(),
      description: form.description.trim() || undefined,
      appointment_date: form.appointment_date,
      appointment_time: form.appointment_time || undefined,
      notes: form.notes.trim() || undefined,
      reminder_enabled: form.reminder_enabled,
      reminder_minutes_before: parseInt(form.reminder_minutes_before) || 30,
    });
  };

  const handleStatusChange = (id, status) => updateMut.mutate({ id, payload: { status } });
  const handleDelete = (id) => { if (window.confirm('Delete this appointment?')) deleteMut.mutate(id); };

  return (
    <div className="space-y-5">
      <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
      <div className="flex items-center justify-between">
        <div>
          <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Appointment Tracker</p>
          <p className="text-sm text-neutral-500 mt-0.5">Book, track & get reminded for medical appointments.</p>
        </div>
        <button onClick={() => setShowForm(!showForm)} className="btn bg-blue-500 hover:bg-blue-600 text-white text-sm">{showForm ? 'Cancel' : '+ New'}</button>
      </div>

      {/* Add Form */}
      {showForm && (
        <div className="card p-5 space-y-3">
          {error && <div className="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700">{error}</div>}
          <div>
            <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Title *</p>
            <input type="text" value={form.title} onChange={e => setForm({ ...form, title: e.target.value })} placeholder="e.g. Annual checkup" className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold outline-none" />
          </div>
          <div>
            <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Description</p>
            <textarea value={form.description} onChange={e => setForm({ ...form, description: e.target.value })} rows={2} placeholder="Reason for visit..." className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm outline-none resize-none" />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Date *</p>
              <input type="date" value={form.appointment_date} onChange={e => setForm({ ...form, appointment_date: e.target.value })} className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold outline-none" />
            </div>
            <div>
              <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Time</p>
              <input type="time" value={form.appointment_time} onChange={e => setForm({ ...form, appointment_time: e.target.value })} className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold outline-none" />
            </div>
          </div>
          <div>
            <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Notes</p>
            <input type="text" value={form.notes} onChange={e => setForm({ ...form, notes: e.target.value })} placeholder="Doctor's name, location..." className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold outline-none" />
          </div>
          <div className="flex items-center gap-3">
            <label className="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" checked={form.reminder_enabled} onChange={e => setForm({ ...form, reminder_enabled: e.target.checked })} className="rounded" />
              <span className="text-sm font-semibold text-neutral-700">Enable reminder</span>
            </label>
            {form.reminder_enabled && (
              <select value={form.reminder_minutes_before} onChange={e => setForm({ ...form, reminder_minutes_before: e.target.value })} className="bg-neutral-50 rounded-lg border border-neutral-200 px-2 py-1.5 text-xs font-semibold outline-none">
                <option value="15">15 min before</option>
                <option value="30">30 min before</option>
                <option value="60">1 hour before</option>
                <option value="1440">1 day before</option>
              </select>
            )}
          </div>
          <button onClick={handleSubmit} disabled={createMut.isPending} className="btn w-full bg-blue-500 hover:bg-blue-600 text-white">{createMut.isPending ? 'Saving...' : 'Save Appointment'}</button>
        </div>
      )}

      {/* Tabs */}
      <div className="flex gap-1 bg-neutral-100 rounded-xl p-1">
        {['upcoming', 'past'].map(tab => (
          <button key={tab} onClick={() => setActiveTab(tab)} className={`flex-1 py-2.5 rounded-lg text-sm font-bold capitalize transition-all ${activeTab === tab ? 'bg-white shadow-sm text-neutral-900' : 'text-neutral-400'}`}>{tab}</button>
        ))}
      </div>

      {/* List */}
      {isLoading ? (
        <div className="text-center py-8"><div className="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto" /></div>
      ) : items.length === 0 ? (
        <div className="card p-8 text-center">
          <span className="text-3xl block mb-3">📅</span>
          <p className="text-sm font-bold text-neutral-900 mb-1">No {activeTab} appointments</p>
          <p className="text-xs text-neutral-500">Create a new appointment to get started</p>
        </div>
      ) : (
        <div className="space-y-2">
          {items.map((a) => {
            const s = STATUS_COLORS[a.status] || STATUS_COLORS.upcoming;
            const date = new Date(a.appointment_date);
            return (
              <div key={a.id} className="card p-4">
                <div className="flex items-start gap-3">
                  <div className="w-12 h-12 rounded-xl bg-neutral-50 border border-neutral-200 flex flex-col items-center justify-center flex-shrink-0">
                    <p className="text-xs font-bold text-blue-600">{date.toLocaleDateString('en-US', { month: 'short' })}</p>
                    <p className="text-lg font-extrabold text-neutral-900">{date.getDate()}</p>
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <p className="text-sm font-bold text-neutral-900 truncate">{a.title}</p>
                      <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded-full ${s.bg} ${s.text}`}>{a.status}</span>
                    </div>
                    {a.appointment_time && <p className="text-xs text-neutral-400 mt-0.5">{a.appointment_time}</p>}
                    {a.description && <p className="text-xs text-neutral-500 mt-1 line-clamp-1">{a.description}</p>}
                    {a.notes && <p className="text-xs text-neutral-400 mt-0.5">{a.notes}</p>}
                    {a.provider && <p className="text-xs text-blue-500 mt-1 font-semibold">{a.provider.name} — {a.provider.type}</p>}
                  </div>
                </div>
                {/* Actions */}
                <div className="flex items-center gap-2 mt-3 pt-3 border-t border-neutral-100">
                  {a.status === 'upcoming' && (
                    <>
                      <button onClick={() => handleStatusChange(a.id, 'completed')} className="text-xs font-bold text-green-600 hover:text-green-700">Mark Done</button>
                      <button onClick={() => handleStatusChange(a.id, 'cancelled')} className="text-xs font-bold text-neutral-400 hover:text-neutral-600">Cancel</button>
                    </>
                  )}
                  <button onClick={() => handleDelete(a.id)} className="text-xs font-bold text-red-400 hover:text-red-600 ml-auto">Delete</button>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}