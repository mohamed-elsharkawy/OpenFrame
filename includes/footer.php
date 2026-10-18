<?php
/**
 * Front Office — Footer
 * ---------------------
 * All closing tags opened in header.php (<main>, footer, Bootstrap JS,
 * </body></html>).
 */

if (!defined('OPENFRAME')) {
    http_response_code(403);
    exit('Direct access is not allowed.');
}
?>
</main>

<footer class="bg-body border-top py-3 mt-auto">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="small text-muted">
            &copy; <?php echo date('Y'); ?> <?php echo e(app_name()); ?>.
            <?php echo e(lang('common.powered_by')); ?>
        </span>
        <span class="small text-muted">
            <i class="bi bi-translate me-1"></i><?php echo e(strtoupper(current_lang())); ?>
        </span>
    </div>
</footer>

<!-- Bootstrap 5 bundle (Popper included) -->
<script src="<?php echo e(url('assets/js/bootstrap.bundle.min.js')); ?>"></script>
</body>
</html>
