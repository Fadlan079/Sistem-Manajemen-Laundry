<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;
use App\Models\Order;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('role', 'pelanggan')->first();
        if (!$user) return;

        $orders = Order::where('user_id', $user->id)->limit(3)->get();
        
        // 1. Unread Today
        Notification::create([
            'user_id' => $user->id,
            'type' => 'order',
            'title' => 'Pesanan Sedang Diproses',
            'description' => 'Pakaian Anda sedang dalam tahap pencucian. Estimasi selesai besok sore.',
            'metadata' => ['order_id' => $orders[0]->id ?? null],
            'created_at' => now()->subMinutes(15),
        ]);

        Notification::create([
            'user_id' => $user->id,
            'type' => 'payment',
            'title' => 'Pembayaran Berhasil',
            'description' => 'Terima kasih! Pembayaran sebesar Rp45.000 telah kami terima.',
            'metadata' => ['order_id' => $orders[0]->id ?? null],
            'created_at' => now()->subHours(2),
        ]);

        // 2. Read Yesterday
        Notification::create([
            'user_id' => $user->id,
            'type' => 'delivery',
            'title' => 'Kurir Menuju Lokasi',
            'description' => 'Kurir Ahmad sedang dalam perjalanan untuk menjemput cucian Anda.',
            'metadata' => ['order_id' => $orders[1]->id ?? null],
            'read_at' => Carbon::yesterday()->addHours(10),
            'created_at' => Carbon::yesterday()->addHours(9),
        ]);

        // 3. Older
        Notification::create([
            'user_id' => $user->id,
            'type' => 'promo',
            'title' => 'Diskon Akhir Pekan!',
            'description' => 'Dapatkan potongan 20% untuk layanan Cuci Kering Setrika khusus hari Sabtu & Minggu.',
            'metadata' => null,
            'read_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);
    }
}
