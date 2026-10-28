<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Confirmation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #333; line-height: 1.5; padding: 20px; }
        .invoice-card { max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; }
        .header { border-bottom: 2px solid #3b82f6; padding-bottom: 16px; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: bold; color: #1e3a8a; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; margin-bottom: 16px; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #f1f5f9; }
        th { background: #f8fafc; font-size: 13px; text-transform: uppercase; color: #64748b; }
        .totals { margin-top: 16px; text-align: right; }
        .total-row { display: flex; justify-content: flex-end; gap: 20px; padding: 4px 0; }
        .grand-total { font-size: 18px; font-weight: bold; color: #047857; }
    </style>
</head>
<body>
    <div class="invoice-card">
        <div class="header">
            <div class="title">Retail Store - Order Confirmation</div>
            <p>Thank you for shopping with us, <strong>{{ $order->customer->name }}</strong>!</p>
            <p><strong>Order Number:</strong> {{ $order->order_number }}<br>
               <strong>Date:</strong> {{ $order->created_at->format('d M Y, h:i A') }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th style="text-align: right;">Tax</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->product_name }}</strong><br>
                            <small style="color: #64748b;">Code: {{ $item->product_code }}</small>
                        </td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">₹{{ number_format($item->unit_price, 2) }}</td>
                        <td style="text-align: right;">₹{{ number_format($item->line_tax, 2) }} ({{ $item->tax_percentage }}%)</td>
                        <td style="text-align: right;">₹{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div><strong>Subtotal:</strong> ₹{{ number_format($order->subtotal, 2) }}</div>
            <div><strong>Tax:</strong> ₹{{ number_format($order->tax_amount, 2) }}</div>
            <div class="grand-total"><strong>Grand Total:</strong> ₹{{ number_format($order->grand_total, 2) }}</div>
            @if ($order->amount_paid)
                <div style="margin-top: 8px;">
                    <small>Amount Paid: ₹{{ number_format($order->amount_paid, 2) }} | Change: ₹{{ number_format($order->change_returned, 2) }}</small>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
