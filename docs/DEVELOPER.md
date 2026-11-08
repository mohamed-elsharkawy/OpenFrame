# OpenFrame — Developer reference

This is the complete function and module reference. For install and a shorter index, see [README.md](../README.md).

---

## Contents

1. [Architecture](#1-architecture)
2. [Create a new module](#2-create-a-new-module)
3. [Create an action inside a module](#3-create-an-action-inside-a-module)
4. [CRUD — `includes/db.php`](#4-crud--includesdbphp)
5. [Helpers — `includes/helpers.php`](#5-helpers--includeshelpersphp)
6. [Config — `includes/config.php`](#6-config--includesconfigphp)
7. [Modules — `includes/modules.php`](#7-modules--includesmodulesphp)
8. [Routes — `includes/routes.php`](#8-routes--includesroutesphp)
9. [QR codes — `includes/qrcode.php`](#9-qr-codes--includesqrcodephp)
10. [Constants](#10-constants)
11. [Conventions](#11-conventions)

---

## 1. Architecture

Every request enters `index.php`. Modules never bootstrap the framework themselves.

```
Browser
  → index.php
      → config.php, helpers.php, db.php, modules.php, routes.php
      → language + OTP / landing-page gates
      → header.php (navbar + sidemenu + flash)
      → modules/<slug>/index.php
      → footer.php
```

Inside a module view you can call any function from `config.php`, `helpers.php`, `db.php`, `modules.php` and `routes.php`. The active module metadata is `$GLOBALS['openframe_module']` (and often `$module` if you assign it).

`load_module()` does `require $file` inside a function. Variables you create in the module stay in that file’s scope unless you write to `$GLOBALS`.

---

## 2. Create a new module

### 2.1 Files

```text
modules/<slug>/
├── info.php     return [ ...metadata ]
└── index.php    the screen + its actions
```

`<slug>` may contain only `a-z`, `0-9`, `_` and `-`. That slug is the value of `?module=`.

A folder without `info.php` is ignored.

### 2.2 `info.php` keys

```php
<?php
return [
    'name_en'        => 'Invoices',
    'name_ar'        => 'الفواتير',
    'name'           => 'Invoices',
    'description_en' => 'Create and print invoices.',
    'description_ar' => 'إنشاء وطباعة الفواتير.',
    'description'    => 'Create and print invoices.',
    'version'        => '1.0.0',
    'icon'           => 'bi-receipt',
    'enabled'        => true,
    'order'          => 20,
    'group_en'       => 'Finance',
    'group_ar'       => 'المالية',
    'group'          => 'Finance',
    'menu'           => true,
    'author'         => 'Finance team',
    'permission'     => 'invoices.manage',  // custom — OpenFrame stores it, does not enforce it
];
```

| Key | Type | Default | Meaning |
|---|---|---|---|
| `name` | string | Title-cased folder | Fallback label |
| `name_en` / `name_ar` | string | `name` | Language-specific label |
| `description` / `_en` / `_ar` | string | `''` | Home module text |
| `version` | string | `1.0.0` | Informational |
| `icon` | string | `bi-puzzle` | Bootstrap Icons class |
| `enabled` | bool | `true` | `false` → no route, no menu, HTTP 404 |
| `order` | int | `100` | Sort (ascending) |
| `group` / `group_en` / `group_ar` | string | `''` | Navbar dropdown. Empty = ungrouped |
| `menu` | bool | `true` | `false` = routable but hidden from navbar/sidemenu |
| `author` | string | — | Informational |
| custom keys | mixed | — | Available as `$module['your_key']` |

Always overwritten by the loader: `slug`, `folder`, `path`, `file`.

### 2.3 `index.php` skeleton

```php
<?php
require_framework();
require_login();
// require_any_level(['superadmin', 'admin']);

$rows = db_rows('SELECT * FROM ' . db_table('invoices') . ' ORDER BY id DESC');
?>
<h1 class="h4 mb-3">
    <i class="bi <?php echo e($GLOBALS['openframe_module']['icon']); ?> me-1"></i>
    <?php echo e($GLOBALS['openframe_module']['name']); ?>
</h1>

<table class="table">
    <?php foreach ($rows as $row): ?>
        <tr><td><?php echo e($row['title']); ?></td></tr>
    <?php endforeach; ?>
</table>
```

### 2.4 Hide from the menu but keep the URL

Used by `login` and `otp`:

```php
'menu'    => false,
'enabled' => true,
```

`index.php?module=otp` still works; the link is not in the navbar.

### 2.5 Disable a module

```php
'enabled' => false,
```

The slug 404s. No need to delete the folder.

---

## 3. Create an action inside a module

There is one file per module. Actions are branches at the top of that file.

### 3.1 Naming

| Kind | How | Example |
|---|---|---|
| Write | POST + hidden `action` | `save`, `delete`, `reset_otp`, `login` |
| Read / navigate | GET query | `?edit=3`, `?page=2`, `?action=logout` |

Use POST for anything that changes data.

### 3.2 Pattern

```php
<?php
require_framework();
require_login();

if (is_post()) {
    csrf_check();
    $action = input('action');

    if ($action === 'save') {
        // validate → insert or update → flash → redirect
    }

    if ($action === 'delete') {
        // delete → flash → redirect
    }
}

// GET: load data for the form / table
$editId  = (int) input('edit');
$editing = $editId > 0 ? db_find('notes', $editId) : null;
$list    = db_rows('SELECT * FROM ' . db_table('notes') . ' ORDER BY id DESC');
```

### 3.3 Save (create or update)

```php
if ($action === 'save') {
    $id    = (int) input('id');
    $title = input('title');

    if ($title === '') {
        flash('danger', 'Title is required.');
        redirect(url('index.php?module=notes'));
    }

    $data = [
        'title'      => $title,
        'updated_at' => now(),
    ];

    if ($id > 0 && db_find('notes', $id)) {
        db_update('notes', $data, 'id = ?', [$id]);
        flash('success', 'Updated.');
    } else {
        $data['created_at'] = now();
        db_insert('notes', $data);
        flash('success', 'Created.');
    }

    redirect(url('index.php?module=notes'));
}
```

Form:

```html
<form method="post">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?php echo $editing ? (int) $editing['id'] : 0; ?>">
    <input class="form-control" name="title"
           value="<?php echo e($editing ? $editing['title'] : ''); ?>" required>
    <button class="btn btn-primary" type="submit">
        <?php echo $editing ? 'Update' : 'Create'; ?>
    </button>
</form>
```

### 3.4 Delete

```php
if ($action === 'delete') {
    $id = (int) input('id');
    db_delete('notes', 'id = ?', [$id]);
    flash('success', 'Deleted.');
    redirect(url('index.php?module=notes'));
}
```

```html
<form method="post" class="d-inline" onsubmit="return confirm('Delete this row?');">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
</form>
```

### 3.5 GET “open editor” action

```php
<a href="<?php echo e(url('index.php?module=notes&edit=' . (int) $row['id'])); ?>">Edit</a>
```

```php
$editId  = (int) input('edit');
$editing = $editId > 0 ? db_find('notes', $editId) : null;
```

### 3.6 GET logout (built-in)

```text
index.php?module=login&action=logout
```

`modules/login/index.php` calls `logout_user()` when `input('action') === 'logout'`.

### 3.7 Rules

- Call `csrf_check()` once per POST, before reading fields.
- Redirect after every successful (and usually every failed) write.
- `flash()` survives the redirect; `header.php` prints it.
- `input()` never returns arrays — use `$_POST['permissions']` if you need a multi-select.
- Integer ids: `(int) input('id')`.

Working references: `modules/users/index.php`, `docs/examples/notes_crud/index.php`.

---

## 4. CRUD — `includes/db.php`

All CRUD helpers apply `DB_PREFIX` (`opf_` by default). Pass `users`, not `opf_users`.

Raw SQL does **not** auto-prefix. Wrap names with `db_table('users')`.

### `db(): PDO`

Shared PDO connection. Opens on first call. `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, native prepares.

```php
$pdo = db();
$pdo->exec('SET time_zone = "+00:00"');
```

### `db_ident($name): string`

Validates `^[A-Za-z_][A-Za-z0-9_]*$` and wraps in backticks. Throws `InvalidArgumentException` otherwise.

```php
db_ident('full_name');   // `full_name`
```

### `db_table($name): string`

```php
db_table('users');   // `opf_users`
```

Use this in every hand-written SQL string.

### `db_row($sql, $params = []): ?array`

One row, or `null`.

```php
$user = db_row(
    'SELECT * FROM ' . db_table('users') . ' WHERE username = ?',
    [$username]
);
```

### `db_rows($sql, $params = []): array`

All rows (empty array if none).

```php
$users = db_rows('SELECT * FROM ' . db_table('users') . ' ORDER BY username');
```

### `db_exec($sql, $params = []): int`

INSERT / UPDATE / DELETE / DDL. Returns affected row count.

```php
db_exec(
    'UPDATE ' . db_table('users') . ' SET full_name = ? WHERE id = ?',
    ['Sara', 3]
);
```

### `db_column($sql, $params = []): mixed`

First column of the first row, or `null`.

```php
$total = (int) db_column('SELECT COUNT(*) FROM ' . db_table('users'));
```

### `db_insert($table, array $data): int`

Inserts `$data` keys as columns. Returns `lastInsertId`. Throws if `$data` is empty.

```php
$id = db_insert('users', [
    'username'      => 'sara',
    'full_name'     => 'Sara',
    'password_hash' => encrypt_password('secret'),
    'level'         => 'user',
    'created_at'    => now(),
    'updated_at'    => now(),
]);
```

### `db_update($table, array $data, $where = '', array $whereParams = []): int`

```php
db_update('users', [
    'full_name'  => 'Sara A.',
    'updated_at' => now(),
], 'id = ?', [$id]);
```

Empty `$where` updates **every row**. Always pass a condition.

### `db_delete($table, $where = '', array $params = []): int`

```php
db_delete('users', 'id = ?', [$id]);
```

Empty `$where` deletes **every row**.

### `db_find($table, $id, $key = 'id'): ?array`

```php
$user = db_find('users', 3);
$city = db_find('cities', 'CAI', 'code');
```

### `db_exists($table, $where = '', array $params = []): bool`

```php
if (db_exists('users', 'username = ?', ['sara'])) {
    flash('danger', 'Username already exists.');
}
```

### `db_count($table, $where = '', array $params = []): int`

```php
$total = db_count('users');
$admins = db_count('users', 'level = ?', ['admin']);
```

### `db_transaction(callable $callback): mixed`

Begins a transaction, runs `$callback($pdo)`, commits. Any `Throwable` rolls back and is rethrown. Returns the callback’s return value.

```php
db_transaction(function () use ($order, $lines) {
    $id = db_insert('orders', $order);
    foreach ($lines as $line) {
        $line['order_id'] = $id;
        db_insert('order_lines', $line);
    }
    return $id;
});
```

### `db_paginate($sql, array $params = [], $page = 1, $perPage = 20): array`

`$sql` must **not** include `LIMIT`. Returns:

```php
[
    'rows'     => [ /* rows */ ],
    'total'    => 42,
    'page'     => 2,
    'per_page' => 20,
    'pages'    => 3,
]
```

```php
$page = db_paginate(
    'SELECT * FROM ' . db_table('users') . ' ORDER BY username',
    [],
    (int) input('page', '1'),
    20
);

foreach ($page['rows'] as $row) { /* ... */ }
```

Write the table name with `db_table()` inside `$sql`.

### `db_next_id($table): int`

Reads `information_schema` (or `MAX(id)+1`) so you can preview a record number before insert. Not a reservation — concurrent inserts can skip that number.

---

## 5. Helpers — `includes/helpers.php`

### 5.1 Request and flow

#### `redirect($url): void`

Sends `Location` if headers are still open; otherwise a meta refresh. Always `exit`s.

```php
redirect(url('index.php?module=home'));
```

#### `input($key, $default = ''): string`

POST wins over GET. Arrays are ignored (returns `$default`). Value is cast to string and trimmed.

```php
$title = input('title');
$page  = (int) input('page', '1');
```

Multi-select: read `$_POST['permissions']` directly.

#### `is_post(): bool`

```php
if (is_post()) {
    csrf_check();
}
```

#### `is_ajax(): bool` / `wants_json(): bool`

True when `X-Requested-With: XMLHttpRequest` or `Accept` contains `application/json`.

#### `json_response($data, $status = 200): void`

JSON-encodes `$data` (`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`) and exits.

```php
json_response(['ok' => true, 'id' => $id]);
```

#### `abort($code = 404, $message = ''): void`

HTTP error page. If the layout already started, prints a Bootstrap alert inside it.

```php
abort(403, 'You are not allowed to view this page.');
```

Known titles: 400, 401, 403, 404, 405, 500.

#### `now(): string`

`date('Y-m-d H:i:s')` for DATETIME columns.

#### `str_limit($value, $limit = 80, $end = '…'): string`

UTF-8 aware when `mbstring` is loaded.

```php
echo e(str_limit($row['body'], 60));
```

#### `require_framework(): void`

If the script URL is `.../modules/<slug>/index.php`, redirect to the app root (except the login module). Call it as the first line of every module `index.php`.

### 5.2 CSRF and flash

#### `csrf_token(): string`

32-byte hex token stored in `$_SESSION['csrf_token']`.

#### `csrf_field(): string`

```html
<form method="post">
    <?php echo csrf_field(); ?>
```

#### `csrf_check(): void`

Compares `$_POST['csrf_token']` with `hash_equals`. On failure: `abort(403, ...)`.

#### `flash($type, $message): void`

`$type`: `success`, `danger`, `warning`, `info`. Queued until the next layout render.

```php
flash('success', lang('msg.created', 'Created.'));
redirect(url('index.php?module=notes'));
```

#### `flash_render(): string`

Renders dismissible Bootstrap alerts and clears the queue. `header.php` calls this; you normally do not.

### 5.3 Language

#### `get_available_languages(): array`

Basenames of `lang/*.php`, sorted (`['ar', 'en']`).

#### `load_lang(): array`

Resolves `?lang=` → session → `en`. Caches for the request. Invalid codes fall back to `en`.

#### `lang($key, $default = ''): string`

```php
echo lang('common.save', 'Save');
```

If the key is missing, returns `$default`, or the key itself when `$default` is `''`.

### 5.4 Authentication

#### `current_user(): ?array`

Loads `SELECT * FROM opf_users WHERE id = ?` from `$_SESSION['user_id']`. Cached. `null` when not logged in.

#### `require_login(): void`

If there is no user, redirect to `$_SESSION['landing_page']` (default `login`). Does not redirect when already on the login module file.

#### `require_level($level): void`

Calls `require_login()`, then `abort(403)` unless `$user['level'] === $level`.

```php
require_level('superadmin');
```

#### `require_any_level(array $levels): void`

```php
require_any_level(['superadmin', 'admin']);
```

#### `logout_user(): void`

Unsets `user_id`, `user_level`, `user_name`, and OTP session keys; regenerates the session id; redirects to the landing module.

### 5.5 TOTP

Used by `modules/otp` and the gate in `index.php`. Only **superadmin** and **admin** must complete OTP. Regular users skip it.

#### `otp_generate_secret($length = 16): string`

Random base32 (`A–Z2–7`) secret.

#### `otp_base32_decode($secret): string`

Binary HMAC key.

#### `otp_generate_code($secret, $time = null): string`

6-digit TOTP, SHA-1, 30-second steps. `$time` is a counter (`floor(unix / 30)`), not a unix timestamp, when passed explicitly.

#### `otp_verify($secret, $code, $window = 1): bool`

Accepts the current step ± `$window` (default ±30 seconds).

#### `otp_provisioning_uri($secret, $username, $issuer = 'OpenFrame'): string`

```text
otpauth://totp/Issuer:user?secret=...&issuer=Issuer
```

#### `otp_is_privileged($user): bool`

`true` when `$user['level']` is `superadmin` or `admin`.

#### `otp_finish_login(array $user): void`

Regenerates session id, sets `user_id`, `user_level`, `user_name`, `otp_verified = 1`, clears pending OTP keys.

#### `otp_qr_html($data, $modulePx = 6): string`

Loads `qrcode.php` if needed and returns an HTML table QR (quiet zone included). Empty string if the encoder is missing.

### 5.6 Password encryption

Login compares `decrypt_password($user['password_hash']) === $password`. This is reversible AES-256-CBC (not `password_hash()`). The key is `ENCRYPTION_KEY`.

#### `encrypt_password($plain): string`

IV + ciphertext, base64.

#### `decrypt_password($encrypted): string|false`

`false` on decode failure.

Change `ENCRYPTION_KEY` only if you re-encrypt every stored password.

### 5.7 `load_modules(): array`

Alias of `get_modules()`.

---

## 6. Config — `includes/config.php`

### `config($key = null, $default = null): mixed`

```php
config();                 // all settings
config('appname');        // 'OpenFrame'
config('theme', 'light'); // default if missing
```

Stored in `$_SESSION['openframe']`. `apppath`, `appurl` and `applogo` are refreshed every request.

### `set_config($key, $value): void`

Persists for the rest of the session. To change a value for everyone, edit `config.php`.

### `e($value): string`

`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. Use at print time:

```php
echo e($row['full_name']);
```

### `url($path = ''): string`

Builds a path under the app base. Strips a leading `index.php?` so links look like `/?module=home`.

```php
url('');                              // /openframe/
url('assets/css/bootstrap.min.css');  // /openframe/assets/css/bootstrap.min.css
url('index.php?module=users&edit=3'); // /openframe/?module=users&edit=3
```

Do not prepend a slash yourself.

---

## 7. Modules — `includes/modules.php`

### `get_modules(): array`

`slug => metadata`, sorted by `order` then `name`. Cached for the request. Resolves `name` / `description` / `group` from `*_ar` or `*_en` using `$_SESSION['lang']`.

### `get_module($slug): ?array`

One module or `null`.

### `enabled_modules(): array`

Subset with `enabled` truthy. Used by the router and menus.

### `get_modules_by_group(): array`

```php
[
    'ungrouped' => [ 'home' => [...], ... ],
    'groups'    => [
        'System Configuration' => [ 'users' => [...], 'profile' => [...] ],
    ],
]
```

“System Configuration” / “إعدادات النظام” is sorted first. Navbar and sidemenu consume this.

Modules with `'menu' => false` are still in this list; the navbar skips them with `if ($module['menu'] === false) continue;`.

---

## 8. Routes — `includes/routes.php`

### `get_routes(): array`

`slug => absolute path to index.php` for enabled modules only.

### `current_module(): string`

- No `?module=` → `home` if enabled, else the first enabled slug.
- Valid slug → that slug.
- Unknown or disabled → `''` (then `load_module()` prints 404).

The query value is stripped to `[a-z0-9_-]`.

### `load_module(): void`

Requires the view, setting `$GLOBALS['openframe_module']`. Missing file or empty slug → HTTP 404 alert inside the layout.

---

## 9. QR codes — `includes/qrcode.php`

Offline encoder (byte mode, ECC M, versions 1–16). No GD, no Composer, no network.

### Public API

```php
$matrix = qrcode_matrix('otpauth://totp/...');          // [row][col] => 0|1
echo qrcode_svg($text, 6, 4);                           // <svg>…
echo qrcode_html($text, 6, 4);                          // <table class="otp-qr">…
```

Prefer `otp_qr_html($uri)` from helpers in application code.

### Internal helpers (not for modules)

`qr_blank`, `qr_append_bits`, `qr_ecc_per_block`, `qr_num_ecc_blocks`, `qr_num_raw_data_modules`, `qr_num_data_codewords`, `qr_ccbits`, `qr_encode_payload`, `qr_rs_multiply`, `qr_rs_divisor`, `qr_rs_remainder`, `qr_add_ecc_and_interleave`, `qr_alignment_positions`, `qr_set_function`, `qr_draw_finder`, `qr_draw_alignment`, `qr_format_bits`, `qr_draw_format_bits`, `qr_draw_version`, `qr_draw_function_patterns`, `qr_draw_codewords`, `qr_mask_bit`, `qr_apply_mask`, `qr_penalty_score`.

---

## 10. Constants

| Constant | Source | Meaning |
|---|---|---|
| `OPENFRAME` | `index.php` | Framework booted (guards layout files). |
| `OPENFRAME_PATH` | `config.php` | Absolute filesystem root. |
| `OPENFRAME_URL` | `config.php` | Base URL, no trailing slash (`''` at domain root). |
| `APP_NAME` | `config.php` | Default `OpenFrame`. |
| `APP_VERSION` | `config.php` | Default `1.0.0`. |
| `APP_LOGO_PATH` | `config.php` | Logo URL. |
| `APP_LOGO_MAX_WIDTH` / `_HEIGHT` | `config.php` | Navbar logo box. |
| `DB_HOST` `DB_PORT` `DB_NAME` `DB_USER` `DB_PASS` `DB_CHARSET` | `config.php` | PDO DSN. |
| `DB_PREFIX` | `config.php` | Default `opf_`. |
| `ENCRYPTION_KEY` | `config.php` | AES key material for `encrypt_password()`. |

Session extras (not constants): `landing_page` (default `login`), `module_selector`, `devtool_disabled`, `lang`.

---

## 11. Conventions

1. **Escape on the way out:** `echo e($value);`
2. **CSRF on every write:** `csrf_field()` in the form, `csrf_check()` in the POST branch.
3. **Prefix in CRUD, not in your argument:** `db_insert('users', $data)` → table `opf_users`.
4. **Prefix in raw SQL yourself:** `'SELECT * FROM ' . db_table('users')`.
5. **Redirect after POST.**
6. **`require_framework()`** at the top of every module `index.php`.
7. **Roles:** `require_login()` / `require_level()` / `require_any_level()`.
8. **OTP** is only for admin and superadmin. Superadmin resets secrets from the Users module.

Built-in table `opf_users` columns: `id`, `username`, `full_name`, `password_hash`, `level`, `permissions`, `otp_secret`, `otp_enabled`, `created_at`, `updated_at`.
