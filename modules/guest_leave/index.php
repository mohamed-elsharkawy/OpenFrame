<?php
/**
 * Module: Record Guest Leave (view)
 */

$q         = input('q');
$selected  = (int) input('select');
$visits    = array();

if ($q !== '') {
    $numeric = ctype_digit($q);
    $visits = db_rows(
        "SELECT gv.*, d.name AS department_name, u.full_name AS entered_by_name
           FROM guest_visits gv
           LEFT JOIN departments d ON d.id = gv.department_id
           LEFT JOIN users u ON u.id = gv.entered_by
          WHERE gv.leave_time IS NULL
            AND (gv.full_name LIKE ? " . ($numeric ? "OR gv.id = ?" : "OR gv.id_number LIKE ?") . ")
          ORDER BY (gv.full_name = ?) DESC, gv.entry_time ASC
          LIMIT 50",
        $numeric
            ? array('%' . like_escape($q) . '%', (int) $q, $q)
            : array('%' . like_escape($q) . '%', '%' . like_escape($q) . '%', $q)
    );
}

/* Nearest result is selected by default */
if ($selected <= 0 && $visits) {
    $selected = (int) $visits[0]['id'];
}

$visit = null;
if ($selected > 0) {
    $visit = db_row(
        "SELECT gv.*, d.name AS department_name, u.full_name AS entered_by_name
           FROM guest_visits gv
           LEFT JOIN departments d ON d.id = gv.department_id
           LEFT JOIN users u ON u.id = gv.entered_by
          WHERE gv.id = ? AND gv.leave_time IS NULL",
        array($selected)
    );
}

if (is_post()) {
    csrf_check();

    $visitId   = (int) input('visit_id');
    $leaveTime = str_replace('T', ' ', input('leave_time'));

    if ($visitId <= 0) {
        flash('warning', lang('guest_leave.pick_first'));
    } elseif (!strtotime($leaveTime)) {
        flash('warning', lang('guest_leave.pick_first'));
    } else {
        $updated = db_exec(
            'UPDATE guest_visits SET leave_time = ? WHERE id = ? AND leave_time IS NULL',
            array(date('Y-m-d H:i:s', strtotime($leaveTime)), $visitId)
        );

        if ($updated > 0) {
            flash('success', str_replace(':id', (string) $visitId, lang('guest_leave.leave_saved')));
            redirect(url('index.php?module=guest_leave&q=' . rawurlencode($q)));
        } else {
            flash('warning', lang('guest_leave.already_closed'));
            redirect(url('index.php?module=guest_leave&q=' . rawurlencode($q)));
        }
    }
}
?>

<h1 class="h4 mb-3">
    <i class="bi bi-person-dash-fill me-1"></i><?php echo e(lang('guest_leave.title')); ?>
</h1>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?php echo e(url('index.php')); ?>" class="row g-2 align-items-end">
            <input type="hidden" name="module" value="guest_leave">
            <div class="col-12 col-md-8">
                <label class="form-label" for="q"><?php echo e(lang('common.search')); ?></label>
                <input type="text" class="form-control" id="q" name="q" value="<?php echo e($q); ?>"
                       placeholder="<?php echo e(lang('guest_leave.search_placeholder')); ?>">
            </div>
            <div class="col-12 col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i><?php echo e(lang('common.search')); ?>
                </button>
            </div>
            <div class="col-12">
                <div class="form-text"><?php echo e(lang('guest_leave.search_hint')); ?></div>
            </div>
        </form>
    </div>
</div>

<?php if ($q !== '' && !$visits): ?>
    <div class="alert alert-info"><?php echo e(lang('guest_leave.no_open_visits')); ?></div>
<?php endif; ?>

<?php if ($visits): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-body">
            <i class="bi bi-list-ul me-1"></i><?php echo e(lang('common.results')); ?>
            <span class="badge text-bg-secondary"><?php echo count($visits); ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo e(lang('grid.record')); ?></th>
                        <th><?php echo e(lang('grid.name')); ?></th>
                        <th><?php echo e(lang('grid.id_number')); ?></th>
                        <th><?php echo e(lang('grid.department')); ?></th>
                        <th><?php echo e(lang('grid.visiting_who')); ?></th>
                        <th><?php echo e(lang('grid.entry')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($visits as $row): ?>
                        <tr class="<?php echo (int) $row['id'] === $selected ? 'table-primary' : ''; ?>">
                            <td class="fw-semibold">
                                <a class="text-decoration-none" href="<?php echo e(url('index.php?module=guest_leave&q=' . rawurlencode($q) . '&select=' . (int) $row['id'])); ?>">
                                    #<?php echo (int) $row['id']; ?>
                                </a>
                            </td>
                            <td><?php echo e($row['full_name']); ?></td>
                            <td><?php echo e($row['id_number']); ?></td>
                            <td><?php echo e($row['department_name'] !== null ? $row['department_name'] : '—'); ?></td>
                            <td><?php echo e($row['visiting_who'] !== null && $row['visiting_who'] !== '' ? $row['visiting_who'] : '—'); ?></td>
                            <td><?php echo e(format_datetime($row['entry_time'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if ($visit !== null): ?>
    <h2 class="h5 mb-3">
        <i class="bi bi-person-lines-fill me-1"></i><?php echo e(lang('guest_leave.selected_visit')); ?>
    </h2>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="post" action="<?php echo e(url('index.php?module=guest_leave')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="visit_id" value="<?php echo (int) $visit['id']; ?>">
                <input type="hidden" name="q" value="<?php echo e($q); ?>">

                <?php
                /* Same design and order as the entry (New Guest) page */
                $readonly = array(
                    lang('guest.record_id')  => '#' . (int) $visit['id'],
                    lang('guest.full_name')  => $visit['full_name'],
                    lang('guest.id_number')  => $visit['id_number'],
                    lang('guest.purpose')    => $visit['purpose'],
                    lang('guest.visiting_who') => ($visit['visiting_who'] !== null && $visit['visiting_who'] !== '') ? $visit['visiting_who'] : '—',
                    lang('guest.department') => ($visit['department_name'] !== null) ? $visit['department_name'] : '—',
                    lang('guest.entry_time') => format_datetime($visit['entry_time']),
                );
                ?>
                <div class="row">
                    <?php $i = 0; foreach ($readonly as $label => $value): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small mb-1"><?php echo e($label); ?></label>
                            <input type="text" class="form-control form-control-plaintext of-readonly"
                                   value="<?php echo e($value); ?>" readonly disabled>
                        </div>
                    <?php $i++; endforeach; ?>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="leave_time">
                            <?php echo e(lang('guest.leave_time')); ?> <span class="of-required">*</span>
                        </label>
                        <div class="input-group">
                            <input type="datetime-local" class="form-control" id="leave_time" name="leave_time" required
                                   value="<?php echo e(date('Y-m-d\TH:i')); ?>">
                            <button type="button" class="btn btn-outline-secondary" id="btnNow"
                                    title="<?php echo e(lang('common.now')); ?>">
                                <i class="bi bi-clock-history me-1"></i><?php echo e(lang('common.now')); ?>
                            </button>
                        </div>
                        <div class="form-text"><?php echo e(lang('guest_leave.now_hint')); ?></div>
                    </div>

                    <?php if (!empty($visit['id_doc_path'])): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small mb-1"><?php echo e(lang('guest.id_doc')); ?></label>
                            <a class="btn btn-outline-primary d-block" target="_blank"
                               href="<?php echo e(url($visit['id_doc_path'])); ?>">
                                <i class="bi bi-image me-1"></i><?php echo e(lang('guest.id_doc_link')); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i><?php echo e(lang('guest_leave.save_leave')); ?>
                    </button>
                    <a href="<?php echo e(url('index.php?module=guest_leave')); ?>" class="btn btn-outline-secondary">
                        <?php echo e(lang('common.cancel')); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php elseif ($q !== '' && $visits): ?>
    <div class="alert alert-warning"><?php echo e(lang('guest_leave.pick_first')); ?></div>
<?php endif; ?>

<?php if ($visit !== null): ?>
<script>
(function () {
    var picker = document.getElementById('leave_time');
    if (!picker) { return; }

    /* Fill the picker with the exact current date/time of the browser
       (still fully editable by the user). */
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function nowValue() {
        var d = new Date();
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
             + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    picker.value = nowValue();

    var btn = document.getElementById('btnNow');
    if (btn) {
        btn.addEventListener('click', function () {
            picker.value = nowValue();
            picker.focus();
        });
    }
})();
</script>
<?php endif; ?>
