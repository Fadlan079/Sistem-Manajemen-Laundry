<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OrdersTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function array(): array
    {
        return [
            [
                'Auto',
                'Budi Santoso',
                'customer@example.com',
                'Cuci Setrika',
                '5.5',
                'antar_jemput',
                'express, treatment',
                'antri',
                'cash',
                'belum',
                '0',
                now()->format('d/m/Y H:i'),
                'Jl. Raya No. 123, Jakarta',
                'Jangan pakai pewangi mawar',
                'Pagar hitam, bel ada di kiri',
                'Auto',
            ],
            [
                'Auto',
                'Siti Aminah',
                'member@gmail.com',
                'Cuci Selimut',
                '2',
                'outlet',
                '-',
                'antri',
                'transfer',
                'belum',
                '0',
                now()->format('d/m/Y H:i'),
                '-',
                'Lipat rapi',
                '-',
                'Auto',
            ],
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

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']]],
        ];
    }
}
