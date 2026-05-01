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

    $user = \App\Models\User::find(1);
    if (!$user) return response()->json(['error' => 'User not found'], 404);

    // Creating a Notification will trigger NotificationObserver → WebPush automatically
    Notification::create([
        'user_id'     => $user->id,
        'type'        => 'order',
        'title'       => '🔔 Test Push Notifikasi',
        'description' => 'Web Push berhasil dikonfigurasi! Notifikasi ini dikirim dari server.',
        'metadata'    => ['order_id' => null],
    ]);

    return response()->json([
        'success' => true,
        'message' => "Push sent to user #{$user->id} ({$user->name})",
    ]);
});
