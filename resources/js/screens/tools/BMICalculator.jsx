import React, { useState, useCallback } from 'react';
import { Link } from 'react-router-dom';
import api from '../../lib/api';

const BMI_CATEGORIES = [
    { min: 0, max: 18.4, label: 'Underweight', color: '#0EA5E9', advice: 'You may be underweight. Consider consulting a nutritionist to help you reach a healthy weight.' },
    { min: 18.5, max: 24.9, label: 'Normal weight', color: '#22C55E', advice: 'You are within a healthy weight range. Keep maintaining a balanced diet and regular exercise.' },
    { min: 25.0, max: 29.9, label: 'Overweight', color: '#F59E0B', advice: 'You may be overweight. Consider adopting healthier eating habits and increasing physical activity.' },
    { min: 30.0, max: 34.9, label: 'Obese (Class I)', color: '#F97316', advice: 'Your weight may increase health risks. Please consult a healthcare professional for guidance.' },
    { min: 35.0, max: 39.9, label: 'Obese (Class II)', color: '#EF4444', advice: 'Your weight may significantly increase health risks. Please consult a healthcare professional.' },
    { min: 40.0, max: 999, label: 'Obese (Class III)', color: '#B91C1C', advice: 'Your weight may pose serious health risks. Please seek medical advice as soon as possible.' },
];

function getCategory(bmi) {
    return BMI_CATEGORIES.find(c => bmi >= c.min && bmi <= c.max) || BMI_CATEGORIES[0];
}

export default function BMICalculator() {
    const [height, setHeight] = useState('');
    const [weight, setWeight] = useState('');
    const [result, setResult] = useState(null);

    const saveResult = useCallback(async (bmi, cat) => {
        try {
            await api.post('/health-metrics', { metric_type: 'bmi', data: { bmi, height_cm: parseFloat(height), weight_kg: parseFloat(weight), category: cat.label, category_color: cat.color } });
        } catch {}
    }, [height, weight]);

    const calculate = () => {
        const h = parseFloat(height); const w = parseFloat(weight);
        if (!h || !w || h <= 0 || w <= 0) return;
        const bmi = w / ((h / 100) ** 2);
        const rounded = Math.round(bmi * 10) / 10;
        const category = getCategory(rounded);
        setResult({ bmi: rounded, category }); saveResult(rounded, category);
    };

    const reset = () => { setHeight(''); setWeight(''); setResult(null); };
    const canCalculate = height && weight && parseFloat(height) > 0 && parseFloat(weight) > 0;

    return (
        <div className="space-y-5">
            <Link to="/health-tools" className="text-sm font-semibold text-neutral-400 hover:text-neutral-600 block">‹ Back to tools</Link>
            <div>
                <p className="text-2xl font-extrabold text-neutral-900 tracking-tight">BMI Calculator</p>
                <p className="text-sm text-neutral-500 mt-0.5">Body Mass Index estimates body fat based on your height and weight.</p>
            </div>
            <div className="card p-5">
                <p className="text-base font-bold text-neutral-900 mb-4">Your Measurements</p>
                <div className="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Height</p>
                        <div className="flex items-center bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                            <input type="number" value={height} onChange={e => setHeight(e.target.value)} placeholder="0" className="flex-1 px-4 py-3 text-lg font-bold text-neutral-900 bg-transparent outline-none" maxLength={5} />
                            <span className="text-sm font-semibold text-neutral-400 pr-4">cm</span>
                        </div>
                    </div>
                    <div>
                        <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1">Weight</p>
                        <div className="flex items-center bg-neutral-50 rounded-xl border border-neutral-200 overflow-hidden">
                            <input type="number" value={weight} onChange={e => setWeight(e.target.value)} placeholder="0" className="flex-1 px-4 py-3 text-lg font-bold text-neutral-900 bg-transparent outline-none" maxLength={5} />
                            <span className="text-sm font-semibold text-neutral-400 pr-4">kg</span>
                        </div>
                    </div>
                </div>
                <button onClick={calculate} disabled={!canCalculate} className={`btn w-full ${canCalculate ? 'btn-primary' : 'bg-neutral-200 text-neutral-400 cursor-not-allowed'}`}>Calculate BMI</button>
                {result && <button onClick={reset} className="block mx-auto mt-3 text-sm font-semibold text-neutral-400 hover:text-neutral-600">Reset</button>}
            </div>
            {result && (
                <div className="card p-6 text-center">
                    <p className="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Your BMI</p>
                    <p className="text-5xl font-extrabold tracking-tight mb-3" style={{ color: result.category.color }}>{result.bmi}</p>
                    <div className="inline-flex items-center gap-2 rounded-xl px-4 py-2 mb-3" style={{ backgroundColor: result.category.color + '15' }}>
                        <span className="w-2 h-2 rounded-full" style={{ backgroundColor: result.category.color }} />
                        <span className="text-base font-bold" style={{ color: result.category.color }}>{result.category.label}</span>
                    </div>
                    <p className="text-sm text-neutral-600 leading-relaxed mb-5 px-4">{result.category.advice}</p>
                    <div className="flex h-2 rounded-full overflow-hidden mb-1">
                        {BMI_CATEGORIES.slice(0, 4).map((cat) => (<div key={cat.label} className="flex-1 transition-opacity" style={{ backgroundColor: cat.color, opacity: cat.label === result.category.label ? 1 : 0.3 }} />))}
                    </div>
                    <div className="flex justify-between text-xs text-neutral-400 px-1">{BMI_CATEGORIES.slice(0, 4).map((cat) => (<span key={cat.label}>{cat.max}</span>))}</div>
                </div>
            )}
            <div className="card p-5 bg-amber-50 border-amber-200">
                <p className="text-base font-bold text-amber-800 mb-2">About BMI</p>
                <p className="text-sm text-amber-800 leading-relaxed">BMI is a screening tool used to estimate body fat. It is not a diagnostic tool. Factors like muscle mass, bone density, age, and ethnicity can affect accuracy. Always consult a healthcare professional for a complete health assessment.</p>
            </div>
        </div>
    );
}