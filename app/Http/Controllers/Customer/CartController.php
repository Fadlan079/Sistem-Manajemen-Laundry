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
use App\Models\Notification;
use Carbon\Carbon;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $cartItems = $user->orders()
            ->with(['service', 'orderItems', 'deliveries'])
            ->where('status', 'cart')
            ->latest()
            ->get()
            ->map(function ($order) {
                $isKg = in_array(strtolower($order->service->unit ?? '/kg'), ['/kg', 'kg']);
                $qty = $order->orderItems->sum('qty');
                $isCalculated = $qty > 0;

                $estimatedRangeText = null;
                if ($isKg && !$isCalculated) {
                    $firstItem = $order->orderItems->first();
                    if ($firstItem && str_contains($firstItem->item_name, 'Estimasi Berat:')) {
                        $part = explode('Estimasi Berat: ', $firstItem->item_name)[1] ?? '';
                        $estimatedRangeText = trim(explode(' - ', $part)[0] ?? '');
                    }
                }

                return [
                    'id'            => $order->id,
                    'service_name'  => $order->service->name ?? '-',
                    'service_unit'  => $order->service->unit ?? '/kg',
                    'image'         => $order->service && $order->service->image ? asset('storage/' . $order->service->image) : null,
                    'total_price'   => (float) $order->total_price,
                    'is_kg'         => $isKg,
                    'qty'           => $qty,
                    'is_calculated' => $isCalculated,
                    'estimated_weight' => $estimatedRangeText,
                    'pickup_address' => $order->pickup_address,
                    'pickup_date'    => $order->deliveries->where('type', 'pickup')->first()?->scheduled_at 
                        ? Carbon::parse($order->deliveries->where('type', 'pickup')->first()->scheduled_at)->translatedFormat('d M Y, H:i') 
                        : '-',
                ];
            });

        return Inertia::render('dashboard/pelanggan/keranjang', [
            'cartItems' => $cartItems,
        ]);
    }

    public function addToCart(Request $request)
    {
        $user = $request->user();
        
        // Limit check
        $cartCount = $user->orders()->where('status', 'cart')->count();
        if ($cartCount >= 3) {
            return back()->with('error', 'Keranjang penuh! Maksimal 3 pesanan di keranjang. Selesaikan pesanan yang ada terlebih dahulu.');
        }

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

        $service = Service::findOrFail($validated['service_id']);

        // Logic from CustomerDashboardController::simpanPesanan
        $deliveryFee  = match($validated['delivery_type']) {
            'antar_jemput' => 10000,
            default        => 5000,
        };
        $totalPrice = $deliveryFee;

        $isKg = in_array(strtolower($service->unit), ['/kg', 'kg']);
        $fixedQty = 0;
        
        $useDiscount = $request->input('use_discount', false);
        if ($useDiscount && $service->is_discount_today) {
            $baseServicePrice = (float) $service->discounted_price;
        } else {
            $baseServicePrice = (float) $service->price;
        }

        $extraPricing = 0;
        $extraLabels = [];
        
        if (!empty($validated['extra_services'])) {
            if (in_array('express', $validated['extra_services'])) {
                $extraPricing += (float)$service->price * 0.5;
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

        $order = Order::create([
            'user_id'          => $user->id,
            'service_id'       => $service->id,
            'status'           => 'cart',
            'total_price'      => $totalPrice,
            'pickup_address'   => $validated['pickup_address'],
            'delivery_address' => $user->address ?? $validated['pickup_address'],
            'notes'            => $validated['laundry_notes'] ?? null,
        ]);

        $extraStr = !empty($extraLabels) ? ' [+ ' . implode(', ', $extraLabels) . ']' : '';
        if ($useDiscount && $service->is_discount_today) {
            $extraStr .= ' [DISKON 20%]';
        }

        if ($isKg) {
            $itemName = 'Estimasi Berat: ' . ($validated['estimated_weight'] ?? 'N/A') . ' - ' . $service->name . $extraStr;
        } else {
            $itemName = $service->name . $extraStr . ' (' . $fixedQty . ' ' . trim($service->unit, '/') . ')';
        }

        OrderItem::create([
            'order_id'  => $order->id,
            'item_name' => $itemName,
            'qty'       => $fixedQty,
            'price'     => $combinedUnitPrice,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount'   => 0,
            'method'   => $validated['payment_preference'],
            'status'   => 'pending',
        ]);

        $scheduledAt = Carbon::parse($validated['pickup_date'] . ' ' . $validated['pickup_time']);

        if (in_array($validated['delivery_type'], ['antar_jemput', 'jemput'])) {
            Delivery::create([
                'order_id'     => $order->id,
                'courier_id'   => null,
                'status'       => 'pending', // Use pending for cart
                'type'         => 'pickup',
                'scheduled_at' => $scheduledAt,
                'notes'        => $validated['courier_notes'] ?? null,
            ]);
        }
        if (in_array($validated['delivery_type'], ['antar_jemput', 'antar'])) {
            Delivery::create([
                'order_id'   => $order->id,
                'courier_id' => null,
                'status'     => 'pending', // Use pending for cart
                'type'       => 'delivery',
            ]);
        }

        return redirect()->route('pelanggan.keranjang')
            ->with('success', 'Pesanan berhasil ditambahkan ke keranjang.');
    }

    public function checkout(Request $request)
    {
        $user = $request->user();
        $orderIds = $request->input('order_ids', []);

        if (empty($orderIds)) {
            return back()->with('error', 'Pilih minimal satu pesanan untuk checkout.');
        }

        $orders = $user->orders()->whereIn('id', $orderIds)->where('status', 'cart')->get();

        foreach ($orders as $order) {
            $order->update(['status' => 'pending']);
            
            // Update deliveries status from 'pending' to active status
            foreach ($order->deliveries as $delivery) {
                if ($delivery->type === 'pickup') {
                    $delivery->update(['status' => 'dijemput']);
                } else {
                    $delivery->update(['status' => 'diantar']);
                }
            }

            // Create Notification
            Notification::create([
                'user_id'     => $user->id,
                'type'        => 'order',
                'title'       => 'Pesanan Berhasil Dibuat',
                'description' => "Pesanan #INV-" . $order->created_at->format('Ymd') . "-" . str_pad($order->id, 4, '0', STR_PAD_LEFT) . " telah berhasil dibuat dari keranjang.",
                'metadata'    => ['order_id' => $order->id]
            ]);
        }

        return redirect()->route('pelanggan.aktivitas')
            ->with('success', count($orders) . ' pesanan berhasil dicheckout!');
    }

    public function remove(Request $request, $id)
    {
        $user = $request->user();
        $order = $user->orders()->where('id', $id)->where('status', 'cart')->firstOrFail();
        
        $order->delete();

        return back()->with('success', 'Pesanan berhasil dihapus dari keranjang.');
    }
}
