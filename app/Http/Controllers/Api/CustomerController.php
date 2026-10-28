<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {
    }

    /**
     * Fetch order history for a customer by email.
     */
    public function orders(string $email): JsonResponse|AnonymousResourceCollection
    {
        $customer = $this->orderService->getCustomerOrderHistory($email);

        if (! $customer) {
            return response()->json([
                'message' => "Customer with email '{$email}' not found.",
                'data' => [],
            ], 404);
        }

        return OrderResource::collection($customer->orders)
            ->additional([
                'customer' => new CustomerResource($customer),
                'total_orders' => $customer->orders->count(),
            ]);
    }

    /**
     * Find a customer by email (useful for UI auto-fill).
     */
    public function show(string $email): JsonResponse
    {
        $customer = Customer::where('email', strtolower(trim($email)))->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found.',
                'found' => false,
            ], 404);
        }

        return response()->json([
            'found' => true,
            'customer' => new CustomerResource($customer),
        ]);
    }
}
