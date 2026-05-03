<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Delivery;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OperatorDashboardController extends Controller
{
    public function index()
    {
        $today     = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisMonth = Carbon::now()->startOfMonth();

        // ── Pesanan Stats ──────────────────────────────────────────
        $pesanan = [
            'antrian'        => Order::whereIn('status', ['dibuat', 'antri'])->count(),
            'diproses'       => Order::where('status', 'diproses')->count(),
            'selesai_hari_ini' => Order::where('status', 'selesai')->whereDate('updated_at', $today)->count(),
            'total_bulan_ini'  => Order::whereDate('created_at', '>=', $thisMonth)->count(),
            'total_hari_ini'   => Order::whereDate('created_at', $today)->count(),
            'total_kemarin'    => Order::whereDate('created_at', $yesterday)->count(),
        ];

        // ── Penjemputan Stats ──────────────────────────────────────
        $lateThreshold = Carbon::now()->subHours(12);
        $pickup = [
            'belum_assign'  => Delivery::where('type', 'pickup')->whereNotIn('status', ['selesai', 'pending'])
                                ->whereNull('courier_id')->whereNull('external_courier_name')->count(),
            'terlambat'     => Delivery::where('type', 'pickup')->whereNotIn('status', ['selesai', 'pending'])
                                ->whereHas('order', fn($q) => $q->where('created_at', '<', $lateThreshold))->count(),
            'hari_ini'      => Delivery::where('type', 'pickup')->whereDate('created_at', $today)->count(),
        ];

        // ── Pengantaran Stats ──────────────────────────────────────
        $delivery = [
            'belum_assign'  => Delivery::where('type', 'delivery')->whereNotIn('status', ['selesai', 'pending'])
                                ->whereNull('courier_id')->whereNull('external_courier_name')->count(),
            'terlambat'     => Delivery::where('type', 'delivery')->whereNotIn('status', ['selesai', 'pending'])
                                ->whereHas('order', fn($q) => $q->where('created_at', '<', $lateThreshold))->count(),
            'hari_ini'      => Delivery::where('type', 'delivery')->whereDate('created_at', $today)->count(),
        ];

        // ── Pembayaran Stats ──────────────────────────────────────
        $pembayaran = [
            'belum_lunas'   => Order::whereHas('payments', fn($q) => $q->where('status', 'pending'))->count(),
            'tunai_cod'     => Order::whereHas('payments', fn($q) => $q->where('status', 'pending')->whereIn('method', ['cash', 'cod']))->count(),
            'lunas_hari_ini' => Order::whereHas('payments', fn($q) => $q->where('status', 'paid')->whereDate('paid_at', $today))->count(),
            'pendapatan_hari_ini' => Payment::where('status', 'paid')->whereDate('paid_at', $today)->sum('amount'),
            'pendapatan_bulan_ini' => Payment::where('status', 'paid')->whereDate('paid_at', '>=', $thisMonth)->sum('amount'),
        ];

        // ── Aktivitas Terbaru (last 5 orders) ─────────────────────
        $recentOrders = Order::with(['user', 'service', 'payments'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($o) => [
                'id'       => $o->id,
                'invoice'  => 'INV-' . $o->created_at->format('Ymd') . '-' . str_pad($o->id, 4, '0', STR_PAD_LEFT),
                'customer' => $o->user?->name ?? '-',
                'service'  => $o->service?->name ?? '-',
                'status'   => $o->status,
                'total_price'    => (float) $o->total_price,
                'date'     => $o->created_at->format('d M, H:i'),
                'payment_status' => $o->payments->first()?->status ?? 'pending',
            ]);

        // ── Antrian Penjemputan Mendesak (unassigned pickups) ─────
        $urgentPickups = Delivery::with(['order.user'])
            ->where('type', 'pickup')
            ->whereNotIn('status', ['selesai', 'pending'])
            ->whereNull('courier_id')
            ->whereNull('external_courier_name')
            ->orderBy('created_at', 'asc')
            ->limit(3)
            ->get()
            ->map(fn($d) => [
                'id'      => $d->id,
                'invoice' => 'INV-' . $d->order->created_at->format('Ymd') . '-' . str_pad($d->order_id, 4, '0', STR_PAD_LEFT),
                'name'    => $d->order->user?->name ?? '-',
                'address' => $d->order->pickup_address ?? '-',
                'time'    => $d->scheduled_at ? $d->scheduled_at->format('H:i') : $d->created_at->format('d M, H:i'),
                'age_hours' => $d->order->created_at->diffInHours(Carbon::now()),
            ]);

        // ── Pembayaran Mendesak (pending belum lunas) ─────────────
        $urgentPayments = Order::with(['user', 'payments'])
            ->whereHas('payments', fn($q) => $q->where('status', 'pending'))
            ->where('total_price', '>', 0)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn($o) => [
                'id'      => $o->id,
                'invoice' => 'INV-' . $o->created_at->format('Ymd') . '-' . str_pad($o->id, 4, '0', STR_PAD_LEFT),
                'name'    => $o->user?->name ?? '-',
                'total_price'   => (float) $o->total_price,
                'method'  => $o->payments->first()?->method ?? '-',
                'date'    => $o->created_at->format('d M'),
            ]);

        // ── Weekly trend (7 hari) ─────────────────────────────────
        $weeklyTrend = collect(range(6, 0))->map(function ($i) {
            $day = Carbon::now()->subDays($i);
            return [
                'label'   => $day->isoFormat('ddd'),
                'pesanan' => Order::whereDate('created_at', $day->toDateString())->count(),
                'selesai' => Order::where('status', 'selesai')->whereDate('updated_at', $day->toDateString())->count(),
            ];
        });

        // ── Status Distribution ───────────────────────────────────
        $statusDist = [
            Order::where('status', 'selesai')->count(),
            Order::where('status', 'diproses')->count(),
            Order::where('status', 'dibuat')->count(),
            Order::where('status', 'diantar')->count(),
            Order::where('status', 'dijemput')->count(),
        ];

        return Inertia::render('dashboard/operator/operator', [
            'pesanan'         => $pesanan,
            'pickup'          => $pickup,
            'delivery'        => $delivery,
            'pembayaran'      => $pembayaran,
            'recentOrders'    => $recentOrders,
            'urgentPickups'   => $urgentPickups,
            'urgentPayments'  => $urgentPayments,
            'weeklyTrend'     => $weeklyTrend,
            'statusDist'      => $statusDist,
        ]);
    }
}
