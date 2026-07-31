import React, { useEffect, useState } from 'react';
import api from '../../lib/api';

export default function AdminMedicationEffects() {
  const [effects, setEffects] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [editing, setEditing] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({});

  useEffect(() => { loadEffects(); }, [search]);

  const loadEffects = async () => {
    try {
      setLoading(true);
      const params = {};
      if (search) params.search = search;
      const res = await api.get('/admin/clinical/medication-effects', { params });
      const data = res.data?.data || res.data || [];
      setEffects(Array.isArray(data) ? data : (data.data || []));
    } finally { setLoading(false); }
  };

  const resetForm = () => {
    setForm({
      medication_slug: '', medication_name: '', test_code: '',
      expected_effect: 'no_effect', severity: 'mild', clinician_note: '',
    });
    setEditing(null);
    setShowForm(true);
  };

  const openEdit = (e) => {
    setForm({
      medication_slug: e.medication_slug,
      medication_name: e.medication_name,
      test_code: e.test_code,
      expected_effect: e.expected_effect || 'no_effect',
      severity: e.severity || 'mild',
      clinician_note: e.clinician_note || '',
    });
    setEditing(e.id);
    setShowForm(true);
  };

  const save = async () => {
    try {
      if (editing) {
        await api.put(`/admin/clinical/medication-effects/${editing}`, form);
      } else {
        await api.post('/admin/clinical/medication-effects', form);
      }
      setShowForm(false);
      loadEffects();
    } catch (e) {
      alert('Failed to save: ' + (e.response?.data?.message || e.message));
    }
  };

  const remove = async (id) => {
    if (!confirm('Delete this medication effect?')) return;
    await api.delete(`/admin/clinical/medication-effects/${id}`);
    loadEffects();
  };

  if (showForm) {
    return (
      <div className="space-y-4">
        <button onClick={() => setShowForm(false)} className="text-sm text-teal-600 hover:text-teal-800">&larr; Back to list</button>
        <h2 className="text-xl font-bold">{editing ? 'Edit' : 'Add'} Medication Effect</h2>
        <div className="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-2 gap-3 text-sm">
          <div><label className="text-xs text-gray-500">Medication Slug</label><input className="w-full border rounded p-1.5" value={form.medication_slug} onChange={e => setForm({...form, medication_slug: e.target.value})} placeholder="metformin" /></div>
          <div><label className="text-xs text-gray-500">Medication Name</label><input className="w-full border rounded p-1.5" value={form.medication_name} onChange={e => setForm({...form, medication_name: e.target.value})} placeholder="Metformin" /></div>
          <div><label className="text-xs text-gray-500">Test Code</label><input className="w-full border rounded p-1.5" value={form.test_code} onChange={e => setForm({...form, test_code: e.target.value})} placeholder="glucose" /></div>
          <div><label className="text-xs text-gray-500">Expected Effect</label>
            <select className="w-full border rounded p-1.5" value={form.expected_effect} onChange={e => setForm({...form, expected_effect: e.target.value})}>
              <option value="no_effect">No Effect</option><option value="elevates">Elevates</option>
              <option value="lowers">Lowers</option><option value="variable">Variable</option>
            </select>
          </div>
          <div><label className="text-xs text-gray-500">Severity</label>
            <select className="w-full border rounded p-1.5" value={form.severity} onChange={e => setForm({...form, severity: e.target.value})}>
              <option value="mild">Mild</option><option value="moderate">Moderate</option><option value="significant">Significant</option>
            </select>
          </div>
          <div />
          <div className="col-span-2"><label className="text-xs text-gray-500">Clinician Note</label><textarea className="w-full border rounded p-1.5" rows={2} value={form.clinician_note} onChange={e => setForm({...form, clinician_note: e.target.value})} placeholder="e.g. Metformin reduces hepatic glucose production. Lower glucose values are expected." /></div>
          <div className="col-span-2"><button onClick={save} className="px-4 py-2 bg-teal-600 text-white rounded text-sm hover:bg-teal-700">Save</button></div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h2 className="text-xl font-bold text-gray-900">Medication Effects</h2>
        <button onClick={resetForm} className="px-3 py-1.5 bg-teal-600 text-white text-sm rounded hover:bg-teal-700">+ Add Effect</button>
      </div>

      <div className="flex gap-2">
        <input placeholder="Search by medication or test code..." className="border rounded px-3 py-1.5 text-sm flex-1" value={search} onChange={e => { setSearch(e.target.value); }} />
      </div>

      {loading && <div className="flex justify-center py-8"><div className="h-6 w-6 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" /></div>}

      <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table className="w-full text-sm">
          <thead><tr className="text-left text-xs text-gray-400 uppercase bg-gray-50">
            <th className="px-3 py-2">Medication</th><th className="px-3 py-2">Test Code</th>
            <th className="px-3 py-2">Effect</th><th className="px-3 py-2">Severity</th>
            <th className="px-3 py-2">Note</th><th className="px-3 py-2">Actions</th>
          </tr></thead>
          <tbody className="divide-y divide-gray-100">
            {effects.map(e => (
              <tr key={e.id} className="hover:bg-gray-50">
                <td className="px-3 py-2 font-medium">{e.medication_name}</td>
                <td className="px-3 py-2 text-xs font-mono">{e.test_code}</td>
                <td className="px-3 py-2">
                  <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                    e.expected_effect === 'elevates' ? 'bg-red-50 text-red-600' :
                    e.expected_effect === 'lowers' ? 'bg-green-50 text-green-600' :
                    e.expected_effect === 'variable' ? 'bg-purple-50 text-purple-600' :
                    'bg-gray-50 text-gray-500'
                  }`}>{e.expected_effect}</span>
                </td>
                <td className="px-3 py-2">
                  <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                    e.severity === 'significant' ? 'bg-red-50 text-red-600' :
                    e.severity === 'moderate' ? 'bg-amber-50 text-amber-600' :
                    'bg-blue-50 text-blue-600'
                  }`}>{e.severity}</span>
                </td>
                <td className="px-3 py-2 text-xs text-gray-500 max-w-xs truncate">{e.clinician_note || '-'}</td>
                <td className="px-3 py-2 flex gap-1">
                  <button onClick={() => openEdit(e)} className="text-xs text-teal-600 hover:text-teal-800">Edit</button>
                  <button onClick={() => remove(e.id)} className="text-xs text-red-500 hover:text-red-700">Del</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}