<?php
/**
 * OpenFrame — Module: AppModules / Action: refresh
 * Scan modules folder and sync to the database, then regenerate groups file.
 */

require_framework();
require_any_level(['superadmin', 'admin']);

// cron.php only defines helpers; its CLI block is skipped during web requests.
require_once dirname(__DIR__, 3) . '/cron.php';

csrf_check();

$synced = 0;
$groups = 0;

if (function_exists('sync_modules_to_db')) {
    $synced = sync_modules_to_db();
}

if (function_exists('cron_generate_groups_file')) {
    $groups = count(cron_generate_groups_file());
}

flash('success', lang('appmodules.refreshed', 'Refreshed.') . ' ' . lang('appmodules.modules_synced', '{count} module(s) synced.', array('{count}' => $synced)) . ' ' . lang('appmodules.groups_generated', '{count} group(s) generated.', array('{count}' => $groups)));
redirect(url('index.php?module=appmodules'));
