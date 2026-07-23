<?php

use App\Http\Controllers\Api\Admin\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);

    // Analytics
    Route::get('/analytics', [AdminController::class, 'analytics']);

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

    // Credit Packages
    Route::get('/credit-packages', [AdminController::class, 'creditPackages']);
    Route::post('/credit-packages', [AdminController::class, 'creditPackageStore']);
    Route::put('/credit-packages/{id}', [AdminController::class, 'creditPackageUpdate']);

    // Users
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/users/{id}', [AdminController::class, 'userShow']);
    Route::post('/users/{id}/credit', [AdminController::class, 'grantCredits']);

    // Appointments
    Route::get('/appointments', [AdminController::class, 'appointments']);
    Route::put('/appointments/{id}', [AdminController::class, 'appointmentUpdate']);

    // User Feedback
    Route::get('/feedback', [AdminController::class, 'feedback']);
    Route::put('/feedback/{id}', [AdminController::class, 'feedbackUpdate']);

    // Partner Portal
    Route::get('/partners', [AdminController::class, 'partners']);

    // PDF Submissions
    Route::get('/pdf-submissions', [AdminController::class, 'pdfSubmissions']);

    // All Submissions
    Route::get('/submissions', [AdminController::class, 'submissions']);

    // Notifications
    Route::get('/notifications', [AdminController::class, 'notifications']);
    Route::post('/notifications', [AdminController::class, 'notificationStore']);

    // Audit Log
    Route::get('/audit-log', [AdminController::class, 'auditLog']);
});

