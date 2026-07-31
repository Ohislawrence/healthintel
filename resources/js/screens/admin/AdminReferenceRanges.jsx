import React, { useEffect, useState } from 'react';
import api from '../../lib/api';

export default function AdminReferenceRanges() {
  const [ranges, setRanges] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [category, setCategory] = useState('');
  const [categories, setCategories] = useState([]);
  const [editing, setEditing] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({});

  useEffect(() => { loadCategories(); }, []);
  useEffect(() => { loadRanges(); }, [page, search, category]);

  const loadCategories = async () => {
    try {
      const res = await api.get('/admin/clinical/ranges/categories');
      setCategories(res.data?.categories || []);
    } catch (e) {}
  };

  const loadRanges = async () => {
    try {
      setLoading(true);
      const params = { page, per_page: 20 };
      if (search) params.search = search;
      if (category) params.category = category;
      const res = await api.get('/admin/clinical/ranges', { params });
      const data = res.data?.data || res.data || [];
      setRanges(Array.isArray(data) ? data : (data.data || []));
    } catch (e) {
      console.error(e);
    } finally { setLoading(false); }
  };

  const resetForm = () => {
    setForm({
      test_code: '', test_name: '', category: '', sex: 'all',
      range_low: '', range_high: '', critical_low: '', critical_high: '',
      unit: '', source: '', age_min_years: '', age_max_years: '',
      pregnancy_applicable: false, pregnancy_trimester: '',
    });
    setEditing(null);
    setShowForm(true);
  };

  const openEdit = (r) => {
    setForm({
      ...r,
      range_low: String(r.range_low ?? ''), range_high: String(r.range_high ?? ''),
      critical_low: String(r.critical_low ?? ''), critical_high: String(r.critical_high ?? ''),
      age_min_years: String(r.age_min_years ?? ''), age_max_years: String(r.age_max_years ?? ''),
      pregnancy_trimester: String(r.pregnancy_trimester ?? ''),
    });
    setEditing(r.id);
    setShowForm(true);
  };

  const save = async () => {
    try {
      const payload = {
        ...form,
        range_low: parseFloat(form.range_low), range_high: parseFloat(form.range_high),
        critical_low: form.critical_low ? parseFloat(form.critical_low) : null,
        critical_high: form.critical_high ? parseFloat(form.critical_high) : null,
        age_min_years: form.age_min_years ? parseFloat(form.age_min_years) : null,
        age_max_years: form.age_max_years ? parseFloat(form.age_max_years) : null,
        pregnancy_trimester: form.pregnancy_trimester ? parseInt(form.pregnancy_trimester) : null,
      };
      if (editing) {
        await api.put(`/admin/clinical/ranges/${editing}`, payload);
      } else {
        await api.post('/admin/clinical/ranges', payload);
      }
      setShowForm(false);
      loadRanges();
      loadCategories();
    } catch (e) {
      alert('Failed to save: ' + (e.response?.data?.message || e.message));
    }
  };

  const remove = async (id) => {
    if (!confirm('Delete this reference range?')) return;
    await api.delete(`/admin/clinical/ranges/${id}`);
    loadRanges();
  };

  if (showForm) {
    return (
      <div className="space-y-4">
        <button onClick={() => setShowForm(false)} className="text-sm text-teal-600 hover:text-teal-800">&larr; Back to list</button>
        <h2 className="text-xl font-bold">{editing ? 'Edit' : 'Add'} Reference Range</h2>
        <div className="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-2 gap-3 text-sm">
          <div><label className="text-xs text-gray-500">Test Code</label><input className="w-full border rounded p-1.5" value={form.test_code} onChange={e => setForm({...form, test_code: e.target.value})} /></div>
          <div><label className="text-xs text-gray-500">Test Name</label><input className="w-full border rounded p-1.5" value={form.test_name} onChange={e => setForm({...form, test_name: e.target.value})} /></div>
          <div><label className="text-xs text-gray-500">Category</label><input className="w-full border rounded p-1.5" value={form.category} onChange={e => setForm({...form, category: e.target.value})} /></div>
          <div><label className="text-xs text-gray-500">Sex</label><select className="w-full border rounded p-1.5" value={form.sex} onChange={e => setForm({...form, sex: e.target.value})}><option value="all">All</option><option value="male">Male</option><option value="female">Female</option></select></div>
          <div><label className="text-xs text-gray-500">Range Low</label><input type="number" step="any" className="w-full border rounded p-1.5" value={form.range_low} onChange={e => setForm({...form, range_low: e.target.value})} /></div>
          <div><label className="text-xs text-gray-500">Range High</label><input type="number" step="any" className="w-full border rounded p-1.5" value={form.range_high} onChange={e => setForm({...form, range_high: e.target.value})} /></div>
          <div><label className="text-xs text-gray-500">Critical Low</label><input type="number" step="any" className="w-full border rounded p-1.5" value={form.critical_low} onChange={e => setForm({...form, critical_low: e.target.value})} /></div>
          <div><label className="text-xs text-gray-500">Critical High</label><input type="number" step="any" className="w-full border rounded p-1.5" value={form.critical_high} onChange={e => setForm({...form, critical_high: e.target.value})} /></div>
          <div><label className="text-xs text-gray-500">Unit</label><input className="w-full border rounded p-1.5" value={form.unit} onChange={e => setForm({...form, unit: e.target.value})} /></div>
          <div><label className="text-xs text-gray-500">Source</label><input className="w-full border rounded p-1.5" value={form.source} onChange={e => setForm({...form, source: e.target.value})} /></div>
          <div><label className="text-xs text-gray-500">Age Min (years)</label><input type="number" step="any" className="w-full border rounded p-1.5" value={form.age_min_years} onChange={e => setForm({...form, age_min_years: e.target.value})} /></div>
          <div><label className="text-xs text-gray-500">Age Max (years)</label><input type="number" step="any" className="w-full border rounded p-1.5" value={form.age_max_years} onChange={e => setForm({...form, age_max_years: e.target.value})} /></div>
          <div><label className="flex items-center gap-2 text-xs text-gray-500"><input type="checkbox" checked={form.pregnancy_applicable} onChange={e => setForm({...form, pregnancy_applicable: e.target.checked})} /> Pregnancy Applicable</label></div>
          <div><label className="text-xs text-gray-500">Trimester (1-3)</label><input type="number" className="w-full border rounded p-1.5" value={form.pregnancy_trimester} onChange={e => setForm({...form, pregnancy_trimester: e.target.value})} /></div>
          <div className="col-span-2">
            <button onClick={save} className="px-4 py-2 bg-teal-600 text-white rounded text-sm hover:bg-teal-700">Save</button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h2 className="text-xl font-bold text-gray-900">Reference Ranges</h2>
        <button onClick={resetForm} className="px-3 py-1.5 bg-teal-600 text-white text-sm rounded hover:bg-teal-700">+ Add Range</button>
      </div>

      <div className="flex gap-2">
        <input placeholder="Search by name or code..." className="border rounded px-3 py-1.5 text-sm flex-1" value={search} onChange={e => { setSearch(e.target.value); setPage(1); }} />
        <select className="border rounded px-3 py-1.5 text-sm" value={category} onChange={e => { setCategory(e.target.value); setPage(1); }}>
          <option value="">All Categories</option>
          {categories.map(c => <option key={c} value={c}>{c}</option>)}
        </select>
      </div>

      {loading && <div className="flex justify-center py-8"><div className="h-6 w-6 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" /></div>}

      <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table className="w-full text-sm">
          <thead><tr className="text-left text-xs text-gray-400 uppercase bg-gray-50">
            <th className="px-3 py-2">Test</th><th className="px-3 py-2">Sex</th><th className="px-3 py-2">Range</th>
            <th className="px-3 py-2">Critical</th><th className="px-3 py-2">Unit</th><th className="px-3 py-2">Category</th>
            <th className="px-3 py-2">Age</th><th className="px-3 py-2">Source</th><th className="px-3 py-2">Actions</th>
          </tr></thead>
          <tbody className="divide-y divide-gray-100">
            {ranges.map(r => (
              <tr key={r.id} className="hover:bg-gray-50">
                <td className="px-3 py-2 font-medium">{r.test_name}</td>
                <td className="px-3 py-2 text-xs">{r.sex}</td>
                <td className="px-3 py-2 text-xs">{r.range_low} – {r.range_high}</td>
                <td className="px-3 py-2 text-xs">{r.critical_low ?? '-'} / {r.critical_high ?? '-'}</td>
                <td className="px-3 py-2 text-xs">{r.unit}</td>
                <td className="px-3 py-2 text-xs text-gray-500">{r.category}</td>
                <td className="px-3 py-2 text-xs">{r.age_min_years ?? '-'} – {r.age_max_years ?? '-'}</td>
                <td className="px-3 py-2 text-xs text-gray-400">{r.source || '-'}</td>
                <td className="px-3 py-2 flex gap-1">
                  <button onClick={() => openEdit(r)} className="text-xs text-teal-600 hover:text-teal-800">Edit</button>
                  <button onClick={() => remove(r.id)} className="text-xs text-red-500 hover:text-red-700">Del</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="flex gap-2 justify-center">
        {[1,2,3,4,5].map(p => (
          <button key={p} onClick={() => setPage(p)} className={`px-3 py-1 rounded text-xs ${p === page ? 'bg-teal-600 text-white' : 'bg-white border text-gray-700'}`}>{p}</button>
        ))}
      </div>
    </div>
  );
}