<?php
/**
 * Module: Notes demo — the exact CRUD pattern shown in the documentation.
 * Table: demo_notes (id, title, body, created_at)
 */

$id     = (int) input('id');
$editId = (int) input('edit');

/* ---------------- write actions ---------------- */
if (is_post()) {
    csrf_check();
    $action = input('action');

    if ($action === 'save') {
        $title = input('title');
        $body  = input('body');

        if ($title === '') {
            flash('danger', 'Title is required.');
        } else {
            if ($id > 0 && db_find('demo_notes', $id)) {
                db_update('demo_notes', array('title' => $title, 'body' => $body), 'id = ?', array($id));
                flash('success', 'Note updated.');
            } else {
                db_insert('demo_notes', array('title' => $title, 'body' => $body, 'created_at' => now()));
                flash('success', 'Note created.');
            }
        }
        redirect(url('index.php?module=notes_demo'));
    }

    if ($action === 'delete') {
        $deleted = db_delete('demo_notes', 'id = ?', array($id));
        flash($deleted ? 'success' : 'warning', $deleted ? 'Note deleted.' : 'Note not found.');
        redirect(url('index.php?module=notes_demo'));
    }
}

/* ---------------- read ---------------- */
$editing = $editId > 0 ? db_find('demo_notes', $editId) : null;
$page    = db_paginate('SELECT * FROM demo_notes ORDER BY id DESC', array(), (int) input('page', '1'), 10);
$notes   = $page['rows'];
?>

<h1 class="h4 mb-3"><i class="bi bi-journal-text me-1"></i>Notes demo</h1>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h6 card-title"><?php echo $editing ? 'Edit note' : 'New note'; ?></h2>
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo $editing ? (int) $editing['id'] : 0; ?>">
                    <div class="mb-3">
                        <label class="form-label" for="title">Title</label>
                        <input class="form-control" id="title" name="title" required
                               value="<?php echo e($editing ? $editing['title'] : ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="body">Body</label>
                        <textarea class="form-control" id="body" name="body" rows="4"><?php echo e($editing ? $editing['body'] : ''); ?></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-save me-1"></i><?php echo $editing ? 'Update' : 'Create'; ?>
                    </button>
                    <?php if ($editing): ?>
                        <a class="btn btn-outline-secondary" href="<?php echo e(url('index.php?module=notes_demo')); ?>">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr><th>#</th><th>Title</th><th>Body</th><th>Created</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$notes): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No notes yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($notes as $note): ?>
                            <tr>
                                <td><?php echo (int) $note['id']; ?></td>
                                <td><?php echo e($note['title']); ?></td>
                                <td class="small text-muted"><?php echo e(str_limit($note['body'], 60)); ?></td>
                                <td class="small"><?php echo e($note['created_at']); ?></td>
                                <td class="text-nowrap">
                                    <a class="btn btn-sm btn-outline-secondary"
                                       href="<?php echo e(url('index.php?module=notes_demo&edit=' . (int) $note['id'])); ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('Delete this note?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $note['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
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
                    <span class="small text-muted">Page <?php echo $page['page']; ?> of <?php echo $page['pages']; ?>
                        (<?php echo $page['total']; ?> notes)</span>
                    <span>
                        <?php for ($i = 1; $i <= $page['pages']; $i++): ?>
                            <a class="btn btn-sm <?php echo $i === $page['page'] ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                               href="<?php echo e(url('index.php?module=notes_demo&page=' . $i)); ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
