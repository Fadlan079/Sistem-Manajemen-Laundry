// HiWash Service Worker for Web Push Notifications
// Place this file at: public/sw.js

const CACHE_NAME = 'hiwash-v1';

// Install event
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

// Push event — fired when server sends a push
self.addEventListener('push', (event) => {
    let data = {};

    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = {
            title: 'HiWash',
            body: event.data ? event.data.text() : 'Ada notifikasi baru.',
        };
    }

    const title = data.title || 'HiWash Laundry';
    const options = {
        body:    data.body || '',
        icon:    data.icon || '/favicon.ico',
        badge:   data.badge || '/favicon.ico',
        vibrate: [200, 100, 200],
        data:    data.data || {},
        actions: [
            { action: 'open', title: 'Lihat Detail' },
            { action: 'close', title: 'Tutup' },
        ],
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'close') return;

    // Determine target URL from the notification's data
    const orderId = event.notification.data?.order_id;
    const targetUrl = orderId
        ? `/pelanggan/aktivitas/${orderId}`
        : '/pelanggan/aktivitas';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // If app is already open, focus it
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            // Otherwise open a new tab
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
