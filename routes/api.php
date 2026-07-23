<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\HealthProfileController;
use App\Http\Controllers\Api\HealthScoreController;
use App\Http\Controllers\Api\LabSubmissionController;
use App\Http\Controllers\Api\PartnerPortalController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProviderDirectoryController;
use App\Http\Controllers\Api\SymptomCheckerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/

// Auth (register & login are public, throttled)
Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:6,1');
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1');

    // Provider directory (public read-only)
    Route::get('/providers', [ProviderDirectoryController::class, 'index']);
    Route::get('/providers/sponsored-banners', [ProviderDirectoryController::class, 'sponsoredBanners']);
// Specific routes must come BEFORE the wildcard {slug} route
Route::get('/providers/specialties', [ProviderDirectoryController::class, 'specialties']);
Route::get('/providers/states', [ProviderDirectoryController::class, 'states']);
Route::get('/providers/types', [ProviderDirectoryController::class, 'types']);
Route::get('/providers/insurance/list', [ProviderDirectoryController::class, 'insuranceList']);
Route::get('/providers/{slug}', [ProviderDirectoryController::class, 'show']);

// Insurance/HMO comparison (public read-only)
Route::get('/insurance/hmos', [ProviderDirectoryController::class, 'insuranceList']);

// Test panels (public)
Route::get('/panels', [LabSubmissionController::class, 'panels']);
Route::get('/panels/{slug}', [LabSubmissionController::class, 'panelShow']);

// Symptoms (public listing)
Route::get('/symptoms', [SymptomCheckerController::class, 'index']);

// Payments (Paystack webhook is public, require no auth)
Route::post('/payment/webhook', [PaymentController::class, 'webhook']);

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

    // Lab Submissions
    Route::get('/submissions', [LabSubmissionController::class, 'index']);
    Route::get('/submissions/{id}', [LabSubmissionController::class, 'show']);
    Route::post('/submissions', [LabSubmissionController::class, 'submit']);
    Route::post('/submissions/pdf', [LabSubmissionController::class, 'submitPdf']);
    Route::get('/trends', [LabSubmissionController::class, 'trends']);

    // Symptom Checker
    Route::post('/symptoms/suggest', [SymptomCheckerController::class, 'suggestPanels']);
    Route::post('/symptoms/check', [SymptomCheckerController::class, 'check']);

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

    // Partner Portal (authenticated partner routes)
    Route::get('/partner/dashboard', [PartnerPortalController::class, 'dashboard']);
    Route::put('/partner/listing', [PartnerPortalController::class, 'updateListing']);
    Route::post('/partner/regenerate-code', [PartnerPortalController::class, 'regenerateAccessCode']);
});
