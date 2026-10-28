<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InsufficientStockException extends Exception
{
    protected ?int $productId;
    protected ?string $productName;
    protected ?int $availableStock;
    protected ?int $requestedQuantity;

    public function __construct(
        string $message = "Insufficient stock available.",
        ?int $productId = null,
        ?string $productName = null,
        ?int $availableStock = null,
        ?int $requestedQuantity = null
    ) {
        parent::__construct($message, 422);
        $this->productId = $productId;
        $this->productName = $productName;
        $this->availableStock = $availableStock;
        $this->requestedQuantity = $requestedQuantity;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function getAvailableStock(): ?int
    {
        return $this->availableStock;
    }

    public function getRequestedQuantity(): ?int
    {
        return $this->requestedQuantity;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'INSUFFICIENT_STOCK',
            'details' => [
                'product_id' => $this->productId,
                'product_name' => $this->productName,
                'available_stock' => $this->availableStock,
                'requested_quantity' => $this->requestedQuantity,
            ],
        ], 422);
    }
}
