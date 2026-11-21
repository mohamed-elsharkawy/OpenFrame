<?php
/**
 * OpenFrame — Module: Users (view)
 * Full CRUD for user management with role-based access control.
 * Action handling is delegated to modules/users/actions/.
 *
 * Requires: config.php, helpers.php, db.php (loaded by index.php).
 */

require_framework();

// Ensure permissions column exists (migration for existing databases)
try {
    db_exec('ALTER TABLE ' . db_table('users') . ' ADD COLUMN permissions VARCHAR(255) NULL');
} catch (Throwable $ex) {
    // Column likely already exists, ignore
}

// Require superadmin or admin to access this module
require_any_level(['superadmin', 'admin']);

// Dispatch to action handler
$action = input('action');
if ($action !== '' && is_file(__DIR__ . '/actions/' . $action . '.php')) {
    require __DIR__ . '/actions/' . $action . '.php';
    exit;
}

$message = '';
$messageType = '';

// Get user for edit
$editUser = null;
$editId = (int) input('edit');
if ($editId > 0) {
    $editUser = db_row('SELECT * FROM ' . db_table('users') . ' WHERE id = ?', [$editId]);
    if (!$editUser) {
        $editId = 0;
    }
}

// Get all users
$allUsers = db_rows('SELECT * FROM ' . db_table('users') . ' ORDER BY username');

// Available permission levels
$userLevels = [
    'superadmin' => lang('common.level', 'Level') . ': superadmin',
    'admin' => lang('common.level', 'Level') . ': admin',
    'user' => lang('common.level', 'Level') . ': user',
];

// Available permissions for multiselect
$allPermissions = [
    'manage_users' => lang('common.name', 'Users') . ' - ' . lang('common.actions', 'Manage'),
    'manage_modules' => lang('common.modules', 'Modules') . ' - ' . lang('common.actions', 'Manage'),
    'view_reports' => lang('common.name', 'Reports') . ' - ' . lang('common.actions', 'View'),
    'manage_settings' => lang('common.settings', 'Settings') . ' - ' . lang('common.actions', 'Manage'),
    'view_audit' => lang('common.name', 'Audit') . ' - ' . lang('common.actions', 'View'),
];

// Get user's current permissions
$userPermissions = [];
if ($editUser && !empty($editUser['permissions'])) {
    $userPermissions = explode(',', $editUser['permissions']);
}

?>
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-people me-2"></i><?php echo e(lang('common.name', 'Users')); ?>
                </h5>
                <?php if (!$editId): ?>
                    <a href="<?php echo e(url('index.php?module=users')); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-list me-1"></i><?php echo e(lang('common.name', 'Users')); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">

                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?php echo e($messageType); ?>"><?php echo e($message); ?></div>
                <?php endif; ?>

                <?php if ($editId && $editUser): ?>
                    <!-- EDIT USER -->
                    <h6 class="mb-3">
                        <i class="bi bi-pencil me-2"></i><?php echo e(lang('common.edit', 'Edit')); ?>: <?php echo e($editUser['username']); ?>
                    </h6>
                    <form method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="save_user">
                        <input type="hidden" name="user_id" value="<?php echo e($editUser['id']); ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo e(lang('common.name', 'Username')); ?></label>
                                <input class="form-control" name="username" value="<?php echo e($editUser['username']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo e(lang('common.full_name', 'Full Name')); ?></label>
                                <input class="form-control" name="full_name" value="<?php echo e($editUser['full_name']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo e(lang('common.level', 'Level')); ?></label>
                                <select class="form-select" name="level" required>
                                    <?php foreach ($userLevels as $levelValue => $levelLabel): ?>
                                        <option value="<?php echo e($levelValue); ?>" <?php echo $editUser['level'] === $levelValue ? 'selected' : ''; ?>>
                                            <?php echo e($levelLabel); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo e(lang('app.password', 'Password')); ?></label>
                                <input class="form-control" name="password" type="password" placeholder="<?php echo e(lang('common.actions', 'Leave blank to keep current')); ?>">
                                <div class="form-text"><?php echo e(lang('common.actions', 'Leave blank to keep current password')); ?></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="userPermissions"><?php echo e(lang('common.actions', 'Permissions')); ?></label>
                            <select id="userPermissions" class="form-select select2-multi" name="permissions[]" multiple="multiple" data-placeholder="<?php echo e(lang('common.select_options', 'Select options')); ?>">
                                <option value=""></option>
                                <?php foreach ($allPermissions as $permKey => $permLabel): ?>
                                    <option value="<?php echo e($permKey); ?>" <?php echo in_array($permKey, $userPermissions) ? 'selected' : ''; ?>>
                                        <?php echo e($permLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text"><?php echo e(lang('common.actions', 'Select multiple permissions')); ?></div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo e(url('index.php?module=users')); ?>" class="btn btn-outline-secondary"><?php echo e(lang('common.cancel', 'Cancel')); ?></a>
                            <?php if (current_user()['level'] === 'superadmin'
                                || (current_user()['level'] === 'admin' && $editUser['level'] !== 'superadmin')): ?>
                                <button class="btn btn-outline-warning" type="submit"
                                        form="resetOtpForm"
                                        onclick="return confirm('<?php echo e(lang('otp.reset', 'Reset OTP?')); ?>');">
                                    <i class="bi bi-shield-x me-1"></i><?php echo e(lang('otp.reset', 'Reset OTP')); ?>
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-primary" type="submit"><?php echo e(lang('common.save', 'Save')); ?></button>
                        </div>
                    </form>
                    <?php if (current_user()['level'] === 'superadmin'
                        || (current_user()['level'] === 'admin' && $editUser['level'] !== 'superadmin')): ?>
                        <form method="post" id="resetOtpForm" class="d-none">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="reset_otp">
                            <input type="hidden" name="reset_id" value="<?php echo e($editUser['id']); ?>">
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- CREATE USER -->
                    <h6 class="mb-3">
                        <i class="bi bi-person-plus me-2"></i><?php echo e(lang('common.create', 'Create')); ?> <?php echo e(lang('common.name', 'User')); ?>
                    </h6>
                    <form method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="save_user">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo e(lang('common.name', 'Username')); ?></label>
                                <input class="form-control" name="username" required autofocus>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo e(lang('common.full_name', 'Full Name')); ?></label>
                                <input class="form-control" name="full_name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo e(lang('common.level', 'Level')); ?></label>
                                <select class="form-select" name="level" required>
                                    <?php foreach ($userLevels as $levelValue => $levelLabel): ?>
                                        <option value="<?php echo e($levelValue); ?>"><?php echo e($levelLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo e(lang('app.password', 'Password')); ?></label>
                                <input class="form-control" name="password" type="password" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="userPermissionsCreate"><?php echo e(lang('common.actions', 'Permissions')); ?></label>
                            <select id="userPermissionsCreate" class="form-select select2-multi" name="permissions[]" multiple="multiple" data-placeholder="<?php echo e(lang('common.select_options', 'Select options')); ?>">
                                <option value=""></option>
                                <?php foreach ($allPermissions as $permKey => $permLabel): ?>
                                    <option value="<?php echo e($permKey); ?>"><?php echo e($permLabel); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text"><?php echo e(lang('common.actions', 'Select multiple permissions')); ?></div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo e(url('index.php?module=users')); ?>" class="btn btn-outline-secondary"><?php echo e(lang('common.cancel', 'Cancel')); ?></a>
                            <button class="btn btn-primary" type="submit"><?php echo e(lang('common.save', 'Create User')); ?></button>
                        </div>
                    </form>
                <?php endif; ?>

                <hr class="my-4">

                <!-- USER LIST -->
                <h6 class="mb-3">
                    <i class="bi bi-table me-2"></i><?php echo e(lang('common.name', 'Users')); ?>
                </h6>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?php echo e(lang('common.name', 'Username')); ?></th>
                                <th><?php echo e(lang('common.full_name', 'Full Name')); ?></th>
                                <th><?php echo e(lang('common.level', 'Level')); ?></th>
                                <th><?php echo e(lang('common.actions', 'Permissions')); ?></th>
                                <th><?php echo e(lang('common.name', 'Created')); ?></th>
                                <th class="text-end"><?php echo e(lang('common.actions', 'Actions')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allUsers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4"><?php echo e(lang('common.no_modules', 'No users found.')); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allUsers as $user): ?>
                                    <tr>
                                        <td><?php echo e($user['username']); ?></td>
                                        <td><?php echo e($user['full_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['level'] === 'superadmin' ? 'danger' : ($user['level'] === 'admin' ? 'primary' : 'info'); ?>">
                                                <?php echo e(ucfirst($user['level'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $userPerms = !empty($user['permissions']) ? explode(',', $user['permissions']) : [];
                                            $permLabels = [];
                                            foreach ($userPerms as $p) {
                                                if (isset($allPermissions[$p])) {
                                                    $permLabels[] = $allPermissions[$p];
                                                }
                                            }
                                            echo implode(', ', $permLabels);
                                            ?>
                                        </td>
                                        <td><?php echo e($user['created_at']); ?></td>
                                        <td class="text-end">
                                            <?php if (current_user()['level'] === 'superadmin'
                                                || (current_user()['level'] === 'admin' && $user['level'] !== 'superadmin')): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('<?php echo e(lang('otp.reset', 'Reset OTP?')); ?>');">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="reset_otp">
                                                    <input type="hidden" name="reset_id" value="<?php echo e($user['id']); ?>">
                                                    <button class="btn btn-sm btn-outline-warning" type="submit" title="<?php echo e(lang('otp.reset', 'Reset OTP')); ?>">
                                                        <i class="bi bi-shield-x"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($user['id'] != current_user()['id']): ?>
                                                <a class="btn btn-sm btn-outline-primary" href="<?php echo e(url('index.php?module=users&edit=' . (int) $user['id'])); ?>" title="<?php echo e(lang('common.edit', 'Edit')); ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('<?php echo e(lang('msg.error', 'Are you sure?')); ?>');">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="delete_id" value="<?php echo e($user['id']); ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="<?php echo e(lang('common.delete', 'Delete')); ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted"><i class="bi bi-person-check"></i></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
