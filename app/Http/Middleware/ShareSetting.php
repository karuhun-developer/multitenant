<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareSetting
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If not login
        if (!auth()->check()) {
            // Check subdomain
            $host = $request->getHost();
            $subdomain = explode('.', $host)[0];

            // Not in the main domain
            if($host !== config('app.main_domain')) {
                // Check tenant
                $tenant = Tenant::where('subdomain', $subdomain)->first();

                // Tenant exists
                if($tenant) {
                    $settings = Setting::where('tenant_id', $tenant->id)->first();
                    View::share('settings', $settings);
                    return $next($request);
                }
            }
        }

        // Pas setting
        try {
            $settings = Setting::first();
            View::share('settings', $settings);
        } catch (\Exception $e) {
            View::share('settings', null);
        }

        return $next($request);
    }
}
