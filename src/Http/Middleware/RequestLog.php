<?php

namespace Xiaozhu\ELog\Http\Middleware;

use Closure;
use Xiaozhu\ELog\Jobs\ELog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestLog
{

    public function handle(Request $request, Closure $next)
    {
        $uuid = $request->headers->get('X-LOG-ID') ?: Str::orderedUuid()->toString();
        $request->server->set('X-LOG-ID', $uuid);

        return $next($request);
    }

    public function terminate(Request $request, $response)
    {
        if (config('elog.request.enabled', true)) {

            if (Str::startsWith(request()->getPathInfo(), config('elog.request.filter', []))) {
                return;
            }

            $start = $request->server('REQUEST_TIME_FLOAT');
            $end = microtime(true);
            $context = [
                'time' => Carbon::createFromTimestamp($start)->toDateTimeString(),
                'duration' => $this->formatDuration($end - $start),
                'request' => json_encode($request->all()),
                'http_code' => $response instanceof Response ? $response->getStatusCode() : 0,
//                'response' => $response instanceof Response ? json_decode($response->getContent(), true) : (string)$response,
            ];

            dispatch(new ELog('request', $context))->onQueue(config('elog.queue_name', 'elog'));
        }

        if (config('elog.db_query.enabled', true)) {
            $query = request()->server->get('db_query');
            !is_null($query) && dispatch(new ELog('db_query', $query))->onQueue(config('elog.queue_name', 'elog'));
        }

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
}
