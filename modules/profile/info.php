<?php
/**
 * Module: My Profile — view and update own profile (name, username, password).
 * Available to any logged-in user.
 */

return array(
    'name'        => 'My profile',
    'name_ar'     => 'الملف الشخصي',
    'description' => 'View and update your profile information.',
    'icon'        => 'bi-person-circle',
    'order'       => 15,
    'menu'        => false, // shown in the user dropdown menu instead
    'layout'      => 'app',
    'permission'  => null, // any logged-in user
);
