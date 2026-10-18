<?php
/**
 * Front Office — Multi language (Arabic / English)
 * ------------------------------------------------
 * $_SESSION['lang'] holds the current language, "ar" by default.
 * Switch with ?lang=en / ?lang=ar (any page, the switcher links do it).
 * Strings live in lang/ar.php and lang/en.php and return arrays.
 */

define('OF_LANGUAGES', array('ar', 'en'));
define('OF_DEFAULT_LANG', 'ar');

/**
 * Current language code ("ar" by default).
 *
 * @return string
 */
function current_lang()
{
    $lang = isset($_SESSION['lang']) ? (string) $_SESSION['lang'] : '';

    return in_array($lang, OF_LANGUAGES, true) ? $lang : OF_DEFAULT_LANG;
}

/**
 * TRUE when the current language is written right-to-left.
 *
 * @return bool
 */
function is_rtl()
{
    return current_lang() === 'ar';
}

/**
 * Set the language for the session.
 *
 * @return string effective language
 */
function set_language($lang)
{
    $lang = strtolower((string) $lang);

    if (!in_array($lang, OF_LANGUAGES, true)) {
        $lang = OF_DEFAULT_LANG;
    }

    $_SESSION['lang'] = $lang;

    return $lang;
}

/**
 * Handle the ?lang= switch. Keeps the current module/page.
 */
function handle_language_switch()
{
    if (isset($_GET['lang'])) {
        set_language($_GET['lang']);
    } elseif (!isset($_SESSION['lang'])) {
        set_language(OF_DEFAULT_LANG);
    }
}

/**
 * URL of the current page in another language (for the switcher).
 *
 * @return string
 */
function lang_url($lang)
{
    $query = $_GET;
    $query['lang'] = $lang;

    return 'index.php?' . http_build_query($query);
}

/**
 * Load the translation array of the current language (English as fallback).
 *
 * @return array
 */
function load_translations()
{
    static $strings = null;

    if ($strings !== null) {
        return $strings;
    }

    $base = __DIR__ . '/../lang';
    $fallback = array();
    $file = $base . '/' . current_lang() . '.php';

    if (is_file($base . '/en.php')) {
        $fallback = include $base . '/en.php';
    }

    $strings = $fallback;

    if (current_lang() !== 'en' && is_file($file)) {
        $translated = include $file;
        if (is_array($translated)) {
            $strings = array_merge($strings, $translated);
        }
    }

    return $strings;
}

/**
 * Translate a key: lang('dashboard.title') or lang('common.dear', ['name' => 'Ali']).
 *
 * @return string
 */
function lang($key, $replace = array())
{
    $strings = load_translations();
    $text = isset($strings[$key]) ? (string) $strings[$key] : (string) $key;

    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }

    return $text;
}

/**
 * Translated month names for PHP-side date formatting.
 *
 * @return array
 */
function lang_months()
{
    $strings = load_translations();

    return isset($strings['months']) && is_array($strings['months'])
        ? $strings['months']
        : array('January', 'February', 'March', 'April', 'May', 'June', 'July',
            'August', 'September', 'October', 'November', 'December');
}

/**
 * Format "18 October 2026 14:30" using the current language.
 *
 * @param  string|int $timestamp MySQL datetime or unix timestamp
 * @return string
 */
function format_datetime($timestamp)
{
    if (!is_numeric($timestamp)) {
        $ts = strtotime((string) $timestamp);
        if ($ts === false) {
            return (string) $timestamp;
        }
    } else {
        $ts = (int) $timestamp;
    }

    return date('d', $ts) . ' ' . lang_months()[(int) date('n', $ts) - 1] . ' ' . date('Y  H:i', $ts);
}

/**
 * Format "18 October 2026".
 *
 * @param  string|int $timestamp
 * @return string
 */
function format_date($timestamp)
{
    if (!is_numeric($timestamp)) {
        $ts = strtotime((string) $timestamp);
        if ($ts === false) {
            return (string) $timestamp;
        }
    } else {
        $ts = (int) $timestamp;
    }

    return date('d', $ts) . ' ' . lang_months()[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

/**
 * Hours:minutes between two datetimes ("02:15").
 *
 * @return string
 */
function duration_between($from, $to)
{
    $a = strtotime((string) $from);
    $b = strtotime((string) $to);

    if ($a === false || $b === false || $b < $a) {
        return '—';
    }

    $minutes = (int) floor(($b - $a) / 60);

    return sprintf('%02d:%02d', (int) floor($minutes / 60), $minutes % 60);
}
