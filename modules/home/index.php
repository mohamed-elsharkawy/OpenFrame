<?php
/**
 * OpenFrame — Module: Home (view)
 * Receives:
 *   $module  -> properties of the current module (from its info.php)
 *   $modulesNav / $activeSlug / $appName / ... from header.php
 */

$allModules = get_modules();
?>

<div class="p-5 mb-4 bg-body rounded-4 shadow-sm border">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <h1 class="display-5 fw-bold mb-3">
                <img src="<?php echo e(config('applogo')); ?>" alt="" height="56" class="rounded me-2 align-middle">
                Welcome to <?php echo e(config('appname', 'OpenFrame')); ?>
            </h1>
            <p class="lead text-muted mb-4">
                A lightweight PHP framework built with plain PHP and Bootstrap <?php echo '5.3'; ?>.
                Modules are discovered automatically: drop a folder into <code>modules/</code>,
                add an <code>info.php</code> file, and it appears in the routes and navbar.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo e(url('index.php?module=home')); ?>" class="btn btn-primary btn-lg">
                    <i class="bi bi-rocket-takeoff me-1"></i>Get started
                </a>
                <a href="<?php echo e(url('index.php?module=home')); ?>" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-folder2-open me-1"></i>Browse modules
                </a>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 bg-primary bg-gradient text-white shadow">
                <div class="card-body p-4">
                    <h2 class="h5 card-title mb-3">
                        <i class="bi bi-info-circle me-1"></i>Application info
                    </h2>
                    <ul class="list-unstyled mb-0 small">
                        <li class="d-flex justify-content-between py-1 border-bottom border-white-25">
                            <span>App name</span><strong><?php echo e(config('appname')); ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom border-white-25">
                            <span>Version</span><strong><?php echo e(config('appversion')); ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom border-white-25">
                            <span>App path</span><code class="text-white"><?php echo e(config('apppath')); ?></code>
                        </li>
                        <li class="d-flex justify-content-between py-1 border-bottom border-white-25">
                            <span>Modules</span><strong><?php echo count($allModules); ?></strong>
                        </li>
                        <li class="d-flex justify-content-between py-1">
                            <span>PHP</span><strong><?php echo e(PHP_VERSION); ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<h2 class="h4 mb-3">
    <i class="bi bi-grid me-1"></i>Installed modules
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
                                <span class="badge text-bg-secondary">disabled</span>
                            <?php endif; ?>
                        </h3>
                    </div>
                    <p class="card-text text-muted small mb-2"><?php echo e($mod['description']); ?></p>
                    <span class="badge text-bg-primary">v<?php echo e($mod['version']); ?></span>
                    <span class="badge text-bg-secondary"><?php echo e($mod['slug']); ?></span>
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
                    <i class="bi bi-diagram-3 me-1 text-primary"></i>How routing works
                </h3>
                <ol class="small text-muted mb-0">
                    <li><code>index.php</code> boots the framework (config → modules → routes).</li>
                    <li><code>routes.php</code> builds the route table from the enabled modules.</li>
                    <li><code>current_module()</code> reads <code>?module=</code> and validates it.</li>
                    <li><code>load_module()</code> executes the module view between header and footer.</li>
                </ol>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h3 class="h5 card-title">
                    <i class="bi bi-plus-square me-1 text-success"></i>Create a module
                </h3>
                <p class="small text-muted mb-2">Create <code>modules/blog/info.php</code>:</p>
                <pre class="small bg-body-tertiary rounded p-2 mb-2"><code>&lt;?php
return [
  'name'        =&gt; 'Blog',
  'description' =&gt; 'Articles module.',
  'version'     =&gt; '1.0.0',
  'icon'        =&gt; 'bi-journal-text',
  'enabled'     =&gt; true,
];</code></pre>
                <p class="small text-muted mb-0">Add <code>modules/blog/index.php</code> as the view — it appears
                    automatically in the navbar and at <code>?module=blog</code>.</p>
            </div>
        </div>
    </div>
</div>
