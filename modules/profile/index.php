<?php
/**
 * OpenFrame — Module: Profile (view)
 * Simple user profile page.
 */

require_framework();
require_login();

$currentUserId = (int) $_SESSION['user_id'] ?? 0;
$currentUser = db_row('SELECT * FROM ' . db_table('users') . ' WHERE id = ?', [$currentUserId]);

if (!$currentUser) {
    redirect(url('index.php?module=login'));
}

$message = '';
$messageType = '';

?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="<?php echo e(config('applogo')); ?>" alt="<?php echo e(config('appname')); ?> logo" height="48" class="mb-2">
                    <h2 class="h4 card-title mb-0"><?php echo e(config('appname', 'OpenFrame')); ?></h2>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?php echo e($messageType); ?>"><?php echo e($message); ?></div>
                <?php endif; ?>

                <h5 class="card-title mb-3">Profile</h5>

                <table class="table table-borderless mb-4">
                    <tr>
                        <th style="width: 40%;"><?php echo e(lang('common.name', 'Username')); ?></th>
                        <td><?php echo e($currentUser['username']); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo e(lang('app.full_name', 'Full Name')); ?></th>
                        <td><?php echo e($currentUser['full_name'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo e(lang('app.level', 'Level')); ?></th>
                        <td><?php echo e(ucfirst($currentUser['level'])); ?></td>
                    </tr>
                </table>

                <a href="<?php echo e(url('index.php?module=home')); ?>" class="btn btn-primary">Go to Home</a>
                <a href="<?php echo e(url('index.php?module=login&action=logout')); ?>" class="btn btn-outline-danger">Log out</a>
            </div>
        </div>
    </div>
</div>
</div>
</div>
</body>
</html>