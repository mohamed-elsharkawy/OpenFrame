<?php
/**
 * OpenFrame — Modules loader
 * ---------------------------
 * Reads every folder inside /modules. When a folder contains an info.php
 * file that returns an array, the module is registered with its properties
 * (name, description, version, icon, enabled, order, ...).
 *
 * info.php example:
 *
 *   <?php
 *   return [
 *       'name'        => 'Home',
 *       'description' => 'Default landing page.',
 *       'version'     => '1.0.0',
 *       'icon'        => 'bi-house-door',
 *       'enabled'     => true,
 *       'order'       => 0,
 *   ];
 */

/**
 * Return all registered modules, sorted by "order" then "name".
 * The result is cached for the current request.
 *
 * @return array<string,array> slug => module properties
 */
function get_modules()
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $base    = defined('OPENFRAME_PATH') ? OPENFRAME_PATH : dirname(__DIR__);
    $baseDir = $base . '/modules';
    $cache   = array();

    if (!is_dir($baseDir)) {
        return $cache;
    }

    $folders = glob($baseDir . '/*', GLOB_ONLYDIR);
    foreach ($folders ? $folders : [] as $folder) {
        $slug     = basename($folder);
        $infoFile = $folder . '/info.php';

        // Only modules providing an info.php file are recognised.
        if (!is_file($infoFile)) {
            continue;
        }

        $info = include $infoFile;
        if (!is_array($info)) {
            continue;
        }

        $module = array_merge(
            array(
                'slug'        => $slug,
                'name'        => ucwords(str_replace(array('-', '_'), ' ', $slug)),
                'description' => '',
                'version'     => '1.0.0',
                'icon'        => 'bi-puzzle',
                'enabled'     => true,
                'order'       => 100,
            ),
            $info
        );

        // The folder name is the canonical slug used for routing.
        $module['slug']   = $slug;
        $module['folder'] = $slug;
        $module['path']   = $folder;
        $module['file']   = $folder . '/index.php';

        $cache[$slug] = $module;
    }

    uasort($cache, function ($a, $b) {
        $left  = array((int) $a['order'], strtolower($a['name']));
        $right = array((int) $b['order'], strtolower($b['name']));

        return $left <=> $right;
    });

    return $cache;
}

/**
 * Return a single module by slug, or null when it does not exist.
 *
 * @return array|null
 */
function get_module($slug)
{
    $modules = get_modules();

    return isset($modules[$slug]) ? $modules[$slug] : null;
}

/**
 * Return only the enabled modules (used by the navbar and the router).
 *
 * @return array<string,array>
 */
function enabled_modules()
{
    $enabled = array();
    foreach (get_modules() as $slug => $module) {
        if (!empty($module['enabled'])) {
            $enabled[$slug] = $module;
        }
    }

    return $enabled;
}
