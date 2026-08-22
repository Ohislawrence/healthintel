<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\HealthProfileController;
use App\Http\Controllers\Api\HealthScoreController;
use App\Http\Controllers\Api\LabSubmissionController;
use App\Http\Controllers\Api\PartnerPortalController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProviderDirectoryController;
use App\Http\Controllers\Api\PushController;
use App\Http\Controllers\Api\SymptomCheckerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/

// Push notifications — VAPID public key (needed by frontend to subscribe)
Route::get('/push/vapid-public-key', [PushController::class, 'vapidPublicKey']);

// Auth (register & login are public, throttled)
Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:6,1');
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');
Route::post('/auth/google', [AuthController::class, 'googleAuth'])
    ->middleware('throttle:10,1');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:6,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:6,1');
Route::post('/auth/verify-email', [AuthController::class, 'verifyEmail'])
    ->middleware('throttle:10,1');
Route::post('/auth/resend-verification', [AuthController::class, 'resendVerificationCode'])
    ->middleware('throttle:6,1');

    // Blog (public)
Route::get('/blog/posts', [BlogController::class, 'index']);
Route::get('/blog/posts/{slug}', [BlogController::class, 'show']);
Route::get('/blog/categories', [BlogController::class, 'categories']);

// Provider directory (public read-only)
    Route::get('/providers', [ProviderDirectoryController::class, 'index']);
    Route::get('/providers/sponsored-banners', [ProviderDirectoryController::class, 'sponsoredBanners']);
// Specific routes must come BEFORE the wildcard {slug} route
Route::get('/providers/specialties', [ProviderDirectoryController::class, 'specialties']);
Route::get('/providers/states', [ProviderDirectoryController::class, 'states']);
Route::get('/providers/types', [ProviderDirectoryController::class, 'types']);
Route::get('/providers/insurance/list', [ProviderDirectoryController::class, 'insuranceList']);
Route::get('/providers/nearby-recommended', [ProviderDirectoryController::class, 'nearbyRecommended']);
Route::get('/providers/{slug}', [ProviderDirectoryController::class, 'show']);

// Insurance/HMO comparison (public read-only)
Route::get('/insurance/hmos', [ProviderDirectoryController::class, 'insuranceList']);

// Test panels (public)
Route::get('/panels', [LabSubmissionController::class, 'panels']);
Route::get('/panels/{slug}', [LabSubmissionController::class, 'panelShow']);

// Symptoms (public listing)
Route::get('/symptoms', [SymptomCheckerController::class, 'index']);

// Payments (Paystack, Flutterwave & Nomba webhooks are public, require no auth)
//
// Providers often send a GET/HEAD "verification ping" when you save a webhook
// URL. Return 200 for those so the URL is accepted — the actual payment
// notifications are signed POSTs handled below.
Route::get('/payment/webhook', fn () => response()->json(['status' => 'ok']));
Route::get('/payment/webhook/flutterwave', fn () => response()->json(['status' => 'ok']));
Route::get('/payment/webhook/nomba', fn () => response()->json(['status' => 'ok']));

Route::post('/payment/webhook', [PaymentController::class, 'webhook']);
Route::post('/payment/webhook/flutterwave', [PaymentController::class, 'flutterwaveWebhook']);
Route::post('/payment/webhook/nomba', [PaymentController::class, 'nombaWebhook']);

// Partnership inquiry (public — from the /partnerships page modal)
Route::post('/partnership-inquiry', [\App\Http\Controllers\Api\PartnershipInquiryController::class, 'store'])
    ->middleware('throttle:5,10');

// Provider listing / ad request (public — from "list your lab/hospital" page)
Route::post('/provider-listing-request', [\App\Http\Controllers\Api\ProviderListingRequestController::class, 'store'])
    ->middleware('throttle:5,10');

// Partner portal login (public, access-code based)
Route::post('/partner/login', [PartnerPortalController::class, 'login'])
    ->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Authenticated API Routes (Sanctum token-based)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Health Profile
    Route::get('/profile', [HealthProfileController::class, 'show']);
    Route::put('/profile', [HealthProfileController::class, 'update']);

    // Health Score & Engagement
    Route::get('/health-score', [HealthScoreController::class, 'score']);
    Route::get('/health-reminders', [HealthScoreController::class, 'reminders']);

    // Health Metrics (trackers & calculators persistence)
    Route::post('/health-metrics', [HealthScoreController::class, 'saveMetric']);
    Route::get('/health-metrics', [HealthScoreController::class, 'getMetrics']);
    Route::post('/health-metrics/sync', [HealthScoreController::class, 'syncTrackers']);
    Route::get('/health-metrics/today', [HealthScoreController::class, 'todayTrackers']);
    Route::get('/health-metrics/food-insights', [HealthScoreController::class, 'foodInsights']);

    // Lab Submissions
    Route::get('/submissions', [LabSubmissionController::class, 'index']);
    Route::get('/submissions/{id}', [LabSubmissionController::class, 'show']);
    Route::post('/submissions', [LabSubmissionController::class, 'submit']);
    Route::post('/submissions/pdf/draft', [LabSubmissionController::class, 'submitPdfDraft']);
    Route::post('/submissions/pdf/draft/{draftId}/confirm', [LabSubmissionController::class, 'confirmPdfDraft']);
    Route::post('/submissions/draft/{draftId}/confirm', [LabSubmissionController::class, 'confirmDraft']);
    Route::post('/submissions/pdf', [LabSubmissionController::class, 'submitPdf']);
    Route::post('/submissions/image', [LabSubmissionController::class, 'submitImage']);
    Route::post('/submissions/{id}/interpret-stream', [LabSubmissionController::class, 'interpretStream']);
    Route::post('/submissions/{id}/translate', [LabSubmissionController::class, 'translate']);
    Route::get('/trends', [LabSubmissionController::class, 'trends']);
    Route::post('/trends/share', [LabSubmissionController::class, 'shareTrend']);

    // Symptom Checker
    Route::post('/symptoms/suggest', [SymptomCheckerController::class, 'suggestPanels']);
    Route::post('/symptoms/check', [SymptomCheckerController::class, 'check']);
    Route::post('/symptoms/funnel/track', [SymptomCheckerController::class, 'trackFunnel']);
    Route::get('/symptoms/funnel/analytics', [SymptomCheckerController::class, 'funnelAnalytics']);

    // Provider Directory (actions requiring auth)
    Route::post('/providers/{slug}/click-out', [ProviderDirectoryController::class, 'clickOut']);
    Route::post('/providers/insurance/enquire', [ProviderDirectoryController::class, 'insuranceEnquire']);

    // Insurance/HMO comparison (actions requiring auth)
    Route::post('/insurance/enquire', [ProviderDirectoryController::class, 'insuranceEnquire']);

    // Payments
    Route::get('/payment/packages', [PaymentController::class, 'packages']);
    Route::post('/payment/initialize', [PaymentController::class, 'initialize']);
    Route::get('/payment/verify', [PaymentController::class, 'verify']);
    Route::get('/payment/summary', [PaymentController::class, 'summary']);
    Route::get('/payment/gateway', [PaymentController::class, 'gateway']);

    // User Feedback
    Route::post('/feedback', [FeedbackController::class, 'store']);
    Route::get('/feedback', [FeedbackController::class, 'index']);
    Route::put('/feedback/{id}', [FeedbackController::class, 'update']);

    // Appointments
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::put('/appointments/{id}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);

    // User Notifications (in-app)
    Route::get('/notifications', [\App\Http\Controllers\Api\UserNotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\UserNotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\UserNotificationController::class, 'markRead']);
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Api\UserNotificationController::class, 'markAllRead']);

    // Global Search
    Route::get('/search', \App\Http\Controllers\Api\SearchController::class);

    // Push Notifications
    Route::post('/push/subscribe', [PushController::class, 'subscribe']);
    Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe']);
    Route::post('/push/subscription-update', [PushController::class, 'subscriptionUpdate']);
    Route::post('/push/notification-received', [PushController::class, 'notificationReceived']);

    // Gamification
    Route::get('/gamification', [HealthScoreController::class, 'gamification']);

    // Health Report Card
    Route::get('/health-report-card', [HealthScoreController::class, 'downloadReportCard']);
    Route::get('/languages', fn () => response()->json(\App\Services\TranslationService::availableLanguages()));

    // Result Chat Conversations
    Route::get('/conversations', [\App\Http\Controllers\Api\ResultChatController::class, 'index']);
    Route::get('/conversations/{id}', [\App\Http\Controllers\Api\ResultChatController::class, 'show']);
    Route::post('/conversations', [\App\Http\Controllers\Api\ResultChatController::class, 'startConversation']);
    Route::post('/conversations/{id}/message', [\App\Http\Controllers\Api\ResultChatController::class, 'sendMessage']);
    Route::delete('/conversations/{id}', [\App\Http\Controllers\Api\ResultChatController::class, 'destroy']);

    // Referral Program
    Route::get('/referral/link', [\App\Http\Controllers\Api\ReferralController::class, 'myLink']);
    Route::get('/referral/earnings', [\App\Http\Controllers\Api\ReferralController::class, 'earnings']);
    Route::get('/referral/earnings/summary', [\App\Http\Controllers\Api\ReferralController::class, 'summary']);
    Route::get('/referral/payouts', [\App\Http\Controllers\Api\ReferralController::class, 'payoutHistory']);
    Route::post('/referral/payout/request', [\App\Http\Controllers\Api\ReferralController::class, 'requestPayout']);
    Route::get('/referral/bank-details', [\App\Http\Controllers\Api\ReferralController::class, 'bankDetails']);
    Route::post('/referral/bank-details', [\App\Http\Controllers\Api\ReferralController::class, 'saveBankDetails']);

    // Partner Portal (authenticated partner routes)
    Route::get('/partner/dashboard', [PartnerPortalController::class, 'dashboard']);
    Route::get('/partner/listing', [PartnerPortalController::class, 'myListing']);
    Route::put('/partner/listing', [PartnerPortalController::class, 'updateListing']);
    Route::get('/partner/listing-requests', [PartnerPortalController::class, 'myRequests']);
    Route::post('/partner/promotion-request', [PartnerPortalController::class, 'requestPromotion']);
    Route::post('/partner/regenerate-code', [PartnerPortalController::class, 'regenerateAccessCode']);

    // Partner Interpretation (B2B lab partners)
    Route::get('/partner/stats', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'stats']);
    Route::get('/partner/roi', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'roi']);
    Route::get('/partner/delivery-health', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'deliveryHealth']);
    Route::get('/partner/panels', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'panels']);
    Route::get('/partner/patients', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'patients']);
    Route::post('/partner/interpretations', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'store']);
    Route::post('/partner/interpretations/bulk', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'bulkStore']);
    Route::post('/partner/interpretations/batch/pdf', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'downloadBatchPdf']);
    Route::get('/partner/interpretations/batch/{batchId}/status', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'batchStatus']);
    Route::post('/partner/interpretations/batch/{batchId}/deliver-all', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'deliverAll']);
    Route::get('/partner/interpretations/{id}', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'show']);
    Route::put('/partner/interpretations/{id}', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'update']);
    Route::post('/partner/interpretations/{id}/suppress', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'suppress']);
    Route::get('/partner/interpretations/{id}/history', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'history']);
    Route::post('/partner/interpretations/{id}/toggle-version', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'toggleVersion']);
    Route::get('/partner/interpretations/{id}/pdf', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'downloadPdf']);
    Route::post('/partner/interpretations/{id}/deliver', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'deliver']);
    Route::post('/partner/v1/interpretations', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'apiInterpretation']);
    Route::post('/partner/v1/hl7', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'hl7Parse']);
    Route::get('/partner/analytics/population', [\App\Http\Controllers\Api\Partner\PartnerInterpretationController::class, 'populationAnalytics']);
});