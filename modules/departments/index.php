<?php
/**
 * Module: Departments hierarchy (view) — admin only.
 */

if (is_post()) {
    csrf_check();
    $action = input('action');

    if ($action === 'save') {
        $id       = (int) input('id');
        $name     = input('name');
        $parentId = (int) input('parent_id');

        if ($name === '') {
            flash('danger', lang('departments.name') . ' — ' . lang('common.required'));
        } else {
            /* A department cannot be its own parent */
            if ($parentId === $id) {
                $parentId = 0;
            }
            $parentOk = $parentId === 0
                || db_row('SELECT id FROM departments WHERE id = ?', array($parentId)) !== null;
            if (!$parentOk) {
                $parentId = 0;
            }

            if ($id > 0) {
                db_exec(
                    'UPDATE departments SET name = ?, parent_id = ? WHERE id = ?',
                    array($name, $parentId > 0 ? $parentId : null, $id)
                );
            } else {
                db_exec(
                    'INSERT INTO departments (name, parent_id) VALUES (?, ?)',
                    array($name, $parentId > 0 ? $parentId : null)
                );
            }

            flash('success', lang('departments.saved_ok'));
            redirect(url('index.php?module=departments'));
        }
    } elseif ($action === 'delete') {
        $id = (int) input('id');

        $dept = db_row('SELECT * FROM departments WHERE id = ?', array($id));
        if ($dept !== null) {
            try {
                db()->beginTransaction();

                /* Children move up to the deleted node's parent */
                db_exec(
                    'UPDATE departments SET parent_id = ? WHERE parent_id = ?',
                    array($dept['parent_id'], $id)
                );
                db_exec('DELETE FROM departments WHERE id = ?', array($id));

                db()->commit();
                flash('success', lang('departments.deleted_ok'));
            } catch (PDOException $ex) {
                db()->rollBack();
                flash('danger', lang('departments.in_use'));
            }
            redirect(url('index.php?module=departments'));
        }
    }
}

/* ------------------------------------------------------------------
 * Data
 * ------------------------------------------------------------------ */
$departments = db_rows('SELECT id, name, parent_id FROM departments ORDER BY name ASC');

/* children[parent_id][] */
$children = array();
$byId = array();
foreach ($departments as $dept) {
    $byId[(int) $dept['id']] = $dept;
    $parent = $dept['parent_id'] === null ? 0 : (int) $dept['parent_id'];
    $children[$parent][] = $dept;
}
foreach ($children as &$list) {
    usort($list, function ($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });
}
unset($list);

/* Edit state */
$editId = (int) input('edit');
$edit = ($editId > 0 && isset($byId[$editId])) ? $byId[$editId] : null;

/**
 * Recursive tree renderer (Bootstrap list, RTL safe via app.css).
 */
function render_department_tree($parentId, $children, $depth = 0)
{
    if (empty($children[$parentId])) {
        return;
    }
    echo '<ul>';
    foreach ($children[$parentId] as $node) {
        echo '<li class="py-1">';
        echo '<span class="badge text-bg-light border me-1">' . (int) $node['id'] . '</span>';
        echo '<strong>' . e($node['name']) . '</strong> ';
        echo '<a class="btn btn-sm btn-outline-secondary ms-2" href="'
            . e(url('index.php?module=departments&edit=' . (int) $node['id'])) . '">'
            . '<i class="bi bi-pencil me-1"></i>' . e(lang('common.edit')) . '</a> ';
        echo '<form method="post" class="d-inline" onsubmit="return confirm(\''
            . e(lang('departments.delete_confirm')) . '\');">'
            . csrf_field()
            . '<input type="hidden" name="action" value="delete">'
            . '<input type="hidden" name="id" value="' . (int) $node['id'] . '">'
            . '<button type="submit" class="btn btn-sm btn-outline-danger">'
            . '<i class="bi bi-trash me-1"></i>' . e(lang('common.delete')) . '</button>'
            . '</form>';
        echo '</li>';
        render_department_tree((int) $node['id'], $children, $depth + 1);
    }
    echo '</ul>';
}
?>

<h1 class="h4 mb-3">
    <i class="bi bi-diagram-3-fill me-1"></i><?php echo e(lang('departments.title')); ?>
</h1>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-body">
                <i class="bi bi-list-tree me-1"></i><?php echo e(lang('departments.current')); ?>
                <span class="badge text-bg-secondary"><?php echo count($departments); ?></span>
            </div>
            <div class="card-body of-tree">
                <?php if (!$departments): ?>
                    <p class="text-muted mb-0"><?php echo e(lang('departments.empty')); ?></p>
                <?php else: ?>
                    <?php render_department_tree(0, $children); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-body">
                <i class="bi <?php echo $edit ? 'bi-pencil-square' : 'bi-plus-square'; ?> me-1"></i>
                <?php echo e($edit ? lang('departments.edit_title') : lang('departments.add_title')); ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo e(url('index.php?module=departments')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo $edit ? (int) $edit['id'] : 0; ?>">

                    <div class="mb-3">
                        <label class="form-label" for="name">
                            <?php echo e(lang('departments.name')); ?> <span class="of-required">*</span>
                        </label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo e($edit ? $edit['name'] : input('name')); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="parent_id"><?php echo e(lang('departments.parent')); ?></label>
                        <select class="form-select" id="parent_id" name="parent_id">
                            <option value="0"><?php echo e(lang('departments.root_option')); ?></option>
                            <?php foreach ($departments as $dept): ?>
                                <?php if ($edit && (int) $dept['id'] === (int) $edit['id']) continue; ?>
                                <option value="<?php echo (int) $dept['id']; ?>"
                                    <?php echo $edit && (int) $edit['parent_id'] === (int) $dept['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($dept['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i><?php echo e(lang('common.save')); ?>
                        </button>
                        <?php if ($edit): ?>
                            <a href="<?php echo e(url('index.php?module=departments')); ?>" class="btn btn-outline-secondary">
                                <?php echo e(lang('common.cancel')); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
