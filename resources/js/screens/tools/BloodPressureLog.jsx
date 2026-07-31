import React, { useState, useCallback, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

const BP_CATEGORIES = [
    { sysMax: 120, diaMax: 80, label: 'Normal', sysColor: '#22C55E', diaColor: '#22C55E', bg: 'bg-green-50', border: 'border-green-200', text: 'text-green-700', advice: 'Your blood pressure is within a healthy range.' },
    { sysMax: 129, diaMax: 84, label: 'Elevated', sysColor: '#F59E0B', diaColor: '#F59E0B', bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', advice: 'Your blood pressure is slightly elevated. Monitor regularly.' },
    { sysMax: 139, diaMax: 89, label: 'High (Stage 1)', sysColor: '#F97316', diaColor: '#F97316', bg: 'bg-orange-50', border: 'border-orange-200', text: 'text-orange-700', advice: 'Stage 1 hypertension. Lifestyle changes recommended.' },
    { sysMax: 999, diaMax: 999, label: 'High (Stage 2)', sysColor: '#EF4444', diaColor: '#EF4444', bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', advice: 'Stage 2 hypertension. Please consult a healthcare professional.' },
];

function getCategory(sys, dia) {
    for (const c of BP_CATEGORIES) {
        if ((sys <= c.sysMax && dia <= c.diaMax) || (c.sysMax === 999 && (sys > 139 || dia > 89))) return c;
    }
    return BP_CATEGORIES[BP_CATEGORIES.length - 1];
}

export default function BloodPressureLog() {
    const [systolic, setSystolic] = useState('');
    const [diastolic, setDiastolic] = useState('');
    const [log, setLog] = useState([]);
    const [result, setResult] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadToday().finally(() => setLoading(false));
    }, []);

    const loadToday = async () => {
        try {
            const res = await api.get('/health-metrics/today');
            const bpEntries = res?.data?.trackers?.blood_pressure || [];
            setLog(bpEntries);
        } catch {}
    };

    const saveToday = useCallback(async (entries) => {
        try {
            await api.post('/health-metrics/sync', {
                date: new Date().toISOString().split('T')[0],
                data: { blood_pressure: entries },
            });
        } catch {}
    }, []);

    const addReading = () => {
        const sys = parseInt(systolic);
        const dia = parseInt(diastolic);
        if (!sys || !dia || sys <= 0 || dia <= 0) return;
        const cat = getCategory(sys, dia);
        const entry = { systolic: sys, diastolic: dia, time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), label: cat.label, color: cat.sysColor };
        const updated = [entry, ...log];
        setLog(updated);
        saveToday(updated);
        setResult({ sys, dia, category: cat });
        setSystolic('');
        setDiastolic('');
    };

    const canAdd = systolic && diastolic && parseInt(systolic) > 0 && parseInt(diastolic) > 0;

    return (
        <div className="space-y-5">
            <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Blood Pressure Log</p>
                <p className="text-sm text-neutral-500 mt-0.5">Log your systolic & diastolic readings and see trends over time.</p>
            </div>

            {/* Input Card */}
            <div className="card p-5">
                <p className="text-base font-bold text-neutral-900 mb-4">New Reading</p>
                <div className="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Systolic</p>
                        <div className="flex items-center bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                            <input type="number" value={systolic} onChange={e => setSystolic(e.target.value)} placeholder="120" className="flex-1 px-4 py-3 text-lg font-bold text-neutral-900 bg-transparent outline-none" maxLength={3} />
                            <span className="text-sm font-semibold text-neutral-400 pr-4">mmHg</span>
                        </div>
                    </div>
                    <div>
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Diastolic</p>
                        <div className="flex items-center bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                            <input type="number" value={diastolic} onChange={e => setDiastolic(e.target.value)} placeholder="80" className="flex-1 px-4 py-3 text-lg font-bold text-neutral-900 bg-transparent outline-none" maxLength={3} />
                            <span className="text-sm font-semibold text-neutral-400 pr-4">mmHg</span>
                        </div>
                    </div>
                </div>
                <button onClick={addReading} disabled={!canAdd} className={`btn w-full ${canAdd ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed'}`}>Log Reading</button>
            </div>

            {/* Last Result */}
            {result && (
                <div className={`card p-6 text-center ${result.category.bg} ${result.category.border}`}>
                    <p className="text-xs font-bold uppercase tracking-wider mb-2" style={{ color: result.category.sysColor }}>Latest Reading</p>
                    <p className="text-5xl font-extrabold tracking-tight mb-2" style={{ color: result.category.sysColor }}>{result.sys}/{result.dia}</p>
                    <p className={`text-sm font-semibold ${result.category.text}`}>mmHg</p>
                    <div className={`inline-flex items-center gap-2 rounded-xl px-4 py-2 mt-3 ${result.category.bg}`}>
                        <span className="w-2 h-2 rounded-full" style={{ backgroundColor: result.category.sysColor }} />
                        <span className="text-base font-bold" style={{ color: result.category.sysColor }}>{result.category.label}</span>
                    </div>
                    <p className={`text-sm mt-3 leading-relaxed px-4 ${result.category.text}`}>{result.category.advice}</p>
                </div>
            )}

            {/* Today's Log */}
            {log.length > 0 && (
                <div className="card p-5">
                    <p className="text-base font-bold text-neutral-900 mb-4">Today's Readings</p>
                    <div className="space-y-2">
                        {log.map((entry, i) => (
                            <div key={i} className="flex items-center gap-3 bg-neutral-50 rounded-xl p-3">
                                <div className="w-10 h-10 rounded-lg flex items-center justify-center text-lg flex-shrink-0" style={{ backgroundColor: entry.color + '15' }}>
                                    <span style={{ color: entry.color }}>⬤</span>
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-bold text-neutral-900">{entry.systolic}/{entry.diastolic} mmHg</p>
                                    <p className="text-xs text-neutral-400">{entry.time}</p>
                                </div>
                                <span className="text-xs font-bold px-2 py-1 rounded-lg" style={{ backgroundColor: entry.color + '15', color: entry.color }}>{entry.label}</span>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="card p-5 bg-amber-50 border-amber-200">
                <p className="text-base font-bold text-amber-800 mb-2">About Blood Pressure</p>
                <p className="text-sm text-amber-800 leading-relaxed">Systolic (top number) measures pressure when the heart beats. Diastolic (bottom number) measures pressure between beats. Regular monitoring helps detect hypertension early.</p>
            </div>
        </div>
    );
}