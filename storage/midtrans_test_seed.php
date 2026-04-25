<?php

// Script to simulate a finalized order for Midtrans testing
// Run: php artisan tinker < storage/midtrans_test_seed.php

$user = App\Models\User::where('email', 'pelanggan@test.com')->first();
if (!$user) {
    echo "User not found\n";
    exit;
}

$order = App\Models\Order::where('user_id', $user->id)->latest()->first();
if (!$order) {
    echo "No order found\n";
    exit;
}

// Simulate courier has weighed and finalized price
$order->update(['total_price' => 35000]);
App\Models\OrderItem::where('order_id', $order->id)->update(['qty' => 5]);

echo "Done! Order #{$order->id} simulated as calculated:\n";
echo " - total_price: 35000\n";
echo " - qty: 5 kg\n";
