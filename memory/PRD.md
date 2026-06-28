# StockFlow — PRD

## Original problem statement
Create a simple yet powerful sale and inventory management web app with modern compact UI/UX.

Hard constraints honored:
- PHP 8.x custom MVC (no framework)
- MySQL via PDO prepared statements only
- Bootstrap 5 layout + Bootstrap Icons
- Vanilla JS + jQuery
- All state-changing operations via AJAX (no plain HTML form POSTs)
- iziToast for every alert / confirmation / question
- Session-based auth with admin/staff roles
- CSRF token validated on every state-changing AJAX call
- Mobile-first, compact, responsive

## Architecture
- Front controller: `public/index.php`
- MVC: `app/Core/{App,Router,Controller,Model,Database,Auth,Csrf,Request,View,Autoload}.php`
- Schema: `sql/schema.sql` (8 tables, FK constraints, indexes)
- Assets: `public/assets/{css/app.css, js/app.js}`
- Config: `config.php` (DB creds, base URL, admin seed)

## Implemented modules
- Auth: login / logout / session regen / admin seed (bcrypt)
- Dashboard: 4 KPIs + 7-day sales bar chart (Chart.js) + low-stock list + recent invoices
- Products: list/search, create/edit/delete via modal+AJAX, low-stock highlight
- Customers: list/search/CRUD + running balance column
- Suppliers: list/search/CRUD
- Sales/Invoices: multi-line form (product picker w/ stock display), subtotal/discount/tax/paid/balance, auto invoice numbering INV-00001, decrements stock, updates customer balance; invoice detail with print view; record payment endpoint; delete restores stock & balance
- Purchases: modal multi-line form, auto ref no PUR-00001, increments stock + updates cost_price; delete reverses stock
- Reports: Sales by Customer, Sales by Product, Invoice Aging (0-30/31-60/61-90/90+ buckets)
- Users & Roles: admin-only list / create / delete

## Default admin
- `admin@stockflow.test` / `admin123` (seeded on every boot via `App::ensureAdminSeed()`)

## P0/P1 backlog (deferred)
- Excel export via PhpOffice/PhpSpreadsheet
- PDF export via Dompdf/TCPDF and email-invoice flow via PHPMailer
- Edit existing sale (currently delete & recreate)
- Per-user audit log
- Multi-currency / tax-rate library
- Stock movement history table

## Notes
The Emergent preview container does NOT run PHP/Apache/MySQL — only React/FastAPI. The app is meant to be deployed to the user’s own LAMP/XAMPP/MAMP server. Steps in `README.md`.
