<?php
/**
 * OpenFrame — Routes
 * ------------------
 * The router reads the modules folder automatically (through modules.php)
 * and exposes:
 *
 *   get_routes()      -> associative array: slug => module view file
 *   current_module()  -> slug of the requested/active module
 *   load_module()     -> executes the view of the current module
 *
 * URLs use the query string: index.php?module=home
 */

/**
 * Build the route table from the enabled modules.
 *
 * @return array<string,string> slug => absolute path of the module view
 */
function get_routes()
{
    $routes = array();

    foreach (enabled_modules() as $slug => $module) {
        $routes[$slug] = $module['file'];
    }

    return $routes;
}

/**
 * Determine the module requested by the visitor.
 *
 *  - no ?module= parameter -> "home" (or the first enabled module)
 *  - valid ?module= slug   -> that module
 *  - unknown/disabled slug -> "" (load_module() then answers 404)
 *
 * The requested value is always validated against the route table, so
 * unknown or disabled modules can never be loaded.
 *
 * @return string
 */
function current_module()
{
    static $current = null;

    if ($current !== null) {
        return $current;
    }

    $routes    = get_routes();
    $requested = isset($_GET['module']) ? (string) $_GET['module'] : '';
    $requested = strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $requested));

    if ($requested !== '') {
        // Explicit request: only accept routes that really exist.
        $current = isset($routes[$requested]) ? $requested : '';

        return $current;
    }

    if (isset($routes['home'])) {
        $current = 'home';

        return $current;
    }

    // Last resort: first available route.
    $current = $routes ? (string) key($routes) : '';

    return $current;
}

/**
 * Print (require) the view of the current module inside the layout.
 */
function load_module()
{
    $slug   = current_module();
    $routes = get_routes();

    if ($slug === '' || !isset($routes[$slug])) {
        http_response_code(404);
        echo '<div class="alert alert-danger">';
        echo '<h1 class="h4 alert-heading">404 — Module not found</h1>';
        echo '<p class="mb-0">No enabled module matches the requested route.</p>';
        echo '</div>';

        return;
    }

    $file   = $routes[$slug];
    $module = get_module($slug);

    if (!is_file($file)) {
        http_response_code(404);
        echo '<div class="alert alert-danger">';
        echo '<h1 class="h4 alert-heading">404 — Module view missing</h1>';
        echo '<p class="mb-0">The route <strong>' . e($slug) . '</strong> has no <code>index.php</code> view.</p>';
        echo '</div>';

        return;
    }

    // Make the active module available to the view.
    $GLOBALS['openframe_module'] = $module;
    require $file;
}
