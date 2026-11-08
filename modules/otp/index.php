<?php
/**
 * OpenFrame — Module: OTP (view)
 * TOTP setup (QR) and verification for superadmin / admin only.
 */

require_framework();

if (current_user() && !empty($_SESSION['otp_verified']) && empty($_SESSION['otp_pending_user_id'])) {
    redirect(url('index.php?module=home'));
}

$pendingId = (int) ($_SESSION['otp_pending_user_id'] ?? 0);
if ($pendingId <= 0) {
    redirect(url('index.php?module=login'));
}

$user = db_find('users', $pendingId);
if (!$user) {
    unset($_SESSION['otp_pending_user_id'], $_SESSION['otp_setup_secret'], $_SESSION['otp_attempts']);
    redirect(url('index.php?module=login'));
}

if (!otp_is_privileged($user)) {
    otp_finish_login($user);
    flash('success', lang('msg.welcome_back', 'Welcome back.'));
    redirect(url('index.php?module=home'));
}

if (input('action') === 'cancel') {
    unset(
        $_SESSION['otp_pending_user_id'],
        $_SESSION['otp_setup_secret'],
        $_SESSION['otp_attempts']
    );
    redirect(url('index.php?module=login'));
}

$needsSetup = empty($user['otp_enabled']) || empty($user['otp_secret']);
$error = '';

if ($needsSetup) {
    if (empty($_SESSION['otp_setup_secret'])) {
        $_SESSION['otp_setup_secret'] = otp_generate_secret(16);
    }
    $secret = $_SESSION['otp_setup_secret'];
} else {
    $secret = $user['otp_secret'];
}

if (is_post() && input('action') === 'verify_otp') {
    csrf_check();

    $attempts = (int) ($_SESSION['otp_attempts'] ?? 0);
    if ($attempts >= 8) {
        unset(
            $_SESSION['otp_pending_user_id'],
            $_SESSION['otp_setup_secret'],
            $_SESSION['otp_attempts']
        );
        flash('danger', lang('otp.locked', 'Too many attempts. Sign in again.'));
        redirect(url('index.php?module=login'));
    }

    $code = preg_replace('/\D+/', '', input('otp_code'));

    if (!otp_verify($secret, $code)) {
        $_SESSION['otp_attempts'] = $attempts + 1;
        $error = lang('otp.invalid', 'Invalid or expired code. Try again.');
    } else {
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
    }
}

$issuer = config('appname', 'OpenFrame');
$otpUri = otp_provisioning_uri($secret, $user['username'], $issuer);
$qrHtml = $needsSetup ? otp_qr_html($otpUri, 6) : '';
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm mt-4">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="<?php echo e(config('applogo')); ?>" alt="<?php echo e(config('appname')); ?> logo" height="48" class="mb-2">
                    <h2 class="h4 card-title mb-1">
                        <?php echo e($needsSetup
                            ? lang('otp.setup_title', 'Set up authenticator')
                            : lang('otp.title', 'Two-factor authentication')); ?>
                    </h2>
                    <p class="small text-muted mb-0">
                        <?php echo e($user['full_name']); ?>
                        <span class="badge text-bg-<?php echo $user['level'] === 'superadmin' ? 'danger' : 'primary'; ?>">
                            <?php echo e($user['level']); ?>
                        </span>
                    </p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <?php if ($needsSetup): ?>
                    <p class="small text-muted">
                        <?php echo e(lang('otp.setup_help', 'Scan this QR code with Google Authenticator, Microsoft Authenticator or a similar app, then enter the 6-digit code.')); ?>
                    </p>
                    <style>
                        .otp-qr, .otp-qr td { border-collapse: collapse !important; border-spacing: 0 !important; }
                        .otp-qr td { padding: 0 !important; margin: 0 !important; border: 0 !important; }
                    </style>
                    <div class="text-center mb-3">
                        <div class="d-inline-block bg-white p-3 border rounded">
                            <?php echo $qrHtml; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted"><?php echo e(lang('otp.secret_label', 'Or enter this key manually')); ?></label>
                        <input class="form-control font-monospace text-center" value="<?php echo e($secret); ?>" readonly
                               onclick="this.select()">
                    </div>
                <?php else: ?>
                    <p class="small text-muted text-center">
                        <?php echo e(lang('otp.subtitle', 'Enter the 6-digit code from your authenticator app.')); ?>
                    </p>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="verify_otp">

                    <div class="mb-3">
                        <label class="form-label"><?php echo e(lang('otp.code', 'Authentication code')); ?></label>
                        <input class="form-control form-control-lg text-center font-monospace"
                               name="otp_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                               autocomplete="one-time-code" required autofocus>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-shield-check me-1"></i><?php echo e(lang('otp.verify', 'Verify')); ?>
                        </button>
                        <a class="btn btn-outline-secondary" href="<?php echo e(url('index.php?module=otp&action=cancel')); ?>">
                            <?php echo e(lang('otp.cancel', 'Back to sign in')); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
