import React, { useState, useCallback, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

export default function MedicationReminder() {
    const [meds, setMeds] = useState([]);
    const [showForm, setShowForm] = useState(false);
    const [form, setForm] = useState({ name: '', dosage: '', time: '', frequency: 'daily', reminder_enabled: true });

    useEffect(() => {
        (async () => {
            try {
                const res = await api.get('/health-metrics/today');
                setMeds(res?.data?.trackers?.medication || []);
            } catch {}
        })();
    }, []);

    const persist = useCallback(async (list) => {
        try {
            await api.post('/health-metrics/sync', {
                date: new Date().toISOString().split('T')[0],
                data: { medication: list },
            });
        } catch {}
    }, []);

    const addMed = () => {
        if (!form.name.trim() || !form.time) return;
        const med = {
            id: Date.now().toString(),
            name: form.name.trim(),
            dosage: form.dosage.trim(),
            time: form.time,
            frequency: form.frequency,
            reminder_enabled: form.reminder_enabled,
        };
        const next = [med, ...meds];
        setMeds(next);
        persist(next);
        setForm({ name: '', dosage: '', time: '', frequency: 'daily', reminder_enabled: true });
        setShowForm(false);
    };

    const removeMed = (id) => {
        const next = meds.filter(m => m.id !== id);
        setMeds(next);
        persist(next);
    };

    const toggleEnabled = (id) => {
        const next = meds.map(m => m.id === id ? { ...m, reminder_enabled: !m.reminder_enabled } : m);
        setMeds(next);
        persist(next);
    };

    return (
        <div className="space-y-5">
            <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Medication Reminder</p>
                    <p className="text-sm text-neutral-500 mt-0.5">Never miss a dose — get nudged at the right time.</p>
                </div>
                <button onClick={() => setShowForm(!showForm)} className="btn bg-rose-500 hover:bg-rose-600 text-white text-sm">{showForm ? 'Cancel' : '+ Add'}</button>
            </div>

            {showForm && (
                <div className="card p-5 space-y-3">
                    <p className="text-base font-bold text-neutral-900">New Medication</p>
                    <div>
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Name *</p>
                        <input type="text" value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} placeholder="e.g. Amlodipine" className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold outline-none" />
                    </div>
                    <div>
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Dosage</p>
                        <input type="text" value={form.dosage} onChange={e => setForm({ ...form, dosage: e.target.value })} placeholder="e.g. 5mg" className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold outline-none" />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Time *</p>
                            <input type="time" value={form.time} onChange={e => setForm({ ...form, time: e.target.value })} className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold outline-none" />
                        </div>
                        <div>
                            <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Frequency</p>
                            <select value={form.frequency} onChange={e => setForm({ ...form, frequency: e.target.value })} className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold outline-none">
                                <option value="daily">Daily</option>
                                <option value="twice-daily">Twice daily</option>
                                <option value="weekly">Weekly</option>
                            </select>
                        </div>
                    </div>
                    <label className="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" checked={form.reminder_enabled} onChange={e => setForm({ ...form, reminder_enabled: e.target.checked })} className="rounded" />
                        <span className="text-sm font-semibold text-neutral-700">Enable reminder</span>
                    </label>
                    <button onClick={addMed} disabled={!form.name.trim() || !form.time} className="btn w-full bg-rose-500 hover:bg-rose-600 text-white disabled:bg-neutral-200 disabled:text-neutral-400">Save Medication</button>
                </div>
            )}

            {meds.length === 0 ? (
                <div className="card p-8 text-center">
                    <span className="text-3xl block mb-3">💊</span>
                    <p className="text-sm font-bold text-neutral-900 mb-1">No medications yet</p>
                    <p className="text-xs text-neutral-500">Add a medication to get reminded on schedule.</p>
                </div>
            ) : (
                <div className="space-y-2">
                    {meds.map((m) => (
                        <div key={m.id} className="card p-4 flex items-center gap-3">
                            <div className="w-10 h-10 rounded-lg bg-rose-50 flex items-center justify-center text-lg flex-shrink-0">💊</div>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-bold text-neutral-900">{m.name} {m.dosage && <span className="text-neutral-500 font-normal">· {m.dosage}</span>}</p>
                                <p className="text-xs text-neutral-400">{m.time} · {m.frequency}</p>
                            </div>
                            <button onClick={() => toggleEnabled(m.id)} className={`text-xs font-bold px-2 py-1 rounded-lg ${m.reminder_enabled ? 'bg-green-100 text-green-700' : 'bg-neutral-100 text-neutral-400'}`}>{m.reminder_enabled ? 'On' : 'Off'}</button>
                            <button onClick={() => removeMed(m.id)} className="text-xs font-bold text-red-400 hover:text-red-600">Remove</button>
                        </div>
                    ))}
                </div>
            )}

            <div className="card p-5 bg-amber-50 border-amber-200">
                <p className="text-base font-bold text-amber-800 mb-2">About Reminders</p>
                <p className="text-sm text-amber-800 leading-relaxed">Reminders are sent via push notification at your chosen time. Always take medication exactly as prescribed by your doctor.</p>
            </div>
        </div>
    );
}