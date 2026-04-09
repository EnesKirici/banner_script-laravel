<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityLog extends Model
{
    protected $fillable = [
        'ip_address',
        'event_type',
        'description',
        'request_count',
        'user_agent',
        'url',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'request_count' => 'integer',
        ];
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public static function record(
        string $ip,
        string $eventType,
        string $description,
        int $requestCount = 0,
        ?string $userAgent = null,
        ?string $url = null,
        ?array $metadata = null,
    ): static {
        return static::create([
            'ip_address' => $ip,
            'event_type' => $eventType,
            'description' => $description,
            'request_count' => $requestCount,
            'user_agent' => $userAgent,
            'url' => $url,
            'metadata' => $metadata,
        ]);
    }
}
