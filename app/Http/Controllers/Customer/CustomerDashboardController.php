<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Inertia\Inertia;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Delivery;
use App\Models\Service;

class CustomerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $serviceId = $request->query('service_id');
        $user = $request->user();

        $selectedService = null;
        if ($serviceId) {
            $selectedService = \App\Models\Service::find($serviceId);
        }

        // Active Order
        $activeOrderData = $user->orders()
            ->with(['service', 'orderItems'])
            ->whereIn('status', ['pending', 'diproses'])
            ->latest()
            ->first();

        $activeOrder = null;
        if ($activeOrderData) {
            $progress = $activeOrderData->status == 'pending' ? 10 : 50;
            $itemsCount = $activeOrderData->orderItems->sum('qty') ?? 0;
            $eta = $activeOrderData->created_at->addDays(2)->format('d M, H:i');
            
            $activeOrder = [
                'id' => 'ORD-' . $activeOrderData->id,
                'service' => $activeOrderData->service->name ?? '-',
                'weight' => $itemsCount . ' Item/Kg',
                'status' => ucfirst($activeOrderData->status),
                'progress' => $progress,
                'eta' => $eta
            ];
        }

        // Pending Bill
        $pendingOrderData = $user->orders()
            ->whereHas('payments', function ($query) {
                $query->where('status', 'pending');
            })
            ->latest()
            ->first();

        $pendingBill = null;
        if ($pendingOrderData) {
            $pendingBill = [
                'id' => 'ORD-' . $pendingOrderData->id,
                'total' => $pendingOrderData->total_price,
            ];
        }

        // Recent Orders
        $recentOrdersData = $user->orders()
            ->with('service')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => 'ORD-' . $order->id,
                    'service' => $order->service->name ?? '-',
                    'date' => $order->created_at->format('d M'),
                    'status' => ucfirst($order->status),
                ];
            });

        $totalLaundry = $user->orders()->count();
        $poinReward = $totalLaundry * 50;

        return Inertia::render('dashboard/pelanggan/pelanggan', [
            'auth' => [
                'user' => $user,
            ],
            'selectedServiceId' => $serviceId,
            'selectedService' => $selectedService,
            'activeOrder' => $activeOrder,
            'pendingBill' => $pendingBill,
            'recentOrders' => $recentOrdersData,
            'stats' => [
                'totalLaundry' => $totalLaundry,
                'poinReward' => $poinReward,
            ]
        ]);
    }

    public function aktivitas(Request $request)
    {
        $user = $request->user();
        $dateFilter = $request->query('date', 'semua');

        $query = $user->orders()->with(['service', 'orderItems'])->latest();

        // Apply date filter
        if ($dateFilter === 'hari_ini') {
            $query->whereDate('created_at', today());
        } elseif ($dateFilter === 'minggu_ini') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($dateFilter === 'bulan_ini') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        // Map status DB -> display label & stepper
        $statusMap = [
            'pending'  => ['label' => 'Diterima',   'step' => 'Diterima',    'type' => 'aktif'],
            'diproses' => ['label' => 'Dicuci',     'step' => 'Dicuci',      'type' => 'aktif'],
            'selesai'  => ['label' => 'Selesai',    'step' => 'Selesai',     'type' => 'riwayat'],
            'diantar'  => ['label' => 'Diantar',    'step' => 'Diantar',     'type' => 'riwayat'],
        ];

        $orders = $query->get()->map(function ($order) use ($statusMap) {
            $info   = $statusMap[$order->status] ?? ['label' => ucfirst($order->status), 'step' => 'Diterima', 'type' => 'aktif'];
            $qty    = $order->orderItems->sum('qty');

            return [
                'id'      => 'ORD-' . $order->id,
                'dbId'    => $order->id,
                'service' => $order->service->name ?? '-',
                'date'    => $order->created_at->translatedFormat('d M Y, H:i'),
                'status'  => $info['step'],
                'items'   => $qty . ' item',
                'price'   => (float) $order->total_price,
                'type'    => $info['type'],
            ];
        });

        return Inertia::render('dashboard/pelanggan/aktivitas', [
            'auth'        => ['user' => $user],
            'orders'      => $orders,
            'dateFilter'  => $dateFilter,
        ]);
    }

    public function pembayaran(Request $request)
    {
        return Inertia::render('dashboard/pelanggan/pembayaran', [
            'auth' => [
                'user' => $request->user(),
            ],
        ]);
    }

    public function detailAktivitas(Request $request, $id)
    {
        $order = Order::with(['service', 'orderItems', 'payments'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $steps = ['Diterima', 'Dicuci', 'Dikeringkan', 'Selesai', 'Diantar'];
        $statusMap = [
            'pending'  => 'Diterima',
            'diproses' => 'Dicuci',
            'selesai'  => 'Selesai',
            'diantar'  => 'Diantar',
        ];

        $payment       = $order->payments->first();
        $paymentStatus = $payment && $payment->status === 'paid' ? 'PAID' : 'UNPAID';
        $methodLabel   = ['cash' => 'Tunai', 'transfer' => 'Transfer Bank', 'e-wallet' => 'E-Wallet / QRIS'];
        $qty           = $order->orderItems->sum('qty');
        $servicePrice  = (float) $order->orderItems->sum(fn($i) => $i->price * $i->qty);
        $fee           = (float) $order->total_price - $servicePrice;

        return Inertia::render('dashboard/pelanggan/partials/detail-order', [
            'auth'  => ['user' => $request->user()],
            'order' => [
                'id'            => 'ORD-' . $order->id,
                'dbId'          => $order->id,
                'service'       => $order->service->name ?? '-',
                'items'         => $qty . ' kg',
                'price'         => $servicePrice,
                'fee'           => max($fee, 0),
                'total'         => (float) $order->total_price,
                'date'          => $order->created_at->translatedFormat('d M Y, H:i'),
                'status'        => $statusMap[$order->status] ?? 'Diterima',
                'paymentStatus' => $paymentStatus,
                'paymentMethod' => $methodLabel[$payment?->method ?? 'transfer'] ?? 'Transfer Bank',
                'invoice'       => 'INV-' . $order->created_at->format('Ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                'pickup_address' => $order->pickup_address,
            ],
        ]);
    }

    public function ulasanAktivitas(Request $request, $id)
    {
        return Inertia::render('dashboard/pelanggan/partials/ulasan', [
            'auth' => [
                'user' => $request->user(),
            ],
            'orderId' => $id
        ]);
    }

    public function buatPesanan(Request $request, Service $service)
    {
        return Inertia::render('dashboard/pelanggan/buat-pesanan', [
            'auth'    => ['user' => $request->user()],
            'service' => [
                'id'          => $service->id,
                'name'        => $service->name,
                'description' => $service->description,
                'price'       => (float) $service->price,
                'unit'        => $service->unit ?? '/kg',
                'icon'        => $service->icon,
            ],
        ]);
    }

    public function simpanPesanan(Request $request)
    {
        $validated = $request->validate([
            'service_id'      => 'required|exists:services,id',
            'kg'              => 'required|numeric|min:1|max:100',
            'delivery_type'   => 'required|in:antar_jemput,jemput,antar',
            'pickup_address'  => 'required|string|max:500',
            'payment_method'  => 'required|in:transfer,e-wallet',
        ]);

        $user    = $request->user();
        $service = Service::findOrFail($validated['service_id']);

        $servicePrice = (float) $service->price * (float) $validated['kg'];
        // Fee: antar_jemput +10k, jemput/antar +5k
        $deliveryFee  = match($validated['delivery_type']) {
            'antar_jemput' => 10000,
            default        => 5000,
        };
        $totalPrice   = $servicePrice + $deliveryFee;

        // 1. Create Order
        $order = Order::create([
            'user_id'          => $user->id,
            'service_id'       => $service->id,
            'status'           => 'pending',
            'total_price'      => $totalPrice,
            'pickup_address'   => $validated['pickup_address'],
            'delivery_address' => $user->address ?? $validated['pickup_address'],
        ]);

        // 2. Create Order Item
        OrderItem::create([
            'order_id'  => $order->id,
            'item_name' => $validated['kg'] . ' kg - ' . $service->name,
            'qty'       => (int) $validated['kg'],
            'price'     => $service->price,
        ]);

        // 3. Create Payment (pending until user confirms)
        Payment::create([
            'order_id' => $order->id,
            'amount'   => $totalPrice,
            'method'   => $validated['payment_method'],
            'status'   => 'pending',
        ]);

        // 4. Create Delivery records based on type
        if (in_array($validated['delivery_type'], ['antar_jemput', 'jemput'])) {
            Delivery::create([
                'order_id'   => $order->id,
                'courier_id' => null,
                'status'     => 'dijemput',
                'type'       => 'pickup',
            ]);
        }
        if (in_array($validated['delivery_type'], ['antar_jemput', 'antar'])) {
            Delivery::create([
                'order_id'   => $order->id,
                'courier_id' => null,
                'status'     => 'diantar',
                'type'       => 'delivery',
            ]);
        }

        return redirect()->route('pelanggan.aktivitas.detail', $order->id)
            ->with('success', 'Pesanan berhasil dibuat! Silakan selesaikan pembayaran.');
    }

    public function konfirmasiBayar(Request $request, $id)
    {
        $order = Order::with('payments')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $payment = $order->payments()->where('status', 'pending')->first();

        if (! $payment) {
            return back()->with('error', 'Tidak ada tagihan yang pending untuk pesanan ini.');
        }

        // Mark payment as paid immediately (no admin confirmation needed)
        $payment->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        return redirect()->route('pelanggan.aktivitas.detail', $id)
            ->with('success', 'Pembayaran berhasil dikonfirmasi! Pesanan Anda sedang kami proses.');
    }
}
