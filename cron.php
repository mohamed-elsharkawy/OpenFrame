<?php
/**
 * OpenFrame — Cron / maintenance tasks
 * ------------------------------------
 * Run from CLI or include to call individual tasks.
 *
 * Example (CLI):
 *   php cron.php
 */

if (!defined('OPENFRAME_PATH')) {
    define('OPENFRAME_PATH', __DIR__);
}

/**
 * Scan all module info.php files and (re)generate includes/groups.php.
 *
 * - Discovers every non-empty group used by modules.
 * - Adds new groups with menu/submenu enabled by default.
 * - Preserves existing menu/submenu values for groups already present so an
 *   admin's visibility choices survive regeneration.
 *
 * The generated file returns an array keyed by a stable group key:
 *   [
 *     'system-configuration' => [
 *       'key'      => 'system-configuration',
 *       'name_en'  => 'System Configuration',
 *       'name_ar'  => 'إعدادات النظام',
 *       'menu'     => true,   // show/hide this group in the NavBar
 *       'submenu'  => true,   // show/hide this group in the Side menu
 *     ],
 *   ]
 *
 * @param string|null $basePath OpenFrame root path (defaults to OPENFRAME_PATH)
 * @return array The generated groups array
 */
function cron_generate_groups_file($basePath = null)
{
    $basePath = $basePath !== null ? $basePath : (defined('OPENFRAME_PATH') ? OPENFRAME_PATH : __DIR__);
    $modulesDir = $basePath . '/modules';
    $groupsFile = $basePath . '/includes/groups.php';

    $existing = array();
    if (is_file($groupsFile)) {
        $loaded = include $groupsFile;
        if (is_array($loaded)) {
            $existing = $loaded;
        }
    }

    $discovered = array();

    if (is_dir($modulesDir)) {
        $folders = glob($modulesDir . '/*', GLOB_ONLYDIR);
        foreach ($folders ? $folders : array() as $folder) {
            $infoFile = $folder . '/info.php';
            if (!is_file($infoFile)) {
                continue;
            }

            $info = include $infoFile;
            if (!is_array($info)) {
                continue;
            }

            $groupEn = isset($info['group_en']) ? trim((string) $info['group_en']) : '';
            $groupAr = isset($info['group_ar']) ? trim((string) $info['group_ar']) : '';
            $group   = isset($info['group']) ? trim((string) $info['group']) : '';

            // Prefer group_en as canonical name; fall back to generic group.
            $nameEn = $groupEn !== '' ? $groupEn : $group;
            $nameAr = $groupAr !== '' ? $groupAr : $group;

            if ($nameEn === '' && $nameAr === '') {
                continue; // ungrouped module
            }

            $keySource = $nameEn !== '' ? $nameEn : $nameAr;
            $key = cron_group_key($keySource);

            if ($key === '') {
                continue;
            }

            if (!isset($discovered[$key])) {
                $discovered[$key] = array(
                    'key'     => $key,
                    'name_en' => $nameEn,
                    'name_ar' => $nameAr,
                    'menu'    => true,
                    'submenu' => true,
                );
            } else {
                // Fill in missing labels if a later module provides them
                if ($discovered[$key]['name_en'] === '' && $nameEn !== '') {
                    $discovered[$key]['name_en'] = $nameEn;
                }
                if ($discovered[$key]['name_ar'] === '' && $nameAr !== '') {
                    $discovered[$key]['name_ar'] = $nameAr;
                }
            }
        }
    }

    // Merge: keep discovered groups, preserve admin-toggled visibility from existing
    $merged = array();
    foreach ($discovered as $key => $group) {
        if (isset($existing[$key]) && is_array($existing[$key])) {
            $group['menu']    = !empty($existing[$key]['menu']);
            $group['submenu'] = !empty($existing[$key]['submenu']);
            // Preserve admin-edited labels if they customized them
            if (!empty($existing[$key]['name_en'])) {
                $group['name_en'] = (string) $existing[$key]['name_en'];
            }
            if (!empty($existing[$key]['name_ar'])) {
                $group['name_ar'] = (string) $existing[$key]['name_ar'];
            }
        }
        $merged[$key] = $group;
    }

    // Sort: System Configuration first, then alphabetically by name_en
    uasort($merged, function ($a, $b) {
        $aName = isset($a['name_en']) ? $a['name_en'] : '';
        $bName = isset($b['name_en']) ? $b['name_en'] : '';
        $aIsSystem = ($aName === 'System Configuration' || $aName === 'إعدادات النظام');
        $bIsSystem = ($bName === 'System Configuration' || $bName === 'إعدادات النظام');
        if ($aIsSystem && !$bIsSystem) {
            return -1;
        }
        if (!$aIsSystem && $bIsSystem) {
            return 1;
        }
        return strcmp($aName, $bName);
    });

    $content = cron_export_groups_php($merged);

    if (!is_dir(dirname($groupsFile))) {
        @mkdir(dirname($groupsFile), 0777, true);
    }
    file_put_contents($groupsFile, $content);

    return $merged;
}

/**
 * Build a stable key for a group name.
 */
function cron_group_key($name)
{
    $name = trim((string) $name);
    if ($name === '') {
        return '';
    }
    // Transliterate/normalize: keep ascii letters/numbers, replace others with dash
    $key = strtolower($name);
    $key = preg_replace('/[^a-z0-9]+/i', '-', $key);
    $key = trim((string) $key, '-');
    if ($key === '') {
        // Fallback: hash for non-latin-only names
        $key = 'group-' . substr(md5($name), 0, 8);
    }
    return $key;
}

/**
 * Export groups array as a formatted PHP file.
 */
function cron_export_groups_php(array $groups)
{
    $lines = array();
    $lines[] = '<?php';
    $lines[] = '/**';
    $lines[] = ' * OpenFrame — Module groups configuration';
    $lines[] = ' * Generated/updated by cron_generate_groups_file() in cron.php.';
    $lines[] = ' *';
    $lines[] = ' * Admins can set menu/submenu to true (show) or false (hide) to control';
    $lines[] = ' * visibility of an entire group in the NavBar (menu) and Side menu (submenu).';
    $lines[] = ' * Re-running the generator preserves these visibility values.';
    $lines[] = ' */';
    $lines[] = '';
    $lines[] = 'return array(';

    foreach ($groups as $key => $group) {
        $nameEn  = isset($group['name_en']) ? $group['name_en'] : '';
        $nameAr  = isset($group['name_ar']) ? $group['name_ar'] : '';
        $menu    = !empty($group['menu']) ? 'true' : 'false';
        $submenu = !empty($group['submenu']) ? 'true' : 'false';

        $lines[] = '    ' . var_export($key, true) . ' => array(';
        $lines[] = '        ' . "'key'     => " . var_export($key, true) . ',';
        $lines[] = '        ' . "'name_en' => " . var_export($nameEn, true) . ',';
        $lines[] = '        ' . "'name_ar' => " . var_export($nameAr, true) . ',';
        $lines[] = '        ' . "'menu'    => " . $menu . ',';
        $lines[] = '        ' . "'submenu' => " . $submenu . ',';
        $lines[] = '    ),';
    }

    $lines[] = ');';
    $lines[] = '';

    return implode("\n", $lines);
}

// When executed directly from CLI, regenerate groups file.
if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $groups = cron_generate_groups_file(__DIR__);
    echo "Generated includes/groups.php with " . count($groups) . " group(s).\n";
}
