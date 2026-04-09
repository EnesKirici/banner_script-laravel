<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AutoBanMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Whitelist kontrolü
        if (BlockedIp::isWhitelisted($ip) || BlockedIp::isWhitelistedBot($request->userAgent())) {
            return $next($request);
        }

        $this->trackRequest($request, $ip);

        return $next($request);
    }

    private function trackRequest(Request $request, string $ip): void
    {
        $config = config('security.auto_ban');
        $window = $config['window'];
        $maxRequests = $config['max_requests'];

        $cacheKey = "req_count_{$ip}";

        $requestCount = (int) Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $requestCount, $window);

        // Eşiğin %80'ine ulaşıldığında uyarı logu
        $warningThreshold = (int) ($maxRequests * 0.8);
        if ($requestCount === $warningThreshold) {
            SecurityLog::record(
                ip: $ip,
                eventType: 'rate_warning',
                description: "{$window}sn içinde {$requestCount} istek - eşiğe yaklaşıyor ({$maxRequests})",
                requestCount: $requestCount,
                userAgent: $request->userAgent(),
                url: $request->fullUrl(),
            );
        }

        if ($requestCount >= $maxRequests) {
            BlockedIp::autoBan(
                ip: $ip,
                reason: "Rate limit aşımı: {$window}sn içinde {$requestCount} istek",
                banType: 'rate_limit',
                requestCount: $requestCount,
            );

            Cache::forget($cacheKey);

            abort(403, 'Erişiminiz engellenmiştir.');
        }
    }
}
