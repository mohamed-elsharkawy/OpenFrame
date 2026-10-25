<?php
/**
 * OpenFrame — Footer
 * ------------------
 * Contains all closing tags opened in header.php (<main>, footer bar,
 * Bootstrap bundle JS, </body></html>).
 *
 * Requires: header.php already included by index.php.
 */

if (!defined('OPENFRAME')) {
    http_response_code(403);
    exit('Direct access is not allowed.');
}

$appName    = config('appname', 'OpenFrame');
$appVersion = config('appversion', '1.0.0');
?>
</main>

<footer class="bg-body border-top py-3 mt-auto">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="small text-muted">
            &copy; <?php echo date('Y'); ?> <?php echo e($appName); ?>.
            Built with <strong>OpenFrame</strong> v<?php echo e($appVersion); ?>.
        </span>
        <span class="small text-muted">
            <i class="bi bi-heart-fill text-danger me-1"></i>PHP <?php echo e(PHP_VERSION); ?>
        </span>
    </div>
</footer>

<!-- Bootstrap 5 bundle (Popper included) -->
<script src="<?php echo e(url('assets/js/bootstrap.bundle.min.js')); ?>"></script>
</body>
</html>
