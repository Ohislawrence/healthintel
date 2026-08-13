import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import useAuthStore from '../../stores/authStore';

const SLIDES = [
    {
        icon: '⚛',
        accent: '#0F766E',
        title: 'Welcome to HealthIntel',
        body: 'Turn your lab reports into clear, actionable health insights. Here’s a quick tour of how everything works.',
        visual: (
            <div className="w-full max-w-[260px] mx-auto grid grid-cols-2 gap-3">
                {[['⚛', 'Lab Results'], ['♡', 'Symptoms'], ['⚕', 'Providers'], ['◉', 'Health Tools']].map(([ic, label]) => (
                    <div key={label} className="card p-4 text-center">
                        <span className="text-2xl block mb-1">{ic}</span>
                        <span className="text-[11px] font-bold text-neutral-600">{label}</span>
                    </div>
                ))}
            </div>
        ),
    },
    {
        icon: '📄',
        accent: '#4F46E5',
        title: 'Add your lab report',
        body: 'From the Lab Tests tab, upload a PDF of your lab report or snap a photo of it with your camera.',
        visual: (
            <div className="w-full max-w-[260px] mx-auto space-y-3">
                <div className="card p-4 flex items-center gap-3 border-2 border-teal-300 bg-teal-50">
                    <span className="text-3xl">📄</span>
                    <div className="text-left">
                        <p className="text-sm font-bold text-neutral-900">report.pdf</p>
                        <p className="text-xs text-neutral-500">120 KB · PDF</p>
                    </div>
                </div>
                <div className="card p-4 flex items-center gap-3 border-2 border-neutral-200">
                    <span className="text-3xl">📸</span>
                    <div className="text-left">
                        <p className="text-sm font-bold text-neutral-900">Take a photo</p>
                        <p className="text-xs text-neutral-500">Snap your lab report</p>
                    </div>
                </div>
            </div>
        ),
    },
    {
        icon: '✦',
        accent: '#7C3AED',
        title: 'Get an AI interpretation',
        body: 'Our AI reads your results and explains what each marker means, flags anything out of range, and suggests next steps — in plain language.',
        visual: (
            <div className="w-full max-w-[260px] mx-auto card p-4 space-y-3 text-left">
                <div className="flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full bg-amber-400" />
                    <p className="text-xs font-bold text-neutral-900">Fasting Glucose</p>
                    <span className="ml-auto badge badge-warning">High</span>
                </div>
                <p className="text-xs text-neutral-500 leading-relaxed">
                    Your glucose is slightly above the normal range. Consider a follow-up with your doctor and review your carbohydrate intake.
                </p>
            </div>
        ),
    },
    {
        icon: '📈',
        accent: '#16A34A',
        title: 'Track your trends',
        body: 'Every result is saved so you can watch your health improve over time with simple, at-a-glance charts.',
        visual: (
            <div className="w-full max-w-[260px] mx-auto card p-4">
                <svg viewBox="0 0 220 80" className="w-full h-20">
                    <polyline points="0,60 40,48 80,52 120,34 160,38 220,14" fill="none" stroke="#0F766E" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
                    <circle cx="220" cy="14" r="5" fill="#0F766E" />
                    <line x1="0" y1="70" x2="220" y2="70" stroke="#E5E7EB" strokeWidth="1" />
                </svg>
                <p className="text-center text-[11px] font-bold text-neutral-400 mt-1">Results over time</p>
            </div>
        ),
    },
    {
        icon: '◆',
        accent: '#D97706',
        title: 'Credits & more',
        body: 'Interpreting a report uses credits — you already got free ones for signing up. You can also check symptoms, find nearby providers, and explore health tools.',
        visual: (
            <div className="w-full max-w-[260px] mx-auto grid grid-cols-3 gap-2 text-center">
                {[['◆', 'Credits'], ['♡', 'Symptom Check'], ['⚕', 'Find Providers']].map(([ic, label]) => (
                    <div key={label} className="card p-3">
                        <span className="text-xl block mb-1">{ic}</span>
                        <span className="text-[10px] font-bold text-neutral-500">{label}</span>
                    </div>
                ))}
            </div>
        ),
    },
    {
        icon: '✓',
        accent: '#0F766E',
        title: 'You’re all set!',
        body: 'You can revisit this tour anytime from your dashboard. Let’s get your first lab result interpreted.',
        visual: (
            <div className="w-full max-w-[260px] mx-auto card p-6 text-center border-2 border-teal-200 bg-teal-50">
                <span className="inline-flex w-16 h-16 rounded-2xl bg-teal-500 text-white items-center justify-center text-3xl mb-3">✓</span>
                <p className="text-sm font-bold text-neutral-900">Ready when you are</p>
                <p className="text-xs text-neutral-500 mt-1">Start with a lab report upload</p>
            </div>
        ),
    },
];

export default function OnboardingTour() {
    const navigate = useNavigate();
    const { hasHealthProfile } = useAuthStore();
    const [index, setIndex] = useState(0);
    const total = SLIDES.length;
    const slide = SLIDES[index];
    const isLast = index === total - 1;

    const finish = () => {
        navigate(hasHealthProfile() ? '/dashboard' : '/onboarding');
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-neutral-50 px-4 py-10">
            <div className="w-full max-w-md animate-fade-in-up">
                {/* Brand */}
                <div className="text-center mb-6">
                    <span className="text-xl font-extrabold tracking-tight text-teal-700">Health</span>
                    <span className="text-xl font-extrabold tracking-tight text-neutral-900">Intel</span>
                </div>

                {/* Skip */}
                <div className="flex justify-end mb-3">
                    {!isLast && (
                        <button onClick={finish} className="text-xs font-semibold text-neutral-400 hover:text-neutral-600">
                            Skip tour →
                        </button>
                    )}
                </div>

                {/* Card */}
                <div className="card p-6 text-center">
                    <div
                        className="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-4"
                        style={{ backgroundColor: (slide.accent || '#0F766E') + '15' }}
                    >
                        {slide.icon}
                    </div>
                    <h2 className="text-xl font-extrabold text-neutral-900 tracking-tight">{slide.title}</h2>
                    <p className="text-sm text-neutral-500 mt-2 leading-relaxed max-w-xs mx-auto">{slide.body}</p>

                    <div className="mt-6 mb-6">{slide.visual}</div>

                    {/* Dots */}
                    <div className="flex items-center justify-center gap-1.5 mb-6">
                        {SLIDES.map((_, i) => (
                            <button
                                key={i}
                                onClick={() => setIndex(i)}
                                aria-label={`Go to step ${i + 1}`}
                                className={`h-1.5 rounded-full transition-all ${i === index ? 'w-6 bg-teal-500' : 'w-1.5 bg-neutral-200 hover:bg-neutral-300'}`}
                            />
                        ))}
                    </div>

                    {/* Controls */}
                    <div className="flex gap-3">
                        {index > 0 && (
                            <button onClick={() => setIndex(i => i - 1)} className="btn btn-outline flex-1">
                                Back
                            </button>
                        )}
                        {isLast ? (
                            <button onClick={finish} className="btn btn-primary flex-1">
                                Let’s go
                            </button>
                        ) : (
                            <button onClick={() => setIndex(i => i + 1)} className="btn btn-primary flex-1">
                                Next
                            </button>
                        )}
                    </div>
                </div>

                {/* Replay / dashboard fallback */}
                <div className="mt-5 text-center">
                    <Link to="/dashboard" className="text-xs font-semibold text-neutral-400 hover:text-neutral-600">
                        Go to dashboard
                    </Link>
                </div>
            </div>
        </div>
    );
}