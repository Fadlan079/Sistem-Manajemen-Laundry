<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Cuci Kiloan',
                'category' => 'Kiloan',
                'price' => 8000,
                'estimate' => '2-3 Hari',
                'status' => 'tersedia',
                'description' => 'Pilihan praktis & hemat untuk pakaian harian keluarga Anda.',
                'icon' => 'fa-solid fa-shirt',
                'features' => [
                    'Cuci + Kering + Lipat',
                    'Estimasi 2-3 Hari',
                    'Higienis Per Pelanggan'
                ],
                'unit' => '/kg',
                'tag' => null,
            ],
            [
                'name' => 'Cuci Satuan',
                'category' => 'Satuan',
                'price' => 15000,
                'estimate' => '3-4 Hari',
                'status' => 'tersedia',
                'description' => 'Penanganan khusus untuk pakaian berharga & material sensitif.',
                'icon' => 'fa-solid fa-user-tie',
                'features' => [
                    'Jas, Dress, Kebaya, dll',
                    'Teknik Finishing Khusus',
                    'Packing Premium'
                ],
                'unit' => '/pcs',
                'tag' => null,
            ],
            [
                'name' => 'Bedcover & Karpet',
                'category' => 'Satuan',
                'price' => 25000,
                'estimate' => '4-5 Hari',
                'status' => 'tersedia',
                'description' => 'Membersihkan debu & tungau pada perlengkapan tidur Anda.',
                'icon' => 'fa-solid fa-rug',
                'features' => [
                    'Deep Cleaning Method',
                    'Sterilisasi Kuman',
                    'Wangi Extra Segar'
                ],
                'unit' => '/pcs',
                'tag' => null,
            ],
            [
                'name' => 'Express Service',
                'category' => 'Kiloan',
                'price' => 15000,
                'estimate' => '6-12 Jam',
                'status' => 'tersedia', // status 'tersedia' agar muncul di landing page
                'description' => 'Solusi cerdas saat Anda butuh pakaian bersih dalam waktu singkat.',
                'icon' => 'fa-solid fa-bolt',
                'features' => [
                    'Selesai Same-Day',
                    'Prioritas Utama',
                    'Pick-up Segera'
                ],
                'unit' => '/kg',
                'tag' => 'Cepat',
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['name' => $service['name']], $service);
        }
    }
}
