<?php
/**
 * OpenFrame — Module: Home (view)
 * Receives:
 *   $module  -> properties of the current module (from its info.php)
 *   $modulesNav / $activeSlug / $appName / ... from header.php
 */

require_framework();

$allModules = get_modules();
?>

<div class="p-5 mb-4 bg-body rounded-4 shadow-sm border">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold mb-3">
                <img src="<?php echo e(config('applogo')); ?>" alt="" class="rounded me-2 align-middle" style="max-height:<?php echo APP_LOGO_MAX_HEIGHT; ?>px; max-width:<?php echo APP_LOGO_MAX_WIDTH; ?>px;">
                <?php echo e(lang('home.welcome', 'Welcome to')); ?> <?php echo e(APP_NAME); ?>
            </h1>
            <p class="lead text-muted mb-4">
                <?php echo e(lang('home.subtitle', 'A lightweight PHP framework built with plain PHP and Bootstrap 5.3.')); ?>
                <?php echo e(lang('common.modules', 'Modules')); ?> are discovered automatically: drop a folder into <code>modules/</code>,
                add an <code>info.php</code> file, and it appears in the routes and navbar.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo e(url('index.php?module=home')); ?>" class="btn btn-primary btn-lg">
                    <i class="bi bi-rocket-takeoff me-1"></i><?php echo e(lang('home.get_started', 'Get started')); ?>
                </a>
                <a href="<?php echo e(url('index.php?module=home')); ?>" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-folder2-open me-1"></i><?php echo e(lang('home.browse_modules', 'Browse modules')); ?>
                </a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 bg-primary bg-gradient text-white shadow">
                <div class="card-body p-4">
                    <h2 class="h5 card-title mb-3">
                        <i class="bi bi-info-circle me-1"></i><?php echo e(lang('home.app_info', 'Application info')); ?>
                    </h2>
                    <ul class="list-unstyled mb-0 small">
                        <li class="d-flex justify-content-between py-1 border-bottom border-white-25">
                            <span><?php echo e(lang('home.app_name', 'App name')); ?></span><strong><?php echo e(APP_NAME); ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom border-white-25">
                            <span><?php echo e(lang('common.version', 'Version')); ?></span><strong><?php echo e(APP_VERSION); ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom border-white-25">
                            <span><?php echo e(lang('home.app_path', 'App path')); ?></span><code class="text-white"><?php echo e(config('apppath')); ?></code>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom border-white-25">
                            <span><?php echo e(lang('common.modules', 'Modules')); ?></span><strong><?php echo count($allModules); ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-1">
                            <span><?php echo e(lang('app.php', 'PHP')); ?></span><strong><?php echo e(PHP_VERSION); ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<h2 class="h4 mb-3">
    <i class="bi bi-grid me-1"></i><?php echo e(lang('home.installed_modules', 'Installed modules')); ?>
</h2>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-4">
    <?php foreach ($allModules as $slug => $mod): ?>
        <div class="col">
            <div class="card h-100 shadow-sm<?php echo $slug === $activeSlug ? ' border-primary' : ''; ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge text-bg-light me-2 fs-6">
                            <i class="bi <?php echo e($mod['icon']); ?>"></i>
                        </span>
                        <h3 class="h5 card-title mb-0">
                            <?php echo e($mod['name']); ?>
                            <?php if (empty($mod['enabled'])): ?>
                                <span class="badge text-bg-secondary"><?php echo e(lang('common.disabled', 'disabled')); ?></span>
                            <?php else: ?>
                                <span class="badge text-bg-success"><?php echo e(lang('common.enabled', 'enabled')); ?></span>
                            <?php endif; ?>
                        </h3>
                    </div>
                    <p class="card-text text-muted small mb-2"><?php echo e($mod['description']); ?></p>
                    <span class="badge text-bg-primary">v<?php echo e($mod['version']); ?></span>
                    <span class="badge text-bg-secondary"><?php echo e($mod['slug']); ?></span>
                    <?php if (!empty($mod['group'])): ?>
                        <span class="badge text-bg-info"><?php echo e($mod['group']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent small text-muted">
                    <i class="bi bi-folder me-1"></i>modules/<?php echo e($mod['folder']); ?>/info.php
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h3 class="h5 card-title">
                    <i class="bi bi-diagram-3 me-1 text-primary"></i><?php echo e(lang('home.how_routing', 'How routing works')); ?>
                </h3>
                <ol class="small text-muted mb-0">
                    <li><?php echo e(lang('home.steps_1', 'index.php boots the framework (config → modules → routes).')); ?></li>
                    <li><?php echo e(lang('home.steps_2', 'routes.php builds the route table from the enabled modules.')); ?></li>
                    <li><?php echo e(lang('home.steps_3', 'current_module() reads ?module= and validates it.')); ?></li>
                    <li><?php echo e(lang('home.steps_4', 'load_module() executes the module view between header and footer.')); ?></li>
                </ol>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h3 class="h5 card-title">
                    <i class="bi bi-plus-square me-1 text-success"></i><?php echo e(lang('home.create_module', 'Create a module')); ?>
                </h3>
                <p class="small text-muted mb-2"><?php echo e(lang('common.create', 'Create')); ?> <code>modules/blog/info.php</code>:</p>
                <pre class="small bg-body-tertiary rounded p-2 mb-2"><code>&lt;?php
return [
  'name'        =&gt; 'Blog',
  'description' =&gt; 'Articles module.',
  'version'     =&gt; '1.0.0',
  'icon'        =&gt; 'bi-journal-text',
  'enabled'     =&gt; true,
  'group'       =&gt; 'System Configuration',
];</code></pre>
                <p class="small text-muted mb-0"><?php echo e(lang('common.add', 'Add')); ?> <code>modules/blog/index.php</code> <?php echo e(lang('home.appears_in_navbar', 'appears automatically in the navbar and at ?module=blog.')); ?></p>
            </div>
        </div>
    </div>
</div>
