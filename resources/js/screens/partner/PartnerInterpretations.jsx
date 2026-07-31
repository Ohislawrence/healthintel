import React, { useEffect, useState } from 'react';
import usePartnerAuthStore from '../../stores/partnerAuthStore';

export default function PartnerInterpretations() {
  const { apiGet, apiPost, apiPut } = usePartnerAuthStore();
  const [list, setList] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [page, setPage] = useState(1);
  const [editing, setEditing] = useState(null);
  const [viewing, setViewing] = useState(null);

  useEffect(() => { loadPage(page); }, [page]);

  const loadPage = async (p) => {
    try {
      setLoading(true);
      setError(null);
      const data = await apiGet('/interpretations', { page: p, per_page: 20 });
      const result = data.data || data;
      setList(result.data || result.interpretations || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const viewDetail = async (id) => {
    try {
      const data = await apiGet(`/interpretations/${id}`);
      setViewing(data.data || data);
    } catch (err) {
      setError(err.message);
    }
  };

  const handleEdit = (item) => {
    setEditing({ ...item });
  };

  const saveEdit = async () => {
    try {
      await apiPut(`/interpretations/${editing.id}`, {
        interpretation_text: editing.interpretation_text,
        clinician_interpretation_text: editing.clinician_interpretation_text,
        version_for_patient: editing.version_for_patient,
        override_reason: 'Manual review by partner',
      });
      setEditing(null);
      loadPage(page);
    } catch (err) {
      setError(err.message);
    }
  };

  const toggleVersion = async (id) => {
    try {
      await apiPost(`/interpretations/${id}/toggle-version`);
      loadPage(page);
    } catch (err) {
      setError(err.message);
    }
  };

  const handleSuppress = async (id) => {
    const reason = prompt('Reason for suppressing this interpretation:');
    if (!reason) return;
    try {
      await apiPost(`/interpretations/${id}/suppress`, { reason });
      loadPage(page);
    } catch (err) {
      setError(err.message);
    }
  };

  const statusBadge = (status) => {
    const classes = {
      completed: 'bg-green-50 text-green-600',
      pending: 'bg-yellow-50 text-yellow-600',
      failed: 'bg-red-50 text-red-600',
      suppressed: 'bg-gray-100 text-gray-400 line-through',
    };
    return <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${classes[status] || 'bg-gray-50 text-gray-500'}`}>{status}</span>;
  };

  const escalationBadge = (level) => {
    if (!level) return null;
    const config = {
      urgent: { bg: 'bg-red-100', text: 'text-red-700', emoji: '🔴' },
      flagged: { bg: 'bg-amber-100', text: 'text-amber-700', emoji: '🟠' },
      info: { bg: 'bg-blue-50', text: 'text-blue-600', emoji: 'ℹ️' },
    };
    const c = config[level] || config.info;
    return <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${c.bg} ${c.text}`}>{c.emoji} {level}</span>;
  };

  const confidenceBar = (score) => {
    if (!score && score !== 0) return null;
    const color = score >= 80 ? 'bg-green-500' : score >= 60 ? 'bg-amber-500' : 'bg-red-400';
    return (
      <div className="flex items-center gap-1">
        <div className="w-10 h-1.5 bg-gray-200 rounded-full overflow-hidden">
          <div className={`h-full ${color} rounded-full`} style={{ width: `${score}%` }} />
        </div>
        <span className="text-xs text-gray-400">{score}%</span>
      </div>
    );
  };

  if (viewing) {
    return (
      <div className="space-y-4">
        <button onClick={() => setViewing(null)} className="text-sm text-teal-600 hover:text-teal-800">&larr; Back to list</button>
        <div className="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
          <div className="flex justify-between">
            <h3 className="font-bold text-lg">{viewing.test_name}</h3>
            {statusBadge(viewing.status)}
          </div>
          <div className="grid grid-cols-3 gap-2 text-sm">
            <div><span className="text-gray-400">Value:</span> <strong>{viewing.value} {viewing.unit}</strong></div>
            <div><span className="text-gray-400">Reference:</span> {viewing.reference_range_low} – {viewing.reference_range_high}</div>
            <div>{escalationBadge(viewing.escalation_level)}</div>
          </div>
          {viewing.escalation_level && viewing.escalation_level !== 'info' && (
            <div className={`p-3 rounded-lg text-sm font-medium ${viewing.escalation_level === 'urgent' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-amber-50 text-amber-700 border border-amber-200'}`}>
              {viewing.escalation_message}
            </div>
          )}
          {confidenceBar(viewing.confidence_score)}
          <div className="border-t pt-3">
            <h4 className="font-semibold text-sm mb-2">Patient-Facing Version</h4>
            <p className="text-sm text-gray-700 bg-gray-50 p-3 rounded">{viewing.interpretation_text || 'Not generated'}</p>
          </div>
          <div className="border-t pt-3">
            <h4 className="font-semibold text-sm mb-2">Clinician Version</h4>
            <p className="text-sm text-gray-700 bg-blue-50 p-3 rounded font-mono text-xs">{viewing.clinician_interpretation_text || 'Not generated'}</p>
          </div>
          <div className="flex gap-2 pt-2">
            <button onClick={() => { setEditing(viewing); setViewing(null); }} className="text-xs px-3 py-1.5 bg-teal-600 text-white rounded hover:bg-teal-700">Edit</button>
            <button onClick={() => toggleVersion(viewing.id)} className="text-xs px-3 py-1.5 border border-gray-300 rounded hover:bg-gray-50">
              {viewing.version_for_patient ? 'Switch to Clinician' : 'Switch to Patient'}
            </button>
            <button onClick={() => { handleSuppress(viewing.id); setViewing(null); }} className="text-xs px-3 py-1.5 border border-red-300 text-red-600 rounded hover:bg-red-50">Suppress</button>
          </div>
        </div>
      </div>
    );
  }

  if (editing) {
    return (
      <div className="space-y-4">
        <button onClick={() => setEditing(null)} className="text-sm text-teal-600 hover:text-teal-800">&larr; Cancel</button>
        <div className="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
          <h3 className="font-bold">Editing: {editing.test_name}</h3>
          <div>
            <label className="text-xs text-gray-500">Patient-Facing Text</label>
            <textarea className="w-full border rounded p-2 text-sm mt-1" rows={3}
              value={editing.interpretation_text || ''}
              onChange={(e) => setEditing({ ...editing, interpretation_text: e.target.value })} />
          </div>
          <div>
            <label className="text-xs text-gray-500">Clinician Text</label>
            <textarea className="w-full border rounded p-2 text-sm mt-1 font-mono" rows={3}
              value={editing.clinician_interpretation_text || ''}
              onChange={(e) => setEditing({ ...editing, clinician_interpretation_text: e.target.value })} />
          </div>
          <button onClick={saveEdit} className="px-4 py-2 bg-teal-600 text-white rounded text-sm hover:bg-teal-700">Save Changes</button>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex justify-between items-center">
        <div>
          <h2 className="text-xl font-bold text-gray-900">Interpretations</h2>
          <p className="text-sm text-gray-500 mt-1">All lab result interpretations for your partnership</p>
        </div>
        <button onClick={() => loadPage(page)} className="text-sm text-teal-600 hover:text-teal-800 flex items-center gap-1">
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
          Refresh
        </button>
      </div>

      {error && <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm">{error}</div>}
      {loading && <div className="flex justify-center py-12"><div className="h-6 w-6 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" /></div>}

      <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="text-left text-xs text-gray-400 uppercase bg-gray-50">
              <th className="px-3 py-3">Patient</th>
              <th className="px-3 py-3">Test</th>
              <th className="px-3 py-3">Value</th>
              <th className="px-3 py-3">Classification</th>
              <th className="px-3 py-3">Escalation</th>
              <th className="px-3 py-3">Conf.</th>
              <th className="px-3 py-3">Status</th>
              <th className="px-3 py-3">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {list.map((i) => (
              <tr key={i.id} className="hover:bg-gray-50">
                <td className="px-3 py-3 font-medium text-gray-900">{i.patient_identifier || 'N/A'}</td>
                <td className="px-3 py-3 text-gray-600">{i.test_name || 'N/A'}</td>
                <td className="px-3 py-3 text-gray-600 text-xs">{i.value} {i.unit || ''}</td>
                <td className="px-3 py-3">
                  {i.classification_status && (
                    <span className={`text-xs px-1.5 py-0.5 rounded font-medium ${
                      i.classification_status === 'normal' ? 'bg-green-50 text-green-600' :
                      i.classification_status.includes('critical') ? 'bg-red-50 text-red-600' :
                      i.classification_status.includes('abnormal') ? 'bg-amber-50 text-amber-600' :
                      'bg-gray-50 text-gray-500'
                    }`}>
                      {i.classification_status.replace(/_/g, ' ')}
                    </span>
                  )}
                </td>
                <td className="px-3 py-3">{escalationBadge(i.escalation_level)}</td>
                <td className="px-3 py-3">{confidenceBar(i.confidence_score)}</td>
                <td className="px-3 py-3">{statusBadge(i.status)}</td>
                <td className="px-3 py-3">
                  <div className="flex gap-1">
                    <button onClick={() => viewDetail(i.id)} className="text-xs text-teal-600 hover:text-teal-800 px-1.5 py-0.5 border border-teal-200 rounded">View</button>
                    <button onClick={() => handleEdit(i)} className="text-xs text-gray-500 hover:text-gray-700 px-1.5 py-0.5 border border-gray-200 rounded">Edit</button>
                  </div>
                </td>
              </tr>
            ))}
            {list.length === 0 && !loading && (
              <tr><td colSpan={8} className="px-5 py-8 text-center text-gray-400">No interpretations found.</td></tr>
            )}
          </tbody>
        </table>
      </div>

      <div className="flex gap-2 justify-center">
        {[1, 2, 3, 4, 5].map(p => (
          <button key={p} onClick={() => setPage(p)}
            className={`px-3 py-1 rounded text-xs font-medium ${p === page ? 'bg-teal-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'}`}>
            {p}
          </button>
        ))}
      </div>
    </div>
  );
}