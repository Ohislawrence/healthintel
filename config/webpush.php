<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAPID Authentication
    |--------------------------------------------------------------------------
    |
    | VAPID (Voluntary Application Server Identification) is used to
    | authenticate the application server with push services (Google FCM,
    | Mozilla autopush, etc.) when sending Web Push notifications.
    |
    | Generate keys using: php artisan webpush:keys
    | Or programmatically: WebPushService::generateVapidKeys()
    |
    */

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', env('APP_URL', 'https://healthintel.ng')),
        'public_key' => env('VAPID_PUBLIC_KEY', 'BC9fhZuJfYJpGjjDfj_lOJS8P8oRcoguAcp2KNKITt5Dy-qrkac07fgrdtmcrHCFYmgLDksznSGEeLcDM2uFvN0'),
        'private_key' => env('VAPID_PRIVATE_KEY', 'L5UfJz7-oQuhLRpFfR6EMI7MH1ON1xw-v6xixlASt8o'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Notification Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'icon' => '/icons/icon-192x192.png',
        'badge' => '/icons/icon-72x72.png',
        'ttl' => 2419200, // 4 weeks in seconds (max for web push)
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Notifications Integration
    |--------------------------------------------------------------------------
    |
    | When an admin sends a notification through the admin panel, should
    | it also be dispatched as a Web Push notification to users?
    |
    */

    'send_admin_notifications' => env('WEBPUSH_SEND_ADMIN_NOTIFICATIONS', true),

];