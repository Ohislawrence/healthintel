import React, { useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

function parseDate(s) { const p = s.split('-').map(Number); if (p.length !== 3 || p.some(isNaN)) return null; return { year: p[0], month: p[1], day: p[2] }; }
function addDays(d, days) { const nd = new Date(d.year, d.month - 1, d.day); nd.setDate(nd.getDate() + days); return { year: nd.getFullYear(), month: nd.getMonth() + 1, day: nd.getDate() }; }
function daysBetween(a, b) { return Math.round((new Date(b.year, b.month - 1, b.day).getTime() - new Date(a.year, a.month - 1, a.day).getTime()) / 86400000); }
function formatDate(d) { return new Date(d.year, d.month - 1, d.day).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }); }

const MILESTONES = [
    { week: 4, label: 'Heart begins to beat' }, { week: 8, label: 'All major organs formed' }, { week: 12, label: 'Fingernails & toenails appear' },
    { week: 16, label: 'May feel first movements' }, { week: 20, label: 'Halfway there! Anatomy scan' }, { week: 24, label: 'Lungs developing rapidly' },
    { week: 28, label: 'Eyes open, third trimester' }, { week: 32, label: 'Baby positions head-down' }, { week: 36, label: 'Baby is considered full term' },
    { week: 40, label: 'Estimated due date! 🎉' },
];

export default function DueDateCalculator() {
    const [lmpY, setY] = useState(''); const [lmpM, setM] = useState(''); const [lmpD, setD] = useState('');
    const [cycle, setCycle] = useState('28'); const [result, setResult] = useState(null);

    const calc = useCallback(() => {
        const lmp = parseDate(`${lmpY}-${lmpM.padStart(2, '0')}-${lmpD.padStart(2, '0')}`);
        if (!lmp) return;
        const cyc = parseInt(cycle) || 28;
        const due = addDays(lmp, 280 + (cyc - 28));
        const today = { year: new Date().getFullYear(), month: new Date().getMonth() + 1, day: new Date().getDate() };
        const conc = addDays(lmp, 14);
        const dp = daysBetween(lmp, today);
        const wp = Math.floor(dp / 7);
        const dr = Math.max(0, Math.floor(dp % 7));
        const tri = wp < 13 ? 1 : wp < 27 ? 2 : 3;
        const dd = daysBetween(today, due);
        const wd = Math.floor(dd / 7);
        setResult({ lmp, due, conc, dp, wp, dr, tri, dd, wd, up: MILESTONES.filter(m => m.week > wp).slice(0, 3), pa: MILESTONES.filter(m => m.week <= wp).slice(-2) });
        try { api.post('/health-metrics', { metric_type: 'due_date', data: { lmp: `${lmpY}-${lmpM.padStart(2, '0')}-${lmpD.padStart(2, '0')}`, due_date: formatDate(due), weeks_pregnant: wp, trimester: tri } }); } catch {}
    }, [lmpY, lmpM, lmpD, cycle]);

    const reset = () => { setY(''); setM(''); setD(''); setCycle('28'); setResult(null); };
    const can = lmpY.length === 4 && lmpM && lmpD && cycle && parseInt(lmpY) >= 2020 && parseInt(lmpM) >= 1 && parseInt(lmpM) <= 12 && parseInt(lmpD) >= 1 && parseInt(lmpD) <= 31;

    return (
        <div className="space-y-5">
            <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Due Date Calculator</p>
                <p className="text-sm text-neutral-500 mt-0.5">Estimate your baby's due date and track pregnancy week by week.</p>
            </div>

            <div className="card p-5">
                <p className="text-base font-bold text-neutral-900 mb-4">Your Details</p>

                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2 mt-4">First Day of Last Period</p>
                <div className="grid grid-cols-3 gap-3 mb-4">
                    {[{ label: 'Year', state: lmpY, set: setY, max: 4, placeholder: 'YYYY' }, { label: 'Month', state: lmpM, set: setM, max: 2, placeholder: 'MM' }, { label: 'Day', state: lmpD, set: setD, max: 2, placeholder: 'DD' }].map(f => (
                        <div key={f.label}>
                            <p className="text-xs font-semibold text-neutral-400 mb-1">{f.label}</p>
                            <input type="number" value={f.state} onChange={e => f.set(e.target.value)} placeholder={f.placeholder} maxLength={f.max} className="w-full bg-neutral-50 rounded-xl border border-neutral-200 py-3 text-center text-base font-bold text-neutral-900 outline-none" />
                        </div>
                    ))}
                </div>

                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Cycle Length</p>
                <div className="grid grid-cols-5 gap-2 mb-2">
                    {['21', '25', '28', '30', '35'].map(d => (
                        <button key={d} onClick={() => setCycle(d)} className={`py-3 rounded-xl text-sm font-bold border-2 transition-all ${cycle === d ? 'border-pink-500 bg-pink-50 text-pink-600' : 'border-neutral-200 bg-neutral-50 text-neutral-500'}`}>
                            {d} days
                        </button>
                    ))}
                </div>
                <p className="text-xs text-neutral-400 mb-4">Most women have a 28-day cycle.</p>

                <button onClick={calc} disabled={!can} className={`btn w-full ${can ? 'bg-pink-500 hover:bg-pink-600 text-white' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed'}`}>
                    Calculate Due Date
                </button>
                {result && (
                    <button onClick={reset} className="block mx-auto mt-3 text-sm font-semibold text-neutral-400 hover:text-neutral-600">
                        Reset
                    </button>
                )}
            </div>

            {result && (
                <>
                    <div className="card p-6 text-center bg-pink-50 border-pink-200">
                        <p className="text-xs font-bold text-pink-700 uppercase tracking-wider mb-2">Estimated Due Date</p>
                        <p className="text-xl font-extrabold text-pink-900">{formatDate(result.due)}</p>
                        <div className="mt-3">
                            <p className="text-xs font-bold text-pink-600 mb-1">Conception</p>
                            <p className="text-sm font-semibold text-pink-800">{formatDate(result.conc)}</p>
                        </div>
                    </div>

                    <div className="card p-5">
                        <p className="text-base font-bold text-neutral-900 mb-4 text-center">Your Pregnancy Progress</p>
                        <div className="grid grid-cols-4 gap-2 mb-4">
                            {[{ v: result.wp, u: 'weeks' }, { v: result.dr, u: 'days' }, { v: `T${result.tri}`, u: 'trimester' }, { v: result.wd, u: 'weeks left' }].map(f => (
                                <div key={f.u} className="text-center">
                                    <p className="text-xl font-extrabold text-neutral-900">{f.v}</p>
                                    <p className="text-xs font-semibold text-neutral-400">{f.u}</p>
                                </div>
                            ))}
                        </div>
                        <div className="h-2 bg-neutral-100 rounded-full overflow-hidden mb-1">
                            <div className="h-full bg-pink-500 rounded-full transition-all" style={{ width: `${Math.min(100, (result.wp / 40) * 100)}%` }} />
                        </div>
                        <p className="text-xs font-semibold text-neutral-400 text-center">{Math.round((result.wp / 40) * 100)}% completed</p>
                    </div>

                    {result.up.length > 0 && (
                        <div className="card p-5">
                            <p className="text-base font-bold text-neutral-900 mb-3">Upcoming Milestones</p>
                            <div className="space-y-2">
                                {result.up.map((m) => (
                                    <div key={m.week} className="flex items-start gap-3">
                                        <span className="w-2 h-2 rounded-full bg-pink-500 mt-1.5 flex-shrink-0" />
                                        <div>
                                            <p className="text-sm font-bold text-neutral-900">Week {m.week}</p>
                                            <p className="text-sm text-neutral-500">{m.label}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </>
            )}

            <div className="card p-5 bg-amber-50 border-amber-200">
                <p className="text-base font-bold text-amber-800 mb-2">How It Works</p>
                <p className="text-sm text-amber-800 leading-relaxed">Uses Naegele's Rule: Due Date = LMP + 280 days, adjusted by cycle length. Only ~5% of babies are born on exact due date.</p>
            </div>
        </div>
    );
}