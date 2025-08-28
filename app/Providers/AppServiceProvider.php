<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Guest-only IP-based rate limiting for public endpoints
        RateLimiter::for('feed', function (Request $request) {
            $perMinute = (int) env('FEED_PER_MINUTE', 60);
            return [Limit::perMinute($perMinute)->by($request->ip())];
        });

        RateLimiter::for('photo-show', function (Request $request) {
            $perMinute = (int) env('SHOW_PER_MINUTE', 120);
            return [Limit::perMinute($perMinute)->by($request->ip())];
        });

        RateLimiter::for('download', function (Request $request) {
            $perMinute = (int) env('DOWNLOADS_PER_MINUTE', 30);
            return [Limit::perMinute($perMinute)->by($request->ip())];
        });
    }
}
