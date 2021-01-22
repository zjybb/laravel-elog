<?php

namespace Duduke\Elog\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;


class ServiceProvider extends IlluminateServiceProvider
{

    protected static $sql = [];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/elog.php' => config_path('elog.php')
            ], 'elog');
        }

        $this->mergeLogConfig();
        $this->setLogDefault();

        config('elog.db_query.enabled', true) && $this->writeSqlLog();
    }

    protected function writeSqlLog()
    {
        DB::listen(function (QueryExecuted $query) {
            $sqlWithPlaceholders = str_replace(['%', '?'], ['%%', '%s'], $query->sql);
            $bindings = $query->connection->prepareBindings($query->bindings);
            $pdo = $query->connection->getPdo();
            $realSql = $sqlWithPlaceholders;
            $duration = $this->formatDuration($query->time / 1000);

            if (count($bindings) > 0) {
                $realSql = vsprintf($sqlWithPlaceholders, array_map([$pdo, 'quote'], $bindings));
            }

            $sql = request()->server->get('db_query', [
                    'query_duration' => 0,
                    'count' => 0,
                    'query' => []
                ]
            );

            $sql['query_duration'] += $query->time;
            $sql['count']++;
            $sql['query'][] = [
                'db' => $query->connection->getDatabaseName(),
                'sql' => $realSql,
                'duration' => $duration,
            ];

            request()->server->set('db_query', $sql);
        });
    }

    private function formatDuration($seconds): string
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        }
        return round($seconds, 2) . 's';
    }

    private function mergeLogConfig()
    {
        config([
            'logging.channels' => array_merge(config('logging.channels'), config('elog.channels', [])),
        ]);
    }

    private function setLogDefault()
    {
        if (config('elog.toes')) {
            if (config('elog.tofile')) {
                config(['logging.default' => 'e_stack']);
            } else {
                config(['logging.default' => 'es']);
            }
        }
    }

}
