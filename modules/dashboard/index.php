<?php
/**
 * Module: Dashboard (view)
 */

$clockLocale = current_lang() === 'ar' ? 'ar-EG' : 'en-GB';

$actions = array(
    array('module' => 'guest_new',    'perm' => 'guest_create', 'icon' => 'bi-person-plus-fill',  'label' => lang('dashboard.new_guest'),    'class' => 'btn-primary'),
    array('module' => 'guest_leave',  'perm' => 'guest_leave',  'icon' => 'bi-person-dash-fill',  'label' => lang('dashboard.record_leave'), 'class' => 'btn-success'),
    array('module' => 'guest_search', 'perm' => 'guest_search', 'icon' => 'bi-search',            'label' => lang('dashboard.search_guest'), 'class' => 'btn-info'),
);

$adminActions = array(
    array('module' => 'report_period', 'perm' => 'report_period',    'icon' => 'bi-calendar3-range',   'label' => lang('dashboard.report_period'), 'class' => 'btn-warning'),
    array('module' => 'report_users',  'perm' => 'report_users',     'icon' => 'bi-people-fill',       'label' => lang('dashboard.report_users'),  'class' => 'btn-secondary'),
    array('module' => 'departments',   'perm' => 'departments_manage', 'icon' => 'bi-diagram-3-fill',  'label' => lang('dashboard.departments'),   'class' => 'btn-dark'),
    array('module' => 'users',         'perm' => 'users_manage',      'icon' => 'bi-person-gear',       'label' => lang('dashboard.users'),         'class' => 'btn-outline-dark'),
);

$visibleActions = array();
foreach ($actions as $action) {
    if (has_permission($action['perm'])) {
        $visibleActions[] = $action;
    }
}

$visibleAdminActions = array();
foreach ($adminActions as $action) {
    if (has_permission($action['perm'])) {
        $visibleAdminActions[] = $action;
    }
}
?>

<div class="text-center mb-4">
    <h1 class="h4 mb-1"><?php echo e(app_name()); ?> — <?php echo e(lang('dashboard.title')); ?></h1>
    <p class="text-muted small mb-0">
        <?php echo e(lang('dashboard.clock')); ?>
    </p>
</div>

<div class="card shadow-sm mb-4 mx-auto" style="max-width: 640px;">
    <div class="card-body text-center of-clock">
        <div class="of-clock-time" id="clockTime">--:--:--</div>
        <div class="of-clock-date" id="clockDate">&nbsp;</div>
    </div>
</div>

<div class="row justify-content-center g-3 mb-2">
    <?php foreach ($visibleActions as $action): ?>
        <div class="col-12 col-md-4">
            <a href="<?php echo e(url('index.php?module=' . $action['module'])); ?>"
               class="btn btn-<?php echo e($action['class']); ?> of-action-btn w-100 h-100 shadow-sm">
                <i class="bi <?php echo e($action['icon']); ?>"></i>
                <span><?php echo e($action['label']); ?></span>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($visibleAdminActions): ?>
    <div class="text-center my-4">
        <span class="badge text-bg-dark px-3 py-2">
            <i class="bi bi-shield-lock me-1"></i><?php
                echo e(lang('dashboard.admin_tools') . ' ' . lang('dashboard.admin_only'));
            ?>
        </span>
    </div>

    <div class="row justify-content-center g-3">
        <?php foreach ($visibleAdminActions as $action): ?>
            <div class="col-12 col-md-4">
                <a href="<?php echo e(url('index.php?module=' . $action['module'])); ?>"
                   class="btn btn-<?php echo e($action['class']); ?> of-action-btn w-100 h-100 shadow-sm">
                    <i class="bi <?php echo e($action['icon']); ?>"></i>
                    <span><?php echo e($action['label']); ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
(function () {
    var locale = '<?php echo e($clockLocale); ?>';
    var timeEl = document.getElementById('clockTime');
    var dateEl = document.getElementById('clockDate');
    var timeFmt = new Intl.DateTimeFormat(locale, {
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
    });
    var dateFmt = new Intl.DateTimeFormat(locale, {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });

    function tick() {
        var now = new Date();
        timeEl.textContent = timeFmt.format(now);
        dateEl.textContent = dateFmt.format(now);
    }

    tick();
    setInterval(tick, 1000);
})();
</script>
