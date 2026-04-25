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

        $query = Notification::where('user_id', $user->id)
            ->when($type !== 'semua', function ($q) use ($type) {
                return $q->where('type', $type);
            })
            ->latest();

        $notifications = $query->paginate(15)->withQueryString();

        // Group by date
        $grouped = $notifications->getCollection()->groupBy(function($notif) {
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
        
        $latest = Notification::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        $unreadCount = Notification::where('user_id', $user->id)
            ->unread()
            ->count();

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
}
