<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\Operator\PickupController;
use App\Http\Controllers\Operator\PengantaranController;
use App\Http\Controllers\Operator\PembayaranController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─────────────────────────────────────────────
//  Public Routes
// ─────────────────────────────────────────────

Route::get('/', function () {
    // Compute review stats
    $reviews = \App\Models\Review::with(['user', 'service'])
        ->latest()
        ->get();

    $totalReviews = $reviews->count();
    $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

    $ratingStats = collect([5, 4, 3, 2, 1])->map(function ($star) use ($reviews, $totalReviews) {
        $count = $reviews->where('rating', $star)->count();
        $pct   = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
        return ['stars' => $star, 'count' => $count, 'percentage' => $pct . '%'];
    })->values();

    $reviewList = $reviews->take(12)->map(fn($r) => [
        'id'          => $r->id,
        'name'        => $r->user->name ?? 'Anonim',
        'initials'    => implode('', array_map(fn($w) => strtoupper($w[0]), array_slice(explode(' ', trim($r->user->name ?? 'A')), 0, 2))),
        'rating'      => $r->rating,
        'comment'     => $r->comment,
        'service'     => $r->service->name ?? '-',
        'created_at'  => $r->created_at->diffForHumans(),
    ]);

    return Inertia::render('home', [
        'canLogin'      => Route::has('login'),
        'canRegister'   => Route::has('register'),
        'serviceList'   => \App\Models\Service::where('status', 'tersedia')->get()->map(fn($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'category'    => $s->category,
            'price'       => $s->price,
            'estimate'    => $s->estimate,
            'description' => $s->description,
            'image_url'   => $s->image ? asset('storage/' . $s->image) : null,
            'features'    => $s->features,
            'unit'        => $s->unit,
            'tag'         => $s->tag,
        ]),
        'banners'       => \App\Models\Banner::where('is_active', true)
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->map(fn($b) => [
                                'id'        => $b->id,
                                'image_url' => asset('storage/' . $b->image),
                                'is_active' => $b->is_active,
                            ]),
        'reviews'       => $reviewList,
        'averageRating' => $averageRating,
        'totalReviews'  => $totalReviews,
        'ratingStats'   => $ratingStats,
    ]);
})->name('home');


// ─────────────────────────────────────────────
//  Dashboard Router (redirect sesuai role)
//  Dipakai sebagai fallback route('dashboard')
// ─────────────────────────────────────────────

Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    $role = auth()->user()->role;
    $query = $request->query();

    return match ($role) {
        'admin'    => redirect()->route('admin.dashboard', $query),
        'operator' => redirect()->route('operator.dashboard', $query),
        'kurir'    => redirect()->route('kurir.dashboard', $query),
        default    => redirect()->route('pelanggan.pelanggan', $query),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

// ─────────────────────────────────────────────
//  Admin Dashboard
// ─────────────────────────────────────────────

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/manajemen-users',          [UserController::class, 'index'])->name('users');
        Route::post('/manajemen-users',           [UserController::class, 'store'])->name('users.store');
        Route::put('/manajemen-users/{user}',     [UserController::class, 'update'])->name('users.update');
        Route::delete('/manajemen-users/{user}',  [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/manajemen-order',           [OrderController::class, 'index'])->name('orders');
        Route::post('/manajemen-order',            [OrderController::class, 'store'])->name('orders.store');
        Route::put('/manajemen-order/{order}',     [OrderController::class, 'update'])->name('orders.update');
        Route::delete('/manajemen-order/{order}',  [OrderController::class, 'destroy'])->name('orders.destroy');

        Route::get('/layanan-laundry',           [ServiceController::class, 'index'])->name('services');
        Route::post('/layanan-laundry',            [ServiceController::class, 'store'])->name('services.store');
        Route::put('/layanan-laundry/{service}',   [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/layanan-laundry/{service}',[ServiceController::class, 'destroy'])->name('services.destroy');

        Route::get('/pickup-delivery',              [DeliveryController::class, 'index'])->name('pickup');
        Route::post('/pickup-delivery',              [DeliveryController::class, 'store'])->name('pickup.store');
        Route::put('/pickup-delivery/{delivery}',   [DeliveryController::class, 'update'])->name('pickup.update');
        Route::delete('/pickup-delivery/{delivery}',[DeliveryController::class, 'destroy'])->name('pickup.destroy');

        // Banners
        Route::post('/banners',            [BannerController::class, 'store'])->name('banners.store');
        Route::put('/banners/{banner}',    [BannerController::class, 'update'])->name('banners.update');
        Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');
    });


// ─────────────────────────────────────────────
//  Operator Dashboard
// ─────────────────────────────────────────────

Route::middleware(['auth', 'verified', 'role:operator'])
    ->prefix('operator')
    ->name('operator.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('dashboard/operator/operator');
        })->name('dashboard');

        Route::get('/PesananMasuk', function () {
            return Inertia::render('dashboard/operator/PesananMasuk');
        })->name('pesanan.masuk');

        Route::get('/penjemputan', [PickupController::class, 'index'])->name('penjemputan');
        Route::put('/penjemputan/{delivery}/assign', [PickupController::class, 'assignCourier'])->name('penjemputan.assign');
        Route::put('/penjemputan/{delivery}/dijemput', [PickupController::class, 'markAsPickedUp'])->name('penjemputan.dijemput');

        Route::get('/pengantaran', [PengantaranController::class, 'index'])->name('pengantaran');
        Route::put('/pengantaran/{delivery}/assign', [PengantaranController::class, 'assignCourier'])->name('pengantaran.assign');
        Route::put('/pengantaran/{delivery}/diantar', [PengantaranController::class, 'markAsDelivered'])->name('pengantaran.diantar');
        
        Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran');
        Route::put('/pembayaran/{order}', [PembayaranController::class, 'processPayment'])->name('pembayaran.process');

        Route::get('/layanan', function () {
            return Inertia::render('dashboard/operator/layanan');
        })->name('layanan');
    });

// ─────────────────────────────────────────────
//  Kurir Dashboard
// ─────────────────────────────────────────────

Route::middleware(['auth', 'verified', 'role:kurir'])
    ->prefix('kurir')
    ->name('kurir.')
    ->group(function () {
        // Route ini akan menangani baik Overview maupun Tugas Jemput via query string (?tab=tugas)
        Route::get('/dashboard', function () {
            return Inertia::render('dashboard/kurir/kurir');
        })->name('dashboard');
    });


// ─────────────────────────────────────────────
//  Pelanggan Dashboard
// ─────────────────────────────────────────────

Route::middleware(['auth', 'verified'])
    ->prefix('pelanggan')
    ->name('pelanggan.')
    ->group(function () {
        Route::get('/aktivitas', [CustomerDashboardController::class, 'aktivitas'])->name('aktivitas');
        Route::get('/aktivitas/{id}', [CustomerDashboardController::class, 'detailAktivitas'])->name('aktivitas.detail');
        Route::get('/aktivitas/{id}/ulasan', [CustomerDashboardController::class, 'ulasanAktivitas'])->name('aktivitas.ulasan');
        Route::post('/aktivitas/{id}/ulasan', [CustomerDashboardController::class, 'simpanUlasan'])->name('aktivitas.ulasan.post');
        Route::get('/pembayaran', [CustomerDashboardController::class, 'pembayaran'])->name('pembayaran');
        Route::get('/pesan/{service?}', [CustomerDashboardController::class, 'buatPesanan'])->name('pesan');
        Route::post('/pesan', [CustomerDashboardController::class, 'simpanPesanan'])->name('pesan.simpan');
        Route::post('/aktivitas/{id}/bayar', [CustomerDashboardController::class, 'konfirmasiBayar'])->name('aktivitas.bayar');
        Route::post('/aktivitas/{id}/batal', [CustomerDashboardController::class, 'batalkanPesanan'])->name('aktivitas.batal');
        Route::get('/lacak-pesanan', [CustomerDashboardController::class, 'lacakPesanan'])->name('lacak');
        Route::post('/lacak-pesanan', [CustomerDashboardController::class, 'cariPesanan'])->name('lacak.post');
    });


// ─────────────────────────────────────────────
//  Profile (semua role yang sudah login)
// ─────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─────────────────────────────────────────────
//  Public Search API for Services (navbar autocomplete)
// ─────────────────────────────────────────────

Route::get('/search/services', function (\Illuminate\Http\Request $request) {
    $q = trim($request->query('q', ''));

    if (strlen($q) < 1) {
        return response()->json([]);
    }

    // 1. Search Services
    $services = \App\Models\Service::where('status', 'tersedia')
        ->where(function ($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('category', 'like', "%{$q}%");
        })
        ->select('id', 'name', 'category', 'price', 'unit', 'image')
        ->limit(6)
        ->get()
        ->map(fn($s) => [
            'type'      => 'service',
            'id'        => $s->id,
            'name'      => $s->name,
            'category'  => $s->category,
            'price'     => $s->price,
            'unit'      => $s->unit,
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

        // Jika menemukan angka dari query, lakukan pencarian order id
        if ($orderId > 0) {
            $orders = \App\Models\Order::with('service')
                ->where('user_id', auth()->id())
                ->where('id', 'like', "%{$orderId}%")
                ->limit(3)
                ->get()
                ->map(function($o) {
                    $invoice = 'INV-' . $o->created_at->format('Ymd') . '-' . str_pad($o->id, 4, '0', STR_PAD_LEFT);
                    return [
                        'type'    => 'order',
                        'id'      => $o->id,
                        'name'    => 'Pesanan ' . ($o->service->name ?? ''),
                        'invoice' => '#' . $invoice,
                        'status'  => ucfirst($o->status),
                        'date'    => $o->created_at->format('d M Y'),
                    ];
                });
        }
    }

    // Return combined results: orders first, then services
    $results = $orders->merge($services)->take(8);

    return response()->json($results);
})->name('services.search');

require __DIR__.'/auth.php';
