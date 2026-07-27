import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

export default function AdminBlogPosts() {
    const [posts, setPosts] = useState([]);
    const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [loading, setLoading] = useState(true);
    const [statusFilter, setStatusFilter] = useState('');
    const [search, setSearch] = useState('');

    const fetchPosts = async (page = 1) => {
        setLoading(true);
        try {
            const params = { page };
            if (statusFilter) params.status = statusFilter;
            if (search) params.search = search;
            const res = await api.get('/admin/blog/posts', { params });
            setPosts(res.data.posts?.data || []);
            setPagination(res.data.pagination || { current_page: 1, last_page: 1, total: 0 });
        } catch (err) {
            console.error('Failed to load posts', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchPosts();
    }, [statusFilter]);

    const handleSearch = (e) => {
        e.preventDefault();
        fetchPosts();
    };

    const handleDelete = async (id, title) => {
        if (!window.confirm(`Delete "${title}"? This cannot be undone.`)) return;
        try {
            await api.delete(`/admin/blog/posts/${id}`);
            fetchPosts(pagination.current_page);
        } catch (err) {
            alert(err?.data?.message || 'Failed to delete post');
        }
    };

    const statusBadge = (status) => {
        return status === 'published'
            ? <span className="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">Published</span>
            : <span className="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Draft</span>;
    };

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-900">Blog Posts</h2>
                <Link
                    to="/admin/blog/posts/new"
                    className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 transition-colors"
                >
                    + New Post
                </Link>
            </div>

            {/* Filters */}
            <div className="mb-6 flex flex-col sm:flex-row gap-3">
                <select
                    value={statusFilter}
                    onChange={(e) => { setStatusFilter(e.target.value); }}
                    className="rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white"
                >
                    <option value="">All Status</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
                <form onSubmit={handleSearch} className="flex gap-2 flex-1">
                    <input
                        type="text"
                        placeholder="Search posts..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm"
                    />
                    <button type="submit" className="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Search
                    </button>
                </form>
            </div>

            {/* Table */}
            <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                {loading ? (
                    <div className="flex justify-center py-12">
                        <div className="h-6 w-6 animate-spin rounded-full border-2 border-teal-500 border-t-transparent" />
                    </div>
                ) : posts.length === 0 ? (
                    <div className="text-center py-12 text-gray-500">
                        No posts found. <Link to="/admin/blog/posts/new" className="text-teal-600 hover:underline">Create one</Link>
                    </div>
                ) : (
                    <table className="w-full text-left text-sm">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-6 py-3 font-medium text-gray-600">Title</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Category</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Status</th>
                                <th className="px-6 py-3 font-medium text-gray-600">Date</th>
                                <th className="px-6 py-3 font-medium text-gray-600 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {posts.map((post) => (
                                <tr key={post.id} className="hover:bg-gray-50">
                                    <td className="px-6 py-4">
                                        <div className="font-medium text-gray-900 max-w-xs truncate">{post.title}</div>
                                        {post.excerpt && (
                                            <div className="text-xs text-gray-500 mt-0.5 max-w-xs truncate">{post.excerpt}</div>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 text-gray-600">
                                        {post.category?.name || '—'}
                                    </td>
                                    <td className="px-6 py-4">{statusBadge(post.status)}</td>
                                    <td className="px-6 py-4 text-gray-500 text-xs">
                                        {post.published_at
                                            ? new Date(post.published_at).toLocaleDateString()
                                            : new Date(post.created_at).toLocaleDateString()}
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            <Link
                                                to={`/admin/blog/posts/${post.id}/edit`}
                                                className="rounded-lg px-3 py-1 text-xs font-medium text-teal-700 bg-teal-50 hover:bg-teal-100 transition-colors"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(post.id, post.title)}
                                                className="rounded-lg px-3 py-1 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 transition-colors"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}

                {/* Pagination */}
                {pagination.last_page > 1 && (
                    <div className="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-center gap-2">
                        {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map((page) => (
                            <button
                                key={page}
                                onClick={() => fetchPosts(page)}
                                className={`rounded px-3 py-1 text-xs font-medium ${
                                    page === pagination.current_page
                                        ? 'bg-teal-600 text-white'
                                        : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                                }`}
                            >
                                {page}
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}