import React, { useState, useCallback, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

const ADULT_SCHEDULE = [
    { name: 'Tetanus Booster', detail: 'Td/Tdap every 10 years', intervalMonths: 120 },
    { name: 'Hepatitis B', detail: '3-dose series if not previously vaccinated', intervalMonths: null },
    { name: 'Yellow Fever', detail: 'Before travel to endemic areas (valid 10 years/lifetime)', intervalMonths: 120 },
    { name: 'Meningitis', detail: 'For the meningitis belt / travel to at-risk regions', intervalMonths: 60 },
    { name: 'COVID-19 Booster', detail: 'Per current public-health guidance', intervalMonths: 12 },
    { name: 'HPV', detail: 'For eligible adults up to age 26 (and some 27–45)', intervalMonths: null },
];

const STATUS_COLORS = {
    done: { bg: 'bg-green-50', text: 'text-green-700', border: 'border-green-200', dot: 'bg-green-500', label: 'Done ✓' },
    due: { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500', label: 'Due' },
    upcoming: { bg: 'bg-neutral-50', text: 'text-neutral-500', border: 'border-neutral-100', dot: 'bg-neutral-300', label: 'Recommended' },
};

export default function AdultImmunizationTracker() {
    const [records, setRecords] = useState({});
    const [lastDate, setLastDate] = useState('');

    useEffect(() => {
        (async () => {
            try {
                const res = await api.get('/health-metrics/today');
                const data = res?.data?.trackers?.adult_immunization;
                if (data) {
                    if (data.records) setRecords(data.records);
                    if (data.last_date) setLastDate(data.last_date);
                }
            } catch {}
        })();
    }, []);

    const persist = useCallback(async (r, l) => {
        try {
            await api.post('/health-metrics/sync', {
                date: new Date().toISOString().split('T')[0],
                data: { adult_immunization: { records: r, last_date: l } },
            });
        } catch {}
    }, []);

    const toggleVaccine = (name) => {
        const exists = records[name];
        const next = { ...records };
        if (exists) {
            delete next[name];
        } else {
            const now = new Date().toISOString().split('T')[0];
            next[name] = now;
            setLastDate(now);
            persist(next, now);
            return;
        }
        setRecords(next);
        persist(next, lastDate);
    };

    const getStatus = (vaccine) => {
        if (records[vaccine.name]) return 'done';
        // Due if an interval-based booster's last dose is older than its interval.
        if (lastDate && vaccine.intervalMonths) {
            const last = new Date(lastDate);
            const due = new Date(last);
            due.setMonth(due.getMonth() + vaccine.intervalMonths);
            if (new Date() >= due) return 'due';
        }
        return 'upcoming';
    };

    const doneCount = ADULT_SCHEDULE.filter(v => records[v.name]).length;

    return (
        <div className="space-y-5">
            <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Adult Vaccination Schedule</p>
                <p className="text-sm text-neutral-500 mt-0.5">Track boosters and travel vaccines you should keep current.</p>
            </div>

            <div className="card p-5">
                <div className="flex items-center justify-between mb-4">
                    <p className="text-base font-bold text-neutral-900">Your Vaccines</p>
                    <span className="text-xs font-bold text-teal-600">{doneCount}/{ADULT_SCHEDULE.length} done</span>
                </div>
                <div className="h-1.5 bg-neutral-200 rounded-full overflow-hidden mb-4">
                    <div className="h-full bg-teal-500 rounded-full" style={{ width: `${(doneCount / ADULT_SCHEDULE.length) * 100}%` }} />
                </div>

                <div className="space-y-1.5">
                    {ADULT_SCHEDULE.map((v) => {
                        const status = getStatus(v);
                        const s = STATUS_COLORS[status];
                        return (
                            <button key={v.name} onClick={() => toggleVaccine(v.name)} className={`w-full flex items-center gap-2 rounded-lg px-3 py-2 text-left text-xs transition-all ${s.bg} border ${s.border}`}>
                                <span className={`w-2 h-2 rounded-full flex-shrink-0 ${s.dot}`} />
                                <span className={`font-bold flex-1 ${s.text}`}>{v.name} <span className="font-normal opacity-70">— {v.detail}</span></span>
                                <span className={`font-semibold ${s.text}`}>{s.label}</span>
                            </button>
                        );
                    })}
                </div>
            </div>

            <div className="card p-5 bg-amber-50 border-amber-200">
                <p className="text-base font-bold text-amber-800 mb-2">About This Schedule</p>
                <p className="text-sm text-amber-800 leading-relaxed">General adult recommendations. Your specific needs depend on age, health conditions, occupation, and travel. Always confirm with your healthcare provider.</p>
            </div>
        </div>
    );
}