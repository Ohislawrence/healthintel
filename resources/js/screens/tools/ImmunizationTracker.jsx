import React, { useState, useCallback, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

const VACCINE_SCHEDULE = [
  { name: 'BCG', full: 'Bacillus Calmette-Guérin (Tuberculosis)', ageWeeks: 0, ageLabel: 'At Birth' },
  { name: 'OPV 0', full: 'Oral Polio Vaccine (Birth dose)', ageWeeks: 0, ageLabel: 'At Birth' },
  { name: 'HepB Birth', full: 'Hepatitis B (Birth dose)', ageWeeks: 0, ageLabel: 'At Birth' },
  { name: 'OPV 1', full: 'Oral Polio Vaccine', ageWeeks: 6, ageLabel: '6 weeks' },
  { name: 'Pentavalent 1', full: 'DPT-HepB-Hib', ageWeeks: 6, ageLabel: '6 weeks' },
  { name: 'PCV 1', full: 'Pneumococcal Conjugate Vaccine', ageWeeks: 6, ageLabel: '6 weeks' },
  { name: 'Rota 1', full: 'Rotavirus Vaccine', ageWeeks: 6, ageLabel: '6 weeks' },
  { name: 'IPV 1', full: 'Inactivated Polio Vaccine', ageWeeks: 6, ageLabel: '6 weeks' },
  { name: 'OPV 2', full: 'Oral Polio Vaccine', ageWeeks: 10, ageLabel: '10 weeks' },
  { name: 'Pentavalent 2', full: 'DPT-HepB-Hib', ageWeeks: 10, ageLabel: '10 weeks' },
  { name: 'PCV 2', full: 'Pneumococcal Conjugate Vaccine', ageWeeks: 10, ageLabel: '10 weeks' },
  { name: 'Rota 2', full: 'Rotavirus Vaccine', ageWeeks: 10, ageLabel: '10 weeks' },
  { name: 'OPV 3', full: 'Oral Polio Vaccine', ageWeeks: 14, ageLabel: '14 weeks' },
  { name: 'Pentavalent 3', full: 'DPT-HepB-Hib', ageWeeks: 14, ageLabel: '14 weeks' },
  { name: 'PCV 3', full: 'Pneumococcal Conjugate Vaccine', ageWeeks: 14, ageLabel: '14 weeks' },
  { name: 'IPV 2', full: 'Inactivated Polio Vaccine', ageWeeks: 14, ageLabel: '14 weeks' },
  { name: 'Vitamin A 1', full: 'Vitamin A Supplement', ageWeeks: 26, ageLabel: '6 months' },
  { name: 'Measles 1', full: 'Measles Vaccine (1st dose)', ageWeeks: 39, ageLabel: '9 months' },
  { name: 'Yellow Fever', full: 'Yellow Fever Vaccine', ageWeeks: 39, ageLabel: '9 months' },
  { name: 'Vitamin A 2', full: 'Vitamin A Supplement', ageWeeks: 52, ageLabel: '12 months' },
  { name: 'Measles 2', full: 'Measles Vaccine (2nd dose)', ageWeeks: 65, ageLabel: '15 months' },
];

const STATUS_COLORS = {
  done: { bg: 'bg-green-50', text: 'text-green-700', border: 'border-green-200', dot: 'bg-green-500', label: 'Done ✓' },
  due: { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', dot: 'bg-amber-500', label: 'Due now' },
  upcoming: { bg: 'bg-neutral-50', text: 'text-neutral-500', border: 'border-neutral-100', dot: 'bg-neutral-300', label: 'Upcoming' },
};

export default function ImmunizationTracker() {
  const [children, setChildren] = useState([]);
  const [records, setRecords] = useState({});
  const [showAdd, setShowAdd] = useState(false);
  const [newName, setNewName] = useState('');
  const [newDob, setNewDob] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    (async () => {
      try {
        const res = await api.get('/health-metrics/today');
        const data = res?.data?.trackers?.immunization || { children: [], records: {} };
        setChildren(data.children || []);
        setRecords(data.records || {});
      } catch {} finally { setLoading(false); }
    })();
  }, []);

  const persist = useCallback(async (c, r) => {
    try { await api.post('/health-metrics/sync', { date: new Date().toISOString().split('T')[0], data: { immunization: { children: c, records: r } } }); } catch {}
  }, []);

  const addChild = () => {
    if (!newName.trim() || !newDob) return;
    const child = { id: Date.now().toString(), name: newName.trim(), dob: newDob };
    const updated = [...children, child];
    setChildren(updated); persist(updated, records);
    setNewName(''); setNewDob(''); setShowAdd(false);
  };

  const toggleVaccine = (childId, vaccineName) => {
    const childRecords = records[childId] || [];
    const exists = childRecords.find(r => r.vaccineName === vaccineName);
    const updated = exists
      ? childRecords.filter(r => r.vaccineName !== vaccineName)
      : [...childRecords, { vaccineName, dateGiven: new Date().toISOString().split('T')[0] }];
    const newRecords = { ...records, [childId]: updated };
    setRecords(newRecords); persist(children, newRecords);
  };

  const getVaccineStatus = (child, vaccine) => {
    const cr = records[child.id] || [];
    if (cr.find(r => r.vaccineName === vaccine.name)) return 'done';
    const dob = new Date(child.dob);
    const dueDate = new Date(dob);
    dueDate.setDate(dueDate.getDate() + vaccine.ageWeeks * 7);
    if (new Date() >= dueDate) return 'due';
    return 'upcoming';
  };

  return (
    <div className="space-y-5">
      <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
      <div>
        <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Immunization Tracker</p>
        <p className="text-sm text-neutral-500 mt-0.5">Track your child's vaccines based on NPHCDA schedule.</p>
      </div>

      {/* Add Child */}
      <div className="card p-5">
        <div className="flex items-center justify-between mb-4">
          <p className="text-base font-bold text-neutral-900">Children</p>
          <button onClick={() => setShowAdd(!showAdd)} className="text-sm font-bold text-purple-600 hover:text-purple-700">{showAdd ? 'Cancel' : '+ Add Child'}</button>
        </div>
        {showAdd && (
          <div className="space-y-3 mb-4 p-4 bg-neutral-50 rounded-xl">
            <input type="text" value={newName} onChange={e => setNewName(e.target.value)} placeholder="Child's name" className="w-full bg-white rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold outline-none" />
            <input type="date" value={newDob} onChange={e => setNewDob(e.target.value)} className="w-full bg-white rounded-xl border border-neutral-200 px-4 py-3 text-sm font-semibold outline-none" />
            <button onClick={addChild} disabled={!newName.trim() || !newDob} className="btn w-full bg-purple-500 hover:bg-purple-600 text-white disabled:bg-neutral-200 disabled:text-neutral-400">Save Child</button>
          </div>
        )}
        {children.length === 0 && !showAdd && <p className="text-sm text-neutral-400 text-center py-4">No children added yet. Tap + Add Child to get started.</p>}
        {children.map(child => {
          let done = 0; let due = 0;
          VACCINE_SCHEDULE.forEach(v => { const s = getVaccineStatus(child, v); if (s === 'done') done++; if (s === 'due') due++; });
          const total = VACCINE_SCHEDULE.length;
          return (
            <div key={child.id} className="bg-neutral-50 rounded-xl p-4 mb-3">
              <div className="flex items-center justify-between mb-2">
                <div>
                  <p className="text-sm font-bold text-neutral-900">{child.name}</p>
                  <p className="text-xs text-neutral-400">Born {new Date(child.dob).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</p>
                </div>
                <span className="text-xs font-bold text-purple-600">{done}/{total} done</span>
              </div>
              <div className="h-1.5 bg-neutral-200 rounded-full overflow-hidden mb-3">
                <div className="h-full bg-purple-500 rounded-full" style={{ width: `${(done / total) * 100}%` }} />
              </div>
              <div className="space-y-1.5">
                {VACCINE_SCHEDULE.map(v => {
                  const status = getVaccineStatus(child, v);
                  const s = STATUS_COLORS[status];
                  return (
                    <button key={v.name} onClick={() => toggleVaccine(child.id, v.name)} className={`w-full flex items-center gap-2 rounded-lg px-3 py-2 text-left text-xs transition-all ${s.bg} border ${s.border}`}>
                      <span className={`w-2 h-2 rounded-full flex-shrink-0 ${s.dot}`} />
                      <span className={`font-bold flex-1 ${s.text}`}>{v.name} — {v.ageLabel}</span>
                      <span className={`font-semibold ${s.text}`}>{s.label}</span>
                    </button>
                  );
                })}
              </div>
            </div>
          );
        })}
      </div>

      <div className="card p-5 bg-amber-50 border-amber-200">
        <p className="text-base font-bold text-amber-800 mb-2">About This Schedule</p>
        <p className="text-sm text-amber-800 leading-relaxed">Based on the Nigerian NPHCDA recommended immunization schedule. Tap any vaccine to mark it as given. Always consult your healthcare provider for personalized vaccination advice.</p>
      </div>
    </div>
  );
}