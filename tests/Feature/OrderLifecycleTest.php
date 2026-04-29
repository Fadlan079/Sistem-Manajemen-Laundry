<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_to_end_order_lifecycle()
    {
        // 1. Arrange: Setup roles and service
        $operator = User::factory()->create(['role' => 'operator']);
        $courier = User::factory()->create(['role' => 'kurir']);
        $customer = User::factory()->create(['role' => 'pelanggan']);
        
        $service = Service::create([
            'name' => 'Cuci Kiloan',
            'price' => 5000,
            'unit' => '/kg',
            'description' => 'Cuci biasa'
        ]);

        // ==========================================
        // STEP 1: Order Creation (By Operator)
        // ==========================================
        $payload = [
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'delivery_type' => 'antar_jemput',
            'pickup_address' => 'Jl. Test No 1',
            'delivery_address' => 'Jl. Test No 1',
            'payment_preference' => 'cash',
            // Notice: We don't provide exact weight initially, maybe just 0 or null 
            // but the controller might require it or not. We'll pass 1 just in case, but overwrite it later during pickup.
            'weight' => 1, 
        ];

        $response = $this->actingAs($operator)->post(route('operator.pesanan.masuk.store'), $payload);
        $response->assertRedirect();
        
        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals('pending', $order->status);

        // Verify that 2 deliveries (pickup and delivery) were created
        $this->assertEquals(2, $order->deliveries()->count());
        $pickupDelivery = $order->deliveries()->where('type', 'pickup')->first();
        $dropoffDelivery = $order->deliveries()->where('type', 'delivery')->first();
        
        $this->assertNotNull($pickupDelivery);
        $this->assertNotNull($dropoffDelivery);

        // ==========================================
        // STEP 2: Assign Pickup Courier
        // ==========================================
        $assignPickupPayload = [
            'is_external' => false,
            'courier_id' => $courier->id,
        ];
        $response = $this->put(route('operator.penjemputan.assign', $pickupDelivery->id), $assignPickupPayload);
        $response->assertRedirect();
        
        // Order status should change to 'dijemput'
        $order->refresh();
        $this->assertEquals('dijemput', $order->status);

        // ==========================================
        // STEP 3: Complete Pickup and Update Weight
        // ==========================================
        // Actual weight is 5 kg. 5kg * 5000 = 25000. 
        // Delivery fee for antar_jemput is 10000. Total = 35000.
        $pickupCompletePayload = [
            'kg' => 5,
        ];
        $response = $this->put(route('operator.penjemputan.dijemput', $pickupDelivery->id), $pickupCompletePayload);
        $response->assertRedirect();

        $pickupDelivery->refresh();
        $this->assertEquals('selesai', $pickupDelivery->status);

        // Order status should be 'diproses'
        $order->refresh();
        $this->assertEquals('diproses', $order->status);
        $this->assertEquals(35000, $order->total_price);

        // ==========================================
        // STEP 4: Process Order to Selesai (Ready to deliver)
        // ==========================================
        // Usually operators mark it as done using pesanan.masuk.update
        $processCompletePayload = [
            'status' => 'selesai',
            'total_price' => $order->total_price,
        ];
        $response = $this->put(route('operator.pesanan.masuk.update', $order->id), $processCompletePayload);
        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('selesai', $order->status);

        // ==========================================
        // STEP 5: Assign Delivery Courier
        // ==========================================
        $assignDropoffPayload = [
            'is_external' => false,
            'courier_id' => $courier->id,
        ];
        $response = $this->put(route('operator.pengantaran.assign', $dropoffDelivery->id), $assignDropoffPayload);
        $response->assertRedirect();

        // Order status becomes 'diantar'
        $order->refresh();
        $this->assertEquals('diantar', $order->status);

        // ==========================================
        // STEP 6: Mark as Delivered
        // ==========================================
        $response = $this->put(route('operator.pengantaran.diantar', $dropoffDelivery->id), []);
        $response->assertRedirect();

        $dropoffDelivery->refresh();
        $this->assertEquals('selesai', $dropoffDelivery->status);

        // Final Order status should be 'selesai'
        $order->refresh();
        $this->assertEquals('selesai', $order->status);
    }
}
