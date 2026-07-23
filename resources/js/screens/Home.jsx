import React from 'react';
import { Link } from 'react-router-dom';
import useAuthStore from '../stores/authStore';

export default function Home() {
    const { user } = useAuthStore();

    const features = [
        {
            icon: '🔬',
            title: 'Lab Result Interpretation',
            body: 'Upload your lab report PDF or enter values manually. Our AI explains every result in simple, everyday language — highlighting what needs attention.',
            link: '/register',
            linkText: 'Try it free',
        },
        {
            icon: '🩺',
            title: 'Symptom Checker',
            body: 'Describe what you\'re feeling. We\'ll suggest relevant tests and connect you with labs and hospitals near you that can run them.',
            link: '/register',
            linkText: 'Check symptoms',
        },
        {
            icon: '🏥',
            title: 'Provider Directory',
            body: 'Find hospitals, labs, specialists, and insurance providers near you. Verified listings with contact info and directions.',
            link: '/register',
            linkText: 'Find providers',
        },
    ];

    const stats = [
        { value: '50+', label: 'Test Panels' },
        { value: '200+', label: 'Symptoms Covered' },
        { value: '24/7', label: 'AI Available' },
        { value: 'Free', label: 'To Get Started' },
    ];

    return (
        <div className="min-h-screen bg-white">
            {/* Nav */}
            <header className="sticky top-0 z-40 border-b border-gray-100 bg-white/80 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                    <Link to="/" className="text-2xl font-bold tracking-tight">
                        <span className="text-teal-600">Health</span>
                        <span className="text-gray-900">Intel</span>
                    </Link>
                    <div className="flex items-center gap-3">
                        {user ? (
                            <Link
                                to="/dashboard"
                                className="rounded-lg bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700 transition-colors"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    to="/login"
                                    className="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
                                >
                                    Sign in
                                </Link>
                                <Link
                                    to="/register"
                                    className="rounded-lg bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700 shadow-sm transition-colors"
                                >
                                    Get started free
                                </Link>
                            </>
                        )}
                    </div>
                </div>
            </header>

            {/* Hero */}
            <section className="relative overflow-hidden bg-gradient-to-br from-teal-50 via-white to-emerald-50">
                <div className="mx-auto max-w-6xl px-4 pt-20 pb-24 text-center">
                    <div className="inline-flex items-center gap-2 rounded-full border border-teal-200 bg-teal-50 px-4 py-1.5 text-sm font-medium text-teal-700 mb-8">
                        ✨ AI-Powered Lab Result Interpretation
                    </div>
                    <h1 className="text-4xl font-bold tracking-tight text-gray-900 sm:text-6xl lg:text-7xl">
                        Understand your
                        <br />
                        <span className="text-teal-600">lab results</span> instantly
                    </h1>
                    <p className="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-gray-600">
                        Upload a PDF of your lab report and get a clear, plain-language explanation in seconds. 
                        No medical degree required — just answers you can actually understand.
                    </p>
                    <div className="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            to="/register"
                            className="inline-flex items-center justify-center rounded-xl bg-teal-600 px-8 py-4 text-base font-semibold text-white hover:bg-teal-700 shadow-lg shadow-teal-200 transition-all hover:shadow-xl"
                        >
                            Get started — it's free ✨
                        </Link>
                        <a
                            href="#features"
                            className="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-8 py-4 text-base font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            See how it works ↓
                        </a>
                    </div>
                </div>
                {/* Decorative gradient blob */}
                <div className="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-teal-100 opacity-30 blur-3xl" />
                <div className="absolute -bottom-20 -left-20 h-60 w-60 rounded-full bg-emerald-100 opacity-30 blur-3xl" />
            </section>

            {/* Stats */}
            <section className="border-y border-gray-100 bg-white">
                <div className="mx-auto max-w-6xl px-4 py-12">
                    <div className="grid grid-cols-2 gap-8 md:grid-cols-4">
                        {stats.map((stat) => (
                            <div key={stat.label} className="text-center">
                                <p className="text-3xl font-bold text-teal-600">{stat.value}</p>
                                <p className="mt-1 text-sm text-gray-500">{stat.label}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Features */}
            <section id="features" className="mx-auto max-w-6xl px-4 py-20">
                <div className="text-center mb-16">
                    <h2 className="text-3xl font-bold text-gray-900 sm:text-4xl">
                        Everything you need to understand your health
                    </h2>
                    <p className="mt-4 text-lg text-gray-600">
                        Three powerful tools, one simple platform.
                    </p>
                </div>
                <div className="grid gap-8 md:grid-cols-3">
                    {features.map((feature) => (
                        <div
                            key={feature.title}
                            className="group relative rounded-2xl border border-gray-200 bg-white p-8 shadow-sm transition-all hover:shadow-md hover:border-teal-200"
                        >
                            <div className="mb-5 text-4xl">{feature.icon}</div>
                            <h3 className="text-xl font-semibold text-gray-900">
                                {feature.title}
                            </h3>
                            <p className="mt-3 text-sm leading-relaxed text-gray-600">
                                {feature.body}
                            </p>
                            <Link
                                to={feature.link}
                                className="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-teal-600 hover:text-teal-700 transition-colors"
                            >
                                {feature.linkText} →
                            </Link>
                        </div>
                    ))}
                </div>
            </section>

            {/* How it works */}
            <section className="bg-gray-50 py-20">
                <div className="mx-auto max-w-6xl px-4">
                    <div className="text-center mb-16">
                        <h2 className="text-3xl font-bold text-gray-900 sm:text-4xl">
                            How it works
                        </h2>
                    </div>
                    <div className="grid gap-8 md:grid-cols-3">
                        {[
                            { step: '01', title: 'Create an account', body: 'Sign up in seconds. You get 3 free credits to start.' },
                            { step: '02', title: 'Upload or enter results', body: 'Upload a PDF lab report or enter your values manually.' },
                            { step: '03', title: 'Get your explanation', body: 'Our AI reads your results and explains them in plain, simple language.' },
                        ].map((item) => (
                            <div key={item.step} className="text-center">
                                <p className="text-4xl font-bold text-teal-200">{item.step}</p>
                                <h3 className="mt-4 text-lg font-semibold text-gray-900">{item.title}</h3>
                                <p className="mt-2 text-sm text-gray-600">{item.body}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* CTA */}
            <section className="mx-auto max-w-4xl px-4 py-20 text-center">
                <h2 className="text-3xl font-bold text-gray-900 sm:text-4xl">
                    Ready to understand your lab results?
                </h2>
                <p className="mx-auto mt-4 max-w-xl text-lg text-gray-600">
                    Join thousands of Nigerians who use HealthIntel to make sense of their medical tests.
                </p>
                <div className="mt-8">
                    <Link
                        to="/register"
                        className="inline-flex items-center rounded-xl bg-teal-600 px-8 py-4 text-base font-semibold text-white hover:bg-teal-700 shadow-lg shadow-teal-200 transition-all"
                    >
                        Get started free →
                    </Link>
                </div>
            </section>

            {/* Footer */}
            <footer className="border-t border-gray-200 bg-white py-8">
                <div className="mx-auto max-w-6xl px-4 text-center text-sm text-gray-500">
                    <p className="mb-2">
                        <span className="font-semibold text-teal-600">Health</span>
                        <span className="font-semibold text-gray-900">Intel</span>
                    </p>
                    <p>
                        This platform provides educational information only.
                        It is not a substitute for professional medical advice, diagnosis, or treatment.
                    </p>
                    <p className="mt-4">
                        &copy; {new Date().getFullYear()} HealthIntel. All rights reserved.
                    </p>
                </div>
            </footer>
        </div>
    );
}