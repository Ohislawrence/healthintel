import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../../lib/api';

export default function SearchModal({ open, onClose }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);
    const inputRef = useRef(null);
    const navigate = useNavigate();
    const debounceRef = useRef(null);

    useEffect(() => {
        if (open && inputRef.current) {
            setTimeout(() => inputRef.current?.focus(), 100);
        }
        if (!open) {
            setQuery('');
            setResults([]);
        }
    }, [open]);

    const doSearch = useCallback(async (q) => {
        if (q.length < 2) {
            setResults([]);
            return;
        }
        setLoading(true);
        try {
            const res = await api.get('/search', { params: { q } });
            setResults(res.data.results || []);
        } catch {
            setResults([]);
        } finally {
            setLoading(false);
        }
    }, []);

    const handleChange = (e) => {
        const val = e.target.value;
        setQuery(val);
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => doSearch(val), 300);
    };

    const handleSelect = (result) => {
        onClose();
        navigate(result.url);
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Escape') onClose();
    };

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center pt-16 px-4">
            {/* Backdrop */}
            <div
                className="absolute inset-0 bg-black/30 backdrop-blur-sm"
                onClick={onClose}
            />

            {/* Modal */}
            <div className="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden animate-fade-in-up">
                {/* Input */}
                <div className="flex items-center gap-3 px-4 border-b border-neutral-100">
                    <svg className="w-5 h-5 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        ref={inputRef}
                        type="text"
                        value={query}
                        onChange={handleChange}
                        onKeyDown={handleKeyDown}
                        placeholder="Search panels, providers, blog posts..."
                        className="flex-1 py-4 text-sm bg-transparent border-none outline-none placeholder:text-neutral-400"
                    />
                    <button
                        onClick={onClose}
                        className="text-neutral-400 hover:text-neutral-600 p-1"
                        aria-label="Close search"
                    >
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {/* Results */}
                <div className="max-h-80 overflow-y-auto">
                    {loading && (
                        <div className="px-4 py-8 text-center">
                            <div className="h-5 w-5 mx-auto animate-spin rounded-full border-2 border-teal-500 border-t-transparent" />
                        </div>
                    )}

                    {!loading && query.length < 2 && (
                        <div className="px-4 py-10 text-center text-sm text-neutral-400">
                            Type at least 2 characters to search
                        </div>
                    )}

                    {!loading && query.length >= 2 && results.length === 0 && (
                        <div className="px-4 py-10 text-center">
                            <p className="text-sm text-neutral-500">No results found</p>
                            <p className="text-xs text-neutral-400 mt-1">Try a different search term</p>
                        </div>
                    )}

                    {!loading && results.length > 0 && (
                        <div className="py-2">
                            {results.map((r, i) => (
                                <button
                                    key={i}
                                    onClick={() => handleSelect(r)}
                                    className="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-neutral-50 transition-colors"
                                >
                                    <span className="w-9 h-9 rounded-lg bg-neutral-100 flex items-center justify-center text-lg shrink-0">
                                        {r.icon}
                                    </span>
                                    <div className="min-w-0">
                                        <p className="text-sm font-semibold text-neutral-900 truncate">{r.title}</p>
                                        <p className="text-xs text-neutral-500 truncate">{r.subtitle}</p>
                                    </div>
                                    <span className="ml-auto text-neutral-300 shrink-0">
                                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </span>
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                {/* Footer hint */}
                <div className="px-4 py-2 border-t border-neutral-100 flex items-center gap-2 text-[10px] text-neutral-400 font-medium">
                    <kbd className="px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-500 font-mono">Esc</kbd>
                    <span>to close</span>
                </div>
            </div>
        </div>
    );
}