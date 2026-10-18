<?php
/**
 * Module: Search Guest Information (view)
 */

$q     = input('q');
$rows  = array();

if ($q !== '') {
    $numeric = ctype_digit($q);
    $rows = db_rows(
        "SELECT gv.*, d.name AS department_name, u.full_name AS entered_by_name
           FROM guest_visits gv
           LEFT JOIN departments d ON d.id = gv.department_id
           LEFT JOIN users u ON u.id = gv.entered_by
          WHERE gv.full_name LIKE ? OR gv.id_number LIKE ? " . ($numeric ? "OR gv.id = ?" : "") . "
          ORDER BY gv.entry_time DESC
          LIMIT 100",
        $numeric
            ? array('%' . like_escape($q) . '%', '%' . like_escape($q) . '%', (int) $q)
            : array('%' . like_escape($q) . '%', '%' . like_escape($q) . '%')
    );
}
?>

<h1 class="h4 mb-3">
    <i class="bi bi-search me-1"></i><?php echo e(lang('guest_search.title')); ?>
</h1>

<div class="card shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?php echo e(url('index.php')); ?>" class="row g-2 align-items-end">
            <input type="hidden" name="module" value="guest_search">
            <div class="col-12 col-md-8">
                <label class="form-label" for="q"><?php echo e(lang('common.search')); ?></label>
                <input type="text" class="form-control" id="q" name="q" value="<?php echo e($q); ?>"
                       placeholder="<?php echo e(lang('guest_search.title')); ?>">
            </div>
            <div class="col-12 col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i><?php echo e(lang('common.search')); ?>
                </button>
            </div>
            <div class="col-12">
                <div class="form-text"><?php echo e(lang('guest_search.hint')); ?></div>
            </div>
        </form>
    </div>
</div>

<?php if ($q !== '' && !$rows): ?>
    <div class="alert alert-info"><?php echo e(lang('guest_search.no_records')); ?></div>
<?php endif; ?>

<?php if ($rows): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-body">
            <i class="bi bi-list-ul me-1"></i><?php echo e(lang('common.results')); ?>
            <span class="badge text-bg-secondary"><?php echo count($rows); ?> <?php echo e(lang('common.records_found')); ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo e(lang('grid.record')); ?></th>
                        <th><?php echo e(lang('grid.name')); ?></th>
                        <th><?php echo e(lang('grid.id_number')); ?></th>
                        <th><?php echo e(lang('grid.purpose')); ?></th>
                        <th><?php echo e(lang('grid.visiting_who')); ?></th>
                        <th><?php echo e(lang('grid.department')); ?></th>
                        <th><?php echo e(lang('grid.entry')); ?></th>
                        <th><?php echo e(lang('grid.leave')); ?></th>
                        <th><?php echo e(lang('grid.status')); ?></th>
                        <th><?php echo e(lang('grid.doc')); ?></th>
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
                            <td><?php echo e($row['purpose']); ?></td>
                            <td><?php echo e($row['visiting_who'] !== null && $row['visiting_who'] !== '' ? $row['visiting_who'] : '—'); ?></td>
                            <td><?php echo e($row['department_name'] !== null ? $row['department_name'] : '—'); ?></td>
                            <td><?php echo e(format_datetime($row['entry_time'])); ?></td>
                            <td><?php echo $inside ? '—' : e(format_datetime($row['leave_time'])); ?></td>
                            <td>
                                <span class="badge text-bg-<?php echo $inside ? 'success' : 'secondary'; ?>">
                                    <?php echo e($inside ? lang('common.inside') : lang('common.left')); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($row['id_doc_path'])): ?>
                                    <a href="<?php echo e(url($row['id_doc_path'])); ?>" target="_blank"
                                       class="btn btn-sm btn-outline-primary" title="<?php echo e(lang('guest.id_doc_link')); ?>">
                                        <i class="bi bi-image"></i>
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($row['entered_by_name'] !== null ? $row['entered_by_name'] : '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
