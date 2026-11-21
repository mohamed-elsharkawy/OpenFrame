<?php
/**
 * OpenFrame — Module: AppModules / Action: save_permissions
 * Save user permissions for a module.
 */

require_framework();
require_any_level(['superadmin', 'admin']);

csrf_check();

$moduleSlug = input('module_slug');
$canView = isset($_POST['can_view']) && is_array($_POST['can_view']) ? $_POST['can_view'] : array();
$canEdit = isset($_POST['can_edit']) && is_array($_POST['can_edit']) ? $_POST['can_edit'] : array();

if (!$moduleSlug) {
    flash('danger', lang('appmodules.invalid_slug', 'Invalid module slug.'));
    redirect(url('index.php?module=appmodules'));
    exit;
}

if (function_exists('db_table') && db_table_exists('module_permissions')) {
    // Get all users
    $allUsers = db_rows('SELECT id FROM ' . db_table('users'));
    foreach ($allUsers as $user) {
        $uid = (int) $user['id'];
        $view = isset($canView[$uid]) ? 1 : 0;
        $edit = isset($canEdit[$uid]) ? 1 : 0;

        $existing = db_row('SELECT id FROM ' . db_table('module_permissions') . ' WHERE user_id = ? AND module_slug = ?', [$uid, $moduleSlug]);
        if ($existing) {
            db_update('module_permissions', array(
                'can_view' => $view,
                'can_edit' => $edit,
                'updated_at' => now(),
            ), 'id = ?', [ (int) $existing['id'] ]);
        } else {
            db_insert('module_permissions', array(
                'user_id' => $uid,
                'module_slug' => $moduleSlug,
                'can_view' => $view,
                'can_edit' => $edit,
                'created_at' => now(),
                'updated_at' => now(),
            ));
        }
    }
}

flash('success', lang('appmodules.permissions_saved', 'Permissions saved.'));

// If the current user just revoked their own access to this admin page,
// sending them back to it would produce a confusing 404. Go home instead.
$target = 'appmodules&perms=' . urlencode($moduleSlug);
if (!module_user_can_view('appmodules')) {
    $target = 'home';
}
redirect(url('index.php?module=' . $target));
