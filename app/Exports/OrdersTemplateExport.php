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
                'customer@example.com',
                'Cuci Setrika',
                '5.5',
                'antar_jemput',
                'Jl. Raya No. 123, Jakarta',
                'Jangan pakai pewangi mawar',
            ],
            [
                'member@gmail.com',
                'Cuci Selimut',
                '2',
                'outlet',
                '-',
                'Lipat rapi',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Email',
            'Layanan',
            'Jumlah_atau_Berat',
            'Tipe_Pengiriman',
            'Alamat',
            'Catatan',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']]],
        ];
    }
}
