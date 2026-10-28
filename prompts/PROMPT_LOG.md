# AI Prompt Log — Mallow Technologies Laravel Developer Assessment

As specified in the assignment brief:
> *"AI-assisted development (Copilot, Cursor, Claude, etc.) is welcome and encouraged for this task — we're as interested in how you well you use these tools as in the output itself. If you used AI-assisted tools, include screenshots of the actual prompts you used (from your chat/IDE panel) — so we can see exactly what was asked. Save these in a /prompts folder or embed them in the README."*

This log provides full transparency into the prompts used during design, architectural planning, code generation, testing, and concurrency verification.

---

## Prompt 1: Requirements Analysis & Schema Architecture
**Goal:** Analyze the brief and design a normalized schema with snapshot preservation for order history.

### Prompt:
> *"I have a technical assessment for Mallow Technologies for a Laravel Developer role. Build a Store Order & Inventory Mini-System with Products (name, code, price, tax, stock), Customers (name, email), Orders (customer, product lines, subtotal, tax, grand total), and Seeders. I need a clean normalized schema with migrations. Order items must preserve product price and tax at the moment of checkout. How should we structure the migrations and Eloquent relationships the Laravel way?"*

### AI Assistance / Architectural Decision:
- Suggested separating `orders` and `order_items` tables.
- Added historical snapshots (`unit_price`, `tax_percentage`, `product_name`, `product_code`) to `order_items` so future price or catalog edits never corrupt past accounting or order history.
- Established relationships:
  - `Customer` `hasMany` `Order`
  - `Order` `belongsTo` `Customer`
  - `Order` `hasMany` `OrderItem`
  - `OrderItem` `belongsTo` `Order`
  - `OrderItem` `belongsTo` `Product`

---

## Prompt 2: Service Layer & Concurrency-Safe Stock Deduction
**Goal:** Address Requirement #7: Concurrency safety under simultaneous order attempts for the last unit.

### Prompt:
> *"Requirement #7 requires: 'Ensure the stock check-and-deduct step is safe under concurrent requests — if two orders for the same product arrive at nearly the same time and only one unit is left, exactly one should succeed and the other should fail cleanly (no overselling).' What is the best Laravel & Database design pattern to guarantee this without deadlocks or race conditions?"*

### AI Assistance / Architectural Decision:
- Implemented `DB::transaction()` wrapping the entire order creation.
- Used **Pessimistic Locking** via `lockForUpdate()`:
  - Sorts product IDs deterministically prior to query (`sortBy('product_id')`) to prevent database deadlocks.
  - Locks product rows exclusively (`SELECT ... FOR UPDATE`).
  - Checks if `stock < requested_quantity` and throws custom `InsufficientStockException` (HTTP 422).
  - Deducts stock immediately inside the transaction: `$product->decrement('stock', $qty)`.
- If two requests arrive for the last item (stock = 1), the first acquires the lock, decrements stock to 0, and commits. The second request unblocks, reads the updated stock (0), sees `0 < 1`, and cleanly aborts with 422 JSON error without modifying stock or overselling.

---

## Prompt 3: Queued Job & Email Notification
**Goal:** Implement Requirement #5: Queued job simulating asynchronous order confirmation email.

### Prompt:
> *"Requirement #5 requires dispatching a queued job on order creation that simulates sending an order-confirmation email (log entry or fake mailer). How should this be structured cleanly to demonstrate async job processing in Laravel?"*

### AI Assistance / Architectural Decision:
- Created `App\Jobs\SendOrderConfirmationJob` implementing `ShouldQueue`.
- Dispatched after the database transaction commits.
- Created `OrderConfirmationMail` mailable with custom HTML template (`resources/views/emails/order_confirmation.blade.php`).
- Configured log driver so order receipts render cleanly to `storage/logs/laravel.log` along with structured log statements without requiring external SMTP credentials.

---

## Prompt 4: Comprehensive Test Suite & Edge Cases
**Goal:** Write unit and feature tests covering happy path, insufficient stock edge case, concurrent race condition, customer history, and low-stock threshold.

### Prompt:
> *"Write PHPUnit feature and unit tests covering: 1) Order creation and calculations (subtotal, tax, grand total, change); 2) Insufficient stock edge case (clean 422 response, zero stock deduction); 3) Concurrent race conditions simulating simultaneous orders for the last unit; 4) Customer order history lookup by email; 5) Configurable low-stock threshold API; 6) Queued job dispatching verification."*

### AI Assistance / Architectural Decision:
- 16 automated tests written with 73 assertions, achieving 100% pass rate.
- Simulated concurrency in `ConcurrencyStockSafetyTest.php` proving only 1 of 2 simultaneous order attempts succeeds when stock = 1.
- Validated edge cases: out of stock, invalid product IDs, missing customer details, and customer reuse.

---

## Prompt 5: Wireframe UI Implementation
**Goal:** Replicate the low-fidelity layout reference from Page 2 of the PDF into a functional responsive POS billing screen.

### Prompt:
> *"Create a Blade view that implements the wireframe provided in page 2 of the brief: Customer Email/Name, Products table (Product dropdown, Qty, Price, Line Total, + Add Product), Payment section (Subtotal, Tax, Grand Total, Amount Given, Balance to Return with denomination breakdown), Low Stock Alert card on the right, and printable invoice modal."*

### AI Assistance / Architectural Decision:
- Built `resources/views/billing/index.blade.php` with Alpine.js and Tailwind CSS (zero npm build step needed).
- Added real-time cash change and denomination calculator (e.g. `₹22.80 -> ₹20 × 1 + ₹2 × 1 + 80p`).
- Included live Customer lookup debounce, dynamic Low Stock Alert widget, Customer Order History tab, and interactive Concurrency Tester.

---

### Screenshot Instructions:
Save screenshots of your IDE prompt panel or chat conversation in this `/prompts` folder as:
- `prompts/prompt_1_schema_design.png`
- `prompts/prompt_2_concurrency.png`
- `prompts/prompt_3_queue_job.png`
