import React, { useEffect, useState } from 'react';
import api from '../../lib/api';

export default function AdminBlogCategories() {
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [editing, setEditing] = useState(null);
    const [newName, setNewName] = useState('');
    const [editName, setEditName] = useState('');

    const fetchCategories = async () => {
        setLoading(true);
        try {
            const res = await api.get('/admin/blog/categories');
            setCategories(res.data.categories || []);
        } catch (err) {
            console.error('Failed to load categories', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchCategories();
    }, []);

    const handleCreate = async (e) => {
        e.preventDefault();
        if (!newName.trim()) return;
        try {
            await api.post('/admin/blog/categories', { name: newName.trim() });
            setNewName('');
            fetchCategories();
        } catch (err) {
            alert(err?.data?.message || 'Failed to create category');
        }
    };

    const handleUpdate = async (id) => {
        if (!editName.trim()) return;
        try {
            await api.put(`/admin/blog/categories/${id}`, { name: editName.trim() });
            setEditing(null);
            setEditName('');
            fetchCategories();
        } catch (err) {
            alert(err?.data?.message || 'Failed to update category');
        }
    };

    const handleDelete = async (id, name) => {
        if (!window.confirm(`Delete category "${name}"?`)) return;
        try {
            await api.delete(`/admin/blog/categories/${id}`);
            fetchCategories();
        } catch (err) {
            alert(err?.data?.message || 'Failed to delete category');
        }
    };

    return (
        <div>
            <h2 className="text-2xl font-bold text-gray-900 mb-6">Blog Categories</h2>

            {/* Create Form */}
            <form onSubmit={handleCreate} className="mb-8 flex gap-3 max-w-md">
                <input
                    type="text"
                    value={newName}
                    onChange={(e) => setNewName(e.target.value)}
                    placeholder="New category name"
                    className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
                />
                <button
                    type="submit"
                    disabled={!newName.trim()}
                    className="rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 transition-colors disabled:opacity-50"
                >
                    Add
                </button>
            </form>

            {/* Categories Table */}
            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden max-w-lg">
                {loading ? (
                    <div className="flex justify-center py-8">
                        <div className="h-6 w-6 animate-spin rounded-full border-2 border-teal-500 border-t-transparent" />
                    </div>
                ) : categories.length === 0 ? (
                    <div className="text-center py-8 text-gray-500 text-sm">
                        No categories yet. Create one above.
                    </div>
                ) : (
                    <table className="w-full text-left text-sm">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 font-medium text-gray-600">Name</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Slug</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Posts</th>
                                <th className="px-6 py-3 font-medium text-gray-600 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {categories.map((cat) => (
                                <tr key={cat.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-3">
                                        {editing === cat.id ? (
                                            <input
                                                type="text"
                                                value={editName}
                                                onChange={(e) => setEditName(e.target.value)}
                                                autoFocus
                                                className="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
                                            />
                                        ) : (
                                            <span className="font-medium text-gray-900">{cat.name}</span>
                                        )}
                                    </td>
                                    <td className="px-6 py-3 text-gray-500 font-mono text-xs">{cat.slug}</td>
                                    <td className="px-6 py-3 text-gray-500">{cat.posts_count || 0}</td>
                                    <td className="px-6 py-3 text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            {editing === cat.id ? (
                                                <>
                                                    <button
                                                        onClick={() => handleUpdate(cat.id)}
                                                        className="rounded px-2 py-1 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100"
                                                    >
                                                        Save
                                                    </button>
                                                    <button
                                                        onClick={() => { setEditing(null); setEditName(''); }}
                                                        className="rounded px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200"
                                                    >
                                                        Cancel
                                                    </button>
                                                </>
                                            ) : (
                                                <>
                                                    <button
                                                        onClick={() => { setEditing(cat.id); setEditName(cat.name); }}
                                                        className="rounded px-2 py-1 text-xs font-medium text-teal-700 bg-teal-50 hover:bg-teal-100"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(cat.id, cat.name)}
                                                        className="rounded px-2 py-1 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100"
                                                    >
                                                        Delete
                                                    </button>
                                                </>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </div>
    );
}