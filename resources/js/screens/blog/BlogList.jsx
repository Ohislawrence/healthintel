import React, { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import api from '../../lib/api';

export default function BlogList() {
    const [searchParams] = useSearchParams();
    const [posts, setPosts] = useState([]);
    const [categories, setCategories] = useState([]);
    const [pagination, setPagination] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const activeCategory = searchParams.get('category') || '';

    const fetchPosts = async (page = 1) => {
        setLoading(true);
        try {
            const params = { page };
            if (activeCategory) params.category = activeCategory;
            if (search) params.search = search;
            const res = await api.get('/blog/posts', { params });
            setPosts(res.data.posts?.data || []);
            setPagination(res.data.pagination || { current_page: 1, last_page: 1, total: 0 });
        } catch (err) {
            console.error('Failed to load posts', err);
        } finally {
            setLoading(false);
        }
    };

    const fetchCategories = async () => {
        try {
            const res = await api.get('/blog/categories');
            setCategories(res.data.categories || []);
        } catch {
            // silently ignore
        }
    };

    useEffect(() => {
        fetchCategories();
    }, []);

    useEffect(() => {
        fetchPosts();
    }, [activeCategory]);

    const handleSearch = (e) => {
        e.preventDefault();
        fetchPosts();
    };

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Header */}
            <section className="bg-gradient-to-br from-teal-50 via-white to-emerald-50 border-b border-gray-200">
                <div className="mx-auto max-w-6xl px-4 py-16 text-center">
                    <h1 className="text-3xl font-bold text-gray-900 sm:text-4xl">
                        HealthIntel Blog
                    </h1>
                    <p className="mt-3 text-lg text-gray-600">
                        Insights, tips, and guides for understanding your health
                    </p>
                    <form onSubmit={handleSearch} className="mt-6 flex max-w-md mx-auto gap-2">
                        <input
                            type="text"
                            placeholder="Search articles..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
                        />
                        <button
                            type="submit"
                            className="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700 transition-colors"
                        >
                            Search
                        </button>
                    </form>
                </div>
            </section>

            <div className="mx-auto max-w-6xl px-4 py-12 flex gap-8">
                {/* Main content */}
                <div className="flex-1 min-w-0">
                    {loading ? (
                        <div className="flex justify-center py-20">
                            <div className="h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" />
                        </div>
                    ) : posts.length === 0 ? (
                        <div className="text-center py-20">
                            <p className="text-gray-500 text-lg">No posts found.</p>
                        </div>
                    ) : (
                        <>
                            <div className="grid gap-8 md:grid-cols-2">
                                {posts.map((post) => (
                                    <Link
                                        key={post.id}
                                        to={`/blog/${post.slug}`}
                                        className="group rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition-all"
                                    >
                                        {post.featured_image && (
                                            <img
                                                src={post.featured_image}
                                                alt={post.title}
                                                className="w-full h-48 object-cover"
                                                loading="lazy"
                                            />
                                        )}
                                        <div className="p-6">
                                            {post.category && (
                                                <span className="inline-block rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-700 mb-3">
                                                    {post.category.name}
                                                </span>
                                            )}
                                            <h3 className="text-lg font-semibold text-gray-900 group-hover:text-teal-600 transition-colors line-clamp-2">
                                                {post.title}
                                            </h3>
                                            {post.excerpt && (
                                                <p className="mt-2 text-sm text-gray-600 line-clamp-2">
                                                    {post.excerpt}
                                                </p>
                                            )}
                                            <div className="mt-4 flex items-center justify-between">
                                                <span className="text-xs text-gray-500">
                                                    {new Date(post.published_at).toLocaleDateString('en-US', {
                                                        year: 'numeric',
                                                        month: 'short',
                                                        day: 'numeric',
                                                    })}
                                                </span>
                                                <span className="text-xs text-gray-400">
                                                    {post.author?.name || 'HealthIntel'}
                                                </span>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>

                            {/* Pagination */}
                            {pagination.last_page > 1 && (
                                <div className="mt-10 flex justify-center gap-2">
                                    {Array.from({ length: pagination.last_page }, (_, i) => i + 1).map((page) => (
                                        <button
                                            key={page}
                                            onClick={() => fetchPosts(page)}
                                            className={`rounded-lg px-4 py-2 text-sm font-medium transition-colors ${
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
                        </>
                    )}
                </div>

                {/* Sidebar */}
                <aside className="hidden lg:block w-64 shrink-0">
                    <div className="sticky top-24 rounded-2xl border border-gray-200 bg-white p-6">
                        <h4 className="font-semibold text-gray-900 mb-4">Categories</h4>
                        <nav className="space-y-1">
                            <Link
                                to="/blog"
                                className={`block rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                    !activeCategory ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-50'
                                }`}
                            >
                                All Posts
                            </Link>
                            {categories.map((cat) => (
                                <Link
                                    key={cat.id}
                                    to={`/blog?category=${cat.slug}`}
                                    className={`flex items-center justify-between rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                        activeCategory === cat.slug
                                            ? 'bg-teal-50 text-teal-700'
                                            : 'text-gray-600 hover:bg-gray-50'
                                    }`}
                                >
                                    <span>{cat.name}</span>
                                    <span className="text-xs text-gray-400">{cat.published_posts_count || 0}</span>
                                </Link>
                            ))}
                        </nav>

                        <div className="mt-6 pt-6 border-t border-gray-200">
                            <Link
                                to="/"
                                className="text-sm text-teal-600 hover:text-teal-700 font-medium"
                            >
                                ← Back to HealthIntel
                            </Link>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    );
}