<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Queue;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Products (matching the wireframe reference and common retail items)
        $products = [
            [
                'name' => 'Colgate Toothpaste',
                'code' => 'CLG-101',
                'price' => 50.00,
                'tax_percentage' => 8.00,
                'stock' => 25,
            ],
            [
                'name' => 'Parle-G Biscuit',
                'code' => 'PRL-101',
                'price' => 10.00,
                'tax_percentage' => 8.00,
                'stock' => 50,
            ],
            [
                'name' => 'Bread',
                'code' => 'BRD-101',
                'price' => 40.00,
                'tax_percentage' => 5.00,
                'stock' => 4, // Low stock alert
            ],
            [
                'name' => 'Milk 1L',
                'code' => 'MLK-101',
                'price' => 60.00,
                'tax_percentage' => 5.00,
                'stock' => 9, // Low stock alert
            ],
            [
                'name' => 'Eggs (12)',
                'code' => 'EGG-101',
                'price' => 84.00,
                'tax_percentage' => 5.00,
                'stock' => 2, // Low stock alert
            ],
            [
                'name' => 'Basmati Rice 1kg',
                'code' => 'RCE-101',
                'price' => 120.00,
                'tax_percentage' => 5.00,
                'stock' => 1, // Single unit left (ideal for concurrency testing!)
            ],
            [
                'name' => 'Tata Salt 1kg',
                'code' => 'TTA-101',
                'price' => 28.00,
                'tax_percentage' => 5.00,
                'stock' => 30,
            ],
            [
                'name' => 'Sunflower Oil 1L',
                'code' => 'OIL-101',
                'price' => 150.00,
                'tax_percentage' => 5.00,
                'stock' => 15,
            ],
            [
                'name' => 'Maggi 2-Minute Noodles',
                'code' => 'MAG-101',
                'price' => 14.00,
                'tax_percentage' => 12.00,
                'stock' => 40,
            ],
            [
                'name' => 'Dettol Soap 75g',
                'code' => 'DET-101',
                'price' => 45.00,
                'tax_percentage' => 18.00,
                'stock' => 20,
            ],
        ];

        foreach ($products as $productData) {
            Product::updateOrCreate(
                ['code' => $productData['code']],
                $productData
            );
        }

        // 2. Seed Customers (including the wireframe example customer)
        $customers = [
            [
                'name' => 'Thomas Anderson',
                'email' => 'thomas@example.com',
                'phone' => '+91 98765 43210',
            ],
            [
                'name' => 'Akil Murugan',
                'email' => 'akilmurugan2002@gmail.com',
                'phone' => '+91 91234 56789',
            ],
            [
                'name' => 'Priya Sharma',
                'email' => 'priya.sharma@example.com',
                'phone' => '+91 98111 22334',
            ],
            [
                'name' => 'Rahul Verma',
                'email' => 'rahul.verma@example.com',
                'phone' => '+91 97222 33445',
            ],
        ];

        foreach ($customers as $customerData) {
            Customer::updateOrCreate(
                ['email' => $customerData['email']],
                $customerData
            );
        }

        // 3. Seed an initial sample order for Thomas Anderson matching the wireframe totals!
        // Wireframe: Colgate Toothpaste (qty 2 @ ₹50 = ₹100, tax 8% = ₹8 -> ₹108)
        //            Parle-G Biscuit (qty 5 @ ₹10 = ₹50, tax 8% = ₹4 -> ₹54)
        // Subtotal: ₹150.00 + tax: ₹12.00 = Grand Total: ₹162.00
        $orderService = app(OrderService::class);

        $colgate = Product::where('code', 'CLG-101')->first();
        $parle = Product::where('code', 'PRL-101')->first();

        if ($colgate && $parle) {
            // Fake queue during seeder to not clog queue workers
            Queue::fake();

            $orderService->createOrder([
                'customer' => [
                    'name' => 'Thomas Anderson',
                    'email' => 'thomas@example.com',
                    'phone' => '+91 98765 43210',
                ],
                'items' => [
                    ['product_id' => $colgate->id, 'quantity' => 2],
                    ['product_id' => $parle->id, 'quantity' => 5],
                ],
                'amount_paid' => 200.00,
            ]);
        }
    }
}
