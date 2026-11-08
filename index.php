<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0);
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

/* ------------------------------------------------------------------
 * Language handling — process ?lang= parameter FIRST so it is available
 * for all subsequent redirects and layout rendering.
 * ------------------------------------------------------------------ */
if (isset($_GET['lang'])) {
    $requestedLang = $_GET['lang'];
    $available = get_available_languages();
    if (in_array($requestedLang, $available, true)) {
        $_SESSION['lang'] = $requestedLang;
    }
}

/* Load language translations (en, ar, or any other added to /lang) */
load_lang();

/* Ensure TOTP columns exist (migration for existing databases) */
try {
    db_exec('ALTER TABLE ' . db_table('users') . ' ADD COLUMN otp_secret VARCHAR(32) NULL');
} catch (Throwable $ex) {
    // Column likely already exists, ignore
}
try {
    db_exec('ALTER TABLE ' . db_table('users') . ' ADD COLUMN otp_enabled TINYINT(1) NOT NULL DEFAULT 0');
} catch (Throwable $ex) {
    // Column likely already exists, ignore
}

/* Handle DevTools toggle from footer (superadmin/admin only) */
if (isset($_GET['toggle_devtool'])) {
    $user = current_user();
    if ($user && in_array($user['level'], ['superadmin', 'admin'], true)) {
        $_SESSION['devtool_disabled'] = empty($_SESSION['devtool_disabled']) ? 1 : 0;
    }
    $redirect = 'index.php';
    $params = array();
    if (isset($_GET['module'])) {
        $params['module'] = $_GET['module'];
    }
    if (!empty($_SESSION['lang'])) {
        $params['lang'] = $_SESSION['lang'];
    }
    if (!empty($params)) {
        $redirect .= '?' . http_build_query($params);
    }
    redirect($redirect);
    exit;
}

/* ------------------------------------------------------------------
 * TOTP gate — superadmin / admin must verify OTP before any module.
 * Regular users skip OTP. Pending OTP is not a full login.
 * ------------------------------------------------------------------ */
$current = current_module();
$otpPendingId = (int) ($_SESSION['otp_pending_user_id'] ?? 0);

if ($otpPendingId > 0) {
    if ($current !== 'otp') {
        $params = array('module' => 'otp');
        if (!empty($_SESSION['lang'])) {
            $params['lang'] = $_SESSION['lang'];
        }
        redirect(url('index.php?' . http_build_query($params)));
    }
} else {
    $authed = current_user();
    if ($authed && otp_is_privileged($authed) && empty($_SESSION['otp_verified'])) {
        $_SESSION['otp_pending_user_id'] = (int) $authed['id'];
        unset($_SESSION['user_id'], $_SESSION['user_level'], $_SESSION['user_name']);
        $params = array('module' => 'otp');
        if (!empty($_SESSION['lang'])) {
            $params['lang'] = $_SESSION['lang'];
        }
        redirect(url('index.php?' . http_build_query($params)));
    }

    /* Landing page redirect for unauthenticated users.
       Set landing_page to false to disable this behavior. */
    $landing = $_SESSION['landing_page'] ?? 'login';
    if ($landing !== false && !$authed) {
        if ($current !== $landing) {
            $params = array('module' => $landing);
            if (!empty($_SESSION['lang'])) {
                $params['lang'] = $_SESSION['lang'];
            }
            redirect(url('index.php?' . http_build_query($params)));
        }
    }
}

require __DIR__ . '/includes/header.php';

load_module();

require __DIR__ . '/includes/footer.php';
