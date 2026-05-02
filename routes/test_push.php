<?php
/**
 * Quick test: send a web push to user ID 1.
 * Access: GET /test-push
 * Only works in local environment.
 */

use App\Models\Notification;
use Illuminate\Support\Facades\Route;

Route::get('/test-push', function () {
    if (!app()->isLocal()) abort(403);

    // Try to find a user that has a push subscription first
    $user = \App\Models\User::whereHas('pushSubscriptions')->first();
    
    // If no one subscribed yet, just pick the first user
    if (!$user) {
        $user = \App\Models\User::first();
    }

    if (!$user) {
        return response()->json([
            'error' => 'User not found',
            'message' => 'Your users table is empty. Please register a user first.'
        ], 404);
    }

    $subscriptionCount = $user->pushSubscriptions()->count();

    // Creating a Notification will trigger NotificationObserver → WebPush automatically
    \App\Models\Notification::create([
        'user_id'     => $user->id,
        'type'        => 'order',
        'title'       => '🔔 Test Push Notifikasi',
        'description' => 'Web Push berhasil dikonfigurasi! Notifikasi ini dikirim dari server.',
        'metadata'    => ['order_id' => null],
    ]);

    return response()->json([
        'success' => true,
        'message' => "Push notification triggered for user #{$user->id} ({$user->name})",
        'details' => [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'subscriptions_found' => $subscriptionCount,
            'warning' => $subscriptionCount === 0 ? 'User found but they have NOT subscribed to push notifications in the browser yet.' : null
        ]
    ]);
});
