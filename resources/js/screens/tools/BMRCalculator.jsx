import React, { useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

const ACTIVITY_LEVELS = [
    { value: 1.2, label: 'Sedentary', desc: 'Little or no exercise' },
    { value: 1.375, label: 'Lightly active', desc: 'Light exercise 1-3 days/week' },
    { value: 1.55, label: 'Moderately active', desc: 'Moderate exercise 3-5 days/week' },
    { value: 1.725, label: 'Very active', desc: 'Hard exercise 6-7 days/week' },
    { value: 1.9, label: 'Extra active', desc: 'Very hard exercise + physical job' },
];

export default function BMRCalculator() {
    const [gender, setGender] = useState(null);
    const [age, setAge] = useState('');
    const [height, setHeight] = useState('');
    const [weight, setWeight] = useState('');
    const [activityLevel, setActivityLevel] = useState(1.2);
    const [result, setResult] = useState(null);

    const saveResult = useCallback(async (bmr, tdee) => {
        try { await api.post('/health-metrics', { metric_type: 'bmr', data: { bmr, tdee, weight_kg: parseFloat(weight), height_cm: parseFloat(height), age: parseInt(age), sex: gender, activity_level: activityLevel } }); } catch {}
    }, [weight, height, age, gender, activityLevel]);

    const calculate = () => {
        const a = parseFloat(age); const h = parseFloat(height); const w = parseFloat(weight);
        if (!a || !h || !w || !gender || a <= 0 || h <= 0 || w <= 0) return;
        const bmr = gender === 'male' ? 10 * w + 6.25 * h - 5 * a + 5 : 10 * w + 6.25 * h - 5 * a - 161;
        const tdee = Math.round(bmr * activityLevel);
        setResult({ bmr: Math.round(bmr), tdee }); saveResult(Math.round(bmr), tdee);
    };

    const reset = () => { setGender(null); setAge(''); setHeight(''); setWeight(''); setActivityLevel(1.2); setResult(null); };
    const canCalculate = gender && age && height && weight && parseFloat(age) > 0 && parseFloat(height) > 0 && parseFloat(weight) > 0;

    return (
        <div className="space-y-5">
            <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">BMR & TDEE Calculator</p>
                <p className="text-sm text-neutral-500 mt-0.5">Estimate how many calories your body burns per day.</p>
            </div>
            <div className="card p-5">
                <p className="text-base font-bold text-neutral-900 mb-4">Your Details</p>
                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Biological Sex</p>
                <div className="grid grid-cols-2 gap-3 mb-4">
                    {[{ key: 'male', label: 'Male' }, { key: 'female', label: 'Female' }].map((g) => (
                        <button key={g.key} onClick={() => setGender(g.key)} className={`py-3.5 rounded-xl text-base font-bold border-2 transition-all ${gender === g.key ? 'border-orange-500 bg-orange-50 text-orange-600' : 'border-neutral-200 bg-neutral-50 text-neutral-500'}`}>{g.label}</button>
                    ))}
                </div>
                {[{ label: 'Age', state: age, set: setAge, unit: 'years', max: 3, placeholder: '0' }, { label: 'Height', state: height, set: setHeight, unit: 'cm', max: 5, placeholder: '0' }, { label: 'Weight', state: weight, set: setWeight, unit: 'kg', max: 5, placeholder: '0' }].map((f) => (
                    <div key={f.label} className="mb-4">
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">{f.label}</p>
                        <div className="flex items-center bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                            <input type="number" value={f.state} onChange={e => f.set(e.target.value)} placeholder={f.placeholder} className="flex-1 px-4 py-3 text-lg font-bold text-neutral-900 bg-transparent outline-none" maxLength={f.max} />
                            <span className="text-sm font-semibold text-neutral-400 pr-4">{f.unit}</span>
                        </div>
                    </div>
                ))}
                <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Activity Level</p>
                <div className="space-y-1 mb-4">
                    {ACTIVITY_LEVELS.map((level) => (
                        <button key={level.value} onClick={() => setActivityLevel(level.value)} className={`w-full flex items-center gap-3 p-3 rounded-xl text-left border transition-all ${activityLevel === level.value ? 'bg-orange-50 border-orange-300' : 'bg-neutral-50 border-transparent'}`}>
                            <span className={`w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 ${activityLevel === level.value ? 'border-orange-500' : 'border-neutral-300'}`}>
                                {activityLevel === level.value && <span className="w-2.5 h-2.5 rounded-full bg-orange-500" />}
                            </span>
                            <div><p className={`text-sm font-semibold ${activityLevel === level.value ? 'text-orange-700' : 'text-neutral-600'}`}>{level.label}</p><p className="text-xs text-neutral-400">{level.desc}</p></div>
                        </button>
                    ))}
                </div>
                <button onClick={calculate} disabled={!canCalculate} className={`btn w-full ${canCalculate ? 'bg-orange-500 hover:bg-orange-600 text-white' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed'}`}>Calculate BMR & TDEE</button>
                {result && <button onClick={reset} className="block mx-auto mt-3 text-sm font-semibold text-neutral-400 hover:text-neutral-600">Reset</button>}
            </div>
            {result && (
                <div className="space-y-3">
                    <div className="grid grid-cols-2 gap-3">
                        <div className="card p-5 text-center bg-green-50 border-green-200"><p className="text-xs font-bold text-green-600 uppercase tracking-wider mb-1">BMR</p><p className="text-4xl font-extrabold text-green-700 tracking-tight">{result.bmr}</p><p className="text-sm font-semibold text-green-500">calories/day</p></div>
                        <div className="card p-5 text-center bg-orange-50 border-orange-200"><p className="text-xs font-bold text-orange-500 uppercase tracking-wider mb-1">TDEE</p><p className="text-4xl font-extrabold text-orange-700 tracking-tight">{result.tdee}</p><p className="text-sm font-semibold text-orange-500">calories/day</p></div>
                    </div>
                    <div className="card p-5">
                        <p className="text-base font-bold text-neutral-900 mb-4 text-center">Calorie Goals (based on TDEE)</p>
                        <div className="grid grid-cols-3 gap-2">
                            {[{ label: 'Weight Loss', val: `${Math.round(result.tdee * 0.8)} - ${Math.round(result.tdee * 0.9)}` }, { label: 'Maintenance', val: `${result.tdee}` }, { label: 'Weight Gain', val: `${Math.round(result.tdee * 1.1)} - ${Math.round(result.tdee * 1.2)}` }].map((g) => (
                                <div key={g.label} className="text-center"><p className="text-xs font-bold text-neutral-400 uppercase mb-1">{g.label}</p><p className="text-sm font-bold text-neutral-900">{g.val}</p><p className="text-xs text-neutral-400">cal/day</p></div>
                            ))}
                        </div>
                    </div>
                </div>
            )}
            <div className="card p-5 bg-amber-50 border-amber-200">
                <p className="text-base font-bold text-amber-800 mb-2">About BMR & TDEE</p>
                <p className="text-sm text-amber-800 leading-relaxed">Uses the Mifflin-St Jeor equation. These are estimates — individual metabolism varies. Consult a nutritionist for personalized advice.</p>
            </div>
        </div>
    );
}