<?php
/**
 * OpenFrame — Entry point
 * -----------------------
 * Load order:
 *   1. config.php   -> session settings (appname, apppath, applogo, ...)
 *   2. modules.php  -> reads the /modules folder and each module's info.php
 *   3. routes.php   -> builds the routes from the enabled modules
 *   4. header.php   -> DOCTYPE, <head>, Bootstrap CSS, navbar
 *   5. module view  -> executed by load_module() (routes.php)
 *   6. footer.php   -> closing tags, footer, Bootstrap JS
 *
 * URL: index.php?module=home
 */

// Marks that the framework booted (guards the layout partials).
if (!defined('OPENFRAME')) {
    define('OPENFRAME', true);
}

// Buffer output so the router can still send a 404 status code
// after header.php has been printed.
ob_start();

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/modules.php';
require __DIR__ . '/includes/routes.php';

require __DIR__ . '/includes/header.php';

load_module();

require __DIR__ . '/includes/footer.php';
