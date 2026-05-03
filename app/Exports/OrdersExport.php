<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $orders;

    public function __construct($orders)
    {
        $this->orders = $orders;
    }

    public function collection()
    {
        return $this->orders;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row (header) as bold
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function headings(): array
    {
        return [
            'Invoice',
            'Pelanggan',
            'Email',
            'Layanan',
            'Berat/Qty',
            'Tipe Pengiriman',
            'Layanan Extra',
            'Status',
            'Metode Pembayaran',
            'Status Pembayaran',
            'Total Harga (Rp)',
            'Tanggal & Waktu',
            'Alamat',
            'Catatan Laundry',
            'Catatan Kurir',
            'Operator',
        ];
    }

    public function map($order): array
    {
        $item = $order->orderItems->first();
        $isKg = in_array(strtolower($order->service?->unit ?? ''), ['/kg', 'kg']);
        
        // Format Extra Services
        $extras = $item ? $item->extras->pluck('label')->implode(', ') : '-';
        if (empty($extras)) $extras = '-';

        // Get Courier Notes (from pickup or delivery)
        $courierNotes = $order->deliveries->pluck('notes')->filter()->first() ?? '-';

        // Get Delivery Type
        $hasPickup = $order->deliveries->where('type', 'pickup')->isNotEmpty();
        $hasDelivery = $order->deliveries->where('type', 'delivery')->isNotEmpty();
        $deliveryType = 'outlet';
        if ($hasPickup && $hasDelivery) $deliveryType = 'antar_jemput';
        elseif ($hasPickup) $deliveryType = 'jemput';
        elseif ($hasDelivery) $deliveryType = 'antar';

        return [
            'INV-' . $order->created_at->format('Ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
            $order->user?->name ?? '-',
            $order->user?->email ?? '-',
            $order->service?->name ?? '-',
            $item ? ($isKg ? ($item->actual_weight ?? $item->qty) . ' Kg' : $item->qty . ' Pcs') : '-',
            $deliveryType,
            $extras,
            ucfirst($order->status),
            strtoupper($order->payments->first()?->method ?? '-'),
            ucfirst($order->payments->first()?->status ?? 'Belum'),
            (float) $order->total_price,
            $order->created_at->format('d/m/Y H:i'),
            $order->pickup_address ?? '-',
            $order->notes ?? '-',
            $courierNotes,
            $order->operator?->name ?? 'System',
        ];
    }
}
