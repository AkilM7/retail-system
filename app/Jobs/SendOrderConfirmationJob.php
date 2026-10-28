<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Eager load customer and items if not already loaded
        $this->order->loadMissing(['customer', 'items']);

        $customerEmail = $this->order->customer?->email ?? 'unknown@example.com';
        $customerName = $this->order->customer?->name ?? 'Valued Customer';

        Log::info("🚀 [SendOrderConfirmationJob] Processing order confirmation email.", [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_email' => $customerEmail,
            'customer_name' => $customerName,
            'grand_total' => $this->order->grand_total,
            'item_count' => $this->order->items->count(),
        ]);

        try {
            Mail::to($customerEmail, $customerName)->send(new OrderConfirmationMail($this->order));

            Log::info("✅ [SendOrderConfirmationJob] Order confirmation email successfully sent to {$customerEmail} for Order #{$this->order->order_number}.");
        } catch (\Throwable $e) {
            Log::error("❌ [SendOrderConfirmationJob] Failed to send order confirmation: {$e->getMessage()}", [
                'order_id' => $this->order->id,
                'exception' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
