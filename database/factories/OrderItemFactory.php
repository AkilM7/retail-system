<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 10, 100);
        $qty = fake()->numberBetween(1, 5);
        $taxRate = 8.00;
        $subtotal = round($unitPrice * $qty, 2);
        $tax = round($subtotal * ($taxRate / 100), 2);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(2, true),
            'product_code' => strtoupper(fake()->bothify('PRD-###')),
            'unit_price' => $unitPrice,
            'tax_percentage' => $taxRate,
            'quantity' => $qty,
            'line_subtotal' => $subtotal,
            'line_tax' => $tax,
            'line_total' => round($subtotal + $tax, 2),
        ];
    }
}
