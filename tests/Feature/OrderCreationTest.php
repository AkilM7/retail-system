<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_be_created_successfully_with_correct_totals_and_stock_deduction(): void
    {
        Queue::fake();

        // Arrange
        $toothpaste = Product::factory()->create([
            'name' => 'Colgate Toothpaste',
            'code' => 'CLG-101',
            'price' => 50.00,
            'tax_percentage' => 8.00,
            'stock' => 10,
        ]);

        $biscuit = Product::factory()->create([
            'name' => 'Parle-G Biscuit',
            'code' => 'PRL-101',
            'price' => 10.00,
            'tax_percentage' => 8.00,
            'stock' => 20,
        ]);

        $payload = [
            'customer' => [
                'name' => 'Thomas Anderson',
                'email' => 'thomas@example.com',
                'phone' => '+91 98765 43210',
            ],
            'items' => [
                ['product_id' => $toothpaste->id, 'quantity' => 2], // 2 * 50 = 100, tax 8% = 8.00 -> 108.00
                ['product_id' => $biscuit->id, 'quantity' => 5],    // 5 * 10 = 50, tax 8% = 4.00 -> 54.00
            ],
            'amount_paid' => 200.00,
        ];

        // Act
        $response = $this->postJson('/api/orders', $payload);

        // Assert HTTP response
        $response->assertStatus(201)
            ->assertJsonPath('data.customer.email', 'thomas@example.com')
            ->assertJsonPath('data.customer.name', 'Thomas Anderson')
            ->assertJsonCount(2, 'data.items');

        $this->assertEquals(150.00, (float) $response->json('data.subtotal'));
        $this->assertEquals(12.00, (float) $response->json('data.tax_amount'));
        $this->assertEquals(162.00, (float) $response->json('data.grand_total'));
        $this->assertEquals(200.00, (float) $response->json('data.amount_paid'));
        $this->assertEquals(38.00, (float) $response->json('data.change_returned'));

        // Assert database records
        $this->assertDatabaseHas('customers', [
            'email' => 'thomas@example.com',
            'name' => 'Thomas Anderson',
        ]);

        $this->assertDatabaseHas('orders', [
            'subtotal' => 150.00,
            'tax_amount' => 12.00,
            'grand_total' => 162.00,
            'amount_paid' => 200.00,
            'change_returned' => 38.00,
        ]);

        // Assert stock deduction
        $this->assertEquals(8, $toothpaste->fresh()->stock); // 10 - 2 = 8
        $this->assertEquals(15, $biscuit->fresh()->stock);   // 20 - 5 = 15

        // Assert confirmation email job dispatched
        Queue::assertPushed(SendOrderConfirmationJob::class, function ($job) use ($response) {
            return $job->order->order_number === $response->json('data.order_number');
        });
    }

    public function test_order_creation_fails_when_product_has_insufficient_stock(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'name' => 'Limited Edition Item',
            'code' => 'LTD-001',
            'price' => 100.00,
            'tax_percentage' => 18.00,
            'stock' => 2,
        ]);

        $payload = [
            'customer' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5], // requesting 5 when only 2 available
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        // Clean 422 response
        $response->assertStatus(422)
            ->assertJsonPath('error', 'INSUFFICIENT_STOCK')
            ->assertJsonPath('details.product_id', $product->id)
            ->assertJsonPath('details.available_stock', 2)
            ->assertJsonPath('details.requested_quantity', 5);

        // Ensure stock was NOT deducted
        $this->assertEquals(2, $product->fresh()->stock);

        // Ensure no order was created
        $this->assertDatabaseCount('orders', 0);

        // Ensure no job was queued
        Queue::assertNothingPushed();
    }

    public function test_order_creation_aggregates_duplicate_items_and_validates_combined_stock(): void
    {
        $product = Product::factory()->create([
            'name' => 'Bread',
            'code' => 'BRD-101',
            'price' => 40.00,
            'tax_percentage' => 5.00,
            'stock' => 3,
        ]);

        $payload = [
            'customer' => [
                'name' => 'Alice Brown',
                'email' => 'alice@example.com',
            ],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
                ['product_id' => $product->id, 'quantity' => 2], // Total 4 > stock 3
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'INSUFFICIENT_STOCK')
            ->assertJsonPath('details.requested_quantity', 4);

        $this->assertEquals(3, $product->fresh()->stock);
    }

    public function test_order_creation_rejects_missing_customer_or_empty_items(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer' => ['name' => ''],
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer.name', 'customer.email', 'items']);
    }

    public function test_order_creation_reuses_existing_customer_profile(): void
    {
        $existingCustomer = Customer::factory()->create([
            'email' => 'repeat.customer@example.com',
            'name' => 'Original Name',
        ]);

        $product = Product::factory()->create(['stock' => 10, 'price' => 50.00]);

        $payload = [
            'customer' => [
                'name' => 'Updated Name',
                'email' => 'repeat.customer@example.com',
            ],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        // Only 1 customer should exist in database
        $this->assertEquals(1, Customer::where('email', 'repeat.customer@example.com')->count());
        $this->assertEquals('Updated Name', $existingCustomer->fresh()->name);
        $this->assertDatabaseHas('orders', [
            'customer_id' => $existingCustomer->id,
        ]);
    }
}
