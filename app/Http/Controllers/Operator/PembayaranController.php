<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PembayaranController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'semua');
        $search = $request->input('search', '');
        $dateFilter = $request->input('date', '');

        // Base query for orders with payments
        $query = Order::with(['user', 'payments' => function($q) {
            $q->latest();
        }])
        ->whereHas('payments');

        if ($dateFilter) {
            $query->whereDate('created_at', $dateFilter);
        }

        if ($search) {
            $isInvoiceSearch = stripos($search, 'INV-') !== false;
            
            $query->where(function ($q) use ($search, $isInvoiceSearch) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('user', fn($u) => $u->where('phone', 'like', "%{$search}%"))
                  ->orWhere('id', 'like', "%{$search}%");
                
                if ($isInvoiceSearch) {
                    $parts = explode('-', $search);
                    $orderId = (int) end($parts);
                    if ($orderId > 0) {
                        $q->orWhere('id', $orderId);
                    }
                }
            });
        }

        $today = Carbon::today();

        $stats = [
            'semua' => Order::whereHas('payments')->count(),
            'belum_lunas' => Order::whereHas('payments', fn($q) => $q->where('status', 'menunggu'))->count(),
            'lunas_hari_ini' => Order::whereHas('payments', fn($q) => $q->where('status', 'paid')->whereDate('paid_at', $today))->count(),
            'tunai_cod' => Order::whereHas('payments', fn($q) => $q->where('status', 'menunggu')->whereIn('method', ['cash', 'cod']))->count(),
        ];

        if ($tab === 'belum-lunas') {
            $query->whereHas('payments', fn($q) => $q->where('status', 'menunggu'));
        } elseif ($tab === 'lunas-hari-ini') {
            $query->whereHas('payments', fn($q) => $q->where('status', 'paid')->whereDate('paid_at', $today));
        } elseif ($tab === 'tunai-cod') {
            $query->whereHas('payments', fn($q) => $q->where('status', 'menunggu')->whereIn('method', ['cash', 'cod']));
        }

        $query->latest();

        $orders = $query->paginate(10)->withQueryString()->through(function ($o) {
            $payment = $o->payments->first();
            return [
                'id' => $o->id,
                'invoice' => 'INV-' . $o->created_at->format('Ymd') . '-' . str_pad($o->id, 4, '0', STR_PAD_LEFT),
                'name' => $o->user->name ?? 'Pelanggan anonim',
                'phone' => $o->user->phone ?? '-',
                'time' => $o->created_at->format('d M, H:i'),
                'total_price' => (float) $o->total_price,
                'status' => $o->status,
                'payment_status' => $payment ? $payment->status : 'menunggu',
                'payment_method' => $payment ? $payment->method : 'unspecified',
                'paid_amount' => (float) ($payment ? $payment->amount : 0),
                'calculated' => $o->total_price > 0
            ];
        });

        return Inertia::render('dashboard/operator/pembayaran', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => [
                'tab' => $tab,
                'search' => $search,
                'date' => $dateFilter,
            ]
        ]);
    }

    public function processPayment(Request $request, Order $order)
    {
        $request->validate([
            'method' => 'required|string|in:cash,cod,transfer,ewallet',
            'amount_given' => 'required|numeric|min:' . $order->total_price,
            'notes' => 'nullable|string|max:1000'
        ]);

        $payment = $order->payments()->first();

        // Update payment row
        if ($payment) {
            $payment->update([
                'method' => $request->method,
                'amount' => $request->amount_given,
                'status' => 'paid',
                'paid_at' => Carbon::now(),
            ]);
        } else {
            Payment::create([
                'order_id' => $order->id,
                'method' => $request->method,
                'amount' => $request->amount_given,
                'status' => 'paid',
                'paid_at' => Carbon::now(),
            ]);
        }

        // Trigger notification
        Notification::create([
            'user_id'     => $order->user_id,
            'type'        => 'order',
            'title'       => 'Pembayaran Berhasil',
            'description' => "Pembayaran untuk pesanan #INV-" . $order->created_at->format('Ymd') . "-" . str_pad($order->id, 4, '0', STR_PAD_LEFT) . " telah dikonfirmasi oleh operator.",
            'metadata'    => ['order_id' => $order->id]
        ]);

        return back()->with('success', 'Pembayaran berhasil dikonfirmasi. (Kembalian: Rp ' . number_format($request->amount_given - $order->total_price, 0, ',', '.') . ')');
    }
}
