<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;

class ServiceSeeder extends Seeder
{
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

        // Mapping discount (lebih scalable)
        $discountMap = [
            'sepatu' => ['day' => 'Monday', 'percent' => 20],
            'bantal' => ['day' => 'Tuesday', 'percent' => 20],
            'guling' => ['day' => 'Tuesday', 'percent' => 20],
            'boneka' => ['day' => 'Wednesday', 'percent' => 20],
            'cuci lipat' => ['day' => 'Thursday', 'percent' => 20],
            'baby stroller car seat' => ['day' => 'Friday', 'percent' => 20],
            'koper' => ['day' => 'Saturday', 'percent' => 20],
            'bed cover' => ['day' => 'Sunday', 'percent' => 20],
            'selimut' => ['day' => 'Sunday', 'percent' => 20],
            'sprei' => ['day' => 'Sunday', 'percent' => 20],
        ];

        // Master services (lebih clean)
        $servicesByCategory = [
            'laundry-harian' => [
                [
                    'name' => 'Cuci kering',
                    'price' => 4500,
                    'unit' => '/kg',
                    'description' => 'Pakaian dicuci bersih lalu dikeringkan tanpa setrika.'
                ],
                [
                    'name' => 'Cuci lipat',
                    'price' => 5000,
                    'unit' => '/kg',
                    'description' => 'Pakaian dicuci, dikeringkan, lalu dilipat rapi.'
                ],
                [
                    'name' => 'Cuci setrika',
                    'price' => 10000,
                    'unit' => '/kg',
                    'description' => 'Cuci, kering, dan setrika hingga rapi.'
                ],
                [
                    'name' => 'Setrika saja',
                    'price' => 6500,
                    'unit' => '/kg',
                    'description' => 'Hanya layanan setrika untuk pakaian bersih.'
                ],
            ],

            'item-satuan' => [
                ['name' => 'Bantal', 'price' => 10000, 'unit' => '/pcs', 'description' => 'Membersihkan bantal dari debu dan bau.'],
                ['name' => 'Guling', 'price' => 10000, 'unit' => '/pcs', 'description' => 'Cuci guling hingga bersih dan segar.'],
                ['name' => 'Boneka', 'price' => 15000, 'unit' => '/pcs', 'description' => 'Perawatan boneka agar tetap higienis.'],
                ['name' => 'Tas', 'price' => 20000, 'unit' => '/pcs', 'description' => 'Membersihkan tas dari noda dan kotoran.'],
                ['name' => 'Sepatu', 'price' => 20000, 'unit' => '/pasang', 'description' => 'Cuci sepatu agar bersih dan nyaman.'],
                ['name' => 'Koper', 'price' => 40000, 'unit' => '/pcs', 'description' => 'Membersihkan koper dari debu dan noda.'],
            ],

            'bedding' => [
                ['name' => 'Bed cover', 'price' => 25000, 'unit' => '/pcs', 'description' => 'Cuci bed cover agar bersih dan higienis.'],
                ['name' => 'Selimut', 'price' => 15000, 'unit' => '/pcs', 'description' => 'Membersihkan selimut agar nyaman digunakan.'],
                ['name' => 'Sprei', 'price' => 10000, 'unit' => '/pcs', 'description' => 'Cuci sprei agar tetap bersih dan segar.'],
            ],

            'special-care' => [
                ['name' => 'Baby stroller car seat', 'price' => 50000, 'unit' => '/pcs', 'description' => 'Membersihkan stroller & car seat bayi.'],
            ]
        ];

        foreach ($categories as $categoryData) {
            $category = ServiceCategory::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );

            $services = $servicesByCategory[$category->slug] ?? [];

            foreach ($services as $service) {

                $nameLower = strtolower($service['name']);
                $discount = $discountMap[$nameLower] ?? null;

                Service::updateOrCreate(
                    ['name' => $service['name']],
                    [
                        'category_id' => $category->id,
                        'category' => $category->name,
                        'price' => $service['price'],
                        'unit' => $service['unit'],
                        'status' => 'tersedia',
                        'estimate' => '1-2 Hari',
                        'description' => $service['description'],
                        'discount_day' => $discount['day'] ?? null,
                        'discount_percentage' => $discount['percent'] ?? 0,
                    ]
                );
            }
        }
    }
}
