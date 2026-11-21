<?php
/**
 * OpenFrame — Module: AppModules / Action: save_module
 * Save module settings (name, group, order, icon) to the DB.
 */

require_framework();
require_any_level(['superadmin', 'admin']);

csrf_check();

$slug = input('slug');
$nameEn = input('name_en');
$nameAr = input('name_ar');
$groupName = input('group_name');
$icon = input('icon');
$moduleOrder = (int) input('module_order', 100);

if (!$slug) {
    flash('danger', lang('appmodules.invalid_slug', 'Invalid module slug.'));
    redirect(url('index.php?module=appmodules'));
    exit;
}

if (function_exists('db_table') && db_table_exists('modules')) {
    $existing = db_row('SELECT slug FROM ' . db_table('modules') . ' WHERE slug = ?', [$slug]);
    $data = array(
        'name_en'      => $nameEn,
        'name_ar'      => $nameAr,
        'group_name'   => $groupName,
        'icon'         => $icon,
        'module_order' => $moduleOrder,
        'updated_at'   => now(),
    );
    if ($existing) {
        db_update('modules', $data, 'slug = ?', [$slug]);
    } else {
        $data['slug'] = $slug;
        $data['enabled'] = 1;
        $data['system'] = 0;
        db_insert('modules', $data);
    }
}

flash('success', lang('appmodules.module_saved', 'Module settings saved.'));
redirect(url('index.php?module=appmodules&edit=' . urlencode($slug)));
