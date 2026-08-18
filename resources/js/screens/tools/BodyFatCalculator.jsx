import React, { useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

const BODY_FAT_CATEGORIES = [
    { min: 2, max: 5, label: 'Essential fat', color: '#0EA5E9' },
    { min: 6, max: 13, label: 'Athletes', color: '#22C55E' },
    { min: 14, max: 17, label: 'Fitness', color: '#84CC16' },
    { min: 18, max: 24, label: 'Average', color: '#F59E0B' },
    { min: 25, max: 100, label: 'Obese', color: '#EF4444' },
];

function getCategory(pct, gender) {
    // Female ranges are slightly higher; simple adjustment for display.
    const shift = gender === 'female' ? 5 : 0;
    const adjusted = pct - shift;
    return BODY_FAT_CATEGORIES.find(c => adjusted >= c.min && adjusted <= c.max) || BODY_FAT_CATEGORIES[BODY_FAT_CATEGORIES.length - 1];
}

export default function BodyFatCalculator() {
    const [gender, setGender] = useState(null);
    const [height, setHeight] = useState('');
    const [neck, setNeck] = useState('');
    const [waist, setWaist] = useState('');
    const [hip, setHip] = useState('');
    const [result, setResult] = useState(null);

    const saveResult = useCallback(async (pct, category) => {
        try {
            await api.post('/health-metrics', {
                metric_type: 'body_fat',
                data: {
                    body_fat_pct: pct,
                    gender,
                    height_cm: parseFloat(height),
                    neck_cm: parseFloat(neck),
                    waist_cm: parseFloat(waist),
                    hip_cm: gender === 'female' ? parseFloat(hip) : null,
                    category: category.label,
                },
            });
        } catch {}
    }, [gender, height, neck, waist, hip]);

    const calculate = () => {
        const h = parseFloat(height);
        const n = parseFloat(neck);
        const w = parseFloat(waist);
        const hp = gender === 'female' ? parseFloat(hip) : 0;
        if (!h || !n || !w || !gender || h <= 0 || n <= 0 || w <= 0) return;
        if (gender === 'female' && (!hp || hp <= 0)) return;

        let pct;
        if (gender === 'male') {
            pct = 495 / (1.0324 - 0.19077 * Math.log10(w - n) + 0.15456 * Math.log10(h)) - 450;
        } else {
            pct = 495 / (1.29579 - 0.35004 * Math.log10(w + hp - n) + 0.22100 * Math.log10(h)) - 450;
        }

        const rounded = Math.max(0, Math.round(pct * 10) / 10);
        const category = getCategory(rounded, gender);
        setResult({ pct: rounded, category });
        saveResult(rounded, category);
    };

    const reset = () => { setGender(null); setHeight(''); setNeck(''); setWaist(''); setHip(''); setResult(null); };
    const canCalc = gender && height && neck && waist && parseFloat(height) > 0 && parseFloat(neck) > 0 && parseFloat(waist) > 0 && (gender !== 'female' || (hip && parseFloat(hip) > 0));

    return (
        <div className="space-y-5">
            <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">Body Fat % Estimator</p>
                <p className="text-sm text-neutral-500 mt-0.5">Estimate body fat using the U.S. Navy tape-measure method.</p>
            </div>

            <div className="card p-5">
                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Biological Sex</p>
                <div className="grid grid-cols-2 gap-3 mb-4">
                    {[{ key: 'male', label: 'Male' }, { key: 'female', label: 'Female' }].map((g) => (
                        <button key={g.key} onClick={() => setGender(g.key)} className={`py-3.5 rounded-xl text-base font-bold border-2 transition-all ${gender === g.key ? 'border-indigo-500 bg-indigo-50 text-indigo-600' : 'border-neutral-200 bg-neutral-50 text-neutral-500'}`}>{g.label}</button>
                    ))}
                </div>

                {[{ label: 'Height', state: height, set: setHeight, unit: 'cm', hint: null }, { label: 'Neck', state: neck, set: setNeck, unit: 'cm', hint: 'below larynx' }, { label: 'Waist', state: waist, set: setWaist, unit: 'cm', hint: 'at navel' }, ...(gender === 'female' ? [{ label: 'Hip', state: hip, set: setHip, unit: 'cm', hint: 'widest point' }] : [])].map((f) => (
                    <div key={f.label} className="mb-4">
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">{f.label}</p>
                        {f.hint && <p className="text-xs text-neutral-400 mb-1">{f.hint}</p>}
                        <div className="flex items-center bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                            <input type="number" value={f.state} onChange={e => f.set(e.target.value)} placeholder="0" className="flex-1 px-4 py-3 text-lg font-bold text-neutral-900 bg-transparent outline-none" maxLength={5} />
                            <span className="text-sm font-semibold text-neutral-400 pr-4">{f.unit}</span>
                        </div>
                    </div>
                ))}

                <button onClick={calculate} disabled={!canCalc} className={`btn w-full ${canCalc ? 'bg-indigo-500 hover:bg-indigo-600 text-white' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed'}`}>Calculate Body Fat %</button>
                {result && <button onClick={reset} className="block mx-auto mt-3 text-sm font-semibold text-neutral-400 hover:text-neutral-600">Reset</button>}
            </div>

            {result && (
                <div className="card p-6 text-center">
                    <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Estimated Body Fat</p>
                    <p className="text-5xl font-extrabold tracking-tight mb-3" style={{ color: result.category.color }}>{result.pct}%</p>
                    <div className="inline-flex items-center gap-2 rounded-xl px-4 py-2 mb-3" style={{ backgroundColor: result.category.color + '15' }}>
                        <span className="w-2 h-2 rounded-full" style={{ backgroundColor: result.category.color }} />
                        <span className="text-base font-bold" style={{ color: result.category.color }}>{result.category.label}</span>
                    </div>
                    <p className="text-sm text-neutral-600 leading-relaxed">Body fat percentage is a more nuanced indicator than BMI alone. Discuss your target range with a healthcare professional.</p>
                </div>
            )}

            <div className="card p-5 bg-amber-50 border-amber-200">
                <p className="text-base font-bold text-amber-800 mb-2">About This Method</p>
                <p className="text-sm text-amber-800 leading-relaxed">Uses the U.S. Navy circumference method — an estimate based on tape measurements. Individual results vary; consult a professional for precise body composition analysis.</p>
            </div>
        </div>
    );
}