<?php
/**
 * OpenFrame — Module: OTP / Action: cancel
 * Cancel OTP setup and return to login.
 */

require_framework();

app_session_forget(
    'otp_pending_user_id',
    'otp_setup_secret',
    'otp_attempts'
);
redirect(url('index.php?module=login'));
