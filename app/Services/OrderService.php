<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Jobs\SendOrderConfirmationJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Create an order with transactional stock deduction, pessimistic locking,
     * calculation of subtotals and taxes, and dispatching confirmation job.
     *
     * @param array{
     *     customer: array{email: string, name: string, phone?: string|null},
     *     items: array<int, array{product_id: int, quantity: int}>,
     *     amount_paid?: float|int|null
     * } $data
     * @return Order
     * @throws InsufficientStockException|\Throwable
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // 1. Resolve or create customer
            $customer = $this->resolveCustomer($data['customer']);

            // 2. Normalize and aggregate items by product_id to handle duplicates cleanly
            $aggregatedItems = $this->aggregateItems($data['items']);

            // 3. Acquire pessimistic locks on products in a deterministic (sorted) order to prevent deadlocks
            $sortedProductIds = $aggregatedItems->keys()->sort()->values()->all();

            /** @var \Illuminate\Database\Eloquent\Collection<int, Product> $products */
            $products = Product::whereIn('id', $sortedProductIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 4. Validate stock availability and prepare calculated order items
            $lineItemsData = [];
            $orderSubtotal = 0.0;
            $orderTaxAmount = 0.0;

            foreach ($aggregatedItems as $productId => $quantity) {
                /** @var Product|null $product */
                $product = $products->get($productId);

                if (! $product) {
                    throw new \InvalidArgumentException("Product with ID {$productId} was not found.");
                }

                if ($product->stock < $quantity) {
                    throw new InsufficientStockException(
                        message: "Insufficient stock for product '{$product->name}' (Code: {$product->code}). Available: {$product->stock}, Requested: {$quantity}.",
                        productId: $product->id,
                        productName: $product->name,
                        availableStock: $product->stock,
                        requestedQuantity: $quantity
                    );
                }

                // Deduct stock safely inside the transaction
                $product->stock -= $quantity;
                $product->save();

                // Compute financial lines
                $unitPrice = (float) $product->price;
                $taxPercentage = (float) $product->tax_percentage;

                $lineSubtotal = round($unitPrice * $quantity, 2);
                $lineTax = round($lineSubtotal * ($taxPercentage / 100), 2);
                $lineTotal = round($lineSubtotal + $lineTax, 2);

                $orderSubtotal += $lineSubtotal;
                $orderTaxAmount += $lineTax;

                $lineItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'unit_price' => $unitPrice,
                    'tax_percentage' => $taxPercentage,
                    'quantity' => $quantity,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal,
                ];
            }

            $orderSubtotal = round($orderSubtotal, 2);
            $orderTaxAmount = round($orderTaxAmount, 2);
            $grandTotal = round($orderSubtotal + $orderTaxAmount, 2);

            $amountPaid = isset($data['amount_paid']) && is_numeric($data['amount_paid'])
                ? round((float) $data['amount_paid'], 2)
                : null;

            $changeReturned = null;
            if ($amountPaid !== null && $amountPaid >= $grandTotal) {
                $changeReturned = round($amountPaid - $grandTotal, 2);
            }

            // 5. Create the Order
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $customer->id,
                'subtotal' => $orderSubtotal,
                'tax_amount' => $orderTaxAmount,
                'grand_total' => $grandTotal,
                'amount_paid' => $amountPaid,
                'change_returned' => $changeReturned,
                'status' => 'completed',
            ]);

            // 6. Save line items
            foreach ($lineItemsData as $itemData) {
                $order->items()->create($itemData);
            }

            // 7. Dispatch queued job for order confirmation email
            SendOrderConfirmationJob::dispatch($order);

            // 8. Return fresh order with customer and items loaded
            return $order->load(['customer', 'items.product']);
        });
    }

    /**
     * Resolve existing customer or create a new one.
     *
     * @param array{email: string, name: string, phone?: string|null} $customerData
     */
    public function resolveCustomer(array $customerData): Customer
    {
        $email = strtolower(trim($customerData['email']));
        $name = trim($customerData['name']);
        $phone = isset($customerData['phone']) ? trim($customerData['phone']) : null;

        $customer = Customer::where('email', $email)->first();

        if ($customer) {
            // Update name or phone if provided
            if (!empty($name) && $customer->name !== $name) {
                $customer->name = $name;
            }
            if ($phone && $customer->phone !== $phone) {
                $customer->phone = $phone;
            }
            if ($customer->isDirty()) {
                $customer->save();
            }
            return $customer;
        }

        return Customer::create([
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
        ]);
    }

    /**
     * Aggregate repeated items in the order payload by product_id.
     *
     * @param array<int, array{product_id: int, quantity: int}> $items
     * @return SupportCollection<int, int> [product_id => total_quantity]
     */
    protected function aggregateItems(array $items): SupportCollection
    {
        $aggregated = collect();

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantity = (int) $item['quantity'];

            $current = $aggregated->get($productId, 0);
            $aggregated->put($productId, $current + $quantity);
        }

        return $aggregated;
    }

    /**
     * Fetch order history for a given customer email.
     */
    public function getCustomerOrderHistory(string $email): ?Customer
    {
        return Customer::where('email', strtolower(trim($email)))
            ->with(['orders.items.product'])
            ->first();
    }

    /**
     * Fetch products below or equal to a configurable threshold.
     */
    public function getLowStockProducts(?int $threshold = null): Collection
    {
        $effectiveThreshold = $threshold ?? (int) config('inventory.low_stock_threshold', 5);

        return Product::lowStock($effectiveThreshold)
            ->orderBy('stock', 'asc')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Generate a unique human-friendly order reference.
     */
    protected function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
