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
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;
use App\Exports\OrdersTemplateExport;
use App\Imports\OrdersImport;

class PesananMasukController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $status = $request->input('status', '');
        $date = $request->input('date', '');
        $reportMode = $request->input('reportMode', 'harian');
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $query = Order::with(['user', 'service', 'payments', 'orderItems', 'deliveries', 'operator'])
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
            ->when($reportMode === 'harian' && $date, fn($q) => $q->whereDate('created_at', $date))
            ->when($reportMode === 'bulanan', fn($q) => $q->whereYear('created_at', $year)->whereMonth('created_at', $month))
            ->when($reportMode === 'tahunan', fn($q) => $q->whereYear('created_at', $year))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest();

        $orders = $query->paginate(10)->withQueryString()->through(function ($o) {
            $item = $o->orderItems->first();
            $dists = $o->deliveries->count();
            // Assume 2 deliveries = 10k, 1 = 5k
            $fee = $dists * 5000;

            return [
                'id' => $o->id,
                'invoice' => 'INV-' . $o->created_at->format('Ymd') . '-' . str_pad($o->id, 4, '0', STR_PAD_LEFT),
                'customer' => $o->user?->name ?? '-',
                'customer_email' => $o->user?->email ?? '-',
                'user_id' => $o->user_id,
                'service' => $o->service?->name ?? '-',
                'service_id' => $o->service_id,
                'status' => $o->status,
                'total_price' => (float) $o->total_price,
                'pickup_address' => $o->pickup_address,
                'delivery_address' => $o->delivery_address,
                'payment_status' => $o->payments->first()?->status ?? 'belum',
                'payment_method' => $o->payments->first()?->method ?? '-',
                'date' => $o->created_at->format('d M Y'),
                'time' => $o->created_at->format('H:i'),
                // Breakdown fields
                'items_qty' => (float) ($item?->qty ?? 0),
                'service_price' => (float) ($item?->price ?? ($o->service?->price ?? 0)),
                'unit' => $o->service?->unit ?? '/kg',
                'fee' => (float) $fee,
                'isKg' => in_array(strtolower($o->service?->unit ?? ''), ['/kg', 'kg']),
                'actual_weight' => (float) ($item?->actual_weight ?? 0),
                'laundry_notes' => $o->notes,
                'hasPickup' => $o->deliveries->where('type', 'pickup')->isNotEmpty(),
                'hasDelivery' => $o->deliveries->where('type', 'delivery')->isNotEmpty(),
                'courier_id' => $o->deliveries->where('type', 'pickup')->first()?->courier_id ?? $o->deliveries->where('type', 'delivery')->first()?->courier_id,
                'external_courier_name' => $o->deliveries->where('type', 'pickup')->first()?->external_courier_name ?? $o->deliveries->where('type', 'delivery')->first()?->external_courier_name,
                'created_at' => $o->created_at->toIso8601String(),
                'operator_name' => $o->operator?->name ?? 'System',
            ];
        });

        $stats = [
            'total' => Order::count(),
            'selesai' => Order::where('status', 'selesai')->count(),
            'diproses' => Order::where('status', 'diproses')->count(),
            'menunggu' => Order::whereIn('status', ['dibuat', 'antri', 'dijemput'])->count(),
            'diterima' => Order::where('status', 'diterima')->count(),
            'antri' => Order::where('status', 'antri')->count(),
            'dibatalkan' => Order::where('status', 'dibatalkan')->count(),
            'siap_jemput' => Order::where('status', 'dibuat')->whereHas('deliveries', fn($q) => $q->where('type', 'pickup'))->count(),
            'siap_antar' => Order::where('status', 'selesai')->whereHas('deliveries', fn($q) => $q->where('type', 'delivery'))->count(),
        ];

        $statusDist = [
            Order::where('status', 'selesai')->count(),
            Order::where('status', 'diproses')->count(),
            Order::where('status', 'dibuat')->count(),
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
            'id' => $s->id,
            'name' => $s->name,
            'price' => (float) $s->price,
            'unit' => $s->unit ?? '/kg',
            'description' => $s->description,
        ]);

        $customerList = User::where('role', 'pelanggan')->select('id', 'name', 'email')->get();
        $courierList = User::where('role', 'kurir')->select('id', 'name')->get();

        return Inertia::render('dashboard/operator/PesananMasuk', [
            'orders' => $orders,
            'stats' => $stats,
            'couriers' => $courierList,
            'filters' => [
                'search' => $search, 
                'status' => $status, 
                'date' => $date,
                'reportMode' => $reportMode,
                'month' => (int)$month,
                'year' => (int)$year
            ],
            'services' => $serviceList,
            'customers' => $customerList,
            'chartData' => [
                'statusDist' => $statusDist,
                'weeklyTrend' => $weeklyTrend,
                'paymentMethods' => $paymentMethods,
                'topServices' => $topServicesChart,
                'serviceNames' => $services->pluck('name'),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $date = $request->input('date');
        $reportMode = $request->input('reportMode', 'harian');
        $month = $request->input('month');
        $year = $request->input('year');

        $query = Order::with(['user', 'service', 'payments', 'operator', 'orderItems.extras', 'deliveries'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            })
            ->when($reportMode === 'harian' && $date, fn($q) => $q->whereDate('created_at', $date))
            ->when($reportMode === 'bulanan', fn($q) => $q->whereYear('created_at', $year)->whereMonth('created_at', $month))
            ->when($reportMode === 'tahunan', fn($q) => $q->whereYear('created_at', $year))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest();

        $orders = $query->get();
        
        $filename = 'laporan-pesanan-' . $reportMode . '-' . now()->format('YmdHis') . '.xlsx';
        return Excel::download(new OrdersExport($orders), $filename);
    }

    public function downloadTemplate()
    {
        return Excel::download(new OrdersTemplateExport, 'template-import-pesanan.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $import = new OrdersImport;
        Excel::import($import, $request->file('file'));

        $report = $import->getImportReport();
        
        if (count($report['errors']) > 0) {
            $msg = "Import selesai dengan " . $report['success_count'] . " data berhasil. ";
            $msg .= "Namun ada beberapa kendala: " . implode(', ', array_unique($report['errors']));
            return back()->with('warning', $msg);
        }

        return back()->with('success', 'Berhasil mengimport ' . $report['success_count'] . ' data pesanan.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'service_id' => 'required|exists:services,id',
            'delivery_type' => 'required|in:antar_jemput,jemput,antar,outlet',
            'pickup_address' => 'nullable|string',
            'delivery_address' => 'nullable|string',
            'pickup_date' => 'nullable|date',
            'pickup_time' => 'nullable|date_format:H:i',
            'laundry_notes' => 'nullable|string',
            'courier_notes' => 'nullable|string',
            'payment_preference' => 'required',
            'weight' => 'nullable|numeric|min:0.1',
            'item_qty' => 'nullable|numeric|min:1',
            'extra_services' => 'nullable|array',
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $user = tap(User::find($validated['user_id']), fn($u) => $u ?: abort(404));

        $deliveryFee = match ($validated['delivery_type']) {
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
        $extraList = [];

        if (!empty($validated['extra_services'])) {
            if (in_array('express', $validated['extra_services'])) {
                $extraPrice = $baseServicePrice * 0.5;
                $extraPricing += $extraPrice;
                $extraList[] = ['type' => 'express', 'label' => 'Express (24 Jam)', 'price' => $extraPrice];
            }
            if (in_array('treatment', $validated['extra_services'])) {
                $extraPrice = $isKg ? 2000 : 5000;
                $extraPricing += $extraPrice;
                $extraList[] = ['type' => 'treatment', 'label' => 'Treatment Khusus', 'price' => $extraPrice];
            }
            if (in_array('own_perfume', $validated['extra_services'])) {
                $extraList[] = ['type' => 'own_perfume', 'label' => 'Pewangi Sendiri', 'price' => 0];
            }
        }

        $combinedUnitPrice = $baseServicePrice + $extraPricing;

        // Operator input is always final (actual weight or fixed qty)
        if ($isKg && !empty($validated['weight'])) {
            $fixedWeight = $validated['weight'];
            $totalPrice += ($fixedWeight * $combinedUnitPrice);
        } elseif (!$isKg && !empty($validated['item_qty'])) {
            $fixedQty = $validated['item_qty'];
            $totalPrice += ($fixedQty * $combinedUnitPrice);
        }

        // 1. Create Order
        $order = Order::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => 'dibuat',
            'total_price' => $totalPrice,
            'pickup_address' => $validated['pickup_address'] ?? '-',
            'delivery_address' => $validated['delivery_address'] ?? '-',
            'notes' => $validated['laundry_notes'] ?? null,
            'operator_id' => auth()->id(),
        ]);

        // 2. Create Order Item with new structure (operator always inputs actual weight/qty)
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'item_name' => $service->name, // Clean name
            'qty' => $isKg ? $fixedWeight : $fixedQty,
            // actual_weight = weight set directly by operator (already weighed)
            'actual_weight' => $isKg ? $fixedWeight : null,
            'price' => $baseServicePrice,
        ]);

        foreach ($extraList as $ext) {
            $orderItem->extras()->create($ext);
        }

        // 3. Create Payment
        Payment::create([
            'order_id' => $order->id,
            'amount' => 0, // Computed later for KG
            'method' => $validated['payment_preference'],
            'status' => 'menunggu',
        ]);

        // 4. Create Delivery depending on type
        if (in_array($validated['delivery_type'], ['antar_jemput', 'jemput'])) {
            $scheduledAt = null;
            if (!empty($validated['pickup_date']) && !empty($validated['pickup_time'])) {
                $scheduledAt = Carbon::parse($validated['pickup_date'] . ' ' . $validated['pickup_time']);
            } else {
                $scheduledAt = Carbon::now();
            }

            Delivery::create([
                'order_id' => $order->id,
                'status' => 'dijemput', // status dijemput as per requirement
                'type' => 'pickup',
                'scheduled_at' => $scheduledAt,
                'notes' => $validated['courier_notes'] ?? null,
            ]);
        }

        if (in_array($validated['delivery_type'], ['antar_jemput', 'antar'])) {
            Delivery::create([
                'order_id' => $order->id,
                'status' => 'diantar',
                'type' => 'delivery',
            ]);
        }

        return back()->with('success', 'Pesanan baru berhasil ditambahkan.');
    }

    public function update(Request $request, Order $order)
    {
        // For operator editing, we usually just update the main order table logic (status/price)
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['dibuat', 'antri', 'dijemput', 'diproses', 'selesai', 'diantar', 'diterima', 'dibatalkan']),
                new \App\Rules\ValidOrderStatusTransition($order->status)
            ],
            'total_price' => 'required|numeric|min:0',
            'actual_weight' => 'nullable|numeric|min:0.01',
            'courier_type' => 'nullable|in:internal,external',
            'courier_id' => 'nullable|exists:users,id',
            'external_courier_name' => 'nullable|string|max:100',
            'external_courier_phone' => 'nullable|string|max:20',
        ]);

        $order->update([
            'status' => $validated['status'],
            'total_price' => $validated['total_price'],
            'operator_id' => auth()->id(),
        ]);

        // Handle weight update if provided
        if (($validated['actual_weight'] ?? null)) {
            $item = $order->orderItems->first();
            if ($item) {
                $item->update(['actual_weight' => $validated['actual_weight']]);
                
                // Recalculate total price if it's a KG service
                $isKg = in_array(strtolower($order->service?->unit ?? ''), ['/kg', 'kg']);
                if ($isKg) {
                    $dists = $order->deliveries->count();
                    $fee = $dists * 5000;
                    $newTotal = $fee + ($validated['actual_weight'] * $item->price);
                    
                    $order->update(['total_price' => $newTotal]);
                }
            }
        }

        // Handle courier assignment if status is changing to 'antri' (assigning for pickup) or 'selesai' (assigning for delivery)
        // or if explicitly provided.
        if (($validated['courier_type'] ?? null)) {
            $type = in_array($validated['status'], ['antri', 'dijemput']) ? 'pickup' : 'delivery';
            
            $delivery = Delivery::where('order_id', $order->id)
                ->where('type', $type)
                ->first();

            if ($delivery) {
                $delivery->update([
                    'courier_id' => $validated['courier_type'] === 'internal' ? ($validated['courier_id'] ?? null) : null,
                    'external_courier_name' => $validated['courier_type'] === 'external' ? ($validated['external_courier_name'] ?? null) : null,
                    'external_courier_phone' => $validated['courier_type'] === 'external' ? ($validated['external_courier_phone'] ?? null) : null,
                ]);
            }
        }

        // Clear snap_token if price changed to force regeneration
        $order->payments()->update(['snap_token' => null]);

        if ($request->filled('actual_weight')) {
            OrderItem::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'service_id' => $order->service_id,
                    'qty' => $request->actual_weight,
                    'actual_weight' => $request->actual_weight,
                    'price' => $order->service?->price ?? 0,
                    'item_name' => $order->service?->name ?? 'Laundry Item'
                ]
            );
        }

        // Trigger notification
        $statusLabels = [
            'diproses' => 'Pesanan Sedang Diproses',
            'selesai' => 'Pesanan Telah Selesai',
            'diantar' => 'Pesanan Sedang Diantar',
            'dijemput' => 'Pesanan Telah Dijemput',
            'diterima' => 'Pesanan Telah Diterima',
            'dibatalkan' => 'Pesanan Dibatalkan',
        ];

        if (isset($statusLabels[$validated['status']])) {
            $type = $validated['status'] === 'dibatalkan' ? 'cancel' : 'order';
            Notification::create([
                'user_id' => $order->user_id,
                'type' => $type,
                'title' => $statusLabels[$validated['status']],
                'description' => "Status pesanan #INV-" . $order->created_at->format('Ymd') . "-" . str_pad($order->id, 4, '0', STR_PAD_LEFT) . " kini: " . strtolower($statusLabels[$validated['status']]) . ".",
                'metadata' => ['order_id' => $order->id, 'status' => $validated['status']]
            ]);
        }

        return back()->with('success', 'Status pesanan berhasil diperbarui.');
    }
}
