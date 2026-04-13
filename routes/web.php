<?php

use App\Http\Controllers\ProfileController;
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
    ]);
})->name('home');


// ─────────────────────────────────────────────
//  Dashboard Router (redirect sesuai role)
//  Dipakai sebagai fallback route('dashboard')
// ─────────────────────────────────────────────

Route::get('/dashboard', function () {
    $role = auth()->user()->role;

    return match ($role) {
        'admin'    => redirect()->route('admin.dashboard'),
        'operator' => redirect()->route('operator.dashboard'),
        'kurir'    => redirect()->route('kurir.dashboard'),
        default    => redirect()->route('home'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');


// ─────────────────────────────────────────────
//  Admin Dashboard
// ─────────────────────────────────────────────

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('dashboard/admin/admin');
        })->name('dashboard');

        Route::get('/manajemen-users', function () {
            return Inertia::render('dashboard/admin/manajemen-users');
        })->name('users');
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
        Route::get('/dashboard', function () {
            return Inertia::render('dashboard/kurir/kurir');
        })->name('dashboard');
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
