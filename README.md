# OpenFrame

**A small, module-driven PHP framework.** One `index.php`, one folder per screen, Bootstrap 5 out of the box — and nothing to learn beyond plain PHP.

OpenFrame is deliberately tiny. There is no templating language, no ORM, no dependency injection and no build step. A module is a folder with an `info.php` and an `index.php`. A database row is a `db_insert()` call. If you can write PHP, you can read this entire framework in under ten minutes.

```text
index.php?module=home  →  includes/config.php    (settings + session)
                       →  includes/helpers.php   (redirect, CSRF, flash, input)
                       →  includes/db.php        (PDO + CRUD helpers)
                       →  includes/modules.php   (auto-discovery)
                       →  includes/routes.php    (routing + 404)
                       →  includes/header.php    (<head>, navbar, <main>)
                       →  modules/<slug>/index.php
                       →  includes/footer.php    (footer, scripts, </html>)
```

---

## Table of contents

1. [Features](#1-features)
2. [Requirements](#2-requirements)
3. [Quick start](#3-quick-start)
4. [Directory structure](#4-directory-structure)
5. [Request lifecycle](#5-request-lifecycle)
6. [Configuration](#6-configuration)
7. [Core helpers](#7-core-helpers)
8. [Modules](#8-modules)
9. [Routing](#9-routing)
10. [Layout, assets and theming](#10-layout-assets-and-theming)
11. [Database and CRUD](#11-database-and-crud)
12. [Function reference](#12-function-reference)
13. [Security](#13-security)
14. [Adding third-party libraries](#14-adding-third-party-libraries)
15. [Recipes: uploads, login, languages](#15-recipes-uploads-login-languages)
16. [Troubleshooting](#16-troubleshooting)
17. [Deployment checklist](#17-deployment-checklist)
18. [License and credits](#18-license-and-credits)

---

## 1. Features

| Feature | How it works |
|---|---|
| **Auto-discovered modules** | Every folder in `modules/` containing an `info.php` becomes a page, a route and a navbar link. No registry file to edit. |
| **Zero-config URLs** | `index.php?module=notes` — works in `/`, in a sub-folder, with or without URL rewriting. |
| **Session-backed settings** | `config('appname')` reads, `set_config('appname', 'X')` writes for the rest of the session. |
| **Bootstrap 5.3 bundled** | CSS, JS bundle and Bootstrap Icons ship inside `assets/`, so the framework works offline. |
| **PDO data layer** | Lazy connection, prepared statements, and CRUD helpers: `db_insert`, `db_update`, `db_delete`, `db_find`, `db_exists`, `db_count`, `db_paginate`, `db_transaction`. |
| **Security helpers** | `e()` escaping, CSRF tokens, safe SQL identifiers, file-upload validation recipe. |
| **No build step** | No Composer required to run. (Composer is optional, for libraries such as mPDF or PhpSpreadsheet.) |
| **Hard 404s** | Unknown or disabled modules return HTTP 404, not a blank page. |

**Design rules**

1. A module may not need the database, the session, or any helper — a module can be one HTML file.
2. Output is always escaped at the edge with `e()`, never sanitised on the way in.
3. SQL values always travel through placeholders. Identifiers always go through `db_ident()`.
4. Nothing is global except the functions; module state lives in its own file scope.

---

## 2. Requirements

| Component | Minimum | Notes |
|---|---|---|
| PHP | 8.0 | Tested on PHP 8.3. Uses `<=>`, `Throwable`, short arrays. |
| Extensions | `pdo_mysql`, `mbstring`, `fileinfo` | `gd` only if you resize images. |
| Database | MySQL 5.7+ / MariaDB 10.3+ | Optional — pages that never call `db()` run without it. |
| Web server | Apache 2.4 / Nginx / PHP built-in server | No rewrite rules needed. |
| Browser | Any modern browser | Bootstrap 5.3. |

The front end is pre-compiled. There is **no Node.js step** — you never need npm, Vite or a bundler.

---

## 3. Quick start

### 3.1 Place the files

Copy the `OpenFrame` folder into your web root and rename it if you like:

```text
D:\wamp64\www\openframe      (WAMP)
/var/www/html/openframe      (Linux)
```

### 3.2 Open it

```text
http://localhost/openframe/
```

The **Home** module lists every installed module. OpenFrame detects its own base URL automatically, so `http://localhost/openframe/` and `http://localhost/` both work without changes.

### 3.3 Create your first module

```bash
mkdir modules/hello
```

`modules/hello/info.php`

```php
<?php
return [
    'name'        => 'Hello',
    'description' => 'My first module.',
    'version'     => '1.0.0',
    'icon'        => 'bi-emoji-smile',
    'enabled'     => true,
    'order'       => 10,
];
```

`modules/hello/index.php`

```php
<h1 class="h3">Hello, OpenFrame!</h1>
<p class="text-muted">Rendered at <?php echo e(date('H:i:s')); ?>.</p>
```

Reload the page: **Hello** is already in the navbar and at `index.php?module=hello`. No configuration, no cache, no command.

### 3.4 (Optional) Connect a database

Create a database named `openframe`, then import a table:

```sql
CREATE TABLE demo_notes (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(120) NOT NULL,
  body       TEXT NULL,
  created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Defaults are `127.0.0.1:3306`, user `root`, empty password. Change them in `includes/config.php` (section 6.3).

### 3.5 Try the complete CRUD module

```bash
# 1. create the table
mysql -h 127.0.0.1 -u root openframe < docs/examples/notes_crud/schema.sql

# 2. activate the module
copy docs\examples\notes_crud modules\notes_crud     (Windows)
cp -r docs/examples/notes_crud modules/notes_crud    (Linux/macOS)
```

Open `index.php?module=notes_crud`. The example implements list, create, edit, delete, validation, flash messages, CSRF protection and pagination in ~150 lines. Section 11.6 walks through it line by line.

---

## 4. Directory structure

```text
OpenFrame/
├── index.php                  entry point (front controller)
├── includes/
│   ├── config.php             settings, session, DB constants, config()/set_config()/e()/url()
│   ├── helpers.php            redirect, CSRF, flash, input, abort, JSON
│   ├── db.php                 PDO connection + CRUD helpers
│   ├── modules.php            module discovery
│   ├── routes.php             route table, current_module(), load_module()
│   ├── header.php             <head>, navbar, opening <main>
│   └── footer.php             footer, Bootstrap JS, closing tags
├── modules/
│   └── home/
│       ├── info.php           metadata (name, icon, order, ...)
│       └── index.php          the view
├── assets/
│   ├── css/bootstrap.min.css
│   ├── css/bootstrap-icons.min.css
│   ├── css/fonts/             icon fonts (local, no CDN)
│   ├── js/bootstrap.bundle.min.js
│   └── img/logo.png
└── docs/
    ├── OpenFrame-User-Guide.docx
    ├── OpenFrame-User-Guide.md
    └── examples/notes_crud/   ready-to-copy CRUD module + schema.sql
```

Only `modules/` grows as you build. Everything else is infrastructure you configure once.

---

## 5. Request lifecycle

`index.php` is 33 lines, in this exact order:

```php
define('OPENFRAME', true);   // guards header.php / footer.php against direct access
ob_start();                  // lets the router change the status code after output
require 'includes/config.php';   // session + settings + DB constants + config helpers
require 'includes/helpers.php';  // redirect, CSRF, flash, input, abort
require 'includes/db.php';       // PDO + CRUD (connection opens lazily)
require 'includes/modules.php';  // scans modules/*/info.php
require 'includes/routes.php';   // builds slug → file map
require 'includes/header.php';   // doctype, <head>, navbar, <main>
load_module();                   // require_once modules/<slug>/index.php
require 'includes/footer.php';   // footer, scripts, </body></html>
```

Three consequences worth remembering:

* **Output buffering.** `ob_start()` means the browser receives nothing until the request ends. That is what allows `load_module()` to send `404` *after* the navbar has already been printed. It also means binary exports must clear the buffer (see [14.3](#143-mpdf-pdf)).
* **Lazy database.** `db()` connects on first call. A static page never touches MySQL, so the site still loads while the database is down.
* **Module file scope.** `load_module()` does `require $file` inside its own function, so every module view receives:

| Variable | Contents |
|---|---|
| `$module` | This module's metadata array (from `info.php`) |
| `$GLOBALS['openframe_module']` | The same array, for helpers that need it |
| `$appName`, `$appVersion`, `$appLogo`, `$modulesNav`, `$activeSlug`, `$pageTitle` | Layout variables from `header.php` |

A module may also call any function from `config.php`, `helpers.php`, `db.php`, `modules.php` and `routes.php`.

---

## 6. Configuration

### 6.1 The settings array

`includes/config.php` defines the defaults:

```php
$settings = [
    'appname'    => 'OpenFrame',
    'apppath'    => $appPath,                       // filesystem path (auto)
    'appurl'     => $appUrl,                        // base URL       (auto)
    'applogo'    => $appUrl . '/assets/img/logo.png',
    'appversion' => '1.0.0',
    'appauthor'  => 'OpenFrame',
    'appdebug'   => true,
];
```

On the first request the array is copied into `$_SESSION['openframe']`. On later requests, session values win, so a `set_config()` call survives page loads. **Paths and URLs are recalculated on every request**, so moving the folder never breaks links.

### 6.2 `config()` and `set_config()`

```php
config();                       // the whole settings array
config('appname');              // 'OpenFrame'
config('theme', 'light');       // 'light' (default when the key is missing)

set_config('appname', 'Front Office');
set_config('appdebug', false);
```

`set_config()` changes the value **for the current session**. To change it for everybody, edit `config.php`.

> `appdebug` is stored for convenience. OpenFrame never prints debug output on its own — use it in your own error handling:
> ```php
> if (config('appdebug')) { echo '<pre>' . e(print_r($row, true)) . '</pre>'; }
> ```

### 6.3 Database connection

`includes/config.php` defines the constants read by `includes/db.php`:

```php
define('DB_HOST', '127.0.0.1');   // use 127.0.0.1, not "localhost", on Windows
define('DB_PORT', 3306);
define('DB_NAME', 'openframe');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
```

Every `define` is wrapped in `if (!defined(...))`, so you can also set them from a bootstrap file **before** `config.php` loads:

```php
define('DB_NAME', 'frontoffice');
define('DB_PASS', 'secret');
require __DIR__ . '/includes/config.php';
```

`utf8mb4` is the default because it stores Arabic, emoji and every other 4-byte character correctly. Do not downgrade to `utf8`.

### 6.4 Application constants

| Constant | Value |
|---|---|
| `OPENFRAME` | `true` once the framework booted |
| `OPENFRAME_PATH` | Absolute path of the application root |
| `OPENFRAME_URL` | Base URL **without** a trailing slash (`''` when installed at the domain root) |

---

## 7. Core helpers

Four functions live in `config.php` and are available everywhere.

```php
// Read configuration
$name = config('appname');

// Write configuration (persists in the session)
set_config('appname', 'My App');

// Escape everything you print
echo e($user['full_name']);          // <script> → &lt;script&gt;

// Build a URL that works in any sub-folder
<a href="<?php echo e(url('index.php?module=home')); ?>">Home</a>
```

`e()` is `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`. **Escape at the point of output**, even for data you think is safe — a database field filled by a visitor is not safe.

`url('')` returns `/openframe/` (base plus slash); `url('assets/x.css')` returns `/openframe/assets/x.css`. Do not prepend a slash yourself.

`helpers.php` adds request utilities — documented in [12.2](#122-helpersphp).

---

## 8. Modules

### 8.1 What makes a module

```text
modules/
└── invoices/
    ├── info.php      ← required: metadata
    └── index.php     ← required: the page
```

Folder name = **slug** = the value used in `?module=invoices`. Slugs are sanitised to `a-z`, `0-9`, `_` and `-`, so use only those characters. A folder without `info.php` is ignored completely.

### 8.2 `info.php` keys

`info.php` must `return` an array.

| Key | Default | Purpose |
|---|---|---|
| `name` | Folder name, title-cased | Label in the navbar and page `<title>` |
| `description` | `''` | Shown by the Home module |
| `version` | `'1.0.0'` | Informational |
| `icon` | `'bi-puzzle'` | Any Bootstrap Icons class, e.g. `bi-people` |
| `enabled` | `true` | `false` hides it from the navbar **and** makes it 404 |
| `order` | `100` | Ascending navbar position; ties sort by name |
| *(anything else)* | — | Stored as-is in `$module` and `$GLOBALS['openframe_module']` |

Example with a custom key:

```php
<?php
return [
    'name'        => 'Invoices',
    'description' => 'Create and print customer invoices.',
    'version'     => '2.1.0',
    'icon'        => 'bi-receipt',
    'enabled'     => true,
    'order'       => 20,
    'author'      => 'Finance team',
    'permission'  => 'invoices.manage',   // your own key — OpenFrame ignores it
];
```

Read the custom key inside the view:

```php
$permission = isset($module['permission']) ? $module['permission'] : null;
```

`slug` and `folder` are always overwritten with the real folder name, so you cannot create a slug that does not match its folder. `path` and `file` (absolute paths) are added automatically.

### 8.3 The view

`index.php` is executed inside the layout, after the navbar. Start with HTML; open `<?php` only when you need it.

```php
<?php
$rows = db_rows('SELECT * FROM customers ORDER BY name');
?>

<h1 class="h4 mb-3"><i class="bi bi-people me-1"></i>Customers</h1>

<table class="table table-striped">
    <?php foreach ($rows as $row): ?>
        <tr><td><?php echo e($row['name']); ?></td></tr>
    <?php endforeach; ?>
</table>
```

### 8.4 Handling forms inside a module

There are no separate controllers. A module handles its own POST, then redirects (Post/Redirect/Get) so a refresh cannot resubmit:

```php
<?php
if (is_post()) {
    csrf_check();

    $name = input('name');

    if ($name === '') {
        flash('danger', 'The name is required.');
    } else {
        db_insert('customers', ['name' => $name, 'created_at' => now()]);
        flash('success', 'Customer saved.');
    }

    redirect(url('index.php?module=customers'));
}
```

`flash()` messages are printed automatically by `header.php` on the next page — no markup needed in the module.

### 8.5 Sub-pages, actions and tabs

Keep one module per task. For a "view record" page, read an `id` from the query string:

```php
$id  = (int) input('id');
$row = db_find('customers', $id);

if (!$row) {
    abort(404, 'Customer not found.');
}
```

For multiple screens inside one module, switch on a `view` parameter:

```php
$view = input('view', 'list');

if ($view === 'edit') {
    // ... render the form ...
    return;          // return from the module file: footer.php still runs
}
```

`return` inside a module ends that file only — the layout closes normally in `footer.php`.

### 8.6 Enabling, disabling and ordering

* **Disable**: set `'enabled' => false`. The route disappears and direct requests 404. Nothing is deleted.
* **Reorder**: change `order`. `0` sorts first; default is `100`.
* **Rename**: change `name`, not the folder (the folder is the URL).
* **Remove**: delete the folder. Nothing else references it.

### 8.7 Module assets

Two supported patterns:

**A. Global assets** (used by all modules) — put files in `assets/` and reference them from `header.php` or `footer.php`. Best for shared CSS/JS.

**B. Per-module assets** — give the module its own folders and link them from the view:

```text
modules/invoices/assets/invoices.css
modules/invoices/assets/invoices.js
```

```php
<link rel="stylesheet" href="<?php echo e(url('modules/invoices/assets/invoices.css')); ?>">
```

Because the view is printed inside `<body>`, a `<link>` there is valid HTML but blocks rendering slightly. For anything used on several pages, prefer pattern A.

### 8.8 Hiding a module from the navbar but keeping it routable

The core navbar lists every **enabled** module. To hide one, add your own flag and filter in `includes/header.php`:

```php
$modulesNav = array_filter(enabled_modules(), function ($module) {
    return !isset($module['menu']) || $module['menu'] !== false;
});
```

Then in the module's `info.php`:

```php
'menu' => false,
```

`get_routes()` still uses `enabled_modules()`, so the page keeps working while staying out of the menu. (This is exactly how the Front Office application hides pages that are already dashboard buttons.)

---

## 9. Routing

### 9.1 The route table

```php
get_routes();      // ['home' => 'D:/.../modules/home/index.php', 'notes' => '...']
```

Routes are rebuilt from the filesystem on every request — **there is no cache to clear**. Adding or deleting a folder takes effect on the next reload.

### 9.2 `current_module()`

| Request | Result |
|---|---|
| `index.php` | `home` if it exists, otherwise the first enabled module |
| `index.php?module=notes` | `notes` |
| `index.php?module=Notes` | `notes` (lower-cased) |
| `index.php?module=notes&id=4` | `notes` (extra parameters are untouched) |
| `index.php?module=nope` | `''` → `load_module()` returns 404 |
| `index.php?module=<script>` | Sanitised, then 404 |

The requested slug is stripped to `[a-z0-9_-]` and then checked against the route table, so a module file can only be reached if it is enabled and present.

### 9.3 `load_module()`

```php
load_module();   // called once, by index.php
```

It resolves the slug, verifies the view file exists, stores the metadata in `$GLOBALS['openframe_module']` and requires the file. Failures produce a Bootstrap alert with status `404`.

### 9.4 Pretty URLs (optional)

OpenFrame does not need rewriting, but if you want `/notes` instead of `?module=notes`, add:

**Apache** — `.htaccess` in the app root:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([a-z0-9_-]+)/?$ index.php?module=$1 [L,QSA]
```

Then map the path to `$_GET['module']` before `current_module()` runs (one line in `index.php`):

```php
if (!isset($_GET['module'])) {
    $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $path = preg_replace('#^' . preg_quote(trim(OPENFRAME_URL, '/'), '#') . '/?#', '', $path);
    if ($path !== '') { $_GET['module'] = explode('/', $path)[0]; }
}
```

`url('index.php?module=x')` keeps working either way, so existing links never break.

---

## 10. Layout, assets and theming

### 10.1 `header.php`

Responsible for:

* `<!DOCTYPE html>`, `<html lang="en">`, charset, viewport, generator meta;
* page `<title>` — `AppName — Module name`, built from the active module;
* favicon (the app logo);
* Bootstrap CSS + Bootstrap Icons CSS;
* the navbar: brand + version badge, one link per enabled module (active state included), "Powered by OpenFrame";
* `<main class="container flex-grow-1 py-4">` and `flash_render()`.

It refuses to run unless `OPENFRAME` is defined:

```php
if (!defined('OPENFRAME')) { http_response_code(403); exit('Direct access is not allowed.'); }
```

### 10.2 `footer.php`

Closes `<main>`, prints the footer bar (copyright, version, PHP version) and loads `bootstrap.bundle.min.js` (Popper included). Load extra scripts **after** the Bootstrap bundle so `bootstrap.*` is available.

### 10.3 Changing the brand

| What | Where |
|---|---|
| App name | `config.php` → `appname` (or `set_config('appname', '…')`) |
| Logo | Replace `assets/img/logo.png` (used as navbar brand and favicon) |
| Version | `config.php` → `appversion` |
| Colours | Override Bootstrap CSS variables on `body` in a `<style>` block in `header.php`, or add your own stylesheet |
| Navbar contents | `header.php` (one `foreach` over `$modulesNav`) |

Example theme override appended to `<head>`:

```html
<style>
    :root {
        --bs-primary: #997512;
        --bs-primary-rgb: 153, 117, 18;
        --bs-link-color: #332b15;
        --bs-link-hover-color: #997512;
    }
    .btn-primary { --bs-btn-bg: #997512; --bs-btn-border-color: #997512; }
</style>
```

### 10.4 RTL and other languages

`<html lang="en">` is hard-coded. To support Arabic (or any RTL language), see [15.3](#153-multilingual--rtl).

---

## 11. Database and CRUD

### 11.1 Connect once

```php
require_once __DIR__ . '/includes/db.php';   // already loaded by index.php
$pdo = db();                                  // the shared PDO instance
```

`db()` caches one connection per request and configures:

```php
PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // failures throw
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // arrays, not objects
PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
```

If the connection fails, it stops with a 500 page that names the database and host — check `DB_*` in `config.php` and confirm MySQL is running.

### 11.2 The four CRUD operations

```php
// CREATE — returns the new AUTO_INCREMENT id
$id = db_insert('customers', [
    'name'       => 'Sara Ahmed',
    'email'      => 'sara@example.com',
    'created_at' => now(),
]);

// READ — one row, or null
$customer = db_find('customers', $id);
$byEmail  = db_find('customers', 'sara@example.com', 'email');   // custom key

// UPDATE — returns affected rows
db_update('customers', ['name' => 'Sara A.'], 'id = ?', [$id]);

// DELETE — returns affected rows
db_delete('customers', 'id = ?', [$id]);
```

`db_insert()` and `db_update()` accept column names as array keys. Column names are validated and back-ticked by `db_ident()`; **values are always bound**, never interpolated.

### 11.3 Reading data

```php
// One row
$row = db_row('SELECT * FROM customers WHERE id = ?', [$id]);
if ($row === null) { /* not found */ }

// Many rows
$rows = db_rows(
    'SELECT c.*, d.name AS department
       FROM customers c
       LEFT JOIN departments d ON d.id = c.department_id
      WHERE c.active = ?
   ORDER BY c.name',
    [1]
);

// A single value
$total = db_column('SELECT COUNT(*) FROM customers WHERE active = ?', [1]);

// Existence and counts
if (db_exists('customers', 'email = ?', [$email])) { /* duplicate */ }
$open = db_count('tickets', 'closed_at IS NULL');
```

Use `?` placeholders for **values**. Never put a variable into the SQL string:

```php
// WRONG — SQL injection
db_rows("SELECT * FROM customers WHERE email = '$email'");

// RIGHT
db_rows('SELECT * FROM customers WHERE email = ?', [$email]);
```

Placeholders cannot stand in for identifiers (table or column names). Build those from a fixed allow-list, or use `db_ident()`:

```php
$sortable = ['name', 'email', 'created_at'];
$sort = in_array(input('sort'), $sortable, true) ? input('sort') : 'name';
$rows = db_rows('SELECT * FROM customers ORDER BY ' . db_ident($sort));
```

### 11.4 Pagination

`db_paginate()` takes a query **without** `LIMIT` and returns everything a page needs:

```php
$page = db_paginate(
    'SELECT * FROM customers ORDER BY name',
    [],                       // bindings
    (int) input('page', '1'), // current page (1-based)
    25                        // rows per page
);

// $page = [
//   'rows'     => [...],   // rows for this page
//   'total'    => 137,
//   'page'     => 2,
//   'per_page' => 25,
//   'pages'    => 6,
// ]
```

It counts with `SELECT COUNT(*) FROM (<your query>) AS of_count`, so it handles joins and `GROUP BY` correctly. With `GROUP BY`, the total is the number of groups.

```php
<?php for ($i = 1; $i <= $page['pages']; $i++): ?>
    <a class="btn btn-sm <?php echo $i === $page['page'] ? 'btn-primary' : 'btn-outline-secondary'; ?>"
       href="<?php echo e(url('index.php?module=customers&page=' . $i)); ?>"><?php echo $i; ?></a>
<?php endfor; ?>
```

### 11.5 Transactions

Anything that must succeed or fail **together** goes in `db_transaction()`. The callback runs inside `BEGIN … COMMIT`; any exception triggers `ROLLBACK` and is re-thrown.

```php
try {
    db_transaction(function () {
        $orderId = db_insert('orders', [
            'customer_id' => 12,
            'total'       => 250.00,
            'created_at'  => now(),
        ]);

        db_insert('order_lines', [
            'order_id' => $orderId,
            'product'  => 'Desk lamp',
            'qty'      => 2,
        ]);
    });

    flash('success', 'Order created.');
} catch (Throwable $ex) {
    flash('danger', 'Could not save the order: ' . $ex->getMessage());
}
```

Return a value from the callback and `db_transaction()` returns it:

```php
$newId = db_transaction(function () {
    return db_insert('invoices', ['number' => 'INV-0001']);
});
```

Transactions require InnoDB tables. MyISAM ignores them silently.

### 11.6 A complete CRUD module, explained

The full source is in `docs/examples/notes_crud/`. This is how it is put together.

#### Step 1 — the table

```sql
CREATE TABLE demo_notes (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(120) NOT NULL,
  body       TEXT NULL,
  created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Step 2 — handle writes before any output

```php
<?php
$id     = (int) input('id');
$editId = (int) input('edit');

if (is_post()) {
    csrf_check();
    $action = input('action');

    if ($action === 'save') {
        $title = input('title');
        $body  = input('body');

        if ($title === '') {
            flash('danger', 'Title is required.');
        } elseif ($id > 0 && db_find('demo_notes', $id)) {
            db_update('demo_notes', ['title' => $title, 'body' => $body], 'id = ?', [$id]);
            flash('success', 'Note updated.');
        } else {
            db_insert('demo_notes', ['title' => $title, 'body' => $body, 'created_at' => now()]);
            flash('success', 'Note created.');
        }

        redirect(url('index.php?module=notes_crud'));
    }

    if ($action === 'delete') {
        db_delete('demo_notes', 'id = ?', [$id]);
        flash('success', 'Note deleted.');
        redirect(url('index.php?module=notes_crud'));
    }
}
```

Why this shape:

* **One form, two modes.** A hidden `action` says what to do; a hidden `id` of `0` means "create".
* **Validate before writing**, and re-render with a message instead of redirecting on error.
* **Redirect after every successful write** so F5 cannot duplicate a row.
* **`csrf_check()` immediately after `is_post()`** — never trust a POST without it.

#### Step 3 — read

```php
$editing = $editId > 0 ? db_find('demo_notes', $editId) : null;

$page = db_paginate(
    'SELECT * FROM demo_notes ORDER BY id DESC',
    [],
    (int) input('page', '1'),
    10
);
$notes = $page['rows'];
```

#### Step 4 — print

```php
<form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?php echo $editing ? (int) $editing['id'] : 0; ?>">

    <input class="form-control" name="title" required
           value="<?php echo e($editing ? $editing['title'] : ''); ?>">

    <button class="btn btn-primary" type="submit">
        <?php echo $editing ? 'Update' : 'Create'; ?>
    </button>
</form>
```

```php
<?php foreach ($notes as $note): ?>
    <tr>
        <td><?php echo (int) $note['id']; ?></td>
        <td><?php echo e($note['title']); ?></td>
        <td><?php echo e(str_limit($note['body'], 60)); ?></td>
        <td>
            <a class="btn btn-sm btn-outline-secondary"
               href="<?php echo e(url('index.php?module=notes_crud&edit=' . (int) $note['id'])); ?>">
                <i class="bi bi-pencil"></i>
            </a>

            <form method="post" class="d-inline" onsubmit="return confirm('Delete this note?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int) $note['id']; ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </td>
    </tr>
<?php endforeach; ?>
```

Note the two habits that prevent most bugs:

* Every **value** in HTML goes through `e()`, including values inside `value="…"`.
* Every **POST form** contains `csrf_field()`, including the tiny delete forms.

### 11.7 Search, filters and sorting

Build the `WHERE` clause and the bindings together:

```php
$term   = input('q');
$where  = [];
$params = [];

if ($term !== '') {
    $where[]  = '(title LIKE ? OR body LIKE ?)';
    $params[] = '%' . $term . '%';
    $params[] = '%' . $term . '%';
}

$sql = 'SELECT * FROM demo_notes'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY id DESC';

$page = db_paginate($sql, $params, (int) input('page', '1'), 10);
```

Then keep the search term in the pagination links:

```php
url('index.php?module=notes_crud&q=' . rawurlencode($term) . '&page=' . $i)
```

### 11.8 Schema conventions that keep things simple

* `id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY` on every table.
* `created_at DATETIME` / `updated_at DATETIME` (`now()` writes them).
* `ENGINE=InnoDB` so transactions and foreign keys work.
* `utf8mb4` / `utf8mb4_unicode_ci` everywhere.
* Foreign keys with the action you actually want, e.g. `ON DELETE SET NULL` for "audit who did it" columns and `ON DELETE RESTRICT` for "must not delete while referenced".

---

## 12. Function reference

### 12.1 `config.php`

| Function | Returns | Notes |
|---|---|---|
| `config($key = null, $default = null)` | mixed / array | No key → whole settings array |
| `set_config($key, $value)` | void | Persists in `$_SESSION['openframe']` |
| `e($value)` | string | HTML-escape with `ENT_QUOTES`, UTF-8 |
| `url($path = '')` | string | Base URL + path |

### 12.2 `helpers.php`

| Function | Returns | Notes |
|---|---|---|
| `redirect($url)` | never | `Location:` header, or a meta refresh if headers were sent |
| `csrf_token()` | string | 64 hex chars, created once per session |
| `csrf_field()` | string | `<input type="hidden" name="csrf_token" …>` |
| `csrf_check()` | void | 403 + message on mismatch |
| `flash($type, $message)` | void | Queue for the next page (`success`, `danger`, `warning`, `info`) |
| `flash_render()` | string | Prints and clears the queue; called by `header.php` |
| `input($key, $default = '')` | string | Trimmed POST value, else GET, else default |
| `is_post()` | bool | Request method is POST |
| `is_ajax()` | bool | `X-Requested-With` or `Accept: application/json` |
| `wants_json()` | bool | Alias of `is_ajax()` |
| `json_response($data, $status = 200)` | never | Sets the header, encodes UTF-8, exits |
| `abort($code = 404, $message = '')` | never | Error page, or an alert if the layout already started |
| `now()` | string | `Y-m-d H:i:s` |
| `str_limit($value, $limit = 80, $end = '…')` | string | Multibyte-safe truncation |

### 12.3 `modules.php`

| Function | Returns | Notes |
|---|---|---|
| `get_modules()` | array | All modules, cached per request, sorted by `order`, `name` |
| `get_module($slug)` | array\|null | One module's metadata |
| `enabled_modules()` | array | Only `enabled = true`; used by navbar and router |

### 12.4 `routes.php`

| Function | Returns | Notes |
|---|---|---|
| `get_routes()` | array | `slug => absolute view path` |
| `current_module()` | string | Resolved slug, `''` when unknown |
| `load_module()` | void | Requires the active view (sets `$module`) |

### 12.5 `db.php`

| Function | Returns | Example |
|---|---|---|
| `db()` | `PDO` | `db()->quote($v)` |
| `db_ident($name)` | string | `` `customers` `` — throws on invalid names |
| `db_row($sql, $params = [])` | array\|null | `db_row('SELECT * FROM t WHERE id = ?', [$id])` |
| `db_rows($sql, $params = [])` | array | `db_rows('SELECT * FROM t')` |
| `db_exec($sql, $params = [])` | int | Affected rows (also for DDL) |
| `db_column($sql, $params = [])` | mixed\|null | First column of the first row |
| `db_insert($table, array $data)` | int | New `AUTO_INCREMENT` id |
| `db_update($table, array $data, $where = '', array $whereParams = [])` | int | Affected rows |
| `db_delete($table, $where = '', array $params = [])` | int | Deleted rows |
| `db_find($table, $id, $key = 'id')` | array\|null | Row by primary key |
| `db_exists($table, $where = '', array $params = [])` | bool | `SELECT 1 … LIMIT 1` |
| `db_count($table, $where = '', array $params = [])` | int | `COUNT(*)` |
| `db_transaction(callable $callback)` | mixed | Callback result; rolls back on exception |
| `db_paginate($sql, array $params = [], $page = 1, $perPage = 20)` | array | `rows`, `total`, `page`, `per_page`, `pages` |
| `db_next_id($table)` | int | Next `AUTO_INCREMENT` (for previews only) |

> **Empty `$where`.** `db_update()` and `db_delete()` with an empty `$where` affect **every row**. This is intentional (bulk operations), but pass a condition in normal use. `db_exists()` and `db_count()` with an empty `$where` simply match the whole table.

> **`db_paginate()` and `LIMIT`.** Do not pass a query that already has `LIMIT`; the count wrapper and the appended `LIMIT` will conflict.

---

## 13. Security

### 13.1 Checklist

- [x] Escape **every** dynamic value with `e()` — text, attributes, URLs, `<title>`.
- [x] Use `?` placeholders for every value; `db_ident()` for identifiers from a fixed list.
- [x] Call `csrf_check()` in every POST branch and print `csrf_field()` in every POST form.
- [x] Cast ids and numbers with `(int)` before use.
- [x] Never trust `$_FILES`; validate size, MIME and extension, and store outside the code path when possible.
- [x] Set `'enabled' => false` rather than relying on an unlinked URL — links are not access control.
- [x] Keep `appdebug` off in production and never echo `$ex->getMessage()` to visitors.
- [x] Serve over HTTPS; use `session_set_cookie_params(['secure' => true, 'httponly' => true, 'samesite' => 'Lax'])` in production.
- [x] Add login and permissions **before** deploying anything private (recipe below).

### 13.2 Session hardening

At the top of `includes/config.php`, before `session_start()`:

```php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
```

Regenerate the id on login:

```php
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
```

### 13.3 Password storage

```php
// store
$hash = password_hash($plain, PASSWORD_DEFAULT);
db_insert('users', ['username' => $username, 'password_hash' => $hash]);

// verify
$user = db_row('SELECT * FROM users WHERE username = ?', [$username]);
if ($user && password_verify($plain, $user['password_hash'])) {
    // login ok
}

// later: re-hash when the algorithm changes
if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    db_update('users', ['password_hash' => password_hash($plain, PASSWORD_DEFAULT)], 'id = ?', [$user['id']]);
}
```

Never store plain passwords or MD5/SHA1 hashes.

### 13.4 Uploads

Full recipe in [15.1](#151-file-uploads).

---

## 14. Adding third-party libraries

OpenFrame runs with zero dependencies. When you need PDF, Excel or Word output, add the library **once** and call it from a module.

### 14.1 The three ways to install a library

| Way | When | How |
|---|---|---|
| **Composer (recommended)** | Any modern library | `composer require vendor/package`, then `require 'vendor/autoload.php'` |
| **Manual, own autoloader** | No Composer on the server, single-file libraries | Copy files to `lib/`, `require` them |
| **Manual, with bundled dependencies** | Library ships a `vendor/` folder | `require 'lib/library/vendor/autoload.php'` |

### 14.2 Install Composer (Windows, WAMP)

```powershell
# 1. download the installer
Invoke-WebRequest https://getcomposer.org/installer -OutFile composer-setup.php

# 2. install composer.phar next to PHP
& "C:\path\to\php.exe" composer-setup.php --install-dir="C:\path\to\php" --filename=composer.phar

# 3. use it from the project folder
& "C:\path\to\php.exe" "C:\path\to\php\composer.phar" require mpdf/mpdf
```

On Linux/macOS: `curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer`, then `composer require mpdf/mpdf`.

This creates:

```text
OpenFrame/
├── composer.json
├── composer.lock
└── vendor/
    └── autoload.php        ← the single file you include
```

### 14.3 Wire the autoloader in

Edit `index.php`, **after** `config.php` (so `OPENFRAME_PATH` exists) and before the layout:

```php
require __DIR__ . '/includes/config.php';

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}
```

The `is_file()` guard keeps the framework working when `vendor/` has not been installed (for example on a fresh clone).

For a library used by **one** module only, you can require it inside that module instead — but then the autoloader is loaded after output has started, which is fine for classes but not for anything that sends headers. Requiring it in `index.php` is simpler and safer.

### 14.4 mPDF — PDF export

```bash
composer require mpdf/mpdf
```

`modules/report_pdf/index.php`

```php
<?php
$rows = db_rows('SELECT * FROM customers ORDER BY name');

$html = '<h1>Customer report</h1><table border="1" cellpadding="6" width="100%">'
      . '<thead><tr><th>#</th><th>Name</th><th>Email</th></tr></thead><tbody>';

foreach ($rows as $i => $row) {
    $html .= '<tr>'
           . '<td>' . ($i + 1) . '</td>'
           . '<td>' . e($row['name']) . '</td>'
           . '<td>' . e($row['email']) . '</td>'
           . '</tr>';
}

$html .= '</tbody></table>';

/* 1. Throw away everything the layout has buffered */
while (ob_get_level() > 0) {
    ob_end_clean();
}

/* 2. Build the PDF */
$mpdf = new \Mpdf\Mpdf([
    'tempDir' => OPENFRAME_PATH . '/storage/tmp',   // must be writable
    'format'  => 'A4',
]);

$mpdf->SetTitle('Customer report');
$mpdf->WriteHTML($html);

/* 3. Send it to the browser */
$mpdf->Output('customers.pdf', \Mpdf\Output\Destination::INLINE);
exit;   // do not let footer.php print HTML after the PDF
```

**Why `ob_end_clean()` matters.** `index.php` starts output buffering so the router can send a 404 after the navbar. A PDF is binary: the HTML layout, the navbar and the footer must be discarded first. Clear every buffer, then output the file and `exit`.

**Arabic and RTL:**

```php
$mpdf = new \Mpdf\Mpdf([
    'mode'              => 'utf-8',
    'format'            => 'A4',
    'autoScriptToLang'  => true,
    'autoLangToFont'    => true,
    'tempDir'           => OPENFRAME_PATH . '/storage/tmp',
]);

$mpdf->SetDirectionality('rtl');          // right-to-left layout
$mpdf->WriteHTML('<html dir="rtl"><body>مرحبا بكم</body></html>');
```

mPDF bundles fonts that cover Arabic, but for a specific typeface add it to `config/fonts` (see the mPDF manual) and select it with `$mpdf->SetFont('name')`. Avoid emoji in PDFs: they need a colour-emoji font that mPDF cannot embed.

**Create the temp folder once:**

```php
if (!is_dir(OPENFRAME_PATH . '/storage/tmp')) {
    mkdir(OPENFRAME_PATH . '/storage/tmp', 0775, true);
}
```

### 14.5 PhpSpreadsheet — Excel export

```bash
composer require phpoffice/phpspreadsheet
```

```php
<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$rows = db_rows('SELECT id, name, email, created_at FROM customers ORDER BY name');

$book  = new Spreadsheet();
$sheet = $book->getActiveSheet();
$sheet->setTitle('Customers');

/* Header row */
$headers = ['ID', 'Name', 'Email', 'Created'];
$sheet->fromArray($headers, null, 'A1');
$sheet->getStyle('A1:D1')->getFont()->setBold(true);
$sheet->getStyle('A1:D1')->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FF1F3864');
$sheet->getStyle('A1:D1')->getFont()->getColor()->setARGB('FFFFFFFF');

/* Data */
$rowNumber = 2;

foreach ($rows as $row) {
    $sheet->setCellValueExplicit('A' . $rowNumber, $row['id'], DataType::TYPE_NUMERIC);
    $sheet->setCellValueExplicit('B' . $rowNumber, $row['name'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('C' . $rowNumber, $row['email'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('D' . $rowNumber, $row['created_at'], DataType::TYPE_STRING);
    $rowNumber++;
}

/* Widths + freeze panes */
foreach (['A' => 8, 'B' => 30, 'C' => 34, 'D' => 20] as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}
$sheet->freezePane('A2');
$sheet->setAutoFilter('A1:D' . max(1, $rowNumber - 1));

/* Send */
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="customers-' . date('Ymd-His') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($book);
$writer->save('php://output');

$book->disconnectWorksheets();   // free memory
exit;
```

**Notes**

* `setCellValueExplicit(..., DataType::TYPE_STRING)` protects against **CSV/Excel formula injection**: a value starting with `=`, `+`, `-` or `@` is stored as text, not executed.
* Arabic text is stored correctly as UTF-8; no extra font is needed in the file.
* For very large exports use `\PhpOffice\PhpSpreadsheet\Reader\…`/`Writer\…` streaming APIs, or write CSV instead — the in-memory model uses roughly 1 KB per cell.
* `.xlsx` needs the `zip` and `xml` extensions (both enabled by default in WAMP).

### 14.6 PHPWord — Word export

```bash
composer require phpoffice/phpword
```

```php
<?php
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

$rows = db_rows('SELECT name, email, created_at FROM customers ORDER BY name');

$document = new PhpWord();
$section  = $document->addSection();

$section->addText('Customer report', ['bold' => true, 'size' => 18], ['alignment' => Jc::CENTER]);
$section->addText('Generated ' . now(), ['size' => 9, 'color' => '666666']);
$section->addTextBreak();

$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'BFBFBF']);

$table->addRow();
foreach (['Name', 'Email', 'Created'] as $heading) {
    $table->addCell(3000, ['bgColor' => '1F3864'])->addText(
        $heading,
        ['bold' => true, 'color' => 'FFFFFF']
    );
}

foreach ($rows as $row) {
    $table->addRow();
    $table->addCell(3000)->addText($row['name']);
    $table->addCell(3500)->addText($row['email']);
    $table->addCell(2500)->addText($row['created_at']);
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="customers-' . date('Ymd') . '.docx"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($document, 'Word2007');
$writer->save('php://output');
exit;
```

**RTL text in PHPWord.** Arabic characters are written correctly, but paragraph direction is a paragraph property. Set `bidi` on the paragraph style (and `rtl` on the run) for a proper right-to-left layout:

```php
$section->addText('مرحبا بكم', ['rtl' => true], ['bidi' => true, 'alignment' => Jc::RIGHT]);
```

### 14.7 PHPExcel (legacy)

PHPExcel is **abandoned** and does not run on PHP 8. Use PhpSpreadsheet, which is its official successor and supports the same concepts (`getActiveSheet()`, `setCellValue()`, `IOFactory::createWriter()`).

If you must maintain an old application:

```bash
composer require phpoffice/phpexcel   # last release 1.8.2, PHP 5.x era
```

Expect deprecation warnings and missing PHP 8 support. Migrate when you can — the mapping is mostly mechanical (`PHPExcel` → `Spreadsheet`, `PHPExcel_IOFactory` → `IOFactory`).

### 14.8 Manual install without Composer

```text
OpenFrame/
└── lib/
    └── mpdf/
        ├── autoload.php
        └── ...
```

```php
// index.php, after config.php
$manual = __DIR__ . '/lib/mpdf/autoload.php';
if (is_file($manual)) {
    require $manual;
}
```

This works for mPDF (it ships its own autoloader and dependencies). PhpSpreadsheet and PHPWord depend on several packages (ZipArchive wrapper, XML parser, etc.); installing them by hand is fragile — use Composer.

### 14.9 Composer troubleshooting

| Symptom | Fix |
|---|---|
| `Class "Mpdf\Mpdf" not found` | The autoloader is not required, or `vendor/` is missing. Check the `is_file()` guard and run `composer install`. |
| `composer` is not recognised | Call it through PHP: `php composer.phar require …` |
| `Allowed memory size exhausted` | Raise `memory_limit` to `512M` for the export, or stream the file. |
| PDF is blank / garbage characters | Output was sent before the PDF. `ob_end_clean()` + `exit`. |
| `tempDir is not writable` (mPDF) | Create `storage/tmp` and give the web user write permission. |
| Arabic shows as `????` | The database connection is not `utf8mb4`, or the HTML lacks `<meta charset="utf-8">`. |
| Composer asks for a PHP extension | Enable it in `php.ini` (`extension=zip`, `extension=gd`, `extension=mbstring`) and restart Apache. |

---

## 15. Recipes: uploads, login, languages

These are patterns, not core features — they show how to extend OpenFrame without changing its five infrastructure files.

### 15.1 File uploads

**Form** — `enctype` is mandatory:

```php
<form method="post" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
    <button class="btn btn-primary">Upload</button>
</form>
```

**Handler** — validate before moving, and never trust the client's filename:

```php
if (is_post()) {
    csrf_check();

    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        flash('danger', 'Choose a file to upload.');
        redirect(url('index.php?module=documents'));
    }

    $file = $_FILES['document'];

    /* 1. size (5 MB) */
    if ($file['size'] > 5 * 1024 * 1024) {
        flash('danger', 'The file is larger than 5 MB.');
        redirect(url('index.php?module=documents'));
    }

    /* 2. real MIME type, not the browser's claim */
    $allowed = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!isset($allowed[$mime])) {
        flash('danger', 'Only PDF, JPG, PNG or WebP files are accepted.');
        redirect(url('index.php?module=documents'));
    }

    /* 3. our own name + dated folder */
    $relative = 'uploads/' . date('Y/m/d');
    $absolute = OPENFRAME_PATH . '/' . $relative;

    if (!is_dir($absolute)) {
        mkdir($absolute, 0775, true);
    }

    $name = 'doc-' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];

    if (!move_uploaded_file($file['tmp_name'], $absolute . '/' . $name)) {
        flash('danger', 'The file could not be saved.');
        redirect(url('index.php?module=documents'));
    }

    db_insert('documents', [
        'path'       => $relative . '/' . $name,
        'mime'       => $mime,
        'size'       => (int) $file['size'],
        'created_at' => now(),
    ]);

    flash('success', 'File uploaded.');
    redirect(url('index.php?module=documents'));
}
```

**Protect the folder.** `uploads/.htaccess` (Apache):

```apache
php_flag engine off
<FilesMatch "\.(php|phtml|phar|cgi|pl|py|sh)$">
    Require all denied
</FilesMatch>
```

Nginx equivalent:

```nginx
location ^~ /uploads/ {
    location ~ \.(php|phtml|phar)$ { deny all; }
}
```

Serving files through PHP (so you can check permissions) instead of linking directly:

```php
$row  = db_find('documents', (int) input('id'));
$path = OPENFRAME_PATH . '/' . $row['path'];

if (!$row || !is_file($path)) {
    abort(404);
}

header('Content-Type: ' . $row['mime']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . basename($row['path']) . '"');
readfile($path);
exit;
```

### 15.2 Minimal login and permissions

**Table**

```sql
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(60)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  level         VARCHAR(20)  NOT NULL DEFAULT 'user',
  created_at    DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**`modules/login/index.php`**

```php
<?php
if (is_post()) {
    csrf_check();

    $username = input('username');
    $password = input('password');

    $user = db_row('SELECT * FROM users WHERE username = ?', [$username]);

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = (int) $user['id'];
        $_SESSION['user_level'] = $user['level'];
        flash('success', 'Welcome back.');
        redirect(url('index.php?module=home'));
    }

    flash('danger', 'Wrong username or password.');
    redirect(url('index.php?module=login'));
}
?>

<form method="post" class="mx-auto" style="max-width: 380px">
    <?php echo csrf_field(); ?>
    <h1 class="h4 mb-3">Sign in</h1>
    <input class="form-control mb-2" name="username" placeholder="Username" required autofocus>
    <input class="form-control mb-3" name="password" type="password" placeholder="Password" required>
    <button class="btn btn-primary w-100">Sign in</button>
</form>
```

**Guard helper** — add to `helpers.php`:

```php
function current_user()
{
    static $user = false;

    if ($user !== false) {
        return $user;
    }

    if (empty($_SESSION['user_id'])) {
        return $user = null;
    }

    return $user = db_find('users', (int) $_SESSION['user_id']);
}

function require_login()
{
    if (!current_user()) {
        redirect(url('index.php?module=login'));
    }
}

function require_level($level)
{
    require_login();

    $user = current_user();

    if ($level === 'admin' && $user['level'] !== 'admin') {
        abort(403, 'You do not have permission to open this page.');
    }
}
```

**Use it at the top of a module** — before any output:

```php
require_level('admin');
```

Remember: hiding a navbar link is not security. A module that must not be public needs `require_login()` / `require_level()` inside it.

### 15.3 Multilingual & RTL

**1. Language files** — `lang/en.php` and `lang/ar.php`, each returning an array:

```php
<?php
return [
    'app.title'      => 'Customer records',
    'nav.home'       => 'Home',
    'common.save'    => 'Save',
];
```

**2. Loader** — `includes/lang.php`:

```php
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$available = ['en', 'ar'];
$lang      = isset($_GET['lang']) ? $_GET['lang'] : ($_SESSION['lang'] ?? 'en');

if (!in_array($lang, $available, true)) {
    $lang = 'en';
}

$_SESSION['lang'] = $lang;

$GLOBALS['lang'] = require OPENFRAME_PATH . '/lang/' . $lang . '.php';

function lang($key, $default = '')
{
    return isset($GLOBALS['lang'][$key]) ? $GLOBALS['lang'][$key] : ($default !== '' ? $default : $key);
}
```

Require it from `index.php` after `config.php`.

**3. Direction** — in `header.php`:

```php
<?php
$isRtl = (($_SESSION['lang'] ?? 'en') === 'ar');
?>
<!DOCTYPE html>
<html lang="<?php echo e($_SESSION['lang'] ?? 'en'); ?>" dir="<?php echo $isRtl ? 'rtl' : 'ltr'; ?>">
```

**4. Bootstrap RTL stylesheet** — Bootstrap publishes an RTL build. Add `assets/css/bootstrap.rtl.min.css` and choose it:

```php
<link rel="stylesheet" href="<?php echo e(url('assets/css/' . ($isRtl ? 'bootstrap.rtl.min.css' : 'bootstrap.min.css'))); ?>">
```

**5. A language switch** — a link that keeps the current page:

```php
<a href="<?php echo e(url('index.php?module=' . rawurlencode($activeSlug) . '&lang=ar')); ?>">العربية</a>
```

**6. Translating** — replace every hard-coded string with `lang('key')`, and keep the keys identical in both files. Missing keys fall back to the key itself, so nothing crashes.

---

## 16. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Blank page, no error | `display_errors` off and a fatal error | Look at the PHP error log; temporarily set `display_errors=1`. |
| `404 — Module not found` | Folder missing `info.php`, or `'enabled' => false`, or slug typo | Check the folder name and `info.php`; slugs allow only `a-z 0-9 _ -`. |
| Module shows but URL 404s | Folder name has spaces/capitals, so the sanitised slug does not match | Rename the folder to lowercase letters, digits, `_` or `-`. |
| CSS missing / unstyled page | Assets loaded from the wrong base URL | Use `url('assets/css/bootstrap.min.css')`, never a hard-coded `/assets/...`. |
| 404 but the page looks fine | Status was already sent before `http_response_code()` | Keep all header work before output, or rely on the existing `ob_start()`. |
| `Database connection failed` | MySQL stopped, wrong port, wrong credentials | Start MySQL; check `DB_*`; on Windows use `127.0.0.1`, not `localhost`. |
| Arabic shows as `????` | Connection charset not `utf8mb4` | Set `DB_CHARSET` to `utf8mb4`; check the table collation. |
| `Invalid SQL identifier` | `db_insert`/`db_update` key contains a space or punctuation | Use a real column name; map form fields explicitly. |
| `db_paginate` returns 0 pages | The query already contains `LIMIT` | Remove `LIMIT` from the query you pass in. |
| PDF/Excel download is corrupt | The HTML layout was included in the file | `while (ob_get_level()) ob_end_clean();`, then output and `exit`. |
| Flash message never appears | `flash()` then no redirect, or session not started | Redirect after flashing; `config.php` starts the session for you. |
| CSRF 403 on every form | Session cookie blocked, or the token is stale | Check cookie settings; make sure `csrf_field()` is inside the form. |
| Changes to `info.php` not visible | PHP opcache | Restart Apache / reload PHP-FPM. |
| Upload fails silently | `upload_max_filesize` / `post_max_size` too small | Raise both in `php.ini`, restart the server. |

---

## 17. Deployment checklist

1. **Turn debug off** — `'appdebug' => false`; `display_errors=0`, `log_errors=1`.
2. **Use a dedicated database user** with only the privileges the app needs (no `GRANT`, no `DROP` unless required).
3. **Move credentials** out of version control if the project is public — load them from environment variables before `config.php`.
4. **HTTPS + secure cookies** (see 13.2), and `session_regenerate_id(true)` on login.
5. **Protect `uploads/`** with the `.htaccess`/Nginx rule in 15.1.
6. **Set permissions** — writable: `uploads/`, `storage/tmp/`; everything else read-only for the web user.
7. **Run `composer install --no-dev --optimize-autoloader`** if you use libraries.
8. **Back up** the database and `uploads/` on a schedule.
9. **Test 404s, disabled modules, and a failed DB connection** before going live.
10. **Keep the credit** — the "Powered by OpenFrame" line in the navbar and the footer credit are part of the license.

Apache virtual host example:

```apache
<VirtualHost *:80>
    ServerName openframe.local
    DocumentRoot "D:/wamp64/www/openframe"

    <Directory "D:/wamp64/www/openframe">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  "logs/openframe-error.log"
    CustomLog "logs/openframe-access.log" combined
</VirtualHost>
```

Nginx example:

```nginx
server {
    listen 80;
    server_name openframe.local;
    root /var/www/openframe;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ^~ /uploads/ {
        location ~ \.(php|phtml|phar)$ { deny all; }
    }
}
```

---

## 18. License and credits

OpenFrame is free to use and modify, including commercially. The **"Powered by OpenFrame"** badge in the navbar and the footer credit identify the framework; please keep them.

* **Framework:** OpenFrame 1.0.0
* **UI:** [Bootstrap 5.3](https://getbootstrap.com/) and [Bootstrap Icons](https://icons.getbootstrap.com/), both bundled locally
* **Author:** OpenFrame
* **Repository:** <https://github.com/mohamed-elsharkawy/OpenFrame>

### Documentation

| File | Audience |
|---|---|
| `README.md` (this file) | Developers: architecture, API, recipes |
| `docs/OpenFrame-User-Guide.docx` | Step-by-step guide for newcomers |
| `docs/OpenFrame-User-Guide.md` | Same guide as plain text |
| `docs/examples/notes_crud/` | Working CRUD module + `schema.sql` |

### Changelog

**1.0.0**

* Module auto-discovery, routing, Bootstrap layout.
* `config()` / `set_config()` session settings, `e()`, `url()`.
* PDO data layer with full CRUD, pagination, transactions.
* CSRF, flash messages, redirects, input handling, JSON responses.
* Example CRUD module and complete documentation.
