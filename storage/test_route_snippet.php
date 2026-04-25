Route::get('/test-midtrans-seed', function () {
    if (!app()->isLocal()) abort(403);
    $user = \App\Models\User::where('email', 'pelanggan@test.com')->first();
    if (!$user) return 'User not found';
    $order = \App\Models\Order::where('user_id', $user->id)->latest()->first();
    if (!$order) return 'No order found';
    $order->update(['total_price' => 35000]);
    \App\Models\OrderItem::where('order_id', $order->id)->update(['qty' => 5]);
    return "Done! Order #{$order->id} simulated as calculated. Total=35000, Qty=5";
});
