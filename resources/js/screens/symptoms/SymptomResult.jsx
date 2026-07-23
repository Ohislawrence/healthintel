import React from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

export default function SymptomResult() {
    const location = useLocation();
    const navigate = useNavigate();
    const { recommendations, symptoms } = location.state || {};
    const panels = recommendations?.panels || [];

    return (
        <div className="space-y-5">
            <button onClick={() => navigate('/symptom-checker')} className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back</button>

            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Recommended Tests</p>
                <p className="text-sm text-neutral-500 mt-0.5">
                    Based on {symptoms?.length || 0} symptom{symptoms?.length !== 1 ? 's' : ''} you selected
                </p>
            </div>

            {panels.length === 0 ? (
                <div className="card p-8 text-center">
                    <span className="text-3xl block mb-3">♡</span>
                    <p className="text-sm font-bold text-neutral-900 mb-1">No recommendations found</p>
                    <p className="text-xs text-neutral-500">Try selecting different or additional symptoms</p>
                </div>
            ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {panels.map((panel) => (
                        <div key={panel.slug} className="card p-4">
                            <p className="text-sm font-bold text-neutral-900">{panel.name}</p>
                            <p className="text-xs text-neutral-500 mt-1 line-clamp-2">{panel.description || 'Recommended test panel'}</p>
                            <div className="flex items-center gap-2 mt-3">
                                {panel.match_score && (
                                    <span className="badge badge-success">Match {panel.match_score}%</span>
                                )}
                                <button
                                    onClick={() => navigate(`/lab-results/${panel.slug}`)}
                                    className="ml-auto text-xs font-bold text-teal-700 hover:text-teal-800"
                                >
                                    Check Now →
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Disclaimer */}
            <div className="card p-4 bg-neutral-25 border-neutral-200">
                <p className="text-xs text-neutral-500 leading-relaxed">
                    <span className="font-bold">⚠ Medical Disclaimer:</span> These test recommendations are AI-generated for educational purposes only. Always consult with a qualified healthcare provider for medical advice and diagnosis.
                </p>
            </div>
        </div>
    );
}