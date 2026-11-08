<?php
/**
 * OpenFrame — Header
 * ------------------
 * DOCTYPE + <head> with all Bootstrap CSS/fonts.
 * The navbar and sidemenu are separate scripts (navbar.php or navbar-rtl.php,
 * sidemenu.php or sidemenu-rtl.php) and are only included when the user is logged in.
 * Opens <body> and <main>; footer.php contains the matching closing tags.
 *
 * Requires: config.php, modules.php, routes.php, helpers.php (loaded by index.php).
 */

if (!defined('OPENFRAME')) {
    http_response_code(403);
    exit('Direct access is not allowed.');
}

/* ------------------------------------------------------------------
 * Language / RTL
 * ------------------------------------------------------------------ */
$availableLangs = get_available_languages();
$isRtl          = (($_SESSION['lang'] ?? 'en') === 'ar');
$htmlDir        = $isRtl ? 'rtl' : 'ltr';
$htmlLang       = $_SESSION['lang'] ?? 'en';

/* ------------------------------------------------------------------
 * Layout variables
 * ------------------------------------------------------------------ */
$appName       = APP_NAME;
$appVersion    = APP_VERSION;
$appLogo       = config('applogo', APP_LOGO_PATH);
$appLogoMaxWidth  = APP_LOGO_MAX_WIDTH;
$appLogoMaxHeight = APP_LOGO_MAX_HEIGHT;
$modulesNav    = enabled_modules();
$activeSlug    = current_module();
$activeName    = isset($modulesNav[$activeSlug]['name'])
    ? $modulesNav[$activeSlug]['name']
    : null;
$pageTitle  = $activeName !== null ? $appName . ' — ' . $activeName : $appName;

/* ------------------------------------------------------------------
 * Show navbar and sidemenu only when user is logged in.
 * On login / landing pages (not authenticated), both are hidden.
 * ------------------------------------------------------------------ */
$isLoggedIn = current_user() !== null;
?>
<!DOCTYPE html>
<html lang="<?php echo e($htmlLang); ?>" dir="<?php echo e($htmlDir); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="generator" content="OpenFrame <?php echo e($appVersion); ?>">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="icon" type="image/png" href="<?php echo e($appLogo); ?>">

    <!-- Bootstrap 5 CSS (offline) — RTL when Arabic, LTR when English -->
    <?php if ($isRtl): ?>
        <link rel="stylesheet" href="<?php echo e(url('assets/css/bootstrap.rtl.min.css')); ?>">
    <?php else: ?>
        <link rel="stylesheet" href="<?php echo e(url('assets/css/bootstrap.min.css')); ?>">
    <?php endif; ?>
    <!-- Bootstrap Icons CSS + fonts (offline) -->
    <link rel="stylesheet" href="<?php echo e(url('assets/css/bootstrap-icons.min.css')); ?>">
    <!-- Select2 CSS (offline) — searchable multiselect -->
    <link rel="stylesheet" href="<?php echo e(url('assets/css/select2.min.css')); ?>">
    <!-- W3.CSS (offline) — animations -->
    <link rel="stylesheet" href="<?php echo e(url('assets/css/w3.css')); ?>">
    <!-- Custom RTL overrides (offline) — only for Arabic -->
    <?php if ($isRtl): ?>
        <link rel="stylesheet" href="<?php echo e(url('assets/css/rtl.css')); ?>">
    <?php endif; ?>

    <!-- Custom OpenFrame layout (fixed navbar, sidemenu, fixed footer) -->
    <style>
        :root {
            --of-navbar-height: 64px;
            --of-footer-height: 50px;
            --of-sidebar-width: 260px;
        }

        body {
            overflow-x: hidden;
        }

        /* Only apply navbar padding when navbar is shown */
        <?php if ($isLoggedIn): ?>
        body {
            padding-top: var(--of-navbar-height);
            padding-bottom: var(--of-footer-height);
        }

        /* Fixed navbar — high contrast */
        .of-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1035;
            height: var(--of-navbar-height);
            background: #fff !important;
            border-bottom: 2px solid var(--bs-border-color);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .of-navbar .navbar-brand {
            color: var(--bs-dark) !important;
            font-weight: 700;
        }

        .of-navbar .nav-link {
            color: var(--bs-dark) !important;
            font-weight: 500;
        }

        .of-navbar .nav-link:hover,
        .of-navbar .nav-link:focus,
        .of-navbar .nav-link.active {
            color: var(--bs-primary) !important;
        }

        .of-navbar .navbar-toggler-icon {
            filter: invert(0);
        }

        .of-navbar .text-muted {
            color: #6c757d !important;
        }

        /* Sidemenu toggle button — dark high contrast */
        .of-sidebar-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #343a40;
            border: none;
            color: #fff;
            font-size: 1.1rem;
            width: 36px;
            height: 36px;
            border-radius: 0.375rem;
        }

        .of-sidebar-toggle:hover {
            background: #495057;
            color: #fff;
        }

        .of-sidebar-toggle:focus {
            box-shadow: 0 0 0 0.2rem rgba(52, 58, 64, 0.5);
        }

        /* RTL: toggle on left side of navbar */
        [dir="rtl"] .of-sidebar-toggle {
            margin-left: 0;
            margin-right: 0.5rem;
        }

        /* LTR: toggle on right side of navbar brand */
        [dir="ltr"] .of-sidebar-toggle {
            margin-left: 0.5rem;
            margin-right: 0;
        }

        /* Sidemenu wrapper */
        .of-sidemenu-wrap {
            position: fixed;
            top: var(--of-navbar-height);
            bottom: var(--of-footer-height);
            width: var(--of-sidebar-width);
            z-index: 1030;
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform 0.25s ease, width 0.25s ease;
        }

        /* LTR Sidemenu: starts on left */
        .of-sidemenu-ltr {
            left: 0;
            right: auto;
            transform: translateX(-100%);
        }
        .of-sidemenu-ltr.open {
            transform: translateX(0);
        }
        .of-sidemenu-ltr.collapsed {
            width: 0;
            transform: translateX(-100%);
        }

        /* RTL Sidemenu: starts on right */
        .of-sidemenu-rtl {
            left: auto;
            right: 0;
            transform: translateX(100%);
        }
        .of-sidemenu-rtl.open {
            transform: translateX(0);
        }
        .of-sidemenu-rtl.collapsed {
            width: 0;
            transform: translateX(100%);
        }

        /* Desktop: sidemenu visible by default, body padding */
        @media (min-width: 992px) {
            .of-sidemenu-ltr {
                transform: translateX(0);
                left: 0;
                right: auto;
            }
            .of-sidemenu-rtl {
                transform: translateX(0);
                left: auto;
                right: 0;
            }

            body {
                padding-left: var(--of-sidebar-width);
                padding-right: 0;
            }
            body.sidemenu-collapsed {
                padding-left: 0;
                padding-right: 0;
            }
            .of-sidemenu-ltr.collapsed {
                width: 0;
                transform: translateX(-100%);
            }
            .of-sidemenu-rtl.collapsed {
                width: 0;
                transform: translateX(100%);
            }
            /* RTL: padding on right instead of left */
            [dir="rtl"] body {
                padding-left: 0;
                padding-right: var(--of-sidebar-width);
            }
            [dir="rtl"] body.sidemenu-collapsed {
                padding-left: 0;
                padding-right: 0;
            }
        }

        /* Mobile: sidemenu hidden by default */
        @media (max-width: 991.98px) {
            body {
                padding-left: 0;
                padding-right: 0;
            }
            /* LTR mobile: hide from left */
            .of-sidemenu-ltr {
                transform: translateX(-100%);
            }
            .of-sidemenu-ltr.open {
                transform: translateX(0);
            }
            /* RTL mobile: hide from right */
            .of-sidemenu-rtl {
                transform: translateX(100%);
            }
            .of-sidemenu-rtl.open {
                transform: translateX(0);
            }
        }
        <?php endif; ?>

        /* Main content padding so content is not hidden under navbar/footer */
        main {
            padding-top: 8vh !important;
            padding-bottom: 8vh !important;
        }

        /* Fixed footer */
        .of-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: var(--of-footer-height);
            z-index: 1035;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            background: var(--bs-body-bg);
            border-top: 1px solid var(--bs-border-color);
        }

        @media (min-width: 992px) {
            .of-footer {
                left: 0;
                right: 0;
                width: 100%;
            }
        }

        /* Select2 — match Bootstrap form-select, stay above navbar */
        .select2-container {
            width: 100% !important;
        }
        .select2-container--default .select2-selection--multiple {
            min-height: calc(1.5em + 0.75rem + 2px);
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: var(--bs-border-radius, 0.375rem);
            padding: 2px 6px;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple,
        .select2-container--default.select2-container--open .select2-selection--multiple {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .select2-dropdown {
            z-index: 2000;
            border-color: var(--bs-border-color, #dee2e6);
        }
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: var(--bs-primary, #0d6efd);
        }
        [dir="rtl"] .select2-container--default .select2-selection--multiple .select2-selection__choice {
            margin-left: 0;
            margin-right: 5px;
        }

        /* Sidemenu styles */
        .of-sidemenu {
            height: 100%;
            background: var(--bs-dark);
            color: #fff;
            display: flex;
            flex-direction: column;
        }

        .of-sidemenu a {
            color: #fff;
            text-decoration: none;
        }

        .of-sidemenu a:hover,
        .of-sidemenu a:focus {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        .of-sidemenu-brand {
            padding: 1rem;
            font-size: 1.25rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .of-sidemenu-brand img {
            height: 32px;
            width: 32px;
        }

        .of-sidemenu-group-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.75rem 1rem 0.5rem 1rem;
            color: rgba(255, 255, 255, 0.5);
            margin: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .of-sidemenu-item {
            list-style: none;
        }

        .of-sidemenu-item a {
            display: block;
            padding: 0.65rem 1rem;
            font-size: 0.95rem;
            border-left: 3px solid transparent;
        }

        .of-sidemenu-item a.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: var(--bs-primary);
        }

        /* RTL: sidemenu item active border on right */
        [dir="rtl"] .of-sidemenu-item a {
            border-left: none;
            border-right: 3px solid transparent;
        }
        [dir="rtl"] .of-sidemenu-item a.active {
            border-left: none;
            border-right-color: var(--bs-primary);
        }

        .of-sidemenu-item i {
            width: 1.5rem;
            text-align: center;
        }

        /* RTL: reverse icon margin */
        [dir="rtl"] .of-sidemenu-item .me-2 {
            margin-left: 0.5rem !important;
            margin-right: 0 !important;
        }

        /* User profile section at bottom of sidemenu */
        .sidemenu-user-section {
            margin-top: auto;
            padding-top: 0.75rem;
        }

        .sidemenu-user-section .dropdown-item {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: background 0.15s;
        }

        .sidemenu-user-section .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            text-decoration: none;
        }

        .sidemenu-user-section .dropdown-item.text-danger:hover {
            background: rgba(220, 53, 69, 0.15);
            color: #ffcaca;
        }

        /* Backdrop for mobile sidemenu */
        .of-sidemenu-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1025;
        }
        .of-sidemenu-backdrop.show {
            display: block;
        }

        /* Sidemenu group collapsible header */
        .sidemenu-group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(255, 255, 255, 0.5);
            margin: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            cursor: pointer;
            transition: background 0.15s;
        }

        .sidemenu-group-header:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .sidemenu-group-header i:first-child {
            transition: transform 0.2s;
        }

        .sidemenu-group-header.expanded i:first-child {
            transform: rotate(180deg);
        }

        .sidemenu-group-items.collapse {
            display: none;
        }

        .sidemenu-group-items.collapse.show {
            display: block;
        }

        /* About Modal — wider on desktop, reduced height on low resolution */
        .modal-dialog.modal-lg {
            max-width: 800px;
            width: 90%;
        }

        /* Low resolution screens (max-height: 700px) — reduce modal height */
        @media (max-height: 700px) {
            .modal-dialog.modal-lg {
                max-width: 90%;
                margin-top: 2rem;
                margin-bottom: 2rem;
            }
            .modal-content {
                max-height: 85vh;
                overflow-y: auto;
            }
            .modal-body {
                max-height: 60vh;
                overflow-y: auto;
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-body-tertiary">

    <?php if ($isLoggedIn): ?>
        <?php if ($isRtl): ?>
            <?php require __DIR__ . '/navbar-rtl.php'; ?>
            <?php require __DIR__ . '/sidemenu-rtl.php'; ?>
        <?php else: ?>
            <?php require __DIR__ . '/navbar.php'; ?>
            <?php require __DIR__ . '/sidemenu.php'; ?>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ===== Main content ===== -->
    <main class="container flex-grow-1 py-4">
    <?php
    /* Flash messages queued with flash() are shown automatically here. */
    if (function_exists('flash_render')) {
        echo flash_render();
    }
    ?>
