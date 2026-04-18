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
use App\Models\Review;

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

        $query = $user->orders()->with(['service', 'orderItems', 'payments', 'review'])->latest();

        // Apply date filter
        if ($dateFilter === 'hari_ini') {
            $query->whereDate('created_at', today());
        } elseif ($dateFilter === 'minggu_ini') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($dateFilter === 'bulan_ini') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        $orders = $query->get()->map(function ($order) {
            $qty = $order->orderItems->sum('qty');
            $isCalculated = $qty > 0;
            $paymentStatus = $order->payments->first()?->status ?? 'UNPAID';
            
            $invoice = 'INV-' . $order->created_at->format('Ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
            $dbStatus = $order->status;
            
            // Tentukan type (aktif atau riwayat)
            $type = in_array($dbStatus, ['selesai', 'diantar']) ? 'riwayat' : 'aktif';

            // Penentuan UI Badge Status
            if ($dbStatus === 'selesai' || $dbStatus === 'diantar') {
                $badgeText = ucfirst($dbStatus);
                $badgeColor = 'green';
            } elseif (!$isCalculated && $dbStatus === 'pending') {
                $badgeText = 'Menunggu Penjemputan';
                $badgeColor = 'blue';
            } elseif ($isCalculated && $paymentStatus === 'UNPAID') {
                $badgeText = 'Menunggu Pembayaran';
                $badgeColor = 'yellow';
            } else {
                $badgeText = ucfirst($dbStatus);
                $badgeColor = 'blue';
            }

            return [
                'dbId'          => $order->id,
                'invoice'       => '#' . $invoice,
                'service'       => $order->service->name ?? '-',
                'service_image' => $order->service && $order->service->image ? asset('storage/' . $order->service->image) : null,
                'date'          => $order->created_at->translatedFormat('l, d F Y, H:i'),
                'shortDate'   => $order->created_at->translatedFormat('l, d F Y'),
                'dbStatus'    => $dbStatus,
                'isCalculated'=> $isCalculated,
                'paymentStatus'=> $paymentStatus,
                'type'        => $type,
                'badgeText'   => $badgeText,
                'badgeColor'  => $badgeColor,
                'is_reviewed' => $order->review !== null,
            ];
        });

        return Inertia::render('dashboard/pelanggan/aktivitas', [
            'auth'        => ['user' => $user],
            'orders'      => $orders,
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
        $order = Order::with(['service', 'orderItems', 'payments', 'review'])
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
        // Delivery fee is initially the total price. But when updated, total_price is servicePrice + fee
        $fee           = (float) $order->total_price - $servicePrice;

        $hasPickup   = $order->deliveries->where('type', 'pickup')->isNotEmpty();
        $hasDelivery = $order->deliveries->where('type', 'delivery')->isNotEmpty();
        
        $isCalculated = $qty > 0; // If qty > 0, it means it has been updated by courier/admin

        return Inertia::render('dashboard/pelanggan/partials/detail-order', [
            'auth'  => ['user' => $request->user()],
            'order' => [
                'id'            => 'ORD-' . $order->id,
                'dbId'          => $order->id,
                'service'       => $order->service->name ?? '-',
                'service_price' => (float) ($order->service->price ?? 0),
                'items_qty'     => $qty,
                'items'         => $qty . ' kg',
                'price'         => $servicePrice,
                'fee'           => max($fee, 0),
                'total'         => (float) $order->total_price,
                'date'          => $order->created_at->translatedFormat('d M Y, H:i'),
                'status'        => $statusMap[$order->status] ?? 'Diterima',
                'dbStatus'      => $order->status,
                'hasPickup'     => $hasPickup,
                'hasDelivery'   => $hasDelivery,
                'isCalculated'  => $isCalculated,
                'paymentStatus' => $paymentStatus,
                'paymentMethod' => $methodLabel[$payment?->method ?? 'transfer'] ?? 'Transfer Bank',
                'invoice'       => 'INV-' . $order->created_at->format('Ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                'pickup_address' => $order->pickup_address,
                'review'        => $order->review ? [
                    'rating'  => (int) $order->review->rating,
                    'comment' => $order->review->comment,
                    'date'    => $order->review->created_at->translatedFormat('d M Y, H:i'),
                ] : null,
            ],
        ]);
    }

    public function ulasanAktivitas(Request $request, $id)
    {
        $order = \App\Models\Order::with('service')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        // Only selesai orders can be reviewed
        if ($order->status !== 'selesai') {
            return redirect()->route('pelanggan.aktivitas.detail', $id)
                ->with('error', 'Pesanan belum selesai.');
        }

        // Check if already reviewed
        $alreadyReviewed = Review::where('order_id', $id)->exists();

        $invoice = 'INV-' . $order->created_at->format('Ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);

        return Inertia::render('dashboard/pelanggan/partials/ulasan', [
            'auth' => [
                'user' => $request->user(),
            ],
            'alreadyReviewed' => $alreadyReviewed,
            'order' => [
                'dbId'       => $order->id,
                'service_id' => $order->service_id,
                'invoice'    => '#' . $invoice,
                'service'    => $order->service->name ?? '-',
                'date'       => $order->created_at->translatedFormat('l, d F Y')
            ]
        ]);
    }

    public function simpanUlasan(Request $request, $id)
    {
        $request->validate([
            'rating'  => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $order = Order::with('service')
            ->where('user_id', $request->user()->id)
            ->where('status', 'selesai')
            ->findOrFail($id);

        // Prevent duplicate reviews
        if (Review::where('order_id', $order->id)->exists()) {
            return redirect()->route('pelanggan.aktivitas')
                ->with('error', 'Anda sudah memberikan ulasan untuk pesanan ini.');
        }

        Review::create([
            'user_id'    => $request->user()->id,
            'order_id'   => $order->id,
            'service_id' => $order->service_id,
            'rating'     => $request->rating,
            'comment'    => $request->comment,
        ]);

        return redirect()->route('pelanggan.aktivitas')
            ->with('success', 'Terima kasih! Ulasan Anda telah dikirim.');
    }

    public function batalkanPesanan(Request $request, $id)
    {
        $order = \App\Models\Order::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->findOrFail($id);

        $qty = $order->orderItems()->sum('qty');
        if ($qty > 0) {
            return redirect()->back()->with('error', 'Pesanan sudah diproses dan tidak dapat dibatalkan.');
        }

        $order->delete();
        return redirect()->route('pelanggan.aktivitas')->with('success', 'Pesanan berhasil dibatalkan.');
    }

    public function lacakPesanan(Request $request)
    {
        return Inertia::render('dashboard/pelanggan/lacak-pesanan', [
            'auth' => ['user' => $request->user()]
        ]);
    }

    public function cariPesanan(Request $request)
    {
        $request->validate([
            'invoice' => 'required|string',
        ]);

        $invoiceStr = trim($request->invoice);
        // Format of Invoice is INV-YYYYMMDD-0004
        $parts = explode('-', $invoiceStr);
        $orderId = null;

        if (count($parts) >= 3) {
            $orderId = (int) end($parts);
        } else {
            // fallback: parse all digits
            $orderId = (int) preg_replace('/[^0-9]/', '', $invoiceStr);
        }

        if (!$orderId) {
            return redirect()->back()->with('error', 'Format invoice tidak valid.');
        }

        $order = \App\Models\Order::where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return redirect()->back()->with('error', 'Pesanan tidak ditemukan atau bukan milik Anda.');
        }

        return redirect()->route('pelanggan.aktivitas.detail', $order->id);
    }

    public function buatPesanan(Request $request, Service $service = null)
    {
        // Support reorder: pre-fill from a previous order
        $prefill = null;
        if ($request->query('reorder')) {
            $prevOrder = Order::with(['service', 'deliveries'])
                ->where('user_id', $request->user()->id)
                ->find($request->query('reorder'));
            if ($prevOrder) {
                $hasPickup   = $prevOrder->deliveries->where('type', 'pickup')->isNotEmpty();
                $hasDelivery = $prevOrder->deliveries->where('type', 'delivery')->isNotEmpty();
                $deliveryType = 'jemput';
                if ($hasPickup && $hasDelivery) $deliveryType = 'antar_jemput';
                elseif ($hasDelivery) $deliveryType = 'antar';
                $prefill = [
                    'service_id'     => $prevOrder->service_id,
                    'delivery_type'  => $deliveryType,
                    'pickup_address' => $prevOrder->pickup_address,
                ];
                // Set the service context to the previous order's service
                $service = $prevOrder->service;
            }
        }

        // Jika tidak ada service yang dipilih, ambil yang pertama
        if (!$service || !$service->exists) {
            $service = Service::first();
        }

        // Jika masih kosong (tidak ada data service di DB)
        if (!$service) {
            return redirect()->route('dashboard')->with('error', 'Layanan tidak tersedia.');
        }

        $services = Service::all()->map(fn($s) => [
            'id'        => $s->id,
            'name'      => $s->name,
            'image_url' => $s->image ? asset('storage/' . $s->image) : null,
        ]);

        return Inertia::render('dashboard/pelanggan/buat-pesanan', [
            'auth'     => ['user' => $request->user()],
            'services' => $services,
            'service'  => [
                'id'          => $service->id,
                'name'        => $service->name,
                'description' => $service->description,
                'price'       => (float) $service->price,
                'unit'        => $service->unit ?? '/kg',
                'image_url'   => $service->image ? asset('storage/' . $service->image) : null,
            ],
            'prefill'  => $prefill,
        ]);
    }

    public function simpanPesanan(Request $request)
    {
        $validated = $request->validate([
            'service_id'      => 'required|exists:services,id',
            'delivery_type'   => 'required|in:antar_jemput,jemput,antar',
            'pickup_address'  => 'required|string|max:500',
        ]);

        $user    = $request->user();
        $service = Service::findOrFail($validated['service_id']);

        // Courier will update KG and total price, initially only delivery fee
        $deliveryFee  = match($validated['delivery_type']) {
            'antar_jemput' => 10000,
            default        => 5000,
        };
        $totalPrice = $deliveryFee;

        // 1. Create Order
        $order = Order::create([
            'user_id'          => $user->id,
            'service_id'       => $service->id,
            'status'           => 'pending',
            'total_price'      => $totalPrice, // Will be updated by courier
            'pickup_address'   => $validated['pickup_address'],
            'delivery_address' => $user->address ?? $validated['pickup_address'],
        ]);

        // 2. Create Order Item (placeholder)
        OrderItem::create([
            'order_id'  => $order->id,
            'item_name' => 'Estimasi - ' . $service->name,
            'qty'       => 0, // Courier will update
            'price'     => $service->price,
        ]);

        // 3. Create Payment (pending, method and amount placeholder)
        Payment::create([
            'order_id' => $order->id,
            'amount'   => 0,
            'method'   => 'cash', 
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
            ->with('success', 'Pesanan berhasil dibuat! Kurir kami akan segera menjemput & mengonfirmasi biaya.');
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
