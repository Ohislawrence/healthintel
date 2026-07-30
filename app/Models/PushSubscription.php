<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'endpoint',
        'p256dh',
        'auth',
        'content_encoding',
        'user_agent',
        'device_type',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription data in Web Push format.
     */
    public function toWebPushConfig(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => [
                'p256dh' => $this->p256dh,
                'auth' => $this->auth,
            ],
            'contentEncoding' => $this->content_encoding ?? 'aes128gcm',
        ];
    }

    /**
     * Create or update a subscription from a browser PushSubscription JSON.
     */
    public static function upsertFromJson(array $subscriptionJson, ?int $userId = null): self
    {
        return self::updateOrCreate(
            ['endpoint' => $subscriptionJson['endpoint']],
            [
                'user_id' => $userId,
                'p256dh' => $subscriptionJson['keys']['p256dh'] ?? null,
                'auth' => $subscriptionJson['keys']['auth'] ?? null,
                'content_encoding' => $subscriptionJson['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => request()->userAgent(),
                'device_type' => self::detectDeviceType(),
                'last_used_at' => now(),
                'is_active' => true,
            ]
        );
    }

    private static function detectDeviceType(): string
    {
        $ua = request()->userAgent() ?? '';
        if (preg_match('/Android/i', $ua)) return 'android';
        if (preg_match('/iPhone|iPad/i', $ua)) return 'ios';
        if (preg_match('/Windows/i', $ua)) return 'windows';
        if (preg_match('/Macintosh|Mac OS/i', $ua)) return 'mac';
        if (preg_match('/Linux/i', $ua)) return 'linux';
        return 'unknown';
    }
}