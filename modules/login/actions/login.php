<?php
/**
 * OpenFrame — Module: Login / Action: login
 * Handle sign-in form submission.
 */

require_framework();

if (current_user()) {
    $landing = app_session_get('landing_page', 'login');
    if ($landing !== 'login') {
        redirect(url('index.php?module=' . $landing));
    }
    exit;
}

csrf_check();

$username = input('username');
$password = input('password');

if ($username === '' || $password === '') {
    flash('danger', lang('msg.wrong_credentials', 'Wrong username or password.'));
    redirect(url('index.php?module=login'));
}

$user = db_row('SELECT * FROM ' . db_table('users') . ' WHERE username = ?', [$username]);

if ($user && decrypt_password($user['password_hash']) === $password) {
    session_regenerate_id(true);

    if (otp_is_privileged($user)) {
        app_session_forget('user_id', 'user_level', 'user_name', 'otp_verified');
        app_session_set('otp_pending_user_id', (int) $user['id']);
        app_session_set('otp_attempts', 0);
        app_session_forget('otp_setup_secret');
        redirect(url('index.php?module=otp'));
    }

    otp_finish_login($user);
    flash('success', lang('msg.welcome_back', 'Welcome back.'));

    $landing = app_session_get('landing_page', 'login');
    if ($landing === 'login') {
        $landing = 'home';
    }

    redirect(url('index.php?module=' . $landing));
}

flash('danger', lang('msg.wrong_credentials', 'Wrong username or password.'));
redirect(url('index.php?module=login'));
