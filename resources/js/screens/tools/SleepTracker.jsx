import React, { useState, useCallback, useEffect, useMemo } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

const QUALITY_OPTIONS = [
    { key: 'poor', label: 'Poor', color: '#EF4444' },
    { key: 'fair', label: 'Fair', color: '#F59E0B' },
    { key: 'good', label: 'Good', color: '#84CC16' },
    { key: 'excellent', label: 'Excellent', color: '#22C55E' },
];

function durationLabel(mins) {
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return `${h}h ${m}m`;
}

export default function SleepTracker() {
    const [log, setLog] = useState([]);
    const [bedTime, setBedTime] = useState('');
    const [wakeTime, setWakeTime] = useState('');
    const [quality, setQuality] = useState('good');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadToday().finally(() => setLoading(false));
    }, []);

    const loadToday = async () => {
        try {
            const res = await api.get('/health-metrics/today');
            setLog(res?.data?.trackers?.sleep || []);
        } catch {}
    };

    const saveToday = useCallback(async (entries) => {
        try {
            await api.post('/health-metrics/sync', {
                date: new Date().toISOString().split('T')[0],
                data: { sleep: entries },
            });
        } catch {}
    }, []);

    const addSleep = () => {
        if (!bedTime || !wakeTime) return;
        const toMinutes = (t) => {
            const [h, m] = t.split(':').map(Number);
            return h * 60 + m;
        };
        let mins = toMinutes(wakeTime) - toMinutes(bedTime);
        if (mins < 0) mins += 24 * 60; // crossed midnight

        const entry = {
            bed_time: bedTime,
            wake_time: wakeTime,
            duration_min: mins,
            quality,
            date: new Date().toISOString().split('T')[0],
        };
        const updated = [entry, ...log];
        setLog(updated);
        saveToday(updated);
        setBedTime('');
        setWakeTime('');
    };

    const canAdd = bedTime && wakeTime;
    const totalSleep = useMemo(() => log.reduce((sum, e) => sum + (e.duration_min || 0), 0), [log]);

    return (
        <div className="space-y-5">
            <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Sleep Tracker</p>
                <p className="text-sm text-neutral-500 mt-0.5">Log your sleep duration and quality — a key input for spotting symptom patterns.</p>
            </div>

            {/* Add Entry */}
            <div className="card p-5">
                <p className="text-base font-bold text-neutral-900 mb-4">Log Sleep</p>
                <div className="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Bed Time</p>
                        <input type="time" value={bedTime} onChange={e => setBedTime(e.target.value)} className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold outline-none" />
                    </div>
                    <div>
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Wake Time</p>
                        <input type="time" value={wakeTime} onChange={e => setWakeTime(e.target.value)} className="w-full bg-neutral-50 rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold outline-none" />
                    </div>
                </div>

                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Sleep Quality</p>
                <div className="grid grid-cols-4 gap-2 mb-4">
                    {QUALITY_OPTIONS.map((q) => (
                        <button key={q.key} onClick={() => setQuality(q.key)} className={`py-2 rounded-xl text-xs font-bold border transition-all ${quality === q.key ? 'border-purple-400 bg-purple-50 text-purple-700' : 'border-neutral-200 bg-neutral-50 text-neutral-500'}`}>{q.label}</button>
                    ))}
                </div>

                <button onClick={addSleep} disabled={!canAdd} className={`btn w-full ${canAdd ? 'bg-purple-500 hover:bg-purple-600 text-white' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed'}`}>Log Sleep</button>
            </div>

            {/* Summary */}
            {log.length > 0 && (
                <div className="card p-5 text-center bg-purple-50 border-purple-200">
                    <p className="text-xs font-bold text-purple-600 uppercase tracking-wider mb-1">Today's Sleep</p>
                    <p className="text-5xl font-extrabold text-purple-700 tracking-tight">{durationLabel(totalSleep)}</p>
                    <p className="text-sm font-semibold text-purple-500 mt-1">{log.length} session{log.length > 1 ? 's' : ''} logged</p>
                </div>
            )}

            {/* Log */}
            {log.length > 0 && (
                <div className="card p-5">
                    <p className="text-base font-bold text-neutral-900 mb-4">Today's Log</p>
                    <div className="space-y-2">
                        {log.map((entry, i) => {
                            const q = QUALITY_OPTIONS.find(x => x.key === entry.quality) || QUALITY_OPTIONS[0];
                            return (
                                <div key={i} className="flex items-center gap-3 bg-neutral-50 rounded-xl p-3">
                                    <div className="w-10 h-10 rounded-lg flex items-center justify-center text-lg flex-shrink-0" style={{ backgroundColor: q.color + '15' }}>
                                        <span style={{ color: q.color }}>😴</span>
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-bold text-neutral-900">{durationLabel(entry.duration_min)}</p>
                                        <p className="text-xs text-neutral-400">{entry.bed_time} → {entry.wake_time}</p>
                                    </div>
                                    <span className="text-xs font-bold px-2 py-1 rounded-lg" style={{ backgroundColor: q.color + '15', color: q.color }}>{q.label}</span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            <div className="card p-5 bg-amber-50 border-amber-200">
                <p className="text-base font-bold text-amber-800 mb-2">About Sleep</p>
                <p className="text-sm text-amber-800 leading-relaxed">Most adults need 7–9 hours per night. Poor sleep can amplify symptoms and affect blood pressure, mood, and metabolism — pair this with the Food & Symptom Diary to spot correlations.</p>
            </div>
        </div>
    );
}