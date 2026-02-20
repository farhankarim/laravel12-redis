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
