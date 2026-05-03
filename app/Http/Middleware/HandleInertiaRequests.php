<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $sidebarCounts = [];

        if ($user && $user->role === 'operator') {
            $sidebarCounts = [
                'pesanan' => \App\Models\Order::whereIn('status', ['dibuat', 'antri'])->count(),
                'penjemputan' => \App\Models\Delivery::where('type', 'pickup')
                    ->whereNotIn('status', ['selesai', 'pending'])
                    ->whereNull('courier_id')
                    ->whereNull('external_courier_name')
                    ->count(),
                'pengantaran' => \App\Models\Delivery::where('type', 'delivery')
                    ->whereNotIn('status', ['selesai', 'pending'])
                    ->whereNull('courier_id')
                    ->whereNull('external_courier_name')
                    ->count(),
                'pembayaran' => \App\Models\Payment::where('status', 'pending')->count(),
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'sidebarCounts' => $sidebarCounts,
            'cartCount' => fn() => $user
                ? $user->orders()->where('status', 'cart')->count()
                : 0,
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
            'turnstile_site_key' => config('services.turnstile.site_key'),
        ];
    }
}
