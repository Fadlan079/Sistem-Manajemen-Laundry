<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class WebPushService
{
    protected WebPush $webPush;

    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject'    => config('services.vapid.subject'),
                'publicKey'  => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];

        $this->webPush = new WebPush($auth);
        $this->webPush->setReuseVAPIDHeaders(true);
    }

    /**
     * Send a push notification to all subscriptions of a given user.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'icon'  => '/favicon.ico',
            'badge' => '/favicon.ico',
            'data'  => $data,
        ]);

        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'keys' => [
                        'p256dh' => $sub->p256dh_key,
                        'auth'   => $sub->auth_token,
                    ],
                ]);

                $this->webPush->queueNotification($subscription, $payload);
            } catch (\Exception $e) {
                \Log::warning('Push subscription invalid, removing: ' . $e->getMessage());
                $sub->delete();
            }
        }

        // Send all queued notifications and clean up expired/invalid ones
        foreach ($this->webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                // Endpoint gone (410) or not found (404) → remove from DB
                if (in_array($report->getResponse()?->getStatusCode(), [404, 410])) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        }
    }
}
