<?php
/**
 * Front Office — Modules loader
 * ------------------------------
 * Reads every folder inside /modules. When a folder contains an info.php
 * file that returns an array, the module is registered.
 *
 * Recognised properties:
 *   name / name_ar  display name in English / Arabic
 *   description     short description
 *   version         module version
 *   icon            Bootstrap icon class (bi-xxx)
 *   enabled         false hides the module completely
 *   order           sort position in the navbar
 *   menu            false keeps the module out of the navbar
 *   layout          "app" (navbar+footer) or "auth" (standalone page)
 *   permission      permission code required to use the module
 */

/**
 * @return array<string,array> slug => module properties (sorted)
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

        if (!is_file($infoFile)) {
            continue;
        }

        $info = include $infoFile;
        if (!is_array($info)) {
            continue;
        }

        $module = array_merge(
            array(
                'name'        => ucwords(str_replace(array('-', '_'), ' ', $slug)),
                'name_ar'     => '',
                'description' => '',
                'version'     => '1.0.0',
                'icon'        => 'bi-puzzle',
                'enabled'     => true,
                'order'       => 100,
                'menu'        => true,
                'layout'      => 'app',
                'permission'  => null,
            ),
            $info
        );

        $module['name_ar'] = $module['name_ar'] !== '' ? $module['name_ar'] : $module['name'];
        $module['slug']    = $slug;
        $module['folder']  = $slug;
        $module['path']    = $folder;
        $module['file']    = $folder . '/index.php';

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
 * @return array|null
 */
function get_module($slug)
{
    $modules = get_modules();

    return isset($modules[$slug]) ? $modules[$slug] : null;
}

/**
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
