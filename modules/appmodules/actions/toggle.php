<?php
/**
 * OpenFrame — Module: AppModules / Action: toggle
 * Toggle a module's enabled flag in info.php and the DB.
 */

require_framework();
require_any_level(['superadmin', 'admin']);

csrf_check();

$slug  = input('slug');
$enabled = input('enabled') === '1';

$mod = get_module($slug);
if (!$mod) {
    flash('danger', lang('appmodules.not_found', 'Module not found.'));
    redirect(url('index.php?module=appmodules'));
    exit;
}

// System modules cannot be disabled via toggle
if (!empty($mod['system']) && !$enabled) {
    flash('danger', lang('appmodules.cannot_disable_system', 'System modules cannot be disabled.'));
    redirect(url('index.php?module=appmodules'));
    exit;
}

module_set_flags($slug, array('enabled' => $enabled));

flash('success', $enabled
    ? lang('appmodules.module_enabled', 'Module enabled.')
    : lang('appmodules.module_disabled', 'Module disabled.'));
redirect(url('index.php?module=appmodules'));
