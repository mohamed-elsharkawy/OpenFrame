<?php
/**
 * OpenFrame — Sidemenu (Arabic / RTL)
 * -----------------------------------
 * Fixed right sidemenu with modules organized by group. Only rendered when
 * the user is logged in.
 *
 * Requires: config.php, modules.php, routes.php, helpers.php.
 */

if (!defined('OPENFRAME')) {
    http_response_code(403);
    exit('Direct access is not allowed.');
}

$appName       = APP_NAME;
$appLogo       = config('applogo', APP_LOGO_PATH);
$appLogoMaxWidth  = APP_LOGO_MAX_WIDTH;
$appLogoMaxHeight = APP_LOGO_MAX_HEIGHT;
$moduleData    = get_modules_by_group();
$moduleGroups  = $moduleData['groups'];
$ungroupedModules = $moduleData['ungrouped'];
$activeSlug    = current_module();
?>
<!-- ===== Sidemenu (outside <main>) ===== -->
<div class="of-sidemenu-wrap of-sidemenu-rtl" id="sidemenuWrap">
    <aside class="of-sidemenu">
        <div class="of-sidemenu-brand">
            <img src="<?php echo e($appLogo); ?>" alt="<?php echo e($appName); ?> logo" class="rounded" style="max-height:<?php echo $appLogoMaxHeight; ?>px; max-width:<?php echo $appLogoMaxWidth; ?>px;">
            <span><?php echo e($appName); ?></span>
        </div>
        <nav class="sidemenu-nav flex-grow-1 overflow-y-auto">
            <!-- Ungrouped modules (no group specified) -->
            <?php foreach ($ungroupedModules as $slug => $module): ?>
                <?php
                if (isset($module['submenu']) && $module['submenu'] === false) {
                    continue;
                }
                $isActive = ($slug === $activeSlug);
                ?>
                <li class="of-sidemenu-item">
                    <a class="<?php echo $isActive ? 'active' : ''; ?>"
                       href="<?php echo e(url('index.php?module=' . rawurlencode($slug))); ?>">
                        <i class="bi <?php echo e($module['icon']); ?> me-2"></i><?php echo e($module['name']); ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <!-- Grouped modules (collapsible by default) -->
            <?php $groupIndex = 0; ?>
            <?php foreach ($moduleGroups as $groupName => $groupModules): ?>
                <?php
                if (!is_group_visible_in_submenu($groupName)) {
                    continue;
                }

                $hasVisibleModules = false;
                foreach ($groupModules as $slug => $module) {
                    if (isset($module['submenu']) && $module['submenu'] === false) {
                        continue;
                    }
                    $hasVisibleModules = true;
                    break;
                }
                if (!$hasVisibleModules) {
                    continue;
                }
                $groupIndex++;
                $groupId = 'sidemenu-group-' . $groupIndex;
                $groupHasActive = false;
                foreach ($groupModules as $slug => $module) {
                    if (isset($module['submenu']) && $module['submenu'] === false) {
                        continue;
                    }
                    if ($slug === $activeSlug) {
                        $groupHasActive = true;
                    }
                }
                ?>
                <div class="sidemenu-group">
                    <!-- Group header (clickable to collapse/expand) -->
                    <div class="sidemenu-group-header" onclick="toggleSidemenuGroup('<?php echo $groupId; ?>', this)"
                         role="button"
                         tabindex="0"
                         aria-expanded="false"
                         aria-controls="<?php echo $groupId; ?>">
                        <i class="bi bi-chevron-down me-2"></i>
                        <?php echo e($groupName); ?>
                        <i class="bi bi-chevron-down float-end mt-1"></i>
                    </div>
                    <!-- Group items (collapsed by default) -->
                    <div class="sidemenu-group-items collapse" id="<?php echo $groupId; ?>" 
                         data-parent="#sidemenuWrap">
                        <?php foreach ($groupModules as $slug => $module): ?>
                            <?php
                            if (isset($module['submenu']) && $module['submenu'] === false) {
                                continue;
                            }
                            $isActive = ($slug === $activeSlug);
                            ?>
                            <li class="of-sidemenu-item">
                                <a class="<?php echo $isActive ? 'active' : ''; ?>"
                                   href="<?php echo e(url('index.php?module=' . rawurlencode($slug))); ?>">
                                    <i class="bi <?php echo e($module['icon']); ?> me-2"></i><?php echo e($module['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </nav>

        <!-- User Profile Section at bottom of sidemenu -->
        <?php if (current_user()): ?>
            <div class="sidemenu-user-section p-3 border-top mt-auto">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <div class="small fw-semibold"><?php echo e(app_session_get('user_name', 'User')); ?></div>
                        <div class="text-muted small text-capitalize"><?php echo e(app_session_get('user_level', 'user')); ?></div>
                    </div>
                </div>
                <a class="dropdown-item d-flex align-items-center gap-2 text-decoration-none" href="<?php echo e(url('index.php?module=profile')); ?>">
                    <i class="bi bi-person"></i><?php echo e(lang('common.profile', 'My Profile')); ?>
                </a>
                <a class="dropdown-item d-flex align-items-center gap-2 text-danger text-decoration-none mt-2" href="<?php echo e(url('index.php?module=login&action=logout')); ?>">
                    <i class="bi bi-box-arrow-right"></i><?php echo e(lang('login.signout', 'Sign out')); ?>
                </a>
            </div>
        <?php endif; ?>
    </aside>
</div>

<!-- Mobile backdrop for sidemenu -->
<div class="of-sidemenu-backdrop" id="sidemenuBackdrop" onclick="closeSidemenu()"></div>
