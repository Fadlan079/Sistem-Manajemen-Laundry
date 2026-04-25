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
        $categories = [
            [
                'name' => 'Laundry Harian',
                'slug' => 'laundry-harian',
                'description' => 'Untuk pakaian umum berbasis kg'
            ],
            [
                'name' => 'Item Satuan',
                'slug' => 'item-satuan',
                'description' => 'Untuk layanan per item (pcs/pasang)'
            ],
            [
                'name' => 'Bedding & Besar',
                'slug' => 'bedding',
                'description' => 'Untuk item besar (butuh handling khusus)'
            ],
            [
                'name' => 'Perawatan Khusus',
                'slug' => 'special-care',
                'description' => 'Untuk item sensitif / premium'
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = \App\Models\ServiceCategory::updateOrCreate(['slug' => $categoryData['slug']], $categoryData);

            if ($category->slug === 'laundry-harian') {
                $services = [
                    ['name' => 'Cuci kering', 'price' => 4500, 'unit' => '/kg'],
                    ['name' => 'Cuci lipat', 'price' => 5000, 'unit' => '/kg'],
                    ['name' => 'Cuci setrika /3kg', 'price' => 28000, 'unit' => '/3kg'],
                    ['name' => 'Cuci setrika /6kg', 'price' => 50400, 'unit' => '/6kg'],
                    ['name' => 'Setrika saja', 'price' => 6500, 'unit' => '/kg'],
                ];
            } elseif ($category->slug === 'item-satuan') {
                $services = [
                    ['name' => 'Bantal', 'price' => 10000, 'unit' => '/pcs'], // Harga asumsi, sesuaikan
                    ['name' => 'Guling', 'price' => 10000, 'unit' => '/pcs'], // Harga asumsi, sesuaikan
                    ['name' => 'Boneka', 'price' => 15000, 'unit' => '/pcs'], // Harga asumsi, sesuaikan
                    ['name' => 'Tas', 'price' => 20000, 'unit' => '/pcs'], // Harga asumsi, sesuaikan
                    ['name' => 'Sepatu', 'price' => 20000, 'unit' => '/pasang'], // Harga asumsi, sesuaikan
                    ['name' => 'Koper', 'price' => 40000, 'unit' => '/pcs'], // Harga asumsi, sesuaikan
                ];
            } elseif ($category->slug === 'bedding') {
                $services = [
                    ['name' => 'Bed cover', 'price' => 25000, 'unit' => '/pcs'], // Harga asumsi, sesuaikan
                    ['name' => 'Selimut', 'price' => 15000, 'unit' => '/pcs'], // Harga asumsi, sesuaikan
                    ['name' => 'Sprei', 'price' => 10000, 'unit' => '/pcs'], // Harga asumsi, sesuaikan
                ];
            } elseif ($category->slug === 'special-care') {
                $services = [
                    ['name' => 'Baby stroller car seat', 'price' => 50000, 'unit' => '/pcs'], // Harga asumsi, sesuaikan
                ];
            } else {
                $services = [];
            }

            foreach ($services as $service) {
                Service::updateOrCreate(
                    ['name' => $service['name']],
                    [
                        'category_id' => $category->id,
                        'category' => $category->name, // Keeping the string column if still needed, can be removed if strictly using category_id
                        'price' => $service['price'],
                        'unit' => $service['unit'],
                        'status' => 'tersedia'
                    ]
                );
            }
        }
    }
}
