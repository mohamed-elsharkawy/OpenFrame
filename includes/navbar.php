<?php
/**
 * OpenFrame — Navbar (English / LTR)
 * ----------------------------------
 * Fixed top navigation bar with modules organized by group.
 * Only rendered when the user is logged in.
 *
 * Requires: config.php, modules.php, routes.php, helpers.php.
 */

if (!defined('OPENFRAME')) {
    http_response_code(403);
    exit('Direct access is not allowed.');
}

$appName       = APP_NAME;
$appVersion    = APP_VERSION;
$appLogo       = config('applogo', APP_LOGO_PATH);
$appLogoMaxWidth  = APP_LOGO_MAX_WIDTH;
$appLogoMaxHeight = APP_LOGO_MAX_HEIGHT;
$moduleData    = get_modules_by_group();
$moduleGroups  = $moduleData['groups'];
$ungroupedModules = $moduleData['ungrouped'];
$activeSlug    = current_module();
$availableLangs = get_available_languages();
?>
<!-- ===== Fixed Navbar (outside <main>) ===== -->
<nav class="navbar navbar-expand-lg bg-body border-bottom shadow-sm of-navbar">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-semibold" href="<?php echo e(url('')); ?>">
            <img src="<?php echo e($appLogo); ?>" alt="<?php echo e($appName); ?> logo" class="rounded" style="max-height:<?php echo $appLogoMaxHeight; ?>px; max-width:<?php echo $appLogoMaxWidth; ?>px;">
        </a>

        <!-- Sidemenu toggle (desktop + mobile) -->
        <button class="btn btn-sm btn-dark of-sidebar-toggle" type="button" id="sidemenuToggle"
                aria-label="<?php echo e(lang('nav.sidemenu', 'Menu')); ?>">
            <i class="bi bi-list text-white"></i>
        </button>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#openframeNav"
                aria-controls="openframeNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="openframeNav">
            <!-- Module links: pack LEFT -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach ($ungroupedModules as $slug => $module): ?>
                    <?php
                    if (isset($module['menu']) && $module['menu'] === false) {
                        continue;
                    }
                    $isActive = ($slug === $activeSlug);
                    ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo $isActive ? ' active' : ''; ?>"
                           href="<?php echo e(url('index.php?module=' . rawurlencode($slug))); ?>"
                           <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                            <i class="bi <?php echo e($module['icon']); ?> me-1"></i><?php echo e($module['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <?php foreach ($moduleGroups as $groupName => $groupModules): ?>
                    <?php if (!is_group_visible_in_menu($groupName)) { continue; } ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-decoration-none" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-folder me-1"></i><?php echo e($groupName); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($groupModules as $slug => $module): ?>
                                <?php
                                if (isset($module['menu']) && $module['menu'] === false) {
                                    continue;
                                }
                                $isActive = ($slug === $activeSlug);
                                ?>
                                <li><a class="dropdown-item<?php echo $isActive ? ' active' : ''; ?>"
                                       href="<?php echo e(url('index.php?module=' . rawurlencode($slug))); ?>"
                                       <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                                    <i class="bi <?php echo e($module['icon']); ?> me-1"></i><?php echo e($module['name']); ?>
                                </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Language + User: pack RIGHT -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                <?php if (count($availableLangs) > 1): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-globe"></i>
                            <?php echo e(app_session_get('lang', OPENFRAME_DEFAULT_LANG)); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                            <?php foreach ($availableLangs as $langCode): ?>
                                <li><a class="dropdown-item<?php echo app_session_get('lang', OPENFRAME_DEFAULT_LANG) === $langCode ? ' active' : ''; ?>"
                                        href="<?php echo e(url('index.php?module=' . rawurlencode($activeSlug) . '&lang=' . $langCode)); ?>"><?php echo e($langCode === 'ar' ? 'العربية' : 'English'); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>

                <?php if (current_user()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <span class="d-none d-sm-inline"><?php echo e(app_session_get('user_name', 'User')); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><h6 class="dropdown-header"><?php echo e(current_user()['full_name'] ?: app_session_get('user_name', '')); ?></h6></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?php echo e(url('index.php?module=profile')); ?>">
                                <i class="bi bi-person"></i><?php echo e(lang('common.profile', 'My Profile')); ?>
                            </a></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="<?php echo e(url('index.php?module=login&action=logout')); ?>">
                                <i class="bi bi-box-arrow-right"></i><?php echo e(lang('login.signout', 'Sign out')); ?>
                            </a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
