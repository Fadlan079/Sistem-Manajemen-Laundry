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
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─────────────────────────────────────────────
//  Public Routes
// ─────────────────────────────────────────────

Route::get('/', function () {
    return Inertia::render('home', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
        'serviceList' => \App\Models\Service::where('status', 'tersedia')->get(),
        'banners'     => \App\Models\Banner::where('is_active', true)
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->map(fn($b) => [
                                'id'        => $b->id,
                                'image_url' => asset('storage/' . $b->image),
                                'is_active' => $b->is_active,
                            ]),
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
        Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('pelanggan');
        Route::get('/aktivitas', [CustomerDashboardController::class, 'aktivitas'])->name('aktivitas');
        Route::get('/aktivitas/{id}', [CustomerDashboardController::class, 'detailAktivitas'])->name('aktivitas.detail');
        Route::get('/aktivitas/{id}/ulasan', [CustomerDashboardController::class, 'ulasanAktivitas'])->name('aktivitas.ulasan');
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

require __DIR__.'/auth.php';
