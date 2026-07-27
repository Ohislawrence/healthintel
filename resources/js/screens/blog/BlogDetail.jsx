import React, { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import api from '../../lib/api';

export default function BlogDetail() {
    const { slug } = useParams();
    const [post, setPost] = useState(null);
    const [related, setRelated] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchPost = async () => {
            setLoading(true);
            setError(null);
            try {
                const res = await api.get(`/blog/posts/${slug}`);
                setPost(res.data.post);
                setRelated(res.data.related || []);
            } catch (err) {
                setError(err?.data?.message || 'Post not found');
            } finally {
                setLoading(false);
            }
        };
        fetchPost();
    }, [slug]);

    if (loading) {
        return (
            <div className="flex min-h-screen items-center justify-center">
                <div className="h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" />
            </div>
        );
    }

    if (error || !post) {
        return (
            <div className="flex min-h-screen flex-col items-center justify-center text-center px-4">
                <h2 className="text-2xl font-bold text-gray-900">Post Not Found</h2>
                <p className="mt-2 text-gray-600">The article you're looking for doesn't exist or has been removed.</p>
                <Link to="/blog" className="mt-6 rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 transition-colors">
                    ← Back to Blog
                </Link>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-white">
            <article className="mx-auto max-w-3xl px-4 py-12">
                {/* Breadcrumb */}
                <nav className="mb-8 flex items-center gap-2 text-sm text-gray-500">
                    <Link to="/blog" className="hover:text-teal-600 transition-colors">Blog</Link>
                    <span>/</span>
                    {post.category && (
                        <>
                            <Link to={`/blog?category=${post.category.slug}`} className="hover:text-teal-600 transition-colors">
                                {post.category.name}
                            </Link>
                            <span>/</span>
                        </>
                    )}
                    <span className="text-gray-400 truncate">{post.title}</span>
                </nav>

                {/* Featured Image */}
                {post.featured_image && (
                    <img
                        src={post.featured_image}
                        alt={post.title}
                        className="w-full rounded-2xl object-cover max-h-96 mb-8"
                    />
                )}

                {/* Category Badge */}
                {post.category && (
                    <span className="inline-block rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-700 mb-4">
                        {post.category.name}
                    </span>
                )}

                {/* Title */}
                <h1 className="text-3xl font-bold text-gray-900 sm:text-4xl leading-tight">
                    {post.title}
                </h1>

                {/* Meta */}
                <div className="mt-4 flex items-center gap-4 text-sm text-gray-500">
                    <span>{post.author?.name || 'HealthIntel'}</span>
                    <span>·</span>
                    <span>{new Date(post.published_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                    })}</span>
                </div>

                {/* Content */}
                <div
                    className="prose prose-teal max-w-none mt-8 text-gray-700 leading-relaxed
                        prose-headings:text-gray-900 prose-headings:font-bold
                        prose-h2:text-2xl prose-h2:mt-10 prose-h2:mb-4
                        prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-3
                        prose-p:leading-7 prose-p:mb-4
                        prose-a:text-teal-600 prose-a:no-underline hover:prose-a:underline
                        prose-img:rounded-xl prose-img:mx-auto
                        prose-strong:text-gray-900
                        prose-blockquote:border-l-teal-500 prose-blockquote:bg-teal-50 prose-blockquote:rounded-r-lg prose-blockquote:py-3 prose-blockquote:px-6
                        prose-ul:list-disc prose-ol:list-decimal
                        prose-li:leading-7"
                    dangerouslySetInnerHTML={{ __html: post.content }}
                />

                {/* CTA */}
                <div className="mt-12 rounded-2xl bg-gradient-to-r from-teal-50 to-emerald-50 border border-teal-200 p-8 text-center">
                    <h3 className="text-xl font-semibold text-gray-900">Want to understand your lab results?</h3>
                    <p className="mt-2 text-gray-600">
                        Upload your lab report and get a clear explanation in seconds.
                    </p>
                    <Link
                        to="/register"
                        className="mt-4 inline-block rounded-xl bg-teal-600 px-6 py-3 text-sm font-semibold text-white hover:bg-teal-700 transition-colors shadow-md"
                    >
                        Get started free →
                    </Link>
                </div>
            </article>

            {/* Related Posts */}
            {related.length > 0 && (
                <section className="bg-gray-50 border-t border-gray-200 py-16">
                    <div className="mx-auto max-w-6xl px-4">
                        <h2 className="text-2xl font-bold text-gray-900 mb-8">Related Articles</h2>
                        <div className="grid gap-6 md:grid-cols-3">
                            {related.map((rp) => (
                                <Link
                                    key={rp.id}
                                    to={`/blog/${rp.slug}`}
                                    className="group rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm hover:shadow-md transition-all"
                                >
                                    {rp.featured_image && (
                                        <img
                                            src={rp.featured_image}
                                            alt={rp.title}
                                            className="w-full h-40 object-cover"
                                            loading="lazy"
                                        />
                                    )}
                                    <div className="p-5">
                                        {rp.category && (
                                            <span className="inline-block rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 mb-2">
                                                {rp.category.name}
                                            </span>
                                        )}
                                        <h3 className="font-semibold text-gray-900 group-hover:text-teal-600 transition-colors line-clamp-2">
                                            {rp.title}
                                        </h3>
                                        <p className="mt-1 text-xs text-gray-500">
                                            {new Date(rp.published_at).toLocaleDateString('en-US', {
                                                month: 'short',
                                                day: 'numeric',
                                                year: 'numeric',
                                            })}
                                        </p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            {/* Footer spacer */}
            <div className="py-8 text-center">
                <Link to="/blog" className="text-sm text-teal-600 hover:text-teal-700 font-medium">
                    ← Back to all articles
                </Link>
            </div>
        </div>
    );
}