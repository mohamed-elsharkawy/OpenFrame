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
 * Per-application session namespace
 *
 * Several OpenFrame applications may be installed on the same web
 * server and share the browser's session cookie (same host). To keep
 * their data apart, EVERYTHING this app stores in the session lives
 * under a nested array:
 *
 *   $_SESSION['openframe_apps'][OPENFRAME_APP_KEY][...]
 *
 * OPENFRAME_APP_KEY defaults to the application's URL path
 * (e.g. "/openframe", or "root" when installed at the webroot).
 * Define it before this file is included to force a custom key.
 *
 * Never read or write top-level $_SESSION keys directly — use the
 * app_session_* helpers below.
 * ------------------------------------------------------------------ */
if (!defined('OPENFRAME_APP_KEY')) {
    define('OPENFRAME_APP_KEY', $appUrl !== '' ? $appUrl : 'root');
}

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

/* ------------------------------------------------------------------
 * One-time migration from the legacy flat session layout
 *
 * OpenFrame <= 1.0 stored keys (user_id, lang, flash, ...) directly in
 * $_SESSION. If such keys are still present (session created before
 * this change), move them into this app's namespace so nobody is
 * logged out after an upgrade.
 * ------------------------------------------------------------------ */
if (empty($_SESSION['openframe_apps_migrated'])) {
    $legacyKeys = array(
        'openframe', 'module_selector', 'landing_page', 'devtool_disabled',
        'lang', 'csrf_token', 'flash',
        'user_id', 'user_level', 'user_name',
        'otp_verified', 'otp_pending_user_id', 'otp_setup_secret', 'otp_attempts',
    );

    foreach ($legacyKeys as $legacyKey) {
        if (isset($_SESSION[$legacyKey]) && !app_session_has($legacyKey)) {
            app_session_set($legacyKey, $_SESSION[$legacyKey]);
        }
        unset($_SESSION[$legacyKey]);
    }

    $_SESSION['openframe_apps_migrated'] = true;
}

/* Merge: keep values previously stored in the session, add new keys */
$sessionSettings = app_session_get('openframe');

if (!is_array($sessionSettings)) {
    $sessionSettings = array();
}

foreach ($settings as $key => $value) {
    if (!array_key_exists($key, $sessionSettings)) {
        $sessionSettings[$key] = $value;
    }
}

/* Derived values are always refreshed for the current request */
$sessionSettings['apppath'] = $appPath;
$sessionSettings['appurl']  = $appUrl;
$sessionSettings['applogo'] = APP_LOGO_PATH;

app_session_set('openframe', $sessionSettings);

/* Module selector — can be changed by admin to show/hide the module selector UI */
if (!app_session_has('module_selector')) {
    app_session_set('module_selector', 'module');
}

/* Landing page — controls where unauthenticated users are redirected.
   Set to a module slug (e.g. 'login', 'home') or false to disable redirection. */
if (!app_session_has('landing_page')) {
    app_session_set('landing_page', 'login');
}

/* DevTools protection — when set to 1, DevTools access is disabled for users.
   Default: protection ON (DevTools disabled). */
if (!app_session_has('devtool_disabled')) {
    app_session_set('devtool_disabled', 1);
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

/* Default application language — used whenever the session has no language
   choice yet (first visit) or when a requested language is unavailable.
   Override by defining OPENFRAME_DEFAULT_LANG before this file loads. */
if (!defined('OPENFRAME_DEFAULT_LANG')) {
    define('OPENFRAME_DEFAULT_LANG', 'ar');
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
    $settings = app_session_get('openframe');

    if (!is_array($settings)) {
        $settings = array();
    }

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
    $settings = app_session_get('openframe');

    if (!is_array($settings)) {
        $settings = array();
    }

    $settings[$key] = $value;

    app_session_set('openframe', $settings);
}

/* ------------------------------------------------------------------
 * Per-app session namespace helpers
 *
 * All session data is stored under:
 *   $_SESSION['openframe_apps'][OPENFRAME_APP_KEY][...]
 *
 * so multiple OpenFrame applications on the same server (sharing one
 * PHP session cookie) never overwrite each other's state.
 * ------------------------------------------------------------------ */

/**
 * Ensure the namespace exists and return a copy of this app's session data.
 *
 * @return array  this application's session values
 */
function app_session_all()
{
    if (!isset($_SESSION['openframe_apps']) || !is_array($_SESSION['openframe_apps'])) {
        $_SESSION['openframe_apps'] = array();
    }

    $key = defined('OPENFRAME_APP_KEY') ? OPENFRAME_APP_KEY : 'root';

    if (!isset($_SESSION['openframe_apps'][$key]) || !is_array($_SESSION['openframe_apps'][$key])) {
        $_SESSION['openframe_apps'][$key] = array();
    }

    return $_SESSION['openframe_apps'][$key];
}

/**
 * Read a value from this application's session namespace.
 *
 *   $userId = app_session_get('user_id');
 *
 * @param string $key
 * @param mixed  $default  returned when the key does not exist
 * @return mixed
 */
function app_session_get($key, $default = null)
{
    $data = app_session_all();

    return array_key_exists($key, $data) ? $data[$key] : $default;
}

/**
 * Write a value into this application's session namespace.
 *
 *   app_session_set('user_id', 1);
 *
 * @param string $key
 * @param mixed  $value
 * @return void
 */
function app_session_set($key, $value)
{
    app_session_all(); // make sure the namespace exists

    $_SESSION['openframe_apps'][defined('OPENFRAME_APP_KEY') ? OPENFRAME_APP_KEY : 'root'][$key] = $value;
}

/**
 * TRUE when the key exists in this application's session namespace.
 *
 * @param string $key
 * @return bool
 */
function app_session_has($key)
{
    return array_key_exists($key, app_session_all());
}

/**
 * Remove one or more keys from this application's session namespace.
 *
 *   app_session_forget('user_id', 'user_level');
 *
 * @param string ...$keys
 * @return void
 */
function app_session_forget(...$keys)
{
    $key = defined('OPENFRAME_APP_KEY') ? OPENFRAME_APP_KEY : 'root';

    foreach ($keys as $name) {
        unset($_SESSION['openframe_apps'][$key][$name]);
    }
}

/**
 * Remove ALL keys from this application's session namespace
 * (other apps on the same server are not affected).
 *
 * @return void
 */
function app_session_flush()
{
    $key = defined('OPENFRAME_APP_KEY') ? OPENFRAME_APP_KEY : 'root';

    $_SESSION['openframe_apps'][$key] = array();
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

if (!app_session_has('lang')) {
	app_session_set('lang', OPENFRAME_DEFAULT_LANG);
}