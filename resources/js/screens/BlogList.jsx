import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../lib/api';

export default function BlogList() {
    const { data: postsData, isLoading } = useQuery({
        queryKey: ['blog-posts'],
        queryFn: () => api.get('/blog/posts'),
    });
    const posts = postsData?.data || [];

    const { data: catsData } = useQuery({
        queryKey: ['blog-categories'],
        queryFn: () => api.get('/blog/categories'),
    });
    const categories = catsData?.data || [];

    if (isLoading) {
        return (
            <div className="max-w-2xl mx-auto space-y-4">
                {[1, 2, 3].map((i) => (
                    <div key={i} className="card p-5 skeleton h-32 rounded-xl" />
                ))}
            </div>
        );
    }

    return (
        <div className="max-w-2xl mx-auto space-y-6">
            <div>
                <h1 className="text-2xl font-extrabold text-neutral-900 tracking-tight">HealthIntel Blog</h1>
                <p className="text-sm text-neutral-500 mt-1">Health tips, lab result guides, and wellness insights</p>
            </div>

            {categories.length > 0 && (
                <div className="flex gap-2 overflow-x-auto pb-2">
                    {categories.map((cat) => (
                        <span key={cat.slug || cat.id} className="text-xs font-semibold bg-teal-50 text-teal-700 px-3 py-1.5 rounded-lg whitespace-nowrap">
                            {cat.name}
                        </span>
                    ))}
                </div>
            )}

            {posts.length === 0 ? (
                <div className="card p-8 text-center text-neutral-400">
                    <p className="text-lg mb-2">📝</p>
                    <p>No blog posts yet.</p>
                </div>
            ) : (
                <div className="space-y-4">
                    {posts.map((post) => (
                        <Link
                            key={post.slug}
                            to={`/blog/${post.slug}`}
                            className="card p-5 block hover:shadow-md hover:border-teal-200 transition-all"
                        >
                            <div className="flex items-start gap-3">
                                {post.featured_image && (
                                    <img src={post.featured_image} alt="" className="w-16 h-16 rounded-lg object-cover flex-shrink-0" />
                                )}
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-bold text-neutral-900 mb-1 line-clamp-2">{post.title}</p>
                                    <p className="text-xs text-neutral-400 line-clamp-2 mb-2">{post.excerpt}</p>
                                    <div className="flex items-center gap-2 text-[10px] text-neutral-400">
                                        {post.category && <span className="bg-teal-50 text-teal-600 px-2 py-0.5 rounded font-semibold">{post.category.name}</span>}
                                        {post.published_at && <span>{new Date(post.published_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}</span>}
                                    </div>
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>
            )}
        </div>
    );
}