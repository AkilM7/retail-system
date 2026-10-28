# 5-10 Minute Video Walkthrough Narration Script
**Mallow Technologies — Laravel Developer Round 1 Mini Task**

Use this script as your guide while recording your 5–10 minute screen video with voice narration.

---

## ⏱️ Minute 0:00 – 1:00: Introduction & Overview
- **Action:** Open your browser to `http://127.0.0.1:8000`.
- **Narration:**
  > *"Hello Mallow Technologies hiring team. My name is Akil Murugan, and this is my submission for the Laravel Developer Round 1 Mini Task: Store Order & Inventory Mini-System.*
  >
  > *In this walkthrough, I will demonstrate how all functional requirements and edge cases are met, including our normalized database schema, thin controllers with service layer architecture, queued asynchronous email processing, concurrency-safe inventory deduction, our test suite, and the interactive retail billing UI matching your wireframe reference."*

---

## ⏱️ Minute 1:00 – 2:30: Retail Billing UI & Order Creation (Wireframe Demo)
- **Action:** Show the web interface. Point out the Customer section, Products table, Payment section, and Low Stock Alert on the right.
- **Narration:**
  > *"Starting with the UI: I implemented the exact layout structure from the wireframe reference in the brief.*
  >
  > *Notice on the top right, our 'Low Stock Alert' card immediately displays items that are at or below our configurable threshold of 5 units — like Bread with 4 units, Milk 1L with 9 units (if threshold is 10), and Eggs with 2 units.*
  >
  > *Now, let's enter a customer email. If I type `thomas@example.com` and press Tab or click Lookup, the system queries our API endpoint `GET /api/customers/{email}` and automatically populates the customer name 'Thomas Anderson'. If a new customer email is entered, they can simply type their name.*
  >
  > *Next, let's click 'Fill Wireframe Sample' or add products manually: we add 2 units of Colgate Toothpaste at ₹50 each (8% tax) and 5 units of Parle-G Biscuit at ₹10 each (8% tax).*
  >
  > *Notice the dynamic line total and real-time totals calculation: Subtotal ₹150.00, Tax ₹12.00, Grand Total ₹162.00.*
  >
  > *In the payment box, if the customer gives ₹200, the system automatically computes the Balance to Return: ₹38.00, with an exact currency denomination breakdown (`₹20 × 1 + ₹10 × 1 + ₹5 × 1 + ₹2 × 1 + ₹1 × 1`), exactly matching the wireframe requirement.*
  >
  > *Now, when I click 'Generate Bill', the UI sends a POST request to `/api/orders`."*

- **Action:** Click "Generate Bill". Show the receipt modal popup.
- **Narration:**
  > *"The order is created instantly! The modal shows the formal receipt with unique order number, customer name, date, itemized table, and tax calculations. It also features a 1-click 'Print Receipt' button.*
  >
  > *Notice the green banner confirming our queued job was dispatched: `SendOrderConfirmationJob`."*

---

## ⏱️ Minute 2:30 – 3:30: Asynchronous Queued Job Demo (Req #5)
- **Action:** Switch to terminal / VS Code. Show `app/Jobs/SendOrderConfirmationJob.php` and open `storage/logs/laravel.log`.
- **Narration:**
  > *"Requirement #5 asks for a queued job that simulates sending an order-confirmation email without blocking the HTTP request.*
  >
  > *Here in `app/Jobs/SendOrderConfirmationJob.php`, our job implements `ShouldQueue` with 3 retries. When the order is committed, `SendOrderConfirmationJob::dispatch($order)` pushes the job to the database queue.*
  >
  > *When we run `php artisan queue:work`, the worker executes the job, compiles the `OrderConfirmationMail` HTML mailable, and logs both structured telemetry and the complete invoice email to `laravel.log`. Here in `storage/logs/laravel.log`, we can see the exact email confirmation dispatched to `thomas@example.com`."*

---

## ⏱️ Minute 3:30 – 5:00: Concurrency Safety & Race Condition Demo (Req #7)
- **Action:** Open the "Concurrency Tester" tab in the web UI, or open `tests/Feature/ConcurrencyStockSafetyTest.php`.
- **Narration:**
  > *"Requirement #7 is one of the most critical requirements in this assignment: ensuring safe stock deduction under concurrent requests without overselling.*
  >
  > *Let me explain how we solved this architecturally in `app/Services/OrderService.php`:*
  > *First, we wrap the entire order creation inside a `DB::transaction()`.*
  > *Second, we sort the requested product IDs deterministically before acquiring locks. This prevents database deadlocks when multiple transactions touch the same items in different orders.*
  > *Third, we apply pessimistic locking using `Product::whereIn('id', $sortedIds)->lockForUpdate()->get()`.*
  > *If any item has `stock < requested_quantity`, we immediately abort and throw a custom `InsufficientStockException`, which renders as an HTTP 422 JSON error.*
  >
  > *Let's see this in action right now! We have 'Basmati Rice 1kg' with exactly 1 unit left.*
  > *When I click 'Fire 2 Simultaneous Orders for the Last Unit', the app uses `Promise.all` to launch two orders simultaneously for Buyer Alpha and Buyer Beta.*
  > *Look at the result: Request #1 succeeded with HTTP 201 Created and deducted the unit. Request #2 was locked out until transaction #1 finished, then saw 0 stock remaining, and cleanly failed with HTTP 422 Unprocessable Entity (`INSUFFICIENT_STOCK`). Zero overselling occurred!"*

---

## ⏱️ Minute 5:00 – 6:30: Customer Order History & Low Stock APIs (Req #3 & #4)
- **Action:** Switch to the "Customer History API" tab in the browser or show cURL / Postman.
- **Narration:**
  > *"Requirement #3 asks for an API endpoint to fetch a customer's order history by email.*
  > *In `app/Http/Controllers/Api/CustomerController.php`, the `orders()` method handles `GET /api/customers/{email}/orders`. When I search `thomas@example.com`, it returns all orders placed by Thomas, sorted newest first, with line items, tax breakdowns, and total order count.*
  >
  > *Requirement #4 asks for an API endpoint returning products below a configurable low-stock threshold.*
  > *In `app/Http/Controllers/Api/ProductController.php`, `GET /api/products/low-stock` reads the threshold from `config('inventory.low_stock_threshold')` or an optional query parameter like `?threshold=10`. It returns low-stock items sorted in ascending order of urgency."*

---

## ⏱️ Minute 6:30 – 7:30: Code Structure & Laravel Best Practices
- **Action:** Show the project folder structure in VS Code:
  - `app/Http/Controllers/Api/` (thin controllers)
  - `app/Http/Requests/StoreOrderRequest.php` (form request validation)
  - `app/Services/OrderService.php` (business logic, transaction, stock lock)
  - `app/Http/Resources/` (API resource transformations)
  - `app/Exceptions/InsufficientStockException.php`
  - `database/migrations/` (normalized schema)
- **Narration:**
  > *"Following Laravel best practices:*
  > *- Our controllers are extremely thin — only delegating input to Form Requests and operations to `OrderService`.*
  > *- Request validation is isolated in `StoreOrderRequest`.*
  > *- API responses are transformed consistently using API Resources (`OrderResource`, `ProductResource`).*
  > *- Database migrations are normalized with foreign key constraints, indexes, and historical price/tax snapshots on `order_items`.*
  > *- Seeders use factories to seed realistic store data."*

---

## ⏱️ Minute 7:30 – 8:30: Automated Test Suite (Req #6)
- **Action:** In terminal, run: `php artisan test`.
- **Narration:**
  > *"Finally, let's run the automated test suite using `php artisan test`.*
  > *We have 16 comprehensive tests and 73 assertions covering:*
  > *1. Successful order creation with accurate math for subtotal, tax, and change returned.*
  > *2. Stock deduction verification.*
  > *3. Insufficient stock edge case (clean 422 response and untouched stock).*
  > *4. Duplicate item aggregation in a single order payload.*
  > *5. Concurrency race conditions simulating simultaneous orders for the last unit.*
  > *6. Customer order history endpoint.*
  > *7. Low stock threshold endpoint with custom query parameters.*
  > *8. Queued job dispatching verification.*
  >
  > *All 16 tests pass 100% green."*

---

## ⏱️ Minute 8:30 – 9:00: Conclusion
- **Narration:**
  > *"The repository includes full setup instructions in `README.md`, prompt documentation in `/prompts`, and pre-seeded data for easy evaluation.*
  > *Thank you for your time, and I look forward to discussing this in the round 1 interview!"*
