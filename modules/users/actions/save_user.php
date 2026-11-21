<?php
/**
 * OpenFrame — Module: Users / Action: save_user
 * Create or update a user.
 */

require_framework();
require_any_level(['superadmin', 'admin']);

csrf_check();

$username = input('username');
$fullName = input('full_name');
$password = input('password');
$level = input('level');
$permList = array();
if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
    foreach ($_POST['permissions'] as $perm) {
        if (is_string($perm) && $perm !== '') {
            $permList[] = $perm;
        }
    }
}
$permissions = implode(',', $permList);

$errors = [];

if ($username === '') {
    $errors[] = lang('common.name', 'Username') . ' ' . lang('common.required', 'is required.');
}
if ($fullName === '') {
    $errors[] = lang('common.full_name', 'Full Name') . ' ' . lang('common.required', 'is required.');
}
if ($level === '') {
    $errors[] = lang('common.level', 'Level') . ' ' . lang('common.required', 'is required.');
}

$userId = (int) input('user_id');
$isEdit = $userId > 0;

// Check username uniqueness
if ($username !== '') {
    $existing = db_row('SELECT id FROM ' . db_table('users') . ' WHERE username = ? AND id <> ?', [$username, $userId]);
    if ($existing) {
        $errors[] = lang('common.name', 'Username') . ' ' . lang('msg.error', 'already exists.');
    }
}

if (empty($errors)) {
    $data = [
        'username'    => $username,
        'full_name'   => $fullName,
        'level'       => $level,
        'permissions' => $permissions !== '' ? $permissions : null,
        'updated_at'  => now(),
    ];

    if ($password !== '') {
        $data['password_hash'] = encrypt_password($password);
    }

    if ($isEdit) {
        db_update('users', $data, 'id = ?', [$userId]);
        flash('success', lang('msg.updated', 'User updated.'));
    } else {
        $data['created_at'] = now();
        db_insert('users', $data);
        flash('success', lang('msg.created', 'User created.'));
    }
    redirect(url('index.php?module=users'));
}

flash('danger', implode('<br>', $errors));
redirect(url('index.php?module=users' . ($userId > 0 ? '&edit=' . $userId : '')));
