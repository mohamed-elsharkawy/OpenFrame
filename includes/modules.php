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
 * Database overrides (opf_modules table) take precedence over info.php
 * for the `enabled` and `system` flags.
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

    /* Load DB-backed module overrides (enabled/system) if the table exists. */
    $dbOverrides = array();
    if (function_exists('db_table') && db_table_exists('modules')) {
        $dbOverrides = db_get_module_overrides();
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
                'system'      => false,
            ),
            $info
        );

        // Apply DB overrides for enabled and system (DB wins over info.php)
        if (isset($dbOverrides[$slug])) {
            $ov = $dbOverrides[$slug];
            if (isset($ov['enabled'])) {
                $module['enabled'] = !empty($ov['enabled']);
            }
            if (isset($ov['system'])) {
                $module['system'] = !empty($ov['system']);
            }
            if (isset($ov['name_en'])) {
                $module['name_en'] = $ov['name_en'];
            }
            if (isset($ov['name_ar'])) {
                $module['name_ar'] = $ov['name_ar'];
            }
            if (isset($ov['group_name'])) {
                // The DB stores one canonical (English) group name. Keep the
                // Arabic translation from info.php; if the module does not
                // provide one, look it up in includes/groups.php.
                $module['group_en'] = $ov['group_name'];
                $module['group'] = $ov['group_name'];
                if (empty($module['group_ar'])) {
                    $groupConfig = find_group_config($ov['group_name']);
                    if ($groupConfig !== null && !empty($groupConfig['name_ar'])) {
                        $module['group_ar'] = $groupConfig['name_ar'];
                    }
                }
            }
            if (isset($ov['module_order'])) {
                $module['order'] = (int) $ov['module_order'];
            }
            if (isset($ov['icon'])) {
                $module['icon'] = $ov['icon'];
            }
            if (isset($ov['description'])) {
                $module['description'] = $ov['description'];
                $module['description_en'] = $ov['description'];
                $module['description_ar'] = $ov['description'];
            }
        }

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
 * Read enabled/system overrides from the opf_modules table.
 *
 * @return array<string,array>
 */
function db_get_module_overrides()
{
    $overrides = array();
    try {
        if (!db_table_exists('modules')) {
            return $overrides;
        }
        $rows = db_rows('SELECT * FROM ' . db_table('modules'));
        foreach ($rows as $row) {
            $overrides[$row['slug']] = $row;
        }
    } catch (Throwable $ex) {
        // Table may not exist yet; return empty overrides
    }

    return $overrides;
}

/**
 * Synchronise modules from the filesystem into the opf_modules table.
 * Inserts new modules and updates existing ones (name, group, order, icon,
 * system flag). Preserves the enabled flag already stored in the DB.
 *
 * Scans every module folder, reads its info.php, and upserts a row.
 *
 * @return int  number of modules synced
 */
function sync_modules_to_db()
{
    if (!function_exists('db_table') || !db_table_exists('modules')) {
        return 0;
    }

    $modules = array();
    $base    = defined('OPENFRAME_PATH') ? OPENFRAME_PATH : dirname(__DIR__);
    $baseDir = $base . '/modules';

    if (!is_dir($baseDir)) {
        return 0;
    }

    $now = now();
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

        $lang = app_session_get('lang', OPENFRAME_DEFAULT_LANG);
        $nameEn = isset($info['name_en']) ? (string) $info['name_en'] : ucwords(str_replace(array('-', '_'), ' ', $slug));
        $nameAr = isset($info['name_ar']) ? (string) $info['name_ar'] : '';
        $group  = isset($info['group_en']) ? (string) $info['group_en'] : (isset($info['group']) ? (string) $info['group'] : '');
        if ($group === '' && isset($info['group_ar'])) {
            $group = (string) $info['group_ar'];
        }

        $modules[] = array(
            'slug'         => $slug,
            'name_en'      => $nameEn,
            'name_ar'      => $nameAr,
            'description'  => isset($info['description_en']) ? (string) $info['description_en'] : '',
            'icon'         => isset($info['icon']) ? (string) $info['icon'] : 'bi-puzzle',
            'group_name'   => $group,
            'enabled'      => !empty($info['enabled']) ? 1 : 0,
            'system'       => !empty($info['system']) ? 1 : 0,
            'module_order' => isset($info['order']) ? (int) $info['order'] : 100,
            'updated_at'   => $now,
        );
    }

    foreach ($modules as $m) {
        $existing = db_row('SELECT slug, enabled FROM ' . db_table('modules') . ' WHERE slug = ?', [$m['slug']]);
        if ($existing) {
            // Preserve the DB-stored enabled flag; update everything else
            db_update('modules', array(
                'name_en'      => $m['name_en'],
                'name_ar'      => $m['name_ar'],
                'description'  => $m['description'],
                'icon'         => $m['icon'],
                'group_name'   => $m['group_name'],
                'system'       => $m['system'],
                'module_order' => $m['module_order'],
                'updated_at'   => $m['updated_at'],
            ), 'slug = ?', [$m['slug']]);
        } else {
            db_insert('modules', $m);
        }
    }

    return count($modules);
}

/**
 * Write the enabled/system flags for a module to both info.php and the DB.
 *
 * @param string $slug
 * @param array  $flags  keys: 'enabled' (bool), 'system' (bool)
 * @return bool
 */
function module_set_flags($slug, array $flags)
{
    $ok = true;

    // Update info.php
    $base    = defined('OPENFRAME_PATH') ? OPENFRAME_PATH : dirname(__DIR__);
    $infoFile = $base . '/modules/' . $slug . '/info.php';

    if (is_file($infoFile)) {
        $content = file_get_contents($infoFile);
        if ($content !== false) {
            $setInfoFlag = function ($name, $value) use (&$content) {
                $literal = $value ? 'true' : 'false';
                if (preg_match("/'" . preg_quote($name, '/') . "'\\s*=>/", $content)) {
                    $content = preg_replace(
                        "/'" . preg_quote($name, '/') . "'\\s*=>\\s*(true|false)/",
                        "'" . $name . "' => " . $literal,
                        $content,
                        1
                    );
                } else {
                    // Insert before the closing bracket of the returned array.
                    $content = preg_replace(
                        "/(\\n\\s*)\\]/",
                        "\$1    '" . $name . "' => " . $literal . ",\n\$1]",
                        $content,
                        1
                    );
                }
            };

            if (array_key_exists('enabled', $flags)) {
                $setInfoFlag('enabled', (bool) $flags['enabled']);
            }
            if (array_key_exists('system', $flags)) {
                $setInfoFlag('system', (bool) $flags['system']);
            }
            file_put_contents($infoFile, $content);
        }
    }

    // Update DB
    if (function_exists('db_table') && db_table_exists('modules')) {
        $data = array('updated_at' => now());
        if (isset($flags['enabled'])) {
            $data['enabled'] = $flags['enabled'] ? 1 : 0;
        }
        if (isset($flags['system'])) {
            $data['system'] = $flags['system'] ? 1 : 0;
        }

        $existing = db_row('SELECT slug FROM ' . db_table('modules') . ' WHERE slug = ?', [$slug]);
        if ($existing) {
            db_update('modules', $data, 'slug = ?', [$slug]);
        } else {
            $data['slug'] = $slug;
            $data['name_en'] = ucwords(str_replace(array('-', '_'), ' ', $slug));
            $data['name_ar'] = '';
            $data['description'] = '';
            $data['icon'] = 'bi-puzzle';
            $data['group_name'] = '';
            $data['module_order'] = 100;
            db_insert('modules', $data);
        }
    }

    return $ok;
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
