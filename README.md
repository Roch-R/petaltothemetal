# Petal to the Metal

A flower shop school project using HTML, CSS, vanilla JavaScript, PHP, and MySQL-compatible SQL with XAMPP.

The approved scope includes customer accounts, a flower catalog, a cart, simulated payment, delivery/pickup, order history, and an admin panel. Payment is a classroom simulation and does not charge real money.

## Features

- Twelve sample arrangements, category filters, search, and sorting.
- Customer registration, login, a session cart, delivery or pickup, and gift messages.
- Reviewed checkout with simulated payment, order confirmation, and customer history.
- Admin catalog and inventory management, fulfillment status, and safe cancellation.
- Transactional stock updates, repeat-submission protection, CSRF checks, and order ownership.

## Setup

With XAMPP installed at `C:\xampp`, double-click **run.bat** to start the local
database and website and open the shop. Double-click **phpmyadmin.bat** to manage
the same database. The launcher creates missing databases and imports sample
flowers automatically; existing data is preserved. Deleting the database and
running the launcher again recreates the demo catalog, but cannot recover deleted
accounts or orders. See HOW_TO_RUN.md for details and alternate XAMPP locations.

Start with [How to run: requirements, dependencies, and setup](HOW_TO_RUN.md). The [detailed XAMPP setup guide](docs/setup.md) is also available. From this project folder, verify the environment with:

```powershell
& 'C:\xampp\php\php.exe' scripts/check-environment.php
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8000 -t public
```

Start XAMPP's MySQL service first, then open **http://127.0.0.1:8000/**. Serve `public/` as the web root. The setup guide covers importing the schema and seed, private configuration, and creating an administrator.

## Verification

```powershell
& 'C:\xampp\php\php.exe' scripts/lint.php
& 'C:\xampp\php\php.exe' tests/run.php
& 'C:\xampp\php\php.exe' tests/bootstrap-error-logging.php
```

Integration tests require a separate configured database ending in `_test`. [Verification results](docs/verification.md) record the database identity, test coverage, browser checks, and limitations. Browser automation uses an optional external Playwright installation; the application needs no Node.js or Composer dependencies.

Shop name, currency, timezone, and fulfillment fees are editable in config/app.php. Database credentials belong in config/local.php, which is excluded from Git.

## Planning

- [Approved design](docs/plans/2026-10-08-flower-shop-design.md)
- [Implementation plan](docs/plans/2026-10-08-flower-shop-implementation-plan.md)
