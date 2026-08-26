import React, { useState } from 'react';
import { Link, useLocation, Outlet } from 'react-router-dom';

const NAV_SECTIONS = [
  {
    title: 'Overview',
    links: [
      { to: '/admin', label: 'Dashboard', icon: '📊', exact: true },
      { to: '/admin/analytics', label: 'Analytics', icon: '📈' },
      { to: '/admin/ai-analyzer', label: 'AI Analyzer', icon: '🤖' },
    ],
  },
  {
    title: 'Users & Engagement',
    links: [
      { to: '/admin/users', label: 'Users', icon: '👥' },
      { to: '/admin/appointments', label: 'Appointments', icon: '📅' },
      { to: '/admin/feedback', label: 'Feedback', icon: '💬' },
      { to: '/admin/notifications', label: 'Notifications', icon: '🔔' },
    ],
  },
  {
    title: 'Lab Results & Clinical',
    links: [
      { to: '/admin/submissions', label: 'Submissions', icon: '🧪' },
      { to: '/admin/panels', label: 'Test Panels', icon: '📋' },
      { to: '/admin/symptom-mappings', label: 'Symptom Links', icon: '🔗' },
      { to: '/admin/clinical/ranges', label: 'Reference Ranges', icon: '�' },
      { to: '/admin/clinical/panels', label: 'Clinical Panels', icon: '📊' },
      { to: '/admin/clinical/medication-effects', label: 'Medication Effects', icon: '�' },
      { to: '/admin/benchmarks', label: 'Benchmarks', icon: '�' },
    ],
  },
  {
    title: 'Partners & Directory',
    links: [
      { to: '/admin/providers', label: 'Providers', icon: '🏥' },
      { to: '/admin/partnerships', label: 'Partnerships', icon: '�' },
      { to: '/admin/partnership-inquiries', label: 'Partner Inquiries', icon: '📨' },
      { to: '/admin/listing-requests', label: 'Listing Requests', icon: '🏪' },
      { to: '/admin/testimonials', label: 'Testimonials', icon: '⭐' },
    ],
  },
  {
    title: 'Revenue & Marketing',
    links: [
      { to: '/admin/credit-packages', label: 'Credit Packages', icon: '₦' },
      { to: '/admin/payments', label: 'Payments', icon: '💳' },
      { to: '/admin/referrals', label: 'Affiliates / Referrals', icon: '🤝' },
      { to: '/admin/blog/posts', label: 'Blog Posts', icon: '📝' },
      { to: '/admin/blog/categories', label: 'Blog Categories', icon: '📂' },
      { to: '/admin/emails', label: 'Email Campaigns', icon: '📧' },
    ],
  },
  {
    title: 'System',
    links: [
      { to: '/admin/error-reports', label: 'Error Logs', icon: '🐞' },
      { to: '/admin/documentation', label: 'Documentation', icon: '📖' },
      { to: '/admin/settings', label: 'Settings', icon: '⚙' },
    ],
  },
];

export default function AdminLayout() {
  const location = useLocation();
  const [sidebarOpen, setSidebarOpen] = useState(false);

  const isActive = (path, exact = false) =>
    exact ? location.pathname === path : location.pathname.startsWith(path);

  return (
    <div className="flex min-h-screen bg-gray-50">
      {/* Sidebar */}
      <aside
        className={`fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-gray-200 flex flex-col transform transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 ${
          sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="flex items-center gap-3 px-6 py-5 border-b border-gray-100 flex-shrink-0">
          <span className="text-2xl">⚕️</span>
          <div>
            <h1 className="font-bold text-gray-900">HealthIntel Admin</h1>
            <p className="text-xs text-gray-500">Management Panel</p>
          </div>
        </div>

        <nav className="flex-1 overflow-y-auto px-3 py-4 space-y-5">
          {NAV_SECTIONS.map((section) => (
            <div key={section.title}>
              <p className="px-3 mb-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400">
                {section.title}
              </p>
              <div className="space-y-0.5">
                {section.links.map((link) => (
                  <Link
                    key={link.to}
                    to={link.to}
                    onClick={() => setSidebarOpen(false)}
                    className={`flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                      isActive(link.to, link.exact)
                        ? 'bg-teal-50 text-teal-700'
                        : 'text-gray-600 hover:bg-gray-50'
                    }`}
                  >
                    <span className="w-4 text-center">{link.icon}</span>
                    {link.label}
                  </Link>
                ))}
              </div>
            </div>
          ))}
        </nav>

        <div className="p-4 border-t border-gray-100 flex-shrink-0">
          <Link to="/" className="block text-sm text-gray-500 hover:text-teal-600">
            ← Back to app
          </Link>
        </div>
      </aside>

      {/* Mobile overlay */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/30 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
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