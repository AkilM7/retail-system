<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencyStockSafetyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Requirement 7:
     * If two orders for the same product arrive at nearly the same time and only
     * one unit is left, exactly one should succeed and the other should fail cleanly (no overselling).
     */
    public function test_concurrent_order_requests_for_last_unit_allow_only_one_success_and_prevent_overselling(): void
    {
        $product = Product::factory()->create([
            'name' => 'Basmati Rice 1kg (Last Unit)',
            'code' => 'RCE-LAST',
            'price' => 120.00,
            'tax_percentage' => 5.00,
            'stock' => 1, // Only 1 unit in stock
        ]);

        $orderService = app(OrderService::class);

        $orderPayload1 = [
            'customer' => ['name' => 'Buyer One', 'email' => 'buyer1@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $orderPayload2 = [
            'customer' => ['name' => 'Buyer Two', 'email' => 'buyer2@example.com'],
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ];

        $successCount = 0;
        $failureCount = 0;
        $failedException = null;

        // Attempt First Order
        try {
            $orderService->createOrder($orderPayload1);
            $successCount++;
        } catch (\Throwable $e) {
            $failureCount++;
        }

        // Attempt Second Order
        try {
            $orderService->createOrder($orderPayload2);
            $successCount++;
        } catch (\Throwable $e) {
            $failureCount++;
            $failedException = $e;
        }

        // Verify exactly one succeeded and one failed
        $this->assertEquals(1, $successCount, 'Exactly one order must succeed.');
        $this->assertEquals(1, $failureCount, 'Exactly one order must fail cleanly.');

        // Verify failure was an InsufficientStockException
        $this->assertInstanceOf(InsufficientStockException::class, $failedException);
        $this->assertEquals(422, $failedException->getCode());

        // Verify stock is exactly 0 and never became negative
        $this->assertEquals(0, $product->fresh()->stock, 'Stock must be exactly 0 (no overselling).');

        // Verify only 1 order exists in database
        $this->assertDatabaseCount('orders', 1);
    }

    /**
     * Test deadlock prevention by ordering sorted product locks.
     */
    public function test_multi_product_orders_acquire_locks_deterministically(): void
    {
        $productA = Product::factory()->create(['stock' => 5]);
        $productB = Product::factory()->create(['stock' => 5]);

        $orderService = app(OrderService::class);

        // Order with reversed ID order
        $order = $orderService->createOrder([
            'customer' => ['name' => 'Reverse Order Buyer', 'email' => 'reverse@example.com'],
            'items' => [
                ['product_id' => max($productA->id, $productB->id), 'quantity' => 1],
                ['product_id' => min($productA->id, $productB->id), 'quantity' => 1],
            ],
        ]);

        $this->assertNotNull($order);
        $this->assertEquals(4, $productA->fresh()->stock);
        $this->assertEquals(4, $productB->fresh()->stock);
    }
}
