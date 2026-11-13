<?php
/**
 * One-time script: re-encrypt all user passwords from bcrypt hash to
 * reversible AES-256-CBC encryption using encrypt_password().
 *
 * Run once from the command line:
 *   php reencrypt_passwords.php
 */

/* The framework layout partials guard on this constant; CLI has no
   front controller, so mark the framework as booted ourselves. */
if (!defined('OPENFRAME')) {
    define('OPENFRAME', true);
}

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/db.php';
$users = db_rows('SELECT id, username FROM ' . db_table('users'));

foreach ($users as $user) {
    // All default users have the plain password "password".
    // In a real migration you would prompt for each user's password.
    $encrypted = encrypt_password('password');

    db_update(
        'users',
        ['password_hash' => $encrypted, 'updated_at' => now()],
        'id = ?',
        [(int) $user['id']]
    );

    echo "Re-encrypted user: {$user['username']}\n";
}

echo "Done. All passwords re-encrypted.\n";
