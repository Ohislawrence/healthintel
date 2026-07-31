import React, { useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

const MALE_CATS = [
    { min: 0, max: 0.90, color: '#22C55E', risk: 'Low', body: 'Apple' },
    { min: 0.91, max: 0.99, color: '#F59E0B', risk: 'Moderate', body: 'Apple' },
    { min: 1.0, max: 99, color: '#EF4444', risk: 'High', body: 'Apple' },
];
const FEMALE_CATS = [
    { min: 0, max: 0.80, color: '#22C55E', risk: 'Low', body: 'Pear' },
    { min: 0.81, max: 0.85, color: '#F59E0B', risk: 'Moderate', body: 'Pear' },
    { min: 0.86, max: 99, color: '#EF4444', risk: 'High', body: 'Pear' },
];

function getCategory(ratio, gender) {
    return (gender === 'male' ? MALE_CATS : FEMALE_CATS).find(c => ratio >= c.min && ratio <= c.max) || (gender === 'male' ? MALE_CATS : FEMALE_CATS)[2];
}

export default function WaistHipCalculator() {
    const [gender, setGender] = useState(null);
    const [waist, setWaist] = useState('');
    const [hip, setHip] = useState('');
    const [result, setResult] = useState(null);

    const saveResult = useCallback(async (ratio, category) => {
        try {
            await api.post('/health-metrics', {
                metric_type: 'waist_hip_ratio',
                data: { ratio, waist_cm: parseFloat(waist), hip_cm: parseFloat(hip), gender, risk: category.risk, body_shape: category.body },
            });
        } catch {}
    }, [waist, hip, gender]);

    const calculate = () => {
        const w = parseFloat(waist); const h = parseFloat(hip);
        if (!w || !h || !gender || w <= 0 || h <= 0) return;
        const ratio = Math.round((w / h) * 100) / 100;
        const category = getCategory(ratio, gender);
        setResult({ ratio, category });
        saveResult(ratio, category);
    };

    const reset = () => { setGender(null); setWaist(''); setHip(''); setResult(null); };
    const canCalc = gender && waist && hip && parseFloat(waist) > 0 && parseFloat(hip) > 0;

    return (
        <div className="space-y-5">
            <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Waist-to-Hip Ratio</p>
                <p className="text-sm text-neutral-500 mt-0.5">Measures body fat distribution. Higher ratio = more abdominal fat = increased health risks.</p>
            </div>

            <div className="card p-5">
                <p className="text-base font-bold text-neutral-900 mb-4">Your Measurements</p>

                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Biological Sex</p>
                <div className="grid grid-cols-2 gap-3 mb-4">
                    {[{ key: 'male', label: 'Male' }, { key: 'female', label: 'Female' }].map((g) => (
                        <button
                            key={g.key}
                            onClick={() => setGender(g.key)}
                            className={`py-3.5 rounded-xl text-base font-bold border-2 transition-all ${
                                gender === g.key ? 'border-orange-500 bg-orange-50 text-orange-600' : 'border-neutral-200 bg-neutral-50 text-neutral-500'
                            }`}
                        >
                            {g.label}
                        </button>
                    ))}
                </div>

                {[{ label: 'Waist (cm)', state: waist, set: setWaist, hint: 'at navel' }, { label: 'Hip (cm)', state: hip, set: setHip, hint: 'widest point' }].map(f => (
                    <div key={f.label} className="mb-4">
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">{f.label}</p>
                        <p className="text-xs text-neutral-400 mb-1">{f.hint}</p>
                        <div className="flex items-center bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                            <input type="number" value={f.state} onChange={e => f.set(e.target.value)} placeholder="0" className="flex-1 px-4 py-3 text-lg font-bold text-neutral-900 bg-transparent outline-none" maxLength={5} />
                            <span className="text-sm font-semibold text-neutral-400 pr-4">cm</span>
                        </div>
                    </div>
                ))}

                <button onClick={calculate} disabled={!canCalc} className={`btn w-full ${canCalc ? 'bg-orange-500 hover:bg-orange-600 text-white' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed'}`}>
                    Calculate Ratio
                </button>
                {result && (
                    <button onClick={reset} className="block mx-auto mt-3 text-sm font-semibold text-neutral-400 hover:text-neutral-600">
                        Reset
                    </button>
                )}
            </div>

            {result && (
                <div className="card p-6 text-center">
                    <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Your Ratio</p>
                    <p className="text-5xl font-extrabold tracking-tight mb-4" style={{ color: result.category.color }}>{result.ratio}</p>
                    <div className="flex items-center bg-neutral-50 rounded-xl p-3 mb-4">
                        <div className="flex-1 text-center">
                            <p className="text-xs font-bold text-neutral-400">Waist</p>
                            <p className="text-base font-bold text-neutral-900">{waist} cm</p>
                        </div>
                        <div className="w-px h-8 bg-neutral-200" />
                        <div className="flex-1 text-center">
                            <p className="text-xs font-bold text-neutral-400">Hip</p>
                            <p className="text-base font-bold text-neutral-900">{hip} cm</p>
                        </div>
                    </div>
                    <div className="inline-flex items-center gap-2 rounded-xl px-4 py-2 mb-3" style={{ backgroundColor: result.category.color + '15' }}>
                        <span className="w-2 h-2 rounded-full" style={{ backgroundColor: result.category.color }} />
                        <span className="text-base font-bold" style={{ color: result.category.color }}>{result.category.risk} Risk — {result.category.body} Shape</span>
                    </div>
                    <div className="flex h-2 rounded-full overflow-hidden mb-1">
                        {(gender === 'male' ? MALE_CATS : FEMALE_CATS).map(c => (
                            <div key={c.risk} className="transition-opacity" style={{ backgroundColor: c.color, flex: c.max <= 0.90 ? 2 : 1, opacity: result.category === c ? 1 : 0.3 }} />
                        ))}
                    </div>
                    <p className="text-xs text-neutral-400 text-center mt-1">← Lower Risk &nbsp;&nbsp; Higher Risk →</p>
                </div>
            )}

            <div className="card p-5 bg-amber-50 border-amber-200">
                <p className="text-base font-bold text-amber-800 mb-2">About WHR</p>
                <p className="text-sm text-amber-800 leading-relaxed">WHO recognizes WHR as a better predictor of cardiovascular risk than BMI alone.</p>
            </div>
        </div>
    );
}