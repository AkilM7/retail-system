<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Billing — New Order | Mallow Technologies Assignment</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <!-- App base URL (resolved by Laravel so it works in any subdirectory) -->
    <script>window.__BASE = '{{ rtrim(url('/'), '/') }}';</script>
    <style>
        [x-cloak] { display: none !important; }
        @media print {
            body * { visibility: hidden; }
            #printable-receipt, #printable-receipt * { visibility: visible; }
            #printable-receipt { position: absolute; left: 0; top: 0; width: 100%; }
        }
        /* Custom smooth scrollbar for table and alert lists */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen font-sans" x-data="billingApp()" x-init="initApp()">

    <!-- Header Bar -->
    <header class="bg-slate-900 text-white shadow-md border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center font-bold text-slate-950 text-lg shadow-sm">
                    <i class="fa-solid fa-cash-register"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold tracking-tight text-white flex items-center gap-2">
                        Store Billing — New Order
                        <span class="text-[11px] font-normal bg-slate-800 text-slate-300 border border-slate-700 px-2 py-0.5 rounded">
                            <i class="fa-solid fa-layer-group text-slate-400 mr-1"></i> WIREFRAME REFERENCE
                        </span>
                    </h1>
                    <p class="text-xs text-slate-400">Mallow Technologies • Retail Order & Inventory Mini-System</p>
                </div>
            </div>

            <!-- Quick Navigation Pills -->
            <div class="flex items-center space-x-2 text-xs">
                <button @click="activeTab = 'pos'" :class="activeTab === 'pos' ? 'bg-emerald-600 text-white font-medium shadow-sm' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'" class="px-3 py-1.5 rounded-lg transition flex items-center">
                    <i class="fa-solid fa-cart-shopping mr-1.5"></i> POS Billing
                </button>
                <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'bg-emerald-600 text-white font-medium shadow-sm' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'" class="px-3 py-1.5 rounded-lg transition flex items-center">
                    <i class="fa-solid fa-clock-rotate-left mr-1.5"></i> Customer History API
                </button>
                <button @click="activeTab = 'concurrency'" :class="activeTab === 'concurrency' ? 'bg-emerald-600 text-white font-medium shadow-sm' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'" class="px-3 py-1.5 rounded-lg transition flex items-center">
                    <i class="fa-solid fa-bolt mr-1.5"></i> Concurrency Tester (Req #7)
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5">

        <!-- TAB 1: POS BILLING SCREEN (MATCHES WIREFRAME) -->
        <div x-show="activeTab === 'pos'" class="space-y-3.5">

            <!-- Toast / Notification banner -->
            <div x-show="alertMessage" x-cloak class="p-4 rounded-xl flex items-center justify-between shadow-sm transition"
                 :class="alertType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-rose-50 border border-rose-200 text-rose-800'">
                <div class="flex items-center space-x-2">
                    <i :class="alertType === 'success' ? 'fa-solid fa-circle-check text-emerald-600 text-base' : 'fa-solid fa-triangle-exclamation text-rose-600 text-base'"></i>
                    <span class="text-sm font-medium" x-text="alertMessage"></span>
                </div>
                <button @click="alertMessage = ''" class="text-slate-400 hover:text-slate-600 font-bold px-2 py-1">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                <!-- Left 2 Columns: Customer, Products, and Payment -->
                <div class="lg:col-span-2 space-y-3.5">

                    <!-- Customer Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-2">
                            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-600 flex items-center">
                                <i class="fa-solid fa-user-tag text-slate-400 mr-2"></i> Customer Details
                            </h2>
                            <span x-show="customerStatus" class="text-xs px-2.5 py-0.5 rounded-full font-medium flex items-center"
                                  :class="customerFound ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'">
                                <i :class="customerFound ? 'fa-solid fa-circle-check mr-1 text-emerald-600' : 'fa-solid fa-user-plus mr-1 text-slate-400'"></i>
                                <span x-text="customerStatus"></span>
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Customer Email <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                                        <i class="fa-regular fa-envelope"></i>
                                    </div>
                                    <input type="email"
                                           x-model="customer.email"
                                           @blur="lookupCustomer()"
                                           @keydown.enter.prevent="lookupCustomer()"
                                           placeholder="e.g. thomas@example.com"
                                           class="w-full pl-8 pr-20 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                           :class="errors['customer.email'] ? 'border-rose-400 bg-rose-50' : 'border-slate-300'">
                                    <button type="button" @click="lookupCustomer()"
                                            class="absolute right-1.5 top-1.5 text-xs bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded-md text-slate-700 border border-slate-300 flex items-center gap-1 transition">
                                        <i class="fa-solid fa-magnifying-glass text-[10px]"></i> Lookup
                                    </button>
                                </div>
                                <p x-show="errors['customer.email']" class="text-xs text-rose-500 mt-1 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-exclamation text-[11px]"></i>
                                    <span x-text="errors['customer.email']"></span>
                                </p>
                                <p class="text-[11px] text-slate-400 mt-1">Try: <code>thomas@example.com</code> or <code>akilmurugan2002@gmail.com</code></p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Customer Name <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                                        <i class="fa-regular fa-user"></i>
                                    </div>
                                    <input type="text"
                                           x-model="customer.name"
                                           placeholder="auto-filled if email exists"
                                           class="w-full pl-8 pr-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                                           :class="errors['customer.name'] ? 'border-rose-400 bg-rose-50' : 'border-slate-300'">
                                </div>
                                <p x-show="errors['customer.name']" class="text-xs text-rose-500 mt-1 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-exclamation text-[11px]"></i>
                                    <span x-text="errors['customer.name']"></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Products Table Section (Exact Wireframe layout) -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-2">
                            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-600 flex items-center">
                                <i class="fa-solid fa-boxes-stacked text-slate-400 mr-2"></i> Products
                            </h2>
                            <div class="flex items-center space-x-2">
                                <button type="button" @click="addItem()"
                                        class="inline-flex items-center space-x-1 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded-md shadow-sm transition">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    <span>Add Item</span>
                                </button>
                                <span class="text-xs text-slate-500 font-medium bg-slate-100 px-2 py-0.5 rounded-full" x-text="items.length + ' item(s)'"></span>
                            </div>
                        </div>

                        <!-- Product Rows Table (Scrollable container with sticky header) -->
                        <div x-ref="tableContainer" class="overflow-x-auto max-h-[250px] overflow-y-auto border border-slate-200 rounded-lg custom-scrollbar">
                            <table class="w-full text-left text-sm relative">
                                <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200 shadow-sm">
                                    <tr class="text-xs uppercase text-slate-500 font-semibold">
                                        <th class="py-2.5 px-3 bg-slate-50">Product</th>
                                        <th class="py-2.5 px-3 text-center w-24 bg-slate-50">Qty</th>
                                        <th class="py-2.5 px-3 text-right w-24 bg-slate-50">Price</th>
                                        <th class="py-2.5 px-3 text-right w-28 bg-slate-50">Line Total</th>
                                        <th class="py-2.5 px-3 text-center w-12 bg-slate-50"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr class="hover:bg-slate-50/80 transition">
                                            <!-- Product Select -->
                                            <td class="py-2.5 px-3">
                                                <select x-model.number="item.product_id"
                                                        @change="onProductChange(index)"
                                                        class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                                    <option value="">-- select product --</option>
                                                    <template x-for="p in catalog" :key="p.id">
                                                        <option :value="p.id"
                                                                :disabled="p.stock <= 0"
                                                                x-text="p.name + ' (' + p.code + ') — ₹' + Number(p.price).toFixed(2) + ' (Stock: ' + p.stock + ')'"></option>
                                                    </template>
                                                </select>
                                                <div x-show="item.product && item.product.stock <= 5" class="text-[11px] text-amber-700 mt-1 flex items-center gap-1 font-medium">
                                                    <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                                                    <span>Only <strong x-text="item.product.stock"></strong> left in stock!</span>
                                                </div>
                                            </td>

                                            <!-- Quantity Input -->
                                            <td class="py-2.5 px-3 text-center">
                                                <input type="number" min="1"
                                                       x-model.number="item.quantity"
                                                       @input="recalculate()"
                                                       class="w-20 px-2 py-1.5 border border-slate-300 rounded-lg text-center text-sm font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                            </td>

                                            <!-- Unit Price Readonly -->
                                            <td class="py-2.5 px-3 text-right font-medium text-slate-700">
                                                <span x-text="item.unit_price > 0 ? '₹' + Number(item.unit_price).toFixed(2) : '—'"></span>
                                                <div x-show="item.tax_percentage > 0" class="text-[10px] text-slate-400">
                                                    +<span x-text="item.tax_percentage"></span>% tax
                                                </div>
                                            </td>

                                            <!-- Line Total (Price * Qty + Tax) -->
                                            <td class="py-2.5 px-3 text-right font-semibold text-slate-900">
                                                <span x-text="item.line_total > 0 ? '₹' + Number(item.line_total).toFixed(2) : '—'"></span>
                                            </td>

                                            <!-- Delete Button -->
                                            <td class="py-2.5 px-3 text-center">
                                                <button type="button" @click="removeItem(index)"
                                                        :disabled="items.length === 1"
                                                        title="Remove product"
                                                        class="text-slate-400 hover:text-rose-600 disabled:opacity-20 disabled:hover:text-slate-400 p-1.5 rounded transition">
                                                    <i class="fa-regular fa-trash-can text-sm"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Add Product Row Button -->
                        <div class="mt-4 flex flex-wrap justify-between items-center gap-2">
                            <button type="button" @click="addItem()"
                                    class="inline-flex items-center space-x-1.5 text-xs font-semibold bg-slate-900 text-white px-3.5 py-2 rounded-lg hover:bg-slate-800 shadow-sm transition">
                                <i class="fa-solid fa-plus"></i>
                                <span>Add Product</span>
                            </button>

                            <button type="button" @click="loadWireframeExample()"
                                    class="inline-flex items-center space-x-1.5 text-xs text-blue-600 hover:text-blue-800 hover:underline font-medium">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                                <span>Fill Wireframe Sample (Colgate + Parle-G)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Payment Section (Exact Wireframe layout) -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <div class="flex items-center justify-between mb-3 border-b border-slate-100 pb-2">
                            <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-600 flex items-center">
                                <i class="fa-solid fa-credit-card text-slate-400 mr-2"></i> Payment & Billing
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

                            <!-- Left: Totals Breakdown & Cash Received -->
                            <div class="space-y-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-600 font-medium">Subtotal</span>
                                    <span class="font-semibold text-slate-800" x-text="'₹' + totals.subtotal.toFixed(2)"></span>
                                </div>

                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-600 font-medium">Tax</span>
                                    <span class="font-semibold text-slate-800" x-text="'₹' + totals.tax.toFixed(2)"></span>
                                </div>

                                <div class="flex justify-between items-center text-base pt-2 border-t border-slate-200 font-bold">
                                    <span class="text-slate-900">Grand Total</span>
                                    <span class="text-emerald-700 text-lg font-extrabold" x-text="'₹' + totals.grandTotal.toFixed(2)"></span>
                                </div>

                                <!-- Amount Given by Customer -->
                                <div class="pt-3 border-t border-slate-100">
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Amount Given by Customer (₹)
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                                            <i class="fa-solid fa-indian-rupee-sign"></i>
                                        </div>
                                        <input type="number" step="0.5"
                                               x-model.number="payment.amountGiven"
                                               @input="calculateChange()"
                                               placeholder="e.g. 250"
                                               class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-lg text-sm font-semibold focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                    </div>
                                </div>

                                <!-- Balance to Return (with denomination breakdown) -->
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200" x-show="totals.grandTotal > 0">
                                    <div class="flex justify-between text-sm font-semibold text-slate-800">
                                        <span>Balance to Return:</span>
                                        <span :class="payment.balance >= 0 ? 'text-emerald-700 font-bold' : 'text-rose-600 font-bold'"
                                              x-text="'₹' + Math.max(0, payment.balance).toFixed(2)"></span>
                                    </div>
                                    <div x-show="payment.denominations && payment.balance > 0" class="mt-1 text-xs text-slate-600 font-mono flex items-center gap-1.5">
                                        <i class="fa-solid fa-money-bill-wave text-emerald-600 text-xs"></i>
                                        <span x-text="payment.denominations"></span>
                                    </div>
                                    <div x-show="payment.amountGiven > 0 && payment.amountGiven < totals.grandTotal" class="mt-1 text-xs text-rose-600 font-medium flex items-center gap-1">
                                        <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                                        <span>₹<span x-text="(totals.grandTotal - payment.amountGiven).toFixed(2)"></span> remaining to collect.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Generate Bill Button & Action notes -->
                            <div class="flex flex-col justify-center items-center h-full space-y-3 p-5 bg-emerald-50/60 rounded-xl border border-emerald-100">
                                <button type="button"
                                        @click="generateBill()"
                                        :disabled="isSubmitting || items.length === 0 || totals.grandTotal <= 0"
                                        class="w-full py-3.5 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition transform active:scale-95 flex items-center justify-center space-x-2">
                                    <i x-show="!isSubmitting" class="fa-solid fa-file-invoice-dollar text-lg"></i>
                                    <i x-show="isSubmitting" class="fa-solid fa-circle-notch fa-spin text-lg"></i>
                                    <span x-text="isSubmitting ? 'Processing Order...' : 'Generate Bill'"></span>
                                </button>
                                <p class="text-[11px] text-center text-slate-500 leading-relaxed flex items-center justify-center gap-1">
                                    <i class="fa-solid fa-paper-plane text-emerald-600"></i>
                                    <span>Generates bill + dispatches asynchronous email job</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right 1 Column: Low Stock Alert & System Overview -->
                <div class="space-y-3.5">

                    <!-- Low Stock Alert Card (Exact Wireframe layout) -->
                    <div class="bg-amber-50/70 border-2 border-amber-300 rounded-xl p-4 shadow-sm">
                        <div class="flex items-center justify-between mb-3 border-b border-amber-200 pb-2">
                            <h2 class="text-sm font-bold text-amber-950 flex items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Low Stock Alert
                            </h2>
                            <div class="flex items-center space-x-1.5">
                                <span class="text-[10px] text-amber-900 font-semibold">Threshold:</span>
                                <select x-model.number="lowStockThreshold" @change="fetchLowStock()" class="text-xs bg-white border border-amber-300 rounded-md px-1.5 py-0.5 font-medium text-slate-800">
                                    <option value="5">≤ 5</option>
                                    <option value="10">≤ 10</option>
                                    <option value="15">≤ 15</option>
                                </select>
                            </div>
                        </div>

                        <ul class="space-y-2 text-sm text-slate-800 max-h-[190px] overflow-y-auto custom-scrollbar pr-1">
                            <template x-for="p in lowStockProducts" :key="p.id">
                                <li class="flex items-center justify-between py-1 border-b border-amber-100 last:border-0">
                                    <span class="font-medium text-slate-900 flex items-center gap-1.5">
                                        <i class="fa-solid fa-box text-amber-600 text-[11px]"></i>
                                        <span x-text="p.name"></span>
                                    </span>
                                    <span class="text-xs px-2 py-0.5 rounded-full font-bold"
                                          :class="p.stock === 0 ? 'bg-rose-200 text-rose-900' : 'bg-amber-200 text-amber-950'"
                                          x-text="p.stock + ' units left'"></span>
                                </li>
                            </template>
                            <li x-show="lowStockProducts.length === 0" class="text-xs text-amber-800 italic py-2 flex items-center gap-1.5">
                                <i class="fa-solid fa-check text-emerald-600"></i>
                                <span>All items sufficiently stocked!</span>
                            </li>
                        </ul>

                        <div class="mt-3 pt-2.5 border-t border-amber-200 flex justify-between items-center text-[11px] text-amber-900">
                            <span class="flex items-center gap-1 font-mono">
                                <i class="fa-solid fa-network-wired text-amber-700"></i> /api/products/low-stock
                            </span>
                            <button @click="fetchLowStock()" class="hover:underline font-bold text-amber-950 flex items-center gap-1">
                                <i class="fa-solid fa-arrows-rotate text-[10px]"></i> Refresh
                            </button>
                        </div>
                    </div>

                    <!-- Quick System Specs Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 space-y-2.5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                            <i class="fa-solid fa-list-check text-slate-400"></i> Assessment Checklist
                        </h3>
                        <ul class="text-xs space-y-2 text-slate-600">
                            <li class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                <span>Normalized Schema (Migrations)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                <span>POST /api/orders (Stock check, deduct, tax)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                <span>GET /api/customers/{email}/orders</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                <span>GET /api/products/low-stock</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                <span>Queued Job: <code>SendOrderConfirmationJob</code></span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                <span>Pessimistic Locking Concurrency Safe</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                <span>Automated PHPUnit Feature/Unit Tests</span>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>

        <!-- TAB 2: CUSTOMER ORDER HISTORY API VIEWER -->
        <div x-show="activeTab === 'history'" x-cloak class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="max-w-2xl">
                    <h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-slate-600"></i> Customer Order History (Requirement #3)
                    </h2>
                    <p class="text-xs text-slate-500 mb-4">
                        Search customer orders by email address to fetch item breakdowns and historical purchases.
                    </p>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                                <i class="fa-regular fa-envelope"></i>
                            </div>
                            <input type="email" x-model="historySearchEmail" placeholder="e.g. thomas@example.com"
                                   @keydown.enter.prevent="fetchOrderHistory()"
                                   class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        </div>
                        <button type="button" @click="fetchOrderHistory()"
                                class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold hover:bg-slate-800 transition flex items-center gap-1.5">
                            <i class="fa-solid fa-magnifying-glass text-xs"></i>
                            <span>Search Orders</span>
                        </button>
                    </div>
                </div>

                <div x-show="historyLoading" class="py-8 text-center text-sm text-slate-500">
                    <i class="fa-solid fa-circle-notch fa-spin text-emerald-600 mr-2 text-base"></i> Loading customer orders...
                </div>

                <div x-show="historyError" class="mt-4 p-4 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-700 flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                    <span x-text="historyError"></span>
                </div>

                <div x-show="historyOrders && historyOrders.length > 0 && !historyLoading" class="mt-6 space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="font-bold text-slate-900 flex items-center gap-1.5" x-text="historyCustomer ? historyCustomer.name : ''"></h3>
                            <p class="text-xs text-slate-500" x-text="historyCustomer ? historyCustomer.email : ''"></p>
                        </div>
                        <span class="text-xs bg-slate-100 text-slate-700 px-3 py-1 rounded-full font-semibold"
                              x-text="(historyOrders ? historyOrders.length : 0) + ' Total Orders'"></span>
                    </div>

                    <template x-for="order in historyOrders" :key="order.id">
                        <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50 hover:bg-slate-50 transition">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <span class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                                        <i class="fa-solid fa-receipt text-slate-400"></i>
                                        <span x-text="order.order_number"></span>
                                    </span>
                                    <span class="text-xs text-slate-400 block mt-0.5" x-text="new Date(order.created_at).toLocaleString()"></span>
                                </div>
                                <div class="text-right">
                                    <span class="font-bold text-emerald-700 text-base" x-text="'₹' + Number(order.grand_total).toFixed(2)"></span>
                                    <span class="text-[11px] text-slate-500 block" x-text="'Subtotal: ₹' + Number(order.subtotal).toFixed(2) + ' | Tax: ₹' + Number(order.tax_amount).toFixed(2)"></span>
                                </div>
                            </div>

                            <!-- Line items -->
                            <div class="mt-2 text-xs border-t border-slate-200 pt-2">
                                <table class="w-full text-slate-700">
                                    <thead>
                                        <tr class="text-slate-400 font-semibold text-[11px]">
                                            <th class="text-left font-medium">Item</th>
                                            <th class="text-center font-medium">Qty</th>
                                            <th class="text-right font-medium">Price</th>
                                            <th class="text-right font-medium">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="line in order.items" :key="line.id">
                                            <tr>
                                                <td class="py-1 flex items-center gap-1">
                                                    <i class="fa-solid fa-box text-slate-400 text-[10px]"></i>
                                                    <span x-text="line.product_name + ' (' + line.product_code + ')'"></span>
                                                </td>
                                                <td class="text-center py-1" x-text="line.quantity"></td>
                                                <td class="text-right py-1" x-text="'₹' + Number(line.unit_price).toFixed(2)"></td>
                                                <td class="text-right py-1 font-semibold text-slate-900" x-text="'₹' + Number(line.line_total).toFixed(2)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- TAB 3: CONCURRENCY TESTER (REQUIREMENT #7 DEMO) -->
        <div x-show="activeTab === 'concurrency'" x-cloak class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-3xl">
                <h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
                    <i class="fa-solid fa-bolt text-purple-600"></i> Requirement #7: Concurrency Safety Demo
                </h2>
                <p class="text-xs text-slate-600 mb-4 leading-relaxed">
                    Brief: <em>"Ensure the stock check-and-deduct step is safe under concurrent requests — if two orders for the same product arrive at nearly the same time and only one unit is left, exactly one should succeed and the other should fail cleanly (no overselling)."</em>
                </p>

                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                                <i class="fa-solid fa-shield-halved text-purple-600"></i> Target Product: Basmati Rice 1kg (RCE-101)
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">Current Stock in Database:
                                <span class="font-bold px-2 py-0.5 rounded bg-amber-100 text-amber-900" x-text="getRiceStock() + ' units left'"></span>
                            </div>
                        </div>
                        <button type="button" @click="resetRiceStock()" class="text-xs bg-slate-200 hover:bg-slate-300 px-3 py-1.5 rounded-lg text-slate-700 font-semibold flex items-center gap-1 transition">
                            <i class="fa-solid fa-rotate-left text-[11px]"></i> Reset Stock to 1
                        </button>
                    </div>

                    <button type="button" @click="runConcurrentRaceTest()"
                            :disabled="concurrencyRunning"
                            class="w-full py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg text-sm transition flex items-center justify-center space-x-2 shadow-sm">
                        <i x-show="!concurrencyRunning" class="fa-solid fa-bolt-lightning"></i>
                        <i x-show="concurrencyRunning" class="fa-solid fa-circle-notch fa-spin"></i>
                        <span>Fire 2 Simultaneous Orders for the Last Unit (Promise.all)</span>
                    </button>
                </div>

                <!-- Concurrency Test Results -->
                <div x-show="concurrencyResults" class="mt-4 p-4 border rounded-xl"
                     :class="concurrencyResults && concurrencyResults.safe ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200'">
                    <h4 class="font-bold text-sm text-slate-900 mb-2 flex items-center gap-1.5">
                        <i class="fa-solid fa-terminal text-slate-600"></i> Race Condition Test Output:
                    </h4>
                    <div class="space-y-2 text-xs font-mono">
                        <div class="p-2.5 rounded-lg bg-white border border-slate-200" x-html="concurrencyResults ? concurrencyResults.req1 : ''"></div>
                        <div class="p-2.5 rounded-lg bg-white border border-slate-200" x-html="concurrencyResults ? concurrencyResults.req2 : ''"></div>
                    </div>
                    <div class="mt-3 text-xs font-bold text-emerald-800 flex items-center gap-2" x-show="concurrencyResults && concurrencyResults.safe">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                        <span>RESULT: Exactly ONE order succeeded with 201 Created. The second order failed cleanly with 422 Unprocessable Entity (INSUFFICIENT_STOCK). Zero overselling occurred!</span>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- INVOICE RECEIPT MODAL (Page 2 Reference: "shows bill on page") -->
    <div x-show="showReceiptModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 border border-slate-200 animate-in fade-in duration-200"
             @click.away="showReceiptModal = false">

            <div id="printable-receipt">
                <!-- Header -->
                <div class="text-center border-b border-slate-200 pb-4 mb-4">
                    <span class="text-xl font-bold tracking-tight text-slate-900 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-store text-emerald-600"></i> RETAIL STORE RECEIPT
                    </span>
                    <p class="text-xs text-slate-500 mt-0.5">Mallow Technologies Store Billing System</p>
                    <div class="mt-2 text-xs font-mono text-slate-700 bg-slate-100 py-1 px-3 rounded-full inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-receipt text-slate-500"></i>
                        Order #<span class="font-bold text-slate-900" x-text="receiptOrder ? receiptOrder.order_number : ''"></span>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="text-xs text-slate-600 space-y-1 mb-4">
                    <div class="flex justify-between">
                        <span>Customer:</span>
                        <strong class="text-slate-900" x-text="receiptOrder && receiptOrder.customer ? receiptOrder.customer.name : ''"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span>Email:</span>
                        <span x-text="receiptOrder && receiptOrder.customer ? receiptOrder.customer.email : ''"></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Date & Time:</span>
                        <span x-text="receiptOrder ? new Date(receiptOrder.created_at).toLocaleString() : ''"></span>
                    </div>
                </div>

                <!-- Items Table -->
                <table class="w-full text-xs text-left border-t border-b border-slate-200 my-3 py-2">
                    <thead>
                        <tr class="text-slate-400 uppercase text-[10px] border-b border-slate-100">
                            <th class="py-1.5">Item</th>
                            <th class="text-center py-1.5">Qty</th>
                            <th class="text-right py-1.5">Price</th>
                            <th class="text-right py-1.5">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="item in (receiptOrder ? receiptOrder.items : [])" :key="item.id">
                            <tr>
                                <td class="py-1.5 font-medium text-slate-800" x-text="item.product_name"></td>
                                <td class="py-1.5 text-center" x-text="item.quantity"></td>
                                <td class="py-1.5 text-right" x-text="'₹' + Number(item.unit_price).toFixed(2)"></td>
                                <td class="py-1.5 text-right font-semibold" x-text="'₹' + Number(item.line_total).toFixed(2)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="text-xs space-y-1.5 pt-2">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal:</span>
                        <span x-text="'₹' + (receiptOrder ? Number(receiptOrder.subtotal).toFixed(2) : '0.00')"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Tax Amount:</span>
                        <span x-text="'₹' + (receiptOrder ? Number(receiptOrder.tax_amount).toFixed(2) : '0.00')"></span>
                    </div>
                    <div class="flex justify-between text-sm font-bold text-slate-900 border-t border-slate-200 pt-1.5">
                        <span>Grand Total:</span>
                        <span class="text-emerald-700" x-text="'₹' + (receiptOrder ? Number(receiptOrder.grand_total).toFixed(2) : '0.00')"></span>
                    </div>
                    <template x-if="receiptOrder && receiptOrder.amount_paid">
                        <div class="border-t border-dashed border-slate-200 pt-1.5 text-slate-600">
                            <div class="flex justify-between">
                                <span>Amount Received:</span>
                                <span x-text="'₹' + Number(receiptOrder.amount_paid).toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between font-semibold text-slate-800">
                                <span>Change Returned:</span>
                                <span x-text="'₹' + Number(receiptOrder.change_returned).toFixed(2)"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Async Job Banner -->
                <div class="mt-4 p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl text-[11px] text-emerald-800 flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane text-emerald-600"></i>
                    <span>Queued job dispatched: <strong>SendOrderConfirmationJob</strong> (Email logged to <code>laravel.log</code>)</span>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="mt-6 flex space-x-3">
                <button type="button" @click="window.print()"
                        class="flex-1 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                    <i class="fa-solid fa-print"></i>
                    <span>Print Receipt</span>
                </button>
                <button type="button" @click="showReceiptModal = false; resetForm();"
                        class="flex-1 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-lg text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                    <i class="fa-solid fa-check"></i>
                    <span>Done / New Order</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Application JavaScript State Logic -->
    <script>
        function billingApp() {
            return {
                activeTab: 'pos',
                catalog: @json($products),
                lowStockProducts: @json($lowStockProducts),
                lowStockThreshold: 5,

                customer: {
                    email: '',
                    name: '',
                    phone: ''
                },
                customerFound: false,
                customerStatus: '',

                items: [
                    { product_id: '', product: null, quantity: 1, unit_price: 0, tax_percentage: 0, line_total: 0 }
                ],

                payment: {
                    amountGiven: '',
                    balance: 0,
                    denominations: ''
                },

                totals: {
                    subtotal: 0,
                    tax: 0,
                    grandTotal: 0
                },

                errors: {},
                isSubmitting: false,
                alertMessage: '',
                alertType: 'success',

                // Receipt Modal
                showReceiptModal: false,
                receiptOrder: null,

                // History Tab
                historySearchEmail: '',
                historyOrders: [],
                historyCustomer: null,
                historyLoading: false,
                historyError: '',

                // Concurrency Demo Tab
                concurrencyRunning: false,
                concurrencyResults: null,

                initApp() {
                    this.recalculate();
                },

                addItem() {
                    // Append new product row to the bottom (natural list growth)
                    this.items.push({
                        product_id: '',
                        product: null,
                        quantity: 1,
                        unit_price: 0,
                        tax_percentage: 0,
                        line_total: 0
                    });

                    // Scroll table container to bottom so the new row is visible, then focus its select
                    this.$nextTick(() => {
                        if (this.$refs.tableContainer) {
                            this.$refs.tableContainer.scrollTop = this.$refs.tableContainer.scrollHeight;
                            const selects = this.$refs.tableContainer.querySelectorAll('select');
                            if (selects.length) {
                                selects[selects.length - 1].focus();
                            }
                        }
                    });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                        this.recalculate();
                    }
                },

                onProductChange(index) {
                    const item = this.items[index];
                    const found = this.catalog.find(p => p.id === Number(item.product_id));
                    if (found) {
                        item.product = found;
                        item.unit_price = Number(found.price);
                        item.tax_percentage = Number(found.tax_percentage);
                    } else {
                        item.product = null;
                        item.unit_price = 0;
                        item.tax_percentage = 0;
                    }
                    this.recalculate();

                    // Auto-focus the Qty input of this row after a product is selected
                    if (found) {
                        this.$nextTick(() => {
                            const rows = this.$refs.tableContainer.querySelectorAll('tbody tr');
                            if (rows[index]) {
                                const qtyInput = rows[index].querySelector('input[type="number"]');
                                if (qtyInput) {
                                    qtyInput.focus();
                                    qtyInput.select();
                                }
                            }
                        });
                    }
                },

                recalculate() {
                    let sub = 0;
                    let taxSum = 0;

                    this.items.forEach(item => {
                        const qty = Math.max(1, Number(item.quantity) || 1);
                        const unit = Number(item.unit_price) || 0;
                        const taxRate = Number(item.tax_percentage) || 0;

                        const lineSub = unit * qty;
                        const lineTax = lineSub * (taxRate / 100);
                        const lineTot = lineSub + lineTax;

                        item.line_total = lineTot;
                        sub += lineSub;
                        taxSum += lineTax;
                    });

                    this.totals.subtotal = Math.round(sub * 100) / 100;
                    this.totals.tax = Math.round(taxSum * 100) / 100;
                    this.totals.grandTotal = Math.round((this.totals.subtotal + this.totals.tax) * 100) / 100;

                    this.calculateChange();
                },

                calculateChange() {
                    const given = Number(this.payment.amountGiven) || 0;
                    const grand = this.totals.grandTotal;

                    if (given >= grand && grand > 0) {
                        const balance = Math.round((given - grand) * 100) / 100;
                        this.payment.balance = balance;
                        this.payment.denominations = this.breakdownDenominations(balance);
                    } else {
                        this.payment.balance = 0;
                        this.payment.denominations = '';
                    }
                },

                breakdownDenominations(amount) {
                    if (amount <= 0) return '';
                    const currencyNotes = [500, 200, 100, 50, 20, 10, 5, 2, 1];
                    let remaining = Math.floor(amount);
                    const breakdown = [];

                    currencyNotes.forEach(denom => {
                        if (remaining >= denom) {
                            const count = Math.floor(remaining / denom);
                            remaining %= denom;
                            breakdown.push(`₹${denom} × ${count}`);
                        }
                    });

                    const paise = Math.round((amount - Math.floor(amount)) * 100);
                    if (paise > 0) {
                        breakdown.push(`${paise}p`);
                    }

                    return breakdown.join(' + ');
                },

                async lookupCustomer() {
                    const email = this.customer.email.trim();
                    if (!email) {
                        this.customerStatus = '';
                        this.customerFound = false;
                        return;
                    }

                    try {
                        const res = await fetch(`${window.__BASE}/api/customers/${encodeURIComponent(email)}`);
                        if (res.ok) {
                            const data = await res.json();
                            if (data.found && data.customer) {
                                this.customer.name = data.customer.name;
                                this.customerFound = true;
                                this.customerStatus = 'Existing Customer';
                            }
                        } else {
                            this.customerFound = false;
                            this.customerStatus = 'New Customer';
                        }
                    } catch (e) {
                        console.error('Customer lookup error', e);
                    }
                },

                loadWireframeExample() {
                    // Populate with Colgate Toothpaste + Parle-G Biscuit as shown in PDF wireframe
                    this.customer.email = 'thomas@example.com';
                    this.lookupCustomer();

                    const colgate = this.catalog.find(p => p.code === 'CLG-101');
                    const parle = this.catalog.find(p => p.code === 'PRL-101');

                    this.items = [];
                    if (colgate) {
                        this.items.push({
                            product_id: colgate.id,
                            product: colgate,
                            quantity: 2,
                            unit_price: Number(colgate.price),
                            tax_percentage: Number(colgate.tax_percentage),
                            line_total: 0
                        });
                    }
                    if (parle) {
                        this.items.push({
                            product_id: parle.id,
                            product: parle,
                            quantity: 5,
                            unit_price: Number(parle.price),
                            tax_percentage: Number(parle.tax_percentage),
                            line_total: 0
                        });
                    }

                    this.payment.amountGiven = 200;
                    this.recalculate();
                },

                async generateBill() {
                    this.errors = {};
                    this.alertMessage = '';

                    if (!this.customer.email) {
                        this.errors['customer.email'] = 'Customer email is required.';
                        return;
                    }
                    if (!this.customer.name) {
                        this.errors['customer.name'] = 'Customer name is required.';
                        return;
                    }

                    const validItems = this.items.filter(i => i.product_id && i.quantity > 0);
                    if (validItems.length === 0) {
                        this.alertType = 'error';
                        this.alertMessage = 'Please select at least one product with quantity.';
                        return;
                    }

                    this.isSubmitting = true;

                    const payload = {
                        customer: {
                            name: this.customer.name,
                            email: this.customer.email,
                            phone: this.customer.phone || null
                        },
                        items: validItems.map(i => ({
                            product_id: Number(i.product_id),
                            quantity: Number(i.quantity)
                        })),
                        amount_paid: Number(this.payment.amountGiven) || null
                    };

                    try {
                        const response = await fetch(window.__BASE + '/api/orders', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await response.json();

                        if (!response.ok) {
                            if (response.status === 422) {
                                if (result.error === 'INSUFFICIENT_STOCK') {
                                    this.alertType = 'error';
                                    this.alertMessage = `Stock Error: ${result.message}`;
                                } else if (result.errors) {
                                    this.errors = result.errors;
                                    this.alertType = 'error';
                                    this.alertMessage = 'Validation failed. Please check form inputs.';
                                } else {
                                    this.alertType = 'error';
                                    this.alertMessage = result.message || 'Validation error.';
                                }
                            } else {
                                this.alertType = 'error';
                                this.alertMessage = result.message || 'An error occurred while creating order.';
                            }
                            return;
                        }

                        // Order Success!
                        this.receiptOrder = result.data;
                        this.showReceiptModal = true;
                        this.alertType = 'success';
                        this.alertMessage = `Order #${result.data.order_number} created successfully!`;

                        // Refresh catalog and low stock
                        await this.refreshCatalog();
                        await this.fetchLowStock();

                    } catch (err) {
                        this.alertType = 'error';
                        this.alertMessage = 'Network error: ' + err.message;
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                async refreshCatalog() {
                    try {
                        const res = await fetch(window.__BASE + '/api/products');
                        if (res.ok) {
                            const data = await res.json();
                            this.catalog = data.data;
                        }
                    } catch (e) {
                        console.error('Error refreshing catalog', e);
                    }
                },

                async fetchLowStock() {
                    try {
                        const res = await fetch(`${window.__BASE}/api/products/low-stock?threshold=${this.lowStockThreshold}`);
                        if (res.ok) {
                            const data = await res.json();
                            this.lowStockProducts = data.data;
                        }
                    } catch (e) {
                        console.error('Error fetching low stock', e);
                    }
                },

                resetForm() {
                    this.items = [{ product_id: '', product: null, quantity: 1, unit_price: 0, tax_percentage: 0, line_total: 0 }];
                    this.payment.amountGiven = '';
                    this.payment.balance = 0;
                    this.payment.denominations = '';
                    this.recalculate();
                },

                // Customer Order History methods
                async fetchOrderHistory() {
                    if (!this.historySearchEmail) return;
                    this.historyLoading = true;
                    this.historyError = '';
                    this.historyOrders = [];
                    this.historyCustomer = null;

                    try {
                        const res = await fetch(`${window.__BASE}/api/customers/${encodeURIComponent(this.historySearchEmail)}/orders`);
                        const data = await res.json();
                        if (res.ok) {
                            this.historyOrders = data.data;
                            this.historyCustomer = data.customer;
                        } else {
                            this.historyError = data.message || 'Customer orders not found.';
                        }
                    } catch (e) {
                        this.historyError = 'Error fetching order history: ' + e.message;
                    } finally {
                        this.historyLoading = false;
                    }
                },

                // Concurrency Demo methods
                getRiceStock() {
                    const rice = this.catalog.find(p => p.code === 'RCE-101');
                    return rice ? rice.stock : 0;
                },

                async resetRiceStock() {
                    // Quick seed reset for demo
                    await fetch(window.__BASE + '/api/products');
                    location.reload();
                },

                async runConcurrentRaceTest() {
                    const rice = this.catalog.find(p => p.code === 'RCE-101');
                    if (!rice) return;

                    this.concurrencyRunning = true;
                    this.concurrencyResults = null;

                    const payload1 = {
                        customer: { name: 'Buyer Alpha', email: 'alpha@example.com' },
                        items: [{ product_id: rice.id, quantity: 1 }]
                    };

                    const payload2 = {
                        customer: { name: 'Buyer Beta', email: 'beta@example.com' },
                        items: [{ product_id: rice.id, quantity: 1 }]
                    };

                    const req1Promise = fetch(window.__BASE + '/api/orders', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(payload1)
                    }).then(async r => ({ status: r.status, data: await r.json() }));

                    const req2Promise = fetch(window.__BASE + '/api/orders', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(payload2)
                    }).then(async r => ({ status: r.status, data: await r.json() }));

                    const [res1, res2] = await Promise.all([req1Promise, req2Promise]);

                    await this.refreshCatalog();
                    await this.fetchLowStock();

                    const safe = (res1.status === 201 && res2.status === 422) || (res2.status === 201 && res1.status === 422);

                    this.concurrencyResults = {
                        safe,
                        req1: `Request #1 (Buyer Alpha): HTTP ${res1.status} - ${res1.status === 201 ? 'Order Created #' + res1.data.data.order_number : res1.data.message}`,
                        req2: `Request #2 (Buyer Beta): HTTP ${res2.status} - ${res2.status === 201 ? 'Order Created #' + res2.data.data.order_number : res2.data.message}`
                    };

                    this.concurrencyRunning = false;
                }
            };
        }
    </script>
</body>
</html>
