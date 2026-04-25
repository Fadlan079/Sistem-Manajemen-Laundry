<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\CustomerOrderController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Operator\PickupController;
use App\Http\Controllers\Operator\PengantaranController;
use App\Http\Controllers\Operator\PembayaranController;
use App\Http\Controllers\Operator\PesananMasukController;
use App\Http\Controllers\Operator\OperatorDashboardController;
use App\Http\Controllers\Kurir\KurirDashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\HomeController;

// ─────────────────────────────────────────────
//  Public Routes
// ─────────────────────────────────────────────

Route::get('/', [HomeController::class, 'index'])->name('home');

// ─────────────────────────────────────────────
//  Dashboard Router (redirect sesuai role)
//  Dipakai sebagai fallback route('dashboard')
// ─────────────────────────────────────────────

Route::get('/dashboard', [HomeController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

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
        Route::get('/dashboard', [OperatorDashboardController::class, 'index'])->name('dashboard');

        Route::get('/PesananMasuk', [PesananMasukController::class, 'index'])->name('pesanan.masuk');
        Route::post('/PesananMasuk', [PesananMasukController::class, 'store'])->name('pesanan.masuk.store');
        Route::put('/PesananMasuk/{order}', [PesananMasukController::class, 'update'])->name('pesanan.masuk.update');

        Route::get('/penjemputan', [PickupController::class, 'index'])->name('penjemputan');
        Route::put('/penjemputan/{delivery}/assign', [PickupController::class, 'assignCourier'])->name('penjemputan.assign');
        Route::put('/penjemputan/{delivery}/dijemput', [PickupController::class, 'markAsPickedUp'])->name('penjemputan.dijemput');

        Route::get('/pengantaran', [PengantaranController::class, 'index'])->name('pengantaran');
        Route::put('/pengantaran/{delivery}/assign', [PengantaranController::class, 'assignCourier'])->name('pengantaran.assign');
        Route::put('/pengantaran/{delivery}/diantar', [PengantaranController::class, 'markAsDelivered'])->name('pengantaran.diantar');
        
        Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran');
        Route::put('/pembayaran/{order}', [PembayaranController::class, 'processPayment'])->name('pembayaran.process');

        Route::get('/layanan', [OperatorDashboardController::class, 'layanan'])->name('layanan');
    });

// ─────────────────────────────────────────────
//  Kurir Dashboard
// ─────────────────────────────────────────────

Route::middleware(['auth', 'verified', 'role:kurir'])
    ->prefix('kurir')
    ->name('kurir.')
    ->group(function () {
        Route::get('/dashboard', [KurirDashboardController::class, 'index'])->name('dashboard');
        Route::put('/deliveries/{delivery}/selesai', [KurirDashboardController::class, 'markAsCompleted'])->name('deliveries.selesai');
        
        // Return view for detail order
        Route::get('/pesanan/{id}', [KurirDashboardController::class, 'detail'])->name('detail');
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
        Route::get('/daftar-layanan', [CustomerDashboardController::class, 'daftarLayanan'])->name('daftar-layanan');
        Route::get('/pesan/{service?}', [CustomerDashboardController::class, 'buatPesanan'])->name('pesan');
        Route::post('/pesan', [CustomerDashboardController::class, 'simpanPesanan'])->name('pesan.simpan');
        Route::post('/aktivitas/{id}/bayar', [CustomerDashboardController::class, 'konfirmasiBayar'])->name('aktivitas.bayar');
        Route::post('/aktivitas/{id}/batal', [CustomerDashboardController::class, 'batalkanPesanan'])->name('aktivitas.batal');
        Route::post('/aktivitas/{id}/midtrans/token', [CustomerDashboardController::class, 'getMidtransToken'])->name('aktivitas.midtrans.token');
        Route::post('/aktivitas/{id}/midtrans/callback', [CustomerDashboardController::class, 'midtransCallback'])->name('aktivitas.midtrans.callback');
        Route::post('/aktivitas/{id}/midtrans/status', [CustomerDashboardController::class, 'checkMidtransStatus'])->name('aktivitas.midtrans.status');
        Route::get('/lacak-pesanan', [CustomerDashboardController::class, 'lacakPesanan'])->name('lacak');
        Route::post('/lacak-pesanan', [CustomerDashboardController::class, 'cariPesanan'])->name('lacak.post');

        // Cart
        Route::get('/keranjang', [CartController::class, 'index'])->name('keranjang');
        Route::post('/keranjang/tambah', [CartController::class, 'addToCart'])->name('keranjang.tambah');
        Route::post('/keranjang/checkout', [CartController::class, 'checkout'])->name('keranjang.checkout');
        Route::delete('/keranjang/{id}', [CartController::class, 'remove'])->name('keranjang.hapus');
    });


// ─────────────────────────────────────────────
//  Profile (semua role yang sudah login)
// ─────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');

    // User Addresses
    Route::post('/profile/addresses', [UserAddressController::class, 'store'])->name('profile.addresses.store');
    Route::put('/profile/addresses/{address}', [UserAddressController::class, 'update'])->name('profile.addresses.update');
    Route::delete('/profile/addresses/{address}', [UserAddressController::class, 'destroy'])->name('profile.addresses.destroy');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/api/notifications/latest', [\App\Http\Controllers\NotificationController::class, 'getLatest'])->name('notifications.latest');
    Route::patch('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

// ─────────────────────────────────────────────
//  Public Search API for Services (navbar autocomplete)
// ─────────────────────────────────────────────

Route::get('/search/services', [HomeController::class, 'searchServices'])->name('services.search');

// ─────────────────────────────────────────────
//  TEMP: Midtrans Test Seed (local only)
// ─────────────────────────────────────────────
Route::get('/test-midtrans-seed', function () {
    if (!app()->isLocal()) abort(403);
    $user = \App\Models\User::where('email', 'pelanggan@test.com')->first();
    if (!$user) return 'User not found';
    $order = \App\Models\Order::where('user_id', $user->id)->latest()->first();
    if (!$order) return 'No order found';
    $order->update(['total_price' => 35000]);
    \App\Models\OrderItem::where('order_id', $order->id)->update(['qty' => 5]);
    return "Done! Order #{$order->id} simulated. Total=35000, Qty=5kg. Visit: /pelanggan/aktivitas/{$order->id}";
});

require __DIR__.'/auth.php';
