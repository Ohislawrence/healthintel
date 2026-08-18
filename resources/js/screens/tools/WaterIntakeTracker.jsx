import React, { useState, useCallback, useEffect, useMemo } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

const CUP_ML = 250;
const DEFAULT_GOAL_ML = 3000;
const ML_PER_KG = 35;

const quickAdds = [
    { label: 'Small glass', ml: 200, icon: '🥛' },
    { label: 'Large glass', ml: 350, icon: '💧' },
    { label: 'Bottle', ml: 500, icon: '🍶' },
    { label: 'Large bottle', ml: 1000, icon: '🫗' },
    { label: 'Cup', ml: 250, icon: '☕' },
];

export default function WaterIntakeTracker() {
    const { user } = useAuthStore();
    const [log, setLog] = useState([]);
    const [customMl, setCustomMl] = useState('');
    const [loading, setLoading] = useState(true);

    const goalMl = useMemo(() => {
        const weight = parseFloat(user?.health_profile?.weight_kg);
        if (weight && weight > 0) {
            const raw = Math.round(weight * ML_PER_KG);
            // Round to nearest 50ml for a cleaner goal display.
            return Math.max(1500, Math.round(raw / 50) * 50);
        }
        return DEFAULT_GOAL_ML;
    }, [user]);

    useEffect(() => {
        loadToday().finally(() => setLoading(false));
    }, []);

    const loadToday = async () => {
        try {
            const res = await api.get('/health-metrics/today');
            setLog(res?.data?.trackers?.water_intake || []);
        } catch {}
    };

    const saveToday = useCallback(async (entries) => {
        try {
            await api.post('/health-metrics/sync', {
                date: new Date().toISOString().split('T')[0],
                data: { water_intake: entries },
            });
        } catch {}
    }, []);

    const addIntake = (ml) => {
        const entry = { ml, time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) };
        const updated = [entry, ...log];
        setLog(updated);
        saveToday(updated);
        setCustomMl('');
    };

    const addCustom = () => {
        const ml = parseInt(customMl);
        if (!ml || ml <= 0) return;
        addIntake(ml);
    };

    const totalMl = useMemo(() => log.reduce((sum, e) => sum + (e.ml || 0), 0), [log]);
    const pct = Math.min(100, Math.round((totalMl / goalMl) * 100));
    const remaining = Math.max(0, goalMl - totalMl);
    const glassesCount = Math.round(totalMl / CUP_ML);

    return (
        <div className="space-y-5">
            <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Water Intake Tracker</p>
                <p className="text-sm text-neutral-500 mt-0.5">Log your daily water intake, set goals & track progress.</p>
            </div>

            {/* Progress Card */}
            <div className="card p-5 text-center bg-blue-50 border-blue-200">
                <p className="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">Today's Progress</p>
                <p className="text-5xl font-extrabold text-blue-700 tracking-tight">{totalMl}</p>
                <p className="text-sm font-semibold text-blue-500 mt-1">ml of {goalMl} ml goal</p>
                <div className="h-3 bg-blue-100 rounded-full overflow-hidden mt-4 mb-2">
                    <div className="h-full bg-blue-500 rounded-full transition-all duration-500" style={{ width: `${pct}%` }} />
                </div>
                <div className="flex justify-between text-xs">
                    <span className="font-bold text-blue-600">{pct}%</span>
                    <span className="text-blue-400">{glassesCount} glasses</span>
                    <span className="text-blue-400">{remaining}ml left</span>
                </div>
            </div>

            {/* Quick Add */}
            <div className="card p-5">
                <p className="text-base font-bold text-neutral-900 mb-4">Quick Add</p>
                <div className="grid grid-cols-3 sm:grid-cols-5 gap-2 mb-4">
                    {quickAdds.map((q) => (
                        <button
                            key={q.label}
                            onClick={() => addIntake(q.ml)}
                            className="flex flex-col items-center gap-1 p-3 rounded-xl bg-neutral-50 hover:bg-blue-50 border border-neutral-200 hover:border-blue-300 transition-all"
                        >
                            <span className="text-xl">{q.icon}</span>
                            <span className="text-xs font-semibold text-neutral-600">{q.label}</span>
                            <span className="text-[10px] font-bold text-neutral-400">{q.ml}ml</span>
                        </button>
                    ))}
                </div>

                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Custom Amount</p>
                <div className="flex gap-2">
                    <div className="flex-1 flex items-center bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                        <input type="number" value={customMl} onChange={e => setCustomMl(e.target.value)} placeholder="Enter ml" className="flex-1 px-4 py-3 text-lg font-bold text-neutral-900 bg-transparent outline-none" />
                        <span className="text-sm font-semibold text-neutral-400 pr-4">ml</span>
                    </div>
                    <button onClick={addCustom} disabled={!customMl || parseInt(customMl) <= 0} className="px-6 py-3 rounded-xl bg-blue-500 hover:bg-blue-600 text-white font-bold text-sm disabled:bg-neutral-200 disabled:text-neutral-400">Add</button>
                </div>
            </div>

            {/* Today's Log */}
            {log.length > 0 && (
                <div className="card p-5">
                    <p className="text-base font-bold text-neutral-900 mb-4">Today's Log</p>
                    <div className="space-y-2">
                        {log.map((entry, i) => (
                            <div key={i} className="flex items-center gap-3 bg-neutral-50 rounded-xl p-3">
                                <div className="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-lg flex-shrink-0">
                                    <span>💧</span>
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-bold text-neutral-900">{entry.ml} ml</p>
                                    <p className="text-xs text-neutral-400">{entry.time}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="card p-5 bg-amber-50 border-amber-200">
                <p className="text-base font-bold text-amber-800 mb-2">Hydration Tips</p>
                <p className="text-sm text-amber-800 leading-relaxed">Aim for 2-3 liters per day. Water helps regulate body temperature, transport nutrients, and flush toxins. Needs increase with exercise and hot weather.</p>
            </div>
        </div>
    );
}