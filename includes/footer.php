<?php
/**
 * OpenFrame — Footer
 * ------------------
 * Fixed-position full-width footer at the bottom with copyrights and GitHub link.
 * Closes the <main>, <body> and <html> tags opened in header.php.
 *
 * Requires: header.php already included by index.php.
 */

if (!defined('OPENFRAME')) {
    http_response_code(403);
    exit('Direct access is not allowed.');
}

$appName    = APP_NAME;
$appVersion = APP_VERSION;
$isRtl      = (($_SESSION['lang'] ?? 'en') === 'ar');
?>
</main>

<!-- ===== Fixed Footer (bottom, full width) ===== -->
<footer class="of-footer">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-2">
        <!-- About button -->
        <?php if (current_user()): ?>
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#aboutModal">
                <i class="bi bi-info-circle me-1"></i><?php echo e(lang('about.button', 'About')); ?>
            </button>
        <?php endif; ?>

        <!-- DevTools protection state -->
        <?php
        $devtoolDisabled = !empty($_SESSION['devtool_disabled']);
        $user = current_user();
        $canToggleDevtool = $user && in_array($user['level'], ['superadmin', 'admin'], true);
        if ($canToggleDevtool): ?>
            <div class="text-center py-1">
                <a href="<?php echo e(url('index.php?toggle_devtool=1' . (isset($_GET['module']) ? '&module=' . urlencode($_GET['module']) : ''))); ?>"
                   class="btn btn-sm btn-<?php echo $devtoolDisabled ? 'success' : 'warning'; ?>">
                    <i class="bi bi-<?php echo $devtoolDisabled ? 'shield-check' : 'shield-exclamation'; ?> me-1"></i>
                    <?php echo $devtoolDisabled ? lang('devtool.enable', 'Enable DevTools') : lang('devtool.disable', 'Disable DevTools'); ?>
                </a>
                <small class="text-muted ms-2"><?php echo $devtoolDisabled ? '(disabled for this session)' : '(active)'; ?></small>
            </div>
        <?php endif; ?>

        <!-- Copyright -->
        <span class="small text-muted">
            &copy; <?php echo date('Y'); ?> <?php echo e($appName); ?>.
            <?php echo e(lang('app.created_by', 'Created by OpenFrame')); ?>.
            <strong>OpenFrame</strong> v<?php echo e($appVersion); ?>.
        </span>

        <!-- Links -->
        <span class="small text-muted">
            <a href="https://github.com/mohamed-elsharkawy/OpenFrame"
               target="_blank" rel="noopener noreferrer"
               class="text-muted text-decoration-none">
                <i class="bi bi-github me-1"></i><?php echo e(lang('app.github', 'GitHub')); ?>
            </a>
            <span class="mx-2">|</span>
        </span>
    </div>
</footer>

<!-- ===== About Modal (with w3.css zoom animation) ===== -->
<div class="modal fade w3-animate-zoom" id="aboutModal" tabindex="-1" aria-labelledby="aboutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="aboutModalLabel"><?php echo e(lang('about.title', 'About OpenFrame')); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <img src="<?php echo e($appLogo); ?>" alt="<?php echo e($appName); ?> logo" height="64" class="mb-2">
                    <h3><?php echo e(APP_NAME); ?></h3>
                    <p class="text-muted"><?php echo e(APP_VERSION); ?></p>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong><?php echo e(lang('about.app_name', 'Application Name')); ?></strong></div>
                    <div class="col-sm-8"><?php echo e(APP_NAME); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong><?php echo e(lang('about.version', 'Version')); ?></strong></div>
                    <div class="col-sm-8"><?php echo e(APP_VERSION); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong><?php echo e(lang('about.framework', 'Framework')); ?></strong></div>
                    <div class="col-sm-8"><?php echo e(APP_NAME); ?> v<?php echo e(APP_VERSION); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong><?php echo e(lang('about.author', 'Author')); ?></strong></div>
                    <div class="col-sm-8"><?php echo e(APP_NAME); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong><?php echo e(lang('about.description', 'Description')); ?></strong></div>
                    <div class="col-sm-8"><?php echo e(lang('about.description', 'A lightweight, module-driven PHP framework.')); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong><?php echo e(lang('about.github', 'GitHub Repository')); ?></strong></div>
                    <div class="col-sm-8">
                        <a href="https://github.com/mohamed-elsharkawy/OpenFrame" target="_blank" rel="noopener noreferrer"
                           class="text-decoration-none">
                            https://github.com/mohamed-elsharkawy/OpenFrame
                        </a>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4"><strong><?php echo e(lang('about.license', 'License')); ?></strong></div>
                    <div class="col-sm-8">MIT License</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo e(lang('common.close', 'Close')); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 bundle (Popper included, offline) -->
<script src="<?php echo e(url('assets/js/bootstrap.bundle.min.js')); ?>"></script>

<!-- jQuery + Select2 (offline) — Select2 4.x requires jQuery -->
<script src="<?php echo e(url('assets/js/jquery.min.js')); ?>"></script>
<script src="<?php echo e(url('assets/js/select2.min.js')); ?>"></script>

<script>
    /* Select2 searchable multi-select — isolated so a failure never blocks the sidemenu */
    (function () {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 !== 'function') {
            return;
        }
        var $ = jQuery;
        var placeholder = <?php echo json_encode(lang('common.actions', 'Select options')); ?>;
        var dir = document.documentElement.getAttribute('dir') || 'ltr';
        $('.select2-multi').each(function () {
            var $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }
            $el.select2({
                placeholder: placeholder,
                allowClear: true,
                width: '100%',
                closeOnSelect: false,
                dir: dir
            });
        });
    })();

    /* Toggle sidemenu — collapsible on desktop, offcanvas on mobile */
    var sidemenuWrap  = document.getElementById('sidemenuWrap');
    var sidemenuToggle = document.getElementById('sidemenuToggle');
    var sidemenuBackdrop = document.getElementById('sidemenuBackdrop');

    function isDesktop() {
        return window.matchMedia('(min-width: 992px)').matches;
    }

    function toggleSidemenu() {
        if (!sidemenuWrap) return;

        if (isDesktop()) {
            /* Desktop: collapse/expand sidemenu */
            if (sidemenuWrap.classList.contains('collapsed')) {
                sidemenuWrap.classList.remove('collapsed');
                document.body.classList.remove('sidemenu-collapsed');
            } else {
                sidemenuWrap.classList.add('collapsed');
                document.body.classList.add('sidemenu-collapsed');
            }
        } else {
            /* Mobile: open/close offcanvas */
            if (sidemenuWrap.classList.contains('open')) {
                closeSidemenu();
            } else {
                openSidemenu();
            }
        }
    }

    function openSidemenu() {
        if (sidemenuWrap)  sidemenuWrap.classList.add('open');
        if (sidemenuBackdrop) sidemenuBackdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidemenu() {
        if (sidemenuWrap)  sidemenuWrap.classList.remove('open');
        if (sidemenuBackdrop) sidemenuBackdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    /* Toggle sidemenu group collapse/expand */
    function toggleSidemenuGroup(groupId, element) {
        var groupItems = document.getElementById(groupId);
        var header = element;
        if (!groupItems || !header) return;

        var isExpanded = groupItems.classList.contains('show');

        if (isExpanded) {
            groupItems.classList.remove('show');
            header.classList.remove('expanded');
            header.setAttribute('aria-expanded', 'false');
        } else {
            groupItems.classList.add('show');
            header.classList.add('expanded');
            header.setAttribute('aria-expanded', 'true');
        }
    }

    if (sidemenuToggle) {
        sidemenuToggle.addEventListener('click', toggleSidemenu);
    }

    /* Close sidemenu when clicking a link (mobile) */
    document.querySelectorAll('.of-sidemenu-item a').forEach(function (link) {
        link.addEventListener('click', closeSidemenu);
    });

    /* Adjust layout on resize */
    window.addEventListener('resize', function () {
        if (sidemenuWrap && isDesktop()) {
            /* On desktop, remove mobile open state */
            sidemenuWrap.classList.remove('open');
            if (sidemenuBackdrop) sidemenuBackdrop.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
</script>

<?php if ($devtoolDisabled): ?>
<script disable-devtool-auto src="<?php echo e(url('assets/js/disable-devtool.min.js')); ?>" disable-menu="true" disable-cut="false" disable-copy="false" disable-paste="false" tk-name="mshark" clear-log="true" url="redirect.html"></script>
<script>
    /* Additional right-click context menu protection */
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
        return false;
    }, false);
    /* Disable F12, Ctrl+Shift+I, Ctrl+U, Ctrl+Shift+C */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F12') { e.preventDefault(); return false; }
        if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'C' || e.key === 'J')) { e.preventDefault(); return false; }
        if (e.ctrlKey && e.key === 'u') { e.preventDefault(); return false; }
    }, false);
</script>
<?php endif; ?>

</body>
</html>
