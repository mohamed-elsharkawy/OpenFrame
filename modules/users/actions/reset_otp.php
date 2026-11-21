<?php
/**
 * OpenFrame — Module: Users / Action: reset_otp
 * Reset OTP for a user. Superadmin can reset anyone; admin cannot reset superadmins.
 */

require_framework();
require_any_level(['superadmin', 'admin']);

csrf_check();

$actor   = current_user();
$resetId = (int) input('reset_id');

if (!$actor || !in_array($actor['level'], array('superadmin', 'admin'), true)) {
    flash('danger', lang('err.forbidden', 'You are not allowed to do that.'));
    redirect(url('index.php?module=users'));
    exit;
}

$target = $resetId > 0
    ? db_row('SELECT id, level FROM ' . db_table('users') . ' WHERE id = ?', array($resetId))
    : null;

if ($target) {
    $canReset = $actor['level'] === 'superadmin'
        || ($actor['level'] === 'admin' && $target['level'] !== 'superadmin');

    if (!$canReset) {
        flash('danger', lang('err.forbidden', 'You are not allowed to do that.'));
        redirect(url('index.php?module=users'));
        exit;
    }

    db_update('users', array(
        'otp_secret'  => null,
        'otp_enabled' => 0,
        'updated_at'  => now(),
    ), 'id = ?', array($resetId));
    flash('success', lang('otp.reset_ok', 'Authenticator reset. The user must scan a new QR code at next sign-in.'));
} else {
    flash('danger', lang('msg.error', 'User not found.'));
}

redirect(url('index.php?module=users'));
