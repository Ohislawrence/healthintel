<?php

use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'home'])->name('home');
Route::get('/offline', fn () => view('app'))->name('offline');
Route::get('/login', fn () => view('app'))->name('login');
Route::get('/about', [FrontendController::class, 'about'])->name('about');
Route::get('/how-it-works', [FrontendController::class, 'howItWorks'])->name('how-it-works');
Route::get('/features', [FrontendController::class, 'features'])->name('features');
Route::get('/clinical-validation', [FrontendController::class, 'clinicalValidation'])->name('clinical-validation');
Route::get('/for-individuals', [FrontendController::class, 'forIndividuals'])->name('for-individuals');
Route::get('/for-clinicians', [FrontendController::class, 'forClinicians'])->name('for-clinicians');
Route::get('/for-labs', [FrontendController::class, 'forLabs'])->name('for-labs');
Route::get('/for-insurance', [FrontendController::class, 'forInsurance'])->name('for-insurance');
Route::get('/contact', [FrontendController::class, 'contact'])->name('contact');
Route::get('/partnerships', [FrontendController::class, 'partnerships'])->name('partnerships');
Route::get('/blog', [FrontendController::class, 'blog'])->name('blog');
Route::get('/blog/{slug}', [FrontendController::class, 'blogShow'])->name('blog.detail');
Route::get('/r/{slug}', [FrontendController::class, 'partnerPatientResults'])->name('partner.results');
Route::get('/privacy', [FrontendController::class, 'privacy'])->name('privacy');
Route::get('/terms', [FrontendController::class, 'terms'])->name('terms');
Route::get('/sitemap.xml', [FrontendController::class, 'sitemap']);

// Admin React SPA — catch-all (only authenticated admin routes handled by React router)
Route::get('/admin/{any?}', function () {
    return view('app');
})->where('any', '.*');

// All remaining paths serve the React SPA (login, register, dashboard, etc.)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api).*$');