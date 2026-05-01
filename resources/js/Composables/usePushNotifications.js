/**
 * usePushNotifications.js
 *
 * Vue composable to:
 *  1. Register the Service Worker
 *  2. Request push permission from the browser
 *  3. Save the subscription to our Laravel backend
 */

import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

// VAPID public key injected from the Laravel blade/Inertia shared data
// OR you can hard-code it here for simplicity in development
const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY || '';

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

export function usePushNotifications() {
    const isSupported    = ref('serviceWorker' in navigator && 'PushManager' in window);
    const isSubscribed   = ref(false);
    const isLoading      = ref(false);
    const permissionStatus = ref(typeof Notification !== 'undefined' ? Notification.permission : 'denied');

    async function registerServiceWorker() {
        if (!isSupported.value) return null;
        try {
            console.log('[Push] Registering Service Worker...');
            const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
            await navigator.serviceWorker.ready;
            console.log('[Push] Service Worker ready.');
            return registration;
        } catch (err) {
            console.error('[Push] Service Worker registration failed:', err);
            return null;
        }
    }

    async function getExistingSubscription(registration) {
        try {
            return await registration.pushManager.getSubscription();
        } catch {
            return null;
        }
    }

    async function subscribe() {
        console.log('[Push] Starting subscription process...');
        if (!isSupported.value) {
            console.warn('[Push] Browser not supported.');
            return;
        }
        if (!VAPID_PUBLIC_KEY) {
            console.warn('[Push] VAPID public key missing. Check your .env and rebuild (npm run dev).');
            return;
        }

        isLoading.value = true;

        try {
            const registration = await registerServiceWorker();
            if (!registration) {
                console.error('[Push] Could not get SW registration.');
                return;
            }

            // Check existing
            let subscription = await getExistingSubscription(registration);
            console.log('[Push] Existing subscription:', subscription);

            if (!subscription) {
                console.log('[Push] Requesting permission...');
                const permission = await Notification.requestPermission();
                permissionStatus.value = permission;
                console.log('[Push] Permission status:', permission);

                if (permission !== 'granted') {
                    return;
                }

                console.log('[Push] Creating new subscription via PushManager...');
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly:      true,
                    applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
                });
            }

            isSubscribed.value = true;

            // Send subscription to backend
            const subJson = subscription.toJSON();
            await fetch('/push/subscribe', {
                method:  'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    endpoint:    subJson.endpoint,
                    p256dh_key:  subJson.keys?.p256dh  ?? null,
                    auth_token:  subJson.keys?.auth    ?? null,
                }),
            });

            console.log('[Push] Subscribed and saved to server.');
        } catch (err) {
            console.error('[Push] Subscribe error:', err);
        } finally {
            isLoading.value = false;
        }
    }

    async function unsubscribe() {
        isLoading.value = true;
        try {
            const registration   = await navigator.serviceWorker.ready;
            const subscription   = await registration.pushManager.getSubscription();

            if (subscription) {
                await fetch('/push/unsubscribe', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({ endpoint: subscription.endpoint }),
                });
                await subscription.unsubscribe();
                isSubscribed.value = false;
                console.log('[Push] Unsubscribed.');
            }
        } catch (err) {
            console.error('[Push] Unsubscribe error:', err);
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Auto-initialise: register SW + silently re-subscribe if previously granted.
     * Call this once from your dashboard layout's onMounted.
     */
    async function init() {
        if (!isSupported.value) return;

        const registration = await registerServiceWorker();
        if (!registration) return;

        permissionStatus.value = Notification.permission;

        const existing = await getExistingSubscription(registration);
        if (existing) {
            isSubscribed.value = true;
        } else if (Notification.permission === 'granted') {
            // Was previously granted but subscription lost — re-subscribe silently
            await subscribe();
        }
    }

    return { isSupported, isSubscribed, isLoading, permissionStatus, subscribe, unsubscribe, init };
}
