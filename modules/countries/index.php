<?php
/**
 * OpenFrame — Module: Countries (view)
 * CRUD for opf_countries (code, name_en, name_ar).
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
        $id      = (int) input('id');
        $code    = strtoupper(input('code'));
        $nameEn  = input('name_en');
        $nameAr  = input('name_ar');

        $errors = array();
        if ($code === '') {
            $errors[] = lang('countries.code_required', 'Country code is required.');
        } elseif (strlen($code) !== 2) {
            $errors[] = lang('countries.code_length', 'Country code must be 2 letters.');
        }
        if ($nameEn === '') {
            $errors[] = lang('countries.name_en_required', 'English name is required.');
        }
        if ($nameAr === '') {
            $errors[] = lang('countries.name_ar_required', 'Arabic name is required.');
        }

        if (empty($errors) && $code !== '') {
            $existing = db_row('SELECT id FROM ' . db_table('countries') . ' WHERE code = ? AND id <> ?', array($code, $id));
            if ($existing) {
                $errors[] = lang('countries.code_exists', 'Country code already exists.');
            }
        }

        if (empty($errors)) {
            $data = array(
                'code'       => $code,
                'name_en'    => $nameEn,
                'name_ar'    => $nameAr,
                'updated_at' => now(),
            );

            if ($id > 0 && db_find('countries', $id)) {
                db_update('countries', $data, 'id = ?', array($id));
                flash('success', lang('countries.updated_ok', 'Country updated.'));
            } else {
                $data['created_at'] = now();
                db_insert('countries', $data);
                flash('success', lang('countries.created_ok', 'Country created.'));
            }
            redirect(url('index.php?module=countries'));
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'danger';
        }
    }

    if ($action === 'delete') {
        $id = (int) input('id');
        $cityCount = db_count('cities', 'country_id = ?', array($id));

        if ($cityCount > 0) {
            flash('danger', lang('countries.cannot_delete_has_cities', 'Cannot delete: country has cities.'));
        } else {
            $deleted = db_delete('countries', 'id = ?', array($id));
            flash($deleted ? 'success' : 'warning', $deleted
                ? lang('countries.deleted_ok', 'Country deleted.')
                : lang('msg.not_found', 'Not found.'));
        }
        redirect(url('index.php?module=countries'));
    }
}

/* ---------------- read ---------------- */
$editId  = (int) input('edit');
$editing = $editId > 0 ? db_find('countries', $editId) : null;

$page      = db_paginate('SELECT * FROM ' . db_table('countries') . ' ORDER BY name_en', array(), (int) input('page', 1), 15);
$countries = $page['rows'];

$valCode   = input('code',   $editing ? $editing['code']   : '');
$valNameEn = input('name_en', $editing ? $editing['name_en'] : '');
$valNameAr = input('name_ar', $editing ? $editing['name_ar'] : '');
?>

<div class="row g-4">
    <!-- Form -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 card-title mb-3">
                    <i class="bi bi-<?php echo $editing ? 'pencil' : 'plus-circle'; ?> me-1"></i>
                    <?php echo e($editing ? lang('countries.edit', 'Edit country') : lang('countries.add', 'Add country')); ?>
                </h2>

                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?php echo e($messageType); ?> py-2"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo (int) ($editing ? $editing['id'] : 0); ?>">

                    <div class="mb-3">
                        <label class="form-label" for="code"><?php echo e(lang('countries.code', 'Code')); ?></label>
                        <input class="form-control text-uppercase" id="code" name="code" maxlength="2" required
                               value="<?php echo e($valCode); ?>">
                        <div class="form-text"><?php echo e(lang('countries.code_help', '2-letter ISO code (e.g. US, EG).')); ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="name_en"><?php echo e(lang('countries.name_en', 'Name (English)')); ?></label>
                        <input class="form-control" id="name_en" name="name_en" required
                               value="<?php echo e($valNameEn); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="name_ar"><?php echo e(lang('countries.name_ar', 'Name (Arabic)')); ?></label>
                        <input class="form-control" id="name_ar" name="name_ar" required dir="rtl"
                               value="<?php echo e($valNameAr); ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-save me-1"></i><?php echo e($editing ? lang('common.update', 'Update') : lang('common.create', 'Create')); ?>
                        </button>
                        <?php if ($editing): ?>
                            <a class="btn btn-outline-secondary" href="<?php echo e(url('index.php?module=countries')); ?>">
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
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-globe me-2"></i><?php echo e(lang('countries.title', 'Countries')); ?>
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width:60px">#</th>
                            <th style="width:80px"><?php echo e(lang('countries.code', 'Code')); ?></th>
                            <th><?php echo e(lang('countries.name_en', 'Name (English)')); ?></th>
                            <th><?php echo e(lang('countries.name_ar', 'Name (Arabic)')); ?></th>
                            <th class="text-end" style="width:110px"><?php echo e(lang('common.actions', 'Actions')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($countries)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4"><?php echo e(lang('countries.no_countries', 'No countries found.')); ?></td></tr>
                        <?php endif; ?>
                        <?php foreach ($countries as $country): ?>
                            <tr>
                                <td><?php echo (int) $country['id']; ?></td>
                                <td><span class="badge text-bg-secondary"><?php echo e($country['code']); ?></span></td>
                                <td><?php echo e($country['name_en']); ?></td>
                                <td dir="rtl"><?php echo e($country['name_ar']); ?></td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="<?php echo e(url('index.php?module=countries&edit=' . (int) $country['id'])); ?>"
                                       title="<?php echo e(lang('common.edit', 'Edit')); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('<?php echo e(lang('countries.confirm_delete', 'Delete this country?')); ?>');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $country['id']; ?>">
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
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <span class="small text-muted">
                        <?php echo e(lang('common.page', 'Page')); ?> <?php echo (int) $page['page']; ?> / <?php echo (int) $page['pages']; ?>
                        (<?php echo (int) $page['total']; ?> <?php echo e(lang('countries.title', 'Countries')); ?>)
                    </span>
                    <span>
                        <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
                            <a class="btn btn-sm <?php echo $i === $page['page'] ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                               href="<?php echo e(url('index.php?module=countries&page=' . $i)); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
