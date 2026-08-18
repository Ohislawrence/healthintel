import React, { useState, useMemo, useCallback, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

const CYCLE_LENGTHS = [21, 25, 28, 30, 35];
const PERIOD_LENGTHS = [3, 4, 5, 6, 7];
const CYCLE_PHASES = [
  { name: 'Menstrual', color: '#DC2626', range: [1, 5] },
  { name: 'Follicular', color: '#F59E0B', range: [6, 13] },
  { name: 'Ovulation', color: '#8B5CF6', range: [14, 16] },
  { name: 'Luteal', color: '#EC4899', range: [17, 28] },
];
function getPhase(day, phases) {
  for (const p of phases) { if (day >= p.range[0] && day <= p.range[1]) return p; }
  return null;
}
function formatShort(d) { return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }); }

export default function PeriodTracker() {
  const [log, setLog] = useState([]);
  const [cycleLen, setCycleLen] = useState(28);
  const [periodLen, setPeriodLen] = useState(5);
  const today = new Date();

  // Load persisted period data on mount
  useEffect(() => {
    (async () => {
      try {
        const res = await api.get('/health-metrics/today');
        const period = res?.data?.trackers?.period;
        if (period) {
          if (Array.isArray(period.period_days)) setLog(period.period_days);
          if (period.cycle_length) setCycleLen(period.cycle_length);
          if (period.period_length) setPeriodLen(period.period_length);
        }
      } catch {}
    })();
  }, []);

  const persist = useCallback(async (nextLog, nextCycleLen, nextPeriodLen) => {
    try {
      await api.post('/health-metrics/sync', {
        date: new Date().toISOString().split('T')[0],
        data: {
          period: {
            period_days: nextLog,
            cycle_length: nextCycleLen,
            period_length: nextPeriodLen,
          },
        },
      });
    } catch {}
  }, []);

  const toggleDate = (ds) => {
    const next = log.includes(ds) ? log.filter(d => d !== ds) : [...log, ds];
    setLog(next);
    persist(next, cycleLen, periodLen);
  };

  const setCycleLength = (d) => { setCycleLen(d); persist(log, d, periodLen); };
  const setPeriodLength = (d) => { setPeriodLen(d); persist(log, cycleLen, d); };

  const daysInMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).getDay();
  const monthLabel = today.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

  const calendarDays = [];
  for (let i = 0; i < firstDay; i++) calendarDays.push(null);
  for (let d = 1; d <= daysInMonth; d++) calendarDays.push(d);

  const sortedLog = useMemo(() => [...log].sort(), [log]);
  const lastPeriod = sortedLog.length > 0 ? sortedLog[sortedLog.length - 1] : null;

  const nextPeriod = useMemo(() => {
    if (!lastPeriod) return null;
    const d = new Date(lastPeriod);
    d.setDate(d.getDate() + cycleLen);
    return d;
  }, [lastPeriod, cycleLen]);

  const ovulation = useMemo(() => {
    if (!nextPeriod) return null;
    const d = new Date(nextPeriod);
    d.setDate(d.getDate() - 14);
    return d;
  }, [nextPeriod]);

  const fertileStart = useMemo(() => {
    if (!nextPeriod) return null;
    const d = new Date(nextPeriod);
    d.setDate(d.getDate() - 16);
    return d;
  }, [nextPeriod]);

  const fertileEnd = useMemo(() => {
    if (!nextPeriod) return null;
    const d = new Date(nextPeriod);
    d.setDate(d.getDate() - 12);
    return d;
  }, [nextPeriod]);

  const cycleDay = useMemo(() => {
    if (!lastPeriod) return null;
    const diff = Math.round((today.getTime() - new Date(lastPeriod).getTime()) / 86400000);
    if (diff < 0 || diff >= cycleLen) return null;
    return diff + 1;
  }, [lastPeriod, cycleLen]);

  const currentPhase = cycleDay ? getPhase(cycleDay, CYCLE_PHASES) : null;

  return (
    <div className="space-y-5">
      <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
      <div>
        <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Period & Cycle Tracker</p>
        <p className="text-sm text-neutral-500 mt-0.5">Log periods, track cycles, predict ovulation & fertile windows.</p>
      </div>

      {/* Settings */}
      <div className="grid grid-cols-2 gap-3">
        <div className="card p-4">
          <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Cycle Length</p>
          <div className="flex flex-wrap gap-1.5">
            {CYCLE_LENGTHS.map(d => (
              <button key={d} onClick={() => setCycleLength(d)} className={`px-3 py-2 rounded-lg text-xs font-bold border ${cycleLen === d ? 'border-pink-500 bg-pink-50 text-pink-600' : 'border-neutral-200 bg-neutral-50 text-neutral-500'}`}>{d} days</button>
            ))}
          </div>
        </div>
        <div className="card p-4">
          <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Period Length</p>
          <div className="flex flex-wrap gap-1.5">
            {PERIOD_LENGTHS.map(d => (
              <button key={d} onClick={() => setPeriodLength(d)} className={`px-3 py-2 rounded-lg text-xs font-bold border ${periodLen === d ? 'border-pink-500 bg-pink-50 text-pink-600' : 'border-neutral-200 bg-neutral-50 text-neutral-500'}`}>{d} days</button>
            ))}
          </div>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-3 gap-3">
        <div className="card p-3 text-center"><p className="text-xl font-extrabold text-pink-600">{log.length}</p><p className="text-[10px] font-bold text-neutral-400 uppercase">Days Logged</p></div>
        <div className="card p-3 text-center"><p className="text-xl font-extrabold text-pink-600">{cycleLen}</p><p className="text-[10px] font-bold text-neutral-400 uppercase">Cycle (Days)</p></div>
        <div className="card p-3 text-center"><p className="text-xl font-extrabold text-pink-600">{periodLen}</p><p className="text-[10px] font-bold text-neutral-400 uppercase">Period (Days)</p></div>
      </div>

      {/* Current Phase */}
      {currentPhase && (
        <div className="card p-4" style={{ borderLeftWidth: 4, borderLeftColor: currentPhase.color }}>
          <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Current Phase</p>
          <div className="flex items-center gap-2 mb-3">
            <span className="w-3 h-3 rounded-full" style={{ backgroundColor: currentPhase.color }} />
            <span className="text-base font-bold" style={{ color: currentPhase.color }}>{currentPhase.name} Phase (Day {cycleDay})</span>
          </div>
          <div className="h-2 bg-neutral-100 rounded-full overflow-hidden mb-2"><div className="h-full rounded-full" style={{ backgroundColor: currentPhase.color, width: `${(cycleDay / cycleLen) * 100}%` }} /></div>
          <p className="text-xs text-neutral-400">{cycleDay > cycleLen - 7 ? 'Next period expected soon' : `${cycleLen - cycleDay} days until next period`}</p>
        </div>
      )}

      {/* Predictions */}
      {nextPeriod && (
        <div className="card p-5">
          <p className="text-base font-bold text-neutral-900 mb-4">Predictions</p>
          <div className="space-y-3">
            <div className="flex justify-between items-center"><span className="text-sm font-semibold text-neutral-500">Next Period</span><span className="text-sm font-bold text-neutral-900">{formatShort(nextPeriod)}</span></div>
            {ovulation && <div className="flex justify-between items-center"><span className="text-sm font-semibold text-neutral-500">Ovulation</span><span className="text-sm font-bold text-purple-600">{formatShort(ovulation)}</span></div>}
            {fertileStart && fertileEnd && <div><span className="text-sm font-semibold text-neutral-500">Fertile Window</span><p className="text-sm font-bold text-neutral-900 mt-1">{formatShort(fertileStart)} – {formatShort(fertileEnd)}</p></div>}
          </div>
        </div>
      )}

      {/* Calendar */}
      <div className="card p-5">
        <p className="text-base font-bold text-neutral-900 mb-4 text-center">{monthLabel}</p>
        <div className="grid grid-cols-7 mb-2">
          {['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].map(d => <p key={d} className="text-center text-[10px] font-bold text-neutral-400">{d}</p>)}
        </div>
        <div className="grid grid-cols-7 gap-1">
          {calendarDays.map((day, i) => {
            if (!day) return <div key={`e-${i}`} className="aspect-square" />;
            const ds = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            const isToday = day === today.getDate();
            const isLogged = log.includes(ds);
            let isPredicted = false;
            if (nextPeriod && lastPeriod) {
              const cd = new Date(today.getFullYear(), today.getMonth(), day);
              const dslp = Math.round((cd.getTime() - new Date(lastPeriod).getTime()) / 86400000);
              isPredicted = dslp >= cycleLen && dslp < cycleLen + periodLen;
            }
            return (
              <button key={day} onClick={() => toggleDate(ds)} className={`aspect-square rounded-lg flex flex-col items-center justify-center text-xs font-semibold transition-all ${isToday ? 'bg-pink-50' : ''} ${isLogged ? 'bg-pink-100 border-2 border-pink-500 text-pink-700' : isPredicted ? 'border border-dashed border-pink-200' : 'hover:bg-neutral-50'} ${!isToday && !isLogged && !isPredicted ? 'text-neutral-600' : ''}`}>
                {day}{isLogged && <span className="w-1.5 h-1.5 rounded-full bg-pink-500 mt-0.5" />}
                {isPredicted && !isLogged && <span className="w-1.5 h-1.5 rounded-full bg-pink-200 mt-0.5" />}
              </button>
            );
          })}
        </div>
        <div className="flex justify-center gap-4 mt-3 text-xs">
          <span className="flex items-center gap-1"><span className="w-2 h-2 rounded-full bg-pink-500" /> Period</span>
          <span className="flex items-center gap-1"><span className="w-2 h-2 rounded-full bg-pink-200" /> Predicted</span>
          {ovulation && <span className="flex items-center gap-1"><span className="w-2 h-2 rounded-full bg-purple-500" /> Ovulation {formatShort(ovulation)}</span>}
        </div>
      </div>

      {/* Phase Legend */}
      <div className="card p-5">
        <p className="text-base font-bold text-neutral-900 mb-3">Cycle Phases</p>
        <div className="space-y-2">
          {CYCLE_PHASES.map(p => (
            <div key={p.name} className="flex items-center gap-3"><span className="w-2.5 h-2.5 rounded-full flex-shrink-0" style={{ backgroundColor: p.color }} /><span className="text-sm font-semibold text-neutral-700 flex-1">{p.name}</span><span className="text-xs text-neutral-400">Days {p.range[0]}–{p.range[1]}</span></div>
          ))}
        </div>
      </div>
    </div>
  );
}