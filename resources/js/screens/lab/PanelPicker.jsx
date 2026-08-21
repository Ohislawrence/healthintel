import React, { useState, useRef } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import useAuthStore from '../../stores/authStore';
import api from '../../lib/api';
import CameraUpload from '../../components/upload/CameraUpload';

/**
 * Step 1 — Choose how to share the lab report.
 *
 * Two clear paths (photo / PDF) lead to a single review screen before any
 * credits are charged. Manual entry is kept as a secondary option.
 */

const COST = 3;
const MAX_PDF_MB = 10;

export default function PanelPicker() {
    const navigate = useNavigate();
    const { user, fetchUser } = useAuthStore();
    const fileInputRef = useRef(null);

    const [mode, setMode] = useState(null); // 'photo' | 'pdf' | null
    const [file, setFile] = useState(null);
    const [fileBase64, setFileBase64] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState(null);
    const [dragOver, setDragOver] = useState(false);
    const [showPanels, setShowPanels] = useState(false);

    const balance = user?.credits ?? 0;

    // ── PDF: read file and extract via draft endpoint (no charge) ──
    const readPdfFile = (picked) => {
        if (!picked) return;
        if (picked.type !== 'application/pdf') {
            setError('Please select a PDF file.');
            return;
        }
        if (picked.size > MAX_PDF_MB * 1024 * 1024) {
            setError(`File must be under ${MAX_PDF_MB}MB.`);
            return;
        }
        setError(null);
        setFile(picked);
        const reader = new FileReader();
        reader.onloadend = () => setFileBase64(reader.result.split(',')[1]);
        reader.readAsDataURL(picked);
    };

    const handleFilePick = (e) => readPdfFile(e.target.files?.[0]);

    const handleDrop = (e) => {
        e.preventDefault();
        setDragOver(false);
        readPdfFile(e.dataTransfer.files?.[0]);
    };

    const handleUploadPdf = async () => {
        if (!fileBase64) {
            setError('Please select a PDF file first.');
            return;
        }
        setUploading(true);
        setError(null);
        try {
            const res = await api.post('/submissions/pdf/draft', {
                pdf_base64: fileBase64,
                pdf_name: file?.name || 'report.pdf',
            });
            // res = { ok, message, data: { draft_id, extracted_tests, ... } }
            navigate('/lab-results/review', {
                state: {
                    draft_id: res.data.draft_id,
                    extracted_tests: res.data.extracted_tests || [],
                    is_image: false,
                },
            });
        } catch (err) {
            setError(err?.message || 'Could not read this PDF. Please try again.');
        } finally {
            setUploading(false);
        }
    };

    // ── Photo: CameraUpload posts to /submissions/image → returns draft ──
    const handleCameraDraft = async (draft) => {
        if (draft?.draft_id) {
            setError(null);
            await fetchUser();
            navigate('/lab-results/review', {
                state: {
                    draft_id: draft.draft_id,
                    extracted_tests: draft.extracted_tests || [],
                    is_image: true,
                },
            });
        }
    };

    // ── Manual entry: query active panels ──
    const [panels, setPanels] = useState([]);
    const [panelsLoading, setPanelsLoading] = useState(false);
    const loadPanels = async () => {
        if (showPanels) {
            setShowPanels(false);
            return;
        }
        setShowPanels(true);
        if (panels.length) return;
        setPanelsLoading(true);
        try {
            const res = await api.get('/panels');
            setPanels(res.data?.panels || []);
        } catch {
            setPanels([]);
        } finally {
            setPanelsLoading(false);
        }
    };

    return (
        <div className="space-y-6 max-w-xl mx-auto">
            {/* ── Header ─────────────────────────────────── */}
            <div className="text-center">
                <div className="inline-flex items-center gap-1.5 text-xs font-bold text-teal-700 bg-teal-50 rounded-full px-3 py-1 mb-3">
                    Step 1 of 2
                </div>
                <h1 className="text-2xl font-extrabold text-neutral-900 tracking-tight">
                    Upload your lab report
                </h1>
                <p className="text-sm text-neutral-500 mt-2 max-w-md mx-auto leading-relaxed">
                    Take a photo or upload a PDF — we'll read it and you confirm before anything is charged.
                </p>
            </div>

            {/* ── Method selector ─────────────────────────── */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button
                    onClick={() => { setMode(mode === 'photo' ? null : 'photo'); setError(null); }}
                    className={`group relative overflow-hidden rounded-2xl p-5 text-left transition-all duration-200 border-2 ${
                        mode === 'photo'
                            ? 'border-teal-500 bg-gradient-to-br from-teal-50 to-emerald-50 shadow-lg shadow-teal-200/60'
                            : 'border-neutral-200 bg-white hover:border-teal-400 hover:shadow-xl hover:shadow-teal-100 hover:-translate-y-0.5'
                    }`}
                >
                    <div className="flex items-start gap-3">
                        <span className={`w-12 h-12 rounded-xl flex items-center justify-center text-2xl shrink-0 transition-colors ${
                            mode === 'photo' ? 'bg-teal-600 text-white' : 'bg-teal-50 text-teal-600 group-hover:bg-teal-600 group-hover:text-white'
                        }`}>
                            📸
                        </span>
                        <span className="flex-1 min-w-0">
                            <span className="block text-sm font-bold text-neutral-900">Take a photo</span>
                            <span className="block text-xs text-neutral-500 mt-0.5">Snap your report with the camera</span>
                        </span>
                        <span className={`mt-1 w-6 h-6 rounded-full flex items-center justify-center text-sm font-bold shrink-0 transition-all ${
                            mode === 'photo' ? 'bg-teal-600 text-white translate-x-0' : 'bg-neutral-100 text-neutral-400 group-hover:bg-teal-600 group-hover:text-white'
                        }`}>
                            ›
                        </span>
                    </div>
                </button>

                <button
                    onClick={() => { setMode(mode === 'pdf' ? null : 'pdf'); setError(null); }}
                    className={`group relative overflow-hidden rounded-2xl p-5 text-left transition-all duration-200 border-2 ${
                        mode === 'pdf'
                            ? 'border-teal-500 bg-gradient-to-br from-teal-50 to-emerald-50 shadow-lg shadow-teal-200/60'
                            : 'border-neutral-200 bg-white hover:border-teal-400 hover:shadow-xl hover:shadow-teal-100 hover:-translate-y-0.5'
                    }`}
                >
                    <div className="flex items-start gap-3">
                        <span className={`w-12 h-12 rounded-xl flex items-center justify-center text-2xl shrink-0 transition-colors ${
                            mode === 'pdf' ? 'bg-teal-600 text-white' : 'bg-teal-50 text-teal-600 group-hover:bg-teal-600 group-hover:text-white'
                        }`}>
                            📄
                        </span>
                        <span className="flex-1 min-w-0">
                            <span className="block text-sm font-bold text-neutral-900">Upload a PDF</span>
                            <span className="block text-xs text-neutral-500 mt-0.5">Select a file from your device</span>
                        </span>
                        <span className={`mt-1 w-6 h-6 rounded-full flex items-center justify-center text-sm font-bold shrink-0 transition-all ${
                            mode === 'pdf' ? 'bg-teal-600 text-white translate-x-0' : 'bg-neutral-100 text-neutral-400 group-hover:bg-teal-600 group-hover:text-white'
                        }`}>
                            ›
                        </span>
                    </div>
                </button>
            </div>

            {/* ── Photo panel ─────────────────────────────── */}
            {mode === 'photo' && (
                <CameraUpload
                    onDraft={handleCameraDraft}
                    onError={(err) => setError(err)}
                />
            )}

            {/* ── PDF panel ───────────────────────────────── */}
            {mode === 'pdf' && (
                <div className="space-y-4">
                    <div
                        onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
                        onDragLeave={() => setDragOver(false)}
                        onDrop={handleDrop}
                        onClick={() => fileInputRef.current?.click()}
                        className={`card p-8 text-center cursor-pointer transition-all border-2 border-dashed ${
                            dragOver
                                ? 'border-teal-500 bg-teal-50'
                                : file
                                ? 'border-teal-500 bg-teal-50 border-solid'
                                : 'border-gray-200 hover:border-teal-300'
                        }`}
                    >
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept="application/pdf"
                            className="hidden"
                            onChange={handleFilePick}
                        />
                        {file ? (
                            <div className="flex items-center gap-4 text-left">
                                <div className="w-14 h-14 rounded-xl bg-white border-2 border-teal-500 flex items-center justify-center text-2xl text-teal-600 shrink-0">📄</div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-bold text-teal-700 truncate">{file.name}</p>
                                    <p className="text-xs text-neutral-500 mt-0.5">{(file.size / 1024).toFixed(1)} KB · PDF</p>
                                    <span className="inline-block mt-1.5 bg-teal-100 rounded-lg px-2 py-0.5 text-[10px] font-bold text-teal-600">Tap to change</span>
                                </div>
                            </div>
                        ) : (
                            <div>
                                <div className="w-16 h-16 rounded-full bg-teal-50 flex items-center justify-center mx-auto mb-3">
                                    <span className="text-3xl text-teal-500">⇧</span>
                                </div>
                                <p className="text-base font-bold text-neutral-900 mb-1">Choose a PDF file</p>
                                <p className="text-sm text-neutral-400">Click to browse or drag & drop</p>
                                <span className="inline-block mt-3 bg-neutral-100 rounded-lg px-3 py-1 text-xs font-semibold text-neutral-500">PDF · Max {MAX_PDF_MB}MB</span>
                            </div>
                        )}
                    </div>

                    <button
                        onClick={handleUploadPdf}
                        disabled={!fileBase64 || uploading}
                        className={`btn w-full py-4 text-base font-bold transition-all ${
                            fileBase64 && !uploading
                                ? 'gradient-teal text-white shadow-lg shadow-teal-200 hover:shadow-xl'
                                : 'bg-neutral-300 text-white cursor-not-allowed'
                        }`}
                    >
                        {uploading ? (
                            <span className="flex items-center gap-2">
                                <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                Reading PDF…
                            </span>
                        ) : (
                            <span className="flex items-center gap-2">
                                Continue <span className="text-xl">›</span>
                            </span>
                        )}
                    </button>
                </div>
            )}

            {/* ── Error ───────────────────────────────────── */}
            {error && (
                <div className="rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700 font-medium">
                    {error}
                </div>
            )}

            {/* ── Cost / balance bar ──────────────────────── */}
            <div className="card p-4 flex items-center justify-between">
                <div className="flex-1 text-center">
                    <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Cost per report</p>
                    <p className="text-xl font-extrabold text-teal-600">{COST} <span className="text-sm font-semibold text-neutral-400">credits</span></p>
                </div>
                <div className="w-px h-12 bg-neutral-100" />
                <div className="flex-1 text-center">
                    <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Your balance</p>
                    <p className={`text-xl font-extrabold ${balance < COST ? 'text-danger-600' : 'text-teal-600'}`}>
                        {balance} <span className="text-sm font-semibold text-neutral-400">credits</span>
                    </p>
                </div>
            </div>

            {/* ── Trust indicators (compact, non-interactive) ── */}
            <div className="flex items-center justify-center gap-6 text-xs text-neutral-400">
                <span className="inline-flex items-center gap-1.5">
                    <span aria-hidden="true">⚡</span>
                    AI-Powered
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span aria-hidden="true">◆</span>
                    Private & Secure
                </span>
            </div>

            {/* ── Manual entry (secondary) ─────────────────── */}
            <button
                onClick={loadPanels}
                className="w-full text-sm font-semibold text-neutral-400 hover:text-neutral-600 transition-colors py-1"
            >
                {showPanels ? 'Hide manual entry' : 'Enter values manually instead'}
            </button>

            {showPanels && (
                <div className="space-y-3 pt-1">
                    {panelsLoading ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">{[1, 2, 3, 4].map((i) => (<div key={i} className="card p-4 skeleton h-24 rounded-xl" />))}</div>
                    ) : (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {panels.map((panel) => (
                                <Link key={panel.slug} to={`/lab-results/${panel.slug}`} className="card p-4 hover:shadow-md hover:border-teal-200 transition-all">
                                    <p className="text-sm font-bold text-neutral-900">{panel.name}</p>
                                    <p className="text-xs text-neutral-500 mt-1 line-clamp-2">{panel.description || `Enter your ${panel.name} values for instant interpretation.`}</p>
                                    <p className="text-[10px] font-bold text-teal-700 mt-2 uppercase tracking-wider">{panel.test_count || 'Multiple'} tests →</p>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}