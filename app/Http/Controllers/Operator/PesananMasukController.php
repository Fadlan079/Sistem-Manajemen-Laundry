<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Delivery;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Models\Notification;

class PesananMasukController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $date   = $request->input('date', '');

        $query = Order::with(['user', 'service', 'payments', 'orderItems', 'deliveries'])
            ->when($search, function ($q) use ($search) {
                $numericId = null;
                if (preg_match('/-(\d{4})$/', $search, $m)) {
                    $numericId = (int) $m[1];
                } elseif (is_numeric($search)) {
                    $numericId = (int) $search;
                }

                $q->where(function ($q2) use ($search, $numericId) {
                    $q2->whereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                       ->orWhereHas('service', fn($s) => $s->where('name', 'like', "%{$search}%"));
                    if ($numericId !== null) {
                        $q2->orWhere('id', $numericId);
                    }
                });
            })
            ->when($date, fn($q) => $q->whereDate('created_at', $date))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest();

        $orders = $query->paginate(10)->withQueryString()->through(function($o) {
            $item = $o->orderItems->first();
            $dists = $o->deliveries->count();
            // Assume 2 deliveries = 10k, 1 = 5k
            $fee = $dists * 5000;

            return [
                'id'             => $o->id,
                'invoice'        => 'INV-' . $o->created_at->format('Ymd') . '-' . str_pad($o->id, 4, '0', STR_PAD_LEFT),
                'customer'       => $o->user?->name ?? '-',
                'customer_email' => $o->user?->email ?? '-',
                'user_id'        => $o->user_id,
                'service'        => $o->service?->name ?? '-',
                'service_id'     => $o->service_id,
                'status'         => $o->status,
                'total_price'    => (float) $o->total_price,
                'pickup_address' => $o->pickup_address,
                'delivery_address' => $o->delivery_address,
                'payment_status' => $o->payments->first()?->status ?? 'belum',
                'payment_method' => $o->payments->first()?->method ?? '-',
                'date'           => $o->created_at->format('d M Y'),
                // Breakdown fields
                'items_qty'      => (float) ($item?->qty ?? 0),
                'service_price'  => (float) ($item?->price ?? ($o->service?->price ?? 0)),
                'unit'           => $o->service?->unit ?? '/kg',
                'fee'            => (float) $fee,
                'isKg'           => in_array(strtolower($o->service?->unit ?? ''), ['/kg', 'kg']),
                'laundry_notes'  => $o->notes,
            ];
        });

        $stats = [
            'total'    => Order::count(),
            'selesai'  => Order::where('status', 'selesai')->count(),
            'diproses' => Order::where('status', 'diproses')->count(),
            'pending'  => Order::where('status', 'pending')->count(),
        ];

        $statusDist = [
            Order::where('status', 'selesai')->count(),
            Order::where('status', 'diproses')->count(),
            Order::where('status', 'pending')->count(),
            Order::where('status', 'diantar')->count(),
            Order::where('status', 'dijemput')->count(),
        ];

        $weeklyTrend = collect(range(6, 0))->map(function ($i) {
            $day = Carbon::now()->subDays($i);
            return [
                'label' => $day->format('D'),
                'value' => Order::whereDate('created_at', $day->toDateString())->count(),
            ];
        });

        $paymentMethods = [
            Payment::where('method', 'transfer')->count(),
            Payment::where('method', 'cash')->count(),
            Payment::where('method', 'e-wallet')->count(),
        ];

        $services = Service::orderByDesc(
            Order::selectRaw('COUNT(*)')
                ->whereColumn('service_id', 'services.id')
        )->limit(3)->get();

        $topServicesChart = collect(range(3, 0))->map(function ($i) use ($services) {
            $month = Carbon::now()->subMonths($i);
            $row = ['label' => $month->format('M')];
            foreach ($services as $s) {
                $row[$s->name] = Order::where('service_id', $s->id)
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();
            }
            return $row;
        });

        $serviceList = Service::all()->map(fn($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'price'       => (float) $s->price,
            'unit'        => $s->unit ?? '/kg',
            'description' => $s->description,
        ]);
        
        $customerList = User::where('role', 'pelanggan')->select('id', 'name', 'email')->get();

        return Inertia::render('dashboard/operator/PesananMasuk', [
            'orders'      => $orders,
            'stats'       => $stats,
            'filters'     => ['search' => $search, 'status' => $status, 'date' => $date],
            'services'    => $serviceList,
            'customers'   => $customerList,
            'chartData'   => [
                'statusDist'     => $statusDist,
                'weeklyTrend'    => $weeklyTrend,
                'paymentMethods' => $paymentMethods,
                'topServices'    => $topServicesChart,
                'serviceNames'   => $services->pluck('name'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'            => 'required|exists:users,id',
            'service_id'         => 'required|exists:services,id',
            'delivery_type'      => 'required|in:antar_jemput,jemput,antar,outlet',
            'pickup_address'     => 'nullable|string',
            'delivery_address'   => 'nullable|string',
            'pickup_date'        => 'nullable|date',
            'pickup_time'        => 'nullable|date_format:H:i',
            'laundry_notes'      => 'nullable|string',
            'courier_notes'      => 'nullable|string',
            'payment_preference' => 'required',
            'weight'             => 'nullable|numeric|min:0.1',
            'item_qty'           => 'nullable|numeric|min:1',
            'extra_services'     => 'nullable|array',
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $user = tap(User::find($validated['user_id']), fn($u) => $u ?: abort(404));

        $deliveryFee = match($validated['delivery_type']) {
            'antar_jemput' => 10000,
            'jemput', 'antar' => 5000,
            default => 0,
        };

        $totalPrice = $deliveryFee;
        $isKg = in_array(strtolower($service->unit), ['/kg', 'kg']);
        $fixedQty = 0;
        $fixedWeight = 0;
        
        $baseServicePrice = (float) $service->price;
        $extraPricing = 0;
        $extraLabels = [];
        
        if (!empty($validated['extra_services'])) {
            if (in_array('express', $validated['extra_services'])) {
                $extraPricing += $baseServicePrice * 0.5;
                $extraLabels[] = 'Express';
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
        
        // Calculate costs directly for both pcs and kg since operator input is final
        if ($isKg && !empty($validated['weight'])) {
            $fixedWeight = $validated['weight'];
            $totalPrice += ($fixedWeight * $combinedUnitPrice);
        } elseif (!$isKg && !empty($validated['item_qty'])) {
            $fixedQty = $validated['item_qty'];
            $totalPrice += ($fixedQty * $combinedUnitPrice);
        }

        // 1. Create Order
        $order = Order::create([
            'user_id'          => $user->id,
            'service_id'       => $service->id,
            'status'           => 'pending', 
            'total_price'      => $totalPrice, 
            'pickup_address'   => $validated['pickup_address'] ?? '-',
            'delivery_address' => $validated['delivery_address'] ?? '-',
            'notes'            => $validated['laundry_notes'] ?? null,
        ]);

        // 2. Create Order Item
        $extraStr = !empty($extraLabels) ? ' [+ ' . implode(', ', $extraLabels) . ']' : '';

        if ($isKg) {
            $itemName = 'Berat Aktual: ' . $fixedWeight . ' kg - ' . $service->name . $extraStr;
        } else {
            $itemName = $service->name . $extraStr . ' (' . $fixedQty . ' ' . trim($service->unit, '/') . ')';
        }

        OrderItem::create([
            'order_id'  => $order->id,
            'item_name' => $itemName,
            'qty'       => $isKg ? $fixedWeight : $fixedQty, 
            'price'     => $combinedUnitPrice,
        ]);

        // 3. Create Payment
        Payment::create([
            'order_id' => $order->id,
            'amount'   => 0, // Computed later for KG
            'method'   => $validated['payment_preference'],
            'status'   => 'pending',
        ]);

        // 4. Create Delivery depending on type
        if (in_array($validated['delivery_type'], ['antar_jemput', 'jemput'])) {
            $scheduledAt = null;
            if(!empty($validated['pickup_date']) && !empty($validated['pickup_time'])) {
                $scheduledAt = Carbon::parse($validated['pickup_date'] . ' ' . $validated['pickup_time']);
            } else {
                $scheduledAt = Carbon::now();
            }

            Delivery::create([
                'order_id'     => $order->id,
                'status'       => 'dijemput', // status dijemput as per requirement
                'type'         => 'pickup',
                'scheduled_at' => $scheduledAt,
                'notes'        => $validated['courier_notes'] ?? null,
            ]);
        }
        
        if (in_array($validated['delivery_type'], ['antar_jemput', 'antar'])) {
            Delivery::create([
                'order_id' => $order->id,
                'status'   => 'diantar',
                'type'     => 'delivery',
            ]);
        }

        return back()->with('success', 'Pesanan baru berhasil ditambahkan.');
    }

    public function update(Request $request, Order $order)
    {
        // For operator editing, we usually just update the main order table logic (status/price)
        $validated = $request->validate([
            'status'           => ['required', Rule::in(['pending', 'dijemput', 'diproses', 'selesai', 'diantar'])],
            'total_price'      => 'required|numeric|min:0',
        ]);

        $order->update([
            'status' => $validated['status'],
            'total_price' => $validated['total_price'],
        ]);

        // Trigger notification
        $statusLabels = [
            'diproses' => 'Pesanan Sedang Diproses',
            'selesai' => 'Pesanan Telah Selesai',
            'diantar' => 'Pesanan Sedang Diantar',
            'dijemput' => 'Pesanan Telah Dijemput',
        ];

        if (isset($statusLabels[$validated['status']])) {
            Notification::create([
                'user_id' => $order->user_id,
                'type' => 'order',
                'title' => $statusLabels[$validated['status']],
                'description' => "Status pesanan #INV-" . $order->created_at->format('Ymd') . "-" . str_pad($order->id, 4, '0', STR_PAD_LEFT) . " kini: " . strtolower($statusLabels[$validated['status']]) . ".",
                'metadata' => ['order_id' => $order->id, 'status' => $validated['status']]
            ]);
        }

        return back()->with('success', 'Status pesanan berhasil diperbarui.');
    }
}
