<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    private $operator;
    private $customer;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->operator = User::factory()->create(['role' => 'operator']);
        $this->customer = User::factory()->create(['role' => 'pelanggan']);
        
        $this->service = Service::create([
            'name' => 'Cuci Kiloan',
            'price' => 5000,
            'unit' => '/kg',
            'description' => 'Cuci biasa'
        ]);
    }

    private function createOrder($status)
    {
        return Order::create([
            'user_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'status' => $status,
            'total_price' => 10000,
            'pickup_address' => 'Jl. Test No 1',
            'delivery_address' => 'Jl. Test No 1',
        ]);
    }

    /**
     * Test allowed order state transitions.
     */
    public function test_valid_status_transitions()
    {
        // Define valid transitions (forward direction)
        $validTransitions = [
            ['from' => 'pending', 'to' => 'dijemput'],
            ['from' => 'dijemput', 'to' => 'diproses'],
            ['from' => 'diproses', 'to' => 'selesai'],
            ['from' => 'selesai', 'to' => 'diantar'],
            ['from' => 'diantar', 'to' => 'selesai'], // Delivered state
            ['from' => 'pending', 'to' => 'dibatalkan'], // Cancellation is valid from pending
        ];

        foreach ($validTransitions as $transition) {
            $order = $this->createOrder($transition['from']);

            $response = $this->actingAs($this->operator)
                ->put(route('operator.pesanan.masuk.update', $order->id), [
                    'status' => $transition['to'],
                    'total_price' => $order->total_price,
                ]);

            $response->assertSessionHasNoErrors();
            
            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => $transition['to'],
            ]);
        }
    }

    /**
     * Test invalid order state transitions are rejected.
     */
    public function test_invalid_status_transitions_are_rejected()
    {
        // Define invalid transitions (skipping states or moving backwards)
        $invalidTransitions = [
            ['from' => 'pending', 'to' => 'selesai'],    // Skip all
            ['from' => 'dijemput', 'to' => 'pending'],   // Backward
            ['from' => 'diproses', 'to' => 'pending'],   // Backward
            ['from' => 'diproses', 'to' => 'dijemput'],  // Backward
            ['from' => 'selesai', 'to' => 'diproses'],   // Backward
            ['from' => 'diantar', 'to' => 'diproses'],   // Backward
            ['from' => 'dibatalkan', 'to' => 'pending'], // Cannot un-cancel
        ];

        foreach ($invalidTransitions as $transition) {
            $order = $this->createOrder($transition['from']);

            $response = $this->actingAs($this->operator)
                ->put(route('operator.pesanan.masuk.update', $order->id), [
                    'status' => $transition['to'],
                    'total_price' => $order->total_price,
                ]);

            // We expect the system to reject this with a validation error on 'status'
            // or return a 403 Forbidden / 422 Unprocessable Entity
            // For a typical Laravel form request, it should have a session error
            $response->assertSessionHasErrors(['status']);

            // Assert that the database remains unchanged
            $this->assertDatabaseHas('orders', [
                'id' => $order->id,
                'status' => $transition['from'],
            ]);
        }
    }
}
