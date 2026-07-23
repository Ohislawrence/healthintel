<?php

use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontendController::class, 'home'])->name('home');
Route::get('/about', [FrontendController::class, 'about'])->name('about');
Route::get('/how-it-works', [FrontendController::class, 'howItWorks'])->name('how-it-works');
Route::get('/features', [FrontendController::class, 'features'])->name('features');
Route::get('/pricing', [FrontendController::class, 'pricing'])->name('pricing');
Route::get('/contact', [FrontendController::class, 'contact'])->name('contact');
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