<?php
/**
 * Front Office — Small helpers: redirect, CSRF, flash messages.
 */

/**
 * Redirect and stop immediately.
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
 * CSRF token (per session).
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
 * Hidden input to place inside every POST form.
 *
 * @return string
 */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify the token of a POST request (dies with 403 on failure).
 */
function csrf_check()
{
    $sent = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';

    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        exit('<h1>403</h1><p>Invalid or expired form token. Go back and try again.</p>');
    }
}

/**
 * Store a flash message for the next page view.
 */
function flash($type, $message)
{
    $_SESSION['flash'][] = array('type' => $type, 'message' => $message);
}

/**
 * Print all pending flash messages as Bootstrap alerts and clear them.
 *
 * @return string HTML
 */
function flash_render()
{
    if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        return '';
    }

    $html = '';

    foreach ($_SESSION['flash'] as $item) {
        $type = in_array($item['type'], array('success', 'danger', 'warning', 'info'), true)
            ? $item['type']
            : 'info';

        $html .= '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
            . e($item['message'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="'
            . e(lang('common.close')) . '"></button></div>';
    }

    unset($_SESSION['flash']);

    return $html;
}

/**
 * Value of a GET/POST field (trimmed, or $default).
 *
 * @return string
 */
function input($key, $default = '')
{
    if (isset($_POST[$key])) {
        return trim((string) $_POST[$key]);
    }
    if (isset($_GET[$key])) {
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
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
