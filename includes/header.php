<?php
/**
 * OpenFrame — Header
 * ------------------
 * DOCTYPE + <head> with all Bootstrap CSS/fonts and the top navbar.
 * Module links are generated dynamically from the modules folder.
 * Closes with the opening <body>, navbar and <main> container;
 * footer.php contains the matching closing tags.
 *
 * Requires: config.php, modules.php, routes.php (loaded by index.php).
 */

if (!defined('OPENFRAME')) {
    http_response_code(403);
    exit('Direct access is not allowed.');
}

$appName    = config('appname', 'OpenFrame');
$appVersion = config('appversion', '1.0.0');
$appLogo    = config('applogo', url('assets/img/logo.png'));
$modulesNav = enabled_modules();
$activeSlug = current_module();
$activeName = isset($modulesNav[$activeSlug]['name'])
    ? $modulesNav[$activeSlug]['name']
    : null;
$pageTitle  = $activeName !== null ? $appName . ' — ' . $activeName : $appName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generator" content="OpenFrame <?php echo e($appVersion); ?>">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="icon" type="image/png" href="<?php echo e($appLogo); ?>">

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="<?php echo e(url('assets/css/bootstrap.min.css')); ?>">
    <!-- Bootstrap Icons CSS + fonts (assets/css/fonts/) -->
    <link rel="stylesheet" href="<?php echo e(url('assets/css/bootstrap-icons.min.css')); ?>">
</head>
<body class="d-flex flex-column min-vh-100 bg-body-tertiary">

<nav class="navbar navbar-expand-lg bg-body border-bottom shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" href="<?php echo e(url('index.php')); ?>">
            <img src="<?php echo e($appLogo); ?>" alt="<?php echo e($appName); ?> logo" height="32" class="rounded">
            <span>
                <?php echo e($appName); ?>
                <span class="badge text-bg-primary align-middle">v<?php echo e($appVersion); ?></span>
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#openframeNav"
                aria-controls="openframeNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="openframeNav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($modulesNav as $slug => $module): ?>
                    <?php $isActive = ($slug === $activeSlug); ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo $isActive ? ' active' : ''; ?>"
                           href="<?php echo e(url('index.php?module=' . rawurlencode($slug))); ?>"
                           <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                            <i class="bi <?php echo e($module['icon']); ?> me-1"></i><?php echo e($module['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <span class="navbar-text small text-muted">
                <i class="bi bi-code-slash me-1"></i>Powered by OpenFrame
            </span>
        </div>
    </div>
</nav>

<main class="container flex-grow-1 py-4">
<?php
/* Flash messages queued with flash() are shown automatically here. */
if (function_exists('flash_render')) {
    echo flash_render();
}
?>
