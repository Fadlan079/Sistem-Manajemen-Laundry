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
            'Status',
            'Metode Pembayaran',
            'Status Pembayaran',
            'Total Harga (Rp)',
            'Tanggal & Waktu',
            'Alamat',
            'Operator',
            'Catatan',
        ];
    }

    public function map($order): array
    {
        return [
            'INV-' . $order->created_at->format('Ymd') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
            $order->user?->name ?? '-',
            $order->user?->email ?? '-',
            $order->service?->name ?? '-',
            ucfirst($order->status),
            strtoupper($order->payments->first()?->method ?? '-'),
            ucfirst($order->payments->first()?->status ?? 'Belum'),
            (float) $order->total_price,
            $order->created_at->format('d/m/Y H:i'),
            $order->pickup_address ?? '-',
            $order->operator?->name ?? 'System',
            $order->notes ?? '-',
        ];
    }
}
