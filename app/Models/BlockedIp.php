<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address',
        'reason',
        'ban_type',
        'request_count',
        'violation_count',
        'blocked_until',
    ];

    protected function casts(): array
    {
        return [
            'blocked_until' => 'datetime',
            'request_count' => 'integer',
            'violation_count' => 'integer',
        ];
    }

    public static function isBlocked(string $ip): bool
    {
        return static::where('ip_address', $ip)
            ->where(function ($query) {
                $query->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            })
            ->exists();
    }

    /**
     * IP'yi otomatik olarak banla ve logla.
     */
    public static function autoBan(string $ip, string $reason, string $banType, int $requestCount = 0): static
    {
        $config = config('security.auto_ban');
        $existing = static::where('ip_address', $ip)->first();

        $violationCount = $existing ? $existing->violation_count + 1 : 1;
        $isPermanent = $violationCount >= $config['permanent_after_violations'];

        $blockedUntil = $isPermanent ? null : now()->addMinutes($config['ban_duration']);

        $record = static::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $reason,
                'ban_type' => $banType,
                'request_count' => $requestCount,
                'violation_count' => $violationCount,
                'blocked_until' => $blockedUntil,
            ]
        );

        // Cache'i temizle (CheckBlockedIp middleware hemen algılasın)
        Cache::forget("blocked_ip_{$ip}");

        Log::channel('daily')->warning('Otomatik IP banlandı', [
            'ip' => $ip,
            'reason' => $reason,
            'ban_type' => $banType,
            'request_count' => $requestCount,
            'violation_count' => $violationCount,
            'permanent' => $isPermanent,
            'blocked_until' => $blockedUntil?->toDateTimeString(),
        ]);

        // Veritabanına güvenlik logu yaz
        SecurityLog::record(
            ip: $ip,
            eventType: "ban_{$banType}",
            description: $reason,
            requestCount: $requestCount,
            userAgent: request()->userAgent(),
            url: request()->fullUrl(),
            metadata: [
                'violation_count' => $violationCount,
                'permanent' => $isPermanent,
                'blocked_until' => $blockedUntil?->toDateTimeString(),
            ],
        );

        return $record;
    }

    /**
     * IP'nin whitelist'te olup olmadığını kontrol et.
     */
    public static function isWhitelisted(string $ip): bool
    {
        $whitelist = config('security.whitelist', []);

        return in_array($ip, $whitelist, true);
    }

    /**
     * Bilinen iyi bot olup olmadığını kontrol et.
     */
    public static function isWhitelistedBot(?string $userAgent): bool
    {
        if (! $userAgent) {
            return false;
        }

        $botPatterns = config('security.bot_whitelist', []);

        foreach ($botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
