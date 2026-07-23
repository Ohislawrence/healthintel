import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import useAuthStore from '../../stores/authStore';

export default function ValueEntry() {
    const { slug } = useParams();
    const navigate = useNavigate();
    const { fetchUser } = useAuthStore();
    const [values, setValues] = useState({});
    const [error, setError] = useState(null);

    const { data: panelData, isLoading } = useQuery({
        queryKey: ['panel', slug],
        queryFn: () => api.get(`/panels/${slug}`),
    });
    const panel = panelData?.data || {};
    const tests = panel.tests || [];

    useEffect(() => {
        if (tests.length > 0) {
            const initial = {};
            tests.forEach(t => { initial[t.slug] = ''; });
            setValues(initial);
        }
    }, [tests.length]);

    const submitMutation = useMutation({
        mutationFn: (data) => api.post('/submissions', data),
        onSuccess: async (res) => {
            await fetchUser();
            navigate(`/lab-results/submission/${res.data.submission.id}`);
        },
        onError: (err) => setError(err?.message || 'Submission failed.'),
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        setError(null);
        const testValues = Object.entries(values).map(([testSlug, val]) => ({
            test_slug: testSlug,
            value: parseFloat(val),
        }));
        submitMutation.mutate({
            test_panel_slug: slug,
            test_values: testValues,
        });
    };

    if (isLoading) {
        return <div className="card p-8 text-center"><div className="skeleton h-6 w-48 mx-auto rounded" /></div>;
    }

    return (
        <div className="space-y-5 max-w-lg mx-auto">
            <div>
                <button onClick={() => navigate('/lab-results')} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 mb-3 block">‹ Back</button>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">{panel.name}</p>
                <p className="text-sm text-neutral-500 mt-0.5">Enter your lab values below</p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                {error && (
                    <div className="rounded-xl bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-700 font-medium">{error}</div>
                )}
                {tests.map((test) => (
                    <div key={test.slug} className="card p-4">
                        <label className="text-sm font-bold text-neutral-900">{test.name}</label>
                        <p className="text-xs text-neutral-400 mt-0.5 mb-2">{test.unit && `Unit: ${test.unit}`}</p>
                        <input
                            type="number"
                            step="any"
                            value={values[test.slug] || ''}
                            onChange={(e) => setValues({ ...values, [test.slug]: e.target.value })}
                            className="input-base"
                            placeholder={`Enter ${test.name} value`}
                            required
                        />
                    </div>
                ))}
                <button type="submit" disabled={submitMutation.isPending} className="btn btn-primary w-full">
                    {submitMutation.isPending ? 'Submitting...' : `Submit Results (Costs ${panel.credits_per_submission || 3} credits)`}
                </button>
            </form>
        </div>
    );
}