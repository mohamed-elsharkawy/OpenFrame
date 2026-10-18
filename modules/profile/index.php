<?php
/**
 * Module: My Profile (view)
 */

$user  = current_user();
if ($user === null) {
    redirect(url('index.php?module=login'));
}

$values = array(
    'full_name' => input('full_name', $user['full_name']),
    'username'  => input('username',  $user['username']),
);
$errors = array();

if (is_post()) {
    csrf_check();

    if ($values['full_name'] === '') {
        $errors[] = lang('profile.full_name');
    }
    if ($values['username'] === '') {
        $errors[] = lang('profile.username');
    }

    $password    = isset($_POST['password']) ? (string) trim($_POST['password']) : '';
    $password2   = isset($_POST['password2']) ? (string) trim($_POST['password2']) : '';
    $changePass  = ($password !== '' || $password2 !== '');

    if ($changePass) {
        if ($password === '') {
            $errors[] = lang('login.password');
        } elseif (mb_strlen($password) < 4) {
            $errors[] = lang('profile.password_short');
        } elseif ($password !== $password2) {
            $errors[] = lang('profile.password_mismatch');
        }
    }

    /* Unique username check (except current user) */
    if (!$errors) {
        $dup = db_row(
            'SELECT id FROM users WHERE username = ? AND id <> ?',
            array($values['username'], (int) $user['id'])
        );
        if ($dup !== null) {
            $errors[] = lang('profile.username_taken');
        }
    }

    if (!$errors) {
        if ($changePass) {
            db_exec(
                'UPDATE users SET full_name = ?, username = ?, password_hash = ? WHERE id = ?',
                array(
                    $values['full_name'],
                    $values['username'],
                    password_hash($password, PASSWORD_DEFAULT),
                    (int) $user['id'],
                )
            );
        } else {
            db_exec(
                'UPDATE users SET full_name = ?, username = ? WHERE id = ?',
                array($values['full_name'], $values['username'], (int) $user['id'])
            );
        }

        flash('success', lang('profile.saved_ok'));
        redirect(url('index.php?module=profile'));
    }
}
?>

<h1 class="h4 mb-3">
    <i class="bi bi-person-circle me-1"></i><?php echo e(lang('profile.title')); ?>
</h1>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <?php echo e(implode(' — ', $errors)); ?>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="<?php echo e(url('index.php?module=profile')); ?>">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label"><?php echo e(lang('profile.level')); ?></label>
                        <input type="text" class="form-control form-control-plaintext of-readonly"
                               value="<?php echo e(is_rtl() ? $user['level_name_ar'] : $user['level_name_en']); ?>" readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="full_name">
                            <?php echo e(lang('profile.full_name')); ?> <span class="of-required">*</span>
                        </label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required
                               value="<?php echo e($values['full_name']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="username">
                            <?php echo e(lang('login.username')); ?> <span class="of-required">*</span>
                        </label>
                        <input type="text" class="form-control" id="username" name="username" required
                               value="<?php echo e($values['username']); ?>" autocomplete="username">
                    </div>

                    <div class="alert alert-light border mb-3">
                        <i class="bi bi-key me-1"></i><?php echo e(lang('profile.password_hint')); ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="password"><?php echo e(lang('login.password')); ?></label>
                            <input type="password" class="form-control" id="password" name="password"
                                   autocomplete="new-password">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="password2"><?php echo e(lang('profile.password_confirm')); ?></label>
                            <input type="password" class="form-control" id="password2" name="password2"
                                   autocomplete="new-password">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i><?php echo e(lang('common.save')); ?>
                        </button>
                        <a href="<?php echo e(url('index.php?module=dashboard')); ?>" class="btn btn-outline-secondary">
                            <?php echo e(lang('common.cancel')); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
