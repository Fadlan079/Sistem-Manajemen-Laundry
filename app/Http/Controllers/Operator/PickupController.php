<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Notification;

class PickupController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'semua');
        $search = $request->input('search', '');
        $dateFilter = $request->input('date', '');

        // Base query for deliveries that are pickup tasks and not yet completed
        $query = Delivery::with(['order.user', 'courier', 'order.orderItems'])
            ->where('type', 'pickup')
            ->whereNotIn('status', ['selesai', 'pending']);

        if ($dateFilter) {
            $query->whereHas('order', function ($q) use ($dateFilter) {
                $q->whereDate('created_at', $dateFilter);
            });
        }

        if ($search) {
            // Detect if it's an INV-format search
            $isInvoiceSearch = stripos($search, 'INV-') !== false;
            
            $query->where(function ($q) use ($search, $isInvoiceSearch) {
                // Search by customer name
                $q->whereHas('order.user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  // Search by phone
                  ->orWhereHas('order.user', fn($u) => $u->where('phone', 'like', "%{$search}%"))
                  // Search by order ID (raw number)
                  ->orWhereHas('order', fn($o) => $o->where('id', 'like', "%{$search}%"));
                
                // If it looks like an invoice code, parse and search by order ID
                if ($isInvoiceSearch) {
                    $parts = explode('-', $search);
                    $orderId = (int) end($parts);
                    if ($orderId > 0) {
                        $q->orWhereHas('order', fn($o) => $o->where('id', $orderId));
                    }
                }
            });
        }

        // Calculate stats for badges and cards
        $today = Carbon::today();
        
        // Define Terlama as orders getting closer to 24 hours without pickup
        $lateThreshold = Carbon::now()->subHours(12);

        $stats = [
            'semua' => Delivery::where('type', 'pickup')->whereNotIn('status', ['selesai', 'pending'])->count(),
            'belum_diassign' => Delivery::where('type', 'pickup')->whereNotIn('status', ['selesai', 'pending'])
                                ->whereNull('courier_id')->whereNull('external_courier_name')->count(),
            'sudah_diassign' => Delivery::where('type', 'pickup')->whereNotIn('status', ['selesai', 'pending'])
                                ->where(fn($q) => $q->whereNotNull('courier_id')->orWhereNotNull('external_courier_name'))->count(),
            'terlama' => Delivery::where('type', 'pickup')->whereNotIn('status', ['selesai', 'pending'])
                                ->whereHas('order', fn($q) => $q->where('created_at', '<', $lateThreshold))
                                ->count(),
            // New stats for cards
            'hari_ini' => Delivery::where('type', 'pickup')->whereDate('created_at', $today)->count(),
            'kemarin' => Delivery::where('type', 'pickup')->whereDate('created_at', Carbon::yesterday())->count(),
            'selesai_hari_ini' => Delivery::where('type', 'pickup')->where('status', 'selesai')->whereDate('updated_at', $today)->count(),
        ];

        // Apply tab filters
        if ($tab === 'belum-diassign') {
            $query->whereNull('courier_id')->whereNull('external_courier_name');
        } elseif ($tab === 'sudah-diassign') {
            $query->where(fn($q) => $q->whereNotNull('courier_id')->orWhereNotNull('external_courier_name'));
        } elseif ($tab === 'terlama') {
            $query->whereHas('order', fn($q) => $q->where('created_at', '<', $lateThreshold));
        }

        // Prefer oldest first for pickups so they hit their SLA faster
        $query->orderBy('created_at', 'asc');

        $deliveries = $query->paginate(10)->withQueryString()->through(function ($d) {
            $orderDate = $d->order->created_at;
            $now = Carbon::now();
            $ageInHours = $orderDate->diffInHours($now);
            
            // Late condition: If it's been more than 24 hours, it's overdue.
            // But we will show warning after 20 hours.
            $isLate = $ageInHours >= 20;
            $lateText = '';

            if ($ageInHours >= 24) {
                $lateHours = floor($ageInHours - 24);
                $lateText = "TERLAMBAT " . ($lateHours > 0 ? " {$lateHours} Jam" : " >24 Jam");
            } elseif ($ageInHours >= 20) {
                $remainingHours = 24 - $ageInHours;
                $lateText = "SISA WAKTU {$remainingHours} Jam";
            }

            return [
                'id' => $d->id,
                'order_id' => str_pad($d->order_id, 5, '0', STR_PAD_LEFT),
                'invoice' => 'INV-' . $d->order->created_at->format('Ymd') . '-' . str_pad($d->order_id, 4, '0', STR_PAD_LEFT),
                'name' => $d->order->user->name ?? 'Pelanggan anonim',
                'time' => $d->scheduled_at ? $d->scheduled_at->format('H:i') : $d->created_at->format('d M, H:i'),
                'address' => $d->order->pickup_address ?? '-',
                'courier_id' => $d->courier_id,
                'is_external' => $d->external_courier_name ? true : false,
                'courier' => $d->courier ? 'Internal: ' . $d->courier->name : ($d->external_courier_name ? 'Eksternal: ' . $d->external_courier_name : null),
                'courierTime' => ($d->courier || $d->external_courier_name) ? ($d->scheduled_at ? $d->scheduled_at->format('H:i') : $d->updated_at->format('H:i')) : null,
                'isLate' => $isLate,
                'lateText' => $lateText,
                'phone' => $d->order->user->phone ?? '-',
                'kg' => $d->order->orderItems->first()->qty ?? '',
                'notes' => $d->notes ?? '',
                'proof_image' => $d->proof_image ? asset('storage/' . $d->proof_image) : null,
            ];
        });

        // Get available couriers
        $couriers = User::where('role', 'kurir')->select('id', 'name')->get();

        return Inertia::render('dashboard/operator/penjemputan', [
            'deliveries' => $deliveries,
            'stats' => $stats,
            'couriers' => $couriers,
            'filters' => [
                'tab' => $tab,
                'search' => $search,
                'date' => $dateFilter,
            ]
        ]);
    }

    public function assignCourier(Request $request, Delivery $delivery)
    {
        $request->validate([
            'is_external' => 'required|boolean',
            'courier_id' => 'required_if:is_external,false|nullable|exists:users,id',
            'external_courier_name' => 'required_if:is_external,true|nullable|string|max:255',
            'external_courier_phone' => 'required_if:is_external,true|nullable|string|max:20',
        ]);

        if ($request->is_external) {
            $delivery->update([
                'courier_id' => null,
                'external_courier_name' => $request->external_courier_name,
                'external_courier_phone' => $request->external_courier_phone,
            ]);
        } else {
            $delivery->update([
                'courier_id' => $request->courier_id,
                'external_courier_name' => null,
                'external_courier_phone' => null,
            ]);
        }

        // Change order status to 'dijemput' when courier is assigned
        if ($delivery->order && $delivery->order->status === 'pending') {
            $delivery->order->update([
                'status' => 'dijemput'
            ]);
        }

        Notification::create([
            'user_id'     => $delivery->order->user_id,
            'type'        => 'delivery',
            'title'       => 'Kurir Sedang Menuju Lokasi',
            'description' => "Kurir sedang menuju lokasi Anda untuk menjemput pesanan #INV-" . $delivery->order->created_at->format('Ymd') . "-" . str_pad($delivery->order_id, 4, '0', STR_PAD_LEFT) . ".",
            'metadata'    => ['order_id' => $delivery->order_id]
        ]);

        return back()->with('success', 'Kurir berhasil di-assign dan pesanan dalam penjemputan.');
    }

    public function markAsPickedUp(Request $request, Delivery $delivery)
    {
        $payload = ['status' => 'selesai'];

        // If it's an external courier or form was submitted with details
        if ($request->has('kg')) {
            $request->validate([
                'kg' => 'required|numeric|min:0.5',
                'notes' => 'nullable|string|max:1000',
                'proof_image' => 'nullable|image|max:2048'
            ]);

            // Save notes
            if ($request->filled('notes')) {
                $payload['notes'] = $request->notes;
            }

            // Save image
            if ($request->hasFile('proof_image')) {
                $path = $request->file('proof_image')->store('deliveries', 'public');
                $payload['proof_image'] = $path;
            }

            // Update order and order items based on KG
            if ($delivery->order) {
                // Determine base delivery fee logic
                // The current total_price is just the delivery fee (since qty was 0)
                $deliveryFee = $delivery->order->total_price;
                
                // Get the first order item to update its Qty and calculate new price
                $orderItem = $delivery->order->orderItems()->first();
                if ($orderItem) {
                    $orderItem->update([
                        'qty' => $request->kg,
                    ]);

                    // Recalculate order total price
                    $newTotal = $deliveryFee + ($request->kg * $orderItem->price);
                    $delivery->order->update([
                        'total_price' => $newTotal
                    ]);
                }
            }
        }

        // 1. Ubah task delivery menjadi selesai
        $delivery->update($payload);

        // 2. Ubah status order dari dijemput (atau pending) menjadi diproses
        if ($delivery->order && in_array($delivery->order->status, ['pending', 'dijemput'])) {
            $delivery->order->update([
                'status' => 'diproses'
            ]);
        }

        Notification::create([
            'user_id'     => $delivery->order->user_id,
            'type'        => 'order',
            'title'       => 'Penjemputan Berhasil',
            'description' => "Cucian Anda untuk pesanan #INV-" . $delivery->order->created_at->format('Ymd') . "-" . str_pad($delivery->order_id, 4, '0', STR_PAD_LEFT) . " telah dijemput kurir dan mulai diproses.",
            'metadata'    => ['order_id' => $delivery->order_id]
        ]);

        return back()->with('success', 'Penjemputan berhasil diselesaikan dan pesanan sedang diproses.');
    }
}
