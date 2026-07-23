import React from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import useAuthStore from '../stores/authStore';

export default function AppLayout({ children }) {
    const { user, logout } = useAuthStore();
    const location = useLocation();
    const navigate = useNavigate();

    const handleLogout = async () => {
        await logout();
        navigate('/');
    };

    const navItems = [
        { path: '/dashboard', label: 'Dashboard', icon: '▦' },
        { path: '/lab-results', label: 'Lab Results', icon: '⊕' },
        { path: '/symptom-checker', label: 'Symptoms', icon: '⊘' },
        { path: '/directory', label: 'Directory', icon: '◈' },
    ];

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Top nav */}
            <header className="sticky top-0 z-30 border-b border-gray-200 bg-white">
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                    <Link to="/" className="text-xl font-bold text-teal-600">
                        Lab<span className="text-gray-800">Doc</span>
                    </Link>

                    <nav className="hidden md:flex items-center gap-6">
                        {navItems.map((item) => (
                            <Link
                                key={item.path}
                                to={item.path}
                                className={`text-sm font-medium transition-colors ${
                                    location.pathname === item.path
                                        ? 'text-teal-600'
                                        : 'text-gray-600 hover:text-teal-600'
                                }`}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="flex items-center gap-4">
                        <span className="text-sm text-gray-500">
                            {(user?.credits ?? 0)} credits
                        </span>
                        <button
                            onClick={handleLogout}
                            className="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Sign out
                        </button>
                    </div>
                </div>

                {/* Mobile bottom nav */}
                <nav className="flex justify-around border-t border-gray-100 md:hidden">
                    {navItems.map((item) => (
                        <Link
                            key={item.path}
                            to={item.path}
                            className={`flex flex-col items-center py-2 px-3 text-xs ${
                                location.pathname === item.path
                                    ? 'text-teal-600'
                                    : 'text-gray-500'
                            }`}
                        >
                            <span className="text-lg">{item.icon}</span>
                            {item.label}
                        </Link>
                    ))}
                </nav>
            </header>

            {/* Main content */}
            <main className="mx-auto max-w-6xl px-4 py-6">
                <div>{children}</div>
            </main>
        </div>
    );
}
