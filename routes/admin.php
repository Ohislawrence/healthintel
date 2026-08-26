<?php

use App\Http\Controllers\Api\Admin\AdminBlogController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\AdminPartnershipController;
use App\Http\Controllers\Api\Admin\AdminSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);

    // Analytics
    Route::get('/analytics', [AdminController::class, 'analytics']);

    // AI Analyzer (DeepSeek-powered growth/marketing insights)
    Route::get('/ai-analyzer', [AdminController::class, 'aiAnalyzer']);
    Route::get('/ai-analyzer/latest', [AdminController::class, 'aiAnalyzerLatest']);
    Route::get('/ai-analyzer/history', [AdminController::class, 'aiAnalyzerHistory']);

    // Test Panels
    Route::get('/panels', [AdminController::class, 'panels']);
    Route::get('/panels/{slug}', [AdminController::class, 'panelShow']);
    Route::put('/panels/{slug}', [AdminController::class, 'panelUpdate']);

    // Symptom Mappings
    Route::get('/symptom-mappings', [AdminController::class, 'symptomMappings']);
    Route::post('/symptom-mappings', [AdminController::class, 'symptomMappingStore']);
    Route::delete('/symptom-mappings/{id}', [AdminController::class, 'symptomMappingDelete']);

        // Providers
    Route::get('/providers', [AdminController::class, 'providers']);
    Route::post('/providers', [AdminController::class, 'providerStore']);
    Route::put('/providers/{slug}', [AdminController::class, 'providerUpdate']);
    Route::post('/providers/{slug}/toggle-active', [AdminController::class, 'providerToggleActive']);
    Route::post('/providers/upload-asset', [AdminController::class, 'providerAssetUpload']);

    // Credit Packages
    Route::get('/credit-packages', [AdminController::class, 'creditPackages']);
    Route::post('/credit-packages', [AdminController::class, 'creditPackageStore']);
    Route::put('/credit-packages/{id}', [AdminController::class, 'creditPackageUpdate']);

    // Users
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/users/trashed', [AdminController::class, 'trashedUsers']);
    Route::get('/users/{id}', [AdminController::class, 'userShow']);
    Route::post('/users/{id}/credit', [AdminController::class, 'grantCredits']);
    Route::post('/users/{id}/verify-email', [AdminController::class, 'verifyUserEmail']);
    Route::post('/users/{id}/resend-verification-code', [AdminController::class, 'resendUserVerificationCode']);
    Route::delete('/users/{id}', [AdminController::class, 'softDeleteUser']);
    Route::post('/users/{id}/restore', [AdminController::class, 'restoreUser']);
    Route::delete('/users/{id}/force', [AdminController::class, 'forceDeleteUser']);

    // Appointments
    Route::get('/appointments', [AdminController::class, 'appointments']);
    Route::put('/appointments/{id}', [AdminController::class, 'appointmentUpdate']);
    Route::post('/appointments/{id}/decision', [AdminController::class, 'appointmentDecision']);

    // User Feedback
    Route::get('/feedback', [AdminController::class, 'feedback']);
    Route::put('/feedback/{id}', [AdminController::class, 'feedbackUpdate']);

    // Payments & Reconciliation
    Route::get('/payments', [AdminController::class, 'payments']);
    Route::post('/payments/{id}/reconcile', [AdminController::class, 'paymentReconcile']);

    // Partner Portal
    Route::post('/providers/{slug}/generate-access-code', [AdminController::class, 'generateProviderAccessCode']);

    // PDF Submissions
    Route::get('/pdf-submissions', [AdminController::class, 'pdfSubmissions']);

    // All Submissions
    Route::get('/submissions', [AdminController::class, 'submissions']);

    // Notifications
    Route::get('/notifications', [AdminController::class, 'notifications']);
    Route::post('/notifications', [AdminController::class, 'notificationStore']);

    // Audit Log
    Route::get('/audit-log', [AdminController::class, 'auditLog']);

    // Email Campaigns
    Route::get('/email/tokens', [AdminController::class, 'emailTokens']);
    Route::post('/email/preview', [AdminController::class, 'emailPreview']);
    Route::post('/email/send', [AdminController::class, 'emailSend']);
    Route::post('/email/send-test', [AdminController::class, 'emailSendTest']);

    // Uploads
    Route::post('/upload', [AdminBlogController::class, 'uploadImage']);

    // Lab Partnerships
    Route::get('/partnerships', [AdminPartnershipController::class, 'index']);
    Route::get('/partnerships/{id}', [AdminPartnershipController::class, 'show']);
    Route::post('/partnerships', [AdminPartnershipController::class, 'store']);
    Route::put('/partnerships/{id}', [AdminPartnershipController::class, 'update']);
    Route::delete('/partnerships/{id}', [AdminPartnershipController::class, 'destroy']);
    Route::get('/partnerships/{id}/stats', [AdminPartnershipController::class, 'stats']);
    Route::get('/partnerships/{id}/invoices', [AdminPartnershipController::class, 'partnershipInvoices']);
    Route::post('/partnerships/{id}/invoices', [AdminPartnershipController::class, 'generateInvoice']);
    Route::get('/partnerships/{id}/proposal', [AdminPartnershipController::class, 'proposalPdf']);
    Route::get('/invoices', [AdminPartnershipController::class, 'invoices']);
    Route::post('/invoices/generate-all', [AdminPartnershipController::class, 'generateAllInvoices']);
    Route::get('/partner-health', [AdminPartnershipController::class, 'healthScores']);

    // Blog Posts
    Route::get('/blog/posts', [AdminBlogController::class, 'posts']);
    Route::get('/blog/posts/{id}', [AdminBlogController::class, 'postShow']);
    Route::post('/blog/posts', [AdminBlogController::class, 'postStore']);
    Route::put('/blog/posts/{id}', [AdminBlogController::class, 'postUpdate']);
    Route::delete('/blog/posts/{id}', [AdminBlogController::class, 'postDelete']);

    // Blog Categories
    Route::get('/blog/categories', [AdminBlogController::class, 'categories']);
    Route::post('/blog/categories', [AdminBlogController::class, 'categoryStore']);
    Route::put('/blog/categories/{id}', [AdminBlogController::class, 'categoryUpdate']);
    Route::delete('/blog/categories/{id}', [AdminBlogController::class, 'categoryDelete']);

    // ── Testimonials ──
    Route::get('/testimonials', [AdminController::class, 'testimonials']);
    Route::post('/testimonials', [AdminController::class, 'testimonialStore']);
    Route::put('/testimonials/{id}', [AdminController::class, 'testimonialUpdate']);
    Route::delete('/testimonials/{id}', [AdminController::class, 'testimonialDestroy']);

    // ── Clinical Benchmarks ──
    Route::get('/benchmarks', [AdminController::class, 'benchmarks']);

    // ── Clinical Data Management (Reference Ranges, Panels, Med Effects) ──
    Route::get('/clinical/ranges', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'ranges']);
    Route::post('/clinical/ranges', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'rangeStore']);
    Route::put('/clinical/ranges/{id}', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'rangeUpdate']);
    Route::delete('/clinical/ranges/{id}', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'rangeDestroy']);
    Route::get('/clinical/ranges/categories', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'rangeCategories']);

    Route::get('/clinical/panels', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'panels']);
    Route::post('/clinical/panels', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'panelStore']);
    Route::put('/clinical/panels/{id}', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'panelUpdate']);
    Route::delete('/clinical/panels/{id}', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'panelDestroy']);

    Route::get('/clinical/medication-effects', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'medicationEffects']);
    Route::post('/clinical/medication-effects', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'medicationEffectStore']);
    Route::put('/clinical/medication-effects/{id}', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'medicationEffectUpdate']);
    Route::delete('/clinical/medication-effects/{id}', [\App\Http\Controllers\Api\Admin\AdminClinicalDataController::class, 'medicationEffectDestroy']);

    // Partnership Inquiries
    Route::get('/partnership-inquiries', [AdminController::class, 'partnershipInquiries']);
    Route::put('/partnership-inquiries/{id}', [AdminController::class, 'partnershipInquiryUpdate']);

    // Provider Listing & Ad Requests
    Route::get('/provider-listing-requests', [AdminController::class, 'providerListingRequests']);
    Route::put('/provider-listing-requests/{id}', [AdminController::class, 'providerListingRequestUpdate']);

    // Referral Program Management
    Route::get('/referral/settings', [AdminController::class, 'referralSettings']);
    Route::put('/referral/settings', [AdminController::class, 'referralSettingsUpdate']);
    Route::get('/referral/earnings', [AdminController::class, 'referralEarnings']);
    Route::get('/referral/payout-requests', [AdminController::class, 'referralPayouts']);
    Route::post('/referral/payout-requests/{id}/approve', [AdminController::class, 'referralPayoutApprove']);
    Route::post('/referral/payout-requests/{id}/reject', [AdminController::class, 'referralPayoutReject']);
    Route::get('/referral/stats', [AdminController::class, 'referralStats']);
    Route::get('/referral/referrers', [AdminController::class, 'referralReferrers']);

    // Error Reports
    Route::get('/error-reports', [AdminController::class, 'errorReports']);
    Route::put('/error-reports/{id}', [AdminController::class, 'errorReportUpdate']);

    // Settings
    Route::get('/settings', [AdminSettingController::class, 'index']);
    Route::put('/settings/{setting}', [AdminSettingController::class, 'update']);
    Route::post('/settings/bulk', [AdminSettingController::class, 'bulkUpdate']);
    Route::get('/settings/payment-gateway', [AdminSettingController::class, 'paymentGatewayInfo']);
    Route::post('/settings/payment-gateway', [AdminSettingController::class, 'setPaymentGateway']);
    Route::get('/settings/env-keys', [AdminSettingController::class, 'envKeys']);
    Route::post('/settings/env-keys', [AdminSettingController::class, 'updateEnvKey']);
    Route::get('/settings/config-diagnostic', [AdminSettingController::class, 'configDiagnostic']);
    Route::post('/settings/rebuild-config-cache', [AdminSettingController::class, 'rebuildConfigCache']);
});

