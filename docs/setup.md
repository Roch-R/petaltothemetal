# Local setup with XAMPP

Run all commands from the `Petal to the Metal` project directory containing `app/`, `database/`, and `public/`. The verified application has been merged into `master` and is available directly in the original project folder.

## Verified environment

- PHP: `C:/xampp/php/php.exe`, version 8.0.30, with PDO MySQL and mbstring.
- Database: MariaDB 10.4.32 on port 3306, verified with `SELECT VERSION()`.
- Application database: `petal_to_the_metal`; separate test database: `petal_to_the_metal_test`.

XAMPP labels its database service MySQL, but this installation bundles MariaDB. The SQL uses InnoDB and utf8mb4 with MySQL-compatible syntax. Oracle MySQL has not been tested; if your rubric requires it, configure that server and verify it separately.

## Start the database and configure connections

Open XAMPP Control Panel and start **MySQL**. Use the running instance if port 3306 is already occupied by XAMPP. PHP and the database client are not on PATH in this workstation, so these examples use full executable paths.

If `config/local.php` is missing, copy the example:

```powershell
Copy-Item -LiteralPath 'config/local.example.php' -Destination 'config/local.php'
```

Edit the `database` and `test_database` arrays to match your actual host, port, username, and password. Keep the test database separate. Do not overwrite an existing private configuration. This workstation already has verified local settings; credentials are excluded from Git.

## Create and import databases on a fresh installation

Use phpMyAdmin or an administrative SQL session to create two new databases:

```sql
CREATE DATABASE petal_to_the_metal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE petal_to_the_metal_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Import from this project directory, replacing `YOUR_DB_USER` with your database username. `-p` prompts for the password rather than putting it in a command argument:

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u YOUR_DB_USER -p petal_to_the_metal --execute="SOURCE database/schema.sql"
& 'C:\xampp\mysql\bin\mysql.exe' -u YOUR_DB_USER -p petal_to_the_metal --execute="SOURCE database/seed.sql"
& 'C:\xampp\mysql\bin\mysql.exe' -u YOUR_DB_USER -p petal_to_the_metal_test --execute="SOURCE database/schema.sql"
```

The schema creates six tables. Seed data adds three categories and twelve fictional arrangements, with no predefined users or passwords. Import each file once into the intended fresh database. Existing databases on this workstation already contain the schema and demo catalog; do not reimport them or replace data.

## Create an administrator

```powershell
& 'C:\xampp\php\php.exe' scripts/create-admin.php --email=admin@example.test --name=Administrator
```

Enter a password of 8–72 characters at the prompt. The CLI stores a password hash and rejects invalid inputs and duplicate email addresses. The console may show what you type; the password is not passed as a command argument. Customer registration always creates a customer account.

One administrator is already configured on this workstation. Its local demo login details are in the ignored `storage/demo-credentials.txt` in the active checkout. That file is not distributed with the project; use the creation script on a new installation.

## Verify and start the website

```powershell
& 'C:\xampp\php\php.exe' scripts/check-environment.php
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8000 -t public
```

Open **http://127.0.0.1:8000/**. Keep the server terminal running; Ctrl+C stops it. An Apache virtual host can also serve the app if its `DocumentRoot` points to `public/`. Do not serve the project root: private configuration, SQL, scripts, and tests belong outside the web root. For a subdirectory deployment, set `base_path` in `config/app.php` to its URL prefix.

The default currency is Philippine pesos, timezone is Asia/Singapore, and delivery/pickup fees are both 0.00. Change these defaults in `config/app.php`. Checkout always displays the selected fee. Local flower photos are documented in [image-sources.md](image-sources.md); fonts use Google Fonts with local fallback stacks.

## Classroom demo

1. Register a customer, log in, browse or search the catalog, and add flowers to the bag.
2. Select delivery, a current/future date, recipient details, and an optional gift message.
3. Review the total and choose **Complete demo payment**. No money is charged and no card details are collected.
4. View the confirmation and **My orders**.
5. Log in as the administrator and move the order through Preparing, Out for delivery, and Completed.
6. Repeat with pickup: recipient address is unnecessary, and Preparing leads to Ready for pickup, then Completed.
7. Cancel a separate Pending or Preparing order to demonstrate one stock restoration and a simulated refund. Dispatched, ready, and completed orders cannot be cancelled.

Photos illustrate fictional arrangements and may be reused across products. Monetary dashboard figures describe demo payments.

## Tests

```powershell
& 'C:\xampp\php\php.exe' scripts/lint.php
& 'C:\xampp\php\php.exe' tests/run.php
& 'C:\xampp\php\php.exe' tests/bootstrap-error-logging.php
```

Use `--suite=unit` or `--suite=integration` to select a suite; groups include `auth`, `money`, `checkout`, `orders`, `cancellation`, `admin-catalog`, `order-policy`, and `concurrency`. No matching tests is a failing result. Integration fixtures and temporary failure triggers run only in the dedicated test database; targeted cleanup removes their own records.

Optional browser QA requires a resolvable `playwright` package and Chromium. With the local website running and a local demo admin credential file available:

```powershell
node tests/e2e/browser-qa.cjs
```

This QA targets only a localhost demo, uses temporary `qa-…@example.test` customer accounts, and cleans up those exact accounts and orders, restoring stock. Screenshots are saved under ignored `storage/logs/screenshots/`. The PHP suites do not require Node.js or Playwright. See [verification.md](verification.md) for actual results.

## Troubleshooting

- **Connection check fails:** start XAMPP MySQL and check local credentials, database names, host, and port. The environment script reports failure without printing credentials.
- **Table missing:** import the schema once into the selected empty database. Import the seed into the application database only.
- **Empty catalog:** check that seed data was imported and products are active.
- **Site or assets return 404:** run the server from the active project directory with `-t public`; check `base_path` if using an Apache subdirectory.
- **Port 8000 is occupied:** reuse the intended running preview or choose another local port. For browser QA, set `PETAL_QA_URL` to the corresponding `http://127.0.0.1:PORT`.
- **Expired form or review:** reload the form. A payment review expires after 15 minutes; cart changes or price changes require a new review.
- **Unavailable stock or an oversized total:** reduce quantities or remove an arrangement. No order or stock deduction is saved on a rejected checkout.
- **Unexpected application error:** inspect `storage/logs/php.log` locally. The browser shows a friendly error; exception arguments are excluded from diagnostics.
