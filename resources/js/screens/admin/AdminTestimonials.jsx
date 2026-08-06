import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../../lib/api';

export default function AdminTestimonials() {
    const qc = useQueryClient();
    const [form, setForm] = useState({ author_name: '', author_role: 'Patient', author_organization: '', quote: '', rating: 5, is_active: true, is_featured: false, category: 'patient' });
    const [editing, setEditing] = useState(null);

    const { data, isLoading } = useQuery({ queryKey: ['admin-testimonials'], queryFn: () => api.get('/admin/testimonials') });
    const testimonials = data?.data || [];

    const saveMutation = useMutation({
        mutationFn: (d) => editing ? api.put(`/admin/testimonials/${editing}`, d) : api.post('/admin/testimonials', d),
        onSuccess: () => { qc.invalidateQueries('admin-testimonials'); setEditing(null); setForm({ author_name: '', author_role: 'Patient', author_organization: '', quote: '', rating: 5, is_active: true, is_featured: false, category: 'patient' }); },
    });

    const deleteMutation = useMutation({
        mutationFn: (id) => api.delete(`/admin/testimonials/${id}`),
        onSuccess: () => qc.invalidateQueries('admin-testimonials'),
    });

    const handleEdit = (t) => { setEditing(t.id); setForm({ author_name: t.author_name, author_role: t.author_role || 'Patient', author_organization: t.author_organization || '', quote: t.quote, rating: t.rating || 5, is_active: t.is_active, is_featured: t.is_featured, category: t.category || 'patient' }); };
    const handleSubmit = (e) => { e.preventDefault(); saveMutation.mutate(form); };

    return (
        <div className="space-y-6">
            <h2 className="text-xl font-bold text-gray-900">Testimonials</h2>
            <form onSubmit={handleSubmit} className="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
                <h3 className="font-bold text-sm">{editing ? 'Edit Testimonial' : 'Add Testimonial'}</h3>
                <div className="grid grid-cols-2 gap-3">
                    <input className="input" placeholder="Author Name" value={form.author_name} onChange={e => setForm({ ...form, author_name: e.target.value })} required />
                    <select className="input" value={form.author_role} onChange={e => setForm({ ...form, author_role: e.target.value })}>
                        <option>Patient</option><option>Clinician</option><option>Lab Director</option><option>Partner</option>
                    </select>
                    <input className="input" placeholder="Organization" value={form.author_organization} onChange={e => setForm({ ...form, author_organization: e.target.value })} />
                    <input className="input" type="number" min="1" max="5" value={form.rating} onChange={e => setForm({ ...form, rating: parseInt(e.target.value) })} />
                </div>
                <textarea className="input" rows="3" placeholder="Quote" value={form.quote} onChange={e => setForm({ ...form, quote: e.target.value })} required />
                <div className="flex gap-4">
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.is_active} onChange={e => setForm({ ...form, is_active: e.target.checked })} /> Active</label>
                    <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.is_featured} onChange={e => setForm({ ...form, is_featured: e.target.checked })} /> Featured</label>
                </div>
                <div className="flex gap-2">
                    <button type="submit" className="btn btn-primary text-sm" disabled={saveMutation.isPending}>{saveMutation.isPending ? 'Saving...' : editing ? 'Update' : 'Add'}</button>
                    {editing && <button type="button" onClick={() => { setEditing(null); setForm({ author_name: '', author_role: 'Patient', author_organization: '', quote: '', rating: 5, is_active: true, is_featured: false, category: 'patient' }); }} className="btn btn-ghost text-sm text-gray-500">Cancel</button>}
                </div>
            </form>
            {isLoading ? <p className="text-gray-400 text-sm">Loading...</p> : (
                <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    {testimonials.map((t, i) => (
                        <div key={t.id} className={`flex items-center gap-3 px-4 py-3 ${i < testimonials.length - 1 ? 'border-b' : ''}`}>
                            <div className="flex-1">
                                <p className="text-sm font-bold">{t.author_name} <span className="text-xs text-gray-400">({t.author_role})</span>{' '}{'⭐'.repeat(t.rating || 0)}</p>
                                <p className="text-xs text-gray-500 italic mt-1">"{t.quote.substring(0, 120)}{t.quote.length > 120 ? '...' : ''}"</p>
                            </div>
                            <div className="flex gap-1">
                                <span className={`text-xs px-2 py-1 rounded ${t.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'}`}>{t.is_active ? '✓' : '—'}</span>
                                <button onClick={() => handleEdit(t)} className="text-xs px-2 py-1 rounded bg-blue-50 text-blue-600 hover:bg-blue-100">Edit</button>
                                <button onClick={() => { if (confirm('Delete?')) deleteMutation.mutate(t.id); }} className="text-xs px-2 py-1 rounded bg-red-50 text-red-500 hover:bg-red-100">Del</button>
                            </div>
                        </div>
                    ))}
                    {testimonials.length === 0 && <p className="p-4 text-gray-400 text-sm">No testimonials yet.</p>}
                </div>
            )}
        </div>
    );
}