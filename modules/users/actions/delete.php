<?php
/**
 * OpenFrame — Module: Users / Action: delete
 * Delete a user (cannot delete yourself).
 */

require_framework();
require_any_level(['superadmin', 'admin']);

csrf_check();

$deleteId = (int) input('delete_id');

if ($deleteId > 0 && $deleteId !== (int) app_session_get('user_id', 0)) {
    db_delete('users', 'id = ?', [$deleteId]);
    flash('success', lang('msg.deleted', 'User deleted.'));
} else {
    flash('danger', lang('msg.error', 'Cannot delete this user.'));
}

redirect(url('index.php?module=users'));
