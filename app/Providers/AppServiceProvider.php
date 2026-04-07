<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        // In GitHub Codespaces the user accesses the app through the Vite dev-server
        // at port 5173 (see vite.config.js). APP_URL is typically 'http://localhost'
        // in the .env, which causes Laravel's UrlGenerator to force that host for
        // all generated URLs — overriding the X-Forwarded-Host proxy header set by
        // the Vite proxy. This makes server-side redirect() calls produce URLs like
        // "https://localhost/..." instead of the correct Codespaces URL.
        //
        // When the Codespaces environment variables are present we override the
        // forced root URL at runtime so redirects resolve to the correct host.
        $codespace = env('CODESPACE_NAME');
        $domain    = env('GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN');

        if ($codespace && $domain) {
            URL::forceRootUrl("https://{$codespace}-5173.{$domain}");
            URL::forceScheme('https');
        }

        if ((bool) env('MYSQL_QUERY_LOG_ENABLED', $this->app->environment('local'))) {
            $connection = (string) env('MYSQL_QUERY_LOG_CONNECTION', 'mysql');
            $channel = (string) env('MYSQL_QUERY_LOG_CHANNEL', 'mysql_queries');

            DB::listen(function (QueryExecuted $query) use ($connection, $channel): void {
                if ($query->connectionName !== $connection) {
                    return;
                }

                $bindings = array_map(static function (mixed $binding): mixed {
                    if ($binding instanceof \DateTimeInterface) {
                        return $binding->format('Y-m-d H:i:s');
                    }

                    if (is_bool($binding)) {
                        return (int) $binding;
                    }

                    return $binding;
                }, $query->bindings);

                Log::channel($channel)->info('mysql.query', [
                    'connection' => $query->connectionName,
                    'time_ms' => $query->time,
                    'sql' => $query->sql,
                    'bindings' => $bindings,
                ]);
            });
        }
    }
}
