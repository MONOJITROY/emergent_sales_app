# StockFlow — Sales & Inventory Management

A compact, mobile-first sales & inventory web app built with a **custom PHP 8 MVC**, **MySQL/PDO**, **Bootstrap 5**, **jQuery**, and **iziToast**. All state-changing operations are sent via AJAX and CSRF-protected.

> ⚠️ This project runs on **your own LAMP/XAMPP/MAMP server**. The Emergent preview container only runs React/FastAPI, so the live preview will not serve this PHP app.

## Stack
- PHP 8.x (custom MVC — no framework)
- MySQL 5.7+/8 via PDO **prepared statements only**
- Bootstrap 5.3 (CDN)
- jQuery 3.7 (CDN)
- iziToast (CDN) for all alerts / confirms
- Session-based auth + role-based access (`admin`, `staff`)
- CSRF tokens validated on every state-changing AJAX request

## Folder layout
```
app/
├── Core/          App, Router, Controller, Model, Database, Auth, Csrf, View
├── Controllers/   AuthController, DashboardController, Products, Customers, Suppliers, Sales, Purchases, Reports, Users
├── Models/        User, Product, Customer, Supplier, Sale, Purchase
└── Views/         layouts + per-module views
public/
├── index.php      Front controller (URL rewriting target)
├── .htaccess      Pretty URLs
└── assets/        css/app.css, js/app.js
sql/
└── schema.sql     Database schema + admin seed
config.php         DB credentials, app config (NOT committed in real projects)
```

## Setup (5 minutes)

1. **Install Composer dependencies** (for XLSX/PDF/email):
   ```bash
   composer install
   ```
2. **Create the database** (in MySQL):
   ```sql
   CREATE DATABASE stockflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. **Import schema** (creates tables + seeds admin):
   ```bash
   mysql -u root -p stockflow < sql/schema.sql
   ```
3. **Configure `config.php`** — set DB host/user/password and your `BASE_URL`.
4. **Point your web server** document root at `public/`.
   - Apache: enable `mod_rewrite`, use the bundled `.htaccess`.
   - Nginx: rewrite all unknown paths to `/index.php`.
   - PHP built-in (quick test): from project root run
     ```bash
     php -S localhost:8000 -t public
     ```
5. **Open** `http://localhost:8000` and sign in:
   - **Email:** `admin@stockflow.test`
   - **Password:** `admin123`

## Modules
- Dashboard (KPIs, 7-day sales chart, low-stock list, recent invoices)
- Products + Stock
- Customers (with running balance)
- Suppliers
- Sales / Invoices (multi-line items, discount, tax, payment, print)
- Purchases (increments stock + updates cost price)
- Reports (sales by customer / by product, invoice aging)
- Users & Roles (admin-only)

## Security
- PDO prepared statements for **every** query
- CSRF token validated on all POST/PUT/DELETE
- `htmlspecialchars()` on all view output
- bcrypt password hashing via `password_hash()` / `password_verify()`
- Role-based access in controllers
- Session regeneration on login

## SMTP (for email-invoice)
Set these env vars before starting the server (the values below match Mailtrap’s sandbox):
```bash
export SMTP_HOST=smtp.mailtrap.io
export SMTP_PORT=2525
export SMTP_USER=your_username
export SMTP_PASS=your_password
export SMTP_SECURE=tls
export SMTP_FROM=no-reply@yourdomain.com
export SMTP_FROM_NAME="StockFlow"
```
Then on any invoice detail page click **Email** → enter recipient → a PDF is generated via Dompdf and sent via PHPMailer.

## Exports
- **Sales list → XLSX**: button on `/sales` (uses PhpSpreadsheet) → `GET /api/exports/sales.xlsx`
- **Invoice → PDF**: button on invoice detail → `GET /api/exports/invoice/{id}.pdf` (`?download=1` to force download)

## Edit an existing sale
On the invoice detail page click **Edit**. The form pre-loads the existing lines; on save, prior stock is restored and the new lines are applied atomically (transaction).
