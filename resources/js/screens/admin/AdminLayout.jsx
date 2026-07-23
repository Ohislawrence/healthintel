import React, { useState } from 'react';
import { Link, useLocation, Outlet } from 'react-router-dom';

export default function AdminLayout() {
    const location = useLocation();
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const links = [
        { to: '/admin', label: 'Dashboard', icon: '📊' },
        { to: '/admin/analytics', label: 'Analytics', icon: '📈' },
        { to: '/admin/users', label: 'Users', icon: '👥' },
        { to: '/admin/appointments', label: 'Appointments', icon: '📅' },
        { to: '/admin/feedback', label: 'Feedback', icon: '💬' },
        { to: '/admin/submissions', label: 'Submissions', icon: '🧪' },
        { to: '/admin/panels', label: 'Test Panels', icon: '📋' },
        { to: '/admin/symptom-mappings', label: 'Symptom Links', icon: '🔗' },
        { to: '/admin/providers', label: 'Providers', icon: '🏥' },
        { to: '/admin/partners', label: 'Partners', icon: '🤝' },
        { to: '/admin/credit-packages', label: 'Prices', icon: '₦' },
        { to: '/admin/notifications', label: 'Notifications', icon: '🔔' },
    ];

    const isActive = (path) => location.pathname === path;

    return (
        <div className="flex min-h-screen bg-gray-50">
            {/* Sidebar */}
            <aside className={`fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform lg:relative lg:translate-x-0 ${sidebarOpen ? 'translate-x-0' : '-translate-x-full'}`}>
                <div className="flex items-center gap-3 px-6 py-5 border-b border-gray-100">
                    <span className="text-2xl">⚕️</span>
                    <div>
                        <h1 className="font-bold text-gray-900">HealthIntel Admin</h1>
                        <p className="text-xs text-gray-500">Management Panel</p>
                    </div>
                </div>
                <nav className="p-4 space-y-1">
                    {links.map((link) => (
                        <Link
                            key={link.to}
                            to={link.to}
                            onClick={() => setSidebarOpen(false)}
                            className={`flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors ${isActive(link.to) ? 'bg-teal-50 text-teal-700' : 'text-gray-600 hover:bg-gray-50'}`}
                        >
                            <span>{link.icon}</span>
                            {link.label}
                        </Link>
                    ))}
                </nav>
                <div className="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-100">
                    <Link to="/" className="block text-sm text-gray-500 hover:text-teal-600">
                        ← Back to app
                    </Link>
                </div>
            </aside>

            {/* Mobile overlay */}
            {sidebarOpen && (
                <div className="fixed inset-0 z-40 bg-black/30 lg:hidden" onClick={() => setSidebarOpen(false)} />
            )}

            {/* Main content */}
            <div className="flex-1 min-w-0">
                <header className="sticky top-0 z-30 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between lg:justify-end">
                    <button className="lg:hidden text-gray-600" onClick={() => setSidebarOpen(true)}>
                        ☰ Menu
                    </button>
                    <span className="text-sm text-gray-500">Admin Panel</span>
                </header>
                <main className="p-6">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
