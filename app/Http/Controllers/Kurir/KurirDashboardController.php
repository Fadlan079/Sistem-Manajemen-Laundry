<?php

namespace App\Http\Controllers\Kurir;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Notification;

class KurirDashboardController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'belum-selesai');
        $search = $request->input('search', '');
        $dateFilter = $request->input('date', '');

        $courierId = auth()->id();

        // Base query for tasks assigned to this courier
        $query = Delivery::with(['order.user', 'order.orderItems', 'order.service'])
            ->where('courier_id', $courierId);

        if ($dateFilter) {
            $query->whereDate('created_at', $dateFilter);
        }

        if ($search) {
            $isInvoiceSearch = stripos($search, 'INV-') !== false;

            $query->where(function ($q) use ($search, $isInvoiceSearch) {
                // Search by customer name
                $q->whereHas('order.user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  // Search by phone
                  ->orWhereHas('order.user', fn($u) => $u->where('phone', 'like', "%{$search}%"))
                  // Search by order ID
                  ->orWhereHas('order', fn($o) => $o->where('id', 'like', "%{$search}%"));

                if ($isInvoiceSearch) {
                    $parts = explode('-', $search);
                    $orderId = (int) end($parts);
                    if ($orderId > 0) {
                        $q->orWhereHas('order', fn($o) => $o->where('id', $orderId));
                    }
                }
            });
        }

        // Setup stats for tabs
        $stats = [
            'semua' => Delivery::where('courier_id', $courierId)->count(),
            'belum_selesai' => Delivery::where('courier_id', $courierId)->where('status', '!=', 'selesai')->count(),
            'sudah_dijemput' => Delivery::where('courier_id', $courierId)->where('status', 'selesai')->where('type', 'pickup')->count(),
            'sudah_diantar' => Delivery::where('courier_id', $courierId)->where('status', 'selesai')->where('type', 'delivery')->count(),
        ];
        
        $today = Carbon::today();
        $stats['hari_ini'] = Delivery::where('courier_id', $courierId)->whereDate('created_at', $today)->count();
        $stats['kemarin'] = Delivery::where('courier_id', $courierId)->whereDate('created_at', Carbon::yesterday())->count();
        $stats['selesai_hari_ini'] = Delivery::where('courier_id', $courierId)->where('status', 'selesai')->whereDate('updated_at', $today)->count();
        $stats['sisa'] = Delivery::where('courier_id', $courierId)->where('status', '!=', 'selesai')->whereDate('created_at', '<=', $today)->count();

        // Apply Tab Filter
        if ($tab === 'belum-selesai') {
            $query->where('status', '!=', 'selesai');
        } elseif ($tab === 'sudah-dijemput') {
            $query->where('type', 'pickup')->where('status', 'selesai');
        } elseif ($tab === 'sudah-diantar') {
            $query->where('type', 'delivery')->where('status', 'selesai');
        } // 'semua' shows all

        // Sort by created
        $query->orderBy('created_at', 'asc');

        $deliveries = $query->paginate(10)->withQueryString()->through(function ($d) {
            $isKg = $d->order->service && stripos($d->order->service->unit, 'kg') !== false;
            return [
                'id' => $d->id,
                'order_id' => str_pad($d->order_id, 5, '0', STR_PAD_LEFT),
                'invoice' => 'INV-' . $d->order->created_at->format('Ymd') . '-' . str_pad($d->order_id, 4, '0', STR_PAD_LEFT),
                'name' => $d->order->user->name ?? 'Pelanggan',
                'phone' => $d->order->user->phone ?? '-',
                'address' => $d->type === 'delivery' ? ($d->order->delivery_address ?? $d->order->pickup_address ?? '-') : ($d->order->pickup_address ?? '-'),
                'type' => $d->type,
                'status' => $d->status,
                'time' => $d->scheduled_at ? $d->scheduled_at->format('d M, H:i') : $d->created_at->format('d M, H:i'),
                'notes' => $d->notes ?? '',
                'service_name' => $d->order->service->name ?? '-',
                'isKg' => $isKg,
                'order_db_id' => $d->order_id,
            ];
        });

        return Inertia::render('dashboard/kurir/kurir', [
            'deliveries' => $deliveries,
            'stats' => $stats,
            'filters' => [
                'tab' => $tab,
                'search' => $search,
                'date' => $dateFilter,
            ]
        ]);
    }

    public function markAsCompleted(Request $request, Delivery $delivery)
    {
        // Pastikan task ini benar milik kurir login
        if ($delivery->courier_id !== auth()->id()) {
            abort(403);
        }

        $payload = ['status' => 'selesai'];

        // Jika ini pickup dan ada isian berat (kg)
        if ($delivery->type === 'pickup' && $request->has('kg')) {
            $request->validate([
                'kg' => 'required|numeric|min:0.5',
                'notes' => 'nullable|string|max:1000',
                'proof_image' => 'nullable|image|max:2048'
            ]);

            if ($request->filled('notes')) {
                $payload['notes'] = $request->notes;
            }

            if ($request->hasFile('proof_image')) {
                $path = $request->file('proof_image')->store('deliveries', 'public');
                $payload['proof_image'] = $path;
            }

            if ($delivery->order) {
                // Harga pengantaran tersimpan di total_price awal order saat checkout
                $deliveryFee = $delivery->order->total_price;
                
                $orderItem = $delivery->order->orderItems()->first();
                if ($orderItem) {
                    $orderItem->update([
                        'qty' => $request->kg,
                    ]);

                    // Recalculate price
                    $newTotal = $deliveryFee + ($request->kg * $orderItem->price);
                    $delivery->order->update([
                        'total_price' => $newTotal
                    ]);
                }
            }
        } else {
            // Untuk penjemputan tanpa 'kg' atau untuk delivery
            $request->validate([
                'notes' => 'nullable|string|max:1000',
                'proof_image' => 'nullable|image|max:2048'
            ]);

            if ($request->filled('notes')) {
                $payload['notes'] = $request->notes;
            }

            if ($request->hasFile('proof_image')) {
                $path = $request->file('proof_image')->store('deliveries', 'public');
                $payload['proof_image'] = $path;
            }
        }

        // Tandai selesai
        $delivery->update($payload);

        // Update status Order Parent
        if ($delivery->order) {
            if ($delivery->type === 'pickup') {
                if (in_array($delivery->order->status, ['pending', 'dijemput'])) {
                    $delivery->order->update([
                        'status' => 'diproses'
                    ]);
                }
            } else if ($delivery->type === 'delivery') {
                // Delivery is finished
                if (in_array($delivery->order->status, ['diproses', 'selesai', 'diantar'])) {
                    // Update ke order 'selesai'
                    // Tetapi pastikan jika ordernya 'selesai' belum di trigger, itu tetap 'selesai' atau apa.
                    // Tunggu: status di DB itu "selesai", jadi kalau diantar kita sudah "selesai".
                    $delivery->order->update([
                        'status' => 'selesai'
                    ]);

                    Notification::create([
                        'user_id'     => $delivery->order->user_id,
                        'type'        => 'delivery',
                        'title'       => 'Pesanan Telah Tiba',
                        'description' => "Pesanan #INV-" . $delivery->order->created_at->format('Ymd') . "-" . str_pad($delivery->order_id, 4, '0', STR_PAD_LEFT) . " telah berhasil diantarkan ke lokasi Anda.",
                        'metadata'    => ['order_id' => $delivery->order_id]
                    ]);
                }
            }

            // Pickup notification
            if ($delivery->type === 'pickup' && $delivery->order->status === 'diproses') {
                Notification::create([
                    'user_id'     => $delivery->order->user_id,
                    'type'        => 'order',
                    'title'       => 'Penjemputan Berhasil',
                    'description' => "Cucian Anda untuk pesanan #INV-" . $delivery->order->created_at->format('Ymd') . "-" . str_pad($delivery->order_id, 4, '0', STR_PAD_LEFT) . " telah dijemput kurir.",
                    'metadata'    => ['order_id' => $delivery->order_id]
                ]);
            }
        }

        return back()->with('success', 'Tugas berhasil diselesaikan.');
    }

    public function detail(Request $request, $id)
    {
        $order = Order::with(['service', 'orderItems', 'payments', 'deliveries', 'user'])
            ->findOrFail($id);

        $payment       = $order->payments->first();
        $paymentStatus = $payment && $payment->status === 'paid' ? 'PAID' : 'UNPAID';
        $methodLabel   = ['cash' => 'Tunai', 'transfer' => 'Transfer Bank', 'e-wallet' => 'E-Wallet / QRIS'];

        $isKg          = in_array(strtolower($order->service->unit ?? '/kg'), ['/kg', 'kg']);
        $qty           = $order->orderItems->sum('qty');
        $servicePrice  = (float) $order->orderItems->sum(fn($i) => $i->price * $i->qty);
        $fee           = (float) $order->total_price - $servicePrice;

        $hasPickup   = $order->deliveries->where('type', 'pickup')->isNotEmpty();
        $hasDelivery = $order->deliveries->where('type', 'delivery')->isNotEmpty();

        $isCalculated = $qty > 0;
        
        $estimatedRangeText = null;
        if ($isKg && !$isCalculated) {
            $firstItem = $order->orderItems->first();
            if ($firstItem && str_contains($firstItem->item_name, 'Estimasi Berat:')) {
                $part = explode('Estimasi Berat: ', $firstItem->item_name)[1] ?? '';
                $estimatedRangeText = trim(explode(' - ', $part)[0] ?? '');
            }
        }
        
        $statusMap = [
            'pending'  => 'Diterima',
            'diproses' => 'Dicuci',
            'selesai'  => 'Selesai',
            'diantar'  => 'Diantar',
        ];

        return Inertia::render('dashboard/kurir/detail', [
            'auth'  => ['user' => $request->user()],
            'order' => [
                'id'            => 'ORD-' . $order->id,
                'dbId'          => $order->id,
                'customerName'  => $order->user->name ?? 'Pelanggan anonim',
                'customerPhone' => $order->user->phone ?? '-',
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
            ],
        ]);
    }
}
