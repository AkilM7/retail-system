# Retail Store Order & Inventory Mini-System
**Mallow Technologies — Laravel Developer Round 1 Technical Assessment**

An enterprise-ready, concurrency-safe retail billing and inventory management application built with **Laravel 11 / 12 / 13**, demonstrating normalized schema design, thin controllers, service layer architecture, pessimistic locking concurrency control, asynchronous queued email jobs, automated PHPUnit testing, and an interactive billing POS user interface matching the take-home assignment brief.

---

## 📋 Table of Contents
1. [Core Features & Requirements Matrix](#-core-features--requirements-matrix)
2. [Architectural Highlights & Design Decisions](#-architectural-highlights--design-decisions)
3. [Concurrency Control (Requirement #7)](#-concurrency-control-requirement-7)
4. [Asynchronous Queued Email Job (Requirement #5)](#-asynchronous-queued-email-job-requirement-5)
5. [Database Schema & Entity Relationship](#-database-schema--entity-relationship)
6. [Assumptions Made Under Ambiguity](#-assumptions-made-under-ambiguity)
7. [Installation & Setup Instructions](#-installation--setup-instructions)
8. [Automated Testing Suite (Requirement #6)](#-automated-testing-suite-requirement-6)
9. [API Documentation with cURL Examples](#-api-documentation-with-curl-examples)
10. [Web UI (Wireframe Reference)](#-web-ui-wireframe-reference)
11. [Prompt Log & AI Assistance](#-prompt-log--ai-assistance)
12. [Video Walkthrough Guide](#-video-walkthrough-guide)

---

## 🚀 Core Features & Requirements Matrix

| Requirement | Brief Description | Implementation Details | Status |
|---|---|---|:---:|
| **Req #1** | Normalized Schema & Migrations | `products`, `customers`, `orders`, `order_items` with snapshots, foreign keys, and indexes. | ✅ Complete |
| **Req #2** | API Endpoint: Create Order | `POST /api/orders` — validates stock, aggregates duplicate items, locks rows, computes tax/subtotal, deducts stock, returns order with items. | ✅ Complete |
| **Req #3** | API Endpoint: Customer Order History | `GET /api/customers/{email}/orders` — fetches customer's past orders with line items, tax details, and order count. | ✅ Complete |
| **Req #4** | API Endpoint: Low-Stock Products | `GET /api/products/low-stock?threshold=5` — configurable threshold fallback to `config('inventory.low_stock_threshold')`. | ✅ Complete |
| **Req #5** | Asynchronous Queued Email Job | `SendOrderConfirmationJob` implements `ShouldQueue` with 3 retries, compiles `OrderConfirmationMail`, and logs HTML/telemetry to `laravel.log`. | ✅ Complete |
| **Req #6** | Automated Tests (Edge Cases) | 16 automated PHPUnit tests with 73 assertions covering happy path, 422 insufficient stock, customer reuse, job dispatching, and calculations. | ✅ Complete |
| **Req #7** | Concurrency Safety (No Overselling) | `DB::transaction()` + `lockForUpdate()` with deterministic sorted product keys prevents race conditions & deadlocks. Tested via `ConcurrencyStockSafetyTest`. | ✅ Complete |
| **UI** | Interactive Retail Billing POS | Responsive Blade + Tailwind + Alpine.js screen matching the provided PDF wireframe with real-time cash change and denomination calculator. | ✅ Complete |

---

## 🏛️ Architectural Highlights & Design Decisions

This project follows strict Laravel engineering best practices:
- **Thin Controllers:** `OrderController`, `ProductController`, and `CustomerController` contain zero business logic. They solely validate incoming requests, invoke the service layer, and return formatted responses.
- **Dedicated Service Layer (`App\Services\OrderService`):** Houses all transaction management, stock locking, duplicate line aggregation, price computation, customer resolution, and event/job dispatching.
- **Form Request Validation (`App\Http\Requests\StoreOrderRequest`):** Deep nested validation of customer profile (`email`, `name`, `phone`) and order line items (`product_id`, `quantity >= 1`).
- **Consistent API Resources (`App\Http\Resources`):** Standardizes JSON output schemas for `OrderResource`, `OrderItemResource`, `ProductResource`, and `CustomerResource`.
- **Custom Exceptions (`App\Exceptions\InsufficientStockException`):** Thrown when requested stock exceeds availability; self-renders as a standardized HTTP 422 JSON response with structured product details.
- **Historical Price & Tax Preservation:** `order_items` records snapshots of `unit_price`, `tax_percentage`, `product_name`, and `product_code` at the time of purchase. Future price changes or product renames never alter historical invoices.

---

## ⚡ Concurrency Control (Requirement #7)

> **Brief Requirement:** *"Ensure the stock check-and-deduct step is safe under concurrent requests — if two orders for the same product arrive at nearly the same time and only one unit is left, exactly one should succeed and the other should fail cleanly (no overselling)."*

### How Concurrency is Guaranteed:
1. **Transaction Boundary (`DB::transaction`):** The entire sequence (reading stock, validating quantity, updating stock, and inserting order records) executes within an atomic database transaction.
2. **Pessimistic Row Locking (`lockForUpdate()`):**
   ```php
   $products = Product::whereIn('id', $sortedProductIds)
       ->lockForUpdate()
       ->get()
       ->keyBy('id');
   ```
   In SQL, this issues `SELECT ... FOR UPDATE`. An exclusive lock is acquired on the selected product rows until the transaction commits or rolls back.
3. **Deadlock Prevention (Deterministic Lock Ordering):** If Order A requests products `[2, 5]` and Order B requests `[5, 2]`, simultaneous transactions could deadlock each other. To avoid this, `OrderService` sorts product IDs in ascending order (`$sortedProductIds = $items->keys()->sort()`) prior to acquiring locks.
4. **Clean Abort on Race:** If Transaction 1 and Transaction 2 arrive simultaneously for a product with 1 unit:
   - Transaction 1 acquires the row lock first.
   - Transaction 2 pauses and waits for Transaction 1 to release the lock.
   - Transaction 1 validates `1 >= 1`, decrements stock to 0, saves, and commits.
   - Transaction 2 wakes up with the lock, reads the committed stock (`0`), evaluates `0 < 1`, and throws `InsufficientStockException`.
   - Transaction 2 automatically rolls back without making any database changes.
   - The API client receives a clean `422 Unprocessable Entity` response with code `INSUFFICIENT_STOCK`.

This behavior is verified in `tests/Feature/ConcurrencyStockSafetyTest.php` and via the **Concurrency Tester** tab in the web UI.

---

## 📨 Asynchronous Queued Email Job (Requirement #5)

> **Brief Requirement:** *"Dispatch a queued job on order creation that simulates sending an order-confirmation email (a log entry or fake mailer is fine — no real SMTP needed)."*

### Implementation:
- **Job:** `App\Jobs\SendOrderConfirmationJob` implements `Illuminate\Contracts\Queue\ShouldQueue`.
- **Mailable:** `App\Mail\OrderConfirmationMail` with HTML email template (`resources/views/emails/order_confirmation.blade.php`).
- **Driver:** Uses Laravel's `database` queue driver and `log` mail driver.
- When an order is created, the job is dispatched:
  ```php
  SendOrderConfirmationJob::dispatch($order);
  ```
- When processed by `php artisan queue:work`, it renders the order confirmation invoice and logs both telemetry and the full HTML email to `storage/logs/laravel.log`:
  ```
  [2026-10-28 05:01:30] local.INFO: [SendOrderConfirmationJob] Order confirmation email successfully sent to thomas@example.com for Order #ORD-20261028-KK4GT.
  ```

---

## 🗄️ Database Schema & Entity Relationship

```
+-----------------------------------+       +-----------------------------------+
|             customers             |       |              orders               |
+-----------------------------------+       +-----------------------------------+
| id: bigint unsigned (PK)          |<---+  | id: bigint unsigned (PK)          |
| name: varchar(255)                |    |  | order_number: varchar(50) (UQ)    |
| email: varchar(255) (UQ, Index)   |    +--| customer_id: bigint unsigned (FK) |
| phone: varchar(30) (nullable)     |       | subtotal: decimal(10,2)           |
| created_at, updated_at            |       | tax_amount: decimal(10,2)         |
+-----------------------------------+       | grand_total: decimal(10,2)        |
                                            | amount_paid: decimal(10,2) (null) |
+-----------------------------------+       | change_returned: decimal(10,2)    |
|             products              |       | status: varchar(30)               |
+-----------------------------------+       | created_at, updated_at (Index)    |
| id: bigint unsigned (PK)          |       +-----------------------------------+
| name: varchar(255)                |                         |
| code: varchar(50) (UQ, Index)     |                         | 1
| price: decimal(10,2)              |                         |
| tax_percentage: decimal(5,2)      |                         | hasMany
| stock: int unsigned (Index)       |                         |
| created_at, updated_at            |                         v N
+-----------------------------------+       +-----------------------------------+
                  ^                         |            order_items            |
                  |                         +-----------------------------------+
                  |                         | id: bigint unsigned (PK)          |
                  +-------------------------| order_id: bigint unsigned (FK)    |
                        belongsTo           | product_id: bigint unsigned (FK)  |
                                            | product_name: varchar(255)        |
                                            | product_code: varchar(50)         |
                                            | unit_price: decimal(10,2)         |
                                            | tax_percentage: decimal(5,2)      |
                                            | quantity: int unsigned            |
                                            | line_subtotal: decimal(10,2)      |
                                            | line_tax: decimal(10,2)           |
                                            | line_total: decimal(10,2)         |
                                            | created_at, updated_at            |
                                            +-----------------------------------+
```

---

## 💡 Assumptions Made Under Ambiguity

As instructed in the brief:
> *"If anything is ambiguous, use your best judgment, make a reasonable assumption, and document it in your README."*

1. **Customer Identification by Email:** Customers are uniquely identified by their email. If an order payload contains an email that already exists in the database, the order links to that existing customer record. If a new name or phone is provided, the customer profile is updated without duplicating customer records.
2. **Duplicate Product Lines in Order Payload:** If an API client submits multiple lines referencing the same `product_id` (e.g., `qty: 2` and `qty: 3`), `OrderService` aggregates the total requested quantity (`5`) prior to checking stock to prevent partial deduction inconsistencies.
3. **Product Price & Tax Snapshot:** Rather than calculating invoice history dynamically from current product prices, snapshot columns are preserved in `order_items`. This ensures that accounting history remains immutable even if product prices or tax rates change later.
4. **Denomination Breakdown for Change:** Based on the wireframe note (`₹22.80 -> 1x20 + 1x2 + 1x1`), the payment system includes real-time denomination breakdown logic for cash returned in Indian Rupees (₹500, ₹200, ₹100, ₹50, ₹20, ₹10, ₹5, ₹2, ₹1, and paise).
5. **Configurable Low-Stock Threshold:** The default low-stock threshold is configured at `5` in `config/inventory.php` (overridable via `LOW_STOCK_THRESHOLD` in `.env`), but the API endpoint `GET /api/products/low-stock?threshold=...` also allows the client to provide dynamic thresholds on demand.

---

## 🛠️ Installation & Setup Instructions

### Prerequisites
- PHP 8.2 or higher (PHP 8.5 tested)
- Composer 2.x
- SQLite extension or MySQL (SQLite is enabled by default for zero-friction setup)

### 1. Clone & Enter Project Directory
```bash
git clone <your-repo-url> retail-order-system
cd retail-order-system
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Migrations & Seed Data
Run migrations and populate the catalog with wireframe products (Colgate, Parle-G, Bread, Milk 1L, Eggs, Basmati Rice, etc.):
```bash
php artisan migrate --seed
```

### 5. Start the Application Server
```bash
php artisan serve
```
Open your browser to: **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

### 6. Run the Background Queue Worker (in a separate terminal)
To process the asynchronous order-confirmation email jobs:
```bash
php artisan queue:work
```

---

## 🧪 Automated Testing Suite (Requirement #6)

Run the full PHPUnit test suite:
```bash
php artisan test
```

### Test Coverage Highlights:
- **`OrderCreationTest`:**
  - `test_order_can_be_created_successfully_with_correct_totals_and_stock_deduction`: Validates mathematical calculations, stock deduction, and queue dispatch.
  - `test_order_creation_fails_when_product_has_insufficient_stock`: Verifies HTTP 422 error, clean rollback, and zero stock loss.
  - `test_order_creation_aggregates_duplicate_items_and_validates_combined_stock`: Ensures multi-line duplicate items are aggregated.
  - `test_order_creation_rejects_missing_customer_or_empty_items`: Tests form-request validation constraints.
  - `test_order_creation_reuses_existing_customer_profile`: Verifies customer deduplication.
- **`ConcurrencyStockSafetyTest`:**
  - `test_concurrent_order_requests_for_last_unit_allow_only_one_success_and_prevent_overselling`: Simulates race conditions on a product with 1 unit remaining.
  - `test_multi_product_orders_acquire_locks_deterministically`: Verifies deadlock prevention through sorted key locking.
- **`CustomerOrderHistoryTest`:**
  - `test_can_fetch_customer_order_history_by_email`: Verifies customer order retrieval with line items and totals.
  - `test_customer_order_history_returns_404_for_unknown_customer`: Edge case for non-existent customers.
- **`LowStockProductsTest`:**
  - `test_can_fetch_products_below_default_low_stock_threshold`: Verifies default threshold filtering.
  - `test_can_fetch_products_with_custom_threshold_query_parameter`: Verifies dynamic threshold query param.
- **`OrderServiceTest`:**
  - Unit tests for customer normalization, tax percentage formulas, and balance calculation.

---

## 🔌 API Documentation with cURL Examples

### 1. Create an Order (Req #2)
- **Method:** `POST`
- **URL:** `/api/orders`
- **Headers:** `Content-Type: application/json`, `Accept: application/json`

**cURL Request:**
```bash
curl -X POST http://127.0.0.1:8000/api/orders \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer": {
      "name": "Thomas Anderson",
      "email": "thomas@example.com",
      "phone": "+91 98765 43210"
    },
    "items": [
      { "product_id": 1, "quantity": 2 },
      { "product_id": 2, "quantity": 5 }
    ],
    "amount_paid": 200.00
  }'
```

**Success Response (`201 Created`):**
```json
{
  "data": {
    "id": 2,
    "order_number": "ORD-20261028-AB123",
    "status": "completed",
    "subtotal": 150.00,
    "tax_amount": 12.00,
    "grand_total": 162.00,
    "amount_paid": 200.00,
    "change_returned": 38.00,
    "customer": {
      "id": 1,
      "name": "Thomas Anderson",
      "email": "thomas@example.com",
      "phone": "+91 98765 43210"
    },
    "items": [
      {
        "id": 3,
        "product_id": 1,
        "product_name": "Colgate Toothpaste",
        "product_code": "CLG-101",
        "unit_price": 50.00,
        "tax_percentage": 8.00,
        "quantity": 2,
        "line_subtotal": 100.00,
        "line_tax": 8.00,
        "line_total": 108.00
      },
      {
        "id": 4,
        "product_id": 2,
        "product_name": "Parle-G Biscuit",
        "product_code": "PRL-101",
        "unit_price": 10.00,
        "tax_percentage": 8.00,
        "quantity": 5,
        "line_subtotal": 50.00,
        "line_tax": 4.00,
        "line_total": 54.00
      }
    ]
  }
}
```

**Insufficient Stock Response (`422 Unprocessable Entity`):**
```json
{
  "message": "Insufficient stock for product 'Basmati Rice 1kg' (Code: RCE-101). Available: 0, Requested: 1.",
  "error": "INSUFFICIENT_STOCK",
  "details": {
    "product_id": 6,
    "product_name": "Basmati Rice 1kg",
    "available_stock": 0,
    "requested_quantity": 1
  }
}
```

---

### 2. Fetch Customer Order History (Req #3)
- **Method:** `GET`
- **URL:** `/api/customers/{email}/orders`

**cURL Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/customers/thomas@example.com/orders" \
  -H "Accept: application/json"
```

**Success Response (`200 OK`):**
```json
{
  "data": [
    {
      "id": 2,
      "order_number": "ORD-20261028-AB123",
      "status": "completed",
      "subtotal": 150.00,
      "tax_amount": 12.00,
      "grand_total": 162.00,
      "created_at": "2026-10-28T05:00:00.000000Z",
      "items": [ ... ]
    }
  ],
  "customer": {
    "id": 1,
    "name": "Thomas Anderson",
    "email": "thomas@example.com"
  },
  "total_orders": 1
}
```

---

### 3. Fetch Low-Stock Products (Req #4)
- **Method:** `GET`
- **URL:** `/api/products/low-stock?threshold=5`

**cURL Request:**
```bash
curl -X GET "http://127.0.0.1:8000/api/products/low-stock?threshold=5" \
  -H "Accept: application/json"
```

**Success Response (`200 OK`):**
```json
{
  "data": [
    { "id": 6, "name": "Basmati Rice 1kg", "code": "RCE-101", "price": 120.0, "stock": 1, "is_low_stock": true },
    { "id": 5, "name": "Eggs (12)", "code": "EGG-101", "price": 84.0, "stock": 2, "is_low_stock": true },
    { "id": 3, "name": "Bread", "code": "BRD-101", "price": 40.0, "stock": 4, "is_low_stock": true }
  ],
  "meta": {
    "threshold": 5,
    "count": 3
  }
}
```

---

### 4. CLI Order Simulation Helper
For rapid terminal demonstration without cURL:
```bash
php artisan order:simulate --email=thomas@example.com --product=CLG-101 --qty=2
```

---

## 🖥️ Web UI (Wireframe Reference)

Visit **`http://127.0.0.1:8000`** in any browser.

The web UI includes:
- **POS Billing Screen:** Direct realization of the Page 2 wireframe reference.
  - Auto-fills customer details upon entering an existing email.
  - Interactive table allowing adding/removing product lines with dynamic price, tax, and line total updates.
  - Real-time Subtotal, Tax, and Grand Total computations.
  - Cash received input with dynamic **Balance to Return** and denomination breakdown (`₹20 × 1 + ₹2 × 1...`).
  - **Low Stock Alert Card:** Continuously synced with live inventory.
  - **Invoice Receipt Modal:** Printable receipt with a confirmation badge for the queued email job.
- **Customer Order History Tab:** Look up full order histories by customer email.
- **Interactive Concurrency Tester:** Triggers two simultaneous orders via `Promise.all` targeting the last unit of Basmati Rice to demonstrate clean 201 vs 422 behavior right on screen!

---

## 🤖 Prompt Log & AI Assistance

As requested in the brief, the complete prompt history, architectural discussions, and decision logs are documented in:
👉 **[`prompts/PROMPT_LOG.md`](prompts/PROMPT_LOG.md)**

---

## 🎥 Video Walkthrough Guide

A step-by-step narration script with timestamps and code walkthrough directions is provided in:
👉 **[`VIDEO_WALKTHROUGH_SCRIPT.md`](VIDEO_WALKTHROUGH_SCRIPT.md)**
Use this script to guide your 5–10 minute narrated screen video submission.

---

## 👨‍💻 Candidate Details
- **Candidate:** Akil Murugan
- **Role:** Laravel Developer — Take-Home Assignment (Round 1)
- **Company:** Mallow Technologies
