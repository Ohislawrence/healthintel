import React from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../lib/api';

export default function BlogPostDetail() {
    const { slug } = useParams();
    const navigate = useNavigate();

    const { data, isLoading } = useQuery({
        queryKey: ['blog-post', slug],
        queryFn: () => api.get(`/blog/posts/${slug}`),
        enabled: !!slug,
    });
    const post = data?.data?.post || data?.data || {};

    if (isLoading) {
        return (
            <div className="max-w-2xl mx-auto space-y-4">
                <div className="skeleton h-8 w-48 rounded" />
                <div className="skeleton h-6 w-full rounded" />
                <div className="skeleton h-64 w-full rounded-xl" />
            </div>
        );
    }

    if (!post || !post.title) {
        return (
            <div className="max-w-2xl mx-auto text-center py-16">
                <p className="text-lg font-bold text-neutral-900 mb-2">Post not found</p>
                <button onClick={() => navigate('/blog')} className="text-sm text-teal-600 hover:text-teal-700">← Back to blog</button>
            </div>
        );
    }

    return (
        <div className="max-w-2xl mx-auto space-y-5">
            <button onClick={() => navigate('/blog')} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to blog</button>

            {post.featured_image && (
                <img src={post.featured_image} alt="" className="w-full rounded-xl object-cover max-h-64" />
            )}

            <div>
                <h1 className="text-2xl font-extrabold text-neutral-900 tracking-tight leading-tight">{post.title}</h1>
                <div className="flex items-center gap-3 mt-3 text-xs text-neutral-400">
                    {post.category && <span className="bg-teal-50 text-teal-600 px-2 py-0.5 rounded font-semibold">{post.category.name}</span>}
                    {post.author && <span>By {post.author.name}</span>}
                    {post.published_at && <span>{new Date(post.published_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</span>}
                </div>
            </div>

            {post.excerpt && (
                <p className="text-sm text-neutral-500 italic border-l-2 border-teal-200 pl-4">{post.excerpt}</p>
            )}

            <div className="prose prose-sm max-w-none text-neutral-700 leading-relaxed" dangerouslySetInnerHTML={{ __html: post.content || '' }} />

            <div className="border-t border-neutral-100 pt-4">
                <Link to="/blog" className="text-sm font-semibold text-teal-600 hover:text-teal-700">
                    ← More articles
                </Link>
            </div>
        </div>
    );
}