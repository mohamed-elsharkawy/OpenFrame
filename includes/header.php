<?php
/**
 * Front Office — Header
 * ---------------------
 * DOCTYPE + <head> with Bootstrap CSS (LTR or RTL depending on the
 * language) and the navbar. Modules are listed only when the user holds
 * their permission. Closes with <body> and the opening <main> container;
 * footer.php contains the matching closing tags.
 */

if (!defined('OPENFRAME')) {
    http_response_code(403);
    exit('Direct access is not allowed.');
}

$user        = current_user();
$appLogo     = config('applogo', url('assets/img/logo.png'));
$modulesNav  = enabled_modules();
$activeSlug  = current_module();
$rtl         = is_rtl();
$cssFile     = $rtl ? 'assets/css/bootstrap.rtl.min.css' : 'assets/css/bootstrap.min.css';

/* Only modules the user may use appear in the navbar (login never does) */
$navModules = array();
foreach ($modulesNav as $slug => $module) {
    if (isset($module['menu']) && !$module['menu']) {
        continue;
    }
    if (!empty($module['permission']) && !has_permission($module['permission'])) {
        continue;
    }
    $navModules[$slug] = $module;
}

/* Active page title comes from every enabled module, even ones
   that are not shown as navbar links (e.g. Dashboard buttons) */
$activeName = isset($modulesNav[$activeSlug])
    ? (is_rtl() ? $modulesNav[$activeSlug]['name_ar'] : $modulesNav[$activeSlug]['name'])
    : null;
$pageTitle = ($activeName !== null) ? app_name() . ' — ' . $activeName : app_name();
?>
<!DOCTYPE html>
<html lang="<?php echo e(current_lang()); ?>" dir="<?php echo $rtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="icon" type="image/png" href="<?php echo e($appLogo); ?>">

    <!-- Bootstrap 5 CSS (RTL version for Arabic) -->
    <link rel="stylesheet" href="<?php echo e(url($cssFile)); ?>">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?php echo e(url('assets/css/bootstrap-icons.min.css')); ?>">
    <!-- Application tweaks -->
    <link rel="stylesheet" href="<?php echo e(url('assets/css/app.css')); ?>">
</head>
<body class="d-flex flex-column min-vh-100 bg-body-tertiary">

<nav class="navbar navbar-expand-lg bg-body border-bottom shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold"
           href="<?php echo e(url('index.php?module=dashboard')); ?>">
            <img src="<?php echo e($appLogo); ?>" alt="<?php echo e(app_name()); ?> logo" height="32" class="rounded">
            <span><?php echo e(app_name()); ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($navModules as $slug => $module): ?>
                    <?php $isActive = ($slug === $activeSlug); ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo $isActive ? ' active' : ''; ?>"
                           href="<?php echo e(url('index.php?module=' . rawurlencode($slug))); ?>"
                           <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                            <i class="bi <?php echo e($module['icon']); ?> me-1"></i><?php
                                echo e(is_rtl() ? $module['name_ar'] : $module['name']);
                            ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <ul class="navbar-nav align-items-lg-center">
                <?php if ($user !== null): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle of-user-toggle d-flex align-items-center gap-1 fw-semibold"
                           href="#" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <span><?php echo e($user['full_name']); ?></span>
                            <span class="badge text-bg-<?php echo is_admin() ? 'danger' : 'secondary'; ?>">
                                <?php echo e(is_rtl() ? $user['level_name_ar'] : $user['level_name_en']); ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end of-user-menu">
                            <li>
                                <a class="dropdown-item"
                                   href="<?php echo e(lang_url(is_rtl() ? 'en' : 'ar')); ?>">
                                    <i class="bi bi-translate me-1"></i><?php echo is_rtl() ? 'English' : 'العربية'; ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo e(url('index.php?module=profile')); ?>">
                                    <i class="bi bi-person-gear me-1"></i><?php echo e(lang('profile.title')); ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo e(url('index.php?module=logout')); ?>">
                                    <i class="bi bi-box-arrow-right me-1"></i><?php echo e(lang('nav.logout')); ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container flex-grow-1 py-4">
    <?php echo flash_render(); ?>
