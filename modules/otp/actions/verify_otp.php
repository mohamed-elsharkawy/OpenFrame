<?php
/**
 * OpenFrame — Module: OTP / Action: verify_otp
 * Verify a TOTP code and complete login.
 */

require_framework();

$pendingId = (int) app_session_get('otp_pending_user_id', 0);
if ($pendingId <= 0) {
    redirect(url('index.php?module=login'));
    exit;
}

$user = db_find('users', $pendingId);
if (!$user) {
    app_session_forget('otp_pending_user_id', 'otp_setup_secret', 'otp_attempts');
    redirect(url('index.php?module=login'));
    exit;
}

if (!otp_is_privileged($user)) {
    otp_finish_login($user);
    flash('success', lang('msg.welcome_back', 'Welcome back.'));
    redirect(url('index.php?module=home'));
    exit;
}

csrf_check();

$attempts = (int) app_session_get('otp_attempts', 0);
if ($attempts >= 8) {
    app_session_forget('otp_pending_user_id', 'otp_setup_secret', 'otp_attempts');
    flash('danger', lang('otp.locked', 'Too many attempts. Sign in again.'));
    redirect(url('index.php?module=login'));
    exit;
}

$code = preg_replace('/\D+/', '', input('otp_code'));
$secret = empty(app_session_get('otp_setup_secret')) ? $user['otp_secret'] : app_session_get('otp_setup_secret');
$needsSetup = empty($user['otp_enabled']) || empty($user['otp_secret']);

if (!otp_verify($secret, $code)) {
    app_session_set('otp_attempts', $attempts + 1);
    flash('danger', lang('otp.invalid', 'Invalid or expired code. Try again.'));
    redirect(url('index.php?module=otp'));
    exit;
}

if ($needsSetup) {
    db_update('users', array(
        'otp_secret'  => $secret,
        'otp_enabled' => 1,
        'updated_at'  => now(),
    ), 'id = ?', array($pendingId));
}

otp_finish_login($user);
flash('success', lang('msg.welcome_back', 'Welcome back.'));
redirect(url('index.php?module=home'));
