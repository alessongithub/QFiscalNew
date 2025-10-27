<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Share partner branding if available (fallback to ensure after-auth availability)
        \View::composer('*', function ($view) {
            if (!app()->bound('partner')) {
                try {
                    $user = auth('web')->user();
                    $tenant = $user?->tenant;
                    $tenantPartner = $tenant?->partner;
                    if ($tenantPartner && $tenantPartner->active) {
                        app()->instance('partner', $tenantPartner);
                        $view->with('partner', $tenantPartner);
                    }
                    // Share plan features & limited mode
                    $features = [];
                    if ($tenant && $tenant->plan) {
                        $features = is_array($tenant->plan->features) ? $tenant->plan->features : (json_decode($tenant->plan->features, true) ?? []);
                    }
                    $view->with('planFeatures', $features);
                    $view->with('limitedMode', (bool) config('app.limited_mode', false));
                } catch (\Throwable $e) {
                    // ignore
                }
            } else {
                $view->with('partner', app('partner'));
            }
        });
    }
}
