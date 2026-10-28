<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_products_below_default_low_stock_threshold(): void
    {
        // Default threshold in config is 5
        config(['inventory.low_stock_threshold' => 5]);

        Product::factory()->create(['name' => 'Zero Stock', 'stock' => 0]);
        Product::factory()->create(['name' => 'Two Stock', 'stock' => 2]);
        Product::factory()->create(['name' => 'Five Stock (Exact)', 'stock' => 5]);
        Product::factory()->create(['name' => 'Six Stock', 'stock' => 6]);
        Product::factory()->create(['name' => 'Twenty Stock', 'stock' => 20]);

        $response = $this->getJson('/api/products/low-stock');

        $response->assertStatus(200)
            ->assertJsonPath('meta.threshold', 5)
            ->assertJsonPath('meta.count', 3)
            ->assertJsonCount(3, 'data');

        $stocks = collect($response->json('data'))->pluck('stock')->all();
        $this->assertEquals([0, 2, 5], $stocks);
    }

    public function test_can_fetch_products_with_custom_threshold_query_parameter(): void
    {
        Product::factory()->create(['name' => 'Item 1', 'stock' => 3]);
        Product::factory()->create(['name' => 'Item 2', 'stock' => 8]);
        Product::factory()->create(['name' => 'Item 3', 'stock' => 10]);
        Product::factory()->create(['name' => 'Item 4', 'stock' => 15]);

        // Request threshold = 10
        $response = $this->getJson('/api/products/low-stock?threshold=10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.threshold', 10)
            ->assertJsonPath('meta.count', 3)
            ->assertJsonCount(3, 'data');
    }
}
