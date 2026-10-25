# OpenFrame — User Guide

<!-- cover -->

# About this guide

This guide is written for someone who knows a little PHP and wants to build a small web application without learning a large framework. It walks through the same steps you would take on a real project: install OpenFrame, change its look, add a page, connect a database, build a complete create/read/update/delete screen, and export the data to PDF, Excel and Word.

You do not need to read it from cover to cover. Each chapter stands on its own, and every chapter ends with something you can see working in the browser.

**How to read the examples.** Grey boxes contain code or commands. Copy them exactly, then change the names. A line beginning with `//` is a comment that explains what the next line does. When you see `…` inside a code box, it means "the rest of your code goes here" and should not be typed.

**Two documents, two jobs.** This guide teaches. The `README.md` file in the framework folder is the reference: it lists every function, every option and every security rule. Use this guide to learn the shape of the framework, and the README when you need the exact signature of a function.

**A note on naming.** In this guide, "the application folder" means the folder that contains `index.php`. On Windows with WAMP that is usually `D:\wamp64\www\openframe`. On Linux it is usually `/var/www/html/openframe`.

<!-- pagebreak -->

# Contents

1. What OpenFrame is — and what it is not
2. Before you begin
3. Your first ten minutes
4. The five files that run everything
5. Changing the name, logo and colours
6. Your first module
7. Your first database table
8. A complete CRUD screen, step by step
9. Exporting to PDF, Excel and Word
10. Adding users and permissions
11. Arabic and right-to-left layouts
12. Keeping your site safe
13. When something goes wrong
14. Publishing your site
15. Quick reference
16. Glossary

<!-- pagebreak -->

# 1. What OpenFrame is — and what it is not

OpenFrame is a small collection of PHP files that gives a web application three things it always needs: a single entry point, a menu built from folders, and a consistent page layout. Everything else — the database tables, the screens, the business rules — is yours to write in plain PHP.

That is the whole idea. There is no new language to learn, no template syntax, no configuration files full of options. If you can write a PHP page and a SQL query, you can build an application with OpenFrame.

## 1.1 What you get

- **One entry point.** Every request goes through `index.php`.
- **A menu that builds itself.** Each folder inside `modules/` becomes a page and a menu link.
- **A ready page layout.** Bootstrap 5 is bundled, so pages look modern without any design work.
- **A database layer.** Fifteen small functions cover connecting, reading, inserting, updating, deleting, counting, paging and transactions.
- **Safety helpers.** Escaping, form tokens and safe SQL are built in and easy to use correctly.
- **No build step.** You edit a `.php` file, you refresh the browser. No compiling, no package manager required.

## 1.2 What OpenFrame deliberately leaves out

OpenFrame does not include a login system, a permission system, a translation system or a report generator. Those are application features, and every application wants them slightly differently. This guide shows working recipes for each of them, but they are examples you adapt — not switches you turn on.

It also has no "admin panel" and no scaffolding generator. You build screens by creating folders, which takes about thirty seconds per screen.

## 1.3 When OpenFrame is the right choice

Choose OpenFrame when you want a small internal tool, a reception desk application, a records system, a reporting dashboard, or a prototype that must run on ordinary shared hosting. It works well when one or two developers need to understand the entire code base, and when "it must still run in five years" matters more than "it must use the newest library".

Choose something larger when you need dozens of developers, complex background jobs, or a full-featured CMS. OpenFrame is intentionally small.

<!-- pagebreak -->

# 2. Before you begin

## 2.1 What you need

You need three things:

1. **PHP 8.0 or newer.** OpenFrame is tested on PHP 8.3.
2. **A web server.** Apache, Nginx, or PHP's own built-in server for testing.
3. **A database — optional.** MySQL 5.7+ or MariaDB 10.3+. Pages that never touch the database work without it.

If you installed WAMP, XAMPP or Laragon, you already have all three. Start the Apache and MySQL services from the control panel before continuing.

## 2.2 Checking your PHP version

Open a terminal and run:

```text
php -v
```

The first line shows the version, for example `PHP 8.3.4`. If the command is not found on Windows, PHP is not on your PATH. With WAMP you can use the full path instead:

```text
D:\wamp64\bin\php\php8.3.4\php.exe -v
```

You can also create a temporary file named `info.php` inside your web folder containing `<?php phpinfo();` and open it in the browser. **Delete it afterwards** — it reveals server details.

## 2.3 Confirming the required extensions

OpenFrame needs `pdo_mysql`, `mbstring` and `fileinfo`. All three are enabled by default in WAMP and XAMPP. To check, run:

```text
php -m
```

You should see `fileinfo`, `mbstring` and `pdo_mysql` in the list. If an extension is missing, open `php.ini`, remove the leading semicolon from the matching `extension=` line, and restart Apache.

## 2.4 Placing the files

Copy the `OpenFrame` folder into your web root. You can rename it to anything, for example `reception`, but avoid spaces in the name.

```text
D:\wamp64\www\openframe\index.php
D:\wamp64\www\openframe\includes\
D:\wamp64\www\openframe\modules\
D:\wamp64\www\openframe\assets\
```

Then open:

```text
http://localhost/openframe/
```

If you see the OpenFrame welcome page with a list of installed modules, everything is in place.

## 2.5 If the page does not load

- **"Not Found"** — the folder name in the URL does not match the folder on disk. Check spelling and letter case.
- **A blank white page** — PHP hit an error but is not displaying it. Set `display_errors=1` and `error_reporting=E_ALL` in `php.ini`, restart Apache, and reload.
- **A directory listing instead of the application** — `index.php` is missing from the folder you opened.
- **Styles are missing** — the `assets` folder was not copied. Copy the whole folder, not only the PHP files.

<!-- pagebreak -->

# 3. Your first ten minutes

This chapter is a guided tour. Follow it once and you will know where everything lives.

## 3.1 The welcome page

Open `http://localhost/openframe/`. You should see:

- a navbar with the application name, a version badge and a link for each module;
- a large welcome panel;
- a grid listing every installed module with its icon, version and description;
- a footer with the current year and the PHP version.

At the moment there is exactly one module: **Home**. The next chapters add more.

## 3.2 URLs you will use

OpenFrame uses one URL pattern everywhere:

```text
index.php?module=THE_MODULE_NAME
```

So the welcome page is `index.php?module=home`, and once you create a module called `notes` it will be at `index.php?module=notes`. You can add more parameters after it, for example `index.php?module=notes&edit=4`. Those extra parameters are available to your code and never confuse the router.

You will never write a new `.htaccess` rule or edit a routing table to add a page. The folder is the route.

## 3.3 Open the same page at a different address

Try these three URLs:

```text
http://localhost/openframe/
http://localhost/openframe/index.php
http://localhost/openframe/index.php?module=home
```

All three show the same page. OpenFrame detects where it is installed, so the same files work at `http://localhost/openframe/`, at `http://localhost/`, or in a sub-folder three levels deep — without changing a single line of configuration.

## 3.4 Break it on purpose

Now try a page that does not exist:

```text
http://localhost/openframe/index.php?module=invoices
```

You get a clean "404 — Module not found" message, still inside the normal layout. This is deliberate: a wrong link should never produce a blank page or a PHP warning. Remember this behaviour, because it is also how you temporarily switch a module off: set `enabled` to `false` in its `info.php` and it disappears from both the menu and the router.

<!-- pagebreak -->

# 4. The five files that run everything

You can build a whole application without opening these files. When you do need to change global behaviour, this is where to look.

## 4.1 `index.php`

The entry point. Every request passes through it, in a fixed order: start the session, load the helpers, load the database layer, discover the modules, resolve the route, print the header, run the module, print the footer.

Because it is short — around thirty lines — you can read it in a minute and know the entire request flow. Two details matter in daily work:

- **Output is buffered.** Nothing is sent to the browser until the request ends. That is why a 404 can be sent after the menu has already been drawn.
- **The database is lazy.** The connection opens the first time a database function is called, so pages that do not need MySQL still work when MySQL is stopped.

## 4.2 `includes/config.php`

Application settings, the session, and the four core helpers: `config()`, `set_config()`, `e()` and `url()`. This is the file you edit to change the application name, the logo path, the version number, and the database credentials.

Settings are stored in the session on first use. Values you change with `set_config()` last for the visitor's session; values you change in the file apply to everyone.

## 4.3 `includes/db.php`

The database layer: one shared PDO connection plus the CRUD helpers. Column names are validated, values are always bound with placeholders, and errors throw exceptions you can catch.

## 4.4 `includes/modules.php` and `includes/routes.php`

These two files scan the `modules/` folder. `modules.php` reads each module's `info.php` and builds the list you see in the menu. `routes.php` turns that list into a route table and loads the matching `index.php`, or produces the 404 page.

You rarely edit either file. Adding a module means adding a folder, not editing the loader.

## 4.5 `includes/header.php` and `includes/footer.php`

The layout. `header.php` prints the document head, the page title, the stylesheets and the navbar, and opens the main container. `footer.php` closes it, prints the footer and loads the Bootstrap JavaScript bundle.

Both files refuse to run when opened directly from the browser, so a visitor cannot read them as text.

## 4.6 Where your work goes

Almost all of your work happens in `modules/`. A typical finished application looks like this:

```text
modules/
├── home/
├── visitors/
├── departments/
├── reports/
├── profile/
└── login/
```

Each folder contains an `info.php` (the label, icon and menu position) and an `index.php` (the screen). That is the entire structure.

<!-- pagebreak -->

# 5. Changing the name, logo and colours

## 5.1 The application name

Open `includes/config.php` and find the settings array near the top:

```php
$settings = [
    'appname'    => 'OpenFrame',
    'appversion' => '1.0.0',
    ...
];
```

Change `appname` to your application's name, for example `Reception Desk`, and save. Reload the page: the navbar, the browser tab title and the footer all update, because they all read the same setting.

To rename the application for one visitor only — useful for testing — call:

```php
set_config('appname', 'Test name');
```

from inside a module. The change lasts until that session ends.

## 5.2 The logo

Replace the file `assets/img/logo.png` with your own image, keeping the same name. Use a square image at least 64 by 64 pixels; OpenFrame displays it at 32 pixels in the navbar and uses the same file as the browser favicon.

If your logo has a different name, change the `applogo` line in `config.php`:

```php
'applogo' => $appUrl . '/assets/img/my-logo.png',
```

## 5.3 The version badge

The badge next to the name is there to help you confirm which copy of the application is deployed. Set it to anything:

```php
'appversion' => '2.4.0',
```

## 5.4 Colours

OpenFrame uses Bootstrap 5, which is driven by CSS variables. The cleanest way to rebrand is to add a small `<style>` block in `includes/header.php`, inside `<head>` after the Bootstrap stylesheet link:

```html
<style>
    :root {
        --bs-primary: #997512;
        --bs-primary-rgb: 153, 117, 18;
        --bs-link-color: #332b15;
        --bs-link-hover-color: #997512;
    }
</style>
```

Every Bootstrap component that uses "primary" — buttons, links, badges, active menu items — now uses your colour. Nothing else needs to change.

For a more thorough rebrand, create `assets/css/theme.css`, link it from `header.php`, and put all your overrides there. Keeping custom CSS in one file makes future upgrades of Bootstrap much easier.

## 5.5 The menu

The menu is generated from the modules folder, so you do not edit it for each new page. You can control:

- **position** with `'order' => 20` in a module's `info.php` (lower numbers first, default 100);
- **label** with `'name' => 'Visitors'`;
- **icon** with `'icon' => 'bi-people'`;
- **visibility** with `'enabled' => false`.

Icons come from Bootstrap Icons. Browse the names at `icons.getbootstrap.com`, then use the class exactly as shown, for example `bi-calendar-check`.

## 5.6 Removing the framework credit

The "Powered by OpenFrame" text in the navbar and the credit in the footer identify the framework and are part of its license. Keep them. If you need to add your own company name, add a second line rather than replacing them.

<!-- pagebreak -->

# 6. Your first module

A module is a folder with two files. That is the whole requirement.

## 6.1 Create the folder

Inside `modules/`, create a folder named `hello`. Use lowercase letters, digits, hyphens or underscores only — the folder name becomes part of the URL.

```text
modules/hello/
```

## 6.2 Describe the module

Create `modules/hello/info.php`:

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

This file only returns an array. Each key tells OpenFrame something about the page:

- `name` is the label in the menu and in the browser tab.
- `description` appears on the welcome page.
- `icon` is any Bootstrap Icons class.
- `enabled` switches the page on or off.
- `order` decides where it sits in the menu.

## 6.3 Write the page

Create `modules/hello/index.php`:

```php
<h1 class="h3">Hello, OpenFrame!</h1>

<p class="text-muted">
    This page was rendered at <?php echo e(date('H:i:s')); ?>.
</p>

<div class="alert alert-success mb-0">
    If you can read this, the module works.
</div>
```

Reload the browser. **Hello** is now in the menu, and the page is at:

```text
http://localhost/openframe/index.php?module=hello
```

No registration, no cache to clear, no configuration. OpenFrame scanned the folder on this request and found the new module.

## 6.4 What just happened

1. `includes/modules.php` scanned `modules/` and found `hello/info.php`.
2. It merged your values with the defaults (icon `bi-puzzle`, order `100`, enabled `true`).
3. `includes/routes.php` added the route `hello` and the navbar link.
4. Your `index.php` was executed inside the layout — after the navbar, before the footer.

## 6.5 Make the page interactive

Modules are plain PHP, so a form works the normal way. This version counts how many times the visitor submitted the form in the current session:

```php
<?php
if (is_post()) {
    csrf_check();
    $_SESSION['hello_count'] = (int) ($_SESSION['hello_count'] ?? 0) + 1;
    flash('success', 'Thank you, your click was counted.');
    redirect(url('index.php?module=hello'));
}

$count = (int) ($_SESSION['hello_count'] ?? 0);
?>

<h1 class="h3">Hello, OpenFrame!</h1>

<p>You have clicked <strong><?php echo $count; ?></strong> times.</p>

<form method="post">
    <?php echo csrf_field(); ?>
    <button class="btn btn-primary" type="submit">Click me</button>
</form>
```

Three habits to copy from this example:

- **`csrf_check()` at the top of every POST branch.** It rejects forms that were not generated by your site.
- **`flash()` followed by `redirect()`.** The message survives the redirect and appears once, on the next page.
- **`e()` around anything printed.** Even the count, because it costs nothing and protects you when the value stops being a number.

## 6.6 A second page in the same module

Sometimes a module needs a detail screen. Read a parameter and branch on it:

```php
<?php
$action = input('action', 'list');

if ($action === 'details') {
    $id = (int) input('id');
    ?>
    <h1 class="h4">Details of record <?php echo $id; ?></h1>
    <a class="btn btn-outline-secondary" href="<?php echo e(url('index.php?module=hello')); ?>">
        Back
    </a>
    <?php
    return;   // stop this file; footer.php still closes the layout
}
?>

<h1 class="h4">List</h1>
<a class="btn btn-primary"
   href="<?php echo e(url('index.php?module=hello&action=details&id=1')); ?>">
    Open record 1
</a>
```

`return` stops the module file but not the framework: the footer still runs, so the page stays valid HTML.

<!-- pagebreak -->

# 7. Your first database table

## 7.1 Create the database

Open phpMyAdmin (in WAMP, click the phpMyAdmin link in the tray menu) or use the MySQL command line:

```sql
CREATE DATABASE openframe
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

The name `openframe` is the default in `config.php`. If you use a different name, change `DB_NAME` to match.

## 7.2 Create a table

In phpMyAdmin, select the database, open the SQL tab, and run:

```sql
CREATE TABLE demo_notes (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(120) NOT NULL,
  body       TEXT NULL,
  created_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Four conventions worth adopting in every table:

- an `id` column that is the primary key and auto-increments;
- `created_at` (and `updated_at`) for sorting and auditing;
- `ENGINE=InnoDB` so transactions and foreign keys work;
- `utf8mb4` so Arabic, emoji and every other character store correctly.

## 7.3 Check the connection settings

Open `includes/config.php` and look at the database block:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'openframe');
define('DB_USER', 'root');
define('DB_PASS', '');
```

On a development machine these defaults are usually right. On Windows, use `127.0.0.1` rather than `localhost`: it avoids a slow name lookup and, on some setups, connects to a different MySQL instance.

## 7.4 Prove the connection works

Create a temporary module called `dbtest` with this view:

```php
<?php
$row = db_row('SELECT VERSION() AS version, DATABASE() AS db');

if ($row === null) {
    abort(500, 'Connected, but the query returned nothing.');
}
?>

<h1 class="h4">Database connection OK</h1>

<ul class="list-group">
    <li class="list-group-item">Server version: <?php echo e($row['version']); ?></li>
    <li class="list-group-item">Database: <?php echo e($row['db']); ?></li>
</ul>
```

If you see the version and the database name, the connection is ready. Delete the module afterwards.

If instead you see **"Database connection failed"**, the page tells you which database and host it tried. Check that MySQL is running, that the database exists, and that the user and password in `config.php` are correct.

## 7.5 Reading a table

The two functions you will use most:

```php
<?php
// All rows
$notes = db_rows('SELECT * FROM demo_notes ORDER BY id DESC');

// One row by its primary key
$note = db_find('demo_notes', 1);
?>

<h1 class="h4">Notes</h1>

<?php if (!$notes): ?>
    <p class="text-muted">No notes yet.</p>
<?php else: ?>
    <ul class="list-group">
        <?php foreach ($notes as $row): ?>
            <li class="list-group-item">
                <strong><?php echo e($row['title']); ?></strong><br>
                <span class="text-muted small"><?php echo e($row['created_at']); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
```

`db_rows()` returns an array of associative arrays — one per row, keyed by column name. `db_find()` returns a single row, or `null` when nothing matches.

## 7.6 Writing a row

```php
$id = db_insert('demo_notes', [
    'title'      => 'First note',
    'body'       => 'Created from PHP.',
    'created_at' => now(),
]);

echo 'The new note has id ' . $id;
```

`db_insert()` returns the new `id`, which is useful when you need to attach child rows (for example, an order and its lines).

## 7.7 Changing and deleting

```php
// Change one row
db_update('demo_notes', ['title' => 'Updated title'], 'id = ?', [4]);

// Delete one row
db_delete('demo_notes', 'id = ?', [4]);
```

The third argument of `db_update()` and the second of `db_delete()` are a SQL condition with `?` placeholders. The values go in the array that follows. **Never** paste a value into the condition string.

```php
// WRONG — an attacker can change the query
db_delete('demo_notes', "id = $id");

// RIGHT
db_delete('demo_notes', 'id = ?', [$id]);
```

<!-- pagebreak -->

# 8. A complete CRUD screen, step by step

CRUD means Create, Read, Update, Delete. This chapter builds one screen that does all four, with validation, messages, a confirmation prompt and pagination. The finished code is in `docs/examples/notes_crud/`, so you can also copy it and read along.

## 8.1 The plan

One page shows a form on the left and a table on the right. The same form creates and edits: a hidden field says which mode we are in. Delete uses a small form with a confirmation prompt. Every write ends with a redirect, so refreshing the page cannot repeat it.

## 8.2 Start with the write side

A module must handle its POST **before** printing any HTML, because it will redirect on success. Put this at the very top of `modules/notes_crud/index.php`:

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
            db_insert('demo_notes', [
                'title'      => $title,
                'body'       => $body,
                'created_at' => now(),
            ]);
            flash('success', 'Note created.');
        }

        redirect(url('index.php?module=notes_crud'));
    }

    if ($action === 'delete') {
        $deleted = db_delete('demo_notes', 'id = ?', [$id]);
        flash($deleted ? 'success' : 'warning', $deleted ? 'Note deleted.' : 'Note not found.');
        redirect(url('index.php?module=notes_crud'));
    }
}
```

Read it once more and notice the order: validate, write, set a message, redirect. On an error we do **not** redirect — we fall through and print the form again with the message, so the visitor does not lose what they typed.

## 8.3 Then read the data

Immediately after the write block:

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

`db_paginate()` needs a query **without** `LIMIT`; it adds the limit and offset itself, and counts the total for you. It returns the rows plus `total`, `page`, `per_page` and `pages`.

## 8.4 The form

```php
<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h6 card-title"><?php echo $editing ? 'Edit note' : 'New note'; ?></h2>

        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id"
                   value="<?php echo $editing ? (int) $editing['id'] : 0; ?>">

            <div class="mb-3">
                <label class="form-label" for="title">Title</label>
                <input class="form-control" id="title" name="title" required
                       value="<?php echo e($editing ? $editing['title'] : ''); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label" for="body">Body</label>
                <textarea class="form-control" id="body" name="body" rows="4"><?php
                    echo e($editing ? $editing['body'] : '');
                ?></textarea>
            </div>

            <button class="btn btn-primary" type="submit">
                <?php echo $editing ? 'Update' : 'Create'; ?>
            </button>

            <?php if ($editing): ?>
                <a class="btn btn-outline-secondary"
                   href="<?php echo e(url('index.php?module=notes_crud')); ?>">Cancel</a>
            <?php endif; ?>
        </form>
    </div>
</div>
```

The hidden `id` is the clever part: `0` means "create", any other value means "update this row". One form, two jobs.

## 8.5 The table and the delete button

```php
<table class="table table-striped align-middle mb-0">
    <thead>
        <tr><th>#</th><th>Title</th><th>Body</th><th>Created</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($notes as $note): ?>
            <tr>
                <td><?php echo (int) $note['id']; ?></td>
                <td><?php echo e($note['title']); ?></td>
                <td class="small text-muted"><?php echo e(str_limit($note['body'], 60)); ?></td>
                <td class="small"><?php echo e($note['created_at']); ?></td>
                <td class="text-nowrap">
                    <?php
                    $editUrl = url('index.php?module=notes_crud&edit=' . (int) $note['id']);
                    ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?php echo e($editUrl); ?>">
                        <i class="bi bi-pencil"></i>
                    </a>

                    <form method="post" class="d-inline"
                          onsubmit="return confirm('Delete this note?');">
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
    </tbody>
</table>
```

Why a form for delete instead of a link? Because a link can be triggered by a crawler or by an image tag on another site. A POST form with a token cannot.

## 8.6 Pagination

```php
<?php if ($page['pages'] > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <span class="small text-muted">
            Page <?php echo $page['page']; ?> of <?php echo $page['pages']; ?>
            (<?php echo $page['total']; ?> notes)
        </span>
        <span>
            <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
                <a class="btn btn-sm <?php echo $i === $page['page']
                        ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                   href="<?php echo e(url('index.php?module=notes_crud&page=' . $i)); ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </span>
    </div>
<?php endif; ?>
```

## 8.7 Test all four operations

Before you move on, walk through this list. Each step catches a different class of bug.

1. **Create.** Submit an empty title. A red message appears and nothing is saved. Submit a real title. A green message appears and the row is in the table.
2. **Read.** Reload the page. The row is still there and appears once — not twice. (If it appears twice, you are missing the redirect after the write.)
3. **Update.** Click the pencil. The form is filled in and the button says Update. Change the title and save. Both the table and the database show the new value.
4. **Delete.** Click the bin. The browser asks for confirmation, then the row disappears.
5. **Pagination.** Add eleven notes. The footer shows "Page 1 of 2" and page two loads.
6. **Token.** Open the page in a second tab, log out or delete the session cookie, then submit the form. You should get a 403 message, not a saved row.

## 8.8 Small improvements worth adding later

- **Search.** Add a text input named `q`, append `AND (title LIKE ? OR body LIKE ?)` to the query, and pass two `'%' . $q . '%'` bindings.
- **Sorting.** Allow only a fixed list of column names, then use `db_ident()` before putting one into `ORDER BY`.
- **Soft delete.** Add a `deleted_at` column, set it instead of deleting, and add `WHERE deleted_at IS NULL` to every query.
- **Audit.** Add `created_by` and fill it from `$_SESSION['user_id']`.

<!-- pagebreak -->

# 9. Exporting to PDF, Excel and Word

A web page is convenient for reading and terrible for archiving. Sooner or later someone asks for a PDF or a spreadsheet. OpenFrame does not bundle a report engine, but it works with the standard PHP libraries.

## 9.1 One-time setup: Composer

Composer is the PHP package manager. Install it once, in the application folder:

```text
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

You now have `composer.phar`. Install a library with:

```text
php composer.phar require mpdf/mpdf
```

Then make the autoloader available to the whole application. In `index.php`, right after `config.php`:

```php
$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}
```

The `is_file()` check means the application still runs if `vendor/` is missing.

## 9.2 The rule that saves hours

Every export has the same shape:

1. clear the output buffer;
2. set the response headers;
3. write the file to `php://output` or `Output()`;
4. `exit`.

Clearing the buffer is not optional. OpenFrame buffers output so it can send a 404 after the menu. Without clearing, your PDF starts with the HTML of the navbar and the file is corrupt.

```php
while (ob_get_level() > 0) {
    ob_end_clean();
}
```

## 9.3 A PDF with mPDF

```text
php composer.phar require mpdf/mpdf
```

```php
<?php
$visitors = db_rows('SELECT full_name, purpose, entry_time FROM visits ORDER BY id DESC');

$html  = '<h1>Visitors report</h1>';
$html .= '<table border="1" cellpadding="6" width="100%">';
$html .= '<thead><tr><th>Name</th><th>Purpose</th><th>Time</th></tr></thead><tbody>';

foreach ($visitors as $row) {
    $html .= '<tr>'
           . '<td>' . e($row['full_name']) . '</td>'
           . '<td>' . e($row['purpose']) . '</td>'
           . '<td>' . e($row['entry_time']) . '</td>'
           . '</tr>';
}

$html .= '</tbody></table>';

while (ob_get_level() > 0) {
    ob_end_clean();
}

$mpdf = new \Mpdf\Mpdf([
    'tempDir' => OPENFRAME_PATH . '/storage/tmp',
    'format'  => 'A4',
]);

$mpdf->SetTitle('Visitors report');
$mpdf->WriteHTML($html);
$mpdf->Output('visitors.pdf', \Mpdf\Output\Destination::INLINE);
exit;
```

Create the temporary folder once and make sure it is writable:

```php
if (!is_dir(OPENFRAME_PATH . '/storage/tmp')) {
    mkdir(OPENFRAME_PATH . '/storage/tmp', 0775, true);
}
```

For Arabic, add three options and set the direction:

```php
$mpdf = new \Mpdf\Mpdf([
    'mode'             => 'utf-8',
    'format'           => 'A4',
    'autoScriptToLang' => true,
    'autoLangToFont'   => true,
    'tempDir'          => OPENFRAME_PATH . '/storage/tmp',
]);

$mpdf->SetDirectionality('rtl');
$mpdf->WriteHTML('<html dir="rtl"><body>مرحبا بكم</body></html>');
```

## 9.4 An Excel file with PhpSpreadsheet

```text
php composer.phar require phpoffice/phpspreadsheet
```

```php
<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$rows = db_rows('SELECT full_name, purpose, entry_time FROM visits ORDER BY id DESC');

$book  = new Spreadsheet();
$sheet = $book->getActiveSheet();
$sheet->setTitle('Visits');

$sheet->fromArray(['Name', 'Purpose', 'Time'], null, 'A1');
$sheet->getStyle('A1:C1')->getFont()->setBold(true);

$line = 2;

foreach ($rows as $row) {
    $sheet->setCellValue('A' . $line, $row['full_name']);
    $sheet->setCellValue('B' . $line, $row['purpose']);
    $sheet->setCellValue('C' . $line, $row['entry_time']);
    $line++;
}

$sheet->getColumnDimension('A')->setWidth(32);
$sheet->getColumnDimension('B')->setWidth(32);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->freezePane('A2');

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="visits-' . date('Ymd') . '.xlsx"');

$writer = new Xlsx($book);
$writer->save('php://output');

$book->disconnectWorksheets();
exit;
```

One caution: a value that begins with `=`, `+`, `-` or `@` is treated as a formula by Excel. When the value comes from a visitor, write it as text explicitly:

```php
use PhpOffice\PhpSpreadsheet\Cell\DataType;

$sheet->setCellValueExplicit('A' . $line, $row['full_name'], DataType::TYPE_STRING);
```

## 9.5 A Word file with PHPWord

```text
php composer.phar require phpoffice/phpword
```

```php
<?php
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

$rows = db_rows('SELECT full_name, purpose FROM visits ORDER BY id DESC');

$document = new PhpWord();
$section  = $document->addSection();

$section->addText('Visitors report', ['bold' => true, 'size' => 18]);
$section->addText('Generated ' . now(), ['size' => 9, 'color' => '666666']);
$section->addTextBreak();

$table = $section->addTable(['borderSize' => 6, 'borderColor' => 'BFBFBF']);

$table->addRow();
$table->addCell(5000)->addText('Name', ['bold' => true]);
$table->addCell(5000)->addText('Purpose', ['bold' => true]);

foreach ($rows as $row) {
    $table->addRow();
    $table->addCell(5000)->addText($row['full_name']);
    $table->addCell(5000)->addText($row['purpose']);
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="visits-' . date('Ymd') . '.docx"');

$writer = IOFactory::createWriter($document, 'Word2007');
$writer->save('php://output');
exit;
```

For right-to-left Arabic, mark the paragraph as bidirectional:

```php
$section->addText('مرحبا بكم', ['rtl' => true], ['bidi' => true]);
```

## 9.6 PHPExcel is retired

You may find old code that uses PHPExcel. It no longer works on PHP 8 and receives no fixes. PhpSpreadsheet is its official replacement, and the code is similar: `PHPExcel` becomes `Spreadsheet`, and `PHPExcel_IOFactory` becomes `IOFactory`. Migrate rather than patch.

## 9.7 Which format should I use?

| Need | Format | Library |
|---|---|---|
| Printable report for email or archiving | PDF | mPDF |
| Data someone will filter, sort or chart | Excel (`.xlsx`) | PhpSpreadsheet |
| Editable letter or report | Word (`.docx`) | PHPWord |
| Simple data exchange, huge exports | CSV | Plain PHP (`fputcsv`) |

For a very large export, CSV is the fastest and uses almost no memory. For anything that must look identical on every machine, PDF is the safe choice.

<!-- pagebreak -->

# 10. Adding users and permissions

OpenFrame has no built-in login. This chapter shows a small, complete pattern you can adapt. It uses two levels — a normal user and an administrator — which covers most internal applications.

## 10.1 The users table

```sql
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(60)  NOT NULL UNIQUE,
  full_name     VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  level         VARCHAR(20)  NOT NULL DEFAULT 'user',
  created_at    DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Never store the password itself. Store a hash produced by `password_hash()`, and check it with `password_verify()`.

## 10.2 Creating the first account

Because there is no account yet, run this once from the command line or from a temporary page:

```php
<?php
db_insert('users', [
    'username'      => 'admin',
    'full_name'     => 'System Administrator',
    'password_hash' => password_hash('ChangeMe123!', PASSWORD_DEFAULT),
    'level'         => 'admin',
    'created_at'    => now(),
]);

echo 'Administrator created.';
```

Delete or disable that page immediately afterwards.

## 10.3 The sign-in page

`modules/login/index.php`:

```php
<?php
if (is_post()) {
    csrf_check();

    $user = db_row('SELECT * FROM users WHERE username = ?', [input('username')]);

    if ($user && password_verify(input('password'), $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']    = (int) $user['id'];
        $_SESSION['user_level'] = $user['level'];
        flash('success', 'Welcome back, ' . $user['full_name'] . '.');
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
    <input class="form-control mb-3" name="password" type="password"
           placeholder="Password" required>
    <button class="btn btn-primary w-100" type="submit">Sign in</button>
</form>
```

Note `session_regenerate_id(true)`: it gives the visitor a new session id after login, which prevents a class of session-fixation attacks. It costs one line.

## 10.4 Protecting pages

Add three small helpers to `includes/helpers.php`:

```php
function current_user()
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return db_find('users', (int) $_SESSION['user_id']);
}

function require_login()
{
    if (!current_user()) {
        redirect(url('index.php?module=login'));
    }
}

function require_admin()
{
    require_login();

    $user = current_user();

    if ($user['level'] !== 'admin') {
        abort(403, 'You do not have permission to open this page.');
    }
}
```

Then put one line at the top of every protected module:

```php
require_admin();     // administrators only
require_login();     // any signed-in user
```

## 10.5 Hiding links is not protection

You may be tempted to hide menu items for normal users:

```php
if ($_SESSION['user_level'] === 'admin') {
    echo '<a href="…">Users</a>';
}
```

That improves the interface, but it is **not** security: the URL still works if someone types it. Always pair hidden links with `require_admin()` inside the module.

## 10.6 A logout link

```php
<?php
if (is_post()) {
    csrf_check();
    $_SESSION = [];
    session_destroy();
    redirect(url('index.php?module=login'));
}
?>

<form method="post" class="d-inline">
    <?php echo csrf_field(); ?>
    <button class="btn btn-link p-0" type="submit">Sign out</button>
</form>
```

Read the next chapter before you go live — it covers the remaining essentials in one checklist.

<!-- pagebreak -->

# 11. Arabic and right-to-left layouts

OpenFrame ships as an English, left-to-right interface. Making it bilingual takes four small changes.

## 11.1 Store the language in the session

```php
<?php
$available = ['en', 'ar'];

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';

if (!in_array($lang, $available, true)) {
    $lang = 'en';
}

$_SESSION['lang'] = $lang;
```

Place this in `includes/config.php` after `session_start()`, or in a new `includes/lang.php` that `index.php` requires.

## 11.2 Keep the words in two files

`lang/en.php`:

```php
<?php
return [
    'nav.home'     => 'Home',
    'nav.visitors' => 'Visitors',
    'common.save'  => 'Save',
];
```

`lang/ar.php`:

```php
<?php
return [
    'nav.home'     => 'الرئيسية',
    'nav.visitors' => 'الزوار',
    'common.save'  => 'حفظ',
];
```

A tiny loader makes them available everywhere:

```php
function lang($key, $default = '')
{
    static $words = null;

    if ($words === null) {
        $file  = OPENFRAME_PATH . '/lang/' . ($_SESSION['lang'] ?? 'en') . '.php';
        $words = is_file($file) ? require $file : [];
    }

    return $words[$key] ?? ($default !== '' ? $default : $key);
}
```

Then use `lang('nav.home')` instead of the English word.

## 11.3 Set the page direction

In `includes/header.php`, replace the hard-coded `<html lang="en">` with:

```php
<?php $isRtl = (($_SESSION['lang'] ?? 'en') === 'ar'); ?>
<!DOCTYPE html>
<html lang="<?php echo e($_SESSION['lang'] ?? 'en'); ?>"
      dir="<?php echo $isRtl ? 'rtl' : 'ltr'; ?>">
```

Bootstrap includes an RTL build. Copy `bootstrap.rtl.min.css` into `assets/css/` and choose the file:

```php
<?php $bootstrap = $isRtl ? 'bootstrap.rtl.min.css' : 'bootstrap.min.css'; ?>
<link rel="stylesheet" href="<?php echo e(url('assets/css/' . $bootstrap)); ?>">
```

Everything else — margins, floats, dropdown alignment — flips automatically because Bootstrap reads the `dir` attribute.

## 11.4 The language switch

Keep the visitor on the same page when they switch:

```php
<a href="<?php echo e(url('index.php?module=' . rawurlencode($activeSlug) . '&lang=ar')); ?>">
    العربية
</a>
```

## 11.5 Arabic in the database

Three conditions must all be true:

1. the table is `utf8mb4`;
2. the connection uses `utf8mb4` (OpenFrame's default);
3. the page is UTF-8 (`<meta charset="utf-8">`, already in `header.php`).

If Arabic appears as question marks, one of the three is wrong. A query like `SELECT @@character_set_connection` should return `utf8mb4`.

## 11.6 Arabic in PDF and Excel

Excel handles Arabic without extra work; keep `setCellValue` (not an ASCII-only writer) and the text is stored as UTF-8. For PDF, mPDF needs the language options shown in chapter 9.3 and `SetDirectionality('rtl')`. Always check the output on a real Arabic document — fonts and shaping are the most common source of "boxes" in PDFs.

<!-- pagebreak -->

# 12. Keeping your site safe

This chapter is a checklist, not a lecture. Each line is a habit that prevents a whole class of problems.

## 12.1 Escape everything you print

```php
<p><?php echo e($row['comment']); ?></p>
```

`e()` turns `<` into `&lt;`, so a visitor cannot inject HTML or JavaScript. Use it for attributes too:

```php
<input value="<?php echo e($row['name']); ?>">
```

The only time you skip `e()` is when the value is a number you cast with `(int)` or a string you generated yourself.

## 12.2 Bind every SQL value

```php
db_rows('SELECT * FROM users WHERE email = ?', [$email]);
```

Never build SQL by joining strings with user input. Table and column names cannot be bound, so choose them from a fixed list — OpenFrame's `db_ident()` refuses anything that is not a plain name.

## 12.3 Use a token on every form

```php
<form method="post">
    <?php echo csrf_field(); ?>
    ...
</form>
```

```php
if (is_post()) {
    csrf_check();
    ...
}
```

Without this, another website can make a logged-in visitor submit a form they never saw.

## 12.4 Validate uploads on the server

Check the size, the real MIME type and the extension. Rename the file yourself, and store it in a folder that cannot execute PHP. The full recipe is in chapter 15 of the README.

## 12.5 Treat ids as numbers

```php
$id = (int) input('id');
```

A URL parameter is text, even when it looks like a number.

## 12.6 Keep secrets out of the repository

If you publish your code, do not commit real database passwords. Load them from environment variables or from a file that is not tracked, before `config.php` runs.

## 12.7 Turn debugging off in production

```php
'error_reporting' => E_ALL,
'display_errors'  => 0,
'log_errors'      => 1,
```

Error messages can reveal file paths, table names and query fragments. Log them; do not print them.

## 12.8 Use HTTPS and secure cookies

```php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
```

`httponly` keeps JavaScript from reading the session cookie; `secure` stops it travelling over plain HTTP.

## 12.9 The five-minute review before launch

- [ ] Every page that shows data uses `e()`.
- [ ] Every query uses `?`.
- [ ] Every POST branch calls `csrf_check()`.
- [ ] Every private module calls `require_login()` or `require_admin()`.
- [ ] Uploads are renamed and stored outside executable paths.
- [ ] `display_errors` is off.
- [ ] The database user has only the permissions it needs.
- [ ] You tested a wrong URL and saw a clean 404.

<!-- pagebreak -->

# 13. When something goes wrong

Most problems have a small number of causes. Find the symptom in the left column.

| Symptom | Likely cause | What to do |
|---|---|---|
| Blank white page | A PHP error that is not displayed | Turn on `display_errors`, check the error log, reload |
| "404 — Module not found" | Folder has no `info.php`, or `enabled` is false, or the slug is mistyped | Check the folder name and `info.php` |
| Module is in the menu but the URL 404s | Folder name contains spaces or capitals | Rename to lowercase letters, digits, `_` or `-` |
| No styling at all | The `assets` folder was not copied | Copy the whole folder |
| Styles work but images do not | Wrong path | Use `url('assets/img/logo.png')`, not `/assets/...` |
| "Database connection failed" | MySQL stopped, wrong password, wrong database name | Start MySQL, recheck the `DB_*` lines |
| Arabic appears as `????` | Charset not `utf8mb4` | Fix the column charset and `DB_CHARSET` |
| A form saves twice | No redirect after the write | Add `redirect()` after a successful insert |
| Flash message never shows | No redirect, or the session expired | Redirect after `flash()` |
| "Invalid or expired form token" | The token expired or cookies are blocked | Reload the page and submit again |
| PDF download will not open | HTML was included in the file | Clear buffers with `ob_end_clean()` and `exit` |
| Excel shows `#NAME?` | A value starting with `=` was treated as a formula | Write visitor text as `TYPE_STRING` |
| Upload fails with no message | File larger than `upload_max_filesize` | Raise the limit in `php.ini` and restart |
| Changes do not appear | Opcache is serving an old file | Restart Apache or PHP-FPM |
| `Class "…" not found` | Composer autoloader not loaded | Require `vendor/autoload.php` in `index.php` |

## 13.1 Reading a PHP error

A typical error looks like this:

```text
Fatal error: Uncaught PDOException: SQLSTATE[42S02]:
Base table or view not found: 1146 Table 'openframe.notes' doesn't exist
in D:\wamp64\www\openframe\includes\db.php:78
```

Read it from the end: the file and line that failed, then the message, then the cause. Here the table is named `notes` but the code expects `demo_notes`. Fix the query or rename the table.

## 13.2 Asking for help effectively

When you ask someone (or a search engine) for help, include:

1. the exact error message, including the file and line;
2. the URL you opened;
3. the PHP and MySQL versions;
4. the smallest snippet of code that reproduces the problem.

Nine times out of ten, writing those four things makes the cause obvious.

<!-- pagebreak -->

# 14. Publishing your site

## 14.1 Before you upload

1. Set `appversion` to a meaningful value.
2. Turn `display_errors` off and `log_errors` on.
3. Change the database password and update `config.php`.
4. Create the database and import your tables on the server.
5. If you use Composer, run `composer install --no-dev --optimize-autoloader` on the server.
6. Upload `uploads/` only if it contains data you need; otherwise create an empty folder with the right permissions.
7. Check that `uploads/` and `storage/tmp/` are writable, and that nothing else is.

## 14.2 File permissions

```text
Directories: 755
Files:       644
Writable:    uploads/, storage/tmp/
```

On Linux, ownership matters more than mode: the web server user must own (or be able to write to) the writable folders.

## 14.3 Apache configuration

Most shared hosts already run Apache with `.htaccess` enabled. A minimal virtual host for your own server:

```apache
<VirtualHost *:80>
    ServerName reception.local
    DocumentRoot "D:/wamp64/www/openframe"

    <Directory "D:/wamp64/www/openframe">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

`Options -Indexes` stops visitors from browsing folders that have no `index.php`.

## 14.4 Nginx configuration

```nginx
server {
    listen 80;
    server_name reception.local;
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
}
```

## 14.5 Backups

Two things need backing up: the database and the `uploads/` folder. Code you can restore from your repository; visitor files and records you cannot.

A simple daily habit:

```text
mysqldump -h 127.0.0.1 -u root openframe > backup-2026-10-25.sql
```

Keep at least a week of copies, and test a restore once — a backup you have never restored is only a hope.

## 14.6 Going live checklist

- [ ] Home page loads over the real domain.
- [ ] A wrong module URL shows 404, not a stack trace.
- [ ] Login works, and the wrong password is rejected.
- [ ] A normal user cannot open an administrator page by typing the URL.
- [ ] A record can be created, edited and deleted.
- [ ] An export opens correctly in PDF, Excel or Word.
- [ ] Arabic (if used) displays correctly in the browser and in exports.
- [ ] Uploads arrive in a dated folder and cannot execute.
- [ ] The database backup runs.

<!-- pagebreak -->

# 15. Quick reference

## 15.1 Configuration

| Call | Result |
|---|---|
| `config()` | The whole settings array |
| `config('appname')` | One setting |
| `set_config('appname', 'X')` | Change it for this session |
| `e($value)` | Safe HTML output |
| `url('index.php?module=home')` | URL that works in any folder |

## 15.2 Request and safety

| Call | Result |
|---|---|
| `input('name')` | Trimmed POST value, else GET, else `''` |
| `is_post()` | `true` for a POST request |
| `csrf_field()` | Hidden token input for a form |
| `csrf_check()` | Stops with 403 if the token is wrong |
| `flash('success', 'Saved')` | Message for the next page |
| `redirect($url)` | Go somewhere else and stop |
| `abort(404, 'Not found')` | Error page and stop |
| `json_response($data)` | Send JSON and stop |
| `now()` | Current date and time for MySQL |
| `str_limit($text, 60)` | Shorten text with an ellipsis |

## 15.3 Database

| Call | Result |
|---|---|
| `db()` | The shared PDO connection |
| `db_row($sql, $params)` | One row, or `null` |
| `db_rows($sql, $params)` | Array of rows |
| `db_column($sql, $params)` | One value |
| `db_insert($table, $data)` | New id |
| `db_update($table, $data, $where, $params)` | Rows changed |
| `db_delete($table, $where, $params)` | Rows deleted |
| `db_find($table, $id)` | One row by primary key |
| `db_exists($table, $where, $params)` | `true` or `false` |
| `db_count($table, $where, $params)` | A number |
| `db_paginate($sql, $params, $page, $perPage)` | Rows plus paging info |
| `db_transaction($callback)` | Runs all-or-nothing |

## 15.4 Module metadata

| Key | Meaning |
|---|---|
| `name` | Menu label and page title |
| `description` | Text on the welcome page |
| `version` | Informational version |
| `icon` | Bootstrap Icons class, e.g. `bi-people` |
| `enabled` | `false` hides it and returns 404 |
| `order` | Menu position (default 100) |

## 15.5 Folder layout

```text
modules/
└── module_name/
    ├── info.php      metadata
    └── index.php     the screen
```

<!-- pagebreak -->

# 16. Glossary

**Auto-increment.** A database column that fills itself with the next number. Every OpenFrame table should have one `id`.

**Bootstrap.** A free CSS library that supplies buttons, tables, forms and the grid. OpenFrame bundles it, so no internet connection is needed.

**Buffer.** A holding area for output. OpenFrame buffers the page so it can still send a 404 status after the menu has been drawn.

**CRUD.** Create, Read, Update, Delete — the four operations of almost every data screen.

**CSRF.** Cross-Site Request Forgery. An attack where another site makes your logged-in visitor submit a form. A token per session prevents it.

**DTO / array.** In OpenFrame a database row is a plain PHP array, keyed by column name, for example `$row['full_name']`.

**Escaping.** Converting characters such as `<` into codes that the browser displays instead of interpreting. `e()` does this.

**Foreign key.** A column that points at the `id` of another table, with a rule for what happens when that row is deleted.

**Lazy connection.** Opening the database only when it is first used, so pages that do not need it still load.

**Migration.** A script that changes the database structure. OpenFrame has no migration tool; a `.sql` file is enough.

**Module.** A folder in `modules/` containing `info.php` and `index.php`. It becomes one page and one menu link.

**PDO.** PHP's standard database interface. It supports prepared statements, which separate SQL from data.

**Placeholder.** The `?` in a query. The real value is supplied separately and can never change the query's structure.

**Prepared statement.** A query sent to the database in two steps: structure first, values second. This is what makes placeholders safe.

**RTL.** Right-to-left, the writing direction of Arabic and Hebrew. Bootstrap ships an RTL stylesheet.

**Session.** A small store of data that follows one visitor between pages. OpenFrame keeps the settings and flash messages there.

**Slug.** The short name used in a URL. For OpenFrame it is the module's folder name.

**Transaction.** A group of database changes that all succeed or all fail together.

**UTF-8 / utf8mb4.** Character encodings that can store every language, including Arabic and emoji. Use `utf8mb4` for MySQL.

<!-- pagebreak -->

# Where to go next

You now know enough to build a real application. A sensible next step is to build one small complete feature end to end — a single table, a list, a form, and one report — rather than starting with the largest screen.

Three habits will keep the project healthy:

1. **One module, one job.** A module that lists visitors and a module that edits a visitor are easier to understand than one module that does everything.
2. **Write the table first.** Decide the columns, then the screen. It is much cheaper than the reverse.
3. **Test the four CRUD operations after every change.** Create, read, update, delete — plus pagination. Most regressions show up there.

For the exact signature of any function, open `README.md` and use the function reference. For a working example you can copy, open `docs/examples/notes_crud/`.

OpenFrame is small on purpose. Keep your application equally direct: plain PHP, clear SQL, one screen at a time.
