<?php
/**
 * Front Office — Application configuration
 * ----------------------------------------
 * Session-based app settings + database constants.
 * Session key is "frontoffice" so it never clashes with another
 * OpenFrame app served from the same host.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ------------------------------------------------------------------
 * Database (MySQL / WAMP) — edit these five lines if needed
 * ------------------------------------------------------------------ */
if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', 3306);
    define('DB_NAME', 'frontoffice');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

/* Base filesystem path and base URL of the application */
$appPath = dirname(__DIR__);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$appUrl = ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '')
    ? ''
    : rtrim($scriptDir, '/');

/* ------------------------------------------------------------------
 * Default application settings (stored in the session)
 * ------------------------------------------------------------------ */
$settings = [
    'appname'    => 'Front Office',
    'appname_ar' => 'مكتب الاستقبال',
    'apppath'    => $appPath,
    'appurl'     => $appUrl,
    'applogo'    => $appUrl . '/assets/img/logo.png',
    'appversion' => '1.0.0',
    'appauthor'  => 'Front Office',
    'appdebug'   => true,
];

if (!isset($_SESSION['frontoffice']) || !is_array($_SESSION['frontoffice'])) {
    $_SESSION['frontoffice'] = [];
}

foreach ($settings as $key => $value) {
    if (!array_key_exists($key, $_SESSION['frontoffice'])) {
        $_SESSION['frontoffice'][$key] = $value;
    }
}

/* Derived values are always refreshed for the current request */
$_SESSION['frontoffice']['apppath'] = $appPath;
$_SESSION['frontoffice']['appurl']  = $appUrl;
$_SESSION['frontoffice']['applogo'] = $appUrl . '/assets/img/logo.png';

/* ------------------------------------------------------------------
 * Constants
 * ------------------------------------------------------------------ */
if (!defined('OPENFRAME_PATH')) {
    define('OPENFRAME_PATH', $appPath);
}
if (!defined('OPENFRAME_URL')) {
    define('OPENFRAME_URL', $appUrl);
}
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', $appPath . DIRECTORY_SEPARATOR . 'uploads');
}

/* ------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------ */

/**
 * Read a configuration value: config() / config('appname') / config('k', $default)
 */
function config($key = null, $default = null)
{
    $settings = isset($_SESSION['frontoffice']) && is_array($_SESSION['frontoffice'])
        ? $_SESSION['frontoffice']
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
    if (!isset($_SESSION['frontoffice']) || !is_array($_SESSION['frontoffice'])) {
        $_SESSION['frontoffice'] = [];
    }
    $_SESSION['frontoffice'][$key] = $value;
}

/**
 * Escape a value for safe output in HTML.
 */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Build a URL inside the application (works from root or sub-directory).
 */
function url($path = '')
{
    return OPENFRAME_URL . '/' . ltrim((string) $path, '/');
}

/**
 * Application name in the current language.
 */
function app_name()
{
    return current_lang() === 'ar' ? config('appname_ar') : config('appname');
}

/**
 * Escape a string for use inside a SQL LIKE pattern.
 */
function like_escape($value)
{
    return addcslashes((string) $value, '\\%_');
}
