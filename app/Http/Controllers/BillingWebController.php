<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingWebController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {
    }

    /**
     * Show the Store Billing - New Order screen.
     */
    public function index(): View
    {
        $products = Product::orderBy('name')->get();
        $lowStockProducts = $this->orderService->getLowStockProducts();
        $recentOrders = Order::with(['customer', 'items'])->latest()->take(5)->get();

        return view('billing.index', compact('products', 'lowStockProducts', 'recentOrders'));
    }
}
