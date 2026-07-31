<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PushController extends BaseController
{
    /**
     * Subscribe to push notifications.
     * Stores the browser's PushSubscription JSON for the authenticated user.
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscription' => 'required|array',
            'subscription.endpoint' => 'required|string|url',
            'subscription.keys' => 'required|array',
            'subscription.keys.p256dh' => 'required|string',
            'subscription.keys.auth' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Invalid subscription data', 422, $validator->errors()->toArray());
        }

        $userId = $request->user()?->id;

        $subscription = PushSubscription::upsertFromJson(
            $request->input('subscription'),
            $userId
        );

        return $this->success([
            'subscription_id' => $subscription->id,
            'registered' => true,
        ], 'Push subscription saved');
    }

    /**
     * Unsubscribe from push notifications.
     * Deactivates the subscription identified by endpoint.
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'endpoint' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Endpoint is required', 422);
        }

        $deleted = PushSubscription::where('endpoint', $request->input('endpoint'))->delete();

        return $this->success([
            'unsubscribed' => $deleted > 0,
        ], $deleted > 0 ? 'Unsubscribed successfully' : 'Subscription not found');
    }

    /**
     * Update subscription (used when pushsubscriptionchange fires in SW).
     * The old subscription is removed and the new one is saved.
     */
    public function subscriptionUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscription' => 'required|array',
            'subscription.endpoint' => 'required|string',
            'subscription.keys' => 'required|array',
            'subscription.keys.p256dh' => 'required|string',
            'subscription.keys.auth' => 'required|string',
            'old_endpoint' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Invalid subscription data', 422);
        }

        // Remove old subscription if provided
        if ($request->filled('old_endpoint')) {
            PushSubscription::where('endpoint', $request->input('old_endpoint'))->delete();
        }

        $userId = $request->user()?->id;
        $subscription = PushSubscription::upsertFromJson(
            $request->input('subscription'),
            $userId
        );

        return $this->success([
            'subscription_id' => $subscription->id,
        ], 'Subscription updated');
    }

    /**
     * Record that a push notification was received by the client.
     * Used for delivery tracking.
     */
    public function notificationReceived(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|integer|exists:admin_notifications,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Invalid notification ID', 422);
        }

        // Mark as read if an AdminNotification record exists
        \App\Models\AdminNotification::where('id', $request->input('notification_id'))
            ->update(['read_at' => now()]);

        return $this->success(null, 'Receipt recorded');
    }

    /**
     * Get VAPID public key for the frontend.
     * The frontend needs this to create a push subscription.
     */
    public function vapidPublicKey()
    {
        return $this->success([
            'publicKey' => config('webpush.vapid.public_key'),
        ]);
    }
}