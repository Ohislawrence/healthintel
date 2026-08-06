import React, { useState, useRef } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';
import CameraUpload from '../../components/upload/CameraUpload';

export default function PanelPicker() {
    const navigate = useNavigate();
    const { user, fetchUser } = useAuthStore();
    const fileInputRef = useRef(null);
    const [file, setFile] = useState(null);
    const [fileBase64, setFileBase64] = useState(null);
    const [uploadLoading, setUploadLoading] = useState(false);
    const [uploadError, setUploadError] = useState(null);
    const [dragOver, setDragOver] = useState(false);
    const [showPanels, setShowPanels] = useState(false);
    const [activeMode, setActiveMode] = useState(null); // 'camera' | 'pdf' | null

    const { data, isLoading: panelsLoading } = useQuery({
        queryKey: ['panels'],
        queryFn: () => api.get('/panels'),
        enabled: showPanels,
    });
    const panels = data?.data?.panels || [];

    const cost = 3;
    const balance = user?.credits ?? 0;

    const handleFilePick = (e) => {
        const picked = e.target.files?.[0];
        if (!picked) return;
        if (picked.type !== 'application/pdf') { setUploadError('Please select a PDF file.'); return; }
        if (picked.size > 10 * 1024 * 1024) { setUploadError('File must be under 10MB.'); return; }
        setUploadError(null);
        setFile(picked);
        const reader = new FileReader();
        reader.onloadend = () => setFileBase64(reader.result.split(',')[1]);
        reader.readAsDataURL(picked);
    };

    const handleUpload = async () => {
        if (!fileBase64) { setUploadError('Please select a PDF file first.'); return; }
        setUploadLoading(true);
        setUploadError(null);
        try {
            const res = await api.post('/submissions/pdf', { pdf_base64: fileBase64, pdf_name: file?.name || 'report.pdf' });
            await fetchUser();
            navigate(`/lab-results/submission/${res.data.submission.id}`);
        } catch (err) {
            setUploadError(err?.message || 'Upload failed. Please try again.');
        } finally {
            setUploadLoading(false);
        }
    };

    const handleCameraDraft = (draft) => {
        if (draft?.draft_id && draft?.extracted_tests?.length > 0) {
            setUploadError(null);
            // Navigate to confirm extracted values — reuse existing confirm flow
            navigate(`/lab-results`, { state: { imageDraft: draft } });
            alert(`${draft.extracted_tests.length} test value(s) found! Please confirm them.\n\nTests: ${draft.extracted_tests.map(t => `${t.test_name}: ${t.value} ${t.unit}`).join(', ')}`);
        }
    };

    const canUpload = fileBase64 && !uploadLoading && balance >= cost;

    return (
        <div className="space-y-6 max-w-xl mx-auto">
            {/* ── Header ─────────────────────────────────── */}
            <div className="text-center">
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Upload Lab Report</p>
                <p className="text-sm text-neutral-500 mt-2 max-w-md mx-auto leading-relaxed">
                    Choose how you'd like to share your lab results — take a quick photo or upload a PDF
                </p>
            </div>

            {/* ── Two Upload Options ──────────────────────── */}
            <div className="grid grid-cols-2 gap-3">
                {/* Camera Option */}
                <button
                    onClick={() => setActiveMode(activeMode === 'camera' ? null : 'camera')}
                    className={`card p-5 text-center transition-all border-2 ${
                        activeMode === 'camera' ? 'border-teal-500 bg-teal-50 shadow-md' : 'border-gray-200 hover:border-teal-300 hover:shadow-sm'
                    }`}
                >
                    <span className="text-3xl block mb-2">📸</span>
                    <p className="text-sm font-bold text-neutral-900 mb-1">Take a Photo</p>
                    <p className="text-xs text-neutral-400">Snap a picture of your lab report</p>
                </button>

                {/* PDF Option */}
                <button
                    onClick={() => setActiveMode(activeMode === 'pdf' ? null : 'pdf')}
                    className={`card p-5 text-center transition-all border-2 ${
                        activeMode === 'pdf' ? 'border-teal-500 bg-teal-50 shadow-md' : 'border-gray-200 hover:border-teal-300 hover:shadow-sm'
                    }`}
                >
                    <span className="text-3xl block mb-2">📄</span>
                    <p className="text-sm font-bold text-neutral-900 mb-1">Upload PDF</p>
                    <p className="text-xs text-neutral-400">Select a PDF from your device</p>
                </button>
            </div>

            {/* ── Camera Upload Panel ─────────────────────── */}
            {activeMode === 'camera' && (
                <CameraUpload onDraft={handleCameraDraft} onError={(err) => setUploadError(err)} />
            )}

            {/* ── PDF Upload Panel ────────────────────────── */}
            {activeMode === 'pdf' && (
                <>
                    <div
                        onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
                        onDragLeave={() => setDragOver(false)}
                        onDrop={(e) => { e.preventDefault(); setDragOver(false); const f = e.dataTransfer.files?.[0]; if (f) { if (f.type !== 'application/pdf') { setUploadError('Please drop a PDF file.'); return; } setUploadError(null); setFile(f); const reader = new FileReader(); reader.onloadend = () => setFileBase64(reader.result.split(',')[1]); reader.readAsDataURL(f); } }}
                        onClick={() => fileInputRef.current?.click()}
                        className={`card p-8 text-center cursor-pointer transition-all border-2 border-dashed ${
                            dragOver ? 'border-teal-500 bg-teal-50' : file ? 'border-teal-500 bg-teal-50 border-solid' : 'border-gray-200 hover:border-teal-300'
                        }`}
                    >
                        <input ref={fileInputRef} type="file" accept="application/pdf" className="hidden" onChange={handleFilePick} />
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
                                <div className="w-16 h-16 rounded-full bg-teal-50 flex items-center justify-center mx-auto mb-3"><span className="text-3xl text-teal-500">⇧</span></div>
                                <p className="text-base font-bold text-neutral-900 mb-1">Choose a PDF file</p>
                                <p className="text-sm text-neutral-400">Click to browse or drag & drop</p>
                                <span className="inline-block mt-3 bg-neutral-100 rounded-lg px-3 py-1 text-xs font-semibold text-neutral-500">PDF · Max 10MB</span>
                            </div>
                        )}
                    </div>
                    <button onClick={handleUpload} disabled={!canUpload}
                        className={`btn w-full py-4 text-base font-bold transition-all ${canUpload ? 'gradient-teal text-white shadow-lg shadow-teal-200 hover:shadow-xl' : 'bg-neutral-300 text-white cursor-not-allowed'}`}
                    >
                        {uploadLoading ? (<span className="flex items-center gap-2"><span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />Processing...</span>)
                        : balance < cost ? 'Need 3 credits — Top up first'
                        : (<span className="flex items-center gap-2">Upload & Interpret <span className="text-xl">›</span></span>)}
                    </button>
                </>
            )}

            {/* ── Error Display ────────────────────────────── */}
            {uploadError && (
                <div className="rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700 font-medium">{uploadError}</div>
            )}

            {/* ── Info Cards ───────────────────────────────── */}
            <div className="grid grid-cols-2 gap-3">
                <div className="card p-4 text-center"><span className="text-2xl block mb-2">⚡</span><p className="text-sm font-bold text-neutral-900 mb-1">AI-Powered</p><p className="text-xs text-neutral-400">Smart interpretation of your lab results</p></div>
                <div className="card p-4 text-center"><span className="text-2xl block mb-2">◆</span><p className="text-sm font-bold text-neutral-900 mb-1">Private & Secure</p><p className="text-xs text-neutral-400">Encrypted and never shared</p></div>
            </div>

            {/* ── Cost Card ────────────────────────────────── */}
            <div className="card p-5">
                <div className="flex items-center">
                    <div className="flex-1 text-center"><p className="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Cost Per Report</p><p className="text-xl font-extrabold text-teal-600">{cost} <span className="text-sm font-semibold text-neutral-400">credits</span></p></div>
                    <div className="w-px h-12 bg-neutral-100" />
                    <div className="flex-1 text-center"><p className="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Your Balance</p><p className={`text-xl font-extrabold ${balance < cost ? 'text-danger-600' : 'text-teal-600'}`}>{balance} <span className="text-sm font-semibold text-neutral-400">credits</span></p></div>
                </div>
            </div>

            {/* ── Manual Entry ─────────────────────────────── */}
            <button onClick={() => setShowPanels(!showPanels)} className="card p-4 flex items-center justify-center gap-2 text-neutral-500 hover:text-neutral-700 transition-colors">
                <span className="text-lg">▼</span>
                <span className="text-sm font-semibold">Enter values manually instead</span>
            </button>

            {showPanels && (
                <div className="space-y-3 pt-2">
                    <p className="text-sm font-bold text-neutral-900">Pick a test panel</p>
                    {panelsLoading ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">{[1,2,3,4].map(i => (<div key={i} className="card p-4 skeleton h-24 rounded-xl" />))}</div>
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