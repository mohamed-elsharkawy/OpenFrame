# Front Office

A front-office (reception) application built on the **OpenFrame** micro framework
(pure PHP + Bootstrap 5). Multi-language (Arabic default / English, LTR/RTL),
two user levels with database-driven permissions, guest register with ID
document upload, departments hierarchy and admin reports.

## Requirements

- PHP 8.0+ with `pdo_mysql`, `mbstring`, `fileinfo` (all enabled in a default WAMP install)
- MySQL 8 / MariaDB (WAMP is fine)

## Installation

1. Copy this folder to your web root, e.g. `D:\wamp64\www\frontoffice`
2. Create the database — import **`database.sql`** with phpMyAdmin, or:
   ```
   mysql -uroot < database.sql
   ```
   The SQL creates the `frontoffice` database, all tables (each with an
   auto-increment primary key), permissions, the two user levels, sample
   departments, sample visits and the two accounts below.
3. Check the credentials in **`includes/config.php`** if needed
   (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` — WAMP default is root / empty).
4. Open `http://localhost/frontoffice/` — the app opens directly on the login page
   (there is no landing page).

## Accounts

| User       | Password    | Level       | Can do                                                        |
|------------|-------------|-------------|---------------------------------------------------------------|
| `admin`    | `Admin@123` | Admin user  | Everything: both reports + departments hierarchy              |
| `reception`| `Guest@123` | Entry user  | New guest, record leave, search guests                        |

Permissions live in the `permissions` + `user_level_permissions` tables —
add a row to assign a permission to the entry level, no code change needed.

## Features

- **Dashboard** — live date/time clock (updates every second) + three action
  buttons; admins get a second row (reports, departments).
- **New Guest** — read-only record ID, mandatory full name / national ID or
  passport / purpose / department, optional ID/passport image upload saved to
  `uploads/YYYY/MM/DD/` (folders are created automatically on first use).
- **Record Guest Leave** — search by name or record number, the open visits
  (no leave time yet) are listed in a grid; pick one, choose the leave time
  with the date & time picker and save.
- **Search Guest Information** — search by name, ID/passport or record number;
  every visit of the guest appears in a grid (with the ID copy when uploaded).
- **My profile** — every logged-in user (entry or admin) can view and update
  their full name, username and password from the navbar link.
- **Users management** (admin) — add / edit / delete users and define their
  level (admin or entry) with a dropdown; the admin sets or resets passwords
  (leave empty on edit to keep the current one). Deleting your own account is
  blocked, and the last active administrator cannot be deleted.
- **Departments hierarchy** (admin) — add with parent, rename, delete
  (sub-departments move up one level).
- **Guests report for a period** (admin) — totals + full grid, print view and
  CSV export (UTF-8 BOM, opens correctly in Excel with Arabic).
- **Guest entry report based on users** (admin) — entries per user for a period.
- **Languages** — Arabic (default, RTL Bootstrap) and English (LTR); the
  switcher is on the login page and in the navbar, stored in `$_SESSION['lang']`.

## Security notes

- Passwords: `password_hash()` (bcrypt).
- All SQL uses prepared statements.
- All POST forms carry a CSRF token.
- `uploads/.htaccess` denies script execution inside the uploads folder.
- Visitors are redirected to the login page; every guarded module checks its
  `permission` from its own `info.php`.

## Creating a new module

Create `modules/mytool/info.php`:

```php
<?php
return [
    'name'       => 'My tool',
    'name_ar'    => 'أداتي',
    'icon'       => 'bi-tools',
    'order'      => 70,
    'menu'       => true,        // false = hidden from the navbar
    'layout'     => 'app',       // 'auth' = standalone page (no navbar)
    'permission' => 'my_code',   // checked against the permissions table
];
```

and `modules/mytool/index.php` as the view — it appears automatically in the
routes and (when permitted) in the navbar.
