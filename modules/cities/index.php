<?php
/**
 * OpenFrame — Module: Cities (view)
 * CRUD for opf_cities (country_id, name_en, name_ar).
 *
 * Requires: config.php, helpers.php, db.php (loaded by index.php).
 */

require_framework();
require_any_level(['superadmin', 'admin']);

$message = '';
$messageType = '';

/* ---------------- write actions ---------------- */
if (is_post()) {
    csrf_check();
    $action = input('action');

    if ($action === 'save') {
        $id         = (int) input('id');
        $countryId  = (int) input('country_id');
        $nameEn     = input('name_en');
        $nameAr     = input('name_ar');

        $errors = array();
        if ($countryId <= 0) {
            $errors[] = lang('cities.country_required', 'Country is required.');
        } elseif (!db_find('countries', $countryId)) {
            $errors[] = lang('cities.country_invalid', 'Selected country does not exist.');
        }
        if ($nameEn === '') {
            $errors[] = lang('cities.name_en_required', 'English name is required.');
        }
        if ($nameAr === '') {
            $errors[] = lang('cities.name_ar_required', 'Arabic name is required.');
        }

        if (empty($errors)) {
            $data = array(
                'country_id' => $countryId,
                'name_en'    => $nameEn,
                'name_ar'    => $nameAr,
                'updated_at' => now(),
            );

            if ($id > 0 && db_find('cities', $id)) {
                db_update('cities', $data, 'id = ?', array($id));
                flash('success', lang('cities.updated_ok', 'City updated.'));
            } else {
                $data['created_at'] = now();
                db_insert('cities', $data);
                flash('success', lang('cities.created_ok', 'City created.'));
            }
            redirect(url('index.php?module=cities'));
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'danger';
        }
    }

    if ($action === 'delete') {
        $id = (int) input('id');
        $deleted = db_delete('cities', 'id = ?', array($id));
        flash($deleted ? 'success' : 'warning', $deleted
            ? lang('cities.deleted_ok', 'City deleted.')
            : lang('msg.not_found', 'Not found.'));
        redirect(url('index.php?module=cities'));
    }
}

/* ---------------- read ---------------- */
$editId  = (int) input('edit');
$editing = $editId > 0 ? db_find('cities', $editId) : null;

$countryFilter = (int) input('country_id_filter');
$where  = '';
$params = array();
if ($countryFilter > 0) {
    $where  = 'WHERE c.country_id = ?';
    $params[] = $countryFilter;
}

/*
 * The cities table holds 200k+ rows, so paginate by id first: the inner query
 * is covered by idx_cities_country_name and only the 15 page rows are joined.
 */
$pageNum = max(1, (int) input('page', 1));
$perPage = 15;
$total   = (int) db_column('SELECT COUNT(*) FROM ' . db_table('cities') . ' c ' . $where, $params);
$cities  = db_rows(
    'SELECT c.*, co.name_en AS country_name_en, co.name_ar AS country_name_ar, co.code AS country_code
       FROM (SELECT id FROM ' . db_table('cities') . ' c ' . $where . '
              ORDER BY c.country_id, c.name_en
             LIMIT ' . $perPage . ' OFFSET ' . (($pageNum - 1) * $perPage) . ') t
       JOIN ' . db_table('cities') . ' c ON c.id = t.id
       LEFT JOIN ' . db_table('countries') . ' co ON co.id = c.country_id
      ORDER BY c.country_id, c.name_en',
    $params
);
$page = array(
    'rows'     => $cities,
    'total'    => $total,
    'page'     => $pageNum,
    'per_page' => $perPage,
    'pages'    => (int) ceil($total / $perPage),
);

$countries = db_rows('SELECT id, code, name_en, name_ar FROM ' . db_table('countries') . ' ORDER BY name_en');

$valCountryId = (int) input('country_id', $editing ? (int) $editing['country_id'] : 0);
$valNameEn    = input('name_en', $editing ? $editing['name_en'] : '');
$valNameAr    = input('name_ar', $editing ? $editing['name_ar'] : '');
?>

<div class="row g-4">
    <!-- Form -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 card-title mb-3">
                    <i class="bi bi-<?php echo $editing ? 'pencil' : 'plus-circle'; ?> me-1"></i>
                    <?php echo e($editing ? lang('cities.edit', 'Edit city') : lang('cities.add', 'Add city')); ?>
                </h2>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?php echo e($messageType); ?> py-2"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo (int) ($editing ? $editing['id'] : 0); ?>">

                    <div class="mb-3">
                        <label class="form-label" for="country_id"><?php echo e(lang('cities.country', 'Country')); ?></label>
                        <select class="form-select select2-single" id="country_id" name="country_id" required
                                data-placeholder="<?php echo e(lang('common.select_options', 'Select options')); ?>">
                            <option value=""></option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?php echo (int) $c['id']; ?>" <?php echo $valCountryId === (int) $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($c['code'] . ' - ' . $c['name_en']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="name_en"><?php echo e(lang('cities.name_en', 'Name (English)')); ?></label>
                        <input class="form-control" id="name_en" name="name_en" required
                               value="<?php echo e($valNameEn); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="name_ar"><?php echo e(lang('cities.name_ar', 'Name (Arabic)')); ?></label>
                        <input class="form-control" id="name_ar" name="name_ar" required dir="rtl"
                               value="<?php echo e($valNameAr); ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-save me-1"></i><?php echo e($editing ? lang('common.update', 'Update') : lang('common.create', 'Create')); ?>
                        </button>
                        <?php if ($editing): ?>
                            <a class="btn btn-outline-secondary" href="<?php echo e(url('index.php?module=cities')); ?>">
                                <?php echo e(lang('common.cancel', 'Cancel')); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- List -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-building me-2"></i><?php echo e(lang('cities.title', 'Cities')); ?>
                </h5>
                <form method="get" class="d-flex gap-2" style="min-width: 260px;">
                    <input type="hidden" name="module" value="cities">
                    <select class="form-select form-select-sm select2-single" name="country_id_filter" onchange="this.form.submit()"
                            data-placeholder="<?php echo e(lang('cities.all_countries', 'All countries')); ?>">
                        <option value="0"><?php echo e(lang('cities.all_countries', 'All countries')); ?></option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?php echo (int) $c['id']; ?>" <?php echo $countryFilter === (int) $c['id'] ? 'selected' : ''; ?>>
                                <?php echo e($c['code'] . ' - ' . $c['name_en']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px">#</th>
                            <th><?php echo e(lang('cities.country', 'Country')); ?></th>
                            <th><?php echo e(lang('cities.name_en', 'Name (English)')); ?></th>
                            <th><?php echo e(lang('cities.name_ar', 'Name (Arabic)')); ?></th>
                            <th class="text-end" style="width:110px"><?php echo e(lang('common.actions', 'Actions')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cities)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4"><?php echo e(lang('cities.no_cities', 'No cities found.')); ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($cities as $city): ?>
                            <tr>
                                <td><?php echo (int) $city['id']; ?></td>
                                <td>
                                    <span class="badge text-bg-secondary"><?php echo e($city['country_code'] ?? ''); ?></span>
                                    <?php echo e($city['country_name_en'] ?? ''); ?>
                                </td>
                                <td><?php echo e($city['name_en']); ?></td>
                                <td dir="rtl"><?php echo e($city['name_ar']); ?></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="<?php echo e(url('index.php?module=cities&edit=' . (int) $city['id'])); ?>"
                                       title="<?php echo e(lang('common.edit', 'Edit')); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('<?php echo e(lang('cities.confirm_delete', 'Delete this city?')); ?>');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $city['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit"
                                                title="<?php echo e(lang('common.delete', 'Delete')); ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($page['pages'] > 1): ?>
                <?php
                /* Large datasets produce thousands of pages, so render a compact window. */
                $totalPages  = (int) $page['pages'];
                $curPage     = (int) $page['page'];
                $windowStart = max(1, $curPage - 2);
                $windowEnd   = min($totalPages, $curPage + 2);
                $pageUrl     = function ($n) use ($countryFilter) {
                    return url('index.php?module=cities&page=' . $n . ($countryFilter > 0 ? '&country_id_filter=' . $countryFilter : ''));
                };
                ?>
                <div class="card-footer d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <span class="small text-muted">
                        <?php echo e(lang('common.page', 'Page')); ?> <?php echo $curPage; ?> / <?php echo $totalPages; ?>
                        (<?php echo (int) $page['total']; ?> <?php echo e(lang('cities.title', 'Cities')); ?>)
                    </span>
                    <span class="d-flex flex-wrap gap-1">
                        <?php if ($curPage > 1): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo e($pageUrl(1)); ?>">
                                <?php echo e(lang('common.first', 'First')); ?>
                            </a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo e($pageUrl($curPage - 1)); ?>">
                                &lsaquo; <?php echo e(lang('common.prev', 'Previous')); ?>
                            </a>
                        <?php endif; ?>
                        <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
                            <a class="btn btn-sm <?php echo $i === $curPage ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                               href="<?php echo e($pageUrl($i)); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($curPage < $totalPages): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo e($pageUrl($curPage + 1)); ?>">
                                <?php echo e(lang('common.next', 'Next')); ?> &rsaquo;
                            </a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo e($pageUrl($totalPages)); ?>">
                                <?php echo e(lang('common.last', 'Last')); ?>
                            </a>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
