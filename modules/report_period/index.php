<?php
/**
 * Module: Guests report for a period (view) — admin only.
 * Add &export=csv to any of the URLs below to download the CSV.
 */

$departments = db_rows('SELECT id, name FROM departments ORDER BY name ASC');

$from     = input('from', date('Y-m-d', strtotime('-7 days')));
$to       = input('to', date('Y-m-d'));
$deptId   = (int) input('dept');
$ran      = isset($_GET['run']);
$export   = isset($_GET['export']) && $_GET['export'] === 'csv';
$error    = '';
$rows     = array();
$summary  = array('total' => 0, 'inside' => 0, 'left' => 0);

$fromOk = DateTime::createFromFormat('Y-m-d', $from) && DateTime::createFromFormat('Y-m-d', $from)->format('Y-m-d') === $from;
$toOk   = DateTime::createFromFormat('Y-m-d', $to) && DateTime::createFromFormat('Y-m-d', $to)->format('Y-m-d') === $to;

if ($ran || $export) {
    if (!$fromOk || !$toOk || $from > $to) {
        $error = lang('report_period.invalid');
    } else {
        $sql = "SELECT gv.*, d.name AS department_name, u.full_name AS entered_by_name
                  FROM guest_visits gv
                  LEFT JOIN departments d ON d.id = gv.department_id
                  LEFT JOIN users u ON u.id = gv.entered_by
                 WHERE gv.entry_time >= ? AND gv.entry_time <= ?";
        $params = array($from . ' 00:00:00', $to . ' 23:59:59');

        if ($deptId > 0) {
            $sql .= ' AND gv.department_id = ?';
            $params[] = $deptId;
        }
        $sql .= ' ORDER BY gv.entry_time DESC';

        $rows = db_rows($sql, $params);

        foreach ($rows as $row) {
            $summary['total']++;
            if ($row['leave_time'] === null) {
                $summary['inside']++;
            } else {
                $summary['left']++;
            }
        }
    }
}

/* ------------------------------------------------------------------
 * CSV export (bypasses the HTML layout, index.php knows this)
 * ------------------------------------------------------------------ */
if ($export) {
    $fileName = 'guests_report_' . $from . '_' . $to . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Arabic correctly

    fputcsv($out, array(
        lang('grid.record'), lang('grid.name'), lang('grid.id_number'), lang('grid.purpose'),
        lang('grid.visiting_who'), lang('grid.department'), lang('grid.entry'), lang('grid.leave'),
        lang('grid.duration'), lang('grid.status'), lang('grid.entered_by'),
    ));

    foreach ($rows as $row) {
        fputcsv($out, array(
            $row['id'], $row['full_name'], $row['id_number'], $row['purpose'],
            $row['visiting_who'], $row['department_name'], $row['entry_time'],
            $row['leave_time'] !== null ? $row['leave_time'] : '',
            $row['leave_time'] !== null ? duration_between($row['entry_time'], $row['leave_time']) : '',
            $row['leave_time'] === null ? lang('common.inside') : lang('common.left'),
            $row['entered_by_name'],
        ));
    }

    fclose($out);
    exit;
}

$baseUrl = 'index.php?module=report_period&from=' . rawurlencode($from)
    . '&to=' . rawurlencode($to) . '&dept=' . $deptId . '&run=1';
?>

<h1 class="h4 mb-3">
    <i class="bi bi-calendar3-range me-1"></i><?php echo e(lang('report_period.title')); ?>
</h1>

<div class="card shadow-sm mb-4 of-no-print">
    <div class="card-header bg-body"><?php echo e(lang('report_period.filters')); ?></div>
    <div class="card-body">
        <form method="get" action="<?php echo e(url('index.php')); ?>" class="row g-3 align-items-end">
            <input type="hidden" name="module" value="report_period">
            <input type="hidden" name="run" value="1">
            <div class="col-12 col-md-3">
                <label class="form-label" for="from"><?php echo e(lang('report_period.from_date')); ?></label>
                <input type="date" class="form-control" id="from" name="from" value="<?php echo e($from); ?>" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="to"><?php echo e(lang('report_period.to_date')); ?></label>
                <input type="date" class="form-control" id="to" name="to" value="<?php echo e($to); ?>" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="dept"><?php echo e(lang('report_period.department')); ?></label>
                <select class="form-select" id="dept" name="dept">
                    <option value="0"><?php echo e(lang('report_period.all_depts')); ?></option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo (int) $dept['id']; ?>"
                            <?php echo $deptId === (int) $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo e($dept['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i><?php echo e(lang('report_period.run')); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>

<?php if ($ran && $error === ''): ?>

    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <div class="fs-3 fw-bold text-primary"><?php echo (int) $summary['total']; ?></div>
                    <div class="small text-muted"><?php echo e(lang('report_period.total')); ?></div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <div class="fs-3 fw-bold text-success"><?php echo (int) $summary['inside']; ?></div>
                    <div class="small text-muted"><?php echo e(lang('report_period.inside')); ?></div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <div class="fs-3 fw-bold text-secondary"><?php echo (int) $summary['left']; ?></div>
                    <div class="small text-muted"><?php echo e(lang('report_period.left')); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3 of-no-print">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print();">
            <i class="bi bi-printer me-1"></i><?php echo e(lang('common.print')); ?>
        </button>
        <a class="btn btn-outline-success" href="<?php echo e(url($baseUrl . '&export=csv')); ?>">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i><?php echo e(lang('common.export_csv')); ?>
        </a>
    </div>

    <?php if (!$rows): ?>
        <div class="alert alert-info"><?php echo e(lang('report_period.no_data')); ?></div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header bg-body">
                <?php echo e(format_date($from)); ?> — <?php echo e(format_date($to)); ?>
                <span class="badge text-bg-secondary"><?php echo count($rows); ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo e(lang('grid.record')); ?></th>
                            <th><?php echo e(lang('grid.name')); ?></th>
                            <th><?php echo e(lang('grid.id_number')); ?></th>
                            <th><?php echo e(lang('grid.department')); ?></th>
                            <th><?php echo e(lang('grid.purpose')); ?></th>
                            <th><?php echo e(lang('grid.entry')); ?></th>
                            <th><?php echo e(lang('grid.leave')); ?></th>
                            <th><?php echo e(lang('grid.duration')); ?></th>
                            <th><?php echo e(lang('grid.status')); ?></th>
                            <th><?php echo e(lang('grid.entered_by')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <?php $inside = ($row['leave_time'] === null); ?>
                            <tr>
                                <td class="fw-semibold">#<?php echo (int) $row['id']; ?></td>
                                <td><?php echo e($row['full_name']); ?></td>
                                <td><?php echo e($row['id_number']); ?></td>
                                <td><?php echo e($row['department_name'] !== null ? $row['department_name'] : '—'); ?></td>
                                <td><?php echo e($row['purpose']); ?></td>
                                <td><?php echo e(format_datetime($row['entry_time'])); ?></td>
                                <td><?php echo $inside ? '—' : e(format_datetime($row['leave_time'])); ?></td>
                                <td><?php echo $inside ? '—' : e(duration_between($row['entry_time'], $row['leave_time'])); ?></td>
                                <td>
                                    <span class="badge text-bg-<?php echo $inside ? 'success' : 'secondary'; ?>">
                                        <?php echo e($inside ? lang('common.inside') : lang('common.left')); ?>
                                    </span>
                                </td>
                                <td><?php echo e($row['entered_by_name'] !== null ? $row['entered_by_name'] : '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
