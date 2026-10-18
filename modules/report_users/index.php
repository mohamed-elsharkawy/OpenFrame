<?php
/**
 * Module: Guest entry report based on users (view) — admin only.
 * Add &export=csv to download the CSV.
 */

$from   = input('from', date('Y-m-d', strtotime('-30 days')));
$to     = input('to', date('Y-m-d'));
$ran    = isset($_GET['run']);
$export = isset($_GET['export']) && $_GET['export'] === 'csv';
$error  = '';
$rows   = array();
$totals = array('entries' => 0, 'inside' => 0);

$fromOk = DateTime::createFromFormat('Y-m-d', $from) && DateTime::createFromFormat('Y-m-d', $from)->format('Y-m-d') === $from;
$toOk   = DateTime::createFromFormat('Y-m-d', $to) && DateTime::createFromFormat('Y-m-d', $to)->format('Y-m-d') === $to;

if ($ran || $export) {
    if (!$fromOk || !$toOk || $from > $to) {
        $error = lang('report_period.invalid');
    } else {
        /* Every user is listed, even with zero entries in the period */
        $rows = db_rows(
            "SELECT u.id, u.full_name, u.username,
                    COUNT(gv.id) AS entries,
                    SUM(CASE WHEN gv.leave_time IS NULL THEN 1 ELSE 0 END) AS still_inside,
                    MIN(gv.entry_time) AS first_entry,
                    MAX(gv.entry_time) AS last_entry
               FROM users u
               LEFT JOIN guest_visits gv
                      ON gv.entered_by = u.id
                     AND gv.entry_time >= ? AND gv.entry_time <= ?
              WHERE u.active = 1
              GROUP BY u.id, u.full_name, u.username
              HAVING entries > 0 OR COUNT(gv.id) = 0
              ORDER BY entries DESC, u.full_name ASC",
            array($from . ' 00:00:00', $to . ' 23:59:59')
        );

        foreach ($rows as $row) {
            $totals['entries'] += (int) $row['entries'];
            $totals['inside']  += (int) $row['still_inside'];
        }
    }
}

/* ------------------------------------------------------------------
 * CSV export (bypasses the HTML layout)
 * ------------------------------------------------------------------ */
if ($export) {
    $fileName = 'entries_by_user_' . $from . '_' . $to . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, array(
        lang('report_users.user'), lang('login.username'), lang('report_users.entries'),
        lang('report_users.first'), lang('report_users.last'), lang('report_users.inside'),
    ));

    foreach ($rows as $row) {
        fputcsv($out, array(
            $row['full_name'], $row['username'], $row['entries'],
            $row['first_entry'], $row['last_entry'], $row['still_inside'],
        ));
    }

    fclose($out);
    exit;
}

$baseUrl = 'index.php?module=report_users&from=' . rawurlencode($from) . '&to=' . rawurlencode($to) . '&run=1';
?>

<h1 class="h4 mb-3">
    <i class="bi bi-people-fill me-1"></i><?php echo e(lang('report_users.title')); ?>
</h1>

<div class="card shadow-sm mb-4 of-no-print">
    <div class="card-header bg-body"><?php echo e(lang('report_period.filters')); ?></div>
    <div class="card-body">
        <form method="get" action="<?php echo e(url('index.php')); ?>" class="row g-3 align-items-end">
            <input type="hidden" name="module" value="report_users">
            <input type="hidden" name="run" value="1">
            <div class="col-12 col-md-4">
                <label class="form-label" for="from"><?php echo e(lang('report_period.from_date')); ?></label>
                <input type="date" class="form-control" id="from" name="from" value="<?php echo e($from); ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="to"><?php echo e(lang('report_period.to_date')); ?></label>
                <input type="date" class="form-control" id="to" name="to" value="<?php echo e($to); ?>" required>
            </div>
            <div class="col-12 col-md-4">
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

    <div class="d-flex gap-2 mb-3 of-no-print">
        <button type="button" class="btn btn-outline-secondary" onclick="window.print();">
            <i class="bi bi-printer me-1"></i><?php echo e(lang('common.print')); ?>
        </button>
        <a class="btn btn-outline-success" href="<?php echo e(url($baseUrl . '&export=csv')); ?>">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i><?php echo e(lang('common.export_csv')); ?>
        </a>
    </div>

    <?php if (!$rows): ?>
        <div class="alert alert-info"><?php echo e(lang('report_users.no_data')); ?></div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header bg-body">
                <?php echo e(format_date($from)); ?> — <?php echo e(format_date($to)); ?>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo e(lang('report_users.user')); ?></th>
                            <th><?php echo e(lang('report_users.entries')); ?></th>
                            <th><?php echo e(lang('report_users.first')); ?></th>
                            <th><?php echo e(lang('report_users.last')); ?></th>
                            <th><?php echo e(lang('report_users.inside')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($row['full_name']); ?></strong>
                                    <span class="text-muted small">(<?php echo e($row['username']); ?>)</span>
                                </td>
                                <td class="fw-semibold"><?php echo (int) $row['entries']; ?></td>
                                <td><?php echo $row['first_entry'] ? e(format_datetime($row['first_entry'])) : '—'; ?></td>
                                <td><?php echo $row['last_entry'] ? e(format_datetime($row['last_entry'])) : '—'; ?></td>
                                <td><?php echo (int) $row['still_inside']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td><?php echo e(lang('report_users.total')); ?></td>
                            <td><?php echo (int) $totals['entries']; ?></td>
                            <td colspan="2"></td>
                            <td><?php echo (int) $totals['inside']; ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
