<?php
/**
 * OpenFrame — Module: AppModules (view)
 * Manage installed modules: enable/disable, user permissions, refresh from filesystem.
 * Action handling is delegated to modules/appmodules/actions/.
 *
 * Requires: config.php, helpers.php, db.php, modules.php (loaded by index.php).
 */

require_framework();
require_any_level(['superadmin', 'admin']);

// Dispatch to action handler
$action = input('action');
if ($action !== '' && is_file(__DIR__ . '/actions/' . $action . '.php')) {
    require __DIR__ . '/actions/' . $action . '.php';
    exit;
}

$message = '';
$messageType = '';

// Ensure the modules table exists and is synced with the filesystem
if (function_exists('db_table') && db_table_exists('modules')) {
    sync_modules_to_db();
}

// Get all modules with DB overrides applied
$allModules = get_modules();

// Get DB module rows for enabled/system flags
$dbModules = array();
if (function_exists('db_table') && db_table_exists('modules')) {
    $dbRows = db_rows('SELECT * FROM ' . db_table('modules') . ' ORDER BY module_order, name_en');
    foreach ($dbRows as $row) {
        $dbModules[$row['slug']] = $row;
    }
}

// Get all users for permission management
$allUsers = array();
if (function_exists('db_table') && db_table_exists('users')) {
    $allUsers = db_rows('SELECT id, username, full_name, level FROM ' . db_table('users') . ' ORDER BY username');
}

// Get existing module permissions
$modulePermissions = array();
if (function_exists('db_table') && db_table_exists('module_permissions')) {
    $perms = db_rows('SELECT * FROM ' . db_table('module_permissions'));
    foreach ($perms as $p) {
        $modulePermissions[$p['user_id'] . '|' . $p['module_slug']] = $p;
    }
}

?>
<div class="row g-4">
    <!-- Header / Refresh -->
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="bi bi-layout me-2"></i><?php echo e(lang('appmodules.title', 'App Modules')); ?>
                    </h5>
                    <span class="badge text-bg-<?php echo !empty($messageType) && $messageType === 'success' ? 'success' : 'info'; ?>">
                        <?php echo count($allModules); ?> <?php echo e(lang('common.modules', 'modules')); ?>
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <form method="post" class="d-inline" onsubmit="return confirm('<?php echo e(lang('appmodules.confirm_refresh', 'Refresh modules from filesystem? This will scan the modules folder and sync to the database.')); ?>');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="refresh">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="bi bi-arrow-clockwise me-1"></i><?php echo e(lang('appmodules.refresh', 'Refresh from filesystem')); ?>
                        </button>
                    </form>
                    <a href="<?php echo e(url('index.php?module=home')); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-house-door me-1"></i><?php echo e(lang('common.home', 'Home')); ?>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?php echo e($messageType); ?>"><?php echo e($message); ?></div>
                <?php endif; ?>
                <?php if (function_exists('flash_render')): ?>
                    <?php echo flash_render(); ?>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px">#</th>
                                <th><?php echo e(lang('common.module', 'Module')); ?></th>
                                <th style="width:140px"><?php echo e(lang('appmodules.status', 'Status')); ?></th>
                                <th style="width:120px"><?php echo e(lang('appmodules.type', 'Type')); ?></th>
                                <th style="width:150px"><?php echo e(lang('common.group', 'Group')); ?></th>
                                <th style="width:90px"><?php echo e(lang('common.order', 'Order')); ?></th>
                                <th style="width:120px"><?php echo e(lang('common.actions', 'Actions')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($allModules as $slug => $mod): ?>
                                <?php
                                $isSystem = !empty($mod['system']);
                                $dbRow = isset($dbModules[$slug]) ? $dbModules[$slug] : null;
                                $isEnabled = !empty($mod['enabled']);
                                $canToggle = !$isSystem;
                                ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi <?php echo e($mod['icon']); ?> me-2 text-primary"></i>
                                            <div>
                                                <div class="fw-semibold"><?php echo e($mod['name']); ?></div>
                                                <div class="small text-muted"><?php echo e($slug); ?></div>
                                                <div class="small text-muted"><?php echo e($mod['description']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($canToggle): ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('<?php echo e(lang('appmodules.confirm_toggle', 'Toggle module status?')); ?>');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="slug" value="<?php echo e($slug); ?>">
                                                <input type="hidden" name="enabled" value="<?php echo $isEnabled ? '0' : '1'; ?>">
                                                <button class="btn btn-sm btn-<?php echo $isEnabled ? 'success' : 'outline-secondary'; ?>" type="submit">
                                                    <?php echo $isEnabled ? lang('common.enabled', 'Enabled') : lang('common.disabled', 'Disabled'); ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge text-bg-danger"><?php echo e(lang('appmodules.system_module', 'System module')); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-<?php echo $isSystem ? 'danger' : 'info'; ?>">
                                            <?php echo $isSystem ? lang('appmodules.system', 'System') : lang('appmodules.regular', 'Regular'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo e(!empty($mod['group']) ? $mod['group'] : '-'); ?>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-secondary"><?php echo (int) ($mod['order'] ?? 100); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?php echo e(url('index.php?module=appmodules&edit=' . rawurlencode($slug))); ?>" title="<?php echo e(lang('common.edit', 'Edit')); ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a class="btn btn-sm btn-outline-info" href="<?php echo e(url('index.php?module=appmodules&perms=' . rawurlencode($slug))); ?>" title="<?php echo e(lang('appmodules.permissions', 'Permissions')); ?>">
                                            <i class="bi bi-shield-check"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($allModules)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4"><?php echo e(lang('common.no_modules', 'No modules found.')); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Module Form -->
    <?php $editSlug = input('edit'); if ($editSlug && isset($allModules[$editSlug])): ?>
        <?php
        $mod = $allModules[$editSlug];
        $dbRow = isset($dbModules[$editSlug]) ? $dbModules[$editSlug] : null;
        ?>
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pencil me-2"></i><?php echo e(lang('appmodules.edit_module', 'Edit module')); ?>: <?php echo e($mod['name']); ?>
                </h5>
                <a href="<?php echo e(url('index.php?module=appmodules')); ?>" class="btn btn-sm btn-outline-secondary"><?php echo e(lang('common.cancel', 'Cancel')); ?></a>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save_module">
                    <input type="hidden" name="slug" value="<?php echo e($editSlug); ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label"><?php echo e(lang('common.module', 'Module')); ?></label>
                            <input class="form-control" value="<?php echo e($editSlug); ?>" readonly>
                            <div class="form-text"><?php echo e(lang('appmodules.slug_help', 'The module slug is the folder name and cannot be changed.')); ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo e(lang('appmodules.name_en', 'Name (English)')); ?></label>
                            <input class="form-control" name="name_en" value="<?php echo e($dbRow['name_en'] ?? $mod['name_en'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo e(lang('appmodules.name_ar', 'Name (Arabic)')); ?></label>
                            <input class="form-control" name="name_ar" value="<?php echo e($dbRow['name_ar'] ?? $mod['name_ar'] ?? ''); ?>" dir="rtl">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo e(lang('common.group', 'Group')); ?></label>
                            <input class="form-control" name="group_name" value="<?php echo e($dbRow['group_name'] ?? $mod['group'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo e(lang('common.icon', 'Icon')); ?></label>
                            <input class="form-control" name="icon" value="<?php echo e($dbRow['icon'] ?? $mod['icon'] ?? 'bi-puzzle'); ?>">
                            <div class="form-text"><?php echo e(lang('appmodules.icon_help', 'Bootstrap Icons class name (e.g. bi-house-door).')); ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo e(lang('common.order', 'Order')); ?></label>
                            <input class="form-control" name="module_order" type="number" value="<?php echo e($dbRow['module_order'] ?? $mod['order'] ?? 100); ?>">
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button class="btn btn-primary" type="submit"><?php echo e(lang('common.save', 'Save')); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Module Permissions Form -->
    <?php $permsSlug = input('perms'); if ($permsSlug && isset($allModules[$permsSlug])): ?>
        <?php
        $permMod = $allModules[$permsSlug];
        ?>
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-shield-check me-2"></i><?php echo e(lang('appmodules.module_permissions', 'Module permissions')); ?>: <?php echo e($permMod['name']); ?>
                </h5>
                <a href="<?php echo e(url('index.php?module=appmodules')); ?>" class="btn btn-sm btn-outline-secondary"><?php echo e(lang('common.cancel', 'Cancel')); ?></a>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save_permissions">
                    <input type="hidden" name="module_slug" value="<?php echo e($permsSlug); ?>">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px">#</th>
                                    <th><?php echo e(lang('common.name', 'User')); ?></th>
                                    <th style="width:100px"><?php echo e(lang('common.level', 'Level')); ?></th>
                                    <th style="width:100px"><?php echo e(lang('appmodules.can_view', 'Can View')); ?></th>
                                    <th style="width:100px"><?php echo e(lang('appmodules.can_edit', 'Can Edit')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allUsers)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4"><?php echo e(lang('common.no_modules', 'No users found.')); ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php $pi = 1; foreach ($allUsers as $user): ?>
                                        <?php
                                        $permKey = $user['id'] . '|' . $permsSlug;
                                        $perm = isset($modulePermissions[$permKey]) ? $modulePermissions[$permKey] : null;
                                        $canView = $perm ? !empty($perm['can_view']) : true;
                                        $canEdit = $perm ? !empty($perm['can_edit']) : false;
                                        ?>
                                        <tr>
                                            <td><?php echo $pi++; ?></td>
                                            <td><?php echo e($user['username']); ?></td>
                                            <td><span class="badge text-bg-secondary"><?php echo e(ucfirst($user['level'])); ?></span></td>
                                            <td class="text-center">
                                                <input type="checkbox" name="can_view[<?php echo (int) $user['id']; ?>]" value="1" <?php echo $canView ? 'checked' : ''; ?>>
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="can_edit[<?php echo (int) $user['id']; ?>]" value="1" <?php echo $canEdit ? 'checked' : ''; ?>>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button class="btn btn-primary" type="submit"><?php echo e(lang('common.save', 'Save Permissions')); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
