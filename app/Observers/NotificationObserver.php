<?php

namespace App\Observers;

use App\Models\Notification;
use App\Services\WebPushService;

class NotificationObserver
{
    public function created(Notification $notification): void
    {
        try {
            app(WebPushService::class)->sendToUser(
                userId: $notification->user_id,
                title:  $notification->title,
                body:   $notification->description,
                data:   array_merge(
                    $notification->metadata ?? [],
                    ['type' => $notification->type]
                )
            );
        } catch (\Exception $e) {
            \Log::error('WebPush send failed: ' . $e->getMessage());
        }
    }
}
