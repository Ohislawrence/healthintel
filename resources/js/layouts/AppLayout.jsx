import React, { useState, useEffect, useRef } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import useAuthStore from '../stores/authStore';
import api from '../lib/api';
import SearchModal from '../components/ui/SearchModal';
import NotificationsDrawer from '../components/ui/NotificationsDrawer';

const navItems = [
    { path: '/dashboard', label: 'Home', icon: '⌂' },
    { path: '/lab-results', label: 'Lab Tests', icon: '⚛' },
    { path: '/symptom-checker', label: 'Symptoms', icon: '♡' },
    { path: '/directory', label: 'Directory', icon: '⚕' },
    { path: '/referral', label: 'Referrals', icon: '👥' },
    { path: '/credits', label: 'Credits', icon: '◆' },
];

export default function AppLayout({ children }) {
    const { user, logout } = useAuthStore();
    const location = useLocation();
    const navigate = useNavigate();

    const [searchOpen, setSearchOpen] = useState(false);
    const [notifOpen, setNotifOpen] = useState(false);
    const [profileOpen, setProfileOpen] = useState(false);
    const profileRef = useRef(null);
    const notifBtnRef = useRef(null);

    const handleLogout = async () => {
        await logout();
        navigate('/');
    };

    const isActive = (path) => {
        if (path === '/dashboard') return location.pathname === '/dashboard';
        return location.pathname.startsWith(path);
    };

    const profileComplete = !!user?.health_profile?.profile_completed;

    // Unread notification count
    const { data: unreadData } = useQuery({
        queryKey: ['notifications-unread-count'],
        queryFn: () => api.get('/notifications/unread-count'),
        refetchInterval: 30000,
        staleTime: 15000,
    });
    const unreadCount = unreadData?.data?.unread_count ?? 0;

    // Close profile dropdown on outside click
    useEffect(() => {
        const handler = (e) => {
            if (profileRef.current && !profileRef.current.contains(e.target)) {
                setProfileOpen(false);
            }
        };
        if (profileOpen) {
            document.addEventListener('mousedown', handler);
            document.addEventListener('touchstart', handler);
        }
        return () => {
            document.removeEventListener('mousedown', handler);
            document.removeEventListener('touchstart', handler);
        };
    }, [profileOpen]);

    // Keyboard shortcut: Cmd/Ctrl+K for search
    useEffect(() => {
        const handler = (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setSearchOpen(true);
            }
        };
        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, []);

    // Build user info for profile dropdown
    const firstName = user?.name?.split(' ')[0] || 'User';
    const userInitial = firstName.charAt(0).toUpperCase();
    const userEmail = user?.email || '';
    const userPhone = user?.phone || user?.health_profile?.phone || '';
    const profileBadge = profileComplete ? 'Profile complete' : 'Profile incomplete';

    return (
        <div className="min-h-screen bg-neutral-50">
            {/* ═══════════════════════════════════════════════
                  DESKTOP HEADER
               ═══════════════════════════════════════════════ */}
            <header className="hidden md:flex sticky top-0 z-40 h-16 items-center justify-between border-b border-neutral-100 bg-white/90 backdrop-blur px-6">
                <Link to="/dashboard" className="flex items-center gap-2 shrink-0">
                    <span className="text-xl font-extrabold tracking-tight text-teal-700">Health</span>
                    <span className="text-xl font-extrabold tracking-tight text-neutral-900">Intel</span>
                </Link>

                {/* Desktop right group: Search | Notifications | Credits | Profile */}
                <div className="flex items-center gap-1">
                    {/* Search trigger */}
                    <button
                        onClick={() => setSearchOpen(true)}
                        className="flex items-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-1.5 text-sm font-medium text-neutral-500 hover:border-neutral-300 hover:bg-white hover:text-neutral-700 transition-all"
                        title="Search (Ctrl+K)"
                    >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span className="hidden lg:inline">Search…</span>
                        <kbd className="hidden lg:inline-flex items-center gap-0.5 rounded bg-neutral-200 px-1.5 py-0.5 text-[10px] font-mono font-bold text-neutral-500">
                            <span className="text-xs">⌘</span>K
                        </kbd>
                    </button>

                    {/* Notification bell */}
                    <button
                        ref={notifBtnRef}
                        onClick={() => setNotifOpen(true)}
                        className="relative p-2 rounded-xl text-neutral-500 hover:bg-neutral-100 hover:text-neutral-700 transition-all"
                        aria-label="Notifications"
                    >
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        {unreadCount > 0 && (
                            <span className="absolute top-1 right-1 min-w-[18px] h-[18px] rounded-full bg-red-500 border-2 border-white flex items-center justify-center text-[10px] font-bold text-white px-1">
                                {unreadCount > 9 ? '9+' : unreadCount}
                            </span>
                        )}
                    </button>

                    {/* Credits badge */}
                    <Link to="/credits" className="flex items-center gap-1.5 rounded-xl bg-teal-50 px-3 py-1.5 text-sm font-semibold text-teal-700 hover:bg-teal-100 transition-colors">
                        <span>◆</span>
                        <span>{user?.credits ?? 0}</span>
                    </Link>

                    {/* Profile dropdown */}
                    <div className="relative" ref={profileRef}>
                        <button
                            onClick={() => setProfileOpen(!profileOpen)}
                            className="flex items-center gap-1.5 rounded-xl bg-neutral-100 hover:bg-neutral-200 transition-colors p-1 pl-3"
                        >
                            <span className="text-sm font-semibold text-neutral-700 truncate max-w-[100px]">{firstName}</span>
                            <span className="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-sm font-bold">
                                {userInitial}
                            </span>
                            <svg className={`w-4 h-4 text-neutral-400 transition-transform ${profileOpen ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {profileOpen && (
                            <div className="absolute right-0 top-full mt-2 w-64 bg-white rounded-xl shadow-lg border border-neutral-100 overflow-hidden z-50 animate-fade-in-up">
                                {/* User info */}
                                <div className="px-4 py-3 border-b border-neutral-100">
                                    <div className="flex items-center gap-3">
                                        <span className="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-base font-bold shrink-0">
                                            {userInitial}
                                        </span>
                                        <div className="min-w-0">
                                            <p className="text-sm font-bold text-neutral-900 truncate">{user?.name}</p>
                                            <p className="text-xs text-neutral-500 truncate">{userEmail}</p>
                                        </div>
                                    </div>
                                    {userPhone && (
                                        <p className="text-xs text-neutral-400 mt-2 ml-[52px]">{userPhone}</p>
                                    )}
                                    <div className="flex items-center gap-2 mt-3 ml-[52px]">
                                        <span className={`w-1.5 h-1.5 rounded-full ${profileComplete ? 'bg-green-500' : 'bg-amber-500'}`} />
                                        <span className="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider">{profileBadge}</span>
                                    </div>
                                </div>

                                {/* Menu items */}
                                <div className="py-1">
                                    <Link
                                        to="/onboarding"
                                        onClick={() => setProfileOpen(false)}
                                        className="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition-colors"
                                    >
                                        <span className="text-lg">◉</span>
                                        Health Profile
                                    </Link>
                                    <Link
                                        to="/credits"
                                        onClick={() => setProfileOpen(false)}
                                        className="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition-colors"
                                    >
                                        <span className="text-lg">◆</span>
                                        Credits ({user?.credits ?? 0})
                                    </Link>
                                    <Link
                                        to="/referral"
                                        onClick={() => setProfileOpen(false)}
                                        className="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition-colors"
                                    >
                                        <span className="text-lg">👥</span>
                                        Referral Program
                                    </Link>
                                </div>

                                {/* Sign out */}
                                <div className="border-t border-neutral-100 py-1">
                                    <button
                                        onClick={() => {
                                            setProfileOpen(false);
                                            handleLogout();
                                        }}
                                        className="flex items-center gap-3 w-full px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
                                    >
                                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Sign out
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </header>

            {/* ═══════════════════════════════════════════════
                  MOBILE HEADER
               ═══════════════════════════════════════════════ */}
            <header className="flex md:hidden sticky top-0 z-40 h-14 items-center justify-between border-b border-neutral-100 bg-white/90 backdrop-blur px-4">
                <Link to="/dashboard" className="flex items-center gap-2 shrink-0">
                    <span className="text-lg font-extrabold tracking-tight text-teal-700">Health</span>
                    <span className="text-lg font-extrabold tracking-tight text-neutral-900">Intel</span>
                </Link>

                <div className="flex items-center gap-0.5">
                    {/* Search trigger */}
                    <button
                        onClick={() => setSearchOpen(true)}
                        className="p-2 rounded-lg text-neutral-500 hover:bg-neutral-100 transition-colors"
                        aria-label="Search"
                    >
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>

                    {/* Notification bell */}
                    <button
                        onClick={() => setNotifOpen(true)}
                        className="relative p-2 rounded-lg text-neutral-500 hover:bg-neutral-100 transition-colors"
                        aria-label="Notifications"
                    >
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        {unreadCount > 0 && (
                            <span className="absolute top-0.5 right-0.5 min-w-[16px] h-[16px] rounded-full bg-red-500 border-2 border-white flex items-center justify-center text-[9px] font-bold text-white px-0.5">
                                {unreadCount > 9 ? '9+' : unreadCount}
                            </span>
                        )}
                    </button>

                    {/* Profile avatar (tap to open bottom sheet or navigate) */}
                    <div className="relative" ref={profileRef}>
                        <button
                            onClick={() => setProfileOpen(!profileOpen)}
                            className="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-sm font-bold ml-1 hover:bg-teal-200 transition-colors"
                        >
                            {userInitial}
                        </button>

                        {profileOpen && (
                            <div className="absolute right-0 top-full mt-2 w-64 bg-white rounded-xl shadow-lg border border-neutral-100 overflow-hidden z-50 animate-fade-in-up">
                                {/* User info */}
                                <div className="px-4 py-3 border-b border-neutral-100">
                                    <div className="flex items-center gap-3">
                                        <span className="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-base font-bold shrink-0">
                                            {userInitial}
                                        </span>
                                        <div className="min-w-0">
                                            <p className="text-sm font-bold text-neutral-900 truncate">{user?.name}</p>
                                            <p className="text-xs text-neutral-500 truncate">{userEmail}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 mt-3 ml-[52px]">
                                        <span className={`w-1.5 h-1.5 rounded-full ${profileComplete ? 'bg-green-500' : 'bg-amber-500'}`} />
                                        <span className="text-[10px] font-semibold text-neutral-500 uppercase tracking-wider">{profileBadge}</span>
                                    </div>
                                </div>

                                <div className="py-1">
                                    <Link to="/onboarding" onClick={() => setProfileOpen(false)} className="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition-colors">
                                        <span className="text-lg">◉</span> Health Profile
                                    </Link>
                                    <Link to="/credits" onClick={() => setProfileOpen(false)} className="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition-colors">
                                        <span className="text-lg">◆</span> Credits ({user?.credits ?? 0})
                                    </Link>
                                    <Link to="/referral" onClick={() => setProfileOpen(false)} className="flex items-center gap-3 px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition-colors">
                                        <span className="text-lg">👥</span> Referral Program
                                    </Link>
                                </div>

                                <div className="border-t border-neutral-100 py-1">
                                    <button
                                        onClick={() => { setProfileOpen(false); handleLogout(); }}
                                        className="flex items-center gap-3 w-full px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
                                    >
                                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Sign out
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </header>

            {/* ═══════════════════════════════════════════════
                  DESKTOP SIDEBAR + MAIN
               ═══════════════════════════════════════════════ */}
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

            {/* ═══════════════════════════════════════════════
                  MOBILE BOTTOM TAB BAR
               ═══════════════════════════════════════════════ */}
            <div className="tab-bar-fixed md:hidden flex justify-evenly items-center px-1">
                {navItems.map((item) => {
                    const active = isActive(item.path);
                    return (
                        <Link
                            key={item.path}
                            to={item.path}
                            className={`flex flex-col items-center justify-center gap-0.5 py-1.5 min-w-0 flex-1 transition-all ${
                                active ? 'text-teal-700' : 'text-neutral-400'
                            }`}
                        >
                            <span className={`text-lg leading-none ${active ? 'bg-teal-50 w-7 h-7 flex items-center justify-center rounded-lg' : ''}`}>
                                {item.icon}
                            </span>
                            <span className="text-[9px] font-bold leading-tight truncate max-w-full px-0.5">{item.label}</span>
                            {active && <span className="w-1 h-1 rounded-full bg-teal-500" />}
                        </Link>
                    );
                })}
            </div>

            {/* ═══════════════════════════════════════════════
                  OVERLAYS / MODALS
               ═══════════════════════════════════════════════ */}
            <SearchModal open={searchOpen} onClose={() => setSearchOpen(false)} />
            <NotificationsDrawer open={notifOpen} onClose={() => setNotifOpen(false)} />
        </div>
    );
}