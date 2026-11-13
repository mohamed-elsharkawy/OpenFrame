# OpenFrame

**A small, module-driven PHP framework.** One `index.php`, one folder per screen, Bootstrap 5 out of the box — and nothing to learn beyond plain PHP.

OpenFrame is deliberately tiny. There is no templating language, no ORM, no dependency injection and no build step. A module is a folder with an `info.php` and an `index.php`. A database row is a `db_insert()` call. If you can write PHP, you can read this entire framework in a short sitting.

```text
index.php?module=home  →  includes/config.php     (settings + session)
                       →  includes/helpers.php    (redirect, CSRF, flash, auth, OTP)
                       →  includes/db.php         (PDO + CRUD helpers)
                       →  includes/modules.php    (auto-discovery)
                       →  includes/routes.php     (routing + 404)
                       →  includes/header.php     (<head>, navbar, sidemenu, <main>)
                       →  modules/<slug>/index.php
                       →  includes/footer.php     (footer, scripts, </html>)
```

**Documentation**

| Document | What it covers |
|---|---|
| [README.md](README.md) | Install, structure, modules, actions, function index |
| [docs/DEVELOPER.md](docs/DEVELOPER.md) | Full function reference with examples |
| [docs/OpenFrame-User-Guide.md](docs/OpenFrame-User-Guide.md) | Tutorial-style walkthrough |
| [docs/examples/notes_crud](docs/examples/notes_crud) | Ready-to-copy CRUD module |

---

## Table of contents

1. [Features](#1-features)
2. [Requirements](#2-requirements)
3. [Quick start](#3-quick-start)
4. [Directory structure](#4-directory-structure)
5. [Request lifecycle](#5-request-lifecycle)
6. [Bundled modules](#6-bundled-modules)
7. [Create a new module](#7-create-a-new-module)
8. [Create an action inside a module](#8-create-an-action-inside-a-module)
9. [Database and CRUD](#9-database-and-crud)
10. [Function index](#10-function-index)
11. [Authentication, roles and OTP](#11-authentication-roles-and-otp)
12. [Languages](#12-languages)
13. [Security](#13-security)
14. [License](#14-license)

---

## 1. Features

| Feature | How it works |
|---|---|
| **Auto-discovered modules** | Every folder in `modules/` with an `info.php` becomes a page, a route and (optionally) a navbar link. |
| **Zero-config URLs** | `index.php?module=notes` — works in `/`, in a sub-folder, with or without URL rewriting. |
| **PDO data layer** | Lazy connection, prepared statements, table prefix (`opf_`), and CRUD helpers. |
| **Auth + roles** | Login, logout, `superadmin` / `admin` / `user` levels. |
| **TOTP for admins** | Superadmin and admin must scan a QR code and enter a 6-digit code. Regular users skip OTP. QR codes are generated **offline**. |
| **English + Arabic** | `lang/en.php` and `lang/ar.php`. RTL layout, navbar and sidemenu swap automatically. |
| **Bootstrap 5.3 bundled** | CSS, JS and Bootstrap Icons ship inside `assets/` — the app works offline. |
| **No build step** | No Composer required to run. No Node.js. |

**Design rules**

1. A module may not need the database — a module can be one HTML file.
2. Output is always escaped at the edge with `e()`, never sanitised on the way in.
3. SQL values always travel through placeholders. Identifiers always go through `db_ident()`.
4. Table names passed to CRUD helpers are unprefixed (`users`); the prefix is applied automatically.

---

## 2. Requirements

| Component | Minimum | Notes |
|---|---|---|
| PHP | 8.0 | Tested on PHP 8.3. |
| Extensions | `pdo_mysql`, `mbstring`, `fileinfo`, `openssl` | `openssl` is used by `encrypt_password()` / `decrypt_password()`. |
| Database | MySQL 5.7+ / MariaDB 10.3+ | Optional for pages that never call `db()`. |
| Web server | Apache 2.4 / Nginx / PHP built-in server | No rewrite rules needed. |
| Browser | Any modern browser | Bootstrap 5.3. |

There is **no Node.js step**. You never need npm, Vite or a bundler.

---

## 3. Quick start

### 3.1 Place the files

Copy the project into your web root:

```text
D:\wamp64\www\openframe      (WAMP)
/var/www/html/openframe      (Linux)
```

### 3.2 Configure the database

Edit `includes/config.php`:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'openframe');
define('DB_USER', 'root');
define('DB_PASS', 'your-password');
define('DB_CHARSET', 'utf8mb4');
define('DB_PREFIX', 'opf_');
```

Create the database and import the schema:

```bash
mysql -h 127.0.0.1 -u root -p -e "CREATE DATABASE IF NOT EXISTS openframe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -h 127.0.0.1 -u root -p openframe < schema.sql
```

`schema.sql` includes the full reference data: **250 countries** and **~209,000
populated places**. Country names come from
[umpirsky/country-list](https://github.com/umpirsky/country-list) (CLDR); city
names come from [GeoNames](https://www.geonames.org/) `cities500`
([CC BY 4.0](https://creativecommons.org/licenses/by/4.0/)). Arabic city names
are taken from the GeoNames alternate names when a standard-Arabic name exists,
otherwise the English name is used.

Change `ENCRYPTION_KEY` in `includes/config.php` before going to production.

### 3.3 Open it

```text
http://localhost/openframe/
```

Unauthenticated visitors are sent to the login module.

### 3.4 Default accounts

All default passwords are `password` (encrypted with `encrypt_password()`).

| Username | Level | OTP |
|---|---|---|
| `superadmin` | superadmin | Required (QR on first sign-in) |
| `admin` | admin | Required (QR on first sign-in) |
| `user` | user | Not used |

**Change these passwords immediately** on any shared or production server.

---

## 4. Directory structure

```text
openframe/
├── index.php                 Front controller
├── schema.sql                Database schema (prefixed tables)
├── README.md
├── includes/
│   ├── config.php            Settings, session, DB constants, config()/e()/url()
│   ├── helpers.php           Redirect, CSRF, flash, input, auth, OTP, i18n
│   ├── db.php                PDO connection + CRUD helpers
│   ├── modules.php           Module discovery
│   ├── routes.php            Routing + load_module()
│   ├── header.php            <head>, navbar, sidemenu, <main>
│   ├── footer.php            Footer, Bootstrap JS
│   ├── navbar.php            English / LTR navbar
│   ├── navbar-rtl.php        Arabic / RTL navbar
│   ├── sidemenu.php          English / LTR sidemenu
│   ├── sidemenu-rtl.php      Arabic / RTL sidemenu
│   └── qrcode.php            Offline QR encoder (TOTP setup)
├── modules/
│   ├── home/                 Landing page
│   ├── login/                Sign in / sign out
│   ├── otp/                  TOTP challenge (admins)
│   ├── profile/              Current user profile
│   └── users/                User CRUD + OTP reset (superadmin)
├── lang/
│   ├── en.php
│   └── ar.php
├── assets/                   Bootstrap, icons, images (offline)
└── docs/
    ├── DEVELOPER.md          Full function reference
    ├── OpenFrame-User-Guide.md
    └── examples/notes_crud/  Copy-paste CRUD module
```

Only `modules/` grows as you build. Everything else is infrastructure you configure once.

---

## 5. Request lifecycle

`index.php` loads the framework in this order:

1. Define `OPENFRAME` and start output buffering.
2. `config.php` — session, DB constants, `config()` / `e()` / `url()`.
3. `helpers.php` — request, CSRF, auth, OTP, language.
4. `db.php` — PDO (connection opens on first query).
5. `modules.php` / `routes.php` — discover modules and build routes.
6. Apply `?lang=`, load translations, ensure OTP columns exist.
7. TOTP gate (admin/superadmin) or landing-page redirect (guests).
8. `header.php` — layout, navbar, sidemenu, flash messages.
9. `load_module()` — `require modules/<slug>/index.php`.
10. `footer.php` — scripts and closing tags.

Unknown or disabled modules return HTTP 404.

---

## 6. Bundled modules

| Slug | Menu | Who uses it |
|---|---|---|
| `home` | Yes | Logged-in users — lists installed modules. |
| `login` | Hidden (`menu => false`) | Guests. `?action=logout` signs out. |
| `otp` | Hidden | Superadmin / admin after password, before the app. |
| `profile` | Yes | Any logged-in user. |
| `users` | Yes | Superadmin and admin. Superadmin can reset any user's OTP. |

Set `'menu' => false` in `info.php` to keep a module routable but out of the navbar and sidemenu.

---

## 7. Create a new module

A module is **one folder** under `modules/`. The folder name is the URL slug.

```text
modules/hello/
├── info.php      required — metadata
└── index.php     required — the page
```

### 7.1 `info.php`

Must `return` an array:

```php
<?php
return [
    'name_en'        => 'Hello',
    'name_ar'        => 'مرحبا',
    'name'           => 'Hello',
    'description_en' => 'My first module.',
    'description_ar' => 'وحدتي الأولى.',
    'description'    => 'My first module.',
    'version'        => '1.0.0',
    'icon'           => 'bi-emoji-smile',   // Bootstrap Icons class
    'enabled'        => true,               // false → hidden + 404
    'order'          => 10,                 // navbar sort (ascending)
    'group_en'       => '',                 // empty = ungrouped nav item
    'group_ar'       => '',
    'group'          => '',
    'menu'           => true,               // false = no navbar/sidemenu link
    'author'         => 'You',
];
```

| Key | Default | Purpose |
|---|---|---|
| `name` / `name_en` / `name_ar` | Folder name | Label. Arabic session uses `name_ar`. |
| `description` / `_en` / `_ar` | `''` | Shown on the Home module. |
| `icon` | `bi-puzzle` | Any [Bootstrap Icons](https://icons.getbootstrap.com/) class. |
| `enabled` | `true` | `false` hides it and makes the route 404. |
| `order` | `100` | Ascending. Ties sort by name. |
| `group` / `group_en` / `group_ar` | `''` | Dropdown group in the navbar. Empty = top-level item. |
| `menu` | `true` | `false` keeps the route but omits the menu link. |
| *(anything else)* | — | Copied into `$module` for your own use. |

`slug` and `folder` are always overwritten with the real folder name.

### 7.2 `index.php`

Put this at the top of every module view:

```php
<?php
require_framework();   // blocks direct /modules/hello/index.php access
require_login();       // optional — send guests to login
```

Then write HTML (and PHP where needed):

```php
<h1 class="h3"><?php echo e($module['name'] ?? 'Hello'); ?></h1>
<p class="text-muted"><?php echo e(lang('common.welcome', 'Hello, OpenFrame.')); ?></p>
```

Reload. **Hello** is already in the navbar at `index.php?module=hello`. No registry file, no cache, no command.

### 7.3 Restrict by role

```php
require_any_level(['superadmin', 'admin']);   // 403 otherwise
// or
require_level('superadmin');
```

### 7.4 Group it in the menu

```php
'group_en' => 'System Configuration',
'group_ar' => 'إعدادات النظام',
'group'    => 'System Configuration',
```

Ungrouped modules sit as plain links. Grouped modules become a dropdown.

---

## 8. Create an action inside a module

OpenFrame has **no separate controllers**. One module file handles GET (the screen) and POST (the work). Distinguish operations with a hidden `action` field (POST) or a query parameter (GET).

### 8.1 POST actions (preferred for writes)

```php
<?php
require_framework();
require_login();

if (is_post()) {
    csrf_check();
    $action = input('action');

    if ($action === 'save') {
        $title = input('title');
        if ($title === '') {
            flash('danger', lang('common.required', 'Title is required.'));
        } else {
            db_insert('notes', [
                'title'      => $title,
                'created_at' => now(),
            ]);
            flash('success', lang('msg.created', 'Created.'));
        }
        redirect(url('index.php?module=notes'));
    }

    if ($action === 'delete') {
        db_delete('notes', 'id = ?', [(int) input('id')]);
        flash('success', lang('msg.deleted', 'Deleted.'));
        redirect(url('index.php?module=notes'));
    }
}
```

Form:

```html
<form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="save">
    <input class="form-control" name="title" required>
    <button class="btn btn-primary" type="submit">Save</button>
</form>
```

Delete button (second form on the same page):

```html
<form method="post" class="d-inline" onsubmit="return confirm('Delete?');">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
</form>
```

Always **redirect after POST** (Post/Redirect/Get) so a browser refresh cannot resubmit.

### 8.2 GET actions (safe, read-only)

Use query parameters for screens, not for writes:

```text
index.php?module=users&edit=3
index.php?module=login&action=logout
```

```php
$editId = (int) input('edit');
$editing = $editId > 0 ? db_find('users', $editId) : null;

if (input('action') === 'logout') {
    logout_user();
}
```

`input()` reads POST first, then GET, and always returns a trimmed string.

### 8.3 Action checklist

1. `is_post()` + `csrf_check()` for every write.
2. Hidden `name="action"` to choose the branch (`save`, `delete`, `reset_otp`, …).
3. Validate, write with CRUD helpers, `flash()`, `redirect()`.
4. GET parameters (`edit`, `page`) only change what is displayed.
5. Escape every value you print: `echo e($row['title']);`.

See `modules/users/index.php` and `docs/examples/notes_crud/index.php` for complete patterns.

---

## 9. Database and CRUD

CRUD helpers live in `includes/db.php`. Pass the **logical** table name (`users`). They prepend `DB_PREFIX` (`opf_` by default).

| Function | Purpose |
|---|---|
| `db_insert($table, $data)` | Insert a row. Returns the new id. |
| `db_update($table, $data, $where, $params)` | Update matching rows. Returns affected count. |
| `db_delete($table, $where, $params)` | Delete matching rows. Returns deleted count. |
| `db_find($table, $id, $key = 'id')` | One row by primary key (or another column). |
| `db_exists($table, $where, $params)` | `true` if at least one row matches. |
| `db_count($table, $where, $params)` | Row count. |
| `db_paginate($sql, $params, $page, $perPage)` | `{ rows, total, page, per_page, pages }`. |
| `db_transaction($callback)` | Commit or roll back as a unit. |
| `db_next_id($table)` | Preview the next AUTO_INCREMENT value. |

Raw SQL helpers (you write the table name yourself — use `db_table()`):

```php
$user = db_row('SELECT * FROM ' . db_table('users') . ' WHERE username = ?', [$username]);
$rows = db_rows('SELECT * FROM ' . db_table('users') . ' ORDER BY full_name');
$n    = db_column('SELECT COUNT(*) FROM ' . db_table('users'));
db_exec('UPDATE ' . db_table('users') . ' SET full_name = ? WHERE id = ?', [$name, $id]);
```

**Never** concatenate user input into SQL. Values go in the `$params` array.

Empty `$where` on `db_update` / `db_delete` affects **every row**. Always pass a condition.

Full examples: [docs/DEVELOPER.md](docs/DEVELOPER.md#crud--includesdbphp).

---

## 10. Function index

Every public function in the application. Signatures and examples: [docs/DEVELOPER.md](docs/DEVELOPER.md).

### 10.1 `includes/config.php`

| Function | Returns | Role |
|---|---|---|
| `config($key = null, $default = null)` | mixed | Read session settings. No key → whole array. |
| `set_config($key, $value)` | void | Change a setting for this session. |
| `e($value)` | string | HTML-escape for output. |
| `url($path = '')` | string | App URL. `url('index.php?module=home')` → `/?module=home`. |

### 10.2 `includes/helpers.php`

**Request and flow**

| Function | Returns | Role |
|---|---|---|
| `redirect($url)` | never | `Location` header (or meta refresh) and `exit`. |
| `input($key, $default = '')` | string | Trimmed POST, else GET, else default. |
| `is_post()` | bool | Request method is POST. |
| `is_ajax()` | bool | `X-Requested-With` or `Accept: application/json`. |
| `wants_json()` | bool | Alias of `is_ajax()`. |
| `json_response($data, $status = 200)` | never | JSON body and `exit`. |
| `abort($code = 404, $message = '')` | never | HTTP error page and `exit`. |
| `now()` | string | `Y-m-d H:i:s`. |
| `str_limit($value, $limit = 80, $end = '…')` | string | Truncate with ellipsis. |
| `require_framework()` | void | Block direct access to `modules/*/index.php`. |

**CSRF and flash**

| Function | Returns | Role |
|---|---|---|
| `csrf_token()` | string | Session token (created on first use). |
| `csrf_field()` | string | Hidden `csrf_token` input. |
| `csrf_check()` | void | 403 if the token is missing or wrong. |
| `flash($type, $message)` | void | Queue a Bootstrap alert (`success`, `danger`, `warning`, `info`). |
| `flash_render()` | string | Print and clear flashes (called from `header.php`). |

**Language**

| Function | Returns | Role |
|---|---|---|
| `get_available_languages()` | array | Codes from `lang/*.php`. |
| `load_lang()` | array | Load `en` / `ar` (or `?lang=`). Cached per request. |
| `lang($key, $default = '')` | string | Translate a key. |

**Auth**

| Function | Returns | Role |
|---|---|---|
| `current_user()` | array\|null | Row from `opf_users`, or `null`. Cached. |
| `require_login()` | void | Redirect guests to the landing module. |
| `require_level($level)` | void | Exact level or 403. |
| `require_any_level(array $levels)` | void | One of the levels or 403. |
| `logout_user()` | never | Clear session and redirect to landing. |

**TOTP**

| Function | Returns | Role |
|---|---|---|
| `otp_generate_secret($length = 16)` | string | Random base32 secret. |
| `otp_base32_decode($secret)` | string | Binary key. |
| `otp_generate_code($secret, $time = null)` | string | 6-digit TOTP (30 s). |
| `otp_verify($secret, $code, $window = 1)` | bool | Accept ±`$window` time steps. |
| `otp_provisioning_uri($secret, $username, $issuer)` | string | `otpauth://totp/…` URI. |
| `otp_is_privileged($user)` | bool | `superadmin` or `admin`. |
| `otp_finish_login(array $user)` | void | Set session after password (users) or OTP (admins). |
| `otp_qr_html($data, $modulePx = 6)` | string | Offline HTML QR table. |

**Encryption**

| Function | Returns | Role |
|---|---|---|
| `encrypt_password($plain)` | string | AES-256-CBC, base64 (IV + ciphertext). |
| `decrypt_password($encrypted)` | string\|false | Reverse of `encrypt_password()`. |

**Other**

| Function | Returns | Role |
|---|---|---|
| `load_modules()` | array | Alias of `get_modules()`. |

### 10.3 `includes/db.php`

| Function | Returns | Role |
|---|---|---|
| `db()` | PDO | Shared connection (lazy). |
| `db_ident($name)` | string | Backtick-quoted identifier. |
| `db_table($name)` | string | `opf_` + name, quoted. |
| `db_row($sql, $params = [])` | array\|null | One row. |
| `db_rows($sql, $params = [])` | array | All rows. |
| `db_exec($sql, $params = [])` | int | Affected rows. |
| `db_column($sql, $params = [])` | mixed\|null | First column of first row. |
| `db_insert($table, array $data)` | int | New id. |
| `db_update($table, array $data, $where, $params)` | int | Affected rows. |
| `db_delete($table, $where, $params)` | int | Deleted rows. |
| `db_find($table, $id, $key = 'id')` | array\|null | Row by key. |
| `db_exists($table, $where, $params)` | bool | Any match? |
| `db_count($table, $where, $params)` | int | Count. |
| `db_transaction(callable $callback)` | mixed | Callback result. |
| `db_paginate($sql, $params, $page, $perPage)` | array | Page of rows. |
| `db_next_id($table)` | int | Next AUTO_INCREMENT (preview). |

### 10.4 `includes/modules.php`

| Function | Returns | Role |
|---|---|---|
| `get_modules()` | array | All modules, sorted. Cached. |
| `get_module($slug)` | array\|null | One module. |
| `enabled_modules()` | array | `enabled => true` only. |
| `get_modules_by_group()` | array | `{ ungrouped, groups }` for navbar/sidemenu. |

### 10.5 `includes/routes.php`

| Function | Returns | Role |
|---|---|---|
| `get_routes()` | array | `slug => index.php path`. |
| `current_module()` | string | Active slug, or `''` if invalid. |
| `load_module()` | void | Require the view, or print 404. |

### 10.6 `includes/qrcode.php` (public)

Used by OTP setup. No CDN, no GD, no Composer.

| Function | Returns | Role |
|---|---|---|
| `qrcode_matrix($text)` | array | 0/1 modules `[row][col]`. |
| `qrcode_svg($text, $modulePx = 6, $margin = 4)` | string | SVG markup. |
| `qrcode_html($text, $modulePx = 6, $margin = 4)` | string | High-contrast HTML table. |

Internal `qr_*` helpers implement ECC, masks and drawing. Application code should call the three functions above (or `otp_qr_html()`).

---

## 11. Authentication, roles and OTP

1. Guest hits any module → redirected to the landing page (`app_session_get('landing_page')`, default `login`).
2. Login checks `username` + `decrypt_password(password_hash)`.
3. **user** → `otp_finish_login()` → home.
4. **admin / superadmin** → OTP module. First time: QR + secret. Later: 6-digit code.
5. Superadmin can reset any user's OTP from **Users** (clears `otp_secret` and `otp_enabled`).

Session keys after a full login: `user_id`, `user_level`, `user_name`, `otp_verified`.

All session data is stored per application under the nested array
`$_SESSION['openframe_apps'][OPENFRAME_APP_KEY]` (key defaults to the app's URL
path), so multiple OpenFrame apps on the same server never share state. Access
it only through the `app_session_*` helpers — see `docs/DEVELOPER.md` §5.0.

---

## 12. Languages

Files in `lang/` (`en.php`, `ar.php`) return associative arrays.

```php
echo lang('common.save', 'Save');
```

Switch with `?lang=ar` or `?lang=en`. The navbar language menu does this for you. Arabic loads `bootstrap.rtl.min.css`, `navbar-rtl.php` and `sidemenu-rtl.php`.

Add a language by creating `lang/xx.php` with the same keys as `en.php`.

---

## 13. Security

- Escape output with `e()`.
- Call `csrf_check()` on every POST that changes data.
- Use placeholders (`?`) for SQL values.
- Pass table/column names through `db_table()` / `db_ident()`, never from request input.
- Keep `ENCRYPTION_KEY` and `DB_PASS` out of public repositories.
- Change default user passwords after import.
- `require_framework()` at the top of every module `index.php`.
- OTP QR codes are generated locally — no Google Charts / qrserver / Composer / GD.

---

## 14. License

OpenFrame is provided for use in your own applications. Bootstrap 5, Bootstrap Icons and bundled assets keep their upstream licenses. The QR encoder in `includes/qrcode.php` is adapted from Project Nayuki (MIT / public domain).

---

For signatures, parameters and copy-paste examples of every function, see **[docs/DEVELOPER.md](docs/DEVELOPER.md)**.
