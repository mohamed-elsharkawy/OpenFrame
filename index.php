<?php
/**
 * Front Office — Entry point
 * --------------------------
 * Load order:
 *   1. config.php   -> session settings (appname, apppath, applogo, ...)
 *   2. db.php       -> PDO connection helpers
 *   3. lang.php     -> Arabic / English (session based, Arabic default)
 *   4. helpers.php  -> redirect, CSRF, flash messages
 *   5. modules.php  -> reads the /modules folder and each module's info.php
 *   6. routes.php   -> builds the routes from the enabled modules
 *   7. auth.php     -> login / permissions
 *   8. header.php   -> DOCTYPE, <head> (LTR or RTL Bootstrap), navbar
 *   9. module view  -> executed by load_module() (routes.php)
 *  10. footer.php   -> closing tags, Bootstrap JS
 *
 * There is no public landing page: visitors always start at the login
 * page, and the default module for authenticated users is the dashboard.
 *
 * URL: index.php?module=dashboard
 */

if (!defined('OPENFRAME')) {
    define('OPENFRAME', true);
}

// Buffer output so redirects and 403/404 codes still work after printing.
ob_start();

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/lang.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/modules.php';
require __DIR__ . '/includes/routes.php';
require __DIR__ . '/includes/auth.php';

handle_language_switch();

/* ------------------------------------------------------------------
 * Module metadata of the requested route (validates ?module=)
 * ------------------------------------------------------------------ */
$moduleName  = current_module();
$moduleMeta  = get_module($moduleName);
$moduleLayout = $moduleMeta && !empty($moduleMeta['layout']) ? $moduleMeta['layout'] : 'app';

/* ------------------------------------------------------------------
 * Access rules: no landing page + authentication + permissions
 * ------------------------------------------------------------------ */
if ($moduleName === '' || $moduleMeta === null) {
    http_response_code(404);
    $forbidden = false;
    $notFound = true;
} elseif ($moduleLayout !== 'auth' && !is_logged_in()) {
    // Not authenticated: everything goes to the login page.
    redirect(url('index.php?module=login'));
} elseif ($moduleName === 'login' && is_logged_in()) {
    // Already authenticated: the login page is not needed.
    redirect(url('index.php?module=dashboard'));
} else {
    $notFound = false;
    $perm = isset($moduleMeta['permission']) ? $moduleMeta['permission'] : null;
    $forbidden = ($perm !== null && $perm !== '') && !has_permission($perm);
}

/* ------------------------------------------------------------------
 * CSV exports bypass the HTML layout entirely
 * ------------------------------------------------------------------ */
$rawExport = isset($_GET['export']) && $_GET['export'] === 'csv';

/* ------------------------------------------------------------------
 * Render
 * ------------------------------------------------------------------ */
if ($moduleLayout === 'app' && !$rawExport) {
    require __DIR__ . '/includes/header.php';
}

if ($rawExport) {
    if ($moduleMeta === null || $forbidden) {
        http_response_code(403);
        exit('Export not allowed.');
    }
    load_module();
} elseif ($notFound) {
    echo '<div class="alert alert-danger">';
    echo '<h1 class="h4 alert-heading">404 — ' . e(lang('errors.not_found')) . '</h1>';
    echo '<p class="mb-0">' . e(lang('errors.not_found_msg')) . '</p>';
    echo '</div>';
} elseif ($forbidden) {
    http_response_code(403);
    echo '<div class="alert alert-warning text-center py-5">';
    echo '<i class="bi bi-shield-lock fs-1 d-block mb-2"></i>';
    echo '<h1 class="h5">' . e(lang('errors.forbidden')) . '</h1>';
    echo '<p class="mb-3">' . e(lang('errors.forbidden_msg')) . '</p>';
    echo '<a href="' . e(url('index.php?module=dashboard')) . '" class="btn btn-outline-primary">'
        . e(lang('common.back')) . '</a>';
    echo '</div>';
} else {
    load_module();
}

if ($moduleLayout === 'app' && !$rawExport) {
    require __DIR__ . '/includes/footer.php';
}
