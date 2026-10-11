# Verification record

## Customer UX improvements — 2026-10-08

The user selected a named account menu with My orders and Log out inside. The updated header uses a native disclosure with keyboard dismissal and a separate CSRF-protected logout action. Forms add linked errors, focus recovery, password visibility, submitting feedback, and checkout progress. Bag quantity changes save through the existing endpoint, serialize requests, refresh server-rendered totals, and wait before checkout. Failed drafts remain available for retry or removal; logout remains available. Selected arrangements survive login/registration and validation failures.

UI/UX Pro Max's written guidelines informed readable type, touch targets, focus, contrast, and mobile layouts. Its installed search helpers were missing; no CLI design-system search is claimed. The original cream, blush, sage, Caslon, and DM Sans direction is retained.

| Check | Actual result |
| --- | --- |
| PHP syntax | 73 files pass in app/public/templates/config/tests |
| Unit and integration tests | 26 passed, 0 failed |
| Focused Chromium UX checks | 14 passed |
| Complete Chromium workflow QA | 20 checks passed |
| Responsive sweep | 64 page/viewport combinations at 375, 768, 1024, 1440 pixels |
| QA cleanup | Exact temporary customers/orders removed; stock restored |
| Visual review | Desktop and phone account menus, payment and admin screenshots |

Commands: `C:/xampp/php/php.exe tests/run.php`, PHP `-l` over the listed directories, `node tests/e2e/ux-qa.cjs`, and `node tests/e2e/browser-qa.cjs`. Browser tests use `PETAL_QA_URL` to target only localhost. Screenshots are local ignored artifacts under `storage/logs/screenshots`.

Observed failures before fixes included the absent account menu, missing quantity autosave, rapid multi-row edits lost through page navigation, and removal trapped after failed saves. Regression coverage now checks those interactions, immediate checkout, failed-save retry/removal/logout, no-JavaScript logout, safe product returns, linked registration/checkout errors, and password visibility. Code review identified cart race/recovery issues and nonfield error links; they were corrected and covered where appropriate.

Limits remain Chromium/MariaDB and representative visual review, rather than a full accessibility audit or physical-device/Safari/Firefox testing.

Merged into local `master`. PHP tests/lint and both browser suites were rerun successfully against the main project and its `http://127.0.0.1:8000` preview.

Verified on 2026-10-08 (Asia/Singapore) in the isolated `feature/flower-shop-setup` checkout.

## Environment and results

| Check | Actual result |
| --- | --- |
| PHP | 8.0.30; PDO MySQL available |
| Application and test connections | Both pass; server reports 10.4.32-MariaDB |
| PHP syntax | 71 files pass |
| Unit and integration tests | 25 passed, 0 failed |
| Exception logging regression | Pass; function arguments omitted |
| Chromium browser QA | 20 workflow checks passed |
| Responsive coverage | 57 page/viewport combinations at 375, 768, and 1440 pixels |
| Browser QA cleanup | Pass; exact temporary customers/orders removed and stock restored |

Commands run from the active project directory:

```powershell
& 'C:\xampp\php\php.exe' scripts/check-environment.php
& 'C:\xampp\php\php.exe' scripts/lint.php
& 'C:\xampp\php\php.exe' tests/run.php
& 'C:\xampp\php\php.exe' tests/bootstrap-error-logging.php
node tests/e2e/browser-qa.cjs
```

The integration suite uses the configured `petal_to_the_metal_test` database. It does not substitute SQLite or point at the application database. The concurrency checks launch two independent PHP processes, each with its own PDO connection and a start barrier outside its transaction. Browser requests through the single-worker development server are not used as evidence of database concurrency.

## Database inspection

The application has exactly six tables: users, categories, products, orders, order_items, and payments. All use InnoDB and utf8mb4_unicode_ci. Inspection found the expected unique checkout/order/payment keys, customer/status/category indexes, and five foreign key relationships. The demo catalog contains twelve products across three categories. One administrator exists with a recognized password hash; browser login with its private local credentials succeeds.

After browser QA cleanup, the application contains no test orders or payments. Existing schemas and seed data were inspected rather than reset or reimported during this continuation.

## Behavioral coverage

- Normalized unique emails, password verification, invalid input, and ignored caller-supplied admin roles.
- Exact minor-unit totals, malformed/negative price rejection, checkout dates, conditional delivery fields, quantities, and field lengths.
- Locked database pricing, historical names/prices, changed-price rejection, inactive products, insufficient stock, cross-customer key rejection, and repeat-submission idempotency.
- Persistence failure rollback: no retained order/item/payment or deducted stock after a forced test-database payment failure.
- Last-unit concurrency: one order and payment, zero remaining stock, and one stock validation failure.
- Concurrent same-key checkout: both callers receive the same order and stock is deducted once.
- Method-specific fulfillment, cancellation cutoffs, one-time stock restoration, simulated refund, and cancellation rollback.
- Admin price/stock/category/image validation, category-in-use deletion rejection, product saving, and archiving.
- Large-total regression: an oversized bag remains readable/removable; checkout rejects totals beyond DECIMAL(10,2), including fulfillment fees, before writing an order or deducting stock.

## Browser and visual review

Chromium exercised customer registration/login, protected-page redirects, customer denial from admin pages/actions, missing CSRF, wrong methods, and inaccessible private configuration. It completed delivery and pickup checkout, verified the simulation labels, escaped script-like gift text, confirmed repeat payment returns the original receipt, and checked cross-customer order/confirmation ownership.

Administrator login, dashboard, cancellation, delivery prepare/dispatch/complete, pickup prepare/ready/complete, and completed-order cancellation rejection passed. Catalog search/category filtering and sorting, the keyboard skip link, mobile menu/Escape behavior, CSRF-protected logout, and absence of customer JavaScript errors also passed. Pickup checkout was completed with JavaScript disabled.

The responsive sweep covered sixteen public/customer/admin pages at three widths, plus populated cart, checkout, and payment at all three widths. Images were decoded before checking them so lazy images were not mistaken for broken files. Tables scroll within their containers. Desktop/mobile homepage screenshots were visually inspected; phone payment and admin order screenshots were also inspected. Screenshots are local ignored artifacts under `storage/logs/screenshots/`.

## Review findings addressed

An independent code review identified an oversized cart total that could throw before rendering quantity/removal controls. New regression tests failed first, then passed after separating display formatting from the stored-order limit and validating the total before persistence. The full suite then passed 25/25.

Responsive QA found a phone-width admin order detail overflow caused by a grid child's minimum intrinsic width. A captured failing layout check identified the overflowing panels; allowing the grid children to shrink and long references to wrap made the complete responsive sweep pass.

QA setup was corrected to request a specific post-login return route, preserve the phone viewport for navigation checks, and use reduced-motion mode for stable snapshots and no-JavaScript interaction checks.

## Limits

This verification ran on XAMPP's MariaDB, not Oracle MySQL, and Chromium, not Firefox/Safari or a physical phone. Visual review covers representative screenshots and automated overflow checks; it is not a full accessibility audit. Complete browser CRUD for every admin form was not exercised; catalog mutations were verified through the integration suite. Payment/refund is simulated, and no real payment provider or courier was tested.

The resumed worktree already contained the implementation and its tests. This record does not claim that every preexisting service's initial failing test run was witnessed during the continuation. The new large-total regression's failing and passing runs were observed. Google Fonts needs a network connection; local font fallback stacks and flower images remain available offline.
