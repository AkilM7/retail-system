<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_resolution_creates_new_customer_when_not_found(): void
    {
        $service = app(OrderService::class);

        $customer = $service->resolveCustomer([
            'email' => 'NEW.CUSTOMER@EXAMPLE.COM ',
            'name' => ' Jane Smith ',
            'phone' => '1234567890',
        ]);

        $this->assertEquals('new.customer@example.com', $customer->email);
        $this->assertEquals('Jane Smith', $customer->name);
        $this->assertEquals('1234567890', $customer->phone);
    }

    public function test_order_service_computes_exact_tax_and_totals(): void
    {
        Queue::fake();

        $product1 = Product::factory()->create([
            'price' => 199.99,
            'tax_percentage' => 18.00,
            'stock' => 10,
        ]);

        $product2 = Product::factory()->create([
            'price' => 49.50,
            'tax_percentage' => 5.00,
            'stock' => 10,
        ]);

        $service = app(OrderService::class);

        // product 1: 1 * 199.99 = 199.99, tax 18% = 35.9982 => 36.00
        // product 2: 2 * 49.50 = 99.00, tax 5% = 4.95 => 4.95
        // Subtotal = 298.99, Tax = 40.95, Grand total = 339.94
        $order = $service->createOrder([
            'customer' => [
                'name' => 'Math Test',
                'email' => 'math@example.com',
            ],
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 1],
                ['product_id' => $product2->id, 'quantity' => 2],
            ],
            'amount_paid' => 350.00,
        ]);

        $this->assertEquals(298.99, (float) $order->subtotal);
        $this->assertEquals(40.95, (float) $order->tax_amount);
        $this->assertEquals(339.94, (float) $order->grand_total);
        $this->assertEquals(10.06, (float) $order->change_returned); // 350 - 339.94 = 10.06
    }
}
