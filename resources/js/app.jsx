import './bootstrap';
import React, { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import useAuthStore from './stores/authStore';
import AppLayout from './layouts/AppLayout';
import Home from './screens/Home';
import Login from './screens/auth/Login';
import Register from './screens/auth/Register';
import VerifyPhone from './screens/auth/VerifyPhone';
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
import SymptomResult from './screens/symptoms/SymptomResult';
import InsuranceComparison from './screens/insurance/InsuranceComparison';
import AdminLayout from './screens/admin/AdminLayout';
import AdminDashboard from './screens/admin/AdminDashboard';
import AdminPanels from './screens/admin/AdminPanels';
import AdminSymptomMappings from './screens/admin/AdminSymptomMappings';
import AdminProviders from './screens/admin/AdminProviders';
import AdminCreditPackages from './screens/admin/AdminCreditPackages';
import AdminUsers from './screens/admin/AdminUsers';
import AdminAnalytics from './screens/admin/AdminAnalytics';
import AdminAppointments from './screens/admin/AdminAppointments';
import AdminFeedback from './screens/admin/AdminFeedback';
import AdminSubmissions from './screens/admin/AdminSubmissions';
import AdminPartners from './screens/admin/AdminPartners';
import AdminNotifications from './screens/admin/AdminNotifications';
import AdminAuditLog from './screens/admin/AdminAuditLog';

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
                <Routes>
                    <Route path="/" element={<Home />} />
                    <Route path="/login" element={<GuestRoute><Login /></GuestRoute>} />
                    <Route path="/register" element={<GuestRoute><Register /></GuestRoute>} />
                    <Route path="/verify-phone" element={<GuestRoute><VerifyPhone /></GuestRoute>} />
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
                    <Route path="/symptom-checker/results" element={<ProtectedRoute><SymptomResult /></ProtectedRoute>} />
                    <Route path="/directory" element={<ProtectedRoute><AppLayout><ProviderDirectory /></AppLayout></ProtectedRoute>} />
                    <Route path="/providers/:slug" element={<ProtectedRoute><AppLayout><ProviderDetail /></AppLayout></ProtectedRoute>} />
                    <Route path="/insurance" element={<ProtectedRoute><AppLayout><InsuranceComparison /></AppLayout></ProtectedRoute>} />

                    {/* Admin Routes */}
                    <Route path="/admin" element={<AdminRoute><AdminLayout /></AdminRoute>}>
                        <Route index element={<AdminDashboard />} />
                        <Route path="analytics" element={<AdminAnalytics />} />
                        <Route path="users" element={<AdminUsers />} />
                        <Route path="appointments" element={<AdminAppointments />} />
                        <Route path="feedback" element={<AdminFeedback />} />
                        <Route path="submissions" element={<AdminSubmissions />} />
                        <Route path="panels" element={<AdminPanels />} />
                        <Route path="symptom-mappings" element={<AdminSymptomMappings />} />
                        <Route path="providers" element={<AdminProviders />} />
                        <Route path="partners" element={<AdminPartners />} />
                        <Route path="credit-packages" element={<AdminCreditPackages />} />
                        <Route path="notifications" element={<AdminNotifications />} />
                        <Route path="audit-log" element={<AdminAuditLog />} />
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
