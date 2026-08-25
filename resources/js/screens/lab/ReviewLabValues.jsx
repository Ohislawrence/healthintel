import React, { useEffect, useRef, useState } from 'react';
import { useNavigate, useLocation, Link } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

/**
 * Step 2 — Review & confirm the uploaded report (shared by PDF + image uploads).
 *
 * If structured values were extracted, the user can review/edit them.
 * If nothing was extracted (narrative reports like CT scans, MRI, ECG,
 * ultrasound, etc.), the full report is automatically interpreted in plain
 * language — no manual step required.
 */

let uid = 0;
const nextId = () => `row-${++uid}`;

export default function ReviewLabValues() {
    const navigate = useNavigate();
    const location = useLocation();
    const { fetchUser } = useAuthStore();

    const draft = location.state || {};
    const draftId = draft.draft_id;
    const initialTests = Array.isArray(draft.extracted_tests) ? draft.extracted_tests : [];
    const isImage = draft.is_image === true;
    const isNarrativeReport = initialTests.length === 0;

    const [rows, setRows] = useState(() =>
        initialTests.map((t) => ({
            id: nextId(),
            test_name: t.test_name || '',
            value: t.value ?? '',
            unit: t.unit || '',
        }))
    );
    const [error, setError] = useState(null);

    const cost = 3;
    const balance = useAuthStore((s) => s.user?.credits) ?? 0;

    const confirmMutation = useMutation({
        mutationFn: (payload) => api.post(`/submissions/draft/${draftId}/confirm`, payload),
        onSuccess: async (res) => {
            await fetchUser();
            const id = res?.data?.submission?.id;
            if (id) {
                navigate(`/lab-results/submission/${id}`, { replace: true });
            } else {
                setError('Something went wrong. Please try again.');
            }
        },
        onError: (err) => {
            setError(err?.message || 'Could not interpret this report. Please try again.');
        },
    });

    // Auto-interpret narrative reports (scans, imaging, clinical notes that
    // have no numeric table values). The AI explains the full report in plain
    // language without requiring the user to click anything.
    const autoFiredRef = useRef(false);
    useEffect(() => {
        if (!draftId || !isNarrativeReport) return;
        if (autoFiredRef.current) return;
        autoFiredRef.current = true;
        confirmMutation.mutate({ confirmed_values: [] });
    }, [draftId, isNarrativeReport]);

    const updateRow = (id, field, value) => {
        setRows((prev) => prev.map((r) => (r.id === id ? { ...r, [field]: value } : r)));
    };

    const removeRow = (id) => {
        setRows((prev) => prev.filter((r) => r.id !== id));
    };

    const addRow = () => {
        setRows((prev) => [...prev, { id: nextId(), test_name: '', value: '', unit: '' }]);
    };

    const validRows = rows.filter((r) => r.test_name.trim() && r.value !== '' && !isNaN(parseFloat(r.value)));
    const isNarrative = rows.length === 0;
    const creditsNeeded = Math.max(cost - balance, 0);

    const handleConfirm = () => {
        setError(null);
        // If the user has started adding rows, they must fill at least one
        // valid value (or remove the empty row).
        if (rows.length > 0 && validRows.length === 0) {
            setError('Add at least one test with a valid numeric value, or remove the empty row.');
            return;
        }
        confirmMutation.mutate({
            confirmed_values: validRows.map((r) => ({
                test_name: r.test_name.trim(),
                value: parseFloat(r.value),
                unit: r.unit.trim(),
            })),
        });
    };

    return (
        <div className="space-y-5 max-w-xl mx-auto">
            {/* Header */}
            <div>
                <button
                    onClick={() => navigate('/lab-results')}
                    className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 mb-3 block"
                >
                    ‹ Back
                </button>

                {/* Step indicator */}
                <div className="flex items-center gap-2 mb-3">
                    <span className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-400">
                        <span className="w-5 h-5 rounded-full bg-teal-600 text-white flex items-center justify-center text-[10px]">✓</span>
                        Upload
                    </span>
                    <span className="h-px w-8 bg-neutral-200" />
                    <span className="inline-flex items-center gap-1.5 text-xs font-bold text-teal-700 bg-teal-50 rounded-full px-2.5 py-1">
                        <span className="w-5 h-5 rounded-full bg-teal-600 text-white flex items-center justify-center text-[10px]">2</span>
                        Review
                    </span>
                </div>

                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">
                    {isNarrativeReport ? 'Explaining your report' : 'Review your report'}
                </p>
                <p className="text-sm text-neutral-500 mt-1 leading-relaxed">
                    {isNarrativeReport
                        ? 'We read your report and will explain it in simple, everyday language.'
                        : `We read ${isImage ? 'your photo' : 'your PDF'} — review the values below before we interpret them.`}
                </p>
            </div>

            {/* Error */}
            {error && (
                <div className="rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700 font-medium">
                    {error}
                </div>
            )}

            {/* Editable values */}
            <div className="space-y-3">
                {rows.length === 0 && (
                    <div className="card p-6 text-center">
                        <div className="text-3xl mb-2">🩺</div>
                        <p className="text-sm font-bold text-neutral-900 mb-1">
                            {confirmMutation.isPending ? 'Interpreting your report…' : 'No table values detected'}
                        </p>
                        <p className="text-sm text-neutral-500 leading-relaxed">
                            This is a narrative report (a scan or imaging note). We'll explain the full report in simple language.
                        </p>
                        {confirmMutation.isPending && (
                            <div className="mt-4 w-6 h-6 border-2 border-teal-500 border-t-transparent rounded-full animate-spin mx-auto" />
                        )}
                    </div>
                )}

                {rows.map((row) => (
                    <div key={row.id} className="card p-4">
                        <div className="grid grid-cols-[1fr_auto] gap-3 items-start">
                            <input
                                type="text"
                                value={row.test_name}
                                onChange={(e) => updateRow(row.id, 'test_name', e.target.value)}
                                placeholder="Test name (e.g. Haemoglobin)"
                                className="input-base font-semibold"
                            />
                            <button
                                onClick={() => removeRow(row.id)}
                                className="p-2 text-neutral-400 hover:text-danger-500 transition-colors"
                                aria-label="Remove test"
                            >
                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div className="grid grid-cols-2 gap-3 mt-2">
                            <input
                                type="number"
                                step="any"
                                value={row.value}
                                onChange={(e) => updateRow(row.id, 'value', e.target.value)}
                                placeholder="Value"
                                className="input-base"
                            />
                            <input
                                type="text"
                                value={row.unit}
                                onChange={(e) => updateRow(row.id, 'unit', e.target.value)}
                                placeholder="Unit (e.g. g/dL)"
                                className="input-base"
                            />
                        </div>
                    </div>
                ))}

                {!isNarrativeReport && (
                    <button
                        onClick={addRow}
                        className="w-full py-3 border-2 border-dashed border-gray-200 rounded-xl text-sm font-semibold text-neutral-500 hover:border-teal-300 hover:text-teal-600 transition-colors"
                    >
                        + Add value manually
                    </button>
                )}
            </div>

            {/* Cost / balance summary */}
            <div className="card p-4 flex items-center justify-between">
                <span className="text-sm text-neutral-500">Cost</span>
                <span className="text-sm font-bold text-neutral-900">
                    {cost} <span className="font-medium text-neutral-400">credits</span>
                </span>
                <span className="h-8 w-px bg-neutral-100" />
                <span className="text-sm text-neutral-500">Balance</span>
                <span className={`text-sm font-bold ${balance < cost ? 'text-danger-600' : 'text-teal-600'}`}>
                    {balance} <span className="font-medium text-neutral-400">credits</span>
                </span>
            </div>

            {balance < cost ? (
                <div className="card p-4 border-amber-200 bg-amber-50">
                    <p className="text-sm font-bold text-amber-900">You need {creditsNeeded} more credit{creditsNeeded > 1 ? 's' : ''} to finish this report.</p>
                    <p className="text-sm text-amber-800 mt-1 leading-relaxed">
                        Top up once, then continue immediately from this report without losing progress.
                    </p>
                    <div className="mt-3 h-2 rounded-full bg-amber-100 overflow-hidden">
                        <div className="h-full rounded-full bg-amber-500" style={{ width: `${Math.min((balance / cost) * 100, 100)}%` }} />
                    </div>
                </div>
            ) : (
                <div className="card p-4 border-teal-100 bg-teal-50">
                    <p className="text-sm font-bold text-teal-900">You have enough credits to continue now.</p>
                    <p className="text-sm text-teal-800 mt-1 leading-relaxed">
                        Confirm the values below and get your interpretation right away.
                    </p>
                </div>
            )}

            {/* Actions */}
            {balance < cost ? (
                <Link
                    to="/credits/buy"
                    state={{ from: 'lab-review', neededCredits: creditsNeeded, cost, balance }}
                    className="btn w-full py-4 text-base font-bold text-center transition-all bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-200"
                >
                    Top up to continue
                </Link>
            ) : (
                <button
                    onClick={handleConfirm}
                    disabled={confirmMutation.isPending}
                    className="btn w-full py-4 text-base font-bold transition-all gradient-teal text-white shadow-lg shadow-teal-200 hover:shadow-xl disabled:opacity-60"
                >
                    {confirmMutation.isPending ? (
                        <span className="flex items-center gap-2">
                            <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                            Interpreting…
                        </span>
                    ) : (
                        <span className="flex items-center gap-2">
                            {isNarrative ? 'Interpret report' : 'Confirm & interpret'} <span className="text-xl">›</span>
                        </span>
                    )}
                </button>
            )}
        </div>
    );
}