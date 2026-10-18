<?php
/**
 * Module: New Guest (view)
 */

$departments = db_rows('SELECT id, name FROM departments ORDER BY name ASC');
$nextId      = db_next_id('guest_visits');

/* Old values (kept when validation fails) */
$values = array(
    'full_name'    => input('full_name'),
    'id_number'    => input('id_number'),
    'purpose'      => input('purpose'),
    'visiting_who' => input('visiting_who'),
    'department_id'=> (int) input('department_id'),
);
$errors = array();

if (is_post()) {
    csrf_check();

    if ($values['full_name'] === '') {
        $errors[] = lang('guest.full_name');
    }
    if ($values['id_number'] === '') {
        $errors[] = lang('guest.id_number');
    }
    if ($values['purpose'] === '') {
        $errors[] = lang('guest.purpose');
    }
    if ($values['department_id'] <= 0 || db_row('SELECT id FROM departments WHERE id = ?', array($values['department_id'])) === null) {
        $errors[] = lang('guest.department');
    }

    if (!$errors) {
        /* Optional ID/passport upload — validated before touching the disk */
        $file = isset($_FILES['id_doc']) ? $_FILES['id_doc'] : null;
        $tmpPath = null;
        $extension = null;

        if ($file !== null && (int) $file['error'] !== UPLOAD_ERR_NO_FILE) {
            $allowed = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');

            if ((int) $file['error'] !== UPLOAD_ERR_OK
                || !is_uploaded_file($file['tmp_name'])
                || (int) $file['size'] > 5 * 1024 * 1024
            ) {
                $errors[] = lang('guest_new.upload_error');
            } else {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                if (!isset($allowed[$mime])) {
                    $errors[] = lang('guest_new.upload_error');
                } else {
                    $tmpPath   = $file['tmp_name'];
                    $extension = $allowed[$mime];
                }
            }
        }

        if (!$errors) {
            db_exec(
                'INSERT INTO guest_visits (full_name, id_number, purpose, visiting_who, department_id,
                                           entry_time, entered_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                array(
                    $values['full_name'],
                    $values['id_number'],
                    $values['purpose'],
                    $values['visiting_who'] !== '' ? $values['visiting_who'] : null,
                    $values['department_id'],
                    date('Y-m-d H:i:s'),
                    (int) current_user()['id'],
                )
            );
            $visitId = (int) db()->lastInsertId();

            /* Move the uploaded file to uploads/YYYY/MM/DD/ (created on demand) */
            if ($tmpPath !== null) {
                $relative = date('Y') . '/' . date('m') . '/' . date('d');
                $targetDir = UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

                if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
                    flash('warning', lang('guest_new.upload_error'));
                } else {
                    $targetFile = $targetDir . DIRECTORY_SEPARATOR . 'visit-' . $visitId . '.' . $extension;
                    if (move_uploaded_file($tmpPath, $targetFile)) {
                        db_exec(
                            'UPDATE guest_visits SET id_doc_path = ? WHERE id = ?',
                            array('uploads/' . $relative . '/visit-' . $visitId . '.' . $extension, $visitId)
                        );
                    } else {
                        flash('warning', lang('guest_new.upload_error'));
                    }
                }
            }

            flash('success', str_replace(':id', (string) $visitId, lang('guest_new.saved_ok')));
            redirect(url('index.php?module=guest_new'));
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-9 col-xl-8">

        <h1 class="h4 mb-3">
            <i class="bi bi-person-plus-fill me-1"></i><?php echo e(lang('guest_new.title')); ?>
        </h1>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <strong><?php echo e(lang('guest_new.fill_required')); ?>:</strong>
                <?php echo e(implode('، ', $errors)); ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form method="post" action="<?php echo e(url('index.php?module=guest_new')); ?>" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label"><?php echo e(lang('guest.record_id')); ?></label>
                        <input type="text" class="form-control of-readonly form-control-plaintext fw-bold"
                               value="<?php echo e($nextId); ?>" readonly disabled id="recordId">
                        <div class="form-text"><?php echo e(lang('common.record')); ?> — <?php echo e(lang('common.none')); ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="full_name">
                            <?php echo e(lang('guest.full_name')); ?> <span class="of-required">*</span>
                        </label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required
                               value="<?php echo e($values['full_name']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="id_number">
                            <?php echo e(lang('guest.id_number')); ?> <span class="of-required">*</span>
                        </label>
                        <input type="text" class="form-control" id="id_number" name="id_number" required
                               value="<?php echo e($values['id_number']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="purpose">
                            <?php echo e(lang('guest.purpose')); ?> <span class="of-required">*</span>
                        </label>
                        <input type="text" class="form-control" id="purpose" name="purpose" required
                               value="<?php echo e($values['purpose']); ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="visiting_who"><?php echo e(lang('guest.visiting_who')); ?></label>
                            <input type="text" class="form-control" id="visiting_who" name="visiting_who"
                                   value="<?php echo e($values['visiting_who']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="department_id">
                                <?php echo e(lang('guest.department')); ?> <span class="of-required">*</span>
                            </label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value=""><?php echo e(lang('guest.choose_department')); ?></option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo (int) $dept['id']; ?>"
                                        <?php echo $values['department_id'] === (int) $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="id_doc"><?php echo e(lang('guest.id_doc')); ?></label>
                        <input type="file" class="form-control" id="id_doc" name="id_doc" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text"><?php echo e(lang('guest.id_doc_hint')); ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo e(lang('guest.entry_time')); ?></label>
                        <input type="text" class="form-control form-control-plaintext of-readonly"
                               value="<?php echo e(format_datetime(date('Y-m-d H:i:s'))); ?>" readonly disabled>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i><?php echo e(lang('common.save')); ?>
                        </button>
                        <a href="<?php echo e(url('index.php?module=dashboard')); ?>" class="btn btn-outline-secondary">
                            <?php echo e(lang('common.cancel')); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
