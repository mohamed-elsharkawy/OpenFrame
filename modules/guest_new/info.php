<?php
/**
 * Module: New Guest — registers an incoming guest.
 * Record ID is read-only (next auto-increment number, assigned on save).
 * The ID/passport image is uploaded to uploads/YYYY/MM/DD/ (created
 * automatically on first use).
 */

return array(
    'name'        => 'New Guest',
    'name_ar'     => 'تسجيل ضيف جديد',
    'description' => 'Register an incoming guest with an optional ID/passport copy.',
    'icon'        => 'bi-person-plus-fill',
    'order'       => 20,
    'menu'        => true,
    'layout'      => 'app',
    'permission'  => 'guest_create',
);
