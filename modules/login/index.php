<?php
/**
 * Module: Login (view)
 */

$error = '';

if (is_post()) {
    csrf_check();

    $username = input('username');
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error = lang('login.required_fields');
    } elseif (login($username, $password)) {
        redirect(url('index.php?module=dashboard'));
    } else {
        $error = lang('login.invalid');
    }
}

$appLogo = config('applogo', url('assets/img/logo.png'));
$rtl     = is_rtl();
$cssFile = $rtl ? 'assets/css/bootstrap.rtl.min.css' : 'assets/css/bootstrap.min.css';
?>
<!DOCTYPE html>
<html lang="<?php echo e(current_lang()); ?>" dir="<?php echo $rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e(app_name() . ' — ' . lang('login.title')); ?></title>
    <link rel="icon" type="image/png" href="<?php echo e($appLogo); ?>">
    <link rel="stylesheet" href="<?php echo e(url($cssFile)); ?>">
    <link rel="stylesheet" href="<?php echo e(url('assets/css/bootstrap-icons.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(url('assets/css/app.css')); ?>">
</head>
<body class="bg-body-tertiary">

<div class="container of-auth-wrap">
    <div class="row justify-content-center w-100">
        <div class="col-12 col-sm-8 col-md-6 col-lg-4">

            <div class="text-center mb-4">
                <img src="<?php echo e($appLogo); ?>" alt="<?php echo e(app_name()); ?>" height="72" class="rounded shadow-sm mb-3">
                <h1 class="h4 mb-1"><?php echo e(app_name()); ?></h1>
                <p class="text-muted small mb-0"><?php echo e(lang('login.subtitle')); ?></p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h6 card-title mb-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i><?php echo e(lang('login.title')); ?>
                    </h2>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger py-2 small mb-3"><?php echo e($error); ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo e(url('index.php?module=login')); ?>">
                        <?php echo csrf_field(); ?>

                        <div class="mb-3">
                            <label class="form-label" for="username">
                                <?php echo e(lang('login.username')); ?> <span class="of-required">*</span>
                            </label>
                            <input type="text" class="form-control" id="username" name="username" autofocus
                                   value="<?php echo e(input('username')); ?>" autocomplete="username">
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password">
                                <?php echo e(lang('login.password')); ?> <span class="of-required">*</span>
                            </label>
                            <input type="password" class="form-control" id="password" name="password"
                                   autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right me-1"></i><?php echo e(lang('login.sign_in')); ?>
                        </button>
                    </form>
                </div>
            </div>

            <div class="text-center mt-3">
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo e(lang_url(is_rtl() ? 'en' : 'ar')); ?>">
                    <?php echo is_rtl() ? 'English' : 'العربية'; ?>
                </a>
                <div class="small text-muted mt-2">OpenFrame v<?php echo e(config('appversion')); ?></div>
            </div>

        </div>
    </div>
</div>

<script src="<?php echo e(url('assets/js/bootstrap.bundle.min.js')); ?>"></script>
</body>
</html>
