<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications for the full page.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $type = $request->query('type', 'semua');

        $this->ensureDailyPromoNotification($user);

        $query = Notification::where('user_id', $user->id)
            ->when($type !== 'semua', function ($q) use ($type) {
                return $q->where('type', $type);
            })
            ->latest();

        $notifications = $query->paginate(15)->withQueryString();
        
        $collection = $notifications->getCollection();

        // Inject unverified email notification on first page
        if ($user && !$user->hasVerifiedEmail() && $notifications->currentPage() === 1 && ($type === 'semua' || $type === 'system')) {
            $unverifiedNotif = new \stdClass();
            $unverifiedNotif->id = 'unverified-email';
            $unverifiedNotif->type = 'system';
            $unverifiedNotif->title = 'Set Up Your Account';
            $unverifiedNotif->description = 'Verifikasi email Anda untuk membuka semua fitur aplikasi.';
            $unverifiedNotif->created_at = now();
            $unverifiedNotif->read_at = null;
            $unverifiedNotif->metadata = ['action' => 'verify_email'];
            
            $collection->prepend($unverifiedNotif);
        }

        // Group by date
        $grouped = $collection->groupBy(function($notif) {
            $date = $notif->created_at;
            if ($date->isToday()) return 'Hari ini';
            if ($date->isYesterday()) return 'Kemarin';
            return 'Sebelumnya';
        });

        // Replace collection with grouped data for the view
        $notifications->setCollection($grouped->map(function($items, $key) {
            return [
                'label' => $key,
                'items' => $items
            ];
        })->values());

        return Inertia::render('dashboard/pelanggan/notifikasi', [
            'notifications' => $notifications,
            'filters' => [
                'type' => $type
            ]
        ]);
    }

    /**
     * Get the latest 5 notifications for the dropdown.
     */
    public function getLatest(Request $request)
    {
        $user = $request->user();
        $this->ensureDailyPromoNotification($user);
        
        $latest = Notification::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $unreadCount = Notification::where('user_id', $user->id)
            ->unread()
            ->count();

        // Inject unverified email notification if needed
        if ($user && !$user->hasVerifiedEmail()) {
            $unverifiedNotif = [
                'id' => 'unverified-email',
                'type' => 'system',
                'title' => 'Set Up Your Account',
                'description' => 'Verifikasi email Anda untuk membuka semua fitur aplikasi.',
                'created_at' => now()->toISOString(),
                'read_at' => null,
                'metadata' => ['action' => 'verify_email']
            ];
            
            $latest->prepend($unverifiedNotif);
            $unreadCount++;
            
            // limit to 5 again if it exceeded
            if ($latest->count() > 5) {
                $latest->pop();
            }
        }

        return response()->json([
            'notifications' => $latest,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead($id)
    {
        if ($id === 'unverified-email') {
            return back();
        }

        $notification = Notification::where('user_id', auth()->id())
            ->findOrFail($id);

        $notification->markAsRead();

        return back();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->unread()
            ->update(['read_at' => now()]);

        return back();
    }

    /**
     * Ensure a promo notification exists for today's discounts.
     */
    private function ensureDailyPromoNotification($user)
    {
        if (!$user) return;

        $today = Carbon::today();
        $dayName = $today->format('l'); // Monday, etc.

        // Check if we already created a promo notif for this user today
        $exists = Notification::where('user_id', $user->id)
            ->where('type', 'promo')
            ->where('title', 'like', 'Promo ' . $dayName . '%')
            ->whereDate('created_at', $today)
            ->exists();

        if ($exists) return;

        // Get services with discount today
        $services = \App\Models\Service::where('discount_day', $dayName)
            ->where('discount_percentage', '>', 0)
            ->get();

        if ($services->isEmpty()) return;

        $promoText = "Diskon spesial hari ini: ";
        $details = $services->map(function($s) {
            return $s->name . " (" . number_format($s->discount_percentage, 0) . "%)";
        })->join(', ');

        Notification::create([
            'user_id' => $user->id,
            'type' => 'promo',
            'title' => 'Promo ' . $dayName . '!',
            'description' => $promoText . $details . ". Yuk, pesan sekarang sebelum promo berakhir!",
            'metadata' => [
                'type' => 'daily_promo',
                'day' => $dayName,
                'services_count' => $services->count()
            ]
        ]);
    }
}
