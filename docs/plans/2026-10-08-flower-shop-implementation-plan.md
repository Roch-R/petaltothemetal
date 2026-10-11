# Flower Shop Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a responsive flower shop school project where registered customers order arrangements with simulated payment, delivery or pickup, and staff manage products, inventory, and orders.

**Architecture:** Render HTML using PHP pages and shared templates; use plain CSS and vanilla JavaScript for presentation and progressive enhancement. PHP sessions manage authentication and the cart, while PDO and a relational database handle persistent records and transactional checkout. Keep reusable application services outside the public web directory.

**Tech Stack:** HTML, CSS, vanilla JavaScript, PHP with PDO MySQL, MySQL-compatible InnoDB SQL, XAMPP, and a dependency-free PHP test runner.

---

## Context and boundaries

Date: 2026-10-08. Approved reference: docs/plans/2026-10-08-flower-shop-design.md. User confirmed XAMPP as the local development environment.

The project currently contains the approved design document and no application code. Git's repository root is C:/Users/Jayrald/Desktop/Projects, one level above this project. Other sibling projects are untracked: do not stage, alter, or clean them. All task paths below are relative to the flower shop project root. Use path-specific staging and commits; never run an unrestricted git add.

Planning happens in the existing project so the document is immediately reviewable. Before application implementation, use @using-git-worktrees if isolation is needed; any worktree must live within the authorized workspace, be ignored, and account for the parent repository root. A worktree of this repository contains a nested Petal to the Metal project directory: run project commands from that directory, not from the worktree root. Do not relocate or delete the original project.

Local inspection found:
- C:/xampp/php/php.exe: PHP 8.0.30.
- C:/xampp/mysql/bin/mysql.exe: MariaDB 10.4.32 client.
- PDO mysql is available.
- php and mysql are not currently on PATH.
- The database server's running state, connection credentials, and actual server version have not been verified.

XAMPP's bundled database is MariaDB, rather than Oracle MySQL. Use SQL supported by both; do not claim tests ran on MySQL if they only ran on MariaDB. If the school rubric requires Oracle MySQL specifically, use a MySQL server and change only the connection configuration. Do not install, upgrade, or replace software as part of this plan without a separate need.

Retain compatibility with the observed PHP runtime: avoid enums, readonly properties, and other syntax newer than PHP 8.0. No Composer, npm, frontend framework, real gateway, or courier integration is needed.

### Editable implementation defaults

These are implementation choices, not additional user-approved business requirements:
- Shop name: Petal to the Metal.
- Currency: PHP / Philippine peso, formatted with the peso symbol.
- Timezone: Asia/Singapore, matching the user's client timezone.
- Delivery fee: 0.00 for the demo; pickup fee: 0.00. Show the configured fee explicitly at checkout.
- Same-day or future dates accepted; no delivery capacity or time-slot system.
- Only admins cancel orders; cancellation allowed while Pending or Preparing.
- Products are archived rather than hard-deleted; categories in use cannot be deleted.
- Seed twelve sample arrangements across three categories; use local flower images with documented sources.
- Simulated payment collects no real card details and provides a clearly labeled Complete demo payment action.

Put shop name, currency, timezone, and delivery fee in config/app.php so they are easy to change. Database credentials belong in ignored config/local.php, never public assets or committed files.

## Planned structure

~~~text
config/
  app.php
  local.example.php
  local.php                  # ignored, real local credentials
app/
  bootstrap.php
  database.php
  helpers.php
  security.php
  auth.php
  catalog.php
  cart.php
  money.php
  checkout_validation.php
  order_service.php
  order_policy.php
  order_queries.php
  admin_catalog.php
  media.php
templates/
  header.php
  footer.php
  flash.php
  product_card.php
  admin_header.php
  admin_footer.php
public/
  index.php
  shop.php
  product.php
  register.php
  login.php
  logout.php
  cart.php
  checkout.php
  payment.php
  confirmation.php
  orders.php
  order.php
  actions/
    register.php
    login.php
    cart.php
    checkout.php
  admin/
    index.php
    categories.php
    category-edit.php
    products.php
    product-edit.php
    orders.php
    order.php
    actions/
      category-save.php
      category-delete.php
      product-save.php
      product-archive.php
      order-status.php
  assets/
    css/styles.css
    css/admin.css
    js/main.js
    js/catalog.js
    js/cart.js
    js/checkout.js
    images/
database/
  schema.sql
  seed.sql
scripts/
  create-admin.php
  check-environment.php
  lint.php
tests/
  run.php
  support/assertions.php
  support/database.php
  support/fixtures.php
  unit/money.php
  unit/checkout_validation.php
  unit/order_policy.php
  integration/auth.php
  integration/order_service.php
  integration/cancellation.php
  integration/admin_catalog.php
  integration/concurrency.php
docs/
  setup.md
  verification.md
  image-sources.md
  plans/
README.md
.gitignore
~~~

## Commands and test conventions

Use PowerShell in the flower shop project directory. PHP invocation is:
~~~powershell
& 'C:\xampp\php\php.exe' scripts/check-environment.php
& 'C:\xampp\php\php.exe' scripts/lint.php
& 'C:\xampp\php\php.exe' tests/run.php --suite=unit
& 'C:\xampp\php\php.exe' tests/run.php --suite=integration --group=orders
~~~

The dependency-free test runner loads each selected test file returning a map of test name to callable, reports PASS or FAIL for each, and exits nonzero on failure. Zero discovered tests is an error, not success. Its final summary prints passed/failed counts. Assertions throw RuntimeException with useful expected/actual messages.

Integration tests require an explicitly configured dedicated database named petal_to_the_metal_test. Refuse to run if the configured database name does not end in _test; never use the application database or execute DROP DATABASE. Create/import the schema once and use uniquely named fixture rows per test. Tests whose service commits its own transaction need targeted fixture cleanup in finally blocks; do not pretend an outer transaction can roll back an inner committed checkout. Foreign-key cleanup order is payments, order_items, orders, products, categories, users. Do not put actual passwords in command-line arguments or logs.

For domain behavior, first write a focused test, run it and observe the expected missing function or wrong behavior, implement it, and rerun. For static templates, CSS, images, and documentation, use direct visual/manual review rather than tests that mirror markup. Commit each coherent task after its checks pass; include only the listed project files.

## Task 1: Establish local setup and configuration

**Create:** .gitignore, config/app.php, config/local.example.php, app/bootstrap.php, app/database.php, scripts/check-environment.php, docs/setup.md, README.md.

1. Record XAMPP startup steps and verify the running server with SELECT VERSION(); do not assume the client version is the server version.
2. Add config/local.php and transient logs to .gitignore. Supply local.example.php with editable host, port, database name, username, password, and a separate test connection. Do not assume a blank root password is the user's credential.
3. Implement the configuration and connection contract below. Runtime code must not require a database connection just to format money or validate a date.
4. Implement check-environment.php to report PHP version and PDO mysql availability, and check connectivity only when local.php exists. Report missing configuration cleanly without exposing credentials.
5. Run the environment check. Expected: observed PHP/PDO checks pass; database status reflects the actual service/configuration. Update setup instructions to match evidence.
6. Commit these paths with message chore: add PHP project setup and configuration.

Connection code in app/database.php:
~~~php
<?php
declare(strict_types=1);

function connect_database(array $settings): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $settings['host'],
        $settings['port'],
        $settings['database']
    );
    return new PDO($dsn, $settings['username'], $settings['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
~~~

Bootstrap loads configuration, sets timezone, starts the session with HttpOnly and SameSite=Lax cookies, enables strict session mode, and uses Secure cookies only under HTTPS. Private services and configuration are outside public/. Use a lazy database accessor. Show friendly application errors and log technical errors outside public/.

## Task 2: Create the six-table schema and demo seed

**Create:** database/schema.sql, database/seed.sql, scripts/create-admin.php.
**Modify:** docs/setup.md.

1. Define all tables below using InnoDB, utf8mb4, primary keys, foreign keys, and useful indexes. Use utf8mb4_unicode_ci for MySQL/MariaDB compatibility.
2. Import into a new application database and a separate test database. schema.sql contains table creation, not a hardcoded USE or destructive reset.
3. Insert twelve demo products and three categories. Use prices in DECIMAL(10,2), nonnegative integer stock, safe local image references, and no pre-created plaintext admin password.
4. Implement CLI-only create-admin.php accepting an email and name, and reading the password through a prompt or stdin rather than a command-line password argument. Hash it with password_hash(PASSWORD_DEFAULT). Reject duplicate email and invalid inputs.
5. Inspect SHOW TABLES, key definitions, seed counts, and admin password hash. Expected: six application tables, twelve products, three categories, and one deliberately created admin.
6. Commit with message feat: add shop schema and seed data.

Schema contract:
- users: id, name, normalized unique email, password_hash VARCHAR(255), role limited to customer/admin, created_at.
- categories: id, unique name, created_at.
- products: id, category_id, name, description, image_path, price DECIMAL(10,2), stock_quantity integer, is_active, created_at, updated_at.
- orders: id, user_id, unique order_reference, unique checkout_key CHAR(64), fulfillment_method delivery/pickup, scheduled_date DATE, recipient_name/phone/address nullable for pickup, gift_message, subtotal DECIMAL(10,2), fulfillment_fee DECIMAL(10,2), total DECIMAL(10,2), status, created_at, updated_at.
- order_items: id, order_id, product_id, product_name snapshot, unit_price DECIMAL(10,2), quantity integer.
- payments: id, unique order_id, unique demo_reference, status simulated_paid/simulated_refunded, amount DECIMAL(10,2), created_at, updated_at.

Do not cascade-delete users or products that have orders. Account deletion is outside scope. Index orders(user_id, created_at), orders(status, created_at), products(category_id, is_active). SQL checks supplement application validation, not replace it. Prevent categories used by products from deletion.

## Task 3: Add essential helpers and the test runner

**Create:** app/helpers.php, app/security.php, tests/run.php, tests/support/assertions.php, tests/support/database.php, tests/support/fixtures.php, scripts/lint.php.

1. Build the runner conventions described above and integration database guard.
2. Implement escaped output, flash messages, application-relative URLs, redirects, and require_post().
3. Generate session CSRF tokens with random_bytes; validate with hash_equals on every state-changing request, including login/logout and admin operations. Validation failure returns 403 without mutation; wrong HTTP method returns 405.
4. Implement lint.php to traverse only app, config, public, templates, scripts, and tests, run PHP syntax checks, and return nonzero for any failure. Exclude worktrees and generated files.
5. Run lint. Expected: no syntax errors. Manually verify escaping of a sample string with quotes and angle brackets; do not add a unit test solely mirroring htmlspecialchars.
6. Commit with message chore: add shared helpers and test harness.

Core escaping implementation:
~~~php
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
~~~

All redirects use fixed application routes or an allowlist. Never accept an arbitrary URL from a request. Field validation errors preserve safe old input but never preserve passwords.

## Task 4: Build the visual shell and local image assets

**Create:** templates/header.php, templates/footer.php, templates/flash.php, templates/product_card.php, public/assets/css/styles.css, public/assets/js/main.js, public/assets/images/, docs/image-sources.md, public/index.php (shell).
**Modify:** docs/setup.md.

1. Establish CSS variables: cream #FFF9F3, blush #E8C2C5, sage #72806A, deep green #283A30, and warm dark text #302B28. Confirm text contrast and keep muted colors on decorative surfaces where needed.
2. Create shared navigation, a labeled mobile menu, cart indicator, customer/admin links, footer, and reusable product cards.
3. Design a flower-focused homepage with an editorial hero, generous photos, category links, and a featured-arrangement grid. Use a restrained serif heading stack and a readable system sans-serif body stack.
4. Provide local flower images; record source/license or generated-asset provenance. Avoid remote hotlinks and do not present placeholder art as actual product photographs.
5. Preview at 375px, 768px, and 1440px. Check keyboard focus, menu behavior, readable prices, touch targets, and no horizontal overflow. Static pages must remain usable without JavaScript.
6. Commit with message feat: add flower shop visual shell.

Use @frontend-design for implementation. Labels, errors, and payment simulation disclosures must be visible text; meaning must not depend only on color. Admin styles should share the palette but prioritize readable tables and forms.

## Task 5: Implement registration, login, logout, and role checks

**Create:** app/auth.php, public/register.php, public/login.php, public/logout.php, public/actions/register.php, public/actions/login.php, tests/integration/auth.php.
**Modify:** templates/header.php.

1. Write integration tests for normalized duplicate emails, invalid credentials, successful password verification, and caller-supplied admin role rejection.
2. Run tests/run.php --suite=integration --group=auth. Expected initially: failure because auth service is missing; record the failure before implementation.
3. Implement register_customer(PDO, array), authenticate_user(PDO, string, string), current_user(), require_customer(), and require_admin(). Public registration always assigns customer, ignoring submitted role.
4. Regenerate the session ID after login, store the authenticated user ID, and resolve the current role from the database rather than browser fields. Logout is POST with CSRF and clears session/cookie state.
5. Build labeled forms with inline errors. Unauthenticated protected-page visits redirect to login using allowlisted return routes; forbidden roles return 403.
6. Rerun auth tests and manually check protected customer/admin URLs, logout, missing CSRF, and cross-session behavior. Commit with message feat: add customer authentication and admin access.

Use password_hash and password_verify, not custom password encryption. Require a valid email and a sensible password minimum such as eight characters; report duplicate email with a friendly message. Login errors do not reveal whether a specific account exists.

## Task 6: Implement catalog queries and storefront pages

**Create:** app/catalog.php, public/shop.php, public/product.php, public/assets/js/catalog.js.
**Modify:** public/index.php, templates/product_card.php.

1. Implement prepared queries for active products, category filtering, text search, and allowed sorting choices. Sorting maps an allowlisted key to a fixed SQL fragment; never interpolate a user-supplied column.
2. Populate homepage featured arrangements from MySQL and catalog cards from the active-product query.
3. Build product details with image, category, description, price, stock availability, and quantity selection.
4. Handle malformed/missing IDs as 404; show an empty catalog state with a clear route to remove filters.
5. Verify searching, categories, each sort option, archived-product exclusion, out-of-stock display, and escaped descriptions. Verify filters with JavaScript disabled.
6. Commit with message feat: add flower catalog and product details.

Guests can browse product details; Add to cart redirects guests to login. A product being visible or marked in stock on screen is not proof it remains available at checkout.

## Task 7: Implement money rules and authenticated session carts

**Create:** app/money.php, app/cart.php, public/cart.php, public/actions/cart.php, public/assets/js/cart.js, tests/unit/money.php.
**Modify:** public/product.php, templates/header.php.

1. Write tests for exact decimal-to-minor-unit conversion and totals: 0.10 + 0.20 is exactly 0.30; multiple items and the configured fee are included; malformed or negative prices are rejected.
2. Run tests/run.php --suite=unit --group=money and observe failure before adding money functions.
3. Store only product IDs and positive integer quantities in the session cart. Query current names, prices, stock, and active state for display. Scope/clear cart on account changes and logout so different customers do not inherit it.
4. Handle add/update/remove via POST with CSRF and require_customer(). Reject malformed, zero, negative, excessive, unavailable, or inactive quantities. JavaScript enhancements must call the same protected action and preserve a working form fallback.
5. Implement cart totals using integer minor units, format database decimal strings without floating-point arithmetic, and make removal explicit rather than using zero quantity.
6. Rerun money tests; manually check cart/login interactions, quantity errors, and empty state. Commit with message feat: add authenticated cart and exact totals.

Money conversion contract:
~~~php
function money_to_minor(string $amount): int
{
    if (!preg_match('/\A(0|[1-9][0-9]{0,7})(?:\.([0-9]{1,2}))?\z/', $amount, $match)) {
        throw new InvalidArgumentException('Invalid monetary amount.');
    }
    return ((int) $match[1] * 100)
        + (int) str_pad($match[2] ?? '', 2, '0');
}

function minor_to_money(int $minor): string
{
    if ($minor < 0) {
        throw new InvalidArgumentException('Negative monetary amount.');
    }
    return intdiv($minor, 100) . '.' . str_pad((string) ($minor % 100), 2, '0', STR_PAD_LEFT);
}
~~~

Keep persisted totals within the chosen DECIMAL precision; reject rather than overflow.

## Task 8: Validate checkout details and show demo payment

**Create:** app/checkout_validation.php, public/checkout.php, public/payment.php, public/assets/js/checkout.js, tests/unit/checkout_validation.php.
**Modify:** app/cart.php.

1. Write tests for delivery's required recipient/address/phone, pickup with no address, unknown method, invalid date strings, past dates, empty cart, invalid quantities, and overlong fields.
2. Use an injectable DateTimeImmutable today argument so date tests are deterministic. Run tests/run.php --suite=unit --group=checkout and observe failure.
3. Implement validate_checkout(array $input, DateTimeImmutable $today): array returning normalized fulfillment data or throwing an exception with field errors. Date parsing must round-trip exactly to Y-m-d and reject parser warnings. Use the configured timezone.
4. Build checkout fields, optional gift message, configured fee display, and a review step. JavaScript toggles delivery fields; PHP still enforces all conditional rules.
5. Store validated pending details and a random 64-character checkout key in the customer session, then show a clearly labeled demo payment screen. Bind the key to this customer, cart, fulfillment details, and server-calculated current prices. Cart or detail changes invalidate it.
6. Rerun tests; verify delivery/pickup behavior and simulation labels. Commit with message feat: add checkout validation and simulated payment screen.

Complete demo payment submits POST with CSRF. Do not request card number, CVV, banking credentials, or a real payment provider account. A payment-page view alone creates no order and reduces no stock.

## Task 9: Implement atomic, idempotent order placement

**Create:** app/order_service.php, public/actions/checkout.php, tests/integration/order_service.php.
**Modify:** public/payment.php, tests/support/fixtures.php.

1. Write integration tests for successful delivery/pickup orders, trusted database pricing, snapshot names/prices, insufficient stock, inactive products, rollback after a persistence error, cross-customer key reuse, and repeated submission returning the same order.
2. Run tests/run.php --suite=integration --group=orders and observe failure before implementing the service.
3. Implement place_order(PDO $db, int $customerId, array $cart, array $details, string $checkoutKey, array $appConfig): int following the transaction algorithm below.
4. Implement the action: reject missing/expired pending checkout, authenticate, validate CSRF/details, call the service, clear cart only on success, and redirect to confirmation. For an existing committed key owned by this user, allow a safe retry result even when the first request already cleared session cart.
5. Rerun integration tests, including rollback and duplicate submission. Expected: one order, one payment, exact item snapshots, and one stock deduction per checkout key.
6. Commit with message feat: place demo orders with transactional stock updates.

Transaction algorithm:
1. Find an existing order for the key; if owned by this customer return its ID, otherwise reject.
2. Begin a transaction; lock the customer row and recheck the key to serialize repeated submissions by the same user.
3. Lock product rows in ascending product-ID order using SELECT ... FOR UPDATE. Validate existence, active status, quantity, stock, and current price. All stock-affecting services follow the same product lock order.
4. Recompute subtotal/fee/total from locked database prices and server configuration. If price changed since the payment review, return a review-required error before committing an order.
5. Insert Pending order, item snapshots, and simulated_paid payment. Use unique keys for checkout_key, order_reference, order_id on payments, and demo_reference.
6. Deduct quantities and commit. Roll back on any Throwable. The transaction does not contain schema changes.
7. Clear pending session state only after success. Translate expected stock/price changes into actionable customer errors; log unexpected database exceptions privately.

References for implementation: [PDO transactions](https://www.php.net/manual/en/pdo.begintransaction.php), [prepared queries](https://www.php.net/manual/en/pdo.prepare.php), and [InnoDB locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html). Use transactions for these changes and lock the stock rows while deciding availability.

For a rollback test, introduce a deterministic constraint failure in the dedicated fixture database and assert that order/items/payment and stock are unchanged. Restore any test fixture change in finally. Do not add a production endpoint that forces failures.

## Task 10: Implement confirmation and customer order history

**Create:** app/order_queries.php, public/confirmation.php, public/orders.php, public/order.php.
**Modify:** templates/header.php, public/assets/css/styles.css.

1. Implement customer-specific queries that include user_id in the WHERE condition; never fetch a customer's order solely by its public ID.
2. Build confirmation with order reference, item snapshots, simulated payment receipt, fulfillment details/date, totals, and order status.
3. Build newest-first order history and order details with delivery/pickup-specific labels and an empty-history state.
4. Verify with two customers: each sees only their own data. Changing the URL order ID must not expose the other customer's details.
5. Update a product's current name/price and confirm the historical receipt stays unchanged.
6. Commit with message feat: add order confirmation and customer history.

Use the same ownership checks on confirmation and detail pages. The customer-facing receipt always calls the payment simulated and never claims a real charge or real refund.

## Task 11: Build admin dashboard and product/category management

**Create:** app/admin_catalog.php, app/media.php, public/admin/index.php, public/admin/categories.php, public/admin/category-edit.php, public/admin/products.php, public/admin/product-edit.php, public/admin/actions/category-save.php, public/admin/actions/category-delete.php, public/admin/actions/product-save.php, public/admin/actions/product-archive.php, templates/admin_header.php, templates/admin_footer.php, public/assets/css/admin.css, tests/integration/admin_catalog.php.

1. Write integration tests for invalid/negative price or stock, nonexistent category, category-in-use deletion, and product archiving that preserves order history.
2. Run tests/run.php --suite=integration --group=admin-catalog and observe failures.
3. Build prepared query services and admin forms for create/edit/archive products and create/edit/delete unused categories. Product images are chosen from approved local assets; arbitrary URLs, filesystem paths, and image upload are outside this first version.
4. Require admin authorization and CSRF on every mutation. Product saves/stock edits must acquire the product row lock when touching stock, respecting checkout/cancellation locking rules.
5. Build dashboard totals for products, open orders, and recorded demo sales, excluding cancelled/refunded orders and clearly labeling monetary summaries as demo figures.
6. Rerun tests and manually verify validation messages, archive behavior, direct-action authorization, and mobile table usability. Commit with message feat: add admin catalog and inventory management.

Do not implement sales charts, export tools, supplier purchasing, or other unapproved features.

## Task 12: Implement admin order progression and cancellation

**Create:** app/order_policy.php, public/admin/orders.php, public/admin/order.php, public/admin/actions/order-status.php, tests/unit/order_policy.php, tests/integration/cancellation.php.
**Modify:** app/order_service.php.

1. Write unit tests for allowed method-specific transitions, cancellation cutoff, terminal states, and invalid branch choices. Run tests/run.php --suite=unit --group=order-policy and observe failure.
2. Implement the complete transition policy below.
3. Write integration tests for cancellation restoring stock and simulated_refunded payment once; repeated cancellation; completed/ dispatched/ready orders rejecting cancellation; and failed cancellation rollback. Run tests/run.php --suite=integration --group=cancellation and observe failure.
4. Build admin order list with status filter and method labels, details page with customer/fulfillment/item data, and POST actions that permit only allowed next states.
5. Implement transactional state updates: lock the order, validate the current state under lock, lock affected products in ascending ID order for cancellation, restore quantities, update payment/order, and commit together. Repeated cancellation returns the existing cancelled result without adding stock.
6. Rerun both groups and check delivery/pickup flows end to end. Commit with message feat: manage fulfillment status and safe cancellation.

Complete policy in app/order_policy.php:
~~~php
<?php
declare(strict_types=1);

function allowed_order_transitions(string $method, string $status): array
{
    if (!in_array($method, ['delivery', 'pickup'], true)) {
        return [];
    }
    $ready = $method === 'delivery' ? 'out_for_delivery' : 'ready_for_pickup';
    $flow = [
        'pending' => ['preparing', 'cancelled'],
        'preparing' => [$ready, 'cancelled'],
        $ready => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];
    return $flow[$status] ?? [];
}
~~~

Display friendly title-case labels in templates. State progression does not change the original scheduled date. Cancellation is admin-only in this plan; customer cancellation controls would require a separate scope decision.

## Task 13: Verify access control, concurrency, and failure handling

**Create:** tests/integration/concurrency.php, docs/verification.md.
**Modify:** any files with observed defects.

1. Add a two-process integration test against the dedicated test database using independent PDO connections. Use a barrier outside database transactions so both workers start together. With one product unit left, two different customers attempt checkout; expected: exactly one order/payment, zero remaining stock, and one stock error.
2. Add concurrent repeated-key coverage. Expected: one order and one deduction, both callers receive the same order ID. Use timeouts and cleanup in finally to avoid stranded workers/fixtures.
3. Run all unit and integration suites, not SQLite substitutes; collect actual test counts, outputs, database server identity, and any limits in docs/verification.md.
4. Manually check anonymous admin/customer access, customer hitting admin actions, missing CSRF, wrong HTTP method, another customer's order ID, posted fake prices/roles/statuses, HTML-like gift text, and validation errors. State-changing rejected requests leave database and cart unchanged.
5. Verify service failures leave stock/order/payment consistent and retain a recoverable cart; a changed price requires renewed review. Review logs without exposing customer details or credentials in the report.
6. Commit with message test: verify checkout integrity and access boundaries.

Do not claim that concurrency passes merely because requests were made through PHP's single-worker development server. The workers must connect to the database independently. Use @systematic-debugging for observed failures and @verification-before-completion before claiming completion.

## Task 14: Complete responsive review and classroom setup guide

**Modify:** README.md, docs/setup.md, docs/verification.md, public/assets/css/styles.css, public/assets/css/admin.css, and affected templates only.

1. Review all customer/admin pages at phone and desktop widths; navigate using keyboard, confirm focus visibility, and inspect empty/error/disabled states.
2. Verify the complete classroom demo: register -> login -> browse -> cart -> delivery checkout -> simulated payment -> history -> admin prepare/dispatch/complete. Repeat pickup with ready-for-pickup and complete.
3. Verify a cancellable order restores stock and labels its demo payment refunded; verify a completed order cannot be cancelled.
4. Document exact database creation/import, private config, admin creation, startup, test configuration, and troubleshooting steps. Use XAMPP Control Panel to start the database; for the app, serve public/ using the command below or configure an Apache virtual host whose DocumentRoot is public/.
5. Run syntax checks and the relevant suites once more only if this task changes PHP or relevant behavior. Record which checks were actually run; label unrun checks.
6. Commit with message docs: document XAMPP setup and school demo.

Local development server:
~~~powershell
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8000 -t public
~~~

Open http://127.0.0.1:8000/. Keep the server task in the terminal; do not create an unsolicited visible helper window. Do not expose the project root as the web document root, which would expose private config, SQL, and tests.

Database import examples, executed from the project directory after creating the intended empty database:
~~~powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u YOUR_DB_USER -p petal_to_the_metal --execute="SOURCE database/schema.sql"
& 'C:\xampp\mysql\bin\mysql.exe' -u YOUR_DB_USER -p petal_to_the_metal --execute="SOURCE database/seed.sql"
& 'C:\xampp\mysql\bin\mysql.exe' -u YOUR_DB_USER -p petal_to_the_metal_test --execute="SOURCE database/schema.sql"
~~~

Replace YOUR_DB_USER and configure credentials locally; do not publish them. Do not seed demo orders or real customer data into the application database as a side effect of running tests.

## Completion criteria

- Every approved storefront/admin workflow is implemented with HTML/CSS/vanilla JavaScript and PHP.
- The six-table database imports and seeded arrangements render from actual database queries.
- Required accounts, role checks, CSRF, escaping, password hashing, and order ownership work.
- Both fulfillment methods and simulated payment are clearly represented.
- Totals use server prices; checkout is atomic and idempotent; stock cannot be oversold.
- Cancellation restores stock once and observes the configured cutoff.
- Customer history retains purchase-time prices and names.
- Meaningful tests and the manual demo checks have actual recorded results.
- Local flower assets and responsive pages are reviewed.
- The README lets a classmate start the demo through XAMPP without guessing credentials or web-root configuration.
- Claims distinguish a MySQL run from the installed XAMPP MariaDB run.
- Only project-scoped changes are committed; other projects are untouched.

## Execution handoff

This document is a plan, not an implemented application. Use @executing-plans for inline task-by-task execution, or @subagent-driven-development if the user chooses delegated work. If the user chooses a separate session, point it to this plan and the approved design. Do not start execution merely because a plan was requested.
