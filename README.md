# WEEZ.GG Secure PHP Website (Copy/Paste & Run)

## What you get
- Secure login (sessions + password hashing)
- CSRF protection for state-changing requests
- Role permissions (admin vs customer)
- Admin dashboard stats (Revenue/Profit) in LKR
- Admin item management (add/delete)
- Customer shop + place orders with optional offer codes
- Orders view:
  - Customer sees only their orders
  - Admin sees all orders + can change status

## 1) Requirements
- PHP 8+
- MySQL / MariaDB
- Apache (recommended) OR Nginx (you can still use it)

## 2) Setup (cPanel / shared hosting)
1. Upload the contents of `/public` into your `public_html/`
2. Upload `/api`, `/lib`, `/sql` to the same level (NOT public_html if you can).
   - If your host forces everything inside public_html, it's OK; `.htaccess` blocks direct access.
3. Create a MySQL database named `weezgg` and a DB user, then update:
   - `lib/config.php` (db dsn/user/pass)

## 3) Install database (ONE TIME)
Open:
`/install.php?key=840e15dbf96cea5debd8259661eba585`

It will create tables + seed demo users/items/offers.

**After it says Installed ✅**
DELETE: `public/install.php`

## 4) Login
- Admin: Januk / januk@9865
- Admin: Tobi / tobi@9865
- Admin: Kitty / kitty@9865
- Customer: customer / customer123

## 5) Security notes
- Turn on HTTPS then set `session.secure` to true in `lib/config.php`
- Do not keep install.php on the server after setup.
