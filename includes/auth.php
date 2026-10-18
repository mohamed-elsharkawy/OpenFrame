<?php
/**
 * Front Office — Authentication & permissions
 * -------------------------------------------
 * Two user levels: "entry" (front desk) and "admin".
 * Permissions live in the database (permissions + user_level_permissions)
 * and are checked with has_permission($code). The admin level always
 * passes every check.
 */

/**
 * The logged-in user (id, username, full_name, level_code, ...), or null.
 *
 * @return array|null
 */
function current_user()
{
    static $user = null;
    static $loaded = false;

    if ($loaded) {
        return $user;
    }

    $loaded = true;

    $id = isset($_SESSION['frontoffice']['user_id']) ? (int) $_SESSION['frontoffice']['user_id'] : 0;
    if ($id <= 0) {
        return null;
    }

    $user = db_row(
        'SELECT u.id, u.username, u.full_name, u.level_id, u.active,
                l.code AS level_code, l.name_ar AS level_name_ar, l.name_en AS level_name_en
           FROM users u
           JOIN user_levels l ON l.id = u.level_id
          WHERE u.id = ? AND u.active = 1'
        , array($id)
    );

    if ($user === null) {
        unset($_SESSION['frontoffice']['user_id']);
    }

    return $user;
}

/**
 * TRUE when somebody is logged in.
 *
 * @return bool
 */
function is_logged_in()
{
    return current_user() !== null;
}

/**
 * TRUE for the "admin" level.
 *
 * @return bool
 */
function is_admin()
{
    $user = current_user();

    return $user !== null && $user['level_code'] === 'admin';
}

/**
 * Permission codes of the current user (admin gets everything).
 *
 * @return array
 */
function current_permissions()
{
    static $codes = null;

    if ($codes !== null) {
        return $codes;
    }

    $user = current_user();
    $codes = array();

    if ($user === null) {
        return $codes;
    }

    if ($user['level_code'] === 'admin') {
        $rows = db_rows('SELECT code FROM permissions');
    } else {
        $rows = db_rows(
            'SELECT p.code
               FROM user_level_permissions ulp
               JOIN permissions p ON p.id = ulp.permission_id
              WHERE ulp.level_id = ?',
            array($user['level_id'])
        );
    }

    foreach ($rows as $row) {
        $codes[] = $row['code'];
    }

    return $codes;
}

/**
 * TRUE when the current user holds a permission.
 *
 * @return bool
 */
function has_permission($code)
{
    if ($code === null || $code === '') {
        return true;
    }

    if (is_admin()) {
        return true;
    }

    return in_array($code, current_permissions(), true);
}

/**
 * Attempt a login. Returns true on success (session id is regenerated).
 *
 * @return bool
 */
function login($username, $password)
{
    $user = db_row(
        'SELECT u.id, u.password_hash
           FROM users u
          WHERE u.username = ? AND u.active = 1',
        array((string) $username)
    );

    if ($user === null || !password_verify((string) $password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['frontoffice']['user_id'] = (int) $user['id'];

    return true;
}

/**
 * Log out and destroy the session data.
 */
function logout()
{
    unset($_SESSION['frontoffice']['user_id']);
    unset($_SESSION['csrf_token']);
    session_regenerate_id(true);
}

/**
 * Send the visitor to the login page when not authenticated.
 */
function require_login()
{
    if (!is_logged_in()) {
        redirect(url('index.php?module=login'));
    }
}
