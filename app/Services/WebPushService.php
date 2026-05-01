<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class WebPushService
{
    protected ?WebPush $webPush = null;

    public function __construct()
    {
        $publicKey  = config('services.vapid.public_key');
        $privateKey = config('services.vapid.private_key');
        $subject    = config('services.vapid.subject');

        // Skip init if VAPID keys are not configured
        if (!$publicKey || !$privateKey) {
            return;
        }

        try {
            $auth = [
                'VAPID' => [
                    'subject'    => $subject,
                    'publicKey'  => $publicKey,
                    'privateKey' => $privateKey,
                ],
            ];
            $this->webPush = new WebPush($auth);
            $this->webPush->setReuseVAPIDHeaders(true);
        } catch (\Throwable $e) {
            \Log::warning('[WebPush] Init failed: ' . $e->getMessage());
            $this->webPush = null;
        }
    }

    /**
     * Send a push notification to all subscriptions of a given user.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        // Skip if WebPush is not configured
        if (!$this->webPush) return;

        try {
            $subscriptions = PushSubscription::where('user_id', $userId)->get();
        } catch (\Throwable $e) {
            // Table may not exist yet in production (migration not run)
            \Log::warning('[WebPush] Cannot query push_subscriptions: ' . $e->getMessage());
            return;
        }

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
                    'endpoint' => $sub->endpoint,
                    'keys' => [
                        'p256dh' => $sub->p256dh_key,
                        'auth'   => $sub->auth_token,
                    ],
                ]);

                $this->webPush->queueNotification($subscription, $payload);
            } catch (\Throwable $e) {
                \Log::warning('[WebPush] Subscription invalid, removing: ' . $e->getMessage());
                $sub->delete();
            }
        }

        // Send all queued and clean up expired/invalid
        try {
            foreach ($this->webPush->flush() as $report) {
                if (!$report->isSuccess()) {
                    $statusCode = $report->getResponse()?->getStatusCode();
                    if (in_array($statusCode, [404, 410])) {
                        PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error('[WebPush] Flush error: ' . $e->getMessage());
        }
    }
}
