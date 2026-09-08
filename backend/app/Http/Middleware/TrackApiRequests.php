<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackApiRequests
{
    /**
     * Clave de cache para el contador de peticiones de las ultimas 24h.
     * Se expone publicamente para que AdminStatsController lea el mismo valor.
     */
    public const CACHE_KEY = 'api_requests_count_24h';

    public function handle(Request $request, Closure $next): Response
    {
        // increment() crea la clave en 0 si no existe todavia, y le
        // suma 1 de forma atomica - segura incluso si llegan varias
        // peticiones al mismo tiempo.
        Cache::add(self::CACHE_KEY, 0, now()->addHours(24));
        Cache::increment(self::CACHE_KEY);

        return $next($request);
    }
}