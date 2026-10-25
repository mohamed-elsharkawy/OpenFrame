<?php
/**
 * OpenFrame — Helpers
 * --------------------
 * Small, dependency-free utilities shared by modules: redirects, CSRF
 * protection, flash messages, request input, JSON responses and errors.
 *
 * Load with: require_once __DIR__ . '/includes/helpers.php';
 */

/**
 * Redirect to a URL and stop the request.
 *
 *   redirect(url('index.php?module=home'));
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
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
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
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        $_SESSION['flash'] = array();
    }

    $_SESSION['flash'][] = array('type' => $type, 'message' => $message);
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
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return '';
    }

    $closeLabel = function_exists('lang') ? lang('common.close') : 'Close';

    $html = '';

    foreach ($_SESSION['flash'] as $item) {
        $type = isset($item['type']) && in_array($item['type'], array('success', 'danger', 'warning', 'info'), true)
            ? $item['type']
            : 'info';

        $message = isset($item['message']) ? $item['message'] : '';

        $html .= '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
            . e($message)
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="'
            . e($closeLabel) . '"></button></div>';
    }

    unset($_SESSION['flash']);

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
