<?php
/**
 * OpenFrame — Helpers
 * --------------------
 * Small, dependency-free utilities shared by modules: redirects, CSRF
 * protection, flash messages, request input, JSON responses and errors.
 *
 * Load with: require_once __DIR__ . '/includes/helpers.php';
 */

if (!defined('OPENFRAME')) {
    header('Location: ' . rtrim(OPENFRAME_URL ?? '', '/') . '/');
    exit;
}

/**
 * Redirect to a URL and stop the request.
 *
 *   redirect(url('index.php?module=home'));
 *
 * @param string $url
 * @return void
 */
function redirect($url)
{
    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        echo '<meta http-equiv="refresh" content="0;url=' . e($url) . '">';
    }

    exit;
}

/**
 * The CSRF token for the current session (created on first use).
 *
 * @return string
 */
function csrf_token()
{
    $token = app_session_get('csrf_token');

    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(32));
        app_session_set('csrf_token', $token);
    }

    return $token;
}

/**
 * Hidden input for a POST form.
 *
 *   <form method="post"><?php echo csrf_field(); ?> ... </form>
 *
 * @return string
 */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify the token of the current POST request; stops with 403 on failure.
 *
 * Call it at the top of every POST branch:  csrf_check();
 *
 * @return void
 */
function csrf_check()
{
    $sent = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';

    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        abort(403, 'Invalid or expired form token. Go back, reload the page and try again.');
    }
}

/**
 * Queue a flash message for the next page view.
 *
 *   flash('success', 'Saved successfully.');
 *
 * Types: success, danger, warning, info (Bootstrap alert colours).
 */
function flash($type, $message)
{
    $messages = app_session_get('flash');

    if (!is_array($messages)) {
        $messages = array();
    }

    $messages[] = array('type' => $type, 'message' => $message);

    app_session_set('flash', $messages);
}

/**
 * Render and clear all queued flash messages as Bootstrap alerts.
 *
 * header.php calls this automatically after opening <main>.
 *
 * @return string HTML
 */
function flash_render()
{
    $messages = app_session_get('flash');

    if (empty($messages) || !is_array($messages)) {
        return '';
    }

    $closeLabel = function_exists('lang') ? lang('common.close') : 'Close';

    $html = '';

    foreach ($messages as $item) {
        $type = isset($item['type']) && in_array($item['type'], array('success', 'danger', 'warning', 'info'), true)
            ? $item['type']
            : 'info';

        $message = isset($item['message']) ? $item['message'] : '';

        $html .= '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
            . e($message)
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="'
            . e($closeLabel) . '"></button></div>';
    }

    app_session_forget('flash');

    return $html;
}

/**
 * A trimmed GET/POST value (POST wins), or $default when absent.
 *
 *   $name = input('full_name');
 *
 * @return string
 */
function input($key, $default = '')
{
    if (isset($_POST[$key]) && !is_array($_POST[$key])) {
        return trim((string) $_POST[$key]);
    }

    if (isset($_GET[$key]) && !is_array($_GET[$key])) {
        return trim((string) $_GET[$key]);
    }

    return $default;
}

/**
 * TRUE when the current request is a POST.
 *
 * @return bool
 */
function is_post()
{
    return (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
}

/**
 * TRUE when the current request is an AJAX/fetch call.
 *
 * @return bool
 */
function is_ajax()
{
    $requested = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

    return $requested === 'xmlhttprequest'
        || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
}

/**
 * Send a JSON response and stop.
 *
 *   json_response(array('ok' => true));
 *
 * @param mixed $data
 * @param int   $status
 */
function json_response($data, $status = 200)
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Stop the request with an HTTP error page.
 *
 *   abort(404);
 *   abort(403, 'You are not allowed to view this page.');
 */
function abort($code = 404, $message = '')
{
    $code    = (int) $code;
    $titles  = array(
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        405 => 'Method not allowed',
        500 => 'Server error',
    );
    $title   = isset($titles[$code]) ? $titles[$code] : 'Error';
    $message = $message !== '' ? $message : 'The requested page could not be loaded.';

    http_response_code($code);

    /* When the layout already started, just print the alert inside it. */
    if (headers_sent()) {
        echo '<div class="alert alert-danger"><h1 class="h4 alert-heading">'
            . e($code . ' — ' . $title) . '</h1><p class="mb-0">' . e($message) . '</p></div>';
        exit;
    }

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<title>' . e($code . ' — ' . $title) . '</title>'
        . '<link rel="stylesheet" href="' . e(url('assets/css/bootstrap.min.css')) . '"></head>'
        . '<body class="bg-body-tertiary"><div class="container py-5"><div class="alert alert-danger">'
        . '<h1 class="h4 alert-heading">' . e($code . ' — ' . $title) . '</h1>'
        . '<p class="mb-0">' . e($message) . '</p></div></div></body></html>';
    exit;
}

/**
 * Scan the lang directory for available translation files.
 *
 * @return array  language codes (e.g. ['en', 'ar'])
 */
function get_available_languages()
{
    $langDir = defined('OPENFRAME_PATH') ? OPENFRAME_PATH . '/lang' : __DIR__ . '/../lang';

    if (!is_dir($langDir)) {
        return ['en'];
    }

    $langs = array();
    foreach (glob($langDir . '/*.php') as $file) {
        $langs[] = basename($file, '.php');
    }

    sort($langs);

    return $langs;
}

/**
 * Load the language translations for the current request.
 *
 * The language is taken from ?lang= if provided, otherwise from the session,
 * falling back to OPENFRAME_DEFAULT_LANG (default 'ar'). The result is cached
 * for the request.
 *
 * @return array  translation strings
 */
function load_lang()
{
    static $lang = null;

    if ($lang !== null) {
        return $lang;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $available = get_available_languages();
    $default   = defined('OPENFRAME_DEFAULT_LANG') ? OPENFRAME_DEFAULT_LANG : 'en';
    $langCode  = isset($_GET['lang']) ? $_GET['lang'] : app_session_get('lang', $default);

    if (!in_array($langCode, $available, true)) {
        $langCode = in_array($default, $available, true) ? $default : 'en';
    }

    app_session_set('lang', $langCode);

    $langFile = defined('OPENFRAME_PATH')
        ? OPENFRAME_PATH . '/lang/' . $langCode . '.php'
        : __DIR__ . '/../lang/' . $langCode . '.php';

    if (is_file($langFile)) {
        $lang = include $langFile;
    } else {
        $lang = array();
    }

    return $lang;
}

/**
 * Translate a language key.
 *
 *   echo lang('common.save');
 *
 * @param string $key
 * @param string $default
 * @return string
 */
function lang($key, $default = '')
{
    $translations = load_lang();

    if (isset($translations[$key])) {
        return $translations[$key];
    }

    return $default !== '' ? $default : $key;
}

/**
 * Current date/time in MySQL DATETIME format.
 *
 * @return string  e.g. 2026-10-25 14:30:00
 */
function now()
{
    return date('Y-m-d H:i:s');
}

/**
 * Cut a string to a maximum length, adding an ellipsis.
 *
 * @return string
 */
function str_limit($value, $limit = 80, $end = '…')
{
    $value = (string) $value;

    if (function_exists('mb_strlen') && mb_strlen($value, 'UTF-8') <= $limit) {
        return $value;
    }

    if (!function_exists('mb_strlen') && strlen($value) <= $limit) {
        return $value;
    }

    $cut = function_exists('mb_substr')
        ? mb_substr($value, 0, $limit, 'UTF-8')
        : substr($value, 0, $limit);

    return rtrim($cut) . $end;
}

/**
 * TRUE when the current request expects JSON (AJAX/fetch).
 * Kept as an alias for readability in views.
 *
 * @return bool
 */
function wants_json()
{
    return is_ajax();
}

/**
 * Prevent direct access to a module's index.php file.
 *
 * If a user opens a module file directly (e.g.
 * http://localhost/OpenFrame/modules/home/index.php) instead of going
 * through the front controller (http://localhost/OpenFrame/?module=home),
 * this function redirects them to the root index.php.
 *
 * Call it at the very top of every module index.php file:
 *
 *   require_framework();
 *
 * @return void
 */
function require_framework()
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $script = str_replace('\\', '/', $script);

    // If the requested script lives inside the modules/ folder, redirect to
    // front controller, EXCEPT for login module which needs direct access.
    if (preg_match('#/modules/[^/]+/index\.php$#i', $script)) {
        // Allow login module to be accessed directly (it's the entry point)
        if (strpos($script, 'modules/login/index.php') !== false) {
            return;
        }
        $base = rtrim(OPENFRAME_URL ?? '', '/');
        $redirectUrl = $base . '/';
        if (!headers_sent()) {
            header('Location: ' . $redirectUrl);
        } else {
            echo '<meta http-equiv="refresh" content="0;url=' . e($redirectUrl) . '">';
        }
        exit;
    }
}

/**
 * Read and load all modules from the modules directory.
 *
 * This is a wrapper around get_modules() provided so that modules can be
 * loaded from helper context as well.
 *
 * @return array<string,array> slug => module properties
 */
function load_modules()
{
    return get_modules();
}

/* ------------------------------------------------------------------
 * Authentication helpers
 * ------------------------------------------------------------------ */

/**
 * Get the currently logged-in user, or null if nobody is logged in.
 *
 * Result is cached for the request.
 *
 * @return array|null
 */
function current_user()
{
    static $user = null;

    if ($user !== null) {
        return $user;
    }

    if (empty(app_session_get('user_id'))) {
        return $user = null;
    }

    $user = db_row('SELECT * FROM ' . db_table('users') . ' WHERE id = ?', [(int) app_session_get('user_id')]);

    return $user;
}

/**
 * Require the user to be logged in. Redirects to the login module otherwise.
 *
 * @return void
 */
function require_login()
{
    if (!current_user()) {
        // Don't redirect if we're already on the login page (avoid redirect loop)
        $currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptName = str_replace('\\', '/', $currentScript);
        $isLoginPage = strpos($scriptName, 'modules/login/index.php') !== false;

        if (!$isLoginPage) {
            $landing = app_session_get('landing_page', 'login');
            redirect(url('index.php?module=' . $landing));
        }
    }
}

/**
 * Require the user to have a specific permission level.
 *
 * Levels: superadmin, admin, user
 *
 * @param string $level Required level
 * @return void
 */
function require_level($level)
{
    require_login();

    $user = current_user();

    if (!$user || $user['level'] !== $level) {
        abort(403, 'You do not have permission to open this page.');
    }
}

/**
 * Require the user to have one of several permission levels.
 *
 * @param array $levels Allowed levels
 * @return void
 */
function require_any_level(array $levels)
{
    require_login();

    $user = current_user();

    if (!$user || !in_array($user['level'], $levels, true)) {
        abort(403, 'You do not have permission to open this page.');
    }
}

/**
 * Log the current user out and redirect to the landing page.
 *
 * @return void
 */
function logout_user()
{
    app_session_forget(
        'user_id',
        'user_level',
        'user_name',
        'otp_verified',
        'otp_pending_user_id',
        'otp_setup_secret',
        'otp_attempts'
    );
    session_regenerate_id(true);

    $landing = app_session_get('landing_page', 'login');
    redirect(url('index.php?module=' . $landing));
}

/* ------------------------------------------------------------------
 * TOTP (Time-based One-Time Password) helpers
 * Implemented with hash_hmac (SHA-1) - no openssl required.
 * ------------------------------------------------------------------ */

/**
 * Generate a random TOTP secret key (base32 encoded).
 *
 * @param int $length Length of the secret in bytes (default 16 = 128 bits)
 * @return string Base32-encoded secret key
 */
function otp_generate_secret($length = 16)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    $cl = strlen($chars);
    for ($i = 0; $i < $length; $i++) {
        $secret .= $chars[random_int(0, $cl - 1)];
    }
    return $secret;
}

/**
 * Decode a base32-encoded secret to binary.
 *
 * @param string $secret Base32-encoded secret
 * @return string Binary secret
 */
function otp_base32_decode($secret)
{
    $secret = strtoupper((string) $secret);
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $map = [];
    for ($i = 0; $i < strlen($chars); $i++) {
        $map[$chars[$i]] = $i;
    }

    $bin = '';
    $val = 0;
    $bits = 0;

    for ($i = 0; $i < strlen($secret); $i++) {
        $c = $secret[$i];
        if (!isset($map[$c])) {
            continue;
        }
        $val = ($val << 5) | $map[$c];
        $bits += 5;
        if ($bits >= 8) {
            $bits -= 8;
            $bin .= chr(($val >> $bits) & 0xFF);
        }
    }

    return $bin;
}

/**
 * Generate a TOTP code for the given secret.
 *
 * @param string $secret Base32-encoded secret
 * @param int|null $time Unix timestamp (defaults to current time)
 * @return string 6-digit TOTP code
 */
function otp_generate_code($secret, $time = null)
{
    $time = $time ?? (int) floor(time() / 30);
    $key = otp_base32_decode($secret);
    $msg = '';
    for ($i = 7; $i >= 0; $i--) {
        $msg .= chr(($time >> ($i * 8)) & 0xFF);
    }
    $hash = hash_hmac('sha1', $msg, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $binary = substr($hash, $offset, 4);
    $unpacked = unpack('N', $binary);
    $binary = ($unpacked[1] & 0x7FFFFFFF);
    $code = $binary % 1000000;
    return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
}

/**
 * Verify a TOTP code against the secret.
 *
 * @param string $secret Base32-encoded secret
 * @param string $code 6-digit code to verify
 * @param int $window Time window tolerance (default 1 = ±30 seconds)
 * @return bool True if code is valid
 */
function otp_verify($secret, $code, $window = 1)
{
    if (empty($secret) || $code === '') {
        return false;
    }

    $now = (int) floor(time() / 30);
    $code = (string) $code;

    for ($i = -$window; $i <= $window; $i++) {
        if (otp_generate_code($secret, $now + $i) === $code) {
            return true;
        }
    }

    return false;
}

/**
 * Generate a TOTP provisioning URI for QR code.
 *
 * @param string $secret Base32-encoded secret
 * @param string $username Username
 * @param string $issuer Issuer name (default: OpenFrame)
 * @return string otpauth:// URI
 */
function otp_provisioning_uri($secret, $username, $issuer = 'OpenFrame')
{
    return sprintf(
        'otpauth://totp/%s:%s?secret=%s&issuer=%s',
        rawurlencode($issuer),
        rawurlencode($username),
        rawurlencode($secret),
        rawurlencode($issuer)
    );
}

/**
 * TRUE when the user must complete TOTP (superadmin or admin).
 *
 * @param array|null $user
 * @return bool
 */
function otp_is_privileged($user)
{
    return is_array($user) && in_array($user['level'] ?? '', array('superadmin', 'admin'), true);
}

/**
 * Complete login after password (users) or after TOTP (admins).
 *
 * @param array $user
 * @return void
 */
function otp_finish_login(array $user)
{
    session_regenerate_id(true);

    app_session_set('user_id', (int) $user['id']);
    app_session_set('user_level', $user['level']);
    app_session_set('user_name', $user['full_name']);
    app_session_set('otp_verified', 1);

    app_session_forget(
        'otp_pending_user_id',
        'otp_setup_secret',
        'otp_attempts'
    );
}

/**
 * Offline QR markup for a TOTP provisioning URI.
 * High-contrast HTML table (no CDN, no image API).
 *
 * @param string $data
 * @param int    $modulePx  Pixel size of one QR module
 * @return string
 */
function otp_qr_html($data, $modulePx = 6)
{
    $file = __DIR__ . '/qrcode.php';
    if (!function_exists('qrcode_html') && is_file($file)) {
        require_once $file;
    }

    if (!function_exists('qrcode_html')) {
        return '';
    }

    return qrcode_html($data, max(4, (int) $modulePx), 4);
}

/**
 * Encrypt a plain-text password using AES-256-CBC.
 *
 * The IV is prepended to the ciphertext and the result is base64-encoded
 * so it can be stored in a single VARCHAR column.
 *
 * @param string $plain
 * @return string  base64-encoded encrypted value (IV + ciphertext)
 */
function encrypt_password($plain)
{
    $key    = ENCRYPTION_KEY;
    $method = 'aes-256-cbc';
    $key    = substr(hash('sha256', $key, true), 0, 32);
    $iv     = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

    $cipher = openssl_encrypt($plain, $method, $key, OPENSSL_RAW_DATA, $iv);

    return base64_encode($iv . $cipher);
}

/**
 * Decrypt a password that was encrypted with encrypt_password().
 *
 * @param string $encrypted  base64-encoded value from encrypt_password()
 * @return string|false  plain-text password, or false on failure
 */
function decrypt_password($encrypted)
{
    $key    = ENCRYPTION_KEY;
    $method = 'aes-256-cbc';
    $key    = substr(hash('sha256', $key, true), 0, 32);

    $data   = base64_decode($encrypted, true);
    if ($data === false) {
        return false;
    }

    $ivLen   = openssl_cipher_iv_length($method);
    $iv      = substr($data, 0, $ivLen);
    $cipher  = substr($data, $ivLen);

    return openssl_decrypt($cipher, $method, $key, OPENSSL_RAW_DATA, $iv);
}