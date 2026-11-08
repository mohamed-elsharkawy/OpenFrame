<?php
/**
 * OpenFrame — Application configuration
 * -------------------------------------
 * Holds all information about the application using session variables
 * (appname, apppath, appurl, applogo, appversion, ...).
 *
 * Every request rebuilds the settings array inside the session, so derived
 * values (paths/URLs) always match the current install location, while any
 * value you change at runtime with set_config() survives across pages.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ------------------------------------------------------------------
 * Base filesystem path and base URL of the application
 * ------------------------------------------------------------------ */
$appPath = dirname(__DIR__);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$appUrl = ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '')
    ? ''
    : rtrim($scriptDir, '/');

/* ------------------------------------------------------------------
 * Database connection (read by includes/db.php)
 * Define these before this file loads to use different values.
 * ------------------------------------------------------------------ */
if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', 3306);
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'openframe');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', 'AdminNewp@ss01');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}
if (!defined('DB_PREFIX')) {
    define('DB_PREFIX', 'opf_');
}

/* Encryption key for reversible password encryption (AES-256-CBC).
   Change this to a random 32-character string in production. */
if (!defined('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', 'OpenFrameEncKey2026!@#$');
}

/* Application logo configuration */
if (!defined('APP_LOGO_PATH')) {
    define('APP_LOGO_PATH', $appUrl . '/assets/img/OpenFrame_Logo.png');
}
if (!defined('APP_LOGO_MAX_WIDTH')) {
    define('APP_LOGO_MAX_WIDTH', 70);
}
if (!defined('APP_LOGO_MAX_HEIGHT')) {
    define('APP_LOGO_MAX_HEIGHT', 60);
}

/* Application name and version constants */
if (!defined('APP_NAME')) {
    define('APP_NAME', 'OpenFrame');
}
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.0.0');
}

/* ------------------------------------------------------------------
 * Default application settings
 * ------------------------------------------------------------------ */
$settings = [
    'appname'    => APP_NAME,
    'apppath'    => $appPath,
    'appurl'     => $appUrl,
    'applogo'    => APP_LOGO_PATH,
    'appversion' => APP_VERSION,
    'appauthor'  => 'OpenFrame',
    'appdebug'   => true,
];

/* Merge: keep values previously stored in the session, add new keys */
if (!isset($_SESSION['openframe']) || !is_array($_SESSION['openframe'])) {
    $_SESSION['openframe'] = [];
}

foreach ($settings as $key => $value) {
    if (!array_key_exists($key, $_SESSION['openframe'])) {
        $_SESSION['openframe'][$key] = $value;
    }
}

/* Derived values are always refreshed for the current request */
$_SESSION['openframe']['apppath']    = $appPath;
$_SESSION['openframe']['appurl']     = $appUrl;
$_SESSION['openframe']['applogo']    = APP_LOGO_PATH;

/* Module selector — can be changed by admin to show/hide the module selector UI */
if (!isset($_SESSION['module_selector'])) {
    $_SESSION['module_selector'] = 'module';
}

/* Landing page — controls where unauthenticated users are redirected.
   Set to a module slug (e.g. 'login', 'home') or false to disable redirection. */
if (!isset($_SESSION['landing_page'])) {
    $_SESSION['landing_page'] = 'login';
}

/* DevTools protection — when set to 1, DevTools access is disabled for users.
   Default: protection ON (DevTools disabled). */
if (!isset($_SESSION['devtool_disabled'])) {
    $_SESSION['devtool_disabled'] = 1;
}

/* ------------------------------------------------------------------
 * Constants available everywhere (modules, routes, views)
 * ------------------------------------------------------------------ */
if (!defined('OPENFRAME_PATH')) {
    define('OPENFRAME_PATH', $appPath);
}
if (!defined('OPENFRAME_URL')) {
    define('OPENFRAME_URL', $appUrl);
}

/* ------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------ */

/**
 * Read a configuration value.
 *
 * config()            -> the whole settings array
 * config('appname')   -> a single value (or $default when missing)
 */
function config($key = null, $default = null)
{
    $settings = isset($_SESSION['openframe']) && is_array($_SESSION['openframe'])
        ? $_SESSION['openframe']
        : [];

    if ($key === null) {
        return $settings;
    }

    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

/**
 * Change (or add) a configuration value for the rest of the session.
 */
function set_config($key, $value)
{
    if (!isset($_SESSION['openframe']) || !is_array($_SESSION['openframe'])) {
        $_SESSION['openframe'] = [];
    }
    $_SESSION['openframe'][$key] = $value;
}

/**
 * Escape a value for safe output in HTML.
 */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Build an URL inside the application (works from root or sub-directory).
 *
 * Clean URLs: index.php?module= is converted to /?module= so links look like
 * /?module=home instead of /index.php?module=home.
 */
function url($path = '')
{
    $path = ltrim((string) $path, '/');

    // Convert index.php?module=... to /?module=... for cleaner URLs
    $path = preg_replace('#^index\.php\?#', '?', $path);

    $base = rtrim(OPENFRAME_URL, '/');

    if ($path === '') {
        return $base . '/';
    }

    if (strpos($path, '?') === 0) {
        return $base . '/' . $path;
    }

    return $base . '/' . $path;
}
