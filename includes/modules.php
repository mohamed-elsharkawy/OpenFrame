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
                'name_en'     => ucwords(str_replace(array('-', '_'), ' ', $slug)),
                'name_ar'     => ucwords(str_replace(array('-', '_'), ' ', $slug)),
                'description' => '',
                'description_en' => '',
                'description_ar' => '',
                'version'     => '1.0.0',
                'icon'        => 'bi-puzzle',
                'enabled'     => true,
                'order'       => 100,
                'menu'        => true,
                'submenu'     => true,
            ),
            $info
        );

        // Resolve bilingual name based on current language
        $lang = app_session_get('lang', OPENFRAME_DEFAULT_LANG);
        if ($lang === 'ar' && !empty($module['name_ar'])) {
            $module['name'] = $module['name_ar'];
            $module['description'] = !empty($module['description_ar']) ? $module['description_ar'] : $module['description_en'];
        } else {
            $module['name'] = !empty($module['name_en']) ? $module['name_en'] : $module['name'];
            $module['description'] = !empty($module['description_en']) ? $module['description_en'] : $module['description'];
        }

        // Resolve bilingual group name based on current language
        if ($lang === 'ar' && !empty($module['group_ar'])) {
            $module['group'] = $module['group_ar'];
        } else {
            $module['group'] = !empty($module['group_en']) ? $module['group_en'] : $module['group'];
        }

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

/**
 * Load groups visibility/configuration from includes/groups.php.
 *
 * @return array<string,array> key => group config
 */
function get_groups_config()
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $base = defined('OPENFRAME_PATH') ? OPENFRAME_PATH : dirname(__DIR__);
    $file = $base . '/includes/groups.php';

    if (is_file($file)) {
        $loaded = include $file;
        $cache = is_array($loaded) ? $loaded : array();
    } else {
        $cache = array();
    }

    return $cache;
}

/**
 * Find a group config entry by its displayed/localized name.
 *
 * Matches against key, name_en, and name_ar.
 *
 * @param string $groupName
 * @return array|null
 */
function find_group_config($groupName)
{
    $groupName = (string) $groupName;

    foreach (get_groups_config() as $key => $group) {
        if ($key === $groupName) {
            return $group;
        }
        if (isset($group['name_en']) && (string) $group['name_en'] === $groupName) {
            return $group;
        }
        if (isset($group['name_ar']) && (string) $group['name_ar'] === $groupName) {
            return $group;
        }
    }

    return null;
}

/**
 * Whether a module group should be shown in the NavBar.
 * If no config exists for the group, defaults to visible.
 */
function is_group_visible_in_menu($groupName)
{
    $group = find_group_config($groupName);
    if ($group === null) {
        return true;
    }
    return !empty($group['menu']);
}

/**
 * Whether a module group should be shown in the Side menu.
 * If no config exists for the group, defaults to visible.
 */
function is_group_visible_in_submenu($groupName)
{
    $group = find_group_config($groupName);
    if ($group === null) {
        return true;
    }
    return !empty($group['submenu']);
}

/**
 * Return enabled modules organized by their "group" field (used by the sidemenu).
 *
 * Modules without a group (empty 'group' value) are placed under the null key
 * and rendered as ungrouped items (not inside a dropdown).
 * If a group does not exist yet it is created; otherwise the module is added
 * to the existing group.
 *
 * @return array<string|int,array<string,array>> group => slug => module.
 *   Use null key for ungrouped modules.
 */
function get_modules_by_group()
{
    $groups = array();
    $ungrouped = array();

    foreach (enabled_modules() as $slug => $module) {
        $group = !empty($module['group']) ? (string) $module['group'] : null;

        if ($group === null) {
            $ungrouped[$slug] = $module;
        } else {
            if (!isset($groups[$group])) {
                $groups[$group] = array();
            }

            $groups[$group][$slug] = $module;
        }
    }

    // Sort groups: "System Configuration" / "إعدادات النظام" first, then alphabetically
    uksort($groups, function ($a, $b) {
        $aIsSystem = ($a === 'System Configuration' || $a === 'إعدادات النظام');
        $bIsSystem = ($b === 'System Configuration' || $b === 'إعدادات النظام');
        if ($aIsSystem && !$bIsSystem) {
            return -1;
        }
        if (!$aIsSystem && $bIsSystem) {
            return 1;
        }
        return strcmp($a, $b);
    });

    return array('ungrouped' => $ungrouped, 'groups' => $groups);
}
