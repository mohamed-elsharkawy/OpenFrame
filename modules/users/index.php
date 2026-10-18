<?php
/**
 * Module: Users management (view) — admin only.
 */

$levels = db_rows('SELECT id, code, name_ar, name_en FROM user_levels ORDER BY id ASC');

/* Handle actions */
if (is_post()) {
    csrf_check();
    $action = input('action');

    if ($action === 'save') {
        $id       = (int) input('id');
        $fullName = input('full_name');
        $username = input('username');
        $levelId  = (int) input('level_id');
        $password = isset($_POST['password']) ? (string) trim($_POST['password']) : '';
        $errors   = array();

        if ($fullName === '') {
            $errors[] = lang('profile.full_name');
        }
        if ($username === '') {
            $errors[] = lang('profile.username');
        }
        if ($levelId <= 0 || db_row('SELECT id FROM user_levels WHERE id = ?', array($levelId)) === null) {
            $errors[] = lang('users.level');
        }

        if ($id <= 0 && $password === '') {
            $errors[] = lang('login.password');
        } elseif ($password !== '' && mb_strlen($password) < 4) {
            $errors[] = lang('profile.password_short');
        }

        if (!$errors) {
            $dup = db_row(
                'SELECT id FROM users WHERE username = ?' . ($id > 0 ? ' AND id <> ?' : ''),
                $id > 0 ? array($username, $id) : array($username)
            );
            if ($dup !== null) {
                $errors[] = lang('profile.username_taken');
            }
        }

        if (!$errors) {
            if ($id > 0) {
                $params = array($fullName, $username, $levelId, $id);
                $sql = 'UPDATE users SET full_name = ?, username = ?, level_id = ?';
                if ($password !== '') {
                    $sql .= ', password_hash = ?';
                    $params = array($fullName, $username, $levelId, password_hash($password, PASSWORD_DEFAULT), $id);
                }
                db_exec($sql . ' WHERE id = ?', $params);
            } else {
                db_exec(
                    'INSERT INTO users (username, full_name, password_hash, level_id, active) VALUES (?, ?, ?, ?, 1)',
                    array($username, $fullName, password_hash($password, PASSWORD_DEFAULT), $levelId)
                );
            }

            flash('success', lang('users.saved_ok'));
            redirect(url('index.php?module=users'));
        } else {
            flash('danger', implode(' — ', $errors));
        }
    } elseif ($action === 'delete') {
        $id = (int) input('id');
        $user = db_row('SELECT id, username FROM users WHERE id = ?', array($id));
        if ($user !== null) {
            /* Protect the last admin or current user from accidental deletion? */
            if ($user['id'] === (int) current_user()['id']) {
                flash('warning', lang('users.cannot_delete_self'));
            } else {
                $countAdmins = (int) db_row('SELECT COUNT(*) AS c FROM users WHERE level_id = 2 AND active = 1')['c'];
                $userLevel = db_row('SELECT level_id FROM users WHERE id = ?', array($id))['level_id'];
                if ($userLevel == 2 && $countAdmins <= 1) {
                    flash('warning', lang('users.last_admin'));
                } else {
                    try {
                        /* Prevent FK issues by setting entered_by to NULL where used (already SET NULL) */
                        db_exec('DELETE FROM users WHERE id = ?', array($id));
                        flash('success', lang('users.deleted_ok'));
                    } catch (PDOException $ex) {
                        flash('danger', lang('users.in_use'));
                    }
                }
            }
            redirect(url('index.php?module=users'));
        }
    }
}

$users  = db_rows(
    'SELECT u.id, u.username, u.full_name, u.level_id, u.active,
            l.code AS level_code, l.name_ar AS level_name_ar, l.name_en AS level_name_en
       FROM users u
       JOIN user_levels l ON l.id = u.level_id
      ORDER BY u.id ASC'
);
$editId = (int) input('edit');
$edit   = null;
if ($editId > 0) {
    $edit = db_row('SELECT id, username, full_name, level_id FROM users WHERE id = ?', array($editId));
}

$levelsAssoc = array();
foreach ($levels as $l) {
    $levelsAssoc[(int) $l['id']] = $l;
}
?>

<h1 class="h4 mb-3">
    <i class="bi bi-people me-1"></i><?php echo e(lang('users.title')); ?>
</h1>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-body d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul me-1"></i><?php echo e(lang('users.list')); ?></span>
                <a class="btn btn-sm btn-primary" href="<?php echo e(url('index.php?module=users')); ?>">
                    <i class="bi bi-plus-circle me-1"></i><?php echo e(lang('users.add')); ?>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th><?php echo e(lang('profile.full_name')); ?></th>
                            <th><?php echo e(lang('login.username')); ?></th>
                            <th><?php echo e(lang('users.level')); ?></th>
                            <th><?php echo e(lang('common.actions')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr class="<?php echo $editId === (int) $u['id'] ? 'table-primary' : ''; ?>">
                                <td><?php echo (int) $u['id']; ?></td>
                                <td><?php echo e($u['full_name']); ?></td>
                                <td><?php echo e($u['username']); ?></td>
                                <td>
                                    <span class="badge text-bg-<?php echo $u['level_code'] === 'admin' ? 'danger' : 'secondary'; ?>">
                                        <?php echo e(is_rtl() ? $u['level_name_ar'] : $u['level_name_en']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-outline-secondary"
                                       href="<?php echo e(url('index.php?module=users&edit=' . (int) $u['id'])); ?>">
                                        <i class="bi bi-pencil me-1"></i><?php echo e(lang('common.edit')); ?>
                                    </a>
                                    <?php if ((int) $u['id'] !== (int) current_user()['id']): ?>
                                        <form method="post" class="d-inline"
                                              onsubmit="return confirm('<?php echo e(lang('users.delete_confirm')); ?>');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash me-1"></i><?php echo e(lang('common.delete')); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-body">
                <i class="bi bi-person-plus me-1"></i>
                <?php echo e($edit ? lang('users.edit') : lang('users.add')); ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo e(url('index.php?module=users')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo $edit ? (int) $edit['id'] : 0; ?>">

                    <div class="mb-3">
                        <label class="form-label" for="full_name">
                            <?php echo e(lang('profile.full_name')); ?> <span class="of-required">*</span>
                        </label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required
                               value="<?php echo e($edit ? $edit['full_name'] : input('full_name')); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="username">
                            <?php echo e(lang('login.username')); ?> <span class="of-required">*</span>
                        </label>
                        <input type="text" class="form-control" id="username" name="username" required
                               value="<?php echo e($edit ? $edit['username'] : input('username')); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="level_id">
                            <?php echo e(lang('users.level')); ?> <span class="of-required">*</span>
                        </label>
                        <select class="form-select" id="level_id" name="level_id" required>
                            <option value=""><?php echo e(lang('common.select')); ?></option>
                            <?php foreach ($levels as $l): ?>
                                <option value="<?php echo (int) $l['id']; ?>"
                                    <?php echo ($edit && (int) $edit['level_id'] === (int) $l['id']) ? 'selected' : ''; ?>>
                                    <?php echo e(is_rtl() ? $l['name_ar'] : $l['name_en']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">
                            <?php echo e(lang('login.password')); ?>
                            <?php if (!$edit): ?><span class="of-required">*</span><?php endif; ?>
                        </label>
                        <input type="password" class="form-control" id="password" name="password"
                               <?php if (!$edit): ?>required<?php endif; ?> autocomplete="new-password">
                        <div class="form-text"><?php echo e($edit ? lang('users.password_edit_hint') : lang('users.password_add_hint')); ?></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i><?php echo e(lang('common.save')); ?>
                        </button>
                        <?php if ($edit): ?>
                            <a href="<?php echo e(url('index.php?module=users')); ?>" class="btn btn-outline-secondary">
                                <?php echo e(lang('common.cancel')); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
