import './bootstrap';
import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import useAuthStore from './stores/authStore';
import usePartnerAuthStore from './stores/partnerAuthStore';
import { initPWA, subscribeToPush, isInstalled, isOnline } from './lib/pwa';
import AppLayout from './layouts/AppLayout';
import { useNavigate } from 'react-router-dom';

// PWA Redirect: No frontpages in the PWA — send users directly to auth or dashboard
function PwaEntry() {
    const { user, loading } = useAuthStore();
    const navigate = useNavigate();

    useEffect(() => {
        if (loading) return;
        if (user) {
            navigate(user.roles?.includes('admin') ? '/admin' : '/dashboard', { replace: true });
        } else {
            navigate('/login', { replace: true });
        }
    }, [user, loading, navigate]);

    return (
        <div className="flex min-h-screen items-center justify-center">
            <div className="h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" />
        </div>
    );
}
import Login from './screens/auth/Login';
import Register from './screens/auth/Register';
import ForgotPassword from './screens/auth/ForgotPassword';
import ResetPassword from './screens/auth/ResetPassword';
import VerifyPhone from './screens/auth/VerifyPhone';
import VerifyEmail from './screens/auth/VerifyEmail';
import Dashboard from './screens/Dashboard';
import HealthProfileOnboarding from './screens/onboarding/HealthProfile';
import PanelPicker from './screens/lab/PanelPicker';
import ValueEntry from './screens/lab/ValueEntry';
import ResultScreen from './screens/lab/ResultScreen';
import TrendChart from './screens/lab/TrendChart';
import BuyCredits from './screens/credits/BuyCredits';
import PaymentCallback from './screens/credits/PaymentCallback';
import CreditHistory from './screens/credits/CreditHistory';
import SymptomChecker from './screens/symptoms/SymptomChecker';
import ProviderDirectory from './screens/directory/ProviderDirectory';
import ProviderDetail from './screens/directory/ProviderDetail';
import InsuranceComparison from './screens/insurance/InsuranceComparison';
import HealthTools from './screens/tools/HealthTools';
import BMICalculator from './screens/tools/BMICalculator';
import BMRCalculator from './screens/tools/BMRCalculator';
import DueDateCalculator from './screens/tools/DueDateCalculator';
import WaistHipCalculator from './screens/tools/WaistHipCalculator';
import BloodPressureLog from './screens/tools/BloodPressureLog';
import WaterIntakeTracker from './screens/tools/WaterIntakeTracker';
import FoodSymptomDiary from './screens/tools/FoodSymptomDiary';
import PeriodTracker from './screens/tools/PeriodTracker';
import ImmunizationTracker from './screens/tools/ImmunizationTracker';
import AppointmentTracker from './screens/tools/AppointmentTracker';
import Offline from './screens/Offline';
import AdminLayout from './screens/admin/AdminLayout';
import AdminDashboard from './screens/admin/AdminDashboard';
import AdminPanels from './screens/admin/AdminPanels';
import AdminSymptomMappings from './screens/admin/AdminSymptomMappings';
import AdminProviders from './screens/admin/AdminProviders';
import AdminCreditPackages from './screens/admin/AdminCreditPackages';
import AdminUsers from './screens/admin/AdminUsers';
import AdminUserDetail from './screens/admin/AdminUserDetail';
import AdminAnalytics from './screens/admin/AdminAnalytics';
import AdminAppointments from './screens/admin/AdminAppointments';
import AdminFeedback from './screens/admin/AdminFeedback';
import AdminSubmissions from './screens/admin/AdminSubmissions';
import AdminNotifications from './screens/admin/AdminNotifications';
import AdminAuditLog from './screens/admin/AdminAuditLog';
import AdminSettings from './screens/admin/AdminSettings';
import AdminBlogPosts from './screens/admin/AdminBlogPosts';
import AdminBlogEditor from './screens/admin/AdminBlogEditor';
import AdminBlogCategories from './screens/admin/AdminBlogCategories';
import AdminPartnerships from './screens/admin/AdminPartnerships';
import AdminPartnershipDetail from './screens/admin/AdminPartnershipDetail';
import AdminPartnershipInquiries from './screens/admin/AdminPartnershipInquiries';
import AdminReferenceRanges from './screens/admin/AdminReferenceRanges';
import AdminClinicalPanels from './screens/admin/AdminClinicalPanels';
import AdminMedicationEffects from './screens/admin/AdminMedicationEffects';
import AdminEmails from './screens/admin/AdminEmails';
import AdminDocumentation from './screens/admin/AdminDocumentation';
import PartnerLogin from './screens/partner/PartnerLogin';
import PartnerLayout from './screens/partner/PartnerLayout';
import PartnerDashboard from './screens/partner/PartnerDashboard';
import PartnerInterpretations from './screens/partner/PartnerInterpretations';

// Placeholder screens for partner portal (fallback for routes not yet built)
function PartnerPlaceholder({ title }) {
  return (
    <div className="bg-white rounded-xl border border-gray-200 p-8 text-center">
      <h3 className="text-lg font-semibold text-gray-700 mb-2">{title}</h3>
      <p className="text-sm text-gray-400">This section is coming soon.</p>
    </div>
  );
}

function AdminPlaceholder({ title }) {
  return (
    <div className="space-y-4">
      <h2 className="text-xl font-bold text-gray-900">{title}</h2>
      <div className="bg-white rounded-xl border border-gray-200 p-8 text-center">
        <p className="text-sm text-gray-500">This admin section is under development. The API endpoints are ready at:</p>
        <code className="mt-2 block text-xs text-teal-600 bg-teal-50 p-2 rounded">
          /api/admin/clinical/{title === 'Reference Ranges' ? 'ranges' : title === 'Clinical Panels' ? 'panels' : 'medication-effects'}
        </code>
      </div>
    </div>
  );
}

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 1000 * 60 * 5,
            retry: 1,
        },
    },
});

function ProtectedRoute({ children }) {
    const { user, loading } = useAuthStore();

    if (loading) {
        return (
            <div className="flex min-h-screen items-center justify-center">
                <div className="h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" />
            </div>
        );
    }

    if (!user) {
        return <Navigate to="/login" replace />;
    }

    return children;
}

function GuestRoute({ children }) {
    const { user, loading } = useAuthStore();

    if (loading) {
        return null;
    }

    if (user) {
        if (user.roles?.includes('admin')) {
            return <Navigate to="/admin" replace />;
        }
        return <Navigate to="/dashboard" replace />;
    }

    return children;
}

function PartnerProtectedRoute({ children }) {
    const { provider, token } = usePartnerAuthStore();

    if (!provider || !token) {
        return <Navigate to="/partner/login" replace />;
    }

    return children;
}

function AdminRoute({ children }) {
    const { user, loading } = useAuthStore();

    if (loading) {
        return (
            <div className="flex min-h-screen items-center justify-center">
                <div className="h-8 w-8 animate-spin rounded-full border-4 border-teal-500 border-t-transparent" />
            </div>
        );
    }

    if (!user) return <Navigate to="/login" replace />;
    if (!user.roles?.includes('admin')) {
        return <Navigate to="/dashboard" replace />;
    }

    return children;
}

function PWALifecycle() {
    const { user } = useAuthStore();
    const [updateAvailable, setUpdateAvailable] = useState(false);

    useEffect(() => {
        // Initialize PWA: register SW, listen for install prompt
        initPWA();

        // Listen for PWA update available
        const handleUpdate = () => setUpdateAvailable(true);
        window.addEventListener('pwa:update-available', handleUpdate);
        return () => window.removeEventListener('pwa:update-available', handleUpdate);
    }, []);

    // Subscribe to push notifications when user logs in
    useEffect(() => {
        if (user && 'PushManager' in window) {
            subscribeToPush();
        }
    }, [user]);

    if (!updateAvailable) return null;

    return (
        <div className="fixed bottom-4 right-4 z-50 bg-teal-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3">
            <span className="text-sm">New version available!</span>
            <button
                onClick={() => window.location.reload()}
                className="bg-white text-teal-700 px-3 py-1 rounded text-sm font-medium hover:bg-teal-50"
            >
                Refresh
            </button>
        </div>
    );
}

function App() {
    const fetchUser = useAuthStore((s) => s.fetchUser);

    useEffect(() => {
        fetchUser();
    }, [fetchUser]);

    useEffect(() => {
        const handler = () => useAuthStore.getState().logout();
        window.addEventListener('auth:unauthenticated', handler);
        return () => window.removeEventListener('auth:unauthenticated', handler);
    }, []);

    return (
        <QueryClientProvider client={queryClient}>
            <BrowserRouter>
                <PWALifecycle />
                <Routes>
                    <Route path="/" element={<PwaEntry />} />
                    <Route path="/offline" element={<Offline />} />
                    <Route path="/login" element={<GuestRoute><Login /></GuestRoute>} />
                    <Route path="/register" element={<GuestRoute><Register /></GuestRoute>} />
                    <Route path="/forgot-password" element={<GuestRoute><ForgotPassword /></GuestRoute>} />
                    <Route path="/reset-password" element={<GuestRoute><ResetPassword /></GuestRoute>} />
                    <Route path="/verify-phone" element={<GuestRoute><VerifyPhone /></GuestRoute>} />
                    <Route path="/verify-email" element={<VerifyEmail />} />
                    <Route path="/onboarding" element={<ProtectedRoute><HealthProfileOnboarding /></ProtectedRoute>} />
                    <Route path="/dashboard" element={<ProtectedRoute><AppLayout><Dashboard /></AppLayout></ProtectedRoute>} />
                    <Route path="/lab-results" element={<ProtectedRoute><AppLayout><PanelPicker /></AppLayout></ProtectedRoute>} />
                    <Route path="/lab-results/:slug" element={<ProtectedRoute><AppLayout><ValueEntry /></AppLayout></ProtectedRoute>} />
                    <Route path="/lab-results/submission/:id" element={<ProtectedRoute><AppLayout><ResultScreen /></AppLayout></ProtectedRoute>} />
                    <Route path="/trends/:testSlug" element={<ProtectedRoute><AppLayout><TrendChart /></AppLayout></ProtectedRoute>} />
                    <Route path="/credits" element={<ProtectedRoute><AppLayout><CreditHistory /></AppLayout></ProtectedRoute>} />
                    <Route path="/credits/buy" element={<ProtectedRoute><AppLayout><BuyCredits /></AppLayout></ProtectedRoute>} />
                    <Route path="/payment/callback" element={<PaymentCallback />} />
                    <Route path="/symptom-checker" element={<ProtectedRoute><AppLayout><SymptomChecker /></AppLayout></ProtectedRoute>} />
                    <Route path="/directory" element={<ProtectedRoute><AppLayout><ProviderDirectory /></AppLayout></ProtectedRoute>} />
                    <Route path="/providers/:slug" element={<ProtectedRoute><AppLayout><ProviderDetail /></AppLayout></ProtectedRoute>} />
                    <Route path="/insurance" element={<ProtectedRoute><AppLayout><InsuranceComparison /></AppLayout></ProtectedRoute>} />
<Route path="/health-tools" element={<ProtectedRoute><AppLayout><HealthTools /></AppLayout></ProtectedRoute>} />
                    <Route path="/health-tools/bmi" element={<ProtectedRoute><AppLayout><BMICalculator /></AppLayout></ProtectedRoute>} />
                    <Route path="/health-tools/bmr" element={<ProtectedRoute><AppLayout><BMRCalculator /></AppLayout></ProtectedRoute>} />
                    <Route path="/health-tools/due-date" element={<ProtectedRoute><AppLayout><DueDateCalculator /></AppLayout></ProtectedRoute>} />
                    <Route path="/health-tools/waist-hip" element={<ProtectedRoute><AppLayout><WaistHipCalculator /></AppLayout></ProtectedRoute>} />
                    <Route path="/health-tools/blood-pressure" element={<ProtectedRoute><AppLayout><BloodPressureLog /></AppLayout></ProtectedRoute>} />
                    <Route path="/health-tools/water" element={<ProtectedRoute><AppLayout><WaterIntakeTracker /></AppLayout></ProtectedRoute>} />
                    <Route path="/health-tools/food-diary" element={<ProtectedRoute><AppLayout><FoodSymptomDiary /></AppLayout></ProtectedRoute>} />
                    <Route path="/health-tools/period" element={<ProtectedRoute><AppLayout><PeriodTracker /></AppLayout></ProtectedRoute>} />
                    <Route path="/health-tools/immunization" element={<ProtectedRoute><AppLayout><ImmunizationTracker /></AppLayout></ProtectedRoute>} />
                    <Route path="/health-tools/appointments" element={<ProtectedRoute><AppLayout><AppointmentTracker /></AppLayout></ProtectedRoute>} />

                    {/* Admin Routes */}
                    <Route path="/admin" element={<AdminRoute><AdminLayout /></AdminRoute>}>
                        <Route index element={<AdminDashboard />} />
                        <Route path="analytics" element={<AdminAnalytics />} />
                        <Route path="users" element={<AdminUsers />} />
                        <Route path="users/:id" element={<AdminUserDetail />} />
                        <Route path="appointments" element={<AdminAppointments />} />
                        <Route path="feedback" element={<AdminFeedback />} />
                        <Route path="submissions" element={<AdminSubmissions />} />
                        <Route path="panels" element={<AdminPanels />} />
                        <Route path="symptom-mappings" element={<AdminSymptomMappings />} />
                        <Route path="providers" element={<AdminProviders />} />
                        <Route path="credit-packages" element={<AdminCreditPackages />} />
                        <Route path="notifications" element={<AdminNotifications />} />
                        <Route path="audit-log" element={<AdminAuditLog />} />
                        <Route path="settings" element={<AdminSettings />} />
                        <Route path="blog/posts" element={<AdminBlogPosts />} />
                        <Route path="blog/posts/new" element={<AdminBlogEditor />} />
                        <Route path="blog/posts/:id/edit" element={<AdminBlogEditor />} />
                        <Route path="blog/categories" element={<AdminBlogCategories />} />
                        <Route path="partnerships" element={<AdminPartnerships />} />
                        <Route path="partnerships/new" element={<AdminPartnershipDetail />} />
                        <Route path="partnerships/:id" element={<AdminPartnershipDetail />} />
                        <Route path="clinical/ranges" element={<AdminReferenceRanges />} />
                        <Route path="clinical/panels" element={<AdminClinicalPanels />} />
                        <Route path="clinical/medication-effects" element={<AdminMedicationEffects />} />
                        <Route path="emails" element={<AdminEmails />} />
                        <Route path="partnership-inquiries" element={<AdminPartnershipInquiries />} />
                        <Route path="documentation" element={<AdminDocumentation />} />
                    </Route>

                    {/* Partner Portal Routes */}
                    <Route path="/partner/login" element={<PartnerLogin />} />
                    <Route path="/partner" element={<PartnerProtectedRoute><PartnerLayout /></PartnerProtectedRoute>}>
                        <Route index element={<Navigate to="/partner/dashboard" replace />} />
                        <Route path="dashboard" element={<PartnerDashboard />} />
                        <Route path="interpretations" element={<PartnerInterpretations />} />
                        <Route path="patients" element={<PartnerPlaceholder title="Patients" />} />
                        <Route path="invoices" element={<PartnerPlaceholder title="Invoices" />} />
                        <Route path="submit" element={<PartnerPlaceholder title="Submit Results" />} />
                        <Route path="api-docs" element={<PartnerPlaceholder title="API Documentation" />} />
                        <Route path="settings" element={<PartnerPlaceholder title="Settings" />} />
                    </Route>

                    <Route path="*" element={<Navigate to="/" replace />} />
                </Routes>
            </BrowserRouter>
        </QueryClientProvider>
    );
}

const root = createRoot(document.getElementById('root'));
root.render(
    <React.StrictMode>
        <App />
    </React.StrictMode>
);
