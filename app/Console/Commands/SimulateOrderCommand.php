<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Console\Command;

class SimulateOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:simulate
                            {--email=thomas@example.com : Customer email}
                            {--name=Thomas Anderson : Customer name}
                            {--product=CLG-101 : Product code}
                            {--qty=2 : Quantity to order}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate placing an order via OrderService to test transactions, stock deduction, and queue dispatching';

    /**
     * Execute the console command.
     */
    public function handle(OrderService $orderService): int
    {
        $code = (string) $this->option('product');
        $qty = (int) $this->option('qty');
        $email = (string) $this->option('email');
        $name = (string) $this->option('name');

        $product = Product::where('code', $code)->first();

        if (! $product) {
            $this->error("Product with code '{$code}' not found.");
            return self::FAILURE;
        }

        $this->info("Initial stock for '{$product->name}' ({$product->code}): {$product->stock}");

        try {
            $order = $orderService->createOrder([
                'customer' => [
                    'name' => $name,
                    'email' => $email,
                ],
                'items' => [
                    ['product_id' => $product->id, 'quantity' => $qty],
                ],
                'amount_paid' => $product->price * $qty * 1.5,
            ]);

            $this->newLine();
            $this->info("✅ Order successfully created: {$order->order_number}");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Customer', "{$order->customer->name} ({$order->customer->email})"],
                    ['Subtotal', "₹" . number_format($order->subtotal, 2)],
                    ['Tax Amount', "₹" . number_format($order->tax_amount, 2)],
                    ['Grand Total', "₹" . number_format($order->grand_total, 2)],
                    ['Amount Paid', "₹" . number_format($order->amount_paid, 2)],
                    ['Change Returned', "₹" . number_format($order->change_returned, 2)],
                    ['New Stock for ' . $product->name, $product->fresh()->stock],
                ]
            );

            $this->info("Queued job 'SendOrderConfirmationJob' dispatched to the database queue.");
            $this->line("Run 'php artisan queue:work --once' to process the email notification.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Order failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
