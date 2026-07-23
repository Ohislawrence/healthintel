import React, { useState, useRef } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

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
        if (picked.type !== 'application/pdf') {
            setUploadError('Please select a PDF file.');
            return;
        }
        if (picked.size > 10 * 1024 * 1024) {
            setUploadError('File must be under 10MB.');
            return;
        }
        setUploadError(null);
        setFile(picked);
        // Convert to base64
        const reader = new FileReader();
        reader.onloadend = () => {
            const base64 = (reader.result).split(',')[1];
            setFileBase64(base64);
        };
        reader.readAsDataURL(picked);
    };

    const handleDrop = (e) => {
        e.preventDefault();
        setDragOver(false);
        const dropped = e.dataTransfer.files?.[0];
        if (!dropped) return;
        if (dropped.type !== 'application/pdf') {
            setUploadError('Please drop a PDF file.');
            return;
        }
        if (dropped.size > 10 * 1024 * 1024) {
            setUploadError('File must be under 10MB.');
            return;
        }
        setUploadError(null);
        setFile(dropped);
        const reader = new FileReader();
        reader.onloadend = () => {
            const base64 = (reader.result).split(',')[1];
            setFileBase64(base64);
        };
        reader.readAsDataURL(dropped);
    };

    const handleUpload = async () => {
        if (!fileBase64) {
            setUploadError('Please select a PDF file first.');
            return;
        }
        setUploadLoading(true);
        setUploadError(null);
        try {
            const res = await api.post('/submissions/pdf', {
                pdf_base64: fileBase64,
                pdf_name: file?.name || 'report.pdf',
            });
            await fetchUser();
            navigate(`/lab-results/submission/${res.data.submission.id}`);
        } catch (err) {
            setUploadError(err?.message || 'Upload failed. Please try again.');
        } finally {
            setUploadLoading(false);
        }
    };

    const canUpload = fileBase64 && !uploadLoading && balance >= cost;

    return (
        <div className="space-y-6 max-w-xl mx-auto">
            {/* ── Hero ─────────────────────────────────── */}
            <div className="text-center">
                <div className="w-18 h-18 rounded-2xl bg-teal-50 border-2 border-teal-200 flex items-center justify-center mx-auto mb-4">
                    <span className="text-4xl text-teal-600">⇧</span>
                </div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Upload Lab Report</p>
                <p className="text-sm text-neutral-500 mt-2 max-w-md mx-auto leading-relaxed">
                    Upload a PDF of your lab test result and our AI will interpret it in clear, simple language
                </p>
            </div>

            {/* ── Upload Area ──────────────────────────── */}
            <div
                onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
                onDragLeave={() => setDragOver(false)}
                onDrop={handleDrop}
                onClick={() => fileInputRef.current?.click()}
                className={`card p-8 text-center cursor-pointer transition-all border-2 border-dashed ${
                    dragOver ? 'border-teal-500 bg-teal-50' :
                    file ? 'border-teal-500 bg-teal-50 border-solid' :
                    'border-neutral-200 hover:border-teal-300'
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
                        <div className="w-14 h-14 rounded-xl bg-white border-2 border-teal-500 flex items-center justify-center text-2xl text-teal-600 shrink-0">
                            ⚛
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-bold text-teal-700 truncate">{file.name}</p>
                            <p className="text-xs text-neutral-500 mt-0.5">
                                {(file.size / 1024).toFixed(1)} KB · PDF Document
                            </p>
                            <span className="inline-block mt-1.5 bg-teal-100 rounded-lg px-2 py-0.5 text-[10px] font-bold text-teal-600">
                                Tap to change
                            </span>
                        </div>
                    </div>
                ) : (
                    <div>
                        <div className="w-16 h-16 rounded-full bg-teal-50 flex items-center justify-center mx-auto mb-3">
                            <span className="text-3xl text-teal-500">⇧</span>
                        </div>
                        <p className="text-base font-bold text-neutral-900 mb-1">Choose a PDF file</p>
                        <p className="text-sm text-neutral-400">Click to browse or drag & drop</p>
                        <span className="inline-block mt-3 bg-neutral-100 rounded-lg px-3 py-1 text-xs font-semibold text-neutral-500">
                            PDF · Max 10MB
                        </span>
                    </div>
                )}
            </div>

            {uploadError && (
                <div className="rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700 font-medium">
                    {uploadError}
                </div>
            )}

            {/* ── Info Cards ───────────────────────────── */}
            <div className="grid grid-cols-2 gap-3">
                <div className="card p-4 text-center">
                    <span className="text-2xl block mb-2">⚡</span>
                    <p className="text-sm font-bold text-neutral-900 mb-1">AI-Powered</p>
                    <p className="text-xs text-neutral-400">Smart interpretation of your lab results</p>
                </div>
                <div className="card p-4 text-center">
                    <span className="text-2xl block mb-2">◆</span>
                    <p className="text-sm font-bold text-neutral-900 mb-1">Private & Secure</p>
                    <p className="text-xs text-neutral-400">Encrypted and never shared</p>
                </div>
            </div>

            {/* ── Cost Card ────────────────────────────── */}
            <div className="card p-5">
                <div className="flex items-center">
                    <div className="flex-1 text-center">
                        <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Cost Per Report</p>
                        <p className="text-xl font-extrabold text-teal-600">
                            {cost} <span className="text-sm font-semibold text-neutral-400">credits</span>
                        </p>
                    </div>
                    <div className="w-px h-12 bg-neutral-100" />
                    <div className="flex-1 text-center">
                        <p className="text-[10px] font-bold text-neutral-400 uppercase tracking-wider mb-1">Your Balance</p>
                        <p className={`text-xl font-extrabold ${balance < cost ? 'text-danger-600' : 'text-teal-600'}`}>
                            {balance} <span className="text-sm font-semibold text-neutral-400">credits</span>
                        </p>
                    </div>
                </div>
            </div>

            {/* ── Upload Button ────────────────────────── */}
            <button
                onClick={handleUpload}
                disabled={!canUpload}
                className={`btn w-full py-4 text-base font-bold transition-all ${
                    canUpload
                        ? 'gradient-teal text-white shadow-lg shadow-teal-200 hover:shadow-xl'
                        : 'bg-neutral-300 text-white cursor-not-allowed'
                }`}
            >
                {uploadLoading ? (
                    <span className="flex items-center gap-2">
                        <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                        Processing your report...
                    </span>
                ) : balance < cost ? (
                    'Need 3 credits — Top up first'
                ) : (
                    <span className="flex items-center gap-2">
                        Upload & Interpret
                        <span className="text-xl">›</span>
                    </span>
                )}
            </button>

            {/* ── Manual Entry Link ────────────────────── */}
            <button
                onClick={() => setShowPanels(!showPanels)}
                className="card p-4 flex items-center justify-center gap-2 text-neutral-500 hover:text-neutral-700 transition-colors"
            >
                <span className="text-lg">▼</span>
                <span className="text-sm font-semibold">Enter values manually instead</span>
            </button>

            {/* ── Panel Grid (collapsed by default) ────── */}
            {showPanels && (
                <div className="space-y-3 pt-2">
                    <p className="text-sm font-bold text-neutral-900">Pick a test panel</p>
                    {panelsLoading ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {[1,2,3,4].map(i => (
                                <div key={i} className="card p-4 skeleton h-24 rounded-xl" />
                            ))}
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {panels.map((panel) => (
                                <Link
                                    key={panel.slug}
                                    to={`/lab-results/${panel.slug}`}
                                    className="card p-4 hover:shadow-md hover:border-teal-200 transition-all"
                                >
                                    <p className="text-sm font-bold text-neutral-900">{panel.name}</p>
                                    <p className="text-xs text-neutral-500 mt-1 line-clamp-2">
                                        {panel.description || `Enter your ${panel.name} values for instant interpretation.`}
                                    </p>
                                    <p className="text-[10px] font-bold text-teal-700 mt-2 uppercase tracking-wider">
                                        {panel.test_count || 'Multiple'} tests →
                                    </p>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            )}

            {/* ── Tips Card ────────────────────────────── */}
            <div className="card p-5 bg-warning-50 border-warning-200">
                <p className="text-sm font-bold text-warning-800 mb-3">Tips for best results</p>
                {[
                    'Use a clear, text-based PDF (not a scanned image)',
                    'Ensure the report is in English',
                    'Include all pages of your report',
                    'Results typically appear within 30-60 seconds',
                ].map((tip, i) => (
                    <div key={i} className="flex gap-2 mb-1">
                        <span className="text-sm text-warning-600 font-bold">·</span>
                        <span className="text-sm text-warning-800 leading-relaxed">{tip}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}