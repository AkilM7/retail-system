<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_counter_page_renders_successfully_with_products(): void
    {
        Product::factory()->create([
            'name' => 'Colgate Toothpaste',
            'code' => 'CLG-101',
            'price' => 50.00,
            'tax_percentage' => 8.00,
            'stock' => 15,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Store Billing — New Order');
        $response->assertSee('Colgate Toothpaste');
        $response->assertSee('Low Stock Alert');
    }
}
