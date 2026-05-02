<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Service;
use App\Models\Review;
use App\Models\User;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Display the landing page with services, banners, and reviews.
     */
    public function index()
    {
        // Compute review stats
        $reviews = Review::with(['user', 'service'])
            ->latest()
            ->get();

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

        $ratingStats = collect([5, 4, 3, 2, 1])->map(function ($star) use ($reviews, $totalReviews) {
            $count = $reviews->where('rating', $star)->count();
            $pct = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
            return ['stars' => $star, 'count' => $count, 'percentage' => $pct . '%'];
        })->values();

        $reviewList = $reviews->take(12)->map(fn($r) => [
            'id' => $r->id,
            'name' => $r->user->name ?? 'Anonim',
            'initials' => implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', trim($r->user->name ?? 'A')), 0, 2))),
            'rating' => $r->rating,
            'comment' => $r->comment,
            'service' => $r->service->name ?? '-',
            'created_at' => $r->created_at->diffForHumans(),
        ]);

        $totalOrders = Order::where('status', 'selesai')->count();
        $totalCustomers = User::where('role', 'pelanggan')->count();
        $happyCustomerPct = $totalReviews > 0
            ? round(($reviews->where('rating', '>=', 4)->count() / $totalReviews) * 100)
            : 100;

        return Inertia::render('home', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'serviceList' => Service::with(['serviceCategory'])->withCount(['orders', 'reviews'])
                ->withAvg('reviews', 'rating')
                ->where('status', 'tersedia')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'category' => $s->serviceCategory->name ?? '-',
                    'price' => $s->price,
                    'estimate' => $s->estimate,
                    'description' => $s->description,
                    'image_url' => $s->image ? asset('storage/' . $s->image) : null,
                    'features' => $s->features,
                    'unit' => $s->unit,
                    'tag' => $s->tag,
                    'is_discount_today' => $s->is_discount_today,
                    'discounted_price' => $s->discounted_price,
                    'discount_percentage' => $s->discount_percentage,
                    'orders_count' => $s->orders_count,
                    'rating' => round($s->reviews_avg_rating ?? 0, 1),
                    'reviews_count' => $s->reviews_count,
                ]),
            'banners' => Banner::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($b) => [
                    'id' => $b->id,
                    'image_url' => asset('storage/' . $b->image),
                    'is_active' => $b->is_active,
                ]),
            'reviews' => $reviewList,
            'averageRating' => $averageRating,
            'totalReviews' => $totalReviews,
            'ratingStats' => $ratingStats,
            'stats' => [
                'totalOrders' => $totalOrders,
                'totalCustomers' => $totalCustomers,
                'happyCustomerPct' => $happyCustomerPct,
            ]
        ]);
    }

    /**
     * Redirect users to their respective dashboards based on roles.
     */
    public function dashboard(Request $request)
    {
        $role = auth()->user()->role;
        $query = $request->query();

        return match ($role) {
            'admin' => redirect()->route('admin.dashboard', $query),
            'operator' => redirect()->route('operator.dashboard', $query),
            'kurir' => redirect()->route('kurir.dashboard', $query),
            default => redirect()->route('pelanggan.aktivitas', $query),
        };
    }

    /**
     * Search services and orders for navbar autocomplete.
     */
    public function searchServices(Request $request)
    {
        $q = trim($request->query('q', ''));

        if (strlen($q) < 1) {
            return response()->json([]);
        }

        // 1. Search Services
        $services = Service::with(['serviceCategory'])->where('status', 'tersedia')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhereHas('serviceCategory', fn($sq) => $sq->where('name', 'like', "%{$q}%"));
            })
            ->select('id', 'name', 'category_id', 'price', 'unit', 'image')
            ->limit(6)
            ->get()
            ->map(fn($s) => [
                'type' => 'service',
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->serviceCategory->name ?? '-',
                'price' => $s->price,
                'unit' => $s->unit,
                'image_url' => $s->image ? asset('storage/' . $s->image) : null,
            ]);

        // 2. Search Orders (if logged in & query indicates invoice/number)
        $orders = collect();
        if (auth()->check()) {
            $orderId = null;
            if (stripos($q, 'inv') !== false) {
                $parts = explode('-', $q);
                if (count($parts) >= 3) {
                    $orderId = (int) end($parts);
                } else {
                    $orderId = (int) preg_replace('/[^0-9]/', '', $q);
                }
            } elseif (is_numeric($q)) {
                $orderId = (int) $q;
            }

            if ($orderId > 0) {
                $orders = Order::with('service')
                    ->where('user_id', auth()->id())
                    ->where('id', 'like', "%{$orderId}%")
                    ->limit(3)
                    ->get()
                    ->map(function ($o) {
                        $invoice = 'INV-' . $o->created_at->format('Ymd') . '-' . str_pad($o->id, 4, '0', STR_PAD_LEFT);
                        return [
                            'type' => 'order',
                            'id' => $o->id,
                            'name' => 'Pesanan ' . ($o->service->name ?? ''),
                            'invoice' => '#' . $invoice,
                            'status' => ucfirst($o->status),
                            'date' => $o->created_at->format('d M Y'),
                        ];
                    });
            }
        }

        return response()->json($orders->merge($services)->take(8));
    }
}
