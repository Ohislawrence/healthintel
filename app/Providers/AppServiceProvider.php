<?php

namespace App\Providers;

use App\Services\CreditService;
use App\Services\PaymentService;
use App\Services\PaystackService;
use App\Services\ReferralService; // ADDED
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CreditService::class);
        $this->app->singleton(PaystackService::class);
        $this->app->singleton(PaymentService::class);
        $this->app->singleton(ReferralService::class); // ADDED
    }

    public function boot(): void
    {
        // Fix "Specified key was too long" on MySQL/MariaDB < 5.7.7
        Schema::defaultStringLength(191);

        // API rate limiter: 60 requests per minute per user (or IP for guests)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

    }
}


