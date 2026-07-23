import React, { useState } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import useAuthStore from '../stores/authStore';

const navItems = [
    { path: '/dashboard', label: 'Home', icon: '⌂' },
    { path: '/lab-results', label: 'Lab Tests', icon: '⚛' },
    { path: '/symptom-checker', label: 'Symptoms', icon: '♡' },
    { path: '/directory', label: 'Directory', icon: '⚕' },
    { path: '/credits', label: 'Credits', icon: '◆' },
];

export default function AppLayout({ children }) {
    const { user, logout } = useAuthStore();
    const location = useLocation();
    const navigate = useNavigate();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const handleLogout = async () => {
        await logout();
        navigate('/');
    };

    const isActive = (path) => {
        if (path === '/dashboard') return location.pathname === '/dashboard';
        return location.pathname.startsWith(path);
    };

    const firstName = user?.name?.split(' ')[0] || '';
    const profileComplete = !!user?.health_profile?.profile_completed;

    return (
        <div className="min-h-screen bg-neutral-50">
            {/* ── Desktop Header ─────────────────────────── */}
            <header className="hidden md:flex sticky top-0 z-40 h-16 items-center justify-between border-b border-neutral-100 bg-white/90 backdrop-blur px-6">
                <Link to="/dashboard" className="flex items-center gap-2">
                    <span className="text-xl font-extrabold tracking-tight text-teal-700">Health</span>
                    <span className="text-xl font-extrabold tracking-tight text-neutral-900">Intel</span>
                </Link>
                <div className="flex items-center gap-4">
                    <Link to="/credits" className="flex items-center gap-1.5 rounded-xl bg-teal-50 px-3 py-1.5 text-sm font-semibold text-teal-700 hover:bg-teal-100 transition-colors">
                        <span>◆</span>
                        <span>{user?.credits ?? 0} credits</span>
                    </Link>
                    <button
                        onClick={handleLogout}
                        className="rounded-xl bg-neutral-100 px-3 py-1.5 text-sm font-semibold text-neutral-600 hover:bg-neutral-200 transition-colors"
                    >
                        Sign out ↗
                    </button>
                </div>
            </header>

            {/* ── Desktop Sidebar + Main ─────────────────── */}
            <div className="flex">
                {/* Sidebar */}
                <aside className="hidden md:flex flex-col w-60 min-h-[calc(100vh-64px)] border-r border-neutral-100 bg-white pt-4 px-3 sticky top-16">
                    <nav className="flex flex-col gap-1">
                        {navItems.map((item) => {
                            const active = isActive(item.path);
                            return (
                                <Link
                                    key={item.path}
                                    to={item.path}
                                    className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all ${
                                        active
                                            ? 'bg-teal-50 text-teal-700'
                                            : 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-800'
                                    }`}
                                >
                                    <span className={`text-lg ${active ? 'text-teal-600' : 'text-neutral-400'}`}>
                                        {item.icon}
                                    </span>
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>
                    <div className="mt-auto mb-4 pt-4 border-t border-neutral-100">
                        <Link
                            to="/onboarding"
                            className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-all ${
                                profileComplete
                                    ? 'text-neutral-500 hover:bg-neutral-50 hover:text-neutral-800'
                                    : 'text-warning-600 bg-warning-50 hover:bg-warning-100'
                            }`}
                        >
                            <span className="text-lg">◉</span>
                            {profileComplete ? 'Health Profile' : 'Complete Profile'}
                        </Link>
                    </div>
                </aside>

                {/* Main Content */}
                <main className="flex-1 p-4 md:p-6 pb-24 md:pb-6 max-w-4xl mx-auto w-full">
                    <div className="animate-fade-in-up">{children}</div>
                    {/* Mobile tab bar spacer */}
                    <div className="tab-bar-spacer md:hidden" />
                </main>
            </div>

            {/* ── Mobile Top Bar ─────────────────────────── */}
            <header className="md:hidden sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-neutral-100 px-4 py-3">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-xs font-semibold text-neutral-400 uppercase tracking-widest">HealthIntel</p>
                        <p className="text-lg font-extrabold text-neutral-900 leading-tight">
                            Hello, {firstName || 'there'}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link to="/credits" className="rounded-lg bg-teal-50 px-2.5 py-1.5 text-xs font-bold text-teal-700">
                            ◆ {user?.credits ?? 0}
                        </Link>
                        <button
                            onClick={handleLogout}
                            className="w-9 h-9 flex items-center justify-center rounded-lg bg-neutral-100 text-neutral-500 font-bold text-sm"
                        >
                            ↗
                        </button>
                    </div>
                </div>
            </header>

            {/* ── Mobile Bottom Tab Bar ──────────────────── */}
            <div className="tab-bar-fixed md:hidden">
                {navItems.map((item) => {
                    const active = isActive(item.path);
                    return (
                        <Link
                            key={item.path}
                            to={item.path}
                            className={`flex flex-col items-center justify-center gap-0.5 py-1 px-3 rounded-xl transition-all ${
                                active ? 'text-teal-700' : 'text-neutral-400'
                            }`}
                        >
                            <span className={`text-xl ${active ? 'bg-teal-50 w-8 h-8 flex items-center justify-center rounded-lg' : ''}`}>
                                {item.icon}
                            </span>
                            <span className="text-[10px] font-bold">{item.label}</span>
                            {active && <span className="w-1 h-1 rounded-full bg-teal-500 mt-0.5" />}
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}