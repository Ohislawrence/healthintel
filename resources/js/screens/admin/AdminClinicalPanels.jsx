import React, { useEffect, useState } from 'react';
import api from '../../lib/api';

export default function AdminClinicalPanels() {
  const [panels, setPanels] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({});

  useEffect(() => { loadPanels(); }, []);

  const loadPanels = async () => {
    try {
      setLoading(true);
      const res = await api.get('/admin/clinical/panels');
      const data = res.data?.data || res.data || [];
      setPanels(Array.isArray(data) ? data : (data.data || []));
    } finally { setLoading(false); }
  };

  const resetForm = () => {
    setForm({
      slug: '', name: '', description: '',
      test_codes: '', layout_sections: '',
    });
    setEditing(null);
    setShowForm(true);
  };

  const openEdit = (p) => {
    setForm({
      slug: p.slug,
      name: p.name,
      description: p.description || '',
      test_codes: Array.isArray(p.test_codes) ? p.test_codes.join(', ') : (p.test_codes || ''),
      layout_sections: p.layout_sections ? JSON.stringify(p.layout_sections, null, 2) : '',
    });
    setEditing(p.id);
    setShowForm(true);
  };

  const save = async () => {
    try {
      const payload = {
        ...form,
        test_codes: form.test_codes.split(',').map(s => s.trim()).filter(Boolean),
        layout_sections: form.layout_sections ? JSON.parse(form.layout_sections) : null,
      };
      if (editing) {
        await api.put(`/admin/clinical/panels/${editing}`, payload);
      } else {
        await api.post('/admin/clinical/panels', payload);
      }
      setShowForm(false);
      loadPanels();
    } catch (e) {
      alert('Failed to save: ' + (e.response?.data?.message || e.message));
    }
  };

  const updateStatus = async (id, status) => {
    await api.put(`/admin/clinical/panels/${id}`, { status });
    loadPanels();
  };

  const remove = async (id) => {
    if (!confirm('Delete this panel?')) return;
    await api.delete(`/admin/clinical/panels/${id}`);
    loadPanels();
  };

  if (showForm) {
    return (
      <div className="space-y-4">
        <button onClick={() => setShowForm(false)} className="text-sm text-teal-600 hover:text-teal-800">&larr; Back to list</button>
        <h2 className="text-xl font-bold">{editing ? 'Edit' : 'Add'} Panel</h2>
        <div className="bg-white rounded-xl border border-gray-200 p-5 grid grid-cols-2 gap-3 text-sm">
          <div><label className="text-xs text-gray-500">Slug</label><input className="w-full border rounded p-1.5" value={form.slug} onChange={e => setForm({...form, slug: e.target.value})} /></div>
          <div><label className="text-xs text-gray-500">Name</label><input className="w-full border rounded p-1.5" value={form.name} onChange={e => setForm({...form, name: e.target.value})} /></div>
          <div className="col-span-2"><label className="text-xs text-gray-500">Description</label><textarea className="w-full border rounded p-1.5" rows={2} value={form.description} onChange={e => setForm({...form, description: e.target.value})} /></div>
          <div className="col-span-2"><label className="text-xs text-gray-500">Test Codes (comma-separated)</label><input className="w-full border rounded p-1.5" value={form.test_codes} onChange={e => setForm({...form, test_codes: e.target.value})} placeholder="haemoglobin, wbc, rbc, platelets" /></div>
          <div className="col-span-2"><label className="text-xs text-gray-500">Layout Sections (JSON)</label><textarea className="w-full border rounded p-1.5 font-mono text-xs" rows={4} value={form.layout_sections} onChange={e => setForm({...form, layout_sections: e.target.value})} placeholder='[{"title":"Red Cells","tests":["haemoglobin","pcv"]}]' /></div>
          <div className="col-span-2"><button onClick={save} className="px-4 py-2 bg-teal-600 text-white rounded text-sm hover:bg-teal-700">Save</button></div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <h2 className="text-xl font-bold text-gray-900">Clinical Panels</h2>
        <button onClick={resetForm} className="px-3 py-1.5 bg-teal-600 text-white text-sm rounded hover:bg-teal-700">+ Add Panel</button>
      </div>

      {loading && <div className="flex justify-center py-8"><div className="h-6 w-6 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" /></div>}

      <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table className="w-full text-sm">
          <thead><tr className="text-left text-xs text-gray-400 uppercase bg-gray-50">
            <th className="px-3 py-2">Name</th><th className="px-3 py-2">Slug</th>
            <th className="px-3 py-2">Tests</th><th className="px-3 py-2">Version</th>
            <th className="px-3 py-2">Status</th><th className="px-3 py-2">Actions</th>
          </tr></thead>
          <tbody className="divide-y divide-gray-100">
            {panels.map(p => (
              <tr key={p.id} className="hover:bg-gray-50">
                <td className="px-3 py-2 font-medium">{p.name}</td>
                <td className="px-3 py-2 text-xs font-mono">{p.slug}</td>
                <td className="px-3 py-2 text-xs text-gray-500">{(p.test_codes || []).length} tests</td>
                <td className="px-3 py-2 text-xs">v{p.version || 1}</td>
                <td className="px-3 py-2">
                  <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                    p.status === 'approved' ? 'bg-green-50 text-green-600' :
                    p.status === 'deprecated' ? 'bg-red-50 text-red-600' :
                    'bg-yellow-50 text-yellow-600'
                  }`}>{p.status}</span>
                </td>
                <td className="px-3 py-2 flex gap-1">
                  <button onClick={() => openEdit(p)} className="text-xs text-teal-600 hover:text-teal-800">Edit</button>
                  {p.status !== 'approved' && <button onClick={() => updateStatus(p.id, 'approved')} className="text-xs text-green-600 hover:text-green-800">Approve</button>}
                  {p.status !== 'deprecated' && <button onClick={() => updateStatus(p.id, 'deprecated')} className="text-xs text-amber-600 hover:text-amber-800">Deprecate</button>}
                  <button onClick={() => remove(p.id)} className="text-xs text-red-500 hover:text-red-700">Del</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}