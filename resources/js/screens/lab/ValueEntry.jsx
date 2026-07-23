import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import api from '../../lib/api';
import Disclaimer from '../../components/ui/Disclaimer';

export default function ValueEntry() {
    const { slug } = useParams();
    const navigate = useNavigate();
    const [values, setValues] = useState({});
    const [error, setError] = useState(null);

    const { data, isLoading } = useQuery({
        queryKey: ['panel', slug],
        queryFn: () => api.get(`/panels/${slug}`),
        enabled: !!slug,
    });

    const panel = data?.data?.panel;
    const tests = panel?.tests || [];

    const submitMutation = useMutation({
        mutationFn: (payload) => api.post('/lab-submit', payload),
        onSuccess: (res) => {
            navigate(`/lab-results/${res.data.submission.id}`);
        },
        onError: (err) => {
            setError(err?.message || 'Submission failed. Please try again.');
        },
    });

    const handleChange = (testSlug, value) => {
        setValues((prev) => ({ ...prev, [testSlug]: value }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setError(null);

        const payload = {
            panel_slug: slug,
            values: Object.entries(values).map(([test_slug, value]) => ({
                test_slug,
                value: parseFloat(value),
            })),
        };

        submitMutation.mutate(payload);
    };

    const isComplete = tests.length > 0 && tests.every((t) => values[t.slug] !== undefined && values[t.slug] !== '');

    if (isLoading) {
        return (
            <div className="space-y-4 animate-pulse">
                <div className="h-8 w-48 rounded bg-gray-100" />
                <div className="h-6 w-72 rounded bg-gray-100" />
                {[1, 2, 3, 4, 5].map((i) => (
                    <div key={i} className="h-16 rounded-xl bg-gray-100" />
                ))}
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-xl font-semibold text-gray-900">{panel?.name}</h2>
                <p className="mt-1 text-sm text-gray-500">
                    Enter your test values below. All fields are required.
                </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                {error && (
                    <div className="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
                )}

                {tests.map((test) => (
                    <div key={test.slug} className="rounded-xl border border-gray-200 bg-white p-4">
                        <label htmlFor={test.slug} className="block text-sm font-medium text-gray-700">
                            {test.name}
                        </label>
                        <div className="mt-1 flex items-center gap-2">
                            <input
                                id={test.slug}
                                type="number"
                                step="any"
                                required
                                value={values[test.slug] || ''}
                                onChange={(e) => handleChange(test.slug, e.target.value)}
                                className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none"
                                placeholder={`Enter value`}
                            />
                            <span className="text-sm text-gray-500 w-20">{test.unit}</span>
                        </div>
                    </div>
                ))}

                <button
                    type="submit"
                    disabled={!isComplete || submitMutation.isPending}
                    className="w-full rounded-lg bg-teal-600 px-4 py-3 text-sm font-semibold text-white hover:bg-teal-700 disabled:opacity-50 transition-colors"
                >
                    {submitMutation.isPending ? 'Submitting...' : 'Submit for interpretation (2 credits)'}
                </button>
            </form>

            <Disclaimer />
        </div>
    );
}
