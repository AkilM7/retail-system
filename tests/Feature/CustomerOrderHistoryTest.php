<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_customer_order_history_by_email(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'history.tester@example.com',
            'name' => 'History Tester',
        ]);

        $product = Product::factory()->create([
            'name' => 'Test Item',
            'price' => 50.00,
            'tax_percentage' => 10.00,
        ]);

        // Create 2 orders for this customer
        $order1 = Order::factory()->create([
            'customer_id' => $customer->id,
            'subtotal' => 100.00,
            'tax_amount' => 10.00,
            'grand_total' => 110.00,
            'created_at' => now()->subDay(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order1->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 50.00,
            'quantity' => 2,
            'line_subtotal' => 100.00,
            'line_tax' => 10.00,
            'line_total' => 110.00,
        ]);

        $order2 = Order::factory()->create([
            'customer_id' => $customer->id,
            'subtotal' => 50.00,
            'tax_amount' => 5.00,
            'grand_total' => 55.00,
            'created_at' => now(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order2->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 50.00,
            'quantity' => 1,
            'line_subtotal' => 50.00,
            'line_tax' => 5.00,
            'line_total' => 55.00,
        ]);

        // Query the API endpoint
        $response = $this->getJson('/api/customers/' . urlencode('history.tester@example.com') . '/orders');

        $response->assertStatus(200)
            ->assertJsonPath('customer.email', 'history.tester@example.com')
            ->assertJsonPath('total_orders', 2)
            ->assertJsonCount(2, 'data');

        // Verify order items are embedded
        $this->assertNotEmpty($response->json('data.0.items'));
    }

    public function test_customer_order_history_returns_404_for_unknown_customer(): void
    {
        $response = $this->getJson('/api/customers/nonexistent@example.com/orders');

        $response->assertStatus(404)
            ->assertJsonPath('message', "Customer with email 'nonexistent@example.com' not found.");
    }
}
