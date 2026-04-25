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
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use App\Models\Notification;

class CustomerDashboardController extends Controller
{

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
            $type = in_array($dbStatus, ['selesai']) ? 'riwayat' : 'aktif';

            // Penentuan UI Badge Status
            if ($dbStatus === 'selesai') {
                $badgeText = ucfirst($dbStatus);
                $badgeColor = 'green';
            } elseif ($dbStatus === 'diantar') {
                $badgeText = 'Sedang Diantar';
                $badgeColor = 'blue';
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

    public function daftarLayanan(Request $request)
    {
        $services = Service::withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get()
            ->map(function ($s) {
                return [
                    'id'            => $s->id,
                    'name'          => $s->name,
                    'description'   => $s->description,
                    'price'         => (float) $s->price,
                    'unit'          => $s->unit ?? '/kg',
                    'category'      => $s->category,
                    'image_url'     => $s->image ? asset('storage/' . $s->image) : null,
                    'rating'        => $s->reviews_avg_rating ? round($s->reviews_avg_rating, 1) : null,
                    'reviews_count' => $s->reviews_count,
                    'orders_count'  => $s->orders_count ?? 0, // Fallback if no order count in DB, but normally orders_count is available if queried
                ];
            });

        // Get categories to be used in UI filter
        $categories = $services->pluck('category')->unique()->filter()->values()->toArray();

        return Inertia::render('dashboard/pelanggan/daftar-layanan', [
            'auth' => [
                'user' => $request->user(),
            ],
            'services'   => $services,
            'categories' => $categories,
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
        $order = Order::with(['service', 'orderItems', 'payments', 'review', 'deliveries.courier'])
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

        $isKg          = in_array(strtolower($order->service->unit ?? '/kg'), ['/kg', 'kg']);
        $qty           = $order->orderItems->sum('qty');
        $servicePrice  = (float) $order->orderItems->sum(fn($i) => $i->price * $i->qty);
        // Delivery fee is initially the total price. But when updated, total_price is servicePrice + fee
        $fee           = (float) $order->total_price - $servicePrice;

        $hasPickup   = $order->deliveries->where('type', 'pickup')->isNotEmpty();
        $hasDelivery = $order->deliveries->where('type', 'delivery')->isNotEmpty();

        $isCalculated = $qty > 0; // If qty > 0, it means it has been updated by courier/admin or is fixed qty initially
        
        $estimatedRangeText = null;
        if ($isKg && !$isCalculated) {
            $firstItem = $order->orderItems->first();
            if ($firstItem && str_contains($firstItem->item_name, 'Estimasi Berat:')) {
                // Extracts the string between "Estimasi Berat: " and " - "
                $part = explode('Estimasi Berat: ', $firstItem->item_name)[1] ?? '';
                $estimatedRangeText = trim(explode(' - ', $part)[0] ?? '');
            }
        }

        return Inertia::render('dashboard/pelanggan/partials/detail-order', [
            'auth'  => ['user' => $request->user()],
            'order' => [
                'id'            => 'ORD-' . $order->id,
                'dbId'          => $order->id,
                'service'       => $order->orderItems->first()?->item_name ?? $order->service->name ?? '-',
                'service_price' => (float) ($order->orderItems->first()?->price ?? $order->service->price ?? 0),
                'unit'          => $order->service->unit ?? '/kg',
                'isKg'          => $isKg,
                'items_qty'     => $qty,
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
                'paymentMethodRaw' => $payment ? $payment->method : 'cash',
                'paymentMethod' => $methodLabel[$payment?->method ?? 'transfer'] ?? 'Transfer Bank',
                'invoice'       => 'INV-' . $order->created_at->format('Ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                'pickup_address' => $order->pickup_address,
                'laundry_notes'  => $order->notes,
                'courier_notes'  => $order->deliveries->where('type', 'pickup')->first()?->notes,
                'pickup_timestamp' => $order->deliveries->where('type', 'pickup')->first()?->scheduled_at,
                'pickup_date_text' => $order->deliveries->where('type', 'pickup')->first()?->scheduled_at ? \Carbon\Carbon::parse($order->deliveries->where('type', 'pickup')->first()->scheduled_at)->translatedFormat('l, d F Y - H:i') : null,
                'estimated_weight' => $estimatedRangeText,
                'review'        => $order->review ? [
                    'rating'  => (int) $order->review->rating,
                    'comment' => $order->review->comment,
                    'date'    => $order->review->created_at->translatedFormat('d M Y, H:i'),
                ] : null,
                'delivery_type_label' => ($hasPickup && $hasDelivery) ? 'Antar Jemput' : ($hasPickup ? 'Jemput Saja' : ($hasDelivery ? 'Antar Saja' : 'Tanpa Pengantaran')),
                'pickup_courier' => (function() use ($order) {
                    $d = $order->deliveries->where('type', 'pickup')->first();
                    if (!$d) return null;
                    $c = $d->courier;
                    if (!$c && !$d->external_courier_name) return null;
                    return [
                        'name' => $c ? $c->name : $d->external_courier_name,
                        'phone' => $c ? $c->phone : $d->external_courier_phone,
                        'status' => $d->status,
                    ];
                })(),
                'delivery_courier' => (function() use ($order) {
                    $d = $order->deliveries->where('type', 'delivery')->first();
                    if (!$d) return null;
                    $c = $d->courier;
                    if (!$c && !$d->external_courier_name) return null;
                    return [
                        'name' => $c ? $c->name : $d->external_courier_name,
                        'phone' => $c ? $c->phone : $d->external_courier_phone,
                        'status' => $d->status,
                    ];
                })(),
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
            'price'     => (float) $s->price,
            'unit'      => $s->unit ?? '/kg',
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
            'service_id'         => 'required|exists:services,id',
            'delivery_type'      => 'required|in:antar_jemput,jemput,antar',
            'pickup_address'     => 'required|string|max:500',
            'pickup_date'        => 'required|date',
            'pickup_time'        => 'required|date_format:H:i',
            'laundry_notes'      => 'nullable|string|max:500',
            'courier_notes'      => 'nullable|string|max:500',
            'payment_preference' => 'required|in:cash,transfer',
            'estimated_weight'   => 'nullable|string',
            'item_qty'           => 'nullable|numeric|min:1',
            'extra_services'     => 'nullable|array',
            'extra_services.*'   => 'string|in:express,treatment,own_perfume',
        ]);

        $user    = $request->user();
        $service = Service::findOrFail($validated['service_id']);

        // Courier will update KG and total price, initially only delivery fee
        $deliveryFee  = match($validated['delivery_type']) {
            'antar_jemput' => 10000,
            default        => 5000,
        };
        $totalPrice = $deliveryFee;

        $isKg = in_array(strtolower($service->unit), ['/kg', 'kg']);
        $fixedQty = 0;
        
        $baseServicePrice = (float) $service->price;
        $extraPricing = 0;
        $extraLabels = [];
        
        if (!empty($validated['extra_services'])) {
            if (in_array('express', $validated['extra_services'])) {
                $extraPricing += $baseServicePrice * 0.5;
                $extraLabels[] = 'Express (24 Jam)';
            }
            if (in_array('treatment', $validated['extra_services'])) {
                $extraPricing += $isKg ? 2000 : 5000;
                $extraLabels[] = 'Treatment Khusus';
            }
            if (in_array('own_perfume', $validated['extra_services'])) {
                $extraLabels[] = 'Pewangi Sendiri';
            }
        }
        
        $combinedUnitPrice = $baseServicePrice + $extraPricing;
        
        if (!$isKg && !empty($validated['item_qty'])) {
            $fixedQty = $validated['item_qty'];
            $totalPrice += ($fixedQty * $combinedUnitPrice);
        }

        // Construct final address
        $finalAddress = $validated['pickup_address'];

        // 1. Create Order
        $order = Order::create([
            'user_id'          => $user->id,
            'service_id'       => $service->id,
            'status'           => 'pending',
            'total_price'      => $totalPrice, // Courier updates if KG, else fixed
            'pickup_address'   => $finalAddress,
            'delivery_address' => $user->address ?? $finalAddress,
            'notes'            => $validated['laundry_notes'] ?? null,
        ]);

        // 2. Create Order Item
        $extraStr = !empty($extraLabels) ? ' [+ ' . implode(', ', $extraLabels) . ']' : '';

        if ($isKg) {
            $itemName = 'Estimasi Berat: ' . ($validated['estimated_weight'] ?? 'N/A') . ' - ' . $service->name . $extraStr;
        } else {
            $itemName = $service->name . $extraStr . ' (' . $fixedQty . ' ' . trim($service->unit, '/') . ')';
        }

        OrderItem::create([
            'order_id'  => $order->id,
            'item_name' => $itemName,
            'qty'       => $fixedQty, // 0 if KG (updated by courier), >0 if fixed
            'price'     => $combinedUnitPrice,
        ]);

        // 3. Create Payment (pending, method and amount placeholder)
        Payment::create([
            'order_id' => $order->id,
            'amount'   => 0,
            'method'   => $validated['payment_preference'],
            'status'   => 'pending',
        ]);

        $scheduledAt = \Carbon\Carbon::parse($validated['pickup_date'] . ' ' . $validated['pickup_time']);

        // 4. Create Delivery records based on type
        if (in_array($validated['delivery_type'], ['antar_jemput', 'jemput'])) {
            Delivery::create([
                'order_id'     => $order->id,
                'courier_id'   => null,
                'status'       => 'dijemput',
                'type'         => 'pickup',
                'scheduled_at' => $scheduledAt,
                'notes'        => $validated['courier_notes'] ?? null,
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

        // 5. Create Notification
        Notification::create([
            'user_id'     => $user->id,
            'type'        => 'order',
            'title'       => 'Pesanan Berhasil Dibuat',
            'description' => "Pesanan #INV-" . $order->created_at->format('Ymd') . "-" . str_pad($order->id, 4, '0', STR_PAD_LEFT) . " telah berhasil dibuat.",
            'metadata'    => ['order_id' => $order->id]
        ]);

        return redirect()->route('pelanggan.aktivitas.detail', $order->id)
            ->with('success', 'Pesanan berhasil dibuat! Kurir kami akan segera menjemput & mengonfirmasi biaya.');
    }

    public function getMidtransToken(Request $request, $id)
    {
        $order = Order::with(['payments', 'service'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $payment = $order->payments()->where('status', 'pending')->first();

        if (! $payment) {
            return response()->json(['error' => 'Tidak ada tagihan yang pending.'], 404);
        }

        // If snap_token already exists and order still pending, reuse it
        if ($payment->snap_token) {
            return response()->json([
                'snap_token' => $payment->snap_token,
                'client_key' => config('services.midtrans.client_key'),
            ]);
        }

        // Setup Midtrans config
        MidtransConfig::$serverKey    = config('services.midtrans.server_key');
        MidtransConfig::$isProduction = config('services.midtrans.is_production');
        MidtransConfig::$isSanitized  = true;
        MidtransConfig::$is3ds        = true;

        $user = $request->user();
        $midtransOrderId = 'ORD-' . $order->id . '-' . time();

        $params = [
            'transaction_details' => [
                'order_id'     => $midtransOrderId,
                'gross_amount' => (int) $order->total_price,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone ?? '',
            ],
            'item_details' => [
                [
                    'id'       => $order->service_id,
                    'price'    => (int) $order->total_price,
                    'quantity' => 1,
                    'name'     => substr($order->service->name ?? 'Layanan Laundry', 0, 50),
                ],
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        // Save snap token and midtrans order id to payment
        $payment->update([
            'snap_token'        => $snapToken,
            'midtrans_order_id' => $midtransOrderId,
        ]);

        return response()->json([
            'snap_token' => $snapToken,
            'client_key' => config('services.midtrans.client_key'),
        ]);
    }

    public function midtransCallback(Request $request, $id)
    {
        // After Snap onSuccess callback from frontend, mark payment as paid
        $order = Order::with('payments')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $payment = $order->payments()->where('status', 'pending')->first();

        if (! $payment) {
            return response()->json(['message' => 'Payment already processed.']);
        }

        $payment->update([
            'status'  => 'paid',
            'amount'  => (float) $order->total_price,
            'method'  => 'midtrans',
            'paid_at' => now(),
        ]);

        Notification::create([
            'user_id'     => $order->user_id,
            'type'        => 'payment',
            'title'       => 'Pembayaran Berhasil',
            'description' => "Pembayaran sebesar Rp" . number_format($order->total_price, 0, ',', '.') . " untuk pesanan #" . $order->id . " telah berhasil.",
            'metadata'    => ['order_id' => $order->id]
        ]);

        return response()->json(['message' => 'Payment confirmed successfully.']);
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

        $newMethod = $request->input('method', $payment->method);
        if (in_array(strtolower($newMethod), ['cash', 'cod'])) {
            return back()->with('error', 'Pembayaran tunai harus dikonfirmasi oleh Kurir atau Operator saat penyerahan uang.');
        }

        // Mark payment as paid immediately (no admin confirmation needed)
        $payment->update([
            'method'  => $newMethod,
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        Notification::create([
            'user_id'     => $order->user_id,
            'type'        => 'payment',
            'title'       => 'Pembayaran Berhasil',
            'description' => "Pembayaran sebesar Rp" . number_format($order->total_price, 0, ',', '.') . " untuk pesanan #" . $order->id . " telah berhasil.",
            'metadata'    => ['order_id' => $order->id]
        ]);

        return redirect()->route('pelanggan.aktivitas.detail', $id)
            ->with('success', 'Pembayaran berhasil dikonfirmasi! Pesanan Anda sedang kami proses.');
    }
}
