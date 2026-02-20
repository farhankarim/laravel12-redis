<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        if (! $this->app->runningInConsole() && request()->server('HTTP_HOST')) {
            $forwardedHostHeader = (string) request()->header('X-Forwarded-Host', '');
            $forwardedProtoHeader = (string) request()->header('X-Forwarded-Proto', '');

            $host = trim(explode(',', $forwardedHostHeader)[0] ?? '') ?: request()->getHost();
            $forwardedProto = trim(explode(',', $forwardedProtoHeader)[0] ?? '');

            $scheme = str_contains(strtolower($forwardedProto), 'https') || str_ends_with($host, '.app.github.dev')
                ? 'https'
                : request()->getScheme();

            URL::forceRootUrl("{$scheme}://{$host}");
            URL::forceScheme($scheme);
        }
    }
}
