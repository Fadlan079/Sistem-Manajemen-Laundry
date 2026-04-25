<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Models\Order;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $services = Service::all();
        $users = User::where('role', 'pelanggan')->get();
        
        if ($services->isEmpty() || $users->isEmpty()) {
            return;
        }

        foreach ($services as $service) {
            $count = rand(3, 7);
            for ($i = 0; $i < $count; $i++) {
                // Create a dummy order first to have a valid unique order_id
                $order = Order::create([
                    'user_id' => $users->random()->id,
                    'service_id' => $service->id,
                    'status' => 'selesai',
                    'total_price' => $service->price,
                    'pickup_address' => 'Alamat Dummy',
                    'delivery_address' => 'Alamat Dummy',
                ]);

                Review::create([
                    'user_id' => $order->user_id,
                    'service_id' => $service->id,
                    'order_id' => $order->id,
                    'rating' => rand(4, 5),
                    'comment' => 'Layanan yang sangat memuaskan, baju jadi bersih dan wangi.',
                ]);
            }
        }
    }
}
