<?php

namespace Tests\Feature\Operator;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesananMasukTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_create_new_order()
    {
        // 1. Arrange
        $operator = User::factory()->create(['role' => 'operator']);
        $customer = User::factory()->create(['role' => 'pelanggan']);
        
        $service = Service::create([
            'name' => 'Cuci Komplit',
            'price' => 6000,
            'unit' => '/kg',
            'description' => 'Cuci dan Setrika'
        ]);

        $payload = [
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'delivery_type' => 'antar_jemput',
            'pickup_address' => 'Jl. Test No 1',
            'delivery_address' => 'Jl. Test No 1',
            'payment_preference' => 'cash',
            'weight' => 5, // 5 kg
            'extra_services' => ['express'], // 50% extra
        ];

        // 2. Act
        $response = $this->actingAs($operator)->post(route('operator.pesanan.masuk.store'), $payload);

        // 3. Assert
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'service_id' => $service->id,
            'status' => 'pending',
            // delivery fee = 10000 (antar_jemput)
            // price = 6000 + 3000 (express) = 9000/kg
            // 5 kg = 45000
            // total = 45000 + 10000 = 55000
            'total_price' => 55000,
        ]);

        $this->assertDatabaseHas('order_items', [
            'qty' => 5,
            'price' => 9000,
        ]);
    }
}
