<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {
    }

    /**
     * Store a newly created order.
     *
     * Validates stock availability, deducts stock concurrently-safe,
     * computes tax/totals, records order, and triggers async confirmation email.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder($request->validated());

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * List orders, optionally filtered by customer email or search query.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::with(['customer', 'items.product'])->latest();

        if ($search = $request->query('search')) {
            $term = strtolower(trim($search));
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', function ($cq) use ($term) {
                        $cq->where('email', 'like', "%{$term}%")
                            ->orWhere('name', 'like', "%{$term}%")
                            ->orWhere('phone', 'like', "%{$term}%");
                    });
            });
        } elseif ($email = $request->query('customer_email')) {
            $query->whereHas('customer', function ($q) use ($email) {
                $q->where('email', strtolower(trim($email)));
            });
        }

        $orders = $query->paginate(20);

        return OrderResource::collection($orders);
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): OrderResource
    {
        $order->load(['customer', 'items.product']);

        return new OrderResource($order);
    }
}
