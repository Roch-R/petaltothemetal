# How to run Petal to the Metal

This guide covers running the project locally on Windows with XAMPP. The website uses HTML, CSS, and vanilla JavaScript for the frontend, PHP for the backend, and a MySQL-compatible database.

## Requirements and dependencies

| Requirement | Purpose |
| --- | --- |
| PHP 8.0 or newer | Runs the PHP pages and backend. This project was verified with PHP 8.0.30. |
| PHP extensions: PDO, pdo_mysql, mbstring | Database access and text validation. |
| MariaDB / MySQL-compatible database | Stores accounts, flowers, inventory, and orders. Verified with MariaDB 10.4.32 bundled with XAMPP; Oracle MySQL has not been tested. |
| Web browser | Opens the website. Allow cookies for login and the shopping bag. |
| Writable PHP session directory and `storage/logs/` | Stores session data and application logs. |

XAMPP supplies PHP, the database server, Apache, and phpMyAdmin. These instructions assume it is installed at `C:\xampp`; adjust the executable paths if yours is elsewhere.

**You do not need Node.js, npm, Composer, a frontend build command, or VS Code Live Server to run the website.** PHP's built-in server handles the site in this guide. Apache is optional when using that server; the database service must still be running.

Internet access is used for Google Fonts. If unavailable, the site uses fallback fonts; flower images are stored locally.

## Quick start on this computer

Double-click **run.bat** to start the database and website and open your browser.
Double-click **phpmyadmin.bat** to open phpMyAdmin connected to this project's database.
No need to manually import SQL.

The launcher creates missing databases and imports the schema into empty databases.
It adds sample flowers only when initializing the application database; existing
data is kept. If you delete the whole shop database in phpMyAdmin, close that tab
and run **run.bat** again. This recreates the sample catalog, but deleted accounts,
orders, and custom products are lost unless you restore a backup. If only some
tables are missing, the launcher stops and explains the problem.

On this computer the launcher uses a separate local MariaDB on **port 3307**
because XAMPP's original database was crashing. Its files live in
`storage/logs/local-mariadb/`; keep that folder to preserve your shop data.
The phpMyAdmin shortcut serves a private copy at **http://127.0.0.1:8001/**,
configured for the same server as `config/local.php`. XAMPP's normal
`http://localhost/phpmyadmin/` may show a different server on port 3306.
PHP serves the shop at **http://127.0.0.1:8000/**. Both servers run in the
background. Repeated launches reuse running servers.

Install XAMPP at `C:\xampp`, or set the `PETAL_XAMPP_HOME` environment variable
to your XAMPP folder. The launcher preserves an existing `config/local.php`;
without one it creates local settings for port 3307. Startup diagnostics are
saved under `storage/logs/`. You can also run `run.bat phpmyadmin`.

### Manual startup (optional)

The current project already has its database configuration, sample catalog, and an administrator set up. Use these steps for normal daily use:

1. Open **XAMPP Control Panel** and start **MySQL**. XAMPP uses this label for its bundled MariaDB database service. If the intended database instance is already running, use it.
2. Open a PowerShell terminal in the project folder:

   ```powershell
   Set-Location 'C:\Users\Jayrald\Desktop\Projects\Petal to the Metal'
   ```

3. Start the PHP web server:

   ```powershell
   & 'C:\xampp\php\php.exe' -S 127.0.0.1:8000 -t public
   ```

4. Open **http://127.0.0.1:8000/** in your browser.

Keep that terminal open while using the website. Press **Ctrl+C** in it to stop the web server. If the project's preview is already running at that address, open it instead of starting a second server. To refresh changed styles or scripts, press **Ctrl+F5** in the browser.

Use **Create an account** to register a customer. This computer's existing demo administrator details are in the private `storage/demo-credentials.txt` file. That file is excluded from Git and will not be present on a fresh copy.

## First-time setup on another computer

### 1. Install XAMPP and open the project

Install XAMPP with PHP and its database server. Copy or clone this project, then open a terminal in the folder containing `app/`, `config/`, `database/`, and `public/`.

The project can stay outside XAMPP's `htdocs` folder when using the PHP server command above.

### 2. Configure the database connection

Start **MySQL** in XAMPP Control Panel. If `config/local.php` does not exist, create it from the example:

```powershell
Copy-Item -LiteralPath 'config/local.example.php' -Destination 'config/local.php'
```

Open `config/local.php` in your editor. Replace `YOUR_DB_USER` and `YOUR_DB_PASSWORD` with your actual database credentials in both connection arrays.

| Setting | Local example |
| --- | --- |
| `host` | `127.0.0.1` |
| `port` | `3306` |
| `database.database` | `petal_to_the_metal` |
| `test_database.database` | `petal_to_the_metal_test` |
| `username` / `password` | Your database server's credentials |

Keep an existing `config/local.php` if it is already configured. The application uses the `database` connection. The separate `test_database` connection is used for integration tests and the environment checker, rather than normal website use.

### 3. Create the databases and import the SQL

Run these statements in phpMyAdmin's SQL tab or your database client on a fresh installation:

```sql
CREATE DATABASE petal_to_the_metal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE DATABASE petal_to_the_metal_test
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

The second database lets the environment check and full tests run; it is optional if you only need the website.

From the project folder, import the schema and sample flowers. Replace `YOUR_DB_USER`; `-p` asks for your database password:

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u YOUR_DB_USER -p petal_to_the_metal --execute="SOURCE database/schema.sql"
& 'C:\xampp\mysql\bin\mysql.exe' -u YOUR_DB_USER -p petal_to_the_metal --execute="SOURCE database/seed.sql"
```

If you created the test database, import its schema too:

```powershell
& 'C:\xampp\mysql\bin\mysql.exe' -u YOUR_DB_USER -p petal_to_the_metal_test --execute="SOURCE database/schema.sql"
```

Alternatively, start Apache to access phpMyAdmin, select the application database, and use **Import** for `database/schema.sql`, then `database/seed.sql`. Select the test database and import only `database/schema.sql` there.

Import these files once into fresh databases. An already configured copy does not need reimporting. The seed creates three categories and twelve sample arrangements; it does not create any user accounts.

### 4. Create an administrator

```powershell
& 'C:\xampp\php\php.exe' scripts/create-admin.php --email=admin@example.test --name=Administrator
```

Enter a password of 8 to 72 characters when prompted. Keep those login details for the admin panel. The console may show the password as you type. Registering through the website creates a customer account, so use this script for an administrator.

### 5. Check the environment and start the website

```powershell
& 'C:\xampp\php\php.exe' scripts/check-environment.php
& 'C:\xampp\php\php.exe' -m
```

The environment checker checks PHP, PDO MySQL, and both database connections. The module list should include `PDO`, `pdo_mysql`, and `mbstring`. If you skipped the optional test database, its connection check will fail; the website can still run when its application connection is correct.

Then start the server:

```powershell
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8000 -t public
```

Open **http://127.0.0.1:8000/**. Register a customer to try shopping, or use your administrator account to access the admin panel.

## Where the frontend files are

| Location | Contents |
| --- | --- |
| `public/*.php` | Website pages containing HTML and PHP that fills in database/session data. |
| `templates/` | Shared HTML/PHP parts, including the header, footer, and account menu. |
| `public/assets/css/` | Styles: `styles.css`, `experience.css`, and `admin.css`. |
| `public/assets/js/` | Vanilla JavaScript for navigation, forms, catalog, bag, and checkout. |
| `public/assets/images/` | Local flower photographs. |
| `app/` | Backend logic. |
| `database/` | Database schema and sample data. |
| `config/app.php` | Shop name, currency, timezone, fulfillment fees, and URL base path. |

The page files end in `.php` because PHP generates their HTML. Opening them directly as files or using Live Server will not run the PHP backend. Serve **`public/`** as the web root, as the `-t public` command does. If using Apache instead, point its virtual host's `DocumentRoot` to this project's `public/` directory; see [the detailed setup guide](docs/setup.md).

## Optional development checks

These are useful when changing the project, but are not required every time you start it:

```powershell
& 'C:\xampp\php\php.exe' scripts/lint.php
& 'C:\xampp\php\php.exe' tests/run.php --suite=unit
& 'C:\xampp\php\php.exe' tests/run.php
```

The full test suite requires the separate test database configured above. Keep its name ending in `_test` and separate from the application database.

Browser automation is optional and additionally needs Node.js, a resolvable `playwright` package, and its Chromium browser. The full browser workflow also reads a local demo administrator credential file. See [docs/setup.md](docs/setup.md#tests) for browser QA details and [docs/verification.md](docs/verification.md) for results. None of these browser-test dependencies is needed to use the website.

## Troubleshooting

| Problem | What to do |
| --- | --- |
| PHP command is not recognized | Use the full `C:\xampp\php\php.exe` path shown above; check where XAMPP is installed. |
| Could not find driver | Enable `pdo_mysql` in the PHP configuration used by that executable, then restart the PHP server. |
| Undefined function `mb_strlen` | Enable `mbstring` in that PHP configuration and restart the PHP server. |
| Database connection refused | Start XAMPP MySQL and check the host and port in `config/local.php`. |
| Access denied or unknown database | Correct the database credentials/name and create the application database if missing. |
| Missing tables or no flowers | On a fresh database, import the schema and then the application seed as described above. |
| Pages or assets return 404 | Run from the project folder with `-t public`; leave `base_path` empty for this localhost setup. |
| Port 8000 is already in use | Open the existing project preview, or use `-S 127.0.0.1:8002 -t public` and visit `http://127.0.0.1:8002/`. |
| Old styles are still showing | Press **Ctrl+F5** to reload the browser's cached styles and scripts. |
| The shop is temporarily unavailable | Check `storage/logs/php.log` locally and run the environment checker. |

Payment and refunds in this project are simulated. No payment gateway, card details, or real-money setup is required.
