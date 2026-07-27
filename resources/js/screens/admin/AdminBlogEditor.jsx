import React, { useEffect, useState, useRef } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import api from '../../lib/api';

export default function AdminBlogEditor() {
    const { id } = useParams();
    const isEdit = Boolean(id);
    const navigate = useNavigate();
    const quillRef = useRef(null);
    const quillInstance = useRef(null);

    const [form, setForm] = useState({
        title: '',
        slug: '',
        excerpt: '',
        content: '',
        featured_image: '',
        category_id: '',
        status: 'draft',
        meta_title: '',
        meta_description: '',
    });
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(false);
    const [uploadingImage, setUploadingImage] = useState(false);
    const [fetching, setFetching] = useState(isEdit);
    const [editorReady, setEditorReady] = useState(false);

    // Fetch categories on mount
    useEffect(() => {
        api.get('/admin/blog/categories').then(res => {
            setCategories(res.data.categories || []);
        }).catch(() => {});
    }, []);

    // Fetch post data if editing
    useEffect(() => {
        if (isEdit) {
            setFetching(true);
            api.get(`/admin/blog/posts/${id}`)
                .then(res => {
                    const p = res.data.post;
                    setForm({
                        title: p.title || '',
                        slug: p.slug || '',
                        excerpt: p.excerpt || '',
                        content: p.content || '',
                        featured_image: p.featured_image || '',
                        category_id: p.category_id || '',
                        status: p.status || 'draft',
                        meta_title: p.meta_title || '',
                        meta_description: p.meta_description || '',
                    });
                })
                .catch(() => {
                    alert('Failed to load post');
                    navigate('/admin/blog/posts');
                })
                .finally(() => setFetching(false));
        }
    }, [id]);

    // Initialize Quill editor
    useEffect(() => {
        if (editorReady) return;
        if (fetching) return;

        const loadQuill = async () => {
            try {
                // Load Quill CSS
                const linkEl = document.createElement('link');
                linkEl.href = 'https://cdn.quilljs.com/1.3.7/quill.snow.css';
                linkEl.rel = 'stylesheet';
                document.head.appendChild(linkEl);

                const linkEl2 = document.createElement('link');
                linkEl2.href = 'https://cdn.quilljs.com/1.3.7/quill.bubble.css';
                linkEl2.rel = 'stylesheet';
                document.head.appendChild(linkEl2);

                // Load Quill script
                await new Promise((resolve) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.quilljs.com/1.3.7/quill.min.js';
                    script.onload = resolve;
                    document.head.appendChild(script);
                });

                if (quillRef.current && !quillInstance.current) {
                    const toolbarOptions = [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['blockquote', 'code-block'],
                        ['link', 'image'],
                        [{ 'align': [] }],
                        ['clean']
                    ];

                    quillInstance.current = new window.Quill(quillRef.current, {
                        theme: 'snow',
                        modules: { toolbar: toolbarOptions },
                        placeholder: 'Write your blog post content...',
                    });

                    // Set initial content
                    if (isEdit && quillRef.current.dataset.initialContent) {
                        quillInstance.current.root.innerHTML = quillRef.current.dataset.initialContent;
                    }

                    quillInstance.current.on('text-change', () => {
                        setForm(prev => ({ ...prev, content: quillInstance.current.root.innerHTML }));
                    });
                }
                setEditorReady(true);
            } catch {
                // If Quill fails to load, use a basic textarea fallback
                setEditorReady('fallback');
            }
        };

        loadQuill();
    }, [fetching]);

    // Auto-generate slug from title
    const handleTitleChange = (value) => {
        setForm(prev => ({
            ...prev,
            title: value,
            slug: !isEdit ? value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') : prev.slug,
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!form.title || !form.content) {
            alert('Title and content are required');
            return;
        }
        setLoading(true);
        try {
            if (isEdit) {
                await api.put(`/admin/blog/posts/${id}`, form);
            } else {
                await api.post('/admin/blog/posts', form);
            }
            navigate('/admin/blog/posts');
        } catch (err) {
            const msg = err?.data?.message || 'Failed to save post';
            alert(msg);
        } finally {
            setLoading(false);
        }
    };

    if (fetching) {
        return (
            <div className="flex justify-center py-20">
                <div className="h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" />
            </div>
        );
    }

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-900">
                    {isEdit ? 'Edit Post' : 'New Blog Post'}
                </h2>
                <button
                    onClick={() => navigate('/admin/blog/posts')}
                    className="text-sm text-gray-600 hover:text-teal-600"
                >
                    ← Back to posts
                </button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6 max-w-4xl">
                {/* Title */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input
                        type="text"
                        value={form.title}
                        onChange={(e) => handleTitleChange(e.target.value)}
                        placeholder="Post title"
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
                    />
                </div>

                {/* Slug */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                    <input
                        type="text"
                        value={form.slug}
                        onChange={(e) => setForm(prev => ({ ...prev, slug: e.target.value }))}
                        placeholder="auto-generated-from-title"
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-mono focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
                    />
                    <p className="mt-1 text-xs text-gray-400">Leave blank to auto-generate from title.</p>
                </div>

                {/* Row: Category + Status */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select
                            value={form.category_id}
                            onChange={(e) => setForm(prev => ({ ...prev, category_id: e.target.value }))}
                            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
                        >
                            <option value="">No category</option>
                            {categories.map(cat => (
                                <option key={cat.id} value={cat.id}>{cat.name}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            value={form.status}
                            onChange={(e) => setForm(prev => ({ ...prev, status: e.target.value }))}
                            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-white focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
                        >
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </div>
                </div>

                {/* Excerpt */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Excerpt</label>
                    <textarea
                        value={form.excerpt}
                        onChange={(e) => setForm(prev => ({ ...prev, excerpt: e.target.value }))}
                        rows={2}
                        placeholder="Brief summary for previews..."
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none resize-none"
                    />
                </div>

                {/* Featured Image */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Featured Image</label>
                    {form.featured_image ? (
                        <div className="relative mb-3">
                            <img
                                src={form.featured_image}
                                alt="Featured preview"
                                className="w-full max-w-md rounded-lg border border-gray-200 object-cover max-h-48"
                            />
                            <button
                                type="button"
                                onClick={() => setForm(prev => ({ ...prev, featured_image: '' }))}
                                className="absolute top-2 right-2 rounded-full bg-red-600 text-white w-6 h-6 flex items-center justify-center text-xs hover:bg-red-700"
                            >
                                ✕
                            </button>
                        </div>
                    ) : null}
                    <div className="flex items-center gap-3">
                        <label className="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors inline-flex items-center gap-2">
                            {uploadingImage ? 'Uploading...' : '📷 Choose Image'}
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/gif,image/webp"
                                className="hidden"
                                disabled={uploadingImage}
                                onChange={async (e) => {
                                    const file = e.target.files?.[0];
                                    if (!file) return;
                                    setUploadingImage(true);
                                    try {
                                        const fd = new FormData();
                                        fd.append('image', file);
                                        const res = await api.post('/admin/upload', fd, {
                                            headers: { 'Content-Type': 'multipart/form-data' },
                                        });
                                        setForm(prev => ({ ...prev, featured_image: res.data.url }));
                                    } catch (err) {
                                        alert(err?.data?.message || 'Upload failed');
                                    } finally {
                                        setUploadingImage(false);
                                    }
                                }}
                            />
                        </label>
                        <span className="text-xs text-gray-400">or paste a URL below</span>
                    </div>
                    <input
                        type="text"
                        value={form.featured_image}
                        onChange={(e) => setForm(prev => ({ ...prev, featured_image: e.target.value }))}
                        placeholder="https://example.com/image.jpg"
                        className="w-full mt-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
                    />
                    <p className="mt-1 text-xs text-gray-400">Upload an image or paste a URL. Max 5MB. Supported: JPG, PNG, GIF, WebP.</p>
                </div>

                {/* Content Editor */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Content *</label>
                    <div className="bg-white rounded-lg border border-gray-300 overflow-hidden">
                        {editorReady === 'fallback' ? (
                            <textarea
                                value={form.content}
                                onChange={(e) => setForm(prev => ({ ...prev, content: e.target.value }))}
                                rows={20}
                                placeholder="Write HTML content here..."
                                className="w-full px-4 py-3 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none resize-y font-mono"
                                style={{ minHeight: '400px' }}
                            />
                        ) : (
                            <div
                                ref={quillRef}
                                data-initial-content={isEdit ? form.content : ''}
                                style={{ minHeight: '400px' }}
                            />
                        )}
                    </div>
                </div>

                {/* SEO */}
                <div className="border-t border-gray-200 pt-6">
                    <h3 className="text-sm font-semibold text-gray-900 mb-4">SEO Settings</h3>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                            <input
                                type="text"
                                value={form.meta_title}
                                onChange={(e) => setForm(prev => ({ ...prev, meta_title: e.target.value }))}
                                placeholder="SEO title"
                                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                            <input
                                type="text"
                                value={form.meta_description}
                                onChange={(e) => setForm(prev => ({ ...prev, meta_description: e.target.value }))}
                                placeholder="SEO description"
                                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 outline-none"
                            />
                        </div>
                    </div>
                </div>

                {/* Submit */}
                <div className="flex items-center gap-3 pt-4">
                    <button
                        type="submit"
                        disabled={loading}
                        className="rounded-lg bg-teal-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-teal-700 transition-colors disabled:opacity-50"
                    >
                        {loading ? 'Saving...' : isEdit ? 'Update Post' : 'Create Post'}
                    </button>
                    <button
                        type="button"
                        onClick={() => navigate('/admin/blog/posts')}
                        className="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    );
}