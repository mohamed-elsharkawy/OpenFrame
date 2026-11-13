<?php
/**
 * OpenFrame — Module: Login (view)
 * Simple sign in / sign out with role-based access control.
 */

require_framework();

// If already logged in, redirect to landing page
if (current_user()) {
    $landing = app_session_get('landing_page', 'login');
    if ($landing !== 'login') {
        redirect(url('index.php?module=' . $landing));
    }
}

// Handle logout
if (input('action') === 'logout') {
    logout_user();
}

// Handle login form submission
$error = '';
$username = '';

if (is_post() && input('action') === 'login') {
    csrf_check();

    $username = input('username');
    $password = input('password');

    if ($username === '' || $password === '') {
        $error = lang('msg.wrong_credentials', 'Wrong username or password.');
    } else {
        $user = db_row('SELECT * FROM ' . db_table('users') . ' WHERE username = ?', [$username]);

        if ($user && decrypt_password($user['password_hash']) === $password) {
            session_regenerate_id(true);

            if (otp_is_privileged($user)) {
                app_session_forget('user_id', 'user_level', 'user_name', 'otp_verified');
                app_session_set('otp_pending_user_id', (int) $user['id']);
                app_session_set('otp_attempts', 0);
                app_session_forget('otp_setup_secret');
                redirect(url('index.php?module=otp'));
            }

            otp_finish_login($user);
            flash('success', lang('msg.welcome_back', 'Welcome back.'));

            $landing = app_session_get('landing_page', 'login');
            if ($landing === 'login') {
                $landing = 'home';
            }

            redirect(url('index.php?module=' . $landing));
        } else {
            $error = lang('msg.wrong_credentials', 'Wrong username or password.');
        }
    }
}

?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="<?php echo e(config('applogo')); ?>" alt="<?php echo e(config('appname')); ?> logo" height="48" class="mb-2">
                    <h2 class="h4 card-title mb-0"><?php echo e(config('appname', 'OpenFrame')); ?></h2>
                    <p class="small text-muted"><?php echo e(lang('nav.home', 'Sign in to continue.')); ?></p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="login">

                    <div class="mb-3">
                        <label class="form-label"><?php echo e(lang('common.name', 'Username')); ?></label>
                        <input class="form-control" name="username" required autofocus
                               value="<?php echo e($username); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo e(lang('app.password', 'Password')); ?></label>
                        <input class="form-control" name="password" type="password" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" type="submit"><?php echo e(lang('login.signin', 'Sign in')); ?></button>
                    </div>
                </form>

                <hr class="my-3">

                <p class="small text-muted text-center mb-0">
                    <?php echo e(lang('common.general', 'General')); ?> |
                    <a href="<?php echo e(url('index.php?module=home')); ?>"><?php echo e(lang('nav.home', 'Home')); ?></a>
                </p>
            </div>
        </div>
    </div>
</div>