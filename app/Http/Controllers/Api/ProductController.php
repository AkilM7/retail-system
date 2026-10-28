<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {
    }

    /**
     * Display a listing of products.
     */
    public function index(): AnonymousResourceCollection
    {
        $products = Product::orderBy('name')->get();

        return ProductResource::collection($products);
    }

    /**
     * Return products below a configurable low-stock threshold.
     */
    public function lowStock(Request $request): AnonymousResourceCollection
    {
        $threshold = $request->has('threshold') ? (int) $request->query('threshold') : null;

        $products = $this->orderService->getLowStockProducts($threshold);

        $effectiveThreshold = $threshold ?? (int) config('inventory.low_stock_threshold', 5);

        return ProductResource::collection($products)->additional([
            'meta' => [
                'threshold' => $effectiveThreshold,
                'count' => $products->count(),
            ],
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }
}
